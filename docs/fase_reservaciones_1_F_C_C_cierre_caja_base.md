# Reservaciones 1-F-C-C - Cierre tecnico de Caja funcional base

## 1. Objetivo

Documentar el cierre tecnico de Caja funcional base scoped por `hotel_id`.

## 2. Que se logro

- Caja activa filtrada por `hotel_id`.
- Corte actual validado por `caja_id + hotel_id`.
- Apertura de caja escribiendo `hotel_id` en `cortes_caja`.
- Cierre de caja validando `id + hotel_id`.
- Resumen basico filtrado por `hotel_id`.
- Historial basico filtrado por `hotel_id`.
- Movimientos manuales escribiendo `hotel_id`.
- Lectura, edicion y eliminacion de movimientos validando `hotel_id`.
- `CajaController` usando metodos scoped.

## 3. Archivos modificados en la implementacion

- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`
- `src/app/controllers/CajaController.php`

## 4. Que NO se toco

- `Reservacion.php`.
- `ReservacionController.php`.
- `ReportesController`.
- `DashboardController`.
- `Reporte.php`.
- `Sync.php`.
- PWA/offline.
- APIs globales.
- Reportes/exportaciones/PDF.
- Migraciones.
- Schema.
- `movimientos_caja` fuera de Caja funcional base.

## 5. Validaciones realizadas

- `php -l` en los 3 archivos.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- 0 `hotel_id` NULL en `cajas`, `cortes_caja` y `movimientos_caja`.
- No se hicieron pruebas reales de abrir/cerrar caja porque no habia rollback seguro garantizado desde controlador.

## 6. Riesgos pendientes

- Reportes/Dashboard financieros siguen globales.
- Exportaciones/PDF siguen fuera de scope.
- Sync/PWA/API siguen pendientes.
- Flujos financieros desde Reservaciones como `cambiarMetodoPagoAction`, `modificarDiasAction` y cancelaciones financieras siguen pendientes.
- `denominaciones_efectivo` y `categorias_movimientos` aun no tienen `hotel_id`.
- `hotel_id` sigue `INT NULL` por diseno conservador.
- No hay FKs estrictas todavia.

## 7. Siguiente fase recomendada

Reservaciones 1-F-D-A: auditoria de reportes financieros, dashboard, exportaciones/PDF y cortes avanzados.

No debe implementarse directo porque puede afectar reportes financieros historicos y cortes cerrados. Esa fase debe empezar como auditoria read-only para separar lecturas seguras, reportes historicos, exportaciones y calculos financieros antes de tocar codigo.

