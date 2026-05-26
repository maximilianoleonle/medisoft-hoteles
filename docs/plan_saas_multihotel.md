# Plan SaaS multi-hotel

## Alcance de Fase 1

Esta fase crea solo base tecnica aditiva para preparar el sistema como SaaS
multi-hotel. No cambia la logica funcional del sistema mono-hotel actual.

No se modifica:

- Caja.
- Reservaciones.
- Check-in/check-out.
- Login ni sesiones.
- PWA/offline.
- Consultas operativas criticas.
- Tablas operativas existentes.

## Arquitectura objetivo

El sistema debe evolucionar a un solo codigo base con multiples hoteles
aislados por tenant.

Componentes objetivo:

- `hoteles`: catalogo de tenants/hoteles.
- `hotel_usuarios`: usuarios con acceso a uno o varios hoteles.
- `hotel_configuracion`: configuracion y feature flags por hotel.
- `TenantContext`: contexto del hotel actual, usuario actual, roles y permisos.
- `logs_auditoria`: bitacora por hotel, usuario, entidad y accion.
- Migraciones versionadas en `migrations/`.

El aislamiento real de datos llegara cuando las tablas operativas tengan
`hotel_id` y todas las consultas criticas filtren por `TenantContext::hotelId()`.

## Tablas nuevas

### hoteles

Contiene identidad del hotel/tenant:

- Nombre, slug y codigo.
- Datos fiscales y de contacto.
- Zona horaria.
- Moneda.
- Estado activo.
- Metadata opcional.

### hotel_configuracion

Guarda configuracion por hotel:

- `hotel_id`.
- `clave`.
- `valor`.
- `tipo`.
- `grupo`.
- `es_feature_flag`.
- `activo`.

Convencion recomendada para flags:

- `feature.multi_hotel`
- `feature.selector_hotel`
- `feature.audit_logs`
- `feature.hotel_configuracion`
- `feature.saas_billing`

### hotel_usuarios

Relaciona usuarios con hoteles:

- Un usuario puede tener acceso a uno o varios hoteles.
- Un hotel puede tener varios usuarios.
- Permite rol por hotel.
- Permite preparar superadmin futuro sin tocar login en esta fase.

### migrations

Registro de migraciones ejecutadas:

- Nombre de migracion.
- Batch.
- Checksum opcional.
- Estado.
- Fecha de ejecucion.

### logs_auditoria

Auditoria futura:

- Hotel.
- Usuario.
- Accion.
- Entidad.
- Datos antes/despues.
- IP y user agent.

No reemplaza todavia a `logs_acceso`.

## Tablas que necesitaran hotel_id despues

### Identidad operativa del hotel

- `habitaciones`
- `tipos_habitacion`
- `habitacion_imagenes`
- `mantenimientos_habitaciones`

### Huespedes y vehiculos

- `huespedes`
- `huesped_vehiculos`
- `huespedes_vehiculos`

### Reservaciones y facturacion

- `reservaciones`
- `reservacion_habitaciones`
- `reservacion_pagos`
- `reservacion_abonos`
- `reservacion_notas`
- `solicitudes_factura`

### Caja

- `cajas`
- `cortes_caja`
- `movimientos_caja`
- `denominaciones_efectivo`
- `categorias_movimientos`
- `vista_caja_actual` debe recrearse con filtro por hotel cuando corresponda.

### Llaves y remotos

- `control_llaves`
- `historial_llaves`
- `control_remotos`
- `historial_remotos`
- `control_remotos_backup`
- `historial_remotos_backup`

### Inventario

- `inventario_categorias`
- `inventario_productos`
- `inventario_config_habitacion`
- `inventario_habitacion_config`
- `inventario_movimientos`
- `movimientos_inventario`
- `alertas_inventario`
- `productos`
- `categorias_producto`

### Tarifas y configuracion

- `incrementos_tarifas`
- `tarifas_temporada`
- `configuracion`

### Usuarios, sesiones y PWA/offline

No agregar `hotel_id` directo todavia a:

- `usuarios`
- `remember_tokens`
- `logs_acceso`
- `push_subscriptions`
- `sync_queue`

Estrategia recomendada:

- `usuarios` se mantiene global.
- Acceso por hotel vive en `hotel_usuarios`.
- `remember_tokens` se mantiene por usuario.
- `logs_acceso` se revisa cuando se toque login/sesiones.
- `push_subscriptions` y `sync_queue` requieren fase especial de PWA/offline.

## Supuestos mono-hotel detectados

