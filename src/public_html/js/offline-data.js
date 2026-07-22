/**
 * offline-data.js — Los Cedros
 *
 * Gestiona:
 *   1. Snapshots: guarda habitaciones y reservaciones en IndexedDB
 *      cuando hay internet, para leerlas cuando no hay.
 *   2. Cola tipada: encola operaciones (checkin, checkout, pagos,
 *      cambios de estado) con UUID para sincronización idempotente.
 *   3. Sincronización: envía la cola al servidor cuando regresa
 *      el internet y actualiza el estado de cada operación.
 *
 * Depende de: pwa.js (que expone window.LosCedrosDB y abre la DB)
 * Se carga DESPUÉS de pwa.js en el <head>.
 */
(function () {
  'use strict';

  const BASE = window.BASE_URL || '';

  // Intervalo de snapshot automático (15 min mientras hay internet)
  const SNAPSHOT_INTERVALO_MS = 15 * 60 * 1000;
  const BUSQUEDA_GLOBAL_LIMITE = 40;
  // Rango de reservaciones a cachear (hoy + N días) para poder validar
  // disponibilidad localmente al crear reservaciones sin internet
  const RESERVACIONES_DIAS_SNAPSHOT = 30;
  const DB_VERSION = 5;
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
      dbName: window.LosCedrosDB?.name || `loscedros-db-${scope}`,
    };
  }

  // El contexto frontend solo separa storage local; no autoriza datos en servidor.
  const OFFLINE_STORAGE_CONTEXT = resolveOfflineStorageContext();
  const DB_NAME = OFFLINE_STORAGE_CONTEXT?.dbName || null;

  function hasOfflineStorageContext() {
    return Boolean(DB_NAME);
  }

  function warnMissingOfflineContext() {
    if (missingContextWarned) return;
    missingContextWarned = true;
    console.warn('[OfflineData] Offline data deshabilitado: falta MEDISOFT_CONTEXT.storage_scope/hotel_id.');
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 1. HELPERS DE INDEXEDDB
  //    Trabajan sobre la misma DB scoped que pwa.js.
  //    Usamos window.LosCedrosDB.put/getAll/delete para los stores
  //    existentes y añadimos acceso directo a operaciones_offline.
  // ═══════════════════════════════════════════════════════════════════════════

  /** Abre la DB (la misma que pwa.js — ya tiene todos los stores). */
  function abrirDB() {
    return new Promise((resolve, reject) => {
      if (!hasOfflineStorageContext()) {
        warnMissingOfflineContext();
        reject(new Error('missing_offline_storage_context'));
        return;
      }

      const req = indexedDB.open(DB_NAME, DB_VERSION);
      req.onupgradeneeded = e => {
        const db = e.target.result;
        if (!db.objectStoreNames.contains('huespedes')) {
          const store = db.createObjectStore('huespedes', { keyPath: 'id' });
          store.createIndex('nombre_completo', 'nombre_completo');
          store.createIndex('telefono', 'telefono');
        }
        if (!db.objectStoreNames.contains('busqueda_global')) {
          const store = db.createObjectStore('busqueda_global', { keyPath: 'key' });
          store.createIndex('tipo', 'tipo');
        }
        if (!db.objectStoreNames.contains('reservaciones_busqueda')) {
          db.createObjectStore('reservaciones_busqueda', { keyPath: 'id' });
        }
        // v5: espejo defensivo de los stores que crea pwa.js
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
      req.onsuccess = e => resolve(e.target.result);
      req.onerror   = () => reject(req.error);
      // onupgradeneeded lo maneja pwa.js (que carga primero)
    });
  }

  function _txPut(storeName, data) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return Promise.reject(new Error('missing_offline_storage_context'));
    }

    return abrirDB().then(db => new Promise((resolve, reject) => {
      const tx  = db.transaction(storeName, 'readwrite');
      const req = tx.objectStore(storeName).put(data);
      req.onsuccess = () => resolve(req.result);
      req.onerror   = () => reject(req.error);
    }));
  }

  function _txGetAll(storeName, indexName, valor) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return Promise.resolve([]);
    }

    return abrirDB().then(db => new Promise((resolve, reject) => {
      const tx    = db.transaction(storeName, 'readonly');
      const store = tx.objectStore(storeName);
      const req   = indexName
        ? store.index(indexName).getAll(valor)
        : store.getAll();
      req.onsuccess = () => resolve(req.result);
      req.onerror   = () => reject(req.error);
    }));
  }

  function _txGet(storeName, key) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return Promise.resolve(null);
    }

    return abrirDB().then(db => new Promise((resolve, reject) => {
      const tx  = db.transaction(storeName, 'readonly');
      const req = tx.objectStore(storeName).get(key);
      req.onsuccess = () => resolve(req.result || null);
      req.onerror   = () => reject(req.error);
    }));
  }

  function _txDelete(storeName, key) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return Promise.resolve();
    }

    return abrirDB().then(db => new Promise((resolve, reject) => {
      const tx  = db.transaction(storeName, 'readwrite');
      const req = tx.objectStore(storeName).delete(key);
      req.onsuccess = () => resolve();
      req.onerror   = () => reject(req.error);
    }));
  }

  function _txClear(storeName) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return Promise.resolve();
    }

    return abrirDB().then(db => new Promise((resolve, reject) => {
      const tx  = db.transaction(storeName, 'readwrite');
      const req = tx.objectStore(storeName).clear();
      req.onsuccess = () => resolve();
      req.onerror   = () => reject(req.error);
    }));
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 2. GENERACIÓN DE UUID v4
  //    Usa crypto.randomUUID() si está disponible (Chrome 92+, Firefox 95+).
  //    Fallback manual para Safari < 15.4.
  // ═══════════════════════════════════════════════════════════════════════════

  function generarUUID() {
    if (typeof crypto !== 'undefined' && crypto.randomUUID) {
      return crypto.randomUUID();
    }
    // Fallback RFC 4122 v4
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
      const r = (Math.random() * 16) | 0;
      return (c === 'x' ? r : (r & 0x3) | 0x8).toString(16);
    });
  }

  function fechaISO(d = new Date()) {
    const copia = new Date(d);
    copia.setMinutes(copia.getMinutes() - copia.getTimezoneOffset());
    return copia.toISOString().slice(0, 10);
  }

  function fechaConOffset(dias = 0) {
    const d = new Date();
    d.setDate(d.getDate() + dias);
    return fechaISO(d);
  }

  function traslapa(fechaEntradaA, fechaSalidaA, fechaEntradaB, fechaSalidaB) {
    if (!fechaEntradaA || !fechaSalidaA || !fechaEntradaB || !fechaSalidaB) return false;
    return fechaEntradaA < fechaSalidaB && fechaSalidaA > fechaEntradaB;
  }

  function normalizarIdsHabitaciones(valor) {
    if (Array.isArray(valor)) return valor.map(v => Number(v)).filter(Boolean);
    return String(valor || '')
      .split(',')
      .map(v => Number(String(v).trim()))
      .filter(Boolean);
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 3. SNAPSHOTS — capturas periódicas cuando hay internet
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Descarga el estado actual de habitaciones desde el servidor
   * y lo guarda en IndexedDB. Actualiza meta.ultima_sync_habitaciones.
   */
  async function capturarHabitaciones(fechaEntrada = fechaConOffset(0), fechaSalida = fechaConOffset(1)) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return;
    }
    if (!navigator.onLine) return;

    try {
      const params = new URLSearchParams({
        fecha_entrada: fechaEntrada,
        fecha_salida: fechaSalida,
      });

      const res  = await fetch(`${BASE}/api/habitaciones/todas-con-ocupacion?${params.toString()}`, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      if (!json.success || !Array.isArray(json.data)) return;

      // Reemplazar snapshot completo
      await _txClear('habitaciones');
      for (const hab of json.data) {
        await _txPut('habitaciones', hab);
      }

      await _txPut('meta', {
        key:   'ultima_sync_habitaciones',
        valor: new Date().toISOString(),
        total: json.data.length,
        fecha_entrada: fechaEntrada,
        fecha_salida: fechaSalida,
      });

      console.log(`[OfflineData] Habitaciones cacheadas: ${json.data.length}`);
    } catch (err) {
      console.warn('[OfflineData] No se pudo capturar habitaciones:', err.message);
    }
  }

  /**
   * Descarga reservaciones de hoy + los próximos RESERVACIONES_DIAS_SNAPSHOT
   * días y las guarda en IndexedDB. Actualiza meta.ultima_sync_reservaciones.
   */
  async function capturarReservaciones() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return;
    }
    if (!navigator.onLine) return;

    try {
      const res  = await fetch(`${BASE}/api/reservaciones/hoy?dias=${RESERVACIONES_DIAS_SNAPSHOT}`, {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      if (!json.success || !Array.isArray(json.reservaciones)) return;

      const localesPendientes = (await _txGetAll('reservaciones'))
        .filter(r => r && r.offline_pendiente);

      await _txClear('reservaciones');
      for (const r of json.reservaciones) {
        await _txPut('reservaciones', r);
      }
      for (const r of localesPendientes) {
        await _txPut('reservaciones', r);
      }

      await _txPut('meta', {
        key:   'ultima_sync_reservaciones',
        valor: new Date().toISOString(),
        total: json.reservaciones.length + localesPendientes.length,
        fecha: json.fecha,
      });

      console.log(`[OfflineData] Reservaciones cacheadas: ${json.reservaciones.length}`);
    } catch (err) {
      console.warn('[OfflineData] No se pudo capturar reservaciones:', err.message);
    }
  }

  async function capturarHuespedes() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return;
    }
    if (!navigator.onLine) return;

    try {
      const res = await fetch(BASE + '/api/huespedes/search?q=__offline_cache__', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      if (!json.success || !Array.isArray(json.data)) return;

      await guardarHuespedes(json.data);
      await _txPut('meta', {
        key: 'ultima_sync_huespedes',
        valor: new Date().toISOString(),
        total: json.data.length,
      });
    } catch (err) {
      console.warn('[OfflineData] No se pudo capturar huespedes:', err.message);
    }
  }

  async function capturarIndiceGlobal() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return;
    }
    if (!navigator.onLine) return;

    try {
      const res = await fetch(BASE + '/api/buscar?q=__offline_cache__', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      if (!json.success) return;

      const resultados = Array.isArray(json.resultados) ? json.resultados : [];
      await _txClear('busqueda_global');
      for (let i = 0; i < resultados.length; i++) {
        const item = resultados[i];
        await _txPut('busqueda_global', {
          ...item,
          key: `${item.tipo || 'item'}:${item.url || item.titulo || i}:${i}`,
          cached_at: new Date().toISOString(),
        });
      }

      if (Array.isArray(json.reservaciones)) {
        await _txClear('reservaciones_busqueda');
        for (const r of json.reservaciones) {
          await _txPut('reservaciones_busqueda', {
            ...r,
            id: Number(r.id),
            habitaciones_ids: normalizarIdsHabitaciones(r.habitaciones_ids),
            cached_at: new Date().toISOString(),
          });
        }
      }

      if (Array.isArray(json.huespedes)) {
        await guardarHuespedes(json.huespedes);
      }

      await _txPut('meta', {
        key: 'ultima_sync_busqueda_global',
        valor: new Date().toISOString(),
        total: resultados.length,
      });
    } catch (err) {
      console.warn('[OfflineData] No se pudo capturar indice global:', err.message);
    }
  }

  /**
   * Descarga el estado de la caja (corte abierto, resumen, movimientos y
   * categorías) para poder VER la caja sin internet. Solo lectura: los
   * registros de dinero siguen siendo online-only.
   */
  async function capturarCaja() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return;
    }
    if (!navigator.onLine) return;

    try {
      const res = await fetch(BASE + '/api/caja/snapshot', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      if (!json.success) return;

      await _txPut('caja', {
        key: 'corte_actual',
        corte: json.corte || null,
        resumen: json.resumen || null,
        generado_at: json.generado_at || null,
        cached_at: new Date().toISOString(),
      });

      await _txClear('caja_movimientos');
      for (const mov of (json.movimientos || [])) {
        if (mov && mov.id) {
          await _txPut('caja_movimientos', { ...mov, id: Number(mov.id) });
        }
      }

      await _txClear('caja_categorias');
      for (const cat of (json.categorias || [])) {
        if (cat && cat.id) {
          await _txPut('caja_categorias', { ...cat, id: Number(cat.id) });
        }
      }

      await _txPut('meta', {
        key: 'ultima_sync_caja',
        valor: new Date().toISOString(),
        corte_abierto: Boolean(json.corte),
        total_movimientos: (json.movimientos || []).length,
      });

      console.log(`[OfflineData] Caja cacheada: ${(json.movimientos || []).length} movimientos`);
    } catch (err) {
      console.warn('[OfflineData] No se pudo capturar caja:', err.message);
    }
  }

  /** Descarga los incrementos de tarifa activos para cálculo local de precios. */
  async function capturarTarifas() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return;
    }
    if (!navigator.onLine) return;

    try {
      const res = await fetch(BASE + '/api/tarifas/incrementos', {
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      });
      if (!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      if (!json.success || !Array.isArray(json.incrementos_activos)) return;

      await _txClear('tarifas_incrementos');
      for (const inc of json.incrementos_activos) {
        if (inc && inc.id) {
          await _txPut('tarifas_incrementos', { ...inc, id: Number(inc.id) });
        }
      }

      await _txPut('meta', {
        key: 'ultima_sync_tarifas',
        valor: new Date().toISOString(),
        total: json.incrementos_activos.length,
      });
    } catch (err) {
      console.warn('[OfflineData] No se pudo capturar tarifas:', err.message);
    }
  }

  /** Captura todos los snapshots en paralelo. */
  async function capturarSnapshots() {
    await Promise.allSettled([
      capturarHabitaciones(),
      capturarReservaciones(),
      capturarHuespedes(),
      capturarIndiceGlobal(),
      capturarCaja(),
      capturarTarifas(),
    ]);
    _actualizarUITimestamp();
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 4. LECTURA DESDE CACHÉ
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Devuelve las habitaciones.
   * Si hay internet, actualiza el caché antes de devolver.
   * Si no hay, devuelve el último snapshot.
   */
  async function obtenerHabitaciones(fechaEntrada = fechaConOffset(0), fechaSalida = fechaConOffset(1)) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return [];
    }
    if (navigator.onLine) await capturarHabitaciones(fechaEntrada, fechaSalida);
    const habitaciones = await _txGetAll('habitaciones');
    return _habitacionesParaFechas(habitaciones, fechaEntrada, fechaSalida);
  }

  /**
   * Devuelve las reservaciones del día.
   * Mismo comportamiento: online = actualiza primero, offline = caché.
   */
  async function obtenerReservaciones() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return [];
    }
    if (navigator.onLine) await capturarReservaciones();
    return _txGetAll('reservaciones');
  }

  async function _habitacionesParaFechas(habitaciones = [], fechaEntrada, fechaSalida) {
    if (!Array.isArray(habitaciones) || !habitaciones.length) return [];

    const reservacionesDia = await _txGetAll('reservaciones').catch(() => []);
    const reservacionesIndice = await _txGetAll('reservaciones_busqueda').catch(() => []);
    const reservaciones = [...reservacionesIndice, ...reservacionesDia];

    const activas = reservaciones.filter(r =>
      r &&
      ['confirmada', 'checked_in'].includes(String(r.estado || '')) &&
      traslapa(fechaEntrada, fechaSalida, r.fecha_entrada, r.fecha_salida)
    );

    return habitaciones.map(hab => {
      const id = Number(hab.id);
      const enMantenimiento = !!hab.en_mantenimiento || hab.estado === 'mantenimiento';
      const ocupacion = activas.find(r => normalizarIdsHabitaciones(r.habitaciones_ids).includes(id));
      const room = {
        ...hab,
        ocupada: !!ocupacion && !enMantenimiento,
        en_mantenimiento: enMantenimiento,
      };

      if (room.ocupada) {
        room.info_ocupacion = {
          reservacion_id: ocupacion.id,
          estado: ocupacion.estado,
          huesped_nombre: ocupacion.huesped_nombre || 'Huesped',
          huesped_telefono: ocupacion.huesped_telefono || '',
          fecha_entrada: ocupacion.fecha_entrada,
          fecha_salida: ocupacion.fecha_salida,
          hora_entrada: ocupacion.hora_entrada || ocupacion.hora_llegada_estimada || '',
          noches_ocupadas: [],
          fechas_ocupadas: [],
          total_noches_solicitadas: Math.max(1, Math.ceil((new Date(fechaSalida) - new Date(fechaEntrada)) / 86400000)),
        };
      } else {
        room.info_ocupacion = null;
      }

      return room;
    });
  }

  async function guardarReservacionLocal(reservacion) {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      throw new Error('Offline data no disponible sin contexto de hotel');
    }

    if (!reservacion || !reservacion.id) {
      throw new Error('La reservacion local necesita un id');
    }

    const item = {
      ...reservacion,
      offline_pendiente: reservacion.offline_pendiente ?? true,
      updated_at_local: new Date().toISOString(),
    };

    await _txPut('reservaciones', item);
    return item;
  }

  async function actualizarReservacionLocal(id, cambios = {}) {
    const actual = await _txGet('reservaciones', id);
    if (!actual) {
      throw new Error(`Reservacion local ${id} no encontrada`);
    }

    return guardarReservacionLocal({
      ...actual,
      ...cambios,
      id: actual.id,
    });
  }

  async function guardarHuespedes(huespedes = []) {
    for (const huesped of huespedes) {
      if (huesped && huesped.id) {
        // Los huespedes creados offline usan id temporal string (tmp_hue_*)
        const idNumerico = Number(huesped.id);
        await _txPut('huespedes', {
          ...huesped,
          id: Number.isFinite(idNumerico) && idNumerico > 0 ? idNumerico : huesped.id,
          cached_at: new Date().toISOString(),
        });
      }
    }
  }

  async function buscarHuespedes(termino = '') {
    const texto = termino.trim().toLowerCase();
    if (texto.length < 2) return [];

    const todos = await _txGetAll('huespedes');
    return todos
      .filter(h => [
        h.nombre_completo || '',
        h.telefono || '',
        h.procedencia_estado || '',
      ].join(' ').toLowerCase().includes(texto))
      .sort((a, b) => String(a.nombre_completo || '').localeCompare(String(b.nombre_completo || ''), 'es'))
      .slice(0, 10);
  }

  async function buscarGlobal(termino = '') {
    const texto = termino.trim().toLowerCase();
    if (texto.length < 2) return [];

    const [indice, reservacionesLocales] = await Promise.all([
      _txGetAll('busqueda_global').catch(() => []),
      _txGetAll('reservaciones').catch(() => []),
    ]);

    const resultadosLocales = reservacionesLocales
      .filter(r => r && r.offline_pendiente)
      .map(r => ({
        tipo: 'reservacion',
        icono: 'fa-calendar-check',
        color: '#D97706',
        titulo: `#${r.id} - ${r.huesped_nombre || 'Reservacion pendiente'}`,
        subtitulo: `${r.habitaciones_numeros || '-'} | ${r.fecha_entrada || '-'} | pendiente de sincronizar`,
        url: String(r.id).startsWith('tmp_res_') ? 'reservaciones' : `reservaciones/ver/${r.id}`,
      }));

    const todos = [...resultadosLocales, ...indice];

    return todos
      .filter(item => [
        item.titulo || '',
        item.subtitulo || '',
        item.url || '',
        item.tipo || '',
      ].join(' ').toLowerCase().includes(texto))
      .sort((a, b) => {
        const aExacta = String(a.url || '').endsWith('/' + texto) || String(a.titulo || '').toLowerCase().startsWith('#' + texto);
        const bExacta = String(b.url || '').endsWith('/' + texto) || String(b.titulo || '').toLowerCase().startsWith('#' + texto);
        return Number(bExacta) - Number(aExacta);
      })
      .slice(0, BUSQUEDA_GLOBAL_LIMITE);
  }

  /**
   * Devuelve el snapshot de caja: corte abierto, resumen, movimientos y
   * categorías. Si hay internet actualiza primero; offline devuelve la copia.
   */
  async function obtenerCajaSnapshot() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return null;
    }
    if (navigator.onLine) await capturarCaja();

    const [estado, movimientos, categorias, meta] = await Promise.all([
      _txGet('caja', 'corte_actual'),
      _txGetAll('caja_movimientos').catch(() => []),
      _txGetAll('caja_categorias').catch(() => []),
      _txGet('meta', 'ultima_sync_caja').catch(() => null),
    ]);

    return {
      corte: estado?.corte || null,
      resumen: estado?.resumen || null,
      movimientos: movimientos.sort((a, b) => String(b.created_at || '').localeCompare(String(a.created_at || ''))),
      categorias,
      cached_at: estado?.cached_at || null,
      ultima_sync: meta?.valor || null,
    };
  }

  /**
   * Verifica contra el snapshot local (reservaciones de hoy + ~30 días) si las
   * habitaciones chocan con otra reservación en el rango de fechas dado.
   * Devuelve la lista de conflictos [{habitacion_id, reservacion_id, huesped_nombre, fechas}].
   * OJO: es una validación de "mejor esfuerzo" con datos de hasta 15 min de
   * atraso — el servidor revalida SIEMPRE al sincronizar.
   */
  async function verificarDisponibilidadLocal(habitacionIds = [], fechaEntrada, fechaSalida, excluirId = null) {
    const ids = normalizarIdsHabitaciones(habitacionIds);
    if (!ids.length || !fechaEntrada || !fechaSalida) return [];

    const [reservacionesDia, reservacionesIndice] = await Promise.all([
      _txGetAll('reservaciones').catch(() => []),
      _txGetAll('reservaciones_busqueda').catch(() => []),
    ]);

    const vistas = new Set();
    const conflictos = [];

    for (const r of [...reservacionesDia, ...reservacionesIndice]) {
      if (!r || vistas.has(String(r.id))) continue;
      vistas.add(String(r.id));

      if (excluirId && String(r.id) === String(excluirId)) continue;
      if (!['confirmada', 'checked_in'].includes(String(r.estado || ''))) continue;
      if (!traslapa(fechaEntrada, fechaSalida, r.fecha_entrada, r.fecha_salida)) continue;

      const habsReservadas = normalizarIdsHabitaciones(r.habitaciones_ids);
      for (const id of ids) {
        if (habsReservadas.includes(id)) {
          conflictos.push({
            habitacion_id: id,
            reservacion_id: r.id,
            huesped_nombre: r.huesped_nombre || 'Huésped',
            fechas: `${r.fecha_entrada} → ${r.fecha_salida}`,
          });
        }
      }
    }

    return conflictos;
  }

  /** Categorías de movimientos de caja cacheadas (para formularios offline). */
  function obtenerCategoriasCaja() {
    return _txGetAll('caja_categorias');
  }

  /** Incrementos de tarifa activos cacheados (para cálculo local de precios). */
  function obtenerIncrementosTarifa() {
    return _txGetAll('tarifas_incrementos');
  }

  /** Devuelve el metadato de última sincronización para la UI. */
  async function obtenerMetaSync() {
    const hab = await abrirDB().then(db => new Promise((resolve, reject) => {
      const req = db.transaction('meta', 'readonly').objectStore('meta').get('ultima_sync_habitaciones');
      req.onsuccess = () => resolve(req.result);
      req.onerror   = () => reject(req.error);
    })).catch(() => null);

    const res = await abrirDB().then(db => new Promise((resolve, reject) => {
      const req = db.transaction('meta', 'readonly').objectStore('meta').get('ultima_sync_reservaciones');
      req.onsuccess = () => resolve(req.result);
      req.onerror   = () => reject(req.error);
    })).catch(() => null);

    return { habitaciones: hab, reservaciones: res };
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 5. COLA DE OPERACIONES TIPADAS
  // ═══════════════════════════════════════════════════════════════════════════

  /**
   * Encola una operación para sincronizar con el servidor.
   *
   * @param {string} tipo     - 'checkin' | 'checkout' | 'cambiar_estado_habitacion' |
   *                            'crear_reservacion' | 'crear_huesped' | 'pago_caja' | 'gasto_caja'
   *                            (el dinero se sincroniza con candados desde 2026-07-08:
   *                            el servidor lo rechaza si el corte de caja cambió)
   * @param {object} payload  - Datos específicos del tipo (ver Sync.php)
   * @param {string} [label]  - Descripción legible para mostrar en UI
   * @returns {string}        - UUID de la operación creada
   */
  async function encolarOperacion(tipo, payload, label = '') {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      throw new Error('No se puede encolar operacion offline sin contexto de hotel');
    }

    const uuid = generarUUID();
    const op   = {
      uuid,
      tipo,
      payload,
      label:      label || tipo,
      timestamp:  Date.now(),
      usuario_id: window.USUARIO_ID || null,   // inyectado por el layout PHP
      estado:     'pendiente',                 // pendiente | sincronizado | error
      intentos:   0,
      error:      null,
    };

    await _txPut('operaciones_offline', op);
    _actualizarBadgePendientes();

    console.log(`[OfflineData] Operación encolada: ${tipo} (${uuid})`);
    return uuid;
  }

  /** Devuelve todas las operaciones pendientes. */
  function obtenerPendientes() {
    return _txGetAll('operaciones_offline', 'estado', 'pendiente');
  }

  /** Devuelve todas las operaciones (para panel de estado). */
  function obtenerTodasOperaciones() {
    return _txGetAll('operaciones_offline');
  }

  /**
   * Reencola una operación marcada con error para intentar de nuevo.
   * Devuelve true si la operación existía y se reencoló.
   */
  async function reintentarOperacion(uuid) {
    const op = await _txGet('operaciones_offline', uuid);
    if (!op || op.estado !== 'error') return false;

    await _txPut('operaciones_offline', {
      ...op,
      estado: 'pendiente',
      error: null,
      reintentado_at: new Date().toISOString(),
    });
    _actualizarBadgePendientes();
    return true;
  }

  /**
   * Elimina definitivamente una operación de la cola (pendiente o con error).
   * La UI debe pedir confirmación ANTES de llamar esto — sobre todo con dinero.
   */
  async function descartarOperacion(uuid) {
    const op = await _txGet('operaciones_offline', uuid);
    if (!op || op.estado === 'sincronizado') return false;

    await _txDelete('operaciones_offline', uuid);
    if (op.tipo === 'crear_reservacion' && op.payload?.client_temp_id) {
      await _txDelete('reservaciones', op.payload.client_temp_id).catch(() => {});
    }
    if (op.tipo === 'crear_huesped' && op.payload?.client_temp_id) {
      await _txDelete('huespedes', op.payload.client_temp_id).catch(() => {});
    }
    _actualizarBadgePendientes();
    return true;
  }

  /** Borra del historial las operaciones sincronizadas con más de N días. */
  async function purgarSincronizadasAntiguas(dias = 7) {
    try {
      const limite = Date.now() - dias * 24 * 60 * 60 * 1000;
      const todas = await _txGetAll('operaciones_offline');
      for (const op of todas) {
        if (op.estado === 'sincronizado' && (op.timestamp || 0) < limite) {
          await _txDelete('operaciones_offline', op.uuid);
        }
      }
    } catch (_) {}
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 6. SINCRONIZACIÓN CON EL SERVIDOR
  // ═══════════════════════════════════════════════════════════════════════════

  let _sincronizando = false;
  let _avisoSesionMostrado = false;

  /**
   * La sesión del servidor expiró y no hay remember-token que la restaure.
   * La cola queda INTACTA en este equipo: solo hay que volver a iniciar
   * sesión y se enviará sola. Se avisa una vez por carga de página.
   */
  function _avisarSesionExpirada() {
    if (_avisoSesionMostrado) return;
    _avisoSesionMostrado = true;

    const loginUrl = `${BASE}/login`;
    if (window.Swal) {
      Swal.fire({
        icon: 'warning',
        title: 'Tu sesión expiró',
        text: 'Hay operaciones offline esperando. No se pierden: inicia sesión de nuevo y se enviarán solas.',
        confirmButtonText: 'Iniciar sesión',
        showCancelButton: true,
        cancelButtonText: 'Ahora no',
        confirmButtonColor: '#4A6340',
        reverseButtons: true,
      }).then(res => {
        if (res.isConfirmed) window.location.href = loginUrl;
      });
    } else {
      window.PWA?.showToast('Tu sesión expiró. Inicia sesión para enviar las operaciones pendientes.', 'warning', 8000);
    }
  }

  /**
   * Envía todas las operaciones pendientes a /api/sync.
   * Marca cada una como 'sincronizado' o 'error' según la respuesta.
   * Es seguro llamarla múltiples veces — tiene guard de concurrencia.
   */
  async function sincronizar() {
    if (!hasOfflineStorageContext()) {
      warnMissingOfflineContext();
      return;
    }

    if (_sincronizando || !navigator.onLine) return;

    const pendientes = await obtenerPendientes();
    if (!pendientes.length) return;

    _sincronizando = true;
    _mostrarIndicadorSync(true);

    try {
      const csrfMeta = document.querySelector('meta[name="csrf-token"]');
      const headers  = {
        'Content-Type': 'application/json',
        'Accept':       'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      };
      if (csrfMeta) headers['X-CSRF-Token'] = csrfMeta.content;

      const res = await fetch(BASE + '/api/sync', {
        method:      'POST',
        credentials: 'same-origin',
        headers,
        body: JSON.stringify({ operaciones: pendientes }),
      });

      // Sesión expirada sin remember-token: la cola se conserva tal cual y
      // se reintenta después del re-login (sync automático al cargar página).
      if (res.status === 401) {
        console.warn('[OfflineData] Sync detenido: sesión expirada. La cola queda intacta.');
        _avisarSesionExpirada();
        return;
      }

      if (!res.ok) {
        throw new Error(`Servidor respondió ${res.status}`);
      }

      const json = await res.json();

      // Marcar exitosas
      for (const uuid of (json.exitosas || [])) {
        const op = pendientes.find(o => o.uuid === uuid);
        if (op) {
          await _txPut('operaciones_offline', {
            ...op,
            estado: 'sincronizado',
            sincronizado_at: new Date().toISOString(),
          });
          if (op.tipo === 'crear_reservacion' && op.payload?.client_temp_id) {
            await _txDelete('reservaciones', op.payload.client_temp_id);
          }
        }
      }

      // Marcar fallidas con el mensaje de error
      for (const falla of (json.fallidas || [])) {
        const op = pendientes.find(o => o.uuid === falla.uuid);
        if (op) {
          await _txPut('operaciones_offline', {
            ...op,
            estado:   'error',
            intentos: (op.intentos || 0) + 1,
            error:    falla.error,
          });
        }
      }

      const exitosas = (json.exitosas || []).length;
      const fallidas = (json.fallidas || []).length;

      if (exitosas > 0) {
        window.PWA?.showToast(
          `${exitosas} operación(es) sincronizada(s) correctamente.`,
          'success'
        );
        // Refrescar snapshots para que la UI tenga datos actualizados
        await capturarSnapshots();
      }

      if (fallidas > 0) {
        window.PWA?.showToast(
          `${fallidas === 1 ? '1 cambio fue rechazado' : fallidas + ' cambios fueron rechazados'} al enviarse. Revísalos en "Cambios sin enviar".`,
          'warning',
          8000
        );
      }

      // Aviso a las pantallas interesadas (p.ej. /offline/pendientes) para re-renderizar
      window.dispatchEvent(new CustomEvent('loscedros:sync-done', {
        detail: { exitosas, fallidas },
      }));

      console.log(`[OfflineData] Sync completado — exitosas: ${exitosas}, fallidas: ${fallidas}`);

    } catch (err) {
      console.error('[OfflineData] Error al sincronizar:', err.message);
      window.PWA?.showToast('Error al sincronizar. Se reintentará cuando haya conexión.', 'error');
    } finally {
      _sincronizando = false;
      _mostrarIndicadorSync(false);
      _actualizarBadgePendientes();
    }
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 7. HELPERS DE UI
  // ═══════════════════════════════════════════════════════════════════════════

  function _mostrarIndicadorSync(activo) {
    const el = document.getElementById('pwa-sync-indicator');
    if (el) {
      el.classList.toggle('syncing', activo);
      el.title = activo ? 'Sincronizando...' : 'Sincronizado';
    }
  }

  async function _actualizarBadgePendientes() {
    const badge = document.getElementById('offline-ops-badge');
    const link  = document.getElementById('offline-ops-link');
    if (!badge && !link) return;
    try {
      const todas = await obtenerTodasOperaciones();
      const pendientes = todas.filter(o => o.estado === 'pendiente').length;
      const errores    = todas.filter(o => o.estado === 'error').length;
      const total      = pendientes + errores;

      if (badge) {
        badge.textContent = total;
        badge.classList.toggle('hidden', total === 0);
        // Rojo si hay rechazadas que requieren atención, ámbar si solo pendientes
        badge.style.background = errores > 0 ? '#DC2626' : '#D97706';
      }
      if (link) {
        link.classList.toggle('hidden', total === 0);
        link.title = errores > 0
          ? `${errores} operación(es) rechazadas — requieren tu atención`
          : `${pendientes} operación(es) esperando sincronizar`;
      }
    } catch (_) {}
  }

  async function _actualizarUITimestamp() {
    const el = document.getElementById('offline-sync-timestamp');
    if (!el) return;
    try {
      const meta = await obtenerMetaSync();
      const iso  = meta?.habitaciones?.valor;
      if (iso) {
        const d = new Date(iso);
        el.textContent = `Datos al ${d.toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}`;
        el.classList.remove('hidden');
      }
    } catch (_) {}
  }

  // ═══════════════════════════════════════════════════════════════════════════
  // 8. INICIALIZACIÓN Y EVENTOS
  // ═══════════════════════════════════════════════════════════════════════════

  document.addEventListener('DOMContentLoaded', async () => {
    // Captura inicial al cargar la página (si hay internet)
    if (navigator.onLine) {
      // Pequeño delay para no bloquear el primer render
      setTimeout(capturarSnapshots, 400);
      setTimeout(capturarSnapshots, 3500);
    }

    // Mantener chico el historial de operaciones ya sincronizadas
    purgarSincronizadasAntiguas(7);

    // Sincronizar pendientes al cargar página con internet (fase 7): cubre el
    // caso de re-login tras sesión expirada y cortes de red que el navegador
    // no reportó con el evento 'online'.
    setTimeout(() => {
      if (navigator.onLine) sincronizar();
    }, 1500);

    // Actualizar badge de pendientes
    _actualizarBadgePendientes();
    _actualizarUITimestamp();

    // Captura periódica mientras hay internet
    setInterval(() => {
      if (navigator.onLine) capturarSnapshots();
    }, SNAPSHOT_INTERVALO_MS);
  });

  // Al recuperar internet: sincronizar primero, luego capturar snapshot
  window.addEventListener('online', async () => {
    console.log('[OfflineData] Conexión restaurada — iniciando sync');
    await sincronizar();
    await capturarSnapshots();
  });

  // ═══════════════════════════════════════════════════════════════════════════
  // 9. API PÚBLICA
  // ═══════════════════════════════════════════════════════════════════════════

  window.OfflineData = {
    // Snapshots
    capturarHabitaciones,
    capturarReservaciones,
    capturarHuespedes,
    capturarIndiceGlobal,
    capturarCaja,
    capturarTarifas,
    capturarSnapshots,

    // Lectura
    obtenerHabitaciones,
    obtenerReservaciones,
    guardarReservacionLocal,
    actualizarReservacionLocal,
    guardarHuespedes,
    buscarHuespedes,
    buscarGlobal,
    obtenerCajaSnapshot,
    obtenerCategoriasCaja,
    obtenerIncrementosTarifa,
    verificarDisponibilidadLocal,
    obtenerMetaSync,

    // Cola
    encolarOperacion,
    obtenerPendientes,
    obtenerTodasOperaciones,
    reintentarOperacion,
    descartarOperacion,
    purgarSincronizadasAntiguas,

    // Sincronización
    sincronizar,

    // Utils
    generarUUID,
    storage: {
      dbName: DB_NAME,
      scope: OFFLINE_STORAGE_CONTEXT?.scope || null,
      hasContext: hasOfflineStorageContext,
    },
  };

  console.log('[OfflineData] Modulo listo', DB_NAME || 'sin-contexto');
})();
