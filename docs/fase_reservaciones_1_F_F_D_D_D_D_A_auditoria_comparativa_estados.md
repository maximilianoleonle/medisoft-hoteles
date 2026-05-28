# Reservaciones 1-F-F-D-D-D-D-A - Auditoría de comparativa de estados

## Objetivo

Auditar `obtenerComparativaEstados()` antes de hacerlo tenant-aware, porque tiene subconsultas de período actual/anterior y puede romper comparativas si el scope no se aplica igual en todos los bloques.

## Hallazgo principal

- `obtenerComparativaEstados()` sigue global.
- Usa `reservaciones` y `huespedes` sin `r.hotel_id = ?`.
- Las subconsultas actual y anterior están globales.
- La lista base de estados sale directamente de `huespedes`, lo cual no debe usarse como fuente de scope.
- `huespedes` debe seguir como join auxiliar por `r.huesped_id = h.id`.

## Ubicación

- `src/app/models/Reporte.php`
- Método: `obtenerComparativaEstados()`

## Consultas internas detectadas

- `estados`: `SELECT DISTINCT procedencia_estado FROM huespedes`, global.
- `actual`: cuenta reservaciones por estado en el período actual, global.
- `anterior`: cuenta reservaciones por estado en el período anterior, global.
- Cálculo final: compara `total_actual` contra `total_anterior` y calcula `variacion_porcentaje`.

## Riesgos

- Si solo se scopea actual pero no anterior, la comparación queda corrupta.
- Si `estados` sigue saliendo de `huespedes` global, puede mostrar estados no pertenecientes al hotel actual.
- Huespedes aún no tiene modelo tenant.
- La vista física `ranking-estados.php` no existe.
- PDFs/exportaciones deben seguir fuera de alcance.
- La comparativa puede generar porcentajes incorrectos si actual/anterior no usan el mismo `hotel_id`.

## Propuesta técnica

- Agregar `hotel_id` con `$this->hotelIdActual()`.
- Scopear actual con `r.hotel_id = ?`.
- Scopear anterior con `r.hotel_id = ?`.
- Tratar `huespedes` solo como join auxiliar.
- Reemplazar la lista base global de `huespedes` por una lista derivada de reservaciones scoped del período actual y anterior, idealmente con `UNION`.
- Mantener intacta la fórmula:

```text
((actual - anterior) * 100) / anterior
```

- Conservar el manejo de `anterior = 0`.

## Parámetros esperados

Si se usa lista base scoped con `UNION`, los parámetros deben cubrir:

- hotel/período actual.
- hotel/período anterior.
- hotel/período actual para subconsulta actual.
- hotel/período anterior para subconsulta anterior.

## Qué NO tocar

- PDFs/exportaciones.
- PWA/offline.
- Sync.
- APIs globales.
- Migraciones/schema.
- Cambios de datos.
- Huespedes tenant.
- `rankingEstadosAction()`, salvo documentarlo como dependiente.

## Siguiente fase recomendada

Reservaciones 1-F-F-D-D-D-D-B: implementar únicamente `obtenerComparativaEstados()` scoped por `hotel_id`, sin tocar PDFs/exportaciones ni `rankingEstadosAction()`.

## Pruebas necesarias

- `php -l src/app/models/Reporte.php`.
- `git diff --check`.
- `verificar_estado.php` PASS.
- `preflight_hotel_id.php` PASS.
- Validación por revisión de query para confirmar que actual y anterior usan `r.hotel_id`.
- Validación de que la lista base de estados no sale de `huespedes` global.
- Confirmar que no se tocaron PDFs/PWA/Sync/APIs.
- Confirmar 0 nuevos `hotel_id` NULL.
