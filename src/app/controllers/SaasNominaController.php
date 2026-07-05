<?php
/**
 * Panel Medisoft interno: reglas legales de nomina versionadas (Fase 5).
 * Solo saas_admins. Los negocios consumen el catalogo; jamas lo editan.
 */

require_once __DIR__ . '/../services/NominaReglasLegalesService.php';

class SaasNominaController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('requireSaasAdmin')) {
            requireSaasAdmin();
        }

        return true;
    }

    public function reglasAction() {
        $servicio = new NominaReglasLegalesService();

        $filtros = [
            'pais' => (string) $this->getQuery('pais', 'MX'),
            'tipo_regla' => (string) $this->getQuery('tipo_regla', ''),
            'ejercicio' => (string) $this->getQuery('ejercicio', ''),
        ];

        View::renderTemplate('admin/saas/nomina_reglas', [
            'title' => 'Reglas legales de nomina - Panel Medisoft',
            'reglas' => $servicio->listar(array_filter($filtros)),
            'filtros' => $filtros,
            'tiposSugeridos' => NominaReglasLegalesService::TIPOS_SUGERIDOS,
        ]);
    }

    public function guardarReglaAction() {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/nomina/reglas');
        }
        $this->validateCSRF();

        try {
            (new NominaReglasLegalesService())->crear([
                'pais' => $this->getPost('pais', 'MX'),
                'tipo_regla' => $this->getPost('tipo_regla'),
                'ejercicio' => $this->getPost('ejercicio'),
                'vigente_desde' => $this->getPost('vigente_desde'),
                'valor' => $this->getPost('valor'),
                'valores_json' => $_POST['valores_json'] ?? '',
                'descripcion' => $this->getPost('descripcion'),
                'fuente' => $this->getPost('fuente'),
            ], user_id());
            set_mensaje('Regla legal registrada; la vigencia anterior quedo cerrada.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('admin/saas/nomina/reglas');
    }

    public function alternarReglaAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/nomina/reglas');
        }
        $this->validateCSRF();

        try {
            (new NominaReglasLegalesService())->alternarEstado((int) $id, user_id());
            set_mensaje('Estado de la regla actualizado.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('admin/saas/nomina/reglas');
    }
}
