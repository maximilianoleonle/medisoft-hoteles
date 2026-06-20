# Fase 11A-0 - Contrato perfil operativo de huesped read-only

## Estado

`CONTRATO_11A_0_PERFIL_HUESPED_READONLY_COMPLETADO`

## Objetivo

Definir el siguiente frente seguro despues del cierre 10B: enriquecer la ficha de
huesped/cliente con un perfil operativo de solo lectura.

Este contrato responde al pendiente original de Clientes/Huespedes: clasificacion de
frecuentes, adeudos, documentos, notas visibles, recurrencia y seguimiento, sin crear
todavia nuevas escrituras.

Esta fase es solo documental. No implementa codigo, rutas, controladores, modelos,
vistas, formularios, migraciones, datos ni escrituras.

## Contexto actual observado

Rutas existentes:

- `GET /huespedes`.
- `GET /huespedes/create`.
- `POST /huespedes/store`.
- `GET /huespedes/{id}`.
- `GET /huespedes/{id}/edit`.
- `POST /huespedes/{id}/update`.
- `GET /huespedes/buscar`.
- Rutas POST de vehiculos de huesped existentes.

Lecturas ya disponibles:

- `HuespedController::verAction`.
- `Huesped::findForHotel`.
- `Huesped::getReservacionesPorHotel`.
- `Huesped::getVehiculosPorHotel`.
- `Huesped::contarReservacionesPorHotel`.
- `Documento::documentosPorEntidad` para entidad `huesped`.

Superficie de vista:

- `src/app/views/huespedes/ver.php` ya existe y es grande.
- `src/app/views/huespedes/index.php` ya existe y es grande.
- Cualquier cambio futuro en esas vistas debe ser muy acotado y respetar formularios
  existentes.

## Problema a resolver

La ficha de huesped puede convertirse en un centro operativo de lectura, sin cambiar
flujos existentes:

- identificar huesped frecuente;
- ver reservas historicas y proximas;
- ver adeudos CxC vinculados;
- ver documentos activos vinculados;
- ver vehiculos registrados;
- mostrar notas existentes sin crear nuevo editor;
- mostrar alertas de seguimiento sin persistir automatizaciones.

## Contrato visual futuro

Una futura 11A-A deberia usar la ficha existente `GET /huespedes/{id}` como superficie
principal, salvo que se abra un contrato separado para una ruta nueva.

La vista futura debe:

- usar branding del hotel con tokens `--brand-*`;
- no usar tokens Medisoft `--ms-*`;
- mostrar etiqueta `Solo lectura` en el bloque nuevo;
- no agregar botones operativos;
- no agregar formularios nuevos;
- no mover submits existentes;
- no anidar formularios;
- no cambiar `action`, `method`, `name`, CSRF ni hidden inputs existentes;
- no exponer `hotel_id` editable.

## Contrato de datos futuro

El perfil operativo read-only puede leer:

- datos base del huesped scoped por hotel;
- reservaciones del huesped scoped por hotel;
- habitaciones asociadas mediante `reservacion_habitaciones`;
- vehiculos del huesped scoped por hotel;
- cuentas por cobrar del huesped scoped por hotel;
- documentos vinculados a entidad `huesped` scoped por hotel;
- tareas o seguimientos solo si se derivan de entidades hotel-scoped.

No debe usar `huespedes` como fuente global sin scope.

Si el entorno tiene `huespedes.hotel_id`, debe usarse siempre para la ficha. Si una
lectura debe cruzar historicos o datos auxiliares, debe derivar el scope desde una
entidad del hotel.

## Indicadores permitidos

Solo como calculos read-only, no persistentes:

- total de reservaciones del huesped en el hotel;
- ultima estancia;
- proxima estancia;
- noches acumuladas;
- saldo CxC pendiente;
- total de documentos activos;
- total de vehiculos activos;
- clasificacion visual `frecuente` si supera un umbral documentado;
- alerta visual `con adeudo` si existe saldo pendiente;
- alerta visual `documentacion incompleta` si aplica por configuracion futura;
- score de recurrencia calculado al vuelo.

