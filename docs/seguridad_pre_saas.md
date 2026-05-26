# Seguridad pre-SaaS - Fase 0.5

## Objetivo

Reducir riesgos actuales antes de iniciar cualquier migracion SaaS multi-hotel.
Esta fase no agrega `hotel_id`, no cambia caja, reservaciones, check-in/check-out,
login ni sesiones.

## Riesgos encontrados

- Credenciales reales de Green API estaban hardcodeadas en:
  - `src/config/whatsapp.php`
  - `src/app/config/whatsapp.php`
- `APP_DEBUG` estaba hardcodeado como `true` en `src/config/app.php`.
- Existen herramientas publicas de diagnostico, prueba, instalacion y correccion en
  `src/public_html`.
- `src/app/uploads/` contiene artefactos operativos y no debe seguir recibiendo
  archivos versionados.
- Dumps SQL, respaldos, logs y temporales locales deben quedar fuera del control
  de versiones.

## Cambios realizados

- WhatsApp ahora lee credenciales desde variables de entorno.
- `APP_DEBUG` ahora depende de la variable de entorno `APP_DEBUG`.
- `.env.example` documenta las variables necesarias sin valores reales.
- `.gitignore` ignora `.env`, respaldos, dumps SQL, logs/temporales locales,
  `_local_quarantine_sensitive/` y `src/app/uploads/`.
- `src/public_html/.htaccess` bloquea el acceso web directo a herramientas
  publicas de diagnostico, prueba, instalacion y correccion.

## Secretos que deben rotarse

Rotar en Green API todos los valores que estuvieron versionados o visibles en el
codigo:

- ID de instancia de WhatsApp.
- API token de WhatsApp.
- Numeros destino si se consideran informacion sensible operativa.

Despues de rotar, cargar los nuevos valores solamente en `.env` o en el gestor
de secretos del entorno. No volver a escribirlos en archivos PHP versionados.

## Variables de entorno esperadas

Variables principales:

```env
APP_DEBUG=false
WHATSAPP_ID_INSTANCE=
WHATSAPP_API_TOKEN=
WHATSAPP_DESTINATION=
WHATSAPP_API_HOST=https://api.green-api.com
WHATSAPP_SEND_ENABLED=false
```

Variables opcionales para la configuracion duplicada bajo `src/app/config`:

```env
APP_WHATSAPP_ID_INSTANCE=
APP_WHATSAPP_API_TOKEN=
APP_WHATSAPP_DESTINATION=
APP_WHATSAPP_API_HOST=https://api.green-api.com
APP_WHATSAPP_SEND_ENABLED=false
```

Si las variables `APP_WHATSAPP_*` no existen, `src/app/config/whatsapp.php`
usa las variables `WHATSAPP_*` principales.

## Archivos publicos retirados de `public_html`

Los siguientes archivos se movieron a
`_local_quarantine_sensitive/public_diagnostics/`, carpeta local ignorada por
Git:

- `CorreccionController.php`
- `correccion_index.php`
- `corregir_precios_reservaciones.php`
- `diagnostico_inventario.php`
- `diagnostico_reservaciones.php`
- `diagnostico_tarifas.php`
- `fix_habitaciones_ocupadas.php`
- `info.php`
- `instalar_correccion.php`
- `limpiar_reservaciones_antiguas.php`
- `test-movimientos-caja.php`
- `test-tarifas.php`
- `test_checkout.html`
- `test_inventario_service.php`
- `test_manual.php`
- `verificar-rutas.php`

Antes de moverlos se buscaron referencias exactas y de rutas. No se encontraron
usos productivos desde `src/config/routes.php`; las rutas legacy de correccion
estan comentadas.

## Uploads

`src/app/uploads/` debe conservarse fisicamente en el entorno local/operativo,
pero no debe versionarse. Esta fase solo actualiza `.gitignore`.

Los archivos operativos ya se sacaron del indice de Git sin borrarlos del disco
con:

```powershell
git rm -r --cached src/app/uploads
```

Ese comando solo los quita del indice de Git. No elimina los archivos fisicos.

## Pendiente antes de SaaS

- Rotar secretos de WhatsApp.
- Crear/actualizar `.env` local y variables del entorno de despliegue.
- Decidir si los scripts en cuarentena se eliminan definitivamente del historial
  futuro o se reemplazan por scripts CLI internos revisados.
- Tener backup fresco antes de iniciar migraciones.
- Disenar Fase 1 SaaS con `hoteles`, `hotel_usuarios`,
  `hotel_configuracion`, migraciones, auditoria y estrategia de tenant.
