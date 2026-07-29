<?php
/**
 * Controlador minimo para el Panel Medisoft interno SaaS.
 */

class SaasAdminController extends Controller {
    private $hotelModel;
    private $usuarioModel;
    private $moduloModel;
    private $planModel;
    private $brandingModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->hotelModel = new Hotel();
        $this->usuarioModel = new Usuario();
        $this->moduloModel = new Modulo();
        $this->planModel = new Plan();
        $this->brandingModel = new HotelBranding();
    }

    protected function before() {
        requireSaasAdmin();
        return true;
    }

    public function hotelesAction() {
        $hoteles = $this->hotelModel->listarParaSaasAdmin();

        $cobrosMensuales = [];
        foreach ($hoteles as $hotel) {
            $resumen = $this->moduloModel->resumenCobroMensual((int) $hotel['id']);
            $cobrosMensuales[(int) $hotel['id']] = $resumen ? (float) $resumen['total'] : null;
        }

        View::renderTemplate('admin/saas/hoteles', [
            'title' => 'Panel Medisoft interno - Hoteles',
            'hoteles' => $hoteles,
            'cobrosMensuales' => $cobrosMensuales
        ]);
    }

    public function crearHotelAction() {
        View::renderTemplate('admin/saas/hotel_form', [
            'title' => 'Panel Medisoft interno - Crear hotel',
            'hotel' => null,
            'action' => url('admin/saas/hoteles'),
            'modo' => 'crear'
        ]);
    }

    public function guardarHotelAction() {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles');
        }

        $this->validateCSRF();

        $data = $this->datosHotelDesdePost();
        $data['activo'] = 0;
        $errores = $this->validarDatosHotel($data);

        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('admin/saas/hoteles/crear');
        }

        $hotelId = $this->hotelModel->crearParaSaasAdmin($data);

        if (!$hotelId) {
            save_old_input($data);
            set_mensaje('No se pudo crear el hotel. Revise los datos e intente nuevamente.', 'error');
            $this->redirect('admin/saas/hoteles/crear');
        }

        // Sembrar los roles base del nuevo hotel (no bloquea el alta si falla).
        try {
            (new Rol())->sembrarPresetsParaHotel((int) $hotelId);
        } catch (Throwable $e) {
            error_log('No se pudieron sembrar roles del hotel nuevo ' . (int) $hotelId . ': ' . $e->getMessage());
        }

        // Dejar lista la caja principal desde el alta (idempotente; no bloquea si falla).
        try {
            (new Caja())->ensureDefaultCajaForHotel((int) $hotelId);
        } catch (Throwable $e) {
            error_log('No se pudo asegurar la caja inicial del hotel nuevo ' . (int) $hotelId . ': ' . $e->getMessage());
        }

        set_mensaje('Hotel creado en estado inactivo. Active el hotel cuando complete su configuracion.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotelId);
    }

    // ───────────── Cobros SaaS (facturacion mensual a hoteles) ─────────────

    public function cobrosAction() {
        if (!class_exists('SaasCobroService')) {
            require_once __DIR__ . '/../services/SaasCobroService.php';
        }
        $servicio = new SaasCobroService();

        $periodo = (string) $this->getQuery('periodo', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)) {
            $periodo = date('Y-m');
        }

        View::renderTemplate('admin/saas/cobros', [
            'title' => 'Panel Medisoft interno - Cobros',
            'periodo' => $periodo,
            'cobros' => $servicio->cobrosDelPeriodo($periodo),
            'stripeConfigurado' => $servicio->configurado(),
            'correoConfigurado' => filter_var(trim((string) getenv('SAAS_EMAIL_REMITENTE')), FILTER_VALIDATE_EMAIL) !== false,
        ]);
    }

    /** Ciclo automatico manual: lo mismo que corre el cron, desde un boton. */
    public function ejecutarCicloCobrosAction() {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/cobros');
        }

        $this->validateCSRF();

        if (!class_exists('SaasCobroService')) {
            require_once __DIR__ . '/../services/SaasCobroService.php';
        }
        $periodo = (string) $this->getPost('periodo', date('Y-m'));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)) {
            $periodo = date('Y-m');
        }

        $resultado = (new SaasCobroService())->cicloAutomatico($periodo);
        set_mensaje(implode('<br>', $resultado['log'] ?? ['Ciclo ejecutado.']), 'success');
        $this->redirect('admin/saas/cobros?periodo=' . urlencode($periodo));
    }

    public function enviarCorreoCobroAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/cobros');
        }

        $this->validateCSRF();

        if (!class_exists('SaasCobroService')) {
            require_once __DIR__ . '/../services/SaasCobroService.php';
        }
        $servicio = new SaasCobroService();
        $cobro = $servicio->porId((int) $id);
        $esRecordatorio = (int) $this->getPost('recordatorio', 0) === 1;

        $resultado = $servicio->enviarCorreoCobro((int) $id, $esRecordatorio);
        set_mensaje($resultado['message'], !empty($resultado['success']) ? 'success' : 'error');
        $this->redirect('admin/saas/cobros?periodo=' . urlencode($cobro['periodo'] ?? date('Y-m')));
    }

    public function generarCobrosAction() {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/cobros');
        }

        $this->validateCSRF();

        if (!class_exists('SaasCobroService')) {
            require_once __DIR__ . '/../services/SaasCobroService.php';
        }
        $periodo = (string) $this->getPost('periodo', date('Y-m'));
        $resultado = (new SaasCobroService())->generarPeriodo($periodo);

        set_mensaje($resultado['message'], $resultado['success'] ? 'success' : 'error');
        $this->redirect('admin/saas/cobros?periodo=' . urlencode($periodo));
    }

    public function linkPagoCobroAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/cobros');
        }

        $this->validateCSRF();

        if (!class_exists('SaasCobroService')) {
            require_once __DIR__ . '/../services/SaasCobroService.php';
        }
        $servicio = new SaasCobroService();
        $cobro = $servicio->porId((int) $id);
        $periodo = $cobro['periodo'] ?? date('Y-m');

        $resultado = $servicio->crearLinkPago((int) $id, url('admin/saas/cobros?periodo=' . urlencode($periodo)));

        set_mensaje($resultado['message'], $resultado['success'] ? 'success' : 'error');
        $this->redirect('admin/saas/cobros?periodo=' . urlencode($periodo));
    }

    public function pagadoManualCobroAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/cobros');
        }

        $this->validateCSRF();

        if (!class_exists('SaasCobroService')) {
            require_once __DIR__ . '/../services/SaasCobroService.php';
        }
        $servicio = new SaasCobroService();
        $cobro = $servicio->porId((int) $id);
        $ok = $servicio->marcarPagado((int) $id, 'manual');

        set_mensaje(
            $ok ? 'Cobro marcado como pagado (manual).' : 'No se pudo marcar; quiza ya no estaba pendiente.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('admin/saas/cobros?periodo=' . urlencode($cobro['periodo'] ?? date('Y-m')));
    }

    public function cancelarCobroAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/cobros');
        }

        $this->validateCSRF();

        if (!class_exists('SaasCobroService')) {
            require_once __DIR__ . '/../services/SaasCobroService.php';
        }
        $servicio = new SaasCobroService();
        $cobro = $servicio->porId((int) $id);
        $ok = $servicio->cancelar((int) $id);

        set_mensaje(
            $ok ? 'Cobro cancelado.' : 'No se pudo cancelar; quiza ya no estaba pendiente.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('admin/saas/cobros?periodo=' . urlencode($cobro['periodo'] ?? date('Y-m')));
    }

    public function verHotelAction($id) {
        $hotel = $this->obtenerHotelORedirigir($id);
        $usuariosHotel = $this->hotelModel->listarUsuariosParaSaasAdmin((int) $hotel['id']);
        $modulosHotel = $this->moduloModel->listarParaHotelSaasAdmin((int) $hotel['id']);
        $planes = $this->planModel->listarActivos();
        $planActual = $this->planModel->obtenerActualDeHotel((int) $hotel['id']);
        $modulosPorPlan = $this->modulosPorPlan($planes);
        $auditoriaPlan = $this->planModel->auditarConsistenciaHotel((int) $hotel['id']);
        $brandingHotel = $this->brandingModel->resolverParaHotel((int) $hotel['id'], $hotel);
        $resumenCobro = $this->moduloModel->resumenCobroMensual((int) $hotel['id']);

        if (!class_exists('SaasCobroService')) {
            require_once __DIR__ . '/../services/SaasCobroService.php';
        }
        $cobrosHotel = (new SaasCobroService())->cobrosPorHotel((int) $hotel['id'], 12);

        if (!class_exists('HotelConfiguracionService')) {
            require_once __DIR__ . '/../services/HotelConfiguracionService.php';
        }

        View::renderTemplate('admin/saas/hotel_detalle', [
            'fondoSistemaHotel' => HotelConfiguracionService::fondoGuardado((int) $hotel['id']),
            'title' => 'Panel Medisoft interno - Detalle de hotel',
            'hotel' => $hotel,
            'usuariosHotel' => $usuariosHotel,
            'modulosHotel' => $modulosHotel,
            'planes' => $planes,
            'planActual' => $planActual,
            'modulosPorPlan' => $modulosPorPlan,
            'auditoriaPlan' => $auditoriaPlan,
            'brandingHotel' => $brandingHotel,
            'resumenCobro' => $resumenCobro,
            'cobrosHotel' => $cobrosHotel
        ]);
    }

    public function editarHotelAction($id) {
        $hotel = $this->obtenerHotelORedirigir($id);

        View::renderTemplate('admin/saas/hotel_form', [
            'title' => 'Panel Medisoft interno - Editar hotel',
            'hotel' => $hotel,
            'action' => url('admin/saas/hoteles/' . (int) $hotel['id'] . '/actualizar'),
            'modo' => 'editar'
        ]);
    }

    public function actualizarHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles');
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);

        $data = $this->datosHotelDesdePost();
        $errores = $this->validarDatosHotel($data, (int) $hotel['id']);

        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar');
        }

        $actualizado = $this->hotelModel->actualizarParaSaasAdmin((int) $hotel['id'], $data);

        if (!$actualizado) {
            save_old_input($data);
            set_mensaje('No se pudo actualizar el hotel. Revise los datos e intente nuevamente.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id'] . '/editar');
        }

        set_mensaje('Hotel actualizado correctamente.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
    }

    public function estadoHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles');
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);
        $activo = (int) $this->getPost('activo', 0) === 1;

        $actualizado = $this->hotelModel->actualizarEstadoParaSaasAdmin((int) $hotel['id'], $activo);

        if (!$actualizado) {
            set_mensaje('No se pudo cambiar el estado del hotel.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        set_mensaje($activo ? 'Hotel activado correctamente.' : 'Hotel suspendido correctamente.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
    }

    public function guardarAdminHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles/' . (int) $id);
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);
        $data = $this->datosAdminHotelDesdePost();
        $usuarioExistente = $this->usuarioModel->buscarPorNombreUsuario($data['nombre_usuario']);
        $errores = $this->validarDatosAdminHotel($data, $usuarioExistente);

        if (!empty($errores)) {
            $this->guardarOldInputAdminHotel($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        if ($usuarioExistente && $this->hotelModel->usuarioVinculado((int) $hotel['id'], (int) $usuarioExistente['id'])) {
            $this->guardarOldInputAdminHotel($data);
            set_mensaje('El usuario ya esta vinculado a este hotel.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        if ($usuarioExistente && empty($usuarioExistente['activo'])) {
            $this->guardarOldInputAdminHotel($data);
            set_mensaje('El usuario existe, pero esta inactivo. Active la cuenta antes de vincularla.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        $db = Database::getInstance();

        try {
            $db->safeBeginTransaction();
            $usuarioId = $usuarioExistente['id'] ?? null;
            $usuarioCreado = false;

            if (!$usuarioId) {
                $usuarioId = $this->usuarioModel->crearUsuario([
                    'nombre_usuario' => $data['nombre_usuario'],
                    'password' => $data['password'],
                    'nombre_completo' => $data['nombre_completo'],
                    'email' => $data['email'],
                    'telefono' => null,
                    'rol' => $data['rol_hotel'],
                    'activo' => 1
                ]);
                $usuarioCreado = true;

                if (!$usuarioId) {
                    throw new Exception('No se pudo crear el usuario.');
                }
            }

            $vinculado = $this->hotelModel->vincularUsuarioParaSaasAdmin(
                (int) $hotel['id'],
                (int) $usuarioId,
                $data['rol_hotel'],
                !empty($data['es_principal']),
                !empty($data['activo'])
            );

            if (!$vinculado) {
                throw new Exception('No se pudo vincular el usuario al hotel.');
            }

            $db->safeCommit();
            clear_old_input();

            set_mensaje(
                $usuarioCreado
                    ? 'Usuario administrador creado y vinculado al hotel correctamente.'
                    : 'Usuario existente vinculado al hotel correctamente.',
                'success'
            );
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        } catch (Exception $e) {
            $db->safeRollBack();
            error_log('Error al crear administrador hotelero: ' . $e->getMessage());
            $this->guardarOldInputAdminHotel($data);
            set_mensaje('No se pudo guardar el administrador del hotel. Revise los datos e intente nuevamente.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }
    }

    public function actualizarModulosHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles/' . (int) $id);
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);
        $modulosActivos = $this->normalizarModuloIds($this->getPost('modulos', []));
        $preciosOverride = $this->getPost('precio_override', []);
        $actualizado = $this->moduloModel->actualizarModulosHotel(
            (int) $hotel['id'],
            $modulosActivos,
            user_id(),
            is_array($preciosOverride) ? $preciosOverride : []
        );

        if (!$actualizado) {
            set_mensaje('No se pudieron actualizar los modulos del hotel. Verifique que la migracion de modulos este aplicada.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        set_mensaje('Modulos del hotel actualizados correctamente.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
    }

    public function actualizarPlanHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles/' . (int) $id);
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);
        $planId = (int) $this->getPost('plan_id', 0);
        $aplicarModulos = (int) $this->getPost('aplicar_modulos', 0) === 1;

        if ($planId <= 0) {
            set_mensaje('Seleccione un plan valido.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        $plan = $this->planModel->obtenerPorId($planId);

        if (!$plan || empty($plan['activo'])) {
            set_mensaje('El plan seleccionado no existe o esta inactivo.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        $db = Database::getInstance();

        try {
            $db->safeBeginTransaction();

            if (!$this->planModel->actualizarPlanHotel((int) $hotel['id'], $planId)) {
                throw new Exception('No se pudo actualizar el plan del hotel.');
            }

            if ($aplicarModulos && $plan['clave'] !== 'personalizado') {
                $moduloIds = $this->planModel->moduloIdsDelPlan($planId);

                if (empty($moduloIds)) {
                    throw new Exception('El plan seleccionado no tiene modulos configurados.');
                }

                $actualizado = $this->moduloModel->actualizarModulosHotel(
                    (int) $hotel['id'],
                    $moduloIds,
                    user_id()
                );

                if (!$actualizado) {
                    throw new Exception('No se pudieron aplicar los modulos del plan.');
                }
            }

            $db->safeCommit();

            $mensaje = 'Plan del hotel actualizado correctamente.';
            if ($aplicarModulos && $plan['clave'] !== 'personalizado') {
                $mensaje .= ' Se aplicaron los modulos sugeridos por el plan.';
            } elseif ($plan['clave'] === 'personalizado') {
                $mensaje .= ' El plan personalizado mantiene la seleccion manual de modulos.';
            }

            set_mensaje($mensaje, 'success');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        } catch (Exception $e) {
            $db->safeRollBack();
            error_log('Error al actualizar plan del hotel: ' . $e->getMessage());
            set_mensaje('No se pudo actualizar el plan del hotel. Verifique que la migracion de planes este aplicada.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }
    }

    public function actualizarBrandingHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles/' . (int) $id);
        }

        $this->validateCSRF();
        $hotel = $this->obtenerHotelORedirigir($id);
        $data = $this->datosBrandingDesdePost();
        $errores = $this->procesarUploadsBranding($hotel, $data);
        $errores = array_merge($errores, $this->brandingModel->validarDatos($data));

        if (!empty($errores)) {
            save_old_input($data);
            set_mensaje(implode('<br>', $errores), 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        if (!$this->brandingModel->guardarParaHotel((int) $hotel['id'], $data)) {
            save_old_input($data);
            set_mensaje('No se pudo guardar el branding. Verifique que la migracion de branding este aplicada.', 'error');
            $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
        }

        // El fondo del sistema vive en hotel_configuracion, no en hotel_branding,
        // pero es identidad visual: se edita aqui junto al resto de la marca.
        $this->guardarFondoSistema((int) $hotel['id'], $_POST['hotel_appearance'] ?? null);

        clear_old_input();
        set_mensaje('Branding basico actualizado correctamente.', 'success');
        $this->redirect('admin/saas/hoteles/' . (int) $hotel['id']);
    }

    /**
     * Configuracion operativa de un hotel cliente (la pantalla que antes vivia
     * en /configuracion). Reusa la vista completa apuntandola al hotel objetivo
     * via TenantContext, sin tocar la sesion del admin SaaS.
     */
    public function configuracionHotelAction($id) {
        $hotel = $this->obtenerHotelORedirigir($id);
        $hotelId = (int) $hotel['id'];

        $this->activarContextoHotel($hotel);

        if (!class_exists('HotelConfiguracionService')) {
            require_once __DIR__ . '/../services/HotelConfiguracionService.php';
        }

        $datos = HotelConfiguracionService::datosDeVista($hotelId);
        $branding = $this->brandingModel->resolverParaHotel($hotelId, $hotel);

        View::renderTemplate('configuracion/index', array_merge($datos, [
            'title' => 'Panel Medisoft interno - Configuracion de ' . ($hotel['nombre_comercial'] ?? $hotel['nombre'] ?? 'hotel'),
            // La tabla legacy `configuracion` es global (sin hotel_id): no se lee
            // aqui o mostrariamos datos de otro hotel.
            'config' => ['hotel' => [], 'tarifas' => [], 'inventario' => [], 'sistema' => [], 'upload' => []],
            'hotelBranding' => $branding,
            'hotelBackgroundColor' => '#F5F5F7',
            'hotelBackgroundStored' => HotelConfiguracionService::fondoGuardado($hotelId),
            'pwaPushDevices' => $this->dispositivosPwaPush($hotelId),
            'ultimo_backup' => null,
            'espacio' => null,
            'configContextoSaas' => [
                'id' => $hotelId,
                'nombre' => $branding['nombre_visual'] ?? ($hotel['nombre_comercial'] ?? $hotel['nombre'] ?? ''),
                'slug' => $hotel['slug'] ?? '',
                'activo' => !empty($hotel['activo']),
                'url_detalle' => url('admin/saas/hoteles/' . $hotelId),
                'url_marca' => url('admin/saas/hoteles/' . $hotelId . '#branding'),
            ],
            'configFormAction' => url('admin/saas/hoteles/' . $hotelId . '/configuracion'),
            'configCancelUrl' => url('admin/saas/hoteles/' . $hotelId),
        ]));
    }

    public function guardarConfiguracionHotelAction($id) {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/hoteles/' . (int) $id . '/configuracion');
        }

        $this->validateCSRF();

        $hotel = $this->obtenerHotelORedirigir($id);
        $hotelId = (int) $hotel['id'];
        $destino = 'admin/saas/hoteles/' . $hotelId . '/configuracion';

        $this->activarContextoHotel($hotel);

        if (!class_exists('HotelConfiguracionService')) {
            require_once __DIR__ . '/../services/HotelConfiguracionService.php';
        }

        $normalizado = HotelConfiguracionService::normalizarPayload($_POST);

        if (!empty($normalizado['errors'])) {
            set_mensaje(implode('<br>', $normalizado['errors']), 'error');
            $this->redirect($destino);
        }

        $db = null;

        try {
            $db = Database::getInstance();
            $db->safeBeginTransaction();

            HotelConfiguracionService::guardar($normalizado['values'], $hotelId);

            $db->safeCommit();

            set_mensaje('Configuración del hotel actualizada correctamente.', 'success');
        } catch (Throwable $e) {
            if ($db && method_exists($db, 'safeRollBack')) {
                $db->safeRollBack();
            }

            error_log('Error al actualizar configuracion de hotel desde panel SaaS: ' . $e->getMessage());
            set_mensaje('No se pudo actualizar la configuración del hotel.', 'error');
        }

        $this->redirect($destino);
    }

    /**
     * Apunta los helpers hotel_* al hotel cliente durante este request.
     * No toca $_SESSION: el admin SaaS conserva su propio contexto.
     */
    private function activarContextoHotel(array $hotel) {
        if (class_exists('TenantContext')) {
            TenantContext::setHotel($hotel);
        }

        if (function_exists('hotel_config_cache_invalidar')) {
            hotel_config_cache_invalidar((int) $hotel['id']);
        }
    }

    private function guardarFondoSistema($hotelId, $payload) {
        if (!is_array($payload) || !function_exists('hotel_config_save_value')) {
            return;
        }

        if (!class_exists('HotelConfiguracionService')) {
            require_once __DIR__ . '/../services/HotelConfiguracionService.php';
        }

        $resultado = HotelConfiguracionService::normalizarApariencia($payload);

        if (!empty($resultado['errors'])) {
            return;
        }

        try {
            hotel_config_save_value(
                'apariencia.fondo_sistema',
                $resultado['values']['background_color'],
                'string',
                'apariencia',
                'Color de fondo global de las vistas operativas del hotel en modo claro.',
                (int) $hotelId
            );
        } catch (Throwable $e) {
            error_log('No se pudo guardar el fondo del sistema desde el panel SaaS: ' . $e->getMessage());
        }
    }

    private function dispositivosPwaPush($hotelId) {
        $hotelId = (int) $hotelId;

        if ($hotelId <= 0 || !class_exists('PwaPushSubscription')) {
            return [];
        }

        try {
            return (new PwaPushSubscription())->listarPorHotel($hotelId, 40);
        } catch (Throwable $e) {
            error_log('No se pudieron listar dispositivos PWA Push (panel SaaS): ' . $e->getMessage());
            return [];
        }
    }

    public function modulosCatalogoAction() {
        $modulos = $this->moduloModel->listarGlobales();
        $planes = $this->planModel->listarActivos();

        View::renderTemplate('admin/saas/modulos', [
            'title' => 'Panel Medisoft interno - Bloques y precios',
            'modulos' => $modulos,
            'planes' => $planes
        ]);
    }

    public function actualizarPreciosModulosAction() {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/modulos');
        }

        $this->validateCSRF();
        $precios = $this->getPost('precios', []);

        if (!is_array($precios) || !$this->moduloModel->actualizarPreciosCatalogo($precios)) {
            set_mensaje('No se pudieron actualizar los precios del catalogo. Verifique que la migracion de precios este aplicada.', 'error');
            $this->redirect('admin/saas/modulos');
        }

        set_mensaje('Precios del catalogo de bloques actualizados correctamente.', 'success');
        $this->redirect('admin/saas/modulos');
    }

    public function actualizarPreciosPlanesAction() {
        if (!$this->isPost()) {
            $this->redirect('admin/saas/modulos');
        }

        $this->validateCSRF();
        $precios = $this->getPost('precios_planes', []);

        if (!is_array($precios) || !$this->planModel->actualizarPreciosPlanes($precios)) {
            set_mensaje('No se pudieron actualizar los precios de los planes.', 'error');
            $this->redirect('admin/saas/modulos');
        }

        set_mensaje('Precios de planes actualizados correctamente.', 'success');
        $this->redirect('admin/saas/modulos');
    }

    private function obtenerHotelORedirigir($id) {
        $hotel = $this->hotelModel->obtenerParaSaasAdmin((int) $id);

        if (!$hotel) {
            set_mensaje('Hotel no encontrado.', 'error');
            $this->redirect('admin/saas/hoteles');
        }

        return $hotel;
    }

    private function datosHotelDesdePost() {
        return [
            'nombre' => trim($this->getPost('nombre', '')),
            'slug' => $this->normalizarSlug($this->getPost('slug', '')),
            'codigo' => $this->valorNullable($this->getPost('codigo', '')),
            'razon_social' => $this->valorNullable($this->getPost('razon_social', '')),
            'rfc' => $this->valorNullable(strtoupper($this->getPost('rfc', ''))),
            'telefono' => $this->valorNullable($this->getPost('telefono', '')),
            'email' => $this->valorNullable(strtolower($this->getPost('email', ''))),
            'direccion' => $this->valorNullable($this->getPost('direccion', '')),
            'ciudad' => $this->valorNullable($this->getPost('ciudad', '')),
            'estado' => $this->valorNullable($this->getPost('estado', '')),
            'pais' => $this->valorNullable($this->getPost('pais', '')) ?: 'Mexico',
            'zona_horaria' => $this->valorNullable($this->getPost('zona_horaria', '')) ?: 'America/Mexico_City',
            'moneda_codigo' => $this->valorNullable(strtoupper($this->getPost('moneda_codigo', ''))) ?: 'MXN',
            'moneda_simbolo' => $this->valorNullable($this->getPost('moneda_simbolo', '')) ?: '$',
        ];
    }

    private function validarDatosHotel(array $data, $hotelId = null) {
        $errores = [];

        if ($data['nombre'] === '') {
            $errores[] = 'El nombre comercial es obligatorio.';
        }

        if ($data['slug'] === '') {
            $errores[] = 'El slug es obligatorio.';
        } elseif (!preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $data['slug'])) {
            $errores[] = 'El slug solo puede usar minusculas, numeros y guiones, sin iniciar ni terminar con guion.';
        } elseif ($this->hotelModel->slugExiste($data['slug'], $hotelId)) {
            $errores[] = 'El slug ya esta en uso por otro hotel.';
        }

        if ($this->hotelModel->codigoExiste($data['codigo'], $hotelId)) {
            $errores[] = 'El codigo ya esta en uso por otro hotel.';
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no tiene un formato valido.';
        }

        if (!preg_match('/^[A-Z]{3}$/', $data['moneda_codigo'])) {
            $errores[] = 'La moneda debe usar un codigo ISO de 3 letras, por ejemplo MXN.';
        }

        return $errores;
    }

    private function datosAdminHotelDesdePost() {
        return [
            'nombre_completo' => trim($this->getPost('nombre_completo', '')),
            'nombre_usuario' => strtolower(trim($this->getPost('nombre_usuario', ''))),
            'email' => $this->valorNullable(strtolower($this->getPost('email', ''))),
            'password' => (string) $this->getPost('password', ''),
            'rol_hotel' => trim($this->getPost('rol_hotel', 'administrador')),
            'es_principal' => (int) $this->getPost('es_principal', 0) === 1 ? 1 : 0,
            'activo' => (int) $this->getPost('activo', 1) === 1 ? 1 : 0,
        ];
    }

    private function datosBrandingDesdePost() {
        return [
            'nombre_visual' => trim($this->getPost('nombre_visual', '')),
            'logo_url' => trim($this->getPost('logo_url', '')),
            'favicon_url' => trim($this->getPost('favicon_url', '')),
            'login_background_url' => trim($this->getPost('login_background_url', '')),
            'pwa_icon_192_url' => trim($this->getPost('pwa_icon_192_url', '')),
            'pwa_icon_512_url' => trim($this->getPost('pwa_icon_512_url', '')),
            'color_primary' => trim($this->getPost('color_primary', '')),
            'color_secondary' => trim($this->getPost('color_secondary', '')),
            'color_accent' => trim($this->getPost('color_accent', '')),
            'sidebar_style' => trim($this->getPost('sidebar_style', 'default')),
            'login_style' => trim($this->getPost('login_style', 'default')),
            // Vacio = conserva el tema actual del hotel (normalizarTema del modelo).
            'tema' => trim($this->getPost('tema', '')),
            'activo' => (int) $this->getPost('activo', 1) === 1 ? 1 : 0,
        ];
    }

    private function procesarUploadsBranding(array $hotel, array &$data) {
        $errores = [];
        $mapa = [
            'logo_file' => ['tipo' => 'logo', 'campo' => 'logo_url'],
            'favicon_file' => ['tipo' => 'favicon', 'campo' => 'favicon_url'],
            'login_background_file' => ['tipo' => 'login_bg', 'campo' => 'login_background_url'],
            'pwa_icon_192_file' => ['tipo' => 'pwa_icon_192', 'campo' => 'pwa_icon_192_url'],
            'pwa_icon_512_file' => ['tipo' => 'pwa_icon_512', 'campo' => 'pwa_icon_512_url'],
        ];

        foreach ($mapa as $input => $config) {
            if (empty($_FILES[$input]) || ($_FILES[$input]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (!function_exists('hotel_branding_upload_asset')) {
                $errores[] = 'El helper de upload de branding no esta disponible.';
                continue;
            }

            $resultado = hotel_branding_upload_asset($_FILES[$input], $hotel['slug'] ?? '', $config['tipo']);
            if (empty($resultado['success'])) {
                $errores[] = $resultado['error'] ?? 'No se pudo subir el asset de branding.';
                continue;
            }

            if (!empty($resultado['uploaded']) && !empty($resultado['path'])) {
                $data[$config['campo']] = $resultado['path'];
            }
        }

        return $errores;
    }

    private function validarDatosAdminHotel(array $data, $usuarioExistente = null) {
        $errores = [];

        if ($data['nombre_usuario'] === '') {
            $errores[] = 'El nombre de usuario es obligatorio.';
        } elseif (strlen($data['nombre_usuario']) < 4) {
            $errores[] = 'El nombre de usuario debe tener al menos 4 caracteres.';
        } elseif (!preg_match('/^[a-z0-9_]+$/', $data['nombre_usuario'])) {
            $errores[] = 'El nombre de usuario solo puede contener minusculas, numeros y guiones bajos.';
        }

        if (!$usuarioExistente) {
            if ($data['nombre_completo'] === '') {
                $errores[] = 'El nombre completo es obligatorio para crear un usuario nuevo.';
            }

            if ($data['password'] === '') {
                $errores[] = 'La contrasena temporal es obligatoria para crear un usuario nuevo.';
            } elseif (strlen($data['password']) < 10) {
                $errores[] = 'La contrasena temporal debe tener al menos 10 caracteres.';
            }
        }

        if (!empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El email no tiene un formato valido.';
        }

        $rolesValidos = ['administrador', 'gerente'];
        if (!in_array($data['rol_hotel'], $rolesValidos, true)) {
            $errores[] = 'El rol hotelero seleccionado no es valido.';
        }

        return $errores;
    }

    private function guardarOldInputAdminHotel(array $data) {
        unset($data['password']);
        save_old_input($data);
    }

    private function normalizarModuloIds($modulos) {
        if (!is_array($modulos)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $modulos))));
    }

    private function modulosPorPlan(array $planes) {
        $resultado = [];

        foreach ($planes as $plan) {
            $resultado[(int) $plan['id']] = $this->planModel->listarModulosDelPlan((int) $plan['id']);
        }

        return $resultado;
    }

    private function normalizarSlug($slug) {
        return strtolower(trim((string) $slug));
    }

    private function valorNullable($valor) {
        $valor = trim((string) $valor);
        return $valor === '' ? null : $valor;
    }
}
