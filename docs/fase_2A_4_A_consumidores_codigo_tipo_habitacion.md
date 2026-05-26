# Fase 2A.4-A - Consumidores de codigo/tipo de habitacion

## Objetivo

Documentar los consumidores de `tipos_habitacion.codigo` y `habitaciones.tipo` antes de decidir si el sistema puede convertir el indice unico global de `tipos_habitacion.codigo` a un indice unico compuesto por hotel.

Esta fase fue solo de auditoria y documentacion. No se modificaron archivos funcionales, no se ejecuto SQL, no se tocaron datos, no se crearon migraciones y no se hizo `ALTER TABLE`.

## Conclusion Principal

`habitaciones.numero` es relativamente simple: en SaaS, distintos hoteles deben poder usar el mismo numero de habitacion y el indice futuro natural es `(hotel_id, numero)`.

`tipos_habitacion.codigo` es el verdadero riesgo. Hoy funciona como clave textual global y varios modulos lo usan directa o indirectamente mediante `habitaciones.tipo`, `tipo_habitacion`, JSON de tarifas, configuraciones de inventario, reportes, APIs y Reservaciones.

Por esa razon, no se debe ejecutar ni preparar todavia la migracion 005 para convertir los unicos globales a unicos por hotel. Primero deben auditarse y planearse los consumidores criticos de `tipos_habitacion.codigo`.

## Archivos Encontrados Por Modulo

### Habitaciones

- `src/app/models/Habitacion.php`
- `src/app/models/Mantenimiento.php`
- `src/app/views/habitaciones/index.php`
- `src/app/views/habitaciones/editar.php`
- `src/app/helpers/habitaciones.php`
- `src/app/helpers/functions.php`

### Inventario

- `src/app/services/InventarioService.php`
- `src/app/services/ConfiguracionInventarioService.php`
- `src/app/models/Inventario.php`
- `src/app/models/InventarioReportes.php`
- `src/app/models/ConfiguracionInventario.php`
- `src/app/views/inventario/ConfiguracionHabitacionService.php`
- `src/app/helpers/integracion_inventario.php`
- `src/app/controllers/ProductoController.php`

### Tarifas

- `src/app/controllers/TarifasController.php`
- `src/app/models/IncrementoTarifa.php`
- `src/app/views/configuracion/tarifas/crear.php`
- `src/app/views/configuracion/tarifas/editar.php`
- `src/app/views/configuracion/tarifas/index.php`
- `src/app/views/configuracion/tarifas/ver.php`

### Reportes Y Dashboard

- `src/app/models/Reporte.php`
- `src/app/controllers/ReportesController.php`
- `src/app/controllers/DashboardController.php`

### Reservaciones

- `src/app/models/Reservacion.php`
- `src/app/controllers/ReservacionController.php`
- `src/app/views/reservaciones/crear.php`
- `src/app/views/reservaciones/editar-habitaciones.php`
- `src/public_html/js/reservaciones/crear.js`

### APIs/PWA

- `src/api/buscar.php`
- `src/api/reservaciones/hoy.php`
- `src/app/controllers/ApiController.php`

### Caja Y Otros

- `src/app/models/Caja.php`
- `src/app/models/MovimientoCaja.php`
- `src/app/models/Huesped.php`

## Hallazgos Clave Por Modulo

### Habitaciones

Uso detectado:

- `habitaciones.tipo` como codigo textual del tipo.
- Helpers visuales como `get_tipo_habitacion()` y `tipo_habitacion_badge()`.

Estado:

- El modulo Habitaciones ya esta scoped por `hotel_id` en su codigo principal.
- El uso textual de `tipo` puede mantenerse por ahora mientras el sistema siga en compatibilidad mono-hotel.

Riesgo: bajo.

Recomendacion: mantener `habitaciones.tipo` textual en esta etapa.

### Inventario

Usos detectados:

```sql
LEFT JOIN habitaciones h ON h.tipo = th.codigo
INNER JOIN tipos_habitacion th ON h.tipo = th.codigo
WHERE ich.tipo_habitacion = ?
```

Tambien se detecta uso de:

- `tipo_habitacion` textual.
- `tipo_habitacion_id`.
- configuraciones de inventario por tipo.

Estado:

- Los joins y filtros no incluyen `hotel_id`.
- Si se permiten codigos repetidos por hotel, Inventario puede aplicar configuraciones de un hotel a otro.

Riesgo: alto.

Recomendacion: esperar a una fase Inventario antes de permitir codigos duplicados por hotel.

### Tarifas

Usos detectados:

```sql
SELECT DISTINCT tipo FROM habitaciones WHERE activa = 1 ORDER BY tipo
```

Tambien se detecta uso de:

- `incrementos_tarifas.tipos_habitacion` como JSON.
- comparaciones por codigo textual en `IncrementoTarifa`.

Estado:

- Las tarifas por tipo dependen de `habitaciones.tipo`.
- La seleccion y aplicacion de incrementos no esta completamente tenant-aware.

Riesgo: medio-alto.

Recomendacion: esperar a una fase Tarifas antes de permitir codigos duplicados por hotel.

### Incrementos De Tarifa

Uso detectado:

```php
if (in_array($tipo_habitacion, $tipos)) {
```

Estado:

- El calculo funciona en mono-hotel.
- En multi-hotel, dos hoteles podrian compartir el mismo codigo con reglas distintas.

