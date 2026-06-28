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
  const DB_VERSION = 4;           // v4: agrega indice global y reservaciones para busqueda offline
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

  lockMobileZoomGestures();

  function hasOfflineStorageContext() {
    return Boolean(DB_NAME);
  }

  function lockMobileZoomGestures() {
    if (!('ontouchstart' in window) && (navigator.maxTouchPoints || 0) <= 0) return;

    const prevent = event => event.preventDefault();
    document.addEventListener('gesturestart', prevent, { passive: false });
    document.addEventListener('gesturechange', prevent, { passive: false });
    document.addEventListener('gestureend', prevent, { passive: false });
    document.addEventListener('touchmove', event => {
      if (event.touches && event.touches.length > 1) {
        event.preventDefault();
      }
    }, { passive: false });

    let lastTouchEnd = 0;
    document.addEventListener('touchend', event => {
      const now = Date.now();
      if (now - lastTouchEnd <= 300) {
        event.preventDefault();
      }
      lastTouchEnd = now;
    }, { passive: false });
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
        console.warn('[PWA] Datos offline previos preservados por seguridad al cambiar de contexto.');
      }
    } catch (err) {
      console.warn('[PWA] No se pudo actualizar el marcador de contexto offline:', err);
    } finally {
      rememberCurrentStorageScope();
    }
  }

  async function clearLocalPrivateData() {
    try {
      clearKnownOfflineStorageKeys({ keepCurrentScope: true });
      console.warn('[PWA] Datos offline locales preservados por seguridad durante logout.');
    } catch (err) {
      console.warn('[PWA] No se pudieron actualizar los marcadores locales:', err);
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
      showToast('No hay contexto offline activo para limpiar.', 'warning');
      return;
    }

    const pending = await countPendingOfflineData();
    if (!pending) {
      window.alert(
        'No se pudo verificar si existen operaciones offline pendientes. ' +
        'Por seguridad no se limpiaron datos locales.'
      );
      return;
    }

    const baseMessage = [
      'Esto solo afecta este navegador o dispositivo.',
      'No elimina datos del servidor.',
      'No habilita sincronizacion offline.',
      'Se limpiara solo la base local del hotel/usuario actual.',
    ].join('\n');

    if (pending.total > 0) {
      const typedCount = pending.operacionesOffline;
      const queueCount = pending.offlineQueue;
      const typedLabel = typedCount === 1 ? 'operacion tipada' : 'operaciones tipadas';
      const queueLabel = queueCount === 1 ? 'accion generica' : 'acciones genericas';
      const warning = [
        'Hay datos offline pendientes sin sincronizar:',
        `- ${typedCount} ${typedLabel}`,
        `- ${queueCount} ${queueLabel}`,
        '',
        baseMessage,
        '',
        'Si continuas, esos datos locales podrian perderse.',
        'Escribe LIMPIAR para confirmar.'
      ].join('\n');
      const typedConfirmation = window.prompt(warning, '');

      if (typedConfirmation !== 'LIMPIAR') {
        showToast('Limpieza offline cancelada.', 'info');
        return;
      }
    } else {
      const confirmed = window.confirm(
        `${baseMessage}\n\nNo se detectaron operaciones offline pendientes.\n\n` +
        'Deseas limpiar los datos offline locales de este dispositivo?'
      );

      if (!confirmed) {
        showToast('Limpieza offline cancelada.', 'info');
        return;
      }
    }

    const deleted = await deleteIndexedDBByName(DB_NAME);
    if (!deleted) {
      showToast('No se pudo limpiar la base offline. Cierra otras pestanas e intenta de nuevo.', 'error');
      return;
    }

    clearKnownOfflineStorageKeys({ keepCurrentScope: true });
    rememberCurrentStorageScope();
    showToast('Datos offline locales limpiados en este dispositivo.', 'success');
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
      showToast('Modo offline no disponible sin contexto de hotel.', 'error');
      return;
    }

    showToast('Sin conexion: accion guardada. Se enviara cuando vuelva internet.', 'warning');
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
      showToast(`${synced} accion(es) sincronizadas correctamente.`, 'success');
      setTimeout(() => window.location.reload(), 1500);
    }
  }

  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // 4. ESTADO DE RED
  // â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
  // Rastrea si la Ãºltima vez que cargÃ³ la pÃ¡gina ya habÃ­a conexiÃ³n
  // para no mostrar "ConexiÃ³n restaurada" en cada navegaciÃ³n normal.
  let _onlineConfirmado = navigator.onLine;
  let _ultimaPruebaConexion = 0;
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

    return _onlineConfirmado;
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
    if (label) label.textContent = isOnline ? 'En linea' : 'Sin conexion';

    if (isOnline) {
      // Solo mostrar toast si antes estÃ¡bamos offline (cambio real de estado)
      if (esCambioReal && !_estadoRedAnterior) {
        showToast('Conexion restaurada', 'success');
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
        showToast('Trabajando sin internet. Tus cambios se guardan en este equipo.', 'info', 5000);
      }
    }

    _estadoRedAnterior = isOnline;
  }

  window.addEventListener('online',  handleNetworkChange);
  window.addEventListener('offline', handleNetworkChange);

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
      setPushPanelState(panel, 'error', error.message || 'No se pudo revisar Push PWA.');
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
          showToast(error.message || 'No se pudo cambiar Push PWA.', 'error', 5200);
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

  document.addEventListener('DOMContentLoaded', initPushControls);

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

  document.addEventListener('submit', event => {
    if (event.target && event.target.id === 'logout-form') {
      clearLocalPrivateData();
    }
  });

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
    isOnline: () => _onlineConfirmado,
    initPushControls,
    refreshPushControls: () => document.querySelectorAll('[data-pwa-push-panel]').forEach(refreshPushPanel),
  };

})();
