<?php
/**
 * El Guardian (vigilancia financiera): informe forense sobre la conciliacion
 * determinista + patrones de comportamiento por usuario, con escalera de
 * costo (plantilla $0 / Opus 4.8 / Fable 5 forense).
 *
 * Gating temporal en el bloque ia_ejecutiva mientras no exista el modulo
 * propio; al monetizar como bloque 'vigilancia_financiera' cambiar la clave
 * aqui y en config/navegacion.php. Ademas del modulo, TODO se gatea con el
 * permiso guardian.view: los hallazgos nombran usuarios.
 */

require_once __DIR__ . '/../services/VigilanciaFinancieraService.php';
require_once __DIR__ . '/../models/GuardianPatrones.php';
require_once __DIR__ . '/../models/GuardianHallazgoEstado.php';

class VigilanciaFinancieraController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('ia_ejecutiva');
        }

        // El Guardian analiza patrones POR USUARIO: la vista, los endpoints y
        // el informe se gatean con guardian.view (solo dueno/gerencia). Un
        // operativo jamas debe ver su perfil de riesgo ni el de un companero.
        if (!can('guardian.view')) {
            set_mensaje('No tienes permiso para ver la vigilancia financiera.', 'error');
            $this->redirect('dashboard');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new VigilanciaFinancieraService();
        // regenerar=false: usa el informe del dia si ya existe; si no, lo genera
        // (nivel 1 = plantilla sin costo; nivel 2/3 = una llamada, cacheada).
        $resultado = $servicio->analizar($hotelId, [], false, user_id());

        // Conteos frescos de la conciliacion para el semaforo (solo SQL, $0).
        $totales = ['error' => 0, 'warning' => 0, 'hallazgos' => 0];
        try {
            $reporte = (new ConciliacionFinanciera())->reporteReadOnlyPorHotel($hotelId, ['page' => 1, 'limit' => 50]);
            if (!empty($reporte['schema_ok'])) {
                $totales = array_merge($totales, (array) ($reporte['totales_alertas'] ?? []));
            }
        } catch (Throwable $e) {
            error_log('Guardian (vista): error al leer conciliacion: ' . $e->getMessage());
        }

        // Patrones frescos del Guardian (determinista, $0) + registro de
        // hallazgos con estado (unica escritura del Guardian: tabla propia).
        $patrones = null;
        $estadosPorClave = [];
        $historico = [];
        $conteosEstado = ['nuevo' => 0, 'revisado' => 0, 'resuelto' => 0];
        try {
            $patrones = (new GuardianPatrones())->reporteReadOnlyPorHotel($hotelId);

            $estadoModel = new GuardianHallazgoEstado();
            $estadosPorClave = $estadoModel->sincronizarDesdeReporte($hotelId, $patrones);
            $historico = $estadoModel->listarPorHotel($hotelId, 'todos', 60);
            $conteosEstado = $estadoModel->conteos($hotelId);
        } catch (Throwable $e) {
            error_log('Guardian (vista): error al leer patrones: ' . $e->getMessage());
        }

        View::renderTemplate('ia/vigilancia_financiera', [
            'title' => 'El Guardián - ' . current_hotel_display_name(),
            'resultado' => $resultado,
            'totales' => $totales,
            'patrones' => $patrones,
            'estadosPorClave' => $estadosPorClave,
            'historico' => $historico,
            'conteosEstado' => $conteosEstado,
            'configurado' => $servicio->configurado(),
        ]);
    }

    public function regenerarAction() {
        if (!$this->isPost()) {
            $this->redirect('ia/vigilancia-financiera');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();

        $servicio = new VigilanciaFinancieraService();
        $resultado = $servicio->analizar($hotelId, [], true, user_id());

        if (empty($resultado['success'])) {
            set_mensaje($resultado['message'] ?? 'No se pudo regenerar el informe.', 'error');
        } else {
            set_mensaje('Informe del Guardián regenerado con los datos más recientes.', 'success');
        }

        $this->redirect('ia/vigilancia-financiera');
    }

    /**
     * Marca un hallazgo como nuevo/revisado/resuelto. Escribe SOLO en
     * guardian_hallazgos_estado (jamas en tablas de dinero).
     */
    public function estadoHallazgoAction($id) {
        if (!$this->isPost()) {
            $this->redirect('ia/vigilancia-financiera');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();
        $estado = (string) ($_POST['estado'] ?? '');

        $ok = (new GuardianHallazgoEstado())->cambiarEstado((int) $id, $hotelId, $estado, user_id());

        if ($ok) {
            $textos = [
                'revisado' => 'Hallazgo marcado como revisado.',
                'resuelto' => 'Hallazgo marcado como resuelto. Si vuelve a pasar algo nuevo, el Guardián lo reabrirá.',
                'nuevo' => 'Hallazgo reabierto.',
            ];
            set_mensaje($textos[$estado] ?? 'Estado actualizado.', 'success');
        } else {
            set_mensaje('No se pudo actualizar el hallazgo.', 'error');
        }

        $this->redirect('ia/vigilancia-financiera' . (!empty($_POST['volver_a']) && $_POST['volver_a'] === 'historico' ? '?tab=historico' : ''));
    }
}
