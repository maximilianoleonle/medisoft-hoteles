# Fase reportes 4 - Correo con link seguro

Fecha: 2026-06-11

## Alcance

- Se agrego configuracion por hotel para envio de reportes por correo.
- El correo envia un link seguro al PDF, no adjunta el archivo.
- Cada envio renueva el token del link y mantiene solo el hash en base de datos.
- Se agrego historial tecnico de intentos de envio en `reporte_link_envios`.
- Se agrego boton de correo en `reportes/links`.

## Configuracion editable

Grupo: Reportes seguros.

- `reportes.email_envio_activo`
- `reportes.email_destinatarios`
- `reportes.email_remitente`
- `reportes.email_nombre_remitente`
- `reportes.email_asunto`
- `reportes.email_mensaje`

Los destinatarios aceptan correos separados por coma, punto y coma o salto de linea.

## Archivos nuevos

- `migrations/20260611_002_create_reporte_link_envios.sql`
- `src/app/services/ReporteEmailService.php`

## Archivos modificados

- `src/app/helpers/hotel_config.php`
- `src/app/controllers/ReporteLinkController.php`
- `src/app/models/ReporteLink.php`
- `src/app/views/configuracion/index.php`
- `src/app/views/reportes/links.php`
- `src/config/routes.php`

## Notas

- El transporte actual usa `mail()` del servidor para mantener la fase sin dependencias nuevas ni secretos SMTP en base de datos.
- Si el hosting no tiene correo saliente configurado, el intento se registrara como fallido.
- Una fase posterior puede cambiar el transporte a SMTP autenticado con una libreria como PHPMailer, usando secretos fuera de la base de datos.
