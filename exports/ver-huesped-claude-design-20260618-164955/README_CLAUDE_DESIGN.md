# Brief para Claude Design: rediseño de "Ver huésped"

Objetivo: rehacer el diseño visual de la vista `src/app/views/huespedes/ver.php` sin omitir ninguna sección, estado ni acción existente. El resultado debe seguir siendo una vista PHP compatible con el MVC actual.

## Archivo principal
- `src/app/views/huespedes/ver.php`

## No omitir estas secciones
- Breadcrumb de navegación: `Huespedes > huésped actual`.
- Hero / identidad del huésped: avatar, nombre, chips de ID, procedencia, contacto y cliente frecuente.
- Acciones principales: nueva reservación, editar información, volver al directorio.
- Métricas del huésped: visitas válidas, total gastado, promedio y vehículos.
- Panel de datos del huésped: teléfono, email, procedencia, última visita, vehículo anterior si existe.
- Notas del huésped, cuando existan.
- Panel de vehículos registrados: cards, detalles, editar, eliminar y estados vacíos.
- Modal/formulario de vehículo: alta/edición con todos sus campos y opciones de estacionamiento.
- Historial de reservaciones: estado vacío y lista con fechas, montos, estado y link a reservación.
- Columna lateral: score/resumen, acciones rápidas y datos de sistema.
- Documentos vinculados: partial `src/app/views/partials/documentos_entidad.php` si se conserva el vínculo documental.
- Scripts JS de agregar/actualizar/eliminar vehículos y comportamiento de modales.

## Reglas de implementación
- No cambiar nombres de inputs, rutas, métodos, CSRF, hidden inputs, fetch URLs ni acciones de formularios.
- No quitar condicionales PHP ni variables calculadas.
- No eliminar permisos, validaciones, IDs de entidad, ni URLs existentes.
- Puede reorganizar layout, clases y CSS visual, pero debe conservar toda funcionalidad.
- Usar tokens del hotel (`--brand-*`) y evitar identidad Medisoft (`--ms-*`) en esta vista operativa.
- Mantener responsivo desktop/tablet/móvil.
- Si se cambia estructura, revisar que los modales y botones sigan encontrando los mismos IDs/clases que usa JS.

## Datos disponibles en la vista
- `$huesped`
- `$vehiculos`
- `$reservaciones`
- `$total_reservaciones`
- `$total_gastado`
- `$ultima_visita`
- `$documentosEntidad`
- `$documentosEntidadContexto`

## Archivos de contexto incluidos
- Controlador y modelos del flujo de huéspedes.
- Layout general (`header`, `sidebar`, `footer`) para entender shell visual.
- Partial de documentos vinculados.
- CSS global de layout hotelero/sidebar.

## Entregable deseado
Un rediseño de `src/app/views/huespedes/ver.php` completo, sin secciones omitidas, listo para integrarse de vuelta al proyecto.
