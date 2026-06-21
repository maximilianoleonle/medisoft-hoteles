# Fase 5E-K-0 - Contrato cierre/aprobacion de pre-nomina

Estado formal:
`CONTRATO_5E_K_0_CIERRE_APROBACION_PRENOMINA_READ_ONLY_COMPLETADO`.

## Objetivo

Definir el contrato para una futura revision, congelamiento y aprobacion controlada
de un periodo de pre-nomina, sin implementar todavia codigo, rutas, controladores,
modelos, vistas, servicios, migraciones, permisos, Caja, datos, storage, PWA/offline
ni `/api/sync`.

Esta fase solo documenta el alcance. No autoriza por si misma ningun flujo mutante.
Toda ruta POST, persistencia, migracion, permiso o cambio en Caja requiere una
autorizacion explicita posterior.

## Naturaleza del cierre futuro

El cierre futuro debe ser:

- controlado por hotel;
- ligado a un periodo obligatorio;
- derivado del preview de pre-nomina y del recibo laboral informativo;
- visible como flujo operativo interno, no fiscal;
- auditable solo si una fase futura autoriza persistencia;
- reversible o anulable solo con contrato adicional;
- separado de pagos reales con Caja.

El cierre de pre-nomina no debe confundirse con nomina oficial, CFDI, timbrado,
dispersion bancaria, pago masivo ni recibo fiscal.

## Rutas candidatas futuras

Lectura read-only candidata:

- `GET /trabajadores/nomina/periodos`
- `GET /trabajadores/nomina/periodos/preview`

Acciones mutantes candidatas solo con autorizacion adicional:

- `POST /trabajadores/nomina/periodos/cerrar`
- `POST /trabajadores/nomina/periodos/{id}/aprobar`
- `POST /trabajadores/nomina/periodos/{id}/anular`

Las rutas mutantes no quedan implementadas ni autorizadas en 5E-K-0. Si se crea una
fase 5E-K-A, debe separar explicitamente lectura, cierre, aprobacion y anulacion.

## Datos esperados de un periodo futuro

Una implementacion futura deberia considerar, como minimo:

- hotel actual;
- fecha inicio;
- fecha fin;
- trabajadores incluidos;
- trabajadores excluidos por bloqueo;
- bruto laboral;
- conceptos a favor;
- conceptos en contra;
- anticipos pendientes informativos;
- prestamos vigentes informativos;
- pagos Caja aplicados solo como lectura;
- pagos Caja revertidos solo como lectura;
- neto sugerido;
- pendiente sugerido;
- advertencias y bloqueos;
- usuario que cierra o aprueba, solo si se autoriza persistencia;
- fecha/hora de cierre o aprobacion, solo si se autoriza persistencia;
- snapshot inmutable, solo si se autoriza persistencia.

## Reglas de elegibilidad

Una futura accion de cierre/aprobacion debe bloquearse si:

- falta periodo;
- el periodo tiene fechas invalidas;
- el usuario no pertenece al hotel actual;
- se intenta incluir trabajadores de otro hotel;
- existen calculos ambiguos;
- existen bloqueos de datos laborales;
- el preview de pre-nomina no puede recalcularse;
- se intenta disparar pagos, Caja, timbrado o dispersion implicitamente.

## Prohibido

Queda prohibido en 5E-K-0 y en cualquier implementacion futura sin contrato
adicional:

- crear nomina oficial;
- timbrar;
- generar CFDI;
- emitir recibos fiscales;
- generar folios oficiales;
- dispersar pagos;
- registrar pagos masivos;
- crear movimientos de Caja;
- liquidar anticipos o prestamos automaticamente;
- modificar cortes de Caja;
- guardar snapshots sin migracion autorizada;
- crear auditoria por simple visualizacion;
- tocar permisos/auth sin autorizacion;
- tocar PWA/offline, IndexedDB, cache names o `/api/sync`.

## Archivos candidatos para una futura 5E-K-A

Solo con autorizacion explicita se podrian tocar:

- `src/config/routes.php`
- `src/app/controllers/TrabajadorController.php`
- `src/app/models/Trabajador.php`
- vista nueva o existente de pre-nomina/periodos
- servicio especifico de cierre/aprobacion si se justifica
- checkers/preflight de pagos laborales Caja
- documentacion de fase

Si se requiere persistencia, tambien harian falta autorizacion separada para
migraciones y backup previo de base de datos.

## QA futura sugerida

1. Abrir la lectura de periodos sin sesion y confirmar redireccion a login.
2. Confirmar que el preview de periodo sigue siendo read-only.
3. Confirmar que un periodo sin fechas validas queda bloqueado.
4. Confirmar que no se mezclan trabajadores de otros hoteles.
5. Confirmar que cierre/aprobacion futura exige CSRF, permiso y token si hay POST.
6. Confirmar que no se generan movimientos de Caja.
7. Confirmar que no se genera CFDI, timbrado, dispersion ni pago masivo.
8. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
   `sync_temporarily_disabled`.

## Criterio de avance futuro

La futura 5E-K-A solo debera iniciar si el usuario autoriza explicitamente tocar
rutas, controlador, modelo, vista, checkers y, si aplica, servicio, migraciones,
permisos o Caja. Sin esa autorizacion, el siguiente paso debe mantenerse
documental/read-only.