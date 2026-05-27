# Reservaciones 1-F-F-D-B - Cierre tecnico de reportes historicos operativos basicos

## 1. Objetivo

Documentar el cierre tecnico del scope por `hotel_id` en reportes historicos operativos basicos.

## 2. Que se logro

- Se uso el helper `hotelIdActual()`.
- Se aplico scope por `reservaciones.hotel_id`.
- `huespedes` quedo como join auxiliar, no como fuente de scope.
- Se filtraron reportes historicos basicos sin tocar reportes avanzados.

## 3. Metodos modificados

- `obtenerProcedenciaPorCiudad()`
- `obtenerEvolucionProcedencia()`
- `obtenerOcupacionPorDiaSemana()`
- `obtenerPromedioEstancia()`
- `obtenerDistribucionEstancia()`
- `obtenerTendenciaEstancia()`

## 4. Que NO se toco

- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- `ReportesController`.
- `DashboardController`.
- `CajaController`.
- `Reservacion.php`.
- `ReservacionController.php`.
- Migraciones.
- Schema.
- Rentabilidad avanzada.
- Ocupacion avanzada con constante `66`.
- Ranking completo.
- Reportes historicos avanzados fuera del alcance.

## 5. Validaciones realizadas

- `php -l` en `Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- 0 nuevos `hotel_id` NULL segun herramientas.
- Confirmacion de que no se tocaron PDFs/exportaciones/PWA/Sync/APIs.

## 6. Riesgos pendientes

- Rentabilidad avanzada sigue pendiente.
- Ocupacion diaria/semanal/mensual sigue pendiente por constante global `66`.
- Ranking completo sigue pendiente.
- PDFs/exportaciones siguen pendientes.
- `huespedes` sigue sin modelo tenant.
- Vistas historicas pueden requerir revision posterior.

## 7. Siguiente fase recomendada

Reservaciones 1-F-F-D-C: auditoria/propuesta para rentabilidad y ocupacion avanzada, especialmente por la constante global `66`.
