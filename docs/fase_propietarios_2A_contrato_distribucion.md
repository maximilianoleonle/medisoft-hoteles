# Fase propietarios 2A - contrato de distribucion

## Objetivo

Preparar la base tecnica para soportar multiples propietarios por hotel sin cambiar todavia los calculos activos de Caja, Reportes, PDF, reservaciones ni base de datos.

Esta fase agrega un servicio aislado:

- `src/app/services/PropietarioDistribucionService.php`

El servicio replica la regla legacy actual:

- Si el tipo de habitacion contiene `manolo`, el ingreso se asigna a `manolo`.
- Cualquier otra habitacion se asigna al propietario default `elia`.
- Si una reservacion combina habitaciones de distintos propietarios, reparte el monto proporcionalmente al `precio_base`.
- Si no hay precio total, todo se asigna al propietario default.
- El ajuste por redondeo se suma al propietario con mayor monto calculado.

## Alcance realizado

- No se modifican modelos.
- No se modifican rutas.
- No se modifican migraciones.
- No se modifica base de datos.
- No se modifican calculos vivos de caja.
- No se modifican calculos vivos de reportes.
- No se modifica logica de reservaciones.
- No se modifica check-in/check-out.
- No se modifica PWA, offline, IndexedDB ni `/api/sync`.

## Puntos legacy detectados

- `src/app/models/Caja.php`
  - `obtenerIngresosPorTipoHabitacion()`
  - Devuelve `manolo`, `elia` y opcionalmente `_otros`.
  - Incluye `detalle` y `cantidad_reservas`.

- `src/app/controllers/ReportesController.php`
  - `obtenerIngresosPorPropiedad()`
  - Devuelve `manolo` y `elia`.
  - Incluye `total` y `reservas`.

- `src/includes/ReporteCortePDF.php`
  - Renderiza secciones fijas Manolo/Elia.

- `src/app/includes/ReporteCortePDF.php`
  - Renderiza secciones fijas Manolo/Elia.

- `src/app/views/reportes/ReportePDF.php`
  - Renderiza columnas fijas Manolo/Elia.

## Contrato propuesto de configuracion

La configuracion futura puede guardarse en `hotel_configuracion` como JSON, sin requerir migracion inicial:

```json
{
  "version": 1,
  "propietario_default": "elia",
  "propietarios": {
    "manolo": {
      "key": "manolo",
      "nombre": "Manolo",
      "activo": true
    },
    "elia": {
      "key": "elia",
      "nombre": "Elia",
      "activo": true
    }
  },
  "reglas_tipo_contiene": {
    "manolo": "manolo"
  },
  "habitaciones": [
    {
      "numero": "TURQUESA",
      "propietario_key": "manolo"
    },
    {
      "tipo": "sencilla",
      "propietario_key": "elia"
    }
  ]
}
```

Claves sugeridas:

- `propietarios.distribucion`
- `propietarios.snapshot_activo`

## Estrategia de integracion siguiente

1. Crear una prueba de paridad con movimientos y habitaciones representativos.
2. Conectar `Caja::obtenerIngresosPorTipoHabitacion()` al servicio, manteniendo exactamente el mismo arreglo de salida.
3. Conectar `ReportesController::obtenerIngresosPorPropiedad()` al servicio, manteniendo exactamente el mismo arreglo de salida.
4. Repetir validacion con cortes existentes y reportes PDF.
5. Luego convertir los PDFs para iterar propietarios dinamicos en vez de imprimir columnas fijas Manolo/Elia.

## Criterio de aceptacion para fase 2B

Para aceptar el reemplazo en produccion, con la configuracion legacy default:

- El total por metodo de pago debe coincidir con la version actual.
- El total por propietario debe coincidir con la version actual.
- La cantidad de reservas por propietario debe coincidir con la version actual.
- Los detalles de corte deben conservar huesped, habitaciones, metodo y monto.
- Los ingresos sin reservacion deben seguir separados como `_otros` donde aplique.
