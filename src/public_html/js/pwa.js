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
  ];

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
        resolve();
        return;
      }

      if (preserveCurrent && name === DB_NAME) {
        resolve();
        return;
      }

      if (name === DB_NAME) {
        closeCurrentDB();
      }

      const req = indexedDB.deleteDatabase(name);
      req.onsuccess = () => resolve();
      req.onerror = () => resolve();
      req.onblocked = () => resolve();
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
    const previousDbName = safeLocalStorageGet(STORAGE_DB_KEY)
      || (previousScope ? `loscedros-db-${sanitizeStorageScope(previousScope)}` : null);
    const currentScope = OFFLINE_STORAGE_CONTEXT.scope;
    const dbsToDelete = new Set([LEGACY_DB_NAME]);
    const scopeChanged = Boolean(previousScope && previousScope !== currentScope);

    if (previousDbName && previousDbName !== DB_NAME) {
      dbsToDelete.add(previousDbName);
    }

    try {
      await Promise.all(
        [...dbsToDelete].map(name => deleteIndexedDBByName(name, { preserveCurrent: true }))
      );

      if (scopeChanged) {
        await clearKnownLosCedrosCaches();
        clearKnownOfflineStorageKeys({ keepCurrentScope: true });
      }
    } catch (err) {
      console.warn('[PWA] No se pudo limpiar storage offline previo:', err);
    } finally {
      rememberCurrentStorageScope();
    }
  }

  async function clearLocalPrivateData() {
    try {
      postToSW({ type: 'CLEAR_PRIVATE_DATA' });

      await clearKnownLosCedrosCaches();

      const previousDbName = safeLocalStorageGet(STORAGE_DB_KEY);
      const dbsToDelete = new Set([LEGACY_DB_NAME]);
      if (previousDbName) dbsToDelete.add(previousDbName);
      if (DB_NAME) dbsToDelete.add(DB_NAME);

      await Promise.all(
        [...dbsToDelete].map(name => deleteIndexedDBByName(name))
      );

      clearKnownOfflineStorageKeys();
    } catch (err) {
      console.warn('[PWA] No se pudieron limpiar todos los datos locales:', err);
    }
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
    banner.hidden = false;
    banner.classList.add('visible');
    const btn = banner.querySelector('[data-action="update"]');
    if (btn) {
      btn.addEventListener('click', () => {
        newWorker.postMessage({ type: 'SKIP_WAITING' });
        window.location.reload();
      });
    }
  }

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

  // Exponer utilidades pÃºblicas
  window.PWA = {
    showToast,
    processOfflineQueue,
    clearLocalPrivateData,
    triggerInstall: window.triggerInstall,
    checkOnline: detectarConexionReal,
    isOnline: () => _onlineConfirmado,
  };

})();
