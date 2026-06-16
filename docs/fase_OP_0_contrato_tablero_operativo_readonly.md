# Fase OP-0 - Contrato de Tablero Operativo Diario read-only

Estado formal: `CONTRATO_OP_0_TABLERO_OPERATIVO_READONLY_COMPLETADO`.

## Objetivo

Definir una fase futura para un tablero operativo diario que consolide informacion ya
existente del hotel en modo solo lectura, sin crear tareas, sin cambiar estados, sin
afectar habitaciones, Caja, pagos, abonos, nomina ni sincronizacion offline.

El tablero debe ayudar al usuario operativo a revisar rapidamente:

- ocupacion y reservaciones del dia;
- habitaciones con estatus relevante;
- tareas operativas abiertas o vencidas;
- mantenimientos registrados;
- trabajadores y carga operativa si existen;
- documentos recientes vinculados a entidades operativas;
- alertas de operacion que ya puedan inferirse de datos existentes.

## Alcance permitido en una fase futura OP-A

- Crear una ruta GET protegida, por ejemplo `/operacion/diaria` o equivalente segun el
  patron del proyecto.
- Crear controlador, modelo/servicio y vista read-only.
- Reutilizar datos existentes, siempre filtrados por `hotel_id`.
- Mostrar estados vacios claros cuando no existan registros.
- Agregar navegacion si es coherente con el sidebar actual.
- Actualizar health/preflights para validar ruta, guardas y ausencia de acciones.
- Documentar QA, rollback, fuentes de verdad y decisiones.

## Fuera de alcance

- No crear, asignar, iniciar, completar o cancelar tareas.
- No cambiar estado de habitaciones.
- No ejecutar check-in/check-out.
- No modificar reservaciones.
- No crear movimientos de mantenimiento.
- No crear pagos, abonos, liquidaciones ni movimientos de Caja.
- No calcular nomina.
- No modificar `/api/sync`, service worker, IndexedDB, caches ni flujos offline.
- No crear migraciones en OP-0.
- No escribir datos desde el tablero.

## Fuentes de verdad previstas

- `reservaciones` para agenda y ocupacion operativa.
- `habitaciones` y `tipos_habitacion` para estado fisico/comercial.
- `tareas_operativas` y `tarea_eventos` para carga operativa.
- `mantenimientos_habitaciones` solo como lectura historica.
- `trabajadores` solo para nombres/carga si existen registros activos.
- `documentos` y `documento_entidades` solo para metadatos documentales recientes.

Si alguna tabla no existe o esta vacia, la fase futura debe degradar con estado vacio,
no con error fatal.

## Semaforo de riesgo

- Verde: reporte GET/read-only, estados vacios, navegacion segura.
- Amarillo: joins cruzados entre reservaciones, habitaciones y tareas; deben validar
  `hotel_id` de forma estricta.
- Rojo: cualquier POST, cambio de estado, recalculo financiero, escritura en Caja,
  pago/abono, nomina automatica o cambio en `/api/sync`.

## Definition of Done de OP-A

- Ruta GET protegida por sesion y hotel actual.
- Modelo/servicio centraliza consultas read-only con `hotel_id`.
- Vista no contiene formularios operativos ni POST.
- No se muestran rutas internas de documentos.
- HTTP sin sesion redirige o bloquea segun patron existente.
- Health/preflight detecta que el tablero sigue siendo read-only.
- `php -l` pasa en archivos PHP tocados.
- `git diff --check` sin errores.
- Documentacion, rollback y QA quedan actualizados.

## Rollback previsto

Como OP-0 solo documenta contrato, el rollback es revertir el commit documental.

Para una futura OP-A, el rollback esperado seria retirar ruta GET, controlador, vista,
metodos read-only y entradas de navegacion/checkers, sin tocar datos.

## QA futura

- Abrir el tablero con sesion de hotel y confirmar datos o estados vacios.
- Confirmar que todo queda filtrado por hotel actual.
- Confirmar que no hay botones de escritura ni formularios POST.
- Confirmar que no hay Caja, pagos, abonos, nomina ni cambios de habitacion.
- Confirmar HTTP sin sesion.
- Confirmar que `/api/sync` sigue bloqueado por checker.

