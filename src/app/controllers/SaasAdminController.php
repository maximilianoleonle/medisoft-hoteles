<?php
/**
 * Controlador minimo para el Panel Medisoft interno SaaS.
 */

class SaasAdminController extends Controller {
    private $hotelModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->hotelModel = new Hotel();
    }

    protected function before() {
        requireSaasAdmin();
        return true;
    }

    public function hotelesAction() {
        $hoteles = $this->hotelModel->listarParaSaasAdmin();

        View::renderTemplate('admin/saas/hoteles', [
            'title' => 'Panel Medisoft interno - Hoteles',
            'hoteles' => $hoteles
        ]);
    }
}