- Nombre "Los Cedros" hardcodeado en vistas, controladores, PDFs, PWA y mensajes.
- Configuracion global en `src/config/app.php`.
- `configuracion` actual no distingue hotel.
- Reportes usan supuestos globales y calculos con 66 habitaciones.
- PDFs usan nombre del hotel fijo.
- WhatsApp usa mensaje fijo del hotel.
- PWA usa nombres de cache/base IndexedDB asociados a Los Cedros.
- Consultas de modelos/controladores asumen una unica base operativa.

## Orden recomendado de migracion por modulos

### Fase 1 - Base tecnica

- Crear tablas nuevas.
- Crear helper de configuracion por hotel.
- Crear `TenantContext`.
- Documentar plan y riesgos.
- No integrar en modulos criticos.

### Fase 2 - Bootstrap mono-hotel controlado

- Crear hotel inicial "Los Cedros" en `hoteles`.
- Crear relaciones iniciales en `hotel_usuarios`.
- Crear configuracion inicial en `hotel_configuracion`.
- Activar `TenantContext` solo en modo compatible.
- No migrar caja/reservaciones todavia.

### Fase 3 - Catalogos de bajo riesgo

- Migrar `tipos_habitacion`, `habitaciones`, `habitacion_imagenes`.
- Migrar configuraciones simples.
- Probar filtros por hotel con datos duplicados controlados.

### Fase 4 - Huespedes

- Migrar `huespedes` y vehiculos.
- Ajustar busquedas y validaciones.
- Probar que un hotel no vea huespedes de otro.

### Fase 5 - Reservaciones

- Migrar `reservaciones` y tablas hijas.
- Ajustar calendario, disponibilidad, PDFs y facturacion.
- Esta fase requiere pruebas intensivas.

### Fase 6 - Caja

- Migrar `cajas`, `cortes_caja`, `movimientos_caja` y categorias.
- Recrear vistas/reportes de caja por hotel.
- Requiere backup fresco y ventana controlada.

### Fase 7 - Inventario, llaves, remotos y mantenimientos

- Migrar inventario.
- Migrar control de llaves/remotos.
- Migrar mantenimientos.
- Ajustar integraciones automaticas.

### Fase 8 - PWA/offline y sync

- Redisenar cache, IndexedDB y `sync_queue` por hotel.
- Evitar mezcla offline entre hoteles.
- Requiere pruebas en dispositivos reales.

### Fase 9 - SaaS comercial

- Planes.
- Suscripciones.
- Facturacion SaaS.
- Limites por plan.
- Panel superadmin.

## Como probar aislamiento cuando exista hotel_id

- Crear dos hoteles de prueba.
- Crear usuarios con acceso separado.
- Crear datos con el mismo numero de habitacion en hoteles distintos.
- Verificar que listados, busquedas, reportes y APIs filtran por hotel.
- Probar usuario multi-hotel cambiando contexto.
- Probar superadmin con acceso global controlado.
- Revisar consultas sin `hotel_id` usando busqueda estatica.
- Probar exportaciones PDF/Excel con nombre/configuracion del hotel correcto.
- Probar PWA/offline por separado antes de activar multi-hotel en produccion.

## Riesgos

- Usar `TenantContext` antes de que las consultas operativas filtren por hotel.
- Agregar `hotel_id` sin backfill correcto.
- Migrar caja/reservaciones sin ventana de pruebas.
- Mezclar caches PWA/offline entre hoteles.
- Mantener textos "Los Cedros" en PDFs o mensajes para otros hoteles.
- Crear superadmin sin controles de auditoria.
- Ejecutar migraciones en produccion sin backup validado.

## Rollback

Si solo se crean archivos:

```powershell
git revert <commit>
```

Si la migracion SQL se ejecuto en un entorno de prueba y no hay datos reales en
las tablas nuevas:

```sql
DROP TABLE IF EXISTS logs_auditoria;
DROP TABLE IF EXISTS hotel_usuarios;
DROP TABLE IF EXISTS hotel_configuracion;
DROP TABLE IF EXISTS hoteles;
DROP TABLE IF EXISTS migrations;
```

Si ya existen datos reales en tablas nuevas, no usar `DROP TABLE`; crear una
migracion de reversa revisada y respaldar primero.

## Que NO se debe tocar todavia

- No agregar `hotel_id` a tablas operativas.
- No tocar caja.
- No tocar reservaciones.
- No tocar check-in/check-out.
- No tocar login ni sesiones.
- No modificar PWA/offline.
- No cambiar reportes criticos.
- No activar selector de hotel en UI.
- No crear superadmin funcional todavia.
- No ejecutar migraciones destructivas.
