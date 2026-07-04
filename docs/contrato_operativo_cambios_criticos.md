# Contrato operativo para cambios criticos

Fecha: 2026-07-03

Este contrato existe para evitar que una vista nueva rompa flujos ya testeados.
Aplica antes de modificar cualquier parte conectada con reservaciones, pagos,
check-in, check-out, cuentas por cobrar, facturacion, habitaciones o caja.

Decision de producto:

- No se agregan vistas, botones, endpoints ni automatismos nuevos sobre esta
  frontera sin declarar impacto y prueba.
- Si una idea no respeta este contrato, se rechaza o se parte en una fase menor.
- El objetivo no es avanzar rapido; es avanzar sin romper recepcion ni caja.

## Frontera protegida

Flujo critico protegido:

```text
huesped
-> reservacion
-> anticipos / pagos
-> check-in
-> habitacion ocupada
-> cuenta por cobrar si queda saldo
-> solicitud de factura si aplica
-> cobro / reversion
-> check-out
-> habitacion en limpieza
-> caja / corte / movimientos
```

Modulos que se consideran acoplados:

- Reservaciones
- Habitaciones
- Caja
- Cuentas por cobrar
- Facturacion
- Huespedes cuando participa en reservacion o factura
- Reportes solo si leen dinero, ocupacion, estados o caja

## Regla antes de implementar

Antes de tocar codigo, el responsable debe escribir en el ticket, chat o doc de
fase esta ficha minima:

```md
## Contrato previo

Objetivo:

Archivos que voy a tocar:

Archivos que NO voy a tocar:

Flujos que pueden afectarse:

Datos o tablas que se leen:

Datos o tablas que se escriben:

Formularios afectados:

Pruebas manuales obligatorias:

Comandos de validacion:

Como se revierte si rompe algo:

Decision del mentor/jefe:
- [ ] Aprobado
- [ ] Aprobado con alcance reducido
- [ ] Rechazado por riesgo operativo
```

Si no se puede responder esa ficha, no se implementa.

## Lineas rojas

No tocar sin autorizacion explicita y contrato propio:

- Calculos de caja, cortes o movimientos.
- Logica profunda de check-in/check-out.
- Creacion automatica de cobros, pagos, devoluciones o reversiones.
- Estados de reservacion o habitacion.
- Migraciones o cambios de esquema.
- Rutas, permisos, auth o tenant scope.
- PWA/offline, IndexedDB, cache names, service worker o `/api/sync`.

Si el cambio necesita tocar alguno de esos puntos, primero se abre una fase de
auditoria/read-only. La implementacion viene despues.

## Reglas para formularios

Cuando el cambio toque una vista con formulario:

- No cambiar `action`.
- No cambiar `method`.
- No cambiar `name`.
- No quitar CSRF.
- No quitar hidden inputs.
- No mover submit fuera del form.
- No anidar forms.
- No agregar validacion agresiva que impida capturar o borrar datos parciales.
- Los errores deben aparecer junto al campo y no romper el layout.

## Regresion minima obligatoria

Cada cambio en la frontera protegida debe probar, segun aplique:

### Reservacion sin pago

- Crear o usar reservacion sin anticipo.
- Confirmar saldo pendiente.
- Confirmar que no se crea movimiento de caja fantasma.

### Reservacion con anticipo parcial

- Registrar anticipo menor al total.
- Ver saldo pendiente en detalle.
- Ver movimiento en caja con metodo correcto.
- Si pide factura, ver solicitud relacionada.

### Reservacion pagada completa

- Confirmar que check-in no pide pago extra.
- Confirmar total pagado correcto.
- Confirmar que no genera CxC innecesaria.

### Check-in

- Hacer check-in con saldo pendiente.
- Hacer check-in sin saldo pendiente.
- Confirmar habitaciones ocupadas.
- Confirmar timeline/estado de reservacion.

### Cuentas por cobrar

- Confirmar que la reservacion con saldo aparece en CxC.
- Generar cuenta operativa si aplica.
- Cobrar parcial o total.
- Revertir cobro.
- Confirmar saldo restaurado despues de reversion.

### Facturacion

- Solicitud creada desde anticipo o pago marcado para factura.
- Guardar datos fiscales validos.
- Probar error de RFC o CP fiscal sin perder datos.
- Marcar en proceso.
- Marcar como facturada con folio externo.
- Cancelar solicitud con motivo si aplica.

### Check-out

- Intentar check-out con saldo pendiente.
- Confirmar bloqueo o aviso claro.
- Cobrar saldo.
- Hacer check-out.
- Confirmar habitaciones en limpieza.

### Caja

- Validar ingreso por anticipo/pago/cobro.
- Validar reversion como movimiento contrario.
- Validar efectivo/tarjeta/transferencia en resumen por metodo.
- Confirmar que el corte abierto recibe el movimiento esperado.

## Checklist rapido por cambio

Usar esta lista como semaforo antes de decir "ya quedo":

- [ ] La pantalla carga sin 500.
- [ ] No hay error visible de PHP/SQL.
- [ ] No hay error JS que bloquee el flujo.
- [ ] Los conteos coinciden con lo mostrado.
- [ ] Los filtros no mezclan datos de otra seccion.
- [ ] Los botones llevan a la ruta correcta.
- [ ] El formulario conserva datos al fallar.
- [ ] Los mensajes de error no rompen el layout.
- [ ] Los saldos coinciden entre reservacion, CxC y factura.
- [ ] Caja refleja solo movimientos reales.
- [ ] Reversion restaura saldo y deja trazabilidad.
- [ ] Habitaciones quedan en estado correcto.
- [ ] No aparece informacion de otro hotel.
- [ ] En mobile no se enciman controles principales.

## Validaciones tecnicas minimas

Despues de modificar PHP:

```bash
docker exec medisoft_hoteles_app php -l /var/www/html/app/ruta/del/archivo.php
```

Si se toca JavaScript:

```bash
node --check src/public_html/js/archivo.js
```

Si se toca una vista critica, hacer al menos una revision visual en navegador
con datos reales del flujo afectado.

## Criterio de cierre

Un cambio en esta frontera solo se considera cerrado cuando:

- La ficha previa existe.
- Las pruebas aplicables estan marcadas como PASS.
- Los bugs nuevos quedan corregidos o documentados con severidad.
- El usuario tester confirma el flujo principal.
- El mentor/jefe acepta que el alcance no rompio la frontera protegida.

Si una implementacion rompe un flujo ya aprobado, se pausa la feature nueva y se
repara la regresion antes de seguir.

