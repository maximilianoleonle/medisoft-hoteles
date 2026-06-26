# Auditoria de formularios de inventario - 2026-06-25

## Alcance

- Controlador revisado: `src/app/controllers/InventarioController.php`.
- Vistas revisadas:
  - `src/app/views/inventario/nuevo.php`
  - `src/app/views/inventario/editar.php`
  - `src/app/views/inventario/entrada.php`
  - `src/app/views/inventario/salida.php`
  - `src/app/views/inventario/ajuste.php`
- Revision limitada al contrato superficial de formularios, recuperacion de datos y errores por campo.
- No se modificaron rutas, modelos, base de datos, migraciones, permisos, auth ni archivos PWA/offline.
- No se cambio `action`, `method`, `name`, CSRF ni estructura de envio de stock.
- No se modificaron calculos de stock ni reglas de movimientos.

## Contrato detectado

Producto nuevo:

- `codigo`
- `nombre`
- `categoria_id`
- `unidad_medida`
- `stock_inicial`
- `stock_minimo`
- `costo_unitario`
- `descripcion`
- `descuento_automatico`

Producto editar:

- `codigo`
- `nombre`
- `categoria_id`
- `unidad_medida`
- `stock_minimo`
- `costo_unitario`
- `descripcion`
- `descuento_automatico`

Entrada manual:

- `producto_id`
- `cantidad`
- `motivo`

Salida manual:

- `producto_id`
- `cantidad`
- `habitacion_id`
- `motivo_tipo`
- `motivo`

Ajuste manual:

- `tipo`
- `cantidad`
- `motivo`
- `observaciones`

## Problemas encontrados

1. Producto nuevo/editar guardaba errores, pero la vista no los mostraba por campo.

   `guardarAction()` y `actualizarAction()` ya usaban `save_form_errors()`, pero los formularios solo mostraban mensaje general.

2. Entrada manual perdia datos al fallar.

   Si el producto no existia o fallaba el movimiento, el controlador redirigia sin conservar `old_input` ni errores por campo.

3. Salida manual conservaba la pantalla, pero no datos ni errores.

   Al fallar por stock insuficiente u otro error, el usuario volvia a capturar producto, cantidad, habitacion y motivo.

4. Ajuste manual no recuperaba valores.

   Si fallaba por tipo no seleccionado, cantidad invalida, motivo faltante o stock negativo, la pantalla no marcaba el campo afectado.

5. `motivo_tipo` en salida no es leido por el controlador.

   Se conserva como selector visual que cambia el placeholder del motivo. No se elimino ni renombro para no alterar el contrato de formulario existente.

## Correcciones aplicadas

- Se agrego `erroresCamposInventarioMovimiento()` para mapear errores de entrada/salida/ajuste a campos visibles.
- Entrada manual:
  - guarda `old_input` y errores al fallar;
  - redirige de vuelta a `inventario/entrada`;
  - limpia `old_input` al guardar correctamente.
- Salida manual:
  - guarda `old_input` y errores al fallar;
  - conserva producto, cantidad, habitacion, tipo visual y motivo;
  - limpia `old_input` al guardar correctamente.
- Ajuste manual:
  - guarda `old_input` y errores al fallar;
  - conserva tipo, cantidad, motivo y observaciones;
  - limpia `old_input` al guardar correctamente.
- Producto nuevo/editar:
  - muestra errores bajo los campos principales sin cambiar los nombres del formulario.

## Validaciones ejecutadas

```bash
docker compose exec -T app php -l app/controllers/InventarioController.php
docker compose exec -T app php -l app/views/inventario/nuevo.php
docker compose exec -T app php -l app/views/inventario/editar.php
docker compose exec -T app php -l app/views/inventario/entrada.php
docker compose exec -T app php -l app/views/inventario/salida.php
docker compose exec -T app php -l app/views/inventario/ajuste.php
```

Resultado: sin errores de sintaxis.

Validaciones adicionales:

- `git diff --check` sobre archivos tocados: sin errores.
- Formularios balanceados y sin anidamiento:
  - `nuevo.php`: 1 formulario.
  - `editar.php`: 1 formulario.
  - `entrada.php`: 1 formulario.
  - `salida.php`: 1 formulario.
  - `ajuste.php`: 1 formulario.

## Riesgo residual

- Esta auditoria no cambio la logica de stock. Solo se mejoro la experiencia de error y recuperacion.
- Si se quiere que `motivo_tipo` tenga efecto en la auditoria o en el motivo guardado, debe tratarse como cambio funcional aparte.
