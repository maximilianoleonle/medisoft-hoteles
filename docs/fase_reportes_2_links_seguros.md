# Fase reportes 2 - Links seguros

## Alcance implementado

Se preparo la base para compartir reportes PDF mediante links seguros sin tocar WhatsApp, PWA, service worker, sync, caja, cortes, movimientos ni calculos de reportes.

Piezas agregadas:

- Tabla `reporte_links`.
- Modelo `ReporteLink`.
- Controlador `ReporteLinkController`.
- Vista interna `/reportes/links`.
- Ruta publica `/reportes/link/{token}`.
- Acceso desde el centro de reportes.

## Comportamiento

- Los tokens se generan como valores aleatorios de 64 caracteres hexadecimales.
- En base de datos se guarda `token_hash`, no el token completo.
- La ruta publica valida token, estado activo y expiracion.
- Un link expirado, revocado o inexistente responde `410 Gone`.
- La descarga publica solo sirve PDFs ubicados dentro de `storage/reportes`.
- El historial interno permite revisar estado, expiracion, accesos y revocar links.

## Migracion local

Migracion ejecutada:

```bash
migrations/20260611_001_create_reporte_links.sql
```

Backup local limpio previo:

```bash
backups/backup_pre_reporte_links_clean_20260611_024838.sql
```

Nota: existe una vista local `vista_caja_actual` con referencias invalidas. No se modifico. Para el backup limpio se excluyo esa vista.

## Pendiente para la siguiente fase

- Conectar generadores PDF para guardar una copia en `storage/reportes`.
- Crear el registro con `ReporteLink::crearDesdeArchivo()`.
- Mostrar o enviar el link generado en el momento de creacion.
- Agregar configuracion por hotel para dias de expiracion y canal de envio.
- Integrar envio por correo.
