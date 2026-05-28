# Reservaciones 1-F-F-D-D-D-E - Cierre general de reportes históricos avanzados

## Objetivo

Documentar el cierre general del bloque de reportes históricos avanzados scoped por `hotel_id`.

## Qué quedó cerrado

- `Reporte.php` financiero básico.
- `ReportesController` básico.
- Rentabilidad de habitaciones.
- Ingreso promedio por habitación.
- Reportes históricos operativos básicos.
- Ocupación por tipo.
- Ocupación diaria/semanal/mensual.
- Estadísticas de ocupación.
- Procedencia avanzada.
- Estancia por tipo.
- Estancia por procedencia.
- Ranking de estados.
- Evolución de estados.
- Comparativa de estados.

## Decisiones importantes

- `huespedes` sigue como join auxiliar, no como fuente de scope.
- `usuarios` sigue como join auxiliar, no como fuente de scope.
- La constante global 66 fue reemplazada por capacidad real del hotel actual.
- La capacidad histórica por fecha queda como posible mejora futura.
- PDFs/exportaciones quedan fuera hasta tener datos base confiables.

## Qué NO se tocó

- PDFs/exportaciones.
- `ReporteCortePDF`.
- vistas PDF.
- PWA/offline.
- Sync.
- APIs globales.
- migraciones.
- schema.
- huéspedes tenant.
- usuarios tenant.
- base de datos.

## Validaciones generales

- `php -l` en archivos modificados durante el bloque.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- 0 nuevos `hotel_id` NULL según herramientas.
- No se tocaron PDFs/PWA/Sync/APIs.

## Riesgos pendientes

- PDFs/exportaciones siguen pendientes.
- `ReporteCortePDF` debe revisarse cuando se preparen PDFs.
- Algunas vistas físicas faltantes, como `ranking-estados.php`, `estancia.php` u `ocupacion.php`, fueron detectadas durante auditorías.
- Huespedes sigue sin modelo tenant.
- Usuarios sigue sin modelo tenant.
- Capacidad histórica por fecha no existe todavía.

## Siguiente fase recomendada

Reservaciones 1-F-F-E-A: Auditoría de PDFs/exportaciones y renderizadores de reportes.

No debe implementarse directo porque PDFs/exportaciones generan documentos financieros/históricos y deben consumir únicamente datos ya scoped.
