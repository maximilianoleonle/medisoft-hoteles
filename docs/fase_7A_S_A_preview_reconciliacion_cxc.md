# Fase 7A-S-A - Preview reconciliacion CxC read-only

## Estado

`PREVIEW_7A_S_A_RECONCILIACION_CXC_READONLY_COMPLETADO`

## Objetivo

Construir una matriz de decision read-only para los datos que bloquean una CxC
operativa, sin crear rutas, sin modificar modelos, sin tocar base de datos, sin Caja y
sin `/api/sync`.

Esta subfase no corrige datos. Solo clasifica candidatos y propone la decision segura
por defecto.

## Alcance ejecutado

- Consultas SQL read-only contra datos actuales.
- Matriz de pagos huerfanos.
- Matriz resumida de solicitudes de factura huerfanas.
- Lista de solicitudes de factura scoped validas.
- Matriz de saldos excedentes.
- Decision segura por defecto por grupo.

No se ejecuta:

- `INSERT`
- `UPDATE`
- `DELETE`
- migraciones
- cambios en rutas/modelos/vistas
- cobros CxC
- movimientos de Caja
- cambios en reservaciones, pagos, abonos o facturacion

## Resultado general

| Grupo | Registros | Hotel principal | Decision segura por defecto | Decision pendiente |
| --- | ---: | --- | --- | --- |
| Pagos huerfanos | 3 | Los Cedros / Hotel Demo SaaS | Excluir de CxC operativa | Clasificar si son prueba, historico, asociables o anulables |
| Facturas huerfanas | 170 | Los Cedros | Excluir de CxC operativa | Definir conservar, archivar logicamente, asociar o excluir permanentemente |
| Facturas scoped validas | 5 | Los Cedros / Maximiliano Leon | Mantener en reporte read-only | Ninguna inmediata |
| Excedentes | 3 | Maximiliano Leon | Mantener como excedente informativo | Definir credito, devolucion, ajuste o sin accion |

## Matriz 1 - Pagos huerfanos

| pago_id | hotel_id | hotel | reservacion_id | metodo | monto | created_at | decision_default | decision_requerida |
| ---: | ---: | --- | ---: | --- | ---: | --- | --- | --- |
| 1 | 2 | Hotel Demo SaaS | 1 | efectivo | 550.00 | 2026-06-02 17:24:30 | excluir_cxc_operativa | pendiente_clasificacion_usuario |
| 2 | 1 | Los Cedros | 5 | efectivo | 600.00 | 2026-06-04 15:30:50 | excluir_cxc_operativa | pendiente_clasificacion_usuario |
| 3 | 1 | Los Cedros | 6 | efectivo | 600.00 | 2026-06-06 22:00:36 | excluir_cxc_operativa | pendiente_clasificacion_usuario |

Lectura:

- No tienen reservacion scoped por `hotel_id + reservacion_id`.
- No deben convertirse en CxC.
- No deben moverse a reservaciones sin decision manual.
- No deben generar Caja.

Opciones futuras:

- Marcar/documentar como prueba o demo.
- Asociar a una reservacion valida solo con evidencia.
- Anular contablemente solo con contrato financiero separado.
- Mantener excluidos permanentemente.

## Matriz 2 - Solicitudes de factura huerfanas

| hotel_id | hotel | estatus | requiere_factura | registros | rango_id | primera | ultima | monto_total | decision_default |
| ---: | --- | --- | --- | ---: | --- | --- | --- | ---: | --- |
| 1 | Los Cedros | en_proceso | si | 1 | 192-192 | 2026-05-09 01:46:00 | 2026-05-09 01:46:00 | 2200.00 | excluir_cxc_operativa |
| 1 | Los Cedros | completada | si | 59 | 21-217 | 2026-03-03 01:33:05 | 2026-05-19 01:17:18 | 83400.00 | excluir_cxc_operativa |
| 1 | Los Cedros | completada | no | 109 | 19-220 | 2026-03-03 00:18:03 | 2026-05-19 21:17:12 | 370200.00 | excluir_cxc_operativa |
| 1 | Los Cedros | cancelada | no | 1 | 139-139 | 2026-04-14 01:17:48 | 2026-04-14 01:17:48 | 2400.00 | excluir_cxc_operativa |

Lectura:

