/**
 * pwa.js â€” Los Cedros
 * Gestiona: registro SW, IndexedDB, cola offline, indicador de red, install prompt
 */
(function () {
  'use strict';

  const BASE = window.BASE_URL || '';
  const BASE_LIMPIO = String(BASE || '').replace(/\/$/, '');
  const SW_BASE_PATH = new URL((BASE_LIMPIO || '') + '/', window.location.origin).pathname.replace(/\/$/, '');
  const SW_URL = `${window.location.origin}${SW_BASE_PATH}/service-worker.js`;
  const SW_SCOPE = `${SW_BASE_PATH || ''}/`;
  const DB_VERSION = 5;           // v5: snapshots de caja, categorias y tarifas para modo offline
  let db = null;
  let missingContextWarned = false;

  function sanitizeStorageScope(value) {
    return String(value || '')
      .trim()
      .toLowerCase()
      .replace(/[^a-z0-9_-]+/g, '-')
      .replace(/-+/g, '-')
      .replace(/^-|-$/g, '');
  }

  function resolveOfflineStorageContext() {
    const context = window.MEDISOFT_CONTEXT || null;
    const rawScope = context?.storage_scope || context?.hotel_scope || (context?.hotel_id ? `hotel-${context.hotel_id}` : '');
    const scope = sanitizeStorageScope(rawScope);

    if (!context || !scope) {
      return null;
    }

    return {
      context,
      scope,
      dbName: `loscedros-db-${scope}`,
    };
  }

  // El contexto frontend solo separa storage local; la autorizacion real sigue en servidor.
  const OFFLINE_STORAGE_CONTEXT = resolveOfflineStorageContext();
  const DB_NAME = OFFLINE_STORAGE_CONTEXT?.dbName || null;
  const LEGACY_DB_NAME = 'loscedros-db';
  const STORAGE_SCOPE_KEY = 'loscedros_offline_storage_scope';
  const STORAGE_DB_KEY = 'loscedros_offline_db_name';
  const KNOWN_SESSION_KEYS = [
    'loscedros_sw_controller_reload',
    'sw_cache_list',
    'loscedros_pwa_update_banner_seen',
  ];
  const UPDATE_BANNER_SESSION_KEY = 'loscedros_pwa_update_banner_seen';

  function hasOfflineStorageContext() {
    return Boolean(DB_NAME);
  }

  function warnMissingOfflineContext() {
    if (missingContextWarned) return;
    missingContextWarned = true;
    console.warn('[PWA] Offline storage deshabilitado: falta MEDISOFT_CONTEXT.storage_scope/hotel_id.');
  }

  function safeLocalStorageGet(key) {
    try {
      return window.localStorage?.getItem(key) || null;
    } catch {
      return null;
    }
  }

  function safeLocalStorageSet(key, value) {
    try {
      window.localStorage?.setItem(key, value);
    } catch {}
  }

  function safeLocalStorageRemove(key) {
    try {
      window.localStorage?.removeItem(key);
    } catch {}
  }

  function safeSessionStorageRemove(key) {
    try {
      window.sessionStorage?.removeItem(key);
    } catch {}
  }

  function safeSessionStorageGet(key) {
    try {
      return window.sessionStorage?.getItem(key) || null;
    } catch {
      return null;
    }
  }

  function safeSessionStorageSet(key, value) {
    try {
      window.sessionStorage?.setItem(key, value);
    } catch {}
  }

  function rememberCurrentStorageScope() {
    if (!hasOfflineStorageContext()) return;
    safeLocalStorageSet(STORAGE_SCOPE_KEY, OFFLINE_STORAGE_CONTEXT.scope);
    safeLocalStorageSet(STORAGE_DB_KEY, DB_NAME);
  }

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 1. SERVICE WORKER
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  if ('serviceWorker' in navigator) {
    navigator.serviceWorker.addEventListener('controllerchange', () => {
      if (sessionStorage.getItem('loscedros_sw_controller_reload')) return;
      sessionStorage.setItem('loscedros_sw_controller_reload', '1');
      window.location.reload();
    });

    window.addEventListener('load', async () => {
      try {
        const reg = await navigator.serviceWorker.register(
          SW_URL,
          { scope: SW_SCOPE, updateViaCache: 'none' }
        );

        // Detectar nueva versiÃ³n disponible
        reg.addEventListener('updatefound', () => {
          const newWorker = reg.installing;
          if (!newWorker) return;
          newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
              showUpdateBanner(newWorker);
            }
          });
        });

        // Escuchar mensajes del SW
        navigator.serviceWorker.addEventListener('message', handleSWMessage);

        await navigator.serviceWorker.ready;
        postToSW({ type: 'CACHE_KEY_PAGES' });
        postToSW({ type: 'CACHE_PAGE', url: window.location.href });
        postToSW({ type: 'GET_CACHE_LIST' });
        cacheOfflineBrandingAssets();

      } catch (err) {
        console.error('[PWA] Error al registrar SW:', err);
      }
    });
  }

  function postToSW(message) {
    if (!('serviceWorker' in navigator)) return;

    if (navigator.serviceWorker.controller) {
      navigator.serviceWorker.controller.postMessage(message);
      return;
    }

    navigator.serviceWorker.ready
      .then(reg => {
        const worker = reg.active || reg.waiting || reg.installing;
        if (worker) worker.postMessage(message);
      })
      .catch(() => {});
  }

  function normalizeOfflineBrandingAsset(value) {
    const raw = String(value || '').trim();
    if (!raw || /[\u0000-\u001F<>"']/.test(raw)) return '';

    try {
      const url = new URL(raw, window.location.origin);
      const scopePath = `${SW_SCOPE}`.replace(/\/$/, '/');
      const pathWithinScope = url.pathname.startsWith(scopePath)
        ? url.pathname.slice(scopePath.length)
        : url.pathname.replace(/^\/+/, '');
      const allowedPath = pathWithinScope.startsWith('uploads/branding/') ||
        pathWithinScope.startsWith('uploads/') ||
        pathWithinScope.startsWith('img/');
      const allowedExtension = /\.(png|jpe?g|webp|ico)$/i.test(url.pathname);

      if (url.origin !== window.location.origin || !allowedPath || !allowedExtension) {
        return '';
      }

      return url.pathname + url.search;
    } catch {
      return '';
    }
  }

  function cacheOfflineBrandingAssets() {
    const branding = window.MEDISOFT_OFFLINE_BRANDING || null;
    if (!branding || !window.MEDISOFT_OFFLINE_ENABLED) return;

    const assets = [
      branding.logo,
      branding.favicon,
      branding.pwaIcon192,
      branding.pwaIcon512,
    ]
      .map(normalizeOfflineBrandingAsset)
      .filter(Boolean);

    const uniqueAssets = Array.from(new Set(assets));
    if (!uniqueAssets.length) return;

    postToSW({
      type: 'CACHE_OFFLINE_BRANDING',
      assets: uniqueAssets,
    });
  }

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 2. INDEXEDDB â€” almacÃ©n local para datos offline
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  function openDB() {
    return new Promise((resolve, reject) => {
      if (!hasOfflineStorageContext()) {
        warnMissingOfflineContext();
        reject(new Error('missing_offline_storage_context'));
        return;
      }

      const req = indexedDB.open(DB_NAME, DB_VERSION);

      req.onupgradeneeded = e => {
        const db = e.target.result;

        // Cola de acciones offline (formularios genÃ©ricos)
        if (!db.objectStoreNames.contains('offline_queue')) {
          const store = db.createObjectStore('offline_queue', { keyPath: 'id', autoIncrement: true });
          store.createIndex('timestamp', 'timestamp');
        }

        // CachÃ© de datos de habitaciones
        if (!db.objectStoreNames.contains('habitaciones')) {
          db.createObjectStore('habitaciones', { keyPath: 'id' });
        }

        // CachÃ© de reservaciones activas
        if (!db.objectStoreNames.contains('reservaciones')) {
          db.createObjectStore('reservaciones', { keyPath: 'id' });
        }

        if (!db.objectStoreNames.contains('huespedes')) {
          const huespedStore = db.createObjectStore('huespedes', { keyPath: 'id' });
          huespedStore.createIndex('nombre_completo', 'nombre_completo');
          huespedStore.createIndex('telefono', 'telefono');
        }

        if (!db.objectStoreNames.contains('busqueda_global')) {
          const searchStore = db.createObjectStore('busqueda_global', { keyPath: 'key' });
          searchStore.createIndex('tipo', 'tipo');
        }

        if (!db.objectStoreNames.contains('reservaciones_busqueda')) {
          db.createObjectStore('reservaciones_busqueda', { keyPath: 'id' });
        }

        // Metadatos (Ãºltima sincronizaciÃ³n, etc.)
        if (!db.objectStoreNames.contains('meta')) {
          db.createObjectStore('meta', { keyPath: 'key' });
        }

        // v2: Cola tipada de operaciones offline (checkin, checkout, pagos, etc.)
        if (!db.objectStoreNames.contains('operaciones_offline')) {
          const opStore = db.createObjectStore('operaciones_offline', { keyPath: 'uuid' });
          opStore.createIndex('estado',    'estado');
          opStore.createIndex('timestamp', 'timestamp');
          opStore.createIndex('tipo',      'tipo');
        }

        // v5: snapshots de caja (corte + resumen), movimientos, categorias y tarifas
        if (!db.objectStoreNames.contains('caja')) {
          db.createObjectStore('caja', { keyPath: 'key' });
        }
        if (!db.objectStoreNames.contains('caja_movimientos')) {
          const movStore = db.createObjectStore('caja_movimientos', { keyPath: 'id' });
          movStore.createIndex('tipo', 'tipo');
        }
        if (!db.objectStoreNames.contains('caja_categorias')) {
          db.createObjectStore('caja_categorias', { keyPath: 'id' });
        }
        if (!db.objectStoreNames.contains('tarifas_incrementos')) {
          db.createObjectStore('tarifas_incrementos', { keyPath: 'id' });
        }
      };

      req.onsuccess = e => {
        db = e.target.result;
        resolve(db);
      };
      req.onerror = () => reject(req.error);
    });
  }

  function dbPut(storeName, data) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return Promise.reject(new Error('missing_offline_storage_context'));
    }

    return openDB().then(db => new Promise((resolve, reject) => {
      const tx  = db.transaction(storeName, 'readwrite');
      const req = tx.objectStore(storeName).put(data);
      req.onsuccess = () => resolve(req.result);
      req.onerror   = () => reject(req.error);
    }));
  }

  function dbGetAll(storeName) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return Promise.resolve([]);
    }

    return openDB().then(db => new Promise((resolve, reject) => {
      const tx  = db.transaction(storeName, 'readonly');
      const req = tx.objectStore(storeName).getAll();
      req.onsuccess = () => resolve(req.result);
      req.onerror   = () => reject(req.error);
    }));
  }

  function dbDelete(storeName, key) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return Promise.resolve();
    }

    return openDB().then(db => new Promise((resolve, reject) => {
      const tx  = db.transaction(storeName, 'readwrite');
      const req = tx.objectStore(storeName).delete(key);
      req.onsuccess = () => resolve();
      req.onerror   = () => reject(req.error);
    }));
  }

  function closeCurrentDB() {
    if (!db) return;

    db.close();
    db = null;
  }

  function deleteIndexedDBByName(name, { preserveCurrent = false } = {}) {
    return new Promise(resolve => {
      if (!name || !('indexedDB' in window)) {
        resolve(false);
        return;
      }

      if (preserveCurrent && name === DB_NAME) {
        resolve(false);
        return;
      }

      if (name === DB_NAME) {
        closeCurrentDB();
      }

      const req = indexedDB.deleteDatabase(name);
      req.onsuccess = () => resolve(true);
      req.onerror = () => resolve(false);
      req.onblocked = () => resolve(false);
    });
  }

  async function clearKnownLosCedrosCaches() {
    if (!('caches' in window)) return;

    const keys = await caches.keys();
    await Promise.all(
      keys
        .filter(key => key.startsWith('loscedros-'))
        .map(key => caches.delete(key))
    );
  }

  function clearKnownOfflineStorageKeys({ keepCurrentScope = false } = {}) {
    for (const key of KNOWN_SESSION_KEYS) {
      safeSessionStorageRemove(key);
    }

    if (!keepCurrentScope) {
      safeLocalStorageRemove(STORAGE_SCOPE_KEY);
      safeLocalStorageRemove(STORAGE_DB_KEY);
    }
  }

  async function clearOfflineStorageForScopeChange() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return;
    }

    const previousScope = safeLocalStorageGet(STORAGE_SCOPE_KEY);
    const currentScope = OFFLINE_STORAGE_CONTEXT.scope;
    const scopeChanged = Boolean(previousScope && previousScope !== currentScope);

    try {
      if (scopeChanged) {
        clearKnownOfflineStorageKeys({ keepCurrentScope: true });
        // Las pantallas guardadas del hotel anterior no deben verse en el nuevo contexto
        postToSW({ type: 'CLEAR_PAGES_CACHE' });
        console.warn('[PWA] Datos offline previos preservados por seguridad al cambiar de contexto.');
      }
    } catch (err) {
      console.warn('[PWA] No se pudo actualizar el marcador de contexto offline:', err);
    } finally {
      rememberCurrentStorageScope();
    }
  }

  // Stores que NO se borran al cerrar sesion: son trabajo del usuario, no datos
  // del hotel. `operaciones_offline` es la UNICA copia de lo que se capturo
  // antes de que se apagaran las escrituras (26-jul) — borrarla seria destruir
  // informacion que no existe en ningun otro lado.
  const STORES_QUE_SOBREVIVEN_AL_LOGOUT = ['operaciones_offline', 'offline_queue', 'meta'];

  /**
   * Borra de IndexedDB los datos del hotel (huespedes con telefono y correo,
   * reservaciones, movimientos de caja...). Antes se preservaba la base ENTERA
   * "para no perder pendientes", lo que dejaba datos personales de huespedes
   * reales en cualquier tablet o celular donde alguien hubiera entrado, sin
   * caducidad y recuperables sin contraseña. Ahora se conserva solo la cola.
   */
  async function borrarDatosDelHotelEnEsteEquipo() {
    if (!hasOfflineStorageContext()) return;

    const db = await openDB().catch(() => null);
    if (!db) return;

    const aBorrar = Array.from(db.objectStoreNames)
      .filter(nombre => !STORES_QUE_SOBREVIVEN_AL_LOGOUT.includes(nombre));

    if (!aBorrar.length) { db.close(); return; }

    await new Promise(resolve => {
      let tx;
      try {
        tx = db.transaction(aBorrar, 'readwrite');
      } catch {
        resolve();
        return;
      }
      aBorrar.forEach(nombre => { try { tx.objectStore(nombre).clear(); } catch {} });
      tx.oncomplete = resolve;
      tx.onerror = resolve;
      tx.onabort = resolve;
    });

    db.close();
    console.warn(`[PWA] Datos del hotel borrados de este equipo (${aBorrar.length} almacenes).`);
  }

  async function clearLocalPrivateData() {
    try {
      clearKnownOfflineStorageKeys({ keepCurrentScope: true });
      // El HTML guardado de pantallas contiene datos del usuario/hotel: fuera al cerrar sesion.
      postToSW({ type: 'CLEAR_PAGES_CACHE' });
      // Y los datos personales de la base local tambien (la cola sobrevive).
      await borrarDatosDelHotelEnEsteEquipo();
    } catch (err) {
      console.warn('[PWA] No se pudieron limpiar los datos locales:', err);
    }
  }

  async function countPendingOfflineData() {
    try {
      const [offlineQueue, typedOperations] = await Promise.all([
        dbGetAll('offline_queue'),
        dbGetAll('operaciones_offline'),
      ]);
      const pendingTypedOperations = typedOperations
        .filter(item => item && item.estado !== 'sincronizado');

      return {
        offlineQueue: offlineQueue.length,
        operacionesOffline: pendingTypedOperations.length,
        total: offlineQueue.length + pendingTypedOperations.length,
      };
    } catch (err) {
      console.warn('[PWA] No se pudieron verificar pendientes offline:', err);
      return null;
    }
  }

  async function manualOfflineDataCleanup() {
    if (!hasOfflineStorageContext() || !DB_NAME) {
      warnMissingOfflineContext();
      showToast('No hay datos guardados en este equipo para borrar.', 'warning');
      return;
    }

    const pending = await countPendingOfflineData();
    if (!pending) {
      if (window.msToast) {
        window.msToast('warning', 'Copia local', 'No pudimos verificar si hay cambios sin enviar. Por seguridad no se borró nada.');
      } else {
        window.alert('No pudimos verificar si hay cambios sin enviar. Por seguridad no se borró nada.');
      }
      return;
    }

    const baseMessage = [
      'Esto solo borra la copia guardada en este equipo.',
      'No se borra nada del hotel en el servidor.',
    ].join('\n');

    if (pending.total > 0) {
      const totalCambios = pending.operacionesOffline + pending.offlineQueue;
      const cambiosLabel = totalCambios === 1
        ? 'Hay 1 cambio hecho sin internet (reservación, cobro, limpieza...) que AÚN NO se envía al servidor.'
        : `Hay ${totalCambios} cambios hechos sin internet (reservaciones, cobros, limpiezas...) que AÚN NO se envían al servidor.`;
      const warning = [
        cambiosLabel,
        'Si borras ahora, esos cambios se perderán para siempre.',
        '',
        baseMessage,
        '',
        'Escribe LIMPIAR para confirmar.'
      ].join('\n');
      const typedConfirmation = window.prompt(warning, '');

      if (typedConfirmation !== 'LIMPIAR') {
        showToast('No se borró nada.', 'info');
        return;
      }
    } else {
      const confirmed = window.confirm(
        `${baseMessage}\n\nNo hay cambios pendientes de enviar.\n\n` +
        '¿Borrar la copia local de este equipo?'
      );

      if (!confirmed) {
        showToast('No se borró nada.', 'info');
        return;
      }
    }

    const deleted = await deleteIndexedDBByName(DB_NAME);
    if (!deleted) {
      showToast('No se pudo borrar la copia local. Cierra otras pestañas e intenta de nuevo.', 'error');
      return;
    }

    clearKnownOfflineStorageKeys({ keepCurrentScope: true });
    rememberCurrentStorageScope();
    showToast('Copia local de este equipo borrada.', 'success');
  }

  clearOfflineStorageForScopeChange();

  // Exponer la API de datos para que otras partes de la app la puedan usar
  window.LosCedrosDB = {
    name: DB_NAME,
    storageScope: OFFLINE_STORAGE_CONTEXT?.scope || null,
    hasContext: hasOfflineStorageContext,
    put: dbPut,
    getAll: dbGetAll,
    delete: dbDelete,
    clearLocalPrivateData,
    clearCurrentScopedData: manualOfflineDataCleanup,
  };

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 3. COLA OFFLINE â€” encola acciones cuando no hay conexiÃ³n
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

  /**
   * Intercepta formularios para encolarlos cuando no hay conexiÃ³n.
   * Uso en cualquier form: aÃ±adir data-offline-queue="true"
   */
  document.addEventListener('submit', async function (e) {
    if (!e.target.dataset.offlineQueue) return;
    // MINA: esta es una SEGUNDA cola, anterior a la tipada de offline-data.js, y
    // el candado del 26-jul (OfflineData.encolarOperacion) no la cubre. Hoy no
    // la usa ningun formulario del repo, pero esta documentada como API: el dia
    // que alguien ponga data-offline-queue creyendo que "el offline esta
    // apagado", su formulario diria "guardado" y despues lo borraria anunciando
    // exito, sin pasar por ningun guard. Se alinea con la misma promesa.
    if (window.MEDISOFT_OFFLINE_ESCRITURAS !== true) {
      console.warn('[PWA] Cola offline generica ignorada: la captura sin conexion esta apagada.');
      return;
    }
    if (navigator.onLine) return; // online â†’ enviar normal

    e.preventDefault();

    const form   = e.target;
    const data   = Object.fromEntries(new FormData(form).entries());
    const action = form.action || window.location.href;
    const method = (form.method || 'POST').toUpperCase();

    try {
      await dbPut('offline_queue', {
        url:       action,
        method:    method,
        body:      JSON.stringify(data),
        timestamp: Date.now(),
        label:     form.dataset.offlineLabel || 'Accion pendiente',
      });
    } catch (err) {
      console.warn('[PWA] No se guardo accion offline sin contexto de hotel:', err.message);
      showToast('Para trabajar sin internet, primero entra al sistema con conexión al menos una vez.', 'error');
      return;
    }

    showToast('Sin conexión: tu cambio quedó guardado. Se enviará cuando vuelva internet.', 'warning');
    updateQueueBadge();
  });

  /** Procesa y envÃ­a todas las acciones encoladas */
  async function processOfflineQueue() {
    const items = await dbGetAll('offline_queue');
    if (!items.length) return;

    updateSyncIndicator(true);
    let synced = 0;

    for (const item of items) {
      try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const headers  = { 'Content-Type': 'application/x-www-form-urlencoded' };
        if (csrfMeta) headers['X-CSRF-Token'] = csrfMeta.content;

        const body = new URLSearchParams(JSON.parse(item.body || '{}')).toString();
        const res  = await fetch(item.url, { method: item.method, headers, body });

        if (res.ok) {
          await dbDelete('offline_queue', item.id);
          synced++;
        }
      } catch (err) {
        console.warn('[PWA] No se pudo sincronizar:', item.label, err);
      }
    }

    updateSyncIndicator(false);
    updateQueueBadge();

    if (synced > 0) {
      showToast(synced === 1 ? '1 cambio enviado correctamente.' : `${synced} cambios enviados correctamente.`, 'success');
      setTimeout(() => window.location.reload(), 1500);
    }
  }

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 4. ESTADO DE RED
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // Rastrea si la Ãºltima vez que cargÃ³ la pÃ¡gina ya habÃ­a conexiÃ³n
  // para no mostrar "ConexiÃ³n restaurada" en cada navegaciÃ³n normal.
  let _onlineConfirmado = navigator.onLine;
  // Arranca "recien medido" A PROPOSITO: el handleNetworkChange del
  // DOMContentLoaded caia en el throttle... con 0 NO caia, y disparaba un ping a
  // /api/buscar (2 JOIN + 3 GROUP_CONCAT + 3 LIKE) en CADA carga de pagina, para
  // todo usuario, usara o no el offline. Es informacion que ya tenemos: si esta
  // pagina llego del servidor hay red, y si vino del cache el propio SW nos
  // manda 'OFFLINE'. Los eventos de red, hayConexionAhora() y la reverificacion
  // periodica fuerzan la medicion, asi que no se pierde ninguna deteccion real.
  let _ultimaPruebaConexion = Date.now();
  let _estadoRedAnterior = _onlineConfirmado; // true = online al arrancar

  async function detectarConexionReal(forzar = false) {
    const ahora = Date.now();
    if (!forzar && ahora - _ultimaPruebaConexion < 3000) {
      return _onlineConfirmado;
    }

    _ultimaPruebaConexion = ahora;

    try {
      const res = await fetch(`${BASE}/api/buscar?q=__ping__&t=${ahora}`, {
        method: 'GET',
        cache: 'no-store',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
        },
      });

      _onlineConfirmado = res.status !== 503;
    } catch {
      _onlineConfirmado = false;
    }

    // Mientras el estado sea "sin conexion" hay que SEGUIR midiendo: el navegador
    // no dispara 'online' cuando la interfaz nunca se cayo (servidor que se
    // reinicia, cambio de antena, wifi sin salida), asi que sin este reintento
    // la foto se queda en false PARA SIEMPRE y bloquea guardados con internet
    // perfecto — pasaba al registrar un huesped o cobrar en Caja (jul-30).
    if (_onlineConfirmado) {
      detenerReverificacionRed();
    } else {
      programarReverificacionRed();
    }

    return _onlineConfirmado;
  }

  const REVERIFICAR_RED_MS = 15000;
  let _reintentoRed = null;

  function programarReverificacionRed() {
    if (_reintentoRed) return;

    _reintentoRed = setInterval(async () => {
      if (_onlineConfirmado) {
        detenerReverificacionRed();
        return;
      }
      if (await detectarConexionReal(true)) {
        handleNetworkChange(); // repinta banner/indicadores y avisa a las vistas
      }
    }, REVERIFICAR_RED_MS);
  }

  function detenerReverificacionRed() {
    if (!_reintentoRed) return;
    clearInterval(_reintentoRed);
    _reintentoRed = null;
  }

  /**
   * ¿Se puede hablar con el servidor AHORA? Para decisiones que le cuestan
   * trabajo al usuario (guardar un huesped, cobrar), no basta `isOnline()`:
   * eso es una foto que pudo tomarse hace minutos. Camino normal sin latencia
   * (la foto dice que si) y medicion real solo cuando dice que no.
   */
  async function hayConexionAhora() {
    if (!navigator.onLine) return false;
    if (_onlineConfirmado) return true;
    return await detectarConexionReal(true);
  }

  async function handleNetworkChange(evento) {
    const isOnline = await detectarConexionReal(evento instanceof Event);
    const esCambioReal = (evento instanceof Event); // false si es la llamada inicial

    document.documentElement.classList.toggle('is-offline', !isOnline);
    document.documentElement.classList.toggle('is-online',   isOnline);
    window.dispatchEvent(new CustomEvent('loscedros:network-change', { detail: { online: isOnline } }));

    const banner = document.getElementById('pwa-offline-banner');
    if (banner) {
      banner.hidden = true;
      banner.classList.remove('visible');
    }

    // Actualizar etiqueta en sidebar
    const label = document.getElementById('sidebar-net-label');
    if (label) label.textContent = isOnline ? 'En línea' : 'Sin conexión';

    if (isOnline) {
      // Solo mostrar toast si antes estÃ¡bamos offline (cambio real de estado)
      if (esCambioReal && !_estadoRedAnterior) {
        showToast('Conexión restaurada', 'success');
        processOfflineQueue();
        if ('serviceWorker' in navigator && navigator.serviceWorker.controller) {
          if ('SyncManager' in window) {
            navigator.serviceWorker.ready.then(reg =>
              reg.sync.register('sync-pending-actions').catch(() => {})
            );
          }
        }
      }
    } else {
      if (esCambioReal) {
        // El texto depende de si la captura offline esta viva. Hoy esta apagada
        // (/api/sync = 423): prometer que "tus cambios se guardan" es falso.
        showToast(
          window.OfflineData?.escriturasHabilitadas?.() === true
            ? 'Trabajando sin internet. Tus cambios se guardan en este equipo.'
            : 'Sin internet. Puedes consultar lo ya cargado; para guardar hace falta conexión.',
          'info',
          5000
        );
      }
    }

    _estadoRedAnterior = isOnline;
  }

  window.addEventListener('online',  handleNetworkChange);
  window.addEventListener('offline', handleNetworkChange);

  // Volver a la pestaña es la señal mas barata de "puede que ya haya red":
  // el hotelero deja el celular, atiende, regresa y espera poder guardar.
  document.addEventListener('visibilitychange', () => {
    if (!document.hidden && !_onlineConfirmado) handleNetworkChange();
  });

  // Inicializar estado (sin mostrar toasts)
  document.addEventListener('DOMContentLoaded', () => {
    handleNetworkChange({}); // objeto vacÃ­o, no es instancia de Event â†’ sin toast
    updateQueueBadge();
    postToSW({ type: 'CACHE_KEY_PAGES' });
    postToSW({ type: 'CACHE_PAGE', url: window.location.href });
  });

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 5. INSTALL PROMPT (botÃ³n "Instalar app")
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  let deferredPrompt = null;

  window.addEventListener('beforeinstallprompt', e => {
    e.preventDefault();
    deferredPrompt = e;

    // Mostrar botÃ³n de instalaciÃ³n
    const btn = document.getElementById('pwa-install-btn');
    if (btn) {
      btn.classList.remove('hidden');
      btn.addEventListener('click', triggerInstall);
    }
  });

  window.addEventListener('appinstalled', () => {
    deferredPrompt = null;
    const btn = document.getElementById('pwa-install-btn');
    if (btn) btn.classList.add('hidden');
    showToast('App instalada correctamente.', 'success');
    autoActivarPushDispositivo('appinstalled');
  });

  window.triggerInstall = function () {
    if (!deferredPrompt) return;
    deferredPrompt.prompt();
    deferredPrompt.userChoice.then(result => {
      if (result.outcome === 'accepted') {
        console.log('[PWA] Usuario aceptÃ³ instalar');
      }
      deferredPrompt = null;
    });
  };

  // Modo standalone
  if (window.matchMedia('(display-mode: standalone)').matches) {
    document.body.classList.add('pwa-standalone');
  }

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 6. MENSAJES DEL SERVICE WORKER
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  function handleSWMessage(event) {
    const { type } = event.data || {};
    switch (type) {
      case 'ONLINE':
        if (!_onlineConfirmado) handleNetworkChange(); // reconciliar si el SW detecta antes
        break;
      case 'OFFLINE':
        if (_onlineConfirmado) handleNetworkChange();
        break;
      case 'PROCESS_QUEUE':
        if (_onlineConfirmado) processOfflineQueue();
        break;
      case 'CACHE_LIST':
        // Guardar lista para offline.html
        try {
          sessionStorage.setItem('sw_cache_list', JSON.stringify(event.data.urls || []));
        } catch {}
        break;
    }
  }

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 7. BANNER DE ACTUALIZACIÃ“N
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  function showUpdateBanner(newWorker) {
    const banner = document.getElementById('pwa-update-banner');
    if (!banner) return;
    if (safeSessionStorageGet(UPDATE_BANNER_SESSION_KEY) === '1') return;

    safeSessionStorageSet(UPDATE_BANNER_SESSION_KEY, '1');
    banner.hidden = false;
    banner.classList.add('visible');
    const btn = banner.querySelector('[data-action="update"]');
    if (btn) {
      btn.onclick = () => {
        newWorker.postMessage({ type: 'SKIP_WAITING' });
        window.location.reload();
      };
    }
  }

  // ==========================================================================
  // 8. PUSH PWA POR DISPOSITIVO
  // ==========================================================================
  let pushClientConfig = null;

  function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content || '';
  }

  function isIosDevice() {
    return /iphone|ipad|ipod/i.test(window.navigator.userAgent || '');
  }

  function isStandalonePwa() {
    return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
  }

  function pushSupported() {
    return 'serviceWorker' in navigator &&
      'PushManager' in window &&
      'Notification' in window;
  }

  function base64UrlToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = window.atob(base64);
    const output = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; i++) {
      output[i] = rawData.charCodeAt(i);
    }

    return output;
  }

  async function pushJson(url, payload = {}) {
    const response = await fetch(url, {
      method: 'POST',
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-Token': csrfToken(),
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.success === false) {
      throw new Error(data.message || 'No se pudo completar la accion.');
    }

    return data;
  }

  async function loadPushConfig(panel) {
    if (pushClientConfig) return pushClientConfig;

    const publicKeyUrl = panel?.dataset?.publicKeyUrl || `${BASE}/api/pwa-push/public-key`;
    const response = await fetch(publicKeyUrl, {
      credentials: 'same-origin',
      cache: 'no-store',
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    pushClientConfig = await response.json();
    return pushClientConfig;
  }

  function setPushPanelState(panel, state, message) {
    if (!panel) return;

    const button = panel.querySelector('[data-pwa-push-toggle]');
    const testButton = panel.querySelector('[data-pwa-push-test]');
    const status = panel.querySelector('[data-pwa-push-status]');

    panel.dataset.pushState = state;
    if (status) status.textContent = message || '';

    if (button) {
      button.disabled = state === 'loading' ||
        state === 'unsupported' ||
        state === 'blocked' ||
        state === 'unconfigured' ||
        state === 'disabled';
      button.dataset.mode = state === 'enabled' ? 'disable' : 'enable';
      const label = button.querySelector('[data-pwa-push-label]');
      if (label) {
        label.textContent = state === 'enabled'
          ? 'Desactivar en este dispositivo'
          : 'Activar en este dispositivo';
      }
    }

    if (testButton) {
      testButton.hidden = state !== 'enabled';
      testButton.disabled = state !== 'enabled';
    }
  }

  async function refreshPushPanel(panel) {
    if (!panel) return;

    if (!pushSupported()) {
      const message = isIosDevice() && !isStandalonePwa()
        ? 'En iPhone debes agregar la app a pantalla de inicio para recibir avisos.'
        : 'Este navegador no soporta notificaciones push PWA.';
      setPushPanelState(panel, 'unsupported', message);
      return;
    }

    setPushPanelState(panel, 'loading', 'Revisando estado de este dispositivo...');

    try {
      const config = await loadPushConfig(panel);
      if (!config.enabled || !config.public_key) {
        setPushPanelState(panel, config.configured ? 'disabled' : 'unconfigured', config.message || 'Push no disponible.');
        return;
      }

      if (Notification.permission === 'denied') {
        setPushPanelState(panel, 'blocked', 'El navegador bloqueo los permisos. Activalos desde la configuracion del sitio.');
        return;
      }

      const registration = await navigator.serviceWorker.ready;
      const subscription = await registration.pushManager.getSubscription();

      if (subscription) {
        setPushPanelState(panel, 'enabled', 'Este dispositivo ya recibe avisos del hotel.');
      } else {
        setPushPanelState(panel, 'available', config.message || 'Puedes activar avisos en este dispositivo.');
      }
    } catch (error) {
      setPushPanelState(panel, 'error', error.message || 'No se pudo revisar si este equipo recibe avisos.');
    }
  }

  async function enablePushForPanel(panel) {
    const config = await loadPushConfig(panel);
    if (!config.enabled || !config.public_key) {
      throw new Error(config.message || 'Push no disponible.');
    }

    const permission = await Notification.requestPermission();
    if (permission !== 'granted') {
      throw new Error('Permiso no concedido por el navegador.');
    }

    const registration = await navigator.serviceWorker.ready;
    const existing = await registration.pushManager.getSubscription();
    const subscription = existing || await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: base64UrlToUint8Array(config.public_key),
    });

    await pushJson(panel.dataset.subscribeUrl || `${BASE}/api/pwa-push/subscribe`, {
      subscription: subscription.toJSON(),
    });

    safeLocalStorageRemove(PUSH_OPTOUT_KEY);
    safeLocalStorageSet(PUSH_ENDPOINT_KEY, pushMarcadorRegistro(subscription));

    return subscription;
  }

  async function disablePushForPanel(panel) {
    const registration = await navigator.serviceWorker.ready;
    const subscription = await registration.pushManager.getSubscription();

    if (subscription) {
      await pushJson(panel.dataset.unsubscribeUrl || `${BASE}/api/pwa-push/unsubscribe`, {
        endpoint: subscription.endpoint,
      });
      await subscription.unsubscribe();
    }

    // Decisión explícita del usuario: la activación automática la respeta.
    safeLocalStorageSet(PUSH_OPTOUT_KEY, '1');
    safeLocalStorageRemove(PUSH_ENDPOINT_KEY);
  }

  // ── 8b. Activación automática por dispositivo ──
  // Contrato: al abrir la app instalada (o recién instalarla) el dispositivo
  // queda suscrito solo. Si el usuario apagó push a mano en este equipo
  // (marcador local), la automática NO lo vuelve a prender.
  const PUSH_OPTOUT_KEY = 'loscedros_push_optout';
  const PUSH_ENDPOINT_KEY = 'loscedros_push_endpoint_registrado';
  const PUSH_AUTO_SESSION_KEY = 'loscedros_push_auto_pedido';
  let pushGestureArmado = false;

  function pushMarcadorRegistro(subscription) {
    const usuarioId = window.MEDISOFT_CONTEXT?.usuario_id || '';
    return `${subscription.endpoint}|${usuarioId}`;
  }

  async function registrarPushEnServidor(subscription) {
    await pushJson(`${BASE}/api/pwa-push/subscribe`, {
      subscription: subscription.toJSON(),
    });
    safeLocalStorageSet(PUSH_ENDPOINT_KEY, pushMarcadorRegistro(subscription));
  }

  async function suscribirYRegistrarPush(registration, config) {
    const existente = await registration.pushManager.getSubscription();
    const subscription = existente || await registration.pushManager.subscribe({
      userVisibleOnly: true,
      applicationServerKey: base64UrlToUint8Array(config.public_key),
    });
    await registrarPushEnServidor(subscription);
    return subscription;
  }

  // Safari/iOS solo permite pedir el permiso dentro de un gesto del usuario:
  // el primer toque en la app dispara la solicitud.
  function armarActivacionPushPorGesto(registration, config) {
    if (pushGestureArmado) return;
    pushGestureArmado = true;

    document.addEventListener('pointerup', async () => {
      try {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') return;
        await suscribirYRegistrarPush(registration, config);
        showToast('Notificaciones del hotel activadas en este dispositivo.', 'success');
      } catch (err) {
        console.warn('[PWA] Push: no se pudo activar tras el gesto:', err);
      }
    }, { capture: true, once: true });
  }

  async function autoActivarPushDispositivo(origen) {
    try {
      if (!pushSupported()) return;
      if (!window.MEDISOFT_CONTEXT) return; // sin sesión de hotel no hay a quién suscribir
      if (safeLocalStorageGet(PUSH_OPTOUT_KEY) === '1') return;
      if (Notification.permission === 'denied') return;

      const registration = await navigator.serviceWorker.ready;
      const existente = await registration.pushManager.getSubscription();

      // Ya suscrito y registrado para este usuario: nada que hacer (sin red).
      if (existente && safeLocalStorageGet(PUSH_ENDPOINT_KEY) === pushMarcadorRegistro(existente)) {
        return;
      }

      const config = await loadPushConfig(null);
      if (!config || !config.enabled || !config.public_key) return;

      if (existente || Notification.permission === 'granted') {
        // Reinstalación, cambio de usuario o permiso ya concedido: en silencio.
        await suscribirYRegistrarPush(registration, config);
        return;
      }

      // Permiso aún no pedido: solo en la app instalada (o recién instalada).
      if (!isStandalonePwa() && origen !== 'appinstalled') return;

      if (safeSessionStorageGet(PUSH_AUTO_SESSION_KEY) === '1') return;

      let permission = null;
      try {
        permission = await Notification.requestPermission();
        // Guard por sesión solo si el navegador aceptó mostrar la solicitud.
        safeSessionStorageSet(PUSH_AUTO_SESSION_KEY, '1');
      } catch {
        permission = null; // exige gesto (Safari/iOS): cae al plan B
      }

      if (permission === 'granted') {
        await suscribirYRegistrarPush(registration, config);
        showToast('Notificaciones del hotel activadas en este dispositivo.', 'success');
        return;
      }

      if (permission === null && Notification.permission === 'default') {
        armarActivacionPushPorGesto(registration, config);
      }
    } catch (err) {
      console.warn('[PWA] Push automático no disponible:', err?.message || err);
    }
  }

  function initPushControls() {
    document.querySelectorAll('[data-pwa-push-panel]').forEach(panel => {
      if (panel.dataset.pushBound === '1') {
        refreshPushPanel(panel);
        return;
      }

      panel.dataset.pushBound = '1';
      refreshPushPanel(panel);

      panel.querySelector('[data-pwa-push-toggle]')?.addEventListener('click', async () => {
        const state = panel.dataset.pushState;
        setPushPanelState(panel, 'loading', state === 'enabled' ? 'Desactivando avisos...' : 'Activando avisos...');

        try {
          if (state === 'enabled') {
            await disablePushForPanel(panel);
            showToast('Notificaciones desactivadas en este dispositivo.', 'success');
          } else {
            await enablePushForPanel(panel);
            showToast('Notificaciones activadas en este dispositivo.', 'success');
          }

          pushClientConfig = null;
          await refreshPushPanel(panel);
        } catch (error) {
          showToast(error.message || 'No se pudieron activar los avisos en este equipo.', 'error', 5200);
          await refreshPushPanel(panel);
        }
      });

      panel.querySelector('[data-pwa-push-test]')?.addEventListener('click', async () => {
        setPushPanelState(panel, 'loading', 'Enviando prueba...');

        try {
          const res = await pushJson(panel.dataset.testUrl || `${BASE}/api/pwa-push/test`, {});
          showToast(res.message || 'Prueba enviada. Revisa las notificaciones del dispositivo.', 'success', 5200);
        } catch (error) {
          showToast(error.message || 'No se pudo enviar la prueba.', 'error', 5200);
        } finally {
          await refreshPushPanel(panel);
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    initPushControls();
    autoActivarPushDispositivo('arranque');
  });

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 8. HELPERS UI
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•

  /** Toast de notificaciÃ³n no intrusivo */
  function showToast(message, type = 'info', duration = 3500) {
    const container = getOrCreateToastContainer();
    const toast     = document.createElement('div');
    const icons     = { success: 'OK', warning: '!', error: 'X', info: 'i' };

    toast.className = `pwa-toast pwa-toast-${type}`;
    const icon = document.createElement('span');
    icon.className = 'pwa-toast-icon';
    icon.textContent = icons[type] || 'i';
    const msg = document.createElement('span');
    msg.className = 'pwa-toast-msg';
    msg.textContent = String(message || '');
    toast.append(icon, msg);

    container.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
      toast.classList.remove('show');
      setTimeout(() => toast.remove(), 400);
    }, duration);
  }

  function getOrCreateToastContainer() {
    let el = document.getElementById('pwa-toast-container');
    if (!el) {
      el = document.createElement('div');
      el.id = 'pwa-toast-container';
      document.body.appendChild(el);
    }
    return el;
  }

  function updateSyncIndicator(syncing) {
    const el = document.getElementById('pwa-sync-indicator');
    if (!el) return;
    el.classList.toggle('syncing', syncing);
    el.title = syncing ? 'Sincronizando...' : 'Sincronizado';
  }

  async function updateQueueBadge() {
    const badge = document.getElementById('pwa-queue-badge');
    if (!badge) return;
    try {
      const items = await dbGetAll('offline_queue');
      badge.textContent = items.length;
      badge.classList.toggle('hidden', items.length === 0);
    } catch {}
  }

  /**
   * Al cerrar sesion hay que borrar los datos de huespedes de este equipo.
   * Se detecta por la ACCION del formulario, no por su id: hay DOS formularios
   * de logout (sidebar.php ~923 en el menu movil y ~1055 en escritorio) y solo
   * el de escritorio tenia `id="logout-form"`. Un id no se puede repetir en el
   * DOM, asi que en celular —justo donde trabaja recepcion— el borrado no
   * corria NUNCA y los datos personales se quedaban en el equipo.
   */
  function esFormularioDeLogout(form) {
    if (!form || form.tagName !== 'FORM') return false;
    if (form.id === 'logout-form') return true;

    try {
      return new URL(form.action, window.location.origin).pathname.replace(/\/+$/, '').endsWith('/logout');
    } catch {
      return false;
    }
  }

  document.addEventListener('submit', event => {
    if (esFormularioDeLogout(event.target)) {
      clearLocalPrivateData();
    }
  });

  // La sesion tambien puede terminar SIN pasar por el boton: caduca por
  // inactividad y el servidor responde 401. Ahi tambien hay que limpiar.
  window.addEventListener('loscedros:sesion-expirada', () => clearLocalPrivateData());

  document.addEventListener('click', event => {
    const button = event.target?.closest?.('#manual-offline-cleanup-btn');
    if (!button) return;

    event.preventDefault();
    manualOfflineDataCleanup();
  });

  // Exponer utilidades pÃºblicas
  window.PWA = {
    showToast,
    processOfflineQueue,
    clearLocalPrivateData,
    manualOfflineDataCleanup,
    triggerInstall: window.triggerInstall,
    checkOnline: detectarConexionReal,
    isOnline: () => _onlineConfirmado,          // foto: barata, puede estar vieja
    hayConexionAhora,                            // medicion real: usar ANTES de bloquear al usuario
    initPushControls,
    refreshPushControls: () => document.querySelectorAll('[data-pwa-push-panel]').forEach(refreshPushPanel),
    autoActivarPush: autoActivarPushDispositivo,
  };

})();