## Fuera de alcance

No se implementan en 11A-0:

- rutas nuevas;
- cambios en `HuespedController`;
- cambios en `Huesped`;
- cambios en vistas;
- migraciones;
- nuevos campos de base de datos;
- edicion de notas;
- recordatorios persistentes;
- creacion de CxC;
- cobros;
- pagos;
- check-in/check-out;
- cambios en reservaciones;
- subida o borrado de documentos;
- cambios en tareas;
- permisos/auth;
- PWA/offline, service worker, IndexedDB, cache names ni `/api/sync`.

`/api/sync` debe seguir bloqueado con HTTP 423 y JSON
`sync_temporarily_disabled`.

## Archivos que podria tocar una futura 11A-A

Solo con autorizacion explicita:

- `src/app/controllers/HuespedController.php`.
- `src/app/models/Huesped.php`.
- `src/app/views/huespedes/ver.php`.
- `src/tools/saas/health_check_fase_1a.php`.
- Un preflight nuevo dedicado o una extension read-only de herramienta existente.
- Documentacion de seguimiento.

La fase 11A-0 no autoriza esos cambios. Solo los enumera para un paso futuro.

## Criterios de aceptacion para una futura 11A-A

1. `GET /huespedes/{id}` sigue protegido por sesion y hotel actual.
2. La ficha no muestra datos de huespedes de otro hotel.
3. El bloque nuevo no contiene formularios POST.
4. No se alteran formularios existentes de la ficha.
5. Las reservas se leen solo por hotel actual.
6. Las CxC se leen solo por hotel actual.
7. Los documentos se leen solo mediante `documentosPorEntidad` con hotel actual.
8. El score o clasificacion no se persiste.
9. No se crean recordatorios, tareas ni notificaciones automaticas.
10. No se toca Caja, pagos, cobros, check-in/check-out, reservaciones ni `/api/sync`.

## QA manual futura

Cuando se implemente 11A-A:

1. Abrir `/huespedes/{id}` con sesion de hotel.
2. Confirmar que el bloque nuevo muestra `Solo lectura`.
3. Confirmar datos base del huesped correcto.
4. Confirmar reservaciones historicas/proximas del mismo hotel.
5. Confirmar saldo CxC pendiente si existe.
6. Confirmar documentos vinculados o estado vacio.
7. Confirmar vehiculos registrados o estado vacio.
8. Confirmar que no hay formularios POST nuevos.
9. Confirmar que los formularios existentes siguen funcionando visualmente igual.
10. Confirmar que no cambia ningun dato por abrir la ficha.
11. Ejecutar preflight/health y confirmar `ERROR: 0`.

## Rollback esperado de una futura 11A-A

Si una implementacion futura falla:

- retirar el bloque visual read-only de `huespedes/ver.php`;
- retirar metodos read-only agregados;
- retirar validaciones CLI nuevas;
- retirar referencias documentales de 11A-A;
- no ejecutar SQL;
- no tocar reservas, Caja, CxC, documentos, tareas, permisos/auth, PWA/offline ni
  `/api/sync`.

## Siguiente paso seguro

Despues de este contrato, el paso seguro seria 11A-A solo con autorizacion explicita
para implementar un bloque read-only pequeno en la ficha de huesped y una validacion
CLI/read-only.

## Seguimiento 11A-A

Estado posterior: `PERFIL_11A_A_HUESPED_READONLY_IMPLEMENTADO_QA_MANUAL_PENDIENTE`.

La implementacion 11A-A se realizo con autorizacion explicita para tocar
`HuespedController`, `Huesped`, `huespedes/ver.php` y validacion CLI/read-only.

Documento de implementacion:

- `docs/fase_11A_A_perfil_huesped_readonly.md`.

No se agregaron rutas nuevas, migraciones, formularios, POST ni escrituras.

Seguimiento posterior:

- 11A-A fue validada manualmente por el usuario.
- 11A-F cierra documentalmente el bloque en
  `docs/fase_11A_F_cierre_perfil_huesped_readonly.md`.