Riesgo: medio-alto.

Recomendacion: requerir contexto de hotel antes de permitir duplicidad de codigos.

### Reportes

Usos detectados:

```sql
GROUP BY h.tipo
h.tipo AS tipo_habitacion
```

Estado:

- Los reportes agrupan por tipo textual.
- Si hay codigos duplicados por hotel sin filtro, los agregados pueden mezclarse.

Riesgo: alto.

Recomendacion: esperar a fase Reportes.

### Dashboard

Uso detectado:

```sql
GROUP BY h.tipo
```

Estado:

- Dashboard calcula ingresos y ocupacion por tipo.
- Sin `hotel_id`, puede mezclar informacion entre hoteles.

Riesgo: alto.

Recomendacion: esperar a fase Dashboard.

### APIs/PWA

Usos detectados:

```sql
GROUP_CONCAT(DISTINCT hab.tipo SEPARATOR '||') AS habitaciones_tipos
```

Estado:

- APIs exponen tipos de habitaciones y busquedas.
- PWA/offline puede cachear o consumir datos globales.

Riesgo: alto.

Recomendacion: esperar a fase PWA/API.

### Reservaciones

Usos detectados:

- `hab.tipo` en modelo de Reservacion.
- `hab.tipo` en controlador de Reservacion.
- vistas de creacion y edicion de reservaciones.
- JS de reservaciones agrupando por `hab.tipo`.
- logica relacionada con check-in/check-out.

Estado:

- Reservaciones todavia no tiene `hotel_id`.
- Es zona critica del sistema operativo.

Riesgo: alto.

Recomendacion: esperar a fase Reservaciones.

### Caja Y Otros

Usos detectados:

- consultas de ingresos por tipo de habitacion.
- movimientos que muestran `hab.tipo`.
- datos de huespedes con `tipos_habitacion`.

Estado:

- Se usa principalmente para mostrar o agrupar informacion.
- Puede afectar reportes financieros si se mezclan codigos entre hoteles.

Riesgo: medio-alto.

Recomendacion: esperar a fases Caja/Reportes.

## Clasificacion De Riesgo

| Modulo | Riesgo | Motivo |
| --- | --- | --- |
| Habitaciones | Bajo | Ya esta scoped por `hotel_id` en el codigo principal. |
| Inventario | Alto | Usa joins y configuraciones por codigo/tipo sin `hotel_id`. |
| Configuracion de inventario | Alto | Puede mezclar reglas de productos entre hoteles. |
| Tarifas | Medio-alto | Usa tipos textuales y JSON por tipo de habitacion. |
| Incrementos de tarifa | Medio-alto | Compara codigos textuales sin contexto de hotel. |
| Reportes | Alto | Agrupa por `h.tipo` y podria mezclar hoteles. |
| Dashboard | Alto | Calcula agregados por tipo sin aislamiento completo. |
| APIs/PWA | Alto | Expone y puede cachear tipos globales. |
| Reservaciones | Alto | Afecta check-in/check-out, edicion y disponibilidad. |
| Caja/Otros | Medio-alto | Puede mezclar ingresos o movimientos por tipo. |

## Decisiones

- Mantener `habitaciones.tipo` textual por ahora.
- No migrar todavia a `tipo_habitacion_id`.
- No preparar todavia migracion 005.
- No crear segundo hotel fake todavia.
- No permitir codigos duplicados por hotel todavia.
- No cambiar indices unicos hasta que consumidores criticos esten scoped.

## Riesgos Si Se Cambia El Indice Ahora

- Inventario podria descontar o configurar productos usando el tipo de otro hotel.
- Tarifas podrian aplicarse a tipos incorrectos.
- Reportes podrian agrupar datos de varios hoteles bajo el mismo codigo.
- Dashboard podria mostrar ingresos u ocupacion mezclada.
- APIs/PWA podrian exponer datos cruzados o cachear tipos globales.
- Reservaciones podrian asociar tipos incorrectos en creacion, edicion o check-in/check-out.
- Caja y reportes financieros podrian mezclar ingresos por tipo.

## Siguiente Fase Recomendada

La siguiente fase recomendada es una auditoria/plano especifico de consumidores criticos:

```text
Fase 2A.4-B - Plan de aislamiento para consumidores de tipos_habitacion.codigo
```

Alcance recomendado:

- Definir orden de correccion por modulo.
- Separar Inventario, Tarifas, Reportes, APIs/PWA y Reservaciones.
- Proponer patrones de join con `hotel_id`, por ejemplo `h.tipo = th.codigo AND h.hotel_id = th.hotel_id`.
- Decidir en que punto conviene migrar gradualmente hacia `tipo_habitacion_id`.
- Decidir cuando crear un segundo hotel fake para pruebas.

No se recomienda ejecutar indices unicos compuestos hasta que Inventario, Tarifas, Reportes, APIs/PWA y Reservaciones tengan una estrategia de aislamiento clara.

## Que NO Se Toco

- No se toco Inventario.
- No se tocaron Tarifas.
- No se tocaron Reservaciones.
- No se toco Caja.
- No se tocaron APIs/PWA.
- No se tocaron Reportes.
- No se toco Dashboard.
- No se toco base de datos.
- No se cambiaron indices.
- No se crearon migraciones.
- No se modifico codigo funcional.
