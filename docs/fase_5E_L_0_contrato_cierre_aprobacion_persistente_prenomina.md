# Fase 5E-L-0 - Contrato cierre/aprobacion persistente de pre-nomina

Estado formal:
`CONTRATO_5E_L_0_CIERRE_APROBACION_PERSISTENTE_PRENOMINA_COMPLETADO`.

## Objetivo

Definir el contrato para una futura implementacion de cierre y aprobacion persistente
de periodos de pre-nomina, partiendo de la pantalla read-only validada en 5E-K-A/F.

Esta fase es documental. No implementa codigo, rutas, controladores, modelos, vistas,
servicios, migraciones, permisos, Caja, datos, storage, PWA/offline ni `/api/sync`.

## Principio central

Cerrar o aprobar un periodo de pre-nomina debe significar guardar una fotografia
controlada del calculo laboral de un periodo. No debe significar pagar, timbrar,
emitir CFDI, dispersar, liquidar anticipos/prestamos ni mover Caja.

El flujo futuro debe separar cuatro conceptos:

- preview read-only;
- cierre persistente de snapshot;
- aprobacion administrativa del snapshot;
- pago laboral real con Caja, que sigue siendo un flujo separado y controlado.

## Rutas candidatas futuras

Lectura:

- `GET /trabajadores/nomina/periodos`
- `GET /trabajadores/nomina/periodos/preview`
- `GET /trabajadores/nomina/periodos/{id}`

Mutaciones candidatas solo con autorizacion adicional:

- `POST /trabajadores/nomina/periodos/cerrar`
- `POST /trabajadores/nomina/periodos/{id}/aprobar`
- `POST /trabajadores/nomina/periodos/{id}/anular`

Estas rutas POST no quedan implementadas ni autorizadas por 5E-L-0.

## Persistencia candidata futura

Solo con autorizacion explicita y backup previo se podria crear persistencia para:

- encabezado del periodo cerrado;
- snapshot por trabajador;
- totales agregados;
- filtros usados;
- estado del periodo;
- usuario y fecha de cierre;
- usuario y fecha de aprobacion;
- motivo de anulacion;
- auditoria de transiciones.

Nombres candidatos de tablas, a confirmar en una fase posterior:

- `trabajador_nomina_periodos`;
- `trabajador_nomina_periodo_detalles`;
- `trabajador_nomina_periodo_eventos`.

5E-L-0 no crea ni modifica ninguna tabla.

## Estados candidatos

Una implementacion futura podria usar estados como:

- `cerrado`;
- `aprobado`;
- `anulado`.

Reglas esperadas:

- un periodo `cerrado` conserva snapshot y permite revision;
- un periodo `aprobado` congela administrativamente el snapshot;
- un periodo `anulado` conserva historial y exige motivo;
- ningun estado genera pago, CFDI, timbrado, dispersion ni movimiento de Caja.

## Reglas de cierre futuro

El cierre futuro debe bloquearse si:

- falta periodo;
- el periodo tiene fechas invalidas;
- el periodo ya fue cerrado para el mismo hotel y rango, salvo politica explicita de
  re-cierre;
- el preview contiene bloqueos;
- hay trabajadores fuera del hotel actual;
- se intenta cerrar con filtros ambiguos;
- falta permiso;
- falta CSRF;
- falta token de un solo uso;
- no existe trazabilidad de usuario;
- el calculo no puede recalcularse en una transaccion controlada.

## Reglas de aprobacion futura

La aprobacion futura debe bloquearse si:

- el periodo no existe;
- el periodo pertenece a otro hotel;
- el periodo no esta cerrado;
- el periodo ya fue aprobado;
- el periodo fue anulado;
- falta permiso;
- falta CSRF;
- falta token de un solo uso;
- se intenta modificar el snapshot aprobado;
- se intenta generar pago, CFDI, timbrado o dispersion implicitamente.

## Reglas de anulacion futura

La anulacion futura debe:

- exigir motivo;
- preservar el snapshot;
- registrar usuario y fecha;
- no borrar fisicamente datos;
- no revertir pagos Caja;
- no tocar movimientos Caja;
- no modificar anticipos, prestamos ni conceptos laborales.

## Auditoria y seguridad

Toda implementacion futura debe incluir:

- permisos explicitos separados de lectura;
- CSRF en cada POST;
- token de un solo uso para acciones sensibles;
- transaccion de base de datos;
- validacion de `hotel_id` en encabezado y detalle;
- bloqueo contra doble cierre accidental;
- registro en `logs_auditoria`;
- rollback documentado;
- backup SQL antes de migraciones o aplicacion en produccion.

## Prohibido

Queda prohibido sin contrato posterior adicional:

- crear nomina oficial;
- crear CFDI;
- timbrar;
- dispersar pagos;
- pagar masivamente;
- crear movimientos de Caja;
- modificar cortes de Caja;
- liquidar anticipos o prestamos automaticamente;
- modificar conceptos laborales por aprobar;
- borrar snapshots;
- crear archivos en storage por defecto;
- enviar recibos por correo o WhatsApp;
- tocar permisos/auth sin autorizacion explicita;
- tocar PWA/offline, IndexedDB, cache names o `/api/sync`.

## QA futura sugerida

1. Intentar cerrar sin sesion y confirmar redireccion a login.
2. Intentar cerrar sin CSRF y confirmar bloqueo.
3. Intentar cerrar sin token de accion y confirmar bloqueo.
4. Cerrar un periodo valido y confirmar snapshot por hotel.
5. Intentar cerrar el mismo periodo dos veces y confirmar bloqueo controlado.
6. Intentar aprobar periodo de otro hotel y confirmar bloqueo.
7. Aprobar periodo cerrado y confirmar que el snapshot no cambia.
8. Anular con motivo y confirmar historial sin borrado fisico.
9. Confirmar que no se crean movimientos de Caja.
10. Confirmar que no hay CFDI, timbrado, dispersion ni pago masivo.
11. Confirmar que `/api/sync` sigue bloqueado con HTTP 423 y
    `sync_temporarily_disabled`.

## Criterio de avance futuro

Una futura 5E-L-A solo debera iniciar con autorizacion explicita para tocar rutas
POST, controlador, servicio, modelo, vista, checkers, migraciones, permisos y backup
de base de datos. Sin esa autorizacion, el flujo debe permanecer read-only.
