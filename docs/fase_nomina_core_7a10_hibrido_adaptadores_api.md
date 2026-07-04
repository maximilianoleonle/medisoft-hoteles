# Nomina Core - Fases 7 a 10: hibrido, adaptadores, multinegocio y API (cierre)

Fecha: 2026-07-04

## Fase 7 - Modo hibrido / exportacion para contador

- GET `/nomina/periodos/{id}/exportar`: CSV UTF-8 (BOM) con cabecera del
  periodo y UNA fila por linea congelada (empleado, concepto, tipo,
  clasificacion, origen, cantidad, base, monto, referencia). Doble gate:
  bloque `exportaciones` + permiso `nomina.exportar` (regla AGENTS.md:
  exportaciones se gatean bajo su propio bloque). Boton en el detalle del
  periodo solo si ambos gates pasan.

## Fase 8 - Adaptador hotel

- Contrato `NominaAdaptadorGiro`: los adaptadores PROPONEN incidencias
  normalizadas (origen 'adaptador', estado 'pendiente'); un humano aprueba.
  Se preserva la regla del sistema: la operacion nunca genera nomina sola.
- `NominaAdaptadorHotel` v1: horas extra de `trabajador_asistencias` ->
  propuestas contra el concepto activo clasificacion horas_extra, cantidad =
  horas, idempotente por referencia_origen 'ASI-{id}' (re-proponer no
  duplica; una propuesta rechazada no se re-propone... se excluyen solo las
  no-rechazadas, por lo que rechazar y re-proponer SI la vuelve a ofrecer).
- Aviso si el concepto no tiene tarifa default. Boton "Proponer desde la
  operacion" en /nomina/incidencias usando el rango de los filtros, y boton
  Aprobar para pendientes.

## Fase 9 - Preparacion multinegocio

- Config `negocio.giro` (hotel default; restaurante, academia, clinica,
  lavanderia, otro) editable en /nomina/configuracion y registrada en el
  registry tenant-safe.
- `NominaAdaptadorRegistry`: giro -> adaptador. Giros sin adaptador devuelven
  null y la UI lo explica (captura manual). Agregar un giro = una clase +
  una linea en el registry; el motor core NO se toca.

## Fase 10 - API interna JSON

- GET `/api/nomina/periodos` (lista), GET `/api/nomina/periodos/{id}`
  (cabecera + detalles + lineas congeladas), POST `/api/nomina/incidencias`
  (CSRF + permiso, respuestas 403/405/422 JSON). Mismo gate de bloque
  (require_hotel_module responde JSON en rutas /api/). Sin microservicio:
  endpoints internos reutilizables, como pedia el contrato.

## Pruebas (sesion real, 16/16 PASS)

CSV con bloque ON (contenido real) y bloqueado con bloque OFF; adaptador
propone 3.50 hrs pendientes, idempotente, aprobacion funciona; API JSON con
sesion (lista + detalle con lineas) y rechazada sin sesion; `php -l` limpio
en los 9 archivos tocados.

## Riesgos documentados

1. El adaptador v1 solo cubre horas extra; faltas/retardos requieren definir
   politica de descuento por negocio (backlog Fase 8.1).
2. La API expone datos salariales a cualquier usuario con nomina.view del
   negocio; endpoints de detalle podrian requerir nomina.salarios si el owner
   lo pide.
3. `negocio.giro` es configurable por el negocio (nomina.configurar); si el
   owner prefiere que solo Medisoft cambie el giro, moverlo al panel SaaS.
