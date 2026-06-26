# Auditoria de formularios: caja

Fecha: 2026-06-25

## Alcance

- Controlador: `src/app/controllers/CajaController.php`.
- Vistas modificadas:
  - `src/app/views/caja/apertura.php`
  - `src/app/views/caja/index.php`
  - `src/app/views/caja/categorias.php`
- Vistas revisadas sin cambios funcionales:
  - `src/app/views/caja/corte.php`
  - `src/app/views/caja/movimientos.php`
  - `src/app/views/caja/historial.php`
  - `src/app/views/caja/reporte_metodos.php`
  - `src/app/views/caja/arqueo_metodos.php`
  - `src/app/views/caja/ver_corte.php`

## Hallazgos

- Apertura de caja no conservaba el monto ni observaciones cuando fallaba la validacion de monto inicial.
- Apertura de caja mostraba solo mensaje general; no marcaba el campo de monto inicial.
- Registrar ingreso ya guardaba datos en errores de validacion, pero la vista no rehidrataba campos ni mostraba errores por campo.
- Registrar gasto tenia el mismo problema: datos guardados por el controlador, pero no visibles al volver.
- Si fallaba ingreso o gasto, la pantalla volvia al dashboard de caja con el modal cerrado, obligando a reabrirlo manualmente.
- Los fallos de `registrarMovimiento` guardaban datos pero no exponian `save_form_errors`.
- `categoriasAction()` renderiza `caja/categorias`, pero no existia `src/app/views/caja/categorias.php`; solo estaba la vista con nombre `ConfiguracióndeCategorías.php`.
- Cierre de corte, edicion de movimiento y categorias AJAX son flujos sensibles o por JavaScript; se auditaron sin alterar su logica.

## Correcciones aplicadas

- Apertura de caja:
  - conserva `monto_inicial` y `observaciones` al fallar;
  - muestra error debajo de monto inicial u observaciones;
  - muestra resumen accesible para errores globales;
  - limpia `old_input` al abrir correctamente.
- Ingreso y gasto:
  - guardan `form_origen` para distinguir que modal debe reabrirse;
  - exponen errores por campo con `save_form_errors`;
  - rehidratan categoria, descripcion, monto, metodo, referencia, comprobante y proveedor;
  - reabren automaticamente el modal correcto despues de un error;
  - enfocan el primer campo con error;
  - muestran estados visuales de error, `aria-invalid` y `aria-describedby`.
- Categorias:
  - se agrego `src/app/views/caja/categorias.php` como wrapper minimo hacia la vista existente, para que el render `caja/categorias` encuentre un archivo valido.

## Fuera de alcance

- No se modificaron modelos, rutas, base de datos, migraciones, permisos ni autenticacion.
- No se modificaron calculos de caja, saldos, cortes, movimientos, reportes ni exportaciones.
- No se cambio la logica de apertura, cierre, registro de ingreso/gasto o edicion de movimientos.
- No se modificaron flujos de proveedores, cuentas por cobrar, personal, reservaciones ni PWA/offline.
- No se modifico `/api/sync`.

## Validacion

- `php -l` en `CajaController.php`, `apertura.php`, `index.php`, `categorias.php` y `ConfiguracióndeCategorías.php`: sin errores.
- Formularios de vistas de Caja revisados: aperturas y cierres balanceados.
- `git diff --check` del bloque: sin errores reales; solo avisos CRLF.
