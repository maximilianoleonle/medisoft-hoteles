# Fase NP-B-0 - Contrato CRUD basico de trabajadores

Estado: `CONTRATO_NP_B_CRUD_TRABAJADORES_COMPLETADO`

## Objetivo

Definir una subfase segura para permitir alta, edicion y baja logica de trabajadores
como entidad laboral independiente, sin registrar todavia pagos, anticipos, prestamos,
asistencias operativas, documentos laborales, movimientos de Caja ni salida real de
dinero.

## Estado previo

- NP-0 contrato y diagnostico completado.
- NP-A migracion base completada; tablas `trabajador*` existen y estan vacias.
- NP-A UI read-first completada; existen `GET /trabajadores` y `GET /trabajadores/{id}`.
- No hay POST de Personal.
- No hay Caja/Nomina.
- `/api/sync` fuera de alcance.

## Alcance permitido para NP-B-A futura

- `GET /trabajadores/crear`.
- `POST /trabajadores`.
- `GET /trabajadores/{id}/editar`.
- `POST /trabajadores/{id}/actualizar`.
- `POST /trabajadores/{id}/baja-logica`.
- `POST /trabajadores/{id}/reactivar` si la transicion queda centralizada y auditada.
- Modelo central para crear/actualizar/cambiar estado con validacion `hotel_id`.
- Auditoria con `AuditService` si esta disponible.
- Mensajes claros de exito/error.
- Vistas con CSRF y sin formularios anidados.

## Fuera de alcance

- Pagos, anticipos, prestamos, comisiones, descuentos o ajustes.
- Saldo contable/laboral definitivo.
- Asistencia operativa.
- Documentos laborales y uploads.
- Integracion con Caja, cortes, movimientos o categoria Nomina.
- Modificar `usuarios` de forma destructiva.
- Crear usuarios del sistema automaticamente.
- Permisos profundos nuevos.
- `/api/sync`, PWA/offline, service worker.

## Reglas de datos

- `trabajadores.hotel_id` siempre corresponde al hotel actual.
- `trabajadores.usuario_id` es opcional; si se usa, solo referencia un usuario existente.
- Un trabajador puede existir sin usuario del sistema.
- No se borra fisicamente un trabajador; se usa `estado = 'baja'`.
- Reactivar cambia `baja` o `inactivo` a `activo` solo si el registro pertenece al hotel actual.
- `nombre_completo` obligatorio.
- `email` valido si se captura.
- `salario_base` no negativo si se captura.
- `fecha_baja` se define al dar baja logica; `fecha_alta` puede ser opcional.

## Riesgo

Naranja bajo.

Motivo:

- habilita escrituras sobre una entidad laboral sensible;
- pero no mueve dinero, no toca Caja, no altera `usuarios` destructivamente y no crea
  movimientos laborales.

## Definition of Done NP-B-A futura

- Rutas GET/POST registradas solo para CRUD basico de trabajador.
- Controlador protegido por sesion, hotel, modulo `usuarios` y permiso `usuarios.view`
  como minimo; escritura con rol/permiso administrativo existente.
- CSRF en todos los POST.
- Modelo central valida hotel, datos obligatorios, email, salario no negativo y
  transiciones de estado.
- Auditoria para crear, actualizar, baja logica y reactivar.
- Vistas sin formularios anidados, sin cambiar nombres/metodos accidentalmente.
- Health checker valida rutas, CSRF, ausencia de Caja/Nomina y ausencia de pagos.
- SQL read-only post-prueba confirma que Caja/Nomina sigue en cero.
- QA manual diferida documentada.
- Commit separado.

## Rollback

- Revertir el commit de implementacion NP-B-A.
- Si se crearon trabajadores de prueba, no ejecutar `DELETE` sin autorizacion; preferir
  baja logica o restaurar backup si la prueba fue aislada.
- No borrar tablas `trabajador*`.
- No tocar `usuarios`, `hotel_usuarios`, Caja ni `/api/sync`.
