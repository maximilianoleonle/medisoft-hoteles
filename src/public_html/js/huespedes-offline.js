/**
 * huespedes-offline.js — Los Cedros
 *
 * Intercepta el formulario de "Nuevo Huésped" (POST huespedes/store) cuando
 * no hay internet:
 *
 *   1. Guarda el huésped en IndexedDB con un id temporal (tmp_hue_*) para que
 *      aparezca de inmediato en el buscador de huéspedes offline (por ejemplo
 *      al crear una reservación sin conexión).
 *   2. Encola la operación 'crear_huesped' con client_temp_id; al volver
 *      internet, /api/sync lo crea en el servidor y las reservaciones offline
 *      que lo referencien resuelven el id real automáticamente.
 *
 * Limitaciones offline: no se suben archivos (identificación) ni vehículos;
 * el expediente se completa después con conexión.
 *
 * Se carga solo en vistas de Huéspedes (footer.php, condicional por título).
 * Depende de: offline-data.js (window.OfflineData) y pwa.js (window.PWA)
 */
(function () {
  'use strict';

  document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action*="huespedes/store"]');
    if (!form) return;

    form.addEventListener('submit', function (e) {
      // Online real → flujo normal al servidor. navigator.onLine solo detecta
      // la interfaz de red: si el wifi sigue arriba pero no hay internet,
      // PWA.isOnline() (ping real) es quien detecta el corte.
      if (navigator.onLine && window.PWA?.isOnline?.() !== false) return;

      e.preventDefault();
      e.stopPropagation();

      _guardarHuespedOffline(form);
    });
  });

  async function _guardarHuespedOffline(form) {
    // Captura offline apagada (/api/sync cerrado): el alta no llegaria al
    // servidor. Mejor avisar que dar por registrado a un huesped que no existe.
    if (window.OfflineData?.escriturasHabilitadas?.() !== true) {
      _avisar(
        'warning',
        'Sin conexión',
        'No se puede registrar al huésped ahora. Inténtalo cuando regrese el internet: <strong>no quedó guardado</strong>.'
      );
      return;
    }

    if (!window.OfflineData) {
      _avisar('error', 'Offline no disponible', 'No se pudo abrir el almacenamiento local.');
      return;
    }

    const data = new FormData(form);
    const nombre = String(data.get('nombre_completo') || '').trim();
    const telefono = String(data.get('telefono') || '').trim();
    const email = String(data.get('email') || '').trim();
    const procedenciaEstado = String(data.get('procedencia_estado') || '').trim();
    const procedenciaCiudad = String(data.get('procedencia_ciudad') || '').trim();
    const notas = String(data.get('notas') || '').trim();

    if (nombre.length < 3) {
      _avisar('warning', 'Nombre requerido', 'Escribe el nombre completo del huésped (mínimo 3 caracteres).');
      return;
    }

    // Evitar duplicado local obvio por teléfono
    if (telefono && window.OfflineData.buscarHuespedes) {
      try {
        const parecidos = await window.OfflineData.buscarHuespedes(telefono);
        const repetido = parecidos.find(h => String(h.telefono || '').trim() === telefono);
        if (repetido) {
          _avisar(
            'warning',
            'Teléfono ya registrado',
            `Ya existe "${repetido.nombre_completo}" con ese teléfono en los datos de este equipo. ` +
            'Búscalo en el listado en vez de crearlo de nuevo.'
          );
          return;
        }
      } catch (_) {}
    }

    const tempId = `tmp_hue_${Date.now()}_${Math.random().toString(16).slice(2)}`;

    const payload = {
      client_temp_id: tempId,
      nombre_completo: nombre,
      telefono: telefono || undefined,
      email: email || undefined,
      procedencia_estado: procedenciaEstado || undefined,
      procedencia_ciudad: procedenciaCiudad || undefined,
      notas: notas || undefined,
    };

    const huespedLocal = {
      id: tempId,
      nombre_completo: nombre,
      telefono,
      email,
      procedencia_estado: procedenciaEstado,
      procedencia_ciudad: procedenciaCiudad,
      notas,
      offline_pendiente: true,
    };

    try {
      await window.OfflineData.encolarOperacion('crear_huesped', payload, `Registrar huésped ${nombre}`);
      await window.OfflineData.guardarHuespedes([huespedLocal]);
    } catch (err) {
      console.error('[HuespedesOffline] No se pudo guardar el huésped offline:', err);
      _avisar('error', 'No se pudo guardar', 'El huésped NO quedó guardado. Intenta de nuevo o espera la conexión.');
      return;
    }

    const teniaArchivo = Array.from(form.querySelectorAll('input[type="file"]'))
      .some(input => input.files && input.files.length > 0);

    const destino = _destinoDespuesDeGuardar(form);
    const extra = teniaArchivo
      ? ' Los archivos adjuntos (identificación) no se guardan sin internet: agrégalos después desde el expediente.'
      : '';

    if (window.Swal) {
      await Swal.fire({
        icon: 'success',
        title: 'Huésped guardado en este equipo',
        text: 'Ya puedes usarlo en reservaciones offline. Se registrará en el servidor al volver internet.' + extra,
        confirmButtonText: 'Continuar',
        confirmButtonColor: '#4A6340',
      });
    } else {
      window.PWA?.showToast('Huésped guardado offline. Se sincronizará al volver internet.', 'success', 6000);
    }

    window.location.href = destino;
  }

  /**
   * A dónde ir después de guardar: si el formulario venía de una
   * "reservación rápida" (?return_to=...), regresar ahí para continuar el flujo.
   */
  function _destinoDespuesDeGuardar(form) {
    try {
      const url = new URL(form.action, window.location.origin);
      const returnTo = url.searchParams.get('return_to');
      if (returnTo && returnTo.startsWith('/')) {
        return returnTo;
      }
    } catch (_) {}

    const base = (window.BASE_URL || '').replace(/\/$/, '');
    return `${base}/huespedes`;
  }

  function _avisar(icon, titulo, texto) {
    if (window.Swal) {
      Swal.fire({
        icon,
        title: titulo,
        text: texto,
        confirmButtonText: 'Entendido',
        confirmButtonColor: icon === 'success' ? '#4A6340' : '#B45309',
      });
    } else {
      window.PWA?.showToast(`${titulo}: ${texto}`, icon === 'success' ? 'success' : 'error', 8000);
    }
  }

})();