- Los 170 registros pertenecen a Los Cedros.
- Apuntan a reservaciones inexistentes para el hotel.
- Su rango temporal principal es marzo a mayo de 2026.
- No deben crear deuda CxC.
- No deben borrarse automaticamente.

Opciones futuras:

- Conservar como historico informativo.
- Archivar logicamente si se define un campo/flujo seguro.
- Asociar solo con evidencia a una reservacion valida.
- Excluir permanentemente de CxC operativa.

## Matriz 3 - Solicitudes de factura scoped validas

| solicitud_id | hotel_id | hotel | reservacion_id | estado_reservacion | huesped | estatus | requiere_factura | monto_total | decision_default |
| ---: | ---: | --- | ---: | --- | --- | --- | --- | ---: | --- |
| 224 | 1 | Los Cedros | 8 | checked_out | Adan Jimenez | pendiente | si | 6100.00 | mantener_en_reporte_readonly |
| 225 | 1 | Los Cedros | 17 | checked_out | Juan Carlos Mendez | pendiente | si | 3400.00 | mantener_en_reporte_readonly |
| 226 | 1 | Los Cedros | 18 | checked_out | Juan Carlos Diaz | pendiente | si | 2200.00 | mantener_en_reporte_readonly |
| 227 | 4 | Maximiliano Leon | 19 | checked_out | Abraham Gonzalez | completada | si | 2450.00 | mantener_en_reporte_readonly |
| 228 | 4 | Maximiliano Leon | 15 | checked_out | Sofia Carrillo Demo H4 | pendiente | si | 4400.00 | mantener_en_reporte_readonly |

Lectura:

- Estas 5 solicitudes si coinciden por `hotel_id + reservacion_id`.
- Pueden seguir mostrandose en el reporte read-only actual.
- No autorizan CxC operativa ni cobros.

## Matriz 4 - Reservaciones con excedente

| reservacion_id | hotel_id | hotel | huesped | estado | total | pagos | abonos | saldo_raw | decision_default | decision_requerida |
| ---: | ---: | --- | --- | --- | ---: | ---: | ---: | ---: | --- | --- |
| 10 | 4 | Maximiliano Leon | Carlos Mendez Demo H4 | checked_out | 3600.00 | 2000.00 | 2000.00 | -400.00 | mantener_excedente_informativo | pendiente_politica_credito_devolucion_ajuste |
| 11 | 4 | Maximiliano Leon | Mariana Torres Demo H4 | checked_out | 1200.00 | 1200.00 | 600.00 | -600.00 | mantener_excedente_informativo | pendiente_politica_credito_devolucion_ajuste |
| 15 | 4 | Maximiliano Leon | Sofia Carrillo Demo H4 | checked_out | 4400.00 | 4400.00 | 1200.00 | -1200.00 | mantener_excedente_informativo | pendiente_politica_credito_devolucion_ajuste |

Lectura:

- El patron es doble cobertura: abono previo + pago completo posterior.
- No son saldos a cobrar.
- No deben convertirse en CxC.
- No se deben borrar abonos ni pagos sin contrato financiero separado.

Opciones futuras:

- Mantener como excedente informativo.
- Registrar credito a favor si se crea entidad financiera especifica.
- Registrar devolucion solo con contrato de Caja/devoluciones.
- Ajuste contable solo con backup, auditoria y autorizacion explicita.

## Decision recomendada por defecto

Mantener el sistema en modo conservador:

1. CxC read-only sigue activa.
2. Pagos huerfanos quedan excluidos de CxC operativa.
3. Facturas huerfanas quedan excluidas de CxC operativa.
4. Facturas scoped validas siguen visibles como contexto.
5. Excedentes quedan como informativos, no como deuda.
6. No se habilita CxC operativa todavia.

## Riesgos si se corrige sin contrato posterior

- Duplicar deuda o cobros.
- Alterar historico fiscal/facturacion.
- Romper trazabilidad de pagos de reservacion.
- Crear diferencias con Caja/cortes.
- Convertir excedentes en deuda incorrecta.

## Siguiente accion segura

7A-S-B queda definida en `docs/fase_7A_S_B_politica_clasificacion_cxc.md`.

La politica vigente acepta la decision por defecto:

- huerfanos excluidos de CxC operativa;
- facturas scoped validas solo como contexto read-only;
- excedentes como informativos.

Si se autoriza escribir despues, debe abrirse una fase separada con backup previo,
script transaccional, auditoria y rollback.
