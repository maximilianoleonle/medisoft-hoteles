# Auditoria de formularios: proveedores y compras

Fecha: 2026-06-25

## Alcance

- `src/app/controllers/ProveedorController.php`
- `src/app/controllers/CompraController.php`
- `src/app/views/proveedores/form.php`
- `src/app/views/compras/form.php`

No se tocaron rutas, modelos, base de datos, migraciones, permisos, auth, PWA/offline ni calculos de inventario.

## Hallazgos

### P2 - Errores guardados pero no visibles junto al campo

Los controladores ya guardaban `save_old_input()` y `save_form_errors()`, pero los formularios de proveedores y compras no pintaban esos errores junto a sus campos.

Impacto:

- El usuario ve un mensaje general, pero no sabe con precision que campo corregir.
- En compras, el error de detalle/producto puede quedar poco claro porque el formulario tiene varias lineas.

Correccion:

- Se agregaron helpers locales para leer `$layoutFieldErrors`.
- Se agregaron estilos `pv-form-error` y `cp-form-error`.
- Se muestran errores bajo cada campo principal.
- Los mensajes usan IDs compatibles con el JS global `MEDISOFT_FIELD_ERRORS` para evitar duplicados.

### P3 - Mapeo incompleto de errores de proveedores

El mapeo cubria nombre, RFC, email y telefono. Se amplio a razon social, direccion y notas.

### P3 - Mapeo generico en lineas de compra

Los errores de producto, cantidad y costo se apuntan al primer control de la tabla:

- `producto_id_0`
- `cantidad_0`
- `costo_unitario_0`

Esto evita repetir el mismo error en las cinco filas visibles y mantiene el foco en el primer dato que debe corregirse.

## Validacion aplicada

- `php -l` en ambos controladores.
- `php -l` en ambas vistas.
- `git diff --check` sin errores reales; solo avisos CRLF del workspace.
