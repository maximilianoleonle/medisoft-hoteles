# Auditoria de formularios: personal y usuarios

Fecha: 2026-06-25

## Alcance

- Controladores:
  - `src/app/controllers/TrabajadorController.php`
  - `src/app/controllers/UsuarioController.php`
- Vistas modificadas:
  - `src/app/views/trabajadores/form.php`
  - `src/app/views/usuarios/crear.php`
  - `src/app/views/usuarios/editar.php`
- Vistas revisadas sin cambios:
  - `src/app/views/trabajadores/index.php`
  - `src/app/views/trabajadores/ver.php`
  - `src/app/views/trabajadores/nomina_*.php`
  - `src/app/views/trabajadores/simulador_pago_caja.php`
  - `src/app/views/usuarios/index.php`

## Hallazgos

- El formulario principal de personal ya restauraba datos con `old()`, pero no mostraba errores debajo de los campos.
- El mapeo de errores de personal no normalizaba acentos y descartaba errores sin campo reconocido.
- Crear/editar usuarios conservaba valores capturados al fallar, pero no enviaba errores por campo a la vista.
- Crear/editar usuarios podia conservar `password` en `old_input`; se retiro de la recuperacion para no rehidratar contrasenas.
- Crear/editar usuarios no limpiaba `old_input` al terminar correctamente.
- Las vistas de usuarios no marcaban campos con error, ni exponian `aria-invalid`/`aria-describedby`.
- Los formularios de pagos laborales, reversions, nomina y simulador se cruzan con Caja y calculos laborales; se auditaron como fuera de alcance para no tocar logica restringida.

## Correcciones aplicadas

- Personal:
  - se agrego resumen para errores globales;
  - se muestran errores bajo nombre, usuario vinculado, identificacion, rol laboral, telefono, email, fecha de alta, periodicidad, salario y notas;
  - los campos con error reciben estado visual, `aria-invalid` y `aria-describedby`;
  - el mapeo de errores ahora normaliza acentos y conserva errores globales.
- Usuarios:
  - crear/editar guardan errores estructurados con `save_form_errors`;
  - crear/editar ya no guardan `password` en `old_input`;
  - crear/editar limpian `old_input` al guardar correctamente;
  - nombre de usuario duplicado se muestra debajo del campo `nombre_usuario`;
  - contrasena corta, nombre completo faltante, email invalido y rol invalido se muestran bajo su campo;
  - el valor anterior de `rol` se conserva en el select si la actualizacion falla;
  - se agregaron estados visuales de error y atributos accesibles en crear y editar usuarios.

## Fuera de alcance

- No se modificaron modelos, rutas, base de datos, migraciones, permisos ni autenticacion.
- No se cambio la validacion de contrasena, roles ni reglas de acceso.
- No se agrego validacion server-side para `password_confirmation` porque toca politica de autenticacion.
- No se modificaron pagos laborales, reversions, nomina, Caja, cortes, calculos o movimientos.
- No se modifico logica profunda de reservaciones, check-in/check-out, reportes ni PWA/offline.
- No se modifico `/api/sync`.

## Validacion

- `php -l` en controladores y vistas modificadas: sin errores.
- `git diff --check` del bloque: sin errores reales; solo avisos CRLF.
- Formularios principales balanceados:
  - `trabajadores/form.php`: 1 apertura y 1 cierre.
  - `usuarios/crear.php`: 1 apertura y 1 cierre.
  - `usuarios/editar.php`: 1 apertura y 1 cierre.
