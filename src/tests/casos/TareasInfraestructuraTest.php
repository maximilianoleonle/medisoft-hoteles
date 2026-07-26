<?php
/**
 * Contrato comercial: Tareas operativas es infraestructura compartida.
 * No depende de un bloque SaaS propio y conserva auth, hotel y permisos.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "TareasInfraestructuraTest\n";

$controller = (string)file_get_contents(APP_PATH . '/controllers/TareaController.php');
$sidebar = (string)file_get_contents(APP_PATH . '/views/layout/sidebar.php');
$navegacion = (string)file_get_contents(ROOT_PATH . '/config/navegacion.php');
$footerNav = (string)file_get_contents(APP_PATH . '/helpers/footer_nav.php');
$copiloto = (string)file_get_contents(APP_PATH . '/services/CopilotoService.php');
$migracion = (string)file_get_contents(dirname(ROOT_PATH) . '/migrations/20260722_002_retirar_modulo_tareas_comercial.sql');

t_ok(strpos($controller, '$this->requireAuth()') !== false, 'Tareas conserva autenticacion');
t_ok(strpos($controller, 'require_hotel_context()') !== false, 'Tareas conserva contexto de hotel');
t_ok(strpos($controller, "require_permission('habitaciones.view')") !== false, 'Tareas conserva permiso base');
t_ok(strpos($controller, "require_permission('habitaciones.mantenimiento')") !== false, 'escrituras conservan permiso operativo');
t_ok(strpos($controller, "require_hotel_module('tareas')") === false, 'Tareas no conserva gate comercial propio');
t_ok(strpos($controller, 'validateCSRF()') !== false, 'escrituras conservan CSRF');

t_ok(strpos($sidebar, '$mostrarTareas = false;') !== false, 'sidebar oculta la seccion autonoma de Tareas');
t_ok(strpos($sidebar, "\$menuModuloActivo('tareas')") === false, 'sidebar no consulta el bloque comercial retirado');
t_ok(strpos($navegacion, "'ruta' => 'tareas'") === false, 'buscador no publica la seccion autonoma de Tareas');
t_ok(strpos($navegacion, "'modulo' => 'tareas'") === false, 'navegacion no publica un modulo Tareas');
t_ok(strpos($footerNav, "'path' => 'tareas'") === false, 'navegacion movil no publica la seccion autonoma de Tareas');
t_ok(strpos($controller, "set_mensaje('La seccion independiente de Tareas no esta disponible.', 'error')") !== false, 'GET /tareas informa que la seccion autonoma no esta disponible');
t_ok(strpos($controller, "\$this->redirect('dashboard');") !== false, 'GET /tareas redirige al dashboard');
t_ok(strpos($controller, 'public function porHabitacionAction(): void') !== false, 'consulta contextual por habitacion se conserva');
t_ok(strpos($controller, 'public function crearDesdeLimpiezaAction(): void') !== false, 'creacion contextual desde Limpieza se conserva');
t_ok(strpos($controller, 'public function crearDesdeMantenimientoAction(): void') !== false, 'creacion contextual desde Mantenimiento se conserva');
t_ok(strpos($copiloto, "['clave' => 'tareas', 'modulo' => null") !== false, 'Copiloto trata Tareas como infraestructura');

t_ok(strpos($migracion, "DELETE FROM modulos") !== false, 'migracion retira el catalogo comercial');
t_ok(strpos($migracion, 'tareas_operativas') !== false, 'migracion documenta las tablas preservadas');
t_ok(strpos($migracion, 'DELETE FROM tareas_operativas') === false, 'migracion no elimina tareas');
t_ok(strpos($migracion, 'DROP TABLE') === false, 'migracion no elimina tablas operativas');

t_fin();
