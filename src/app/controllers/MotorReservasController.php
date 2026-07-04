<?php
/**
 * Panel interno del motor de reservas (bloque motor_reservas).
 *
 * - Tablero: pagos online (por conciliar / conciliados / con problema),
 *   estado del motor y link publico.
 * - Conciliar: registra el anticipo REAL en Caja via AnticipoService
 *   (exige corte abierto; misma trazabilidad que un anticipo manual).
 * - Configuracion: claves motor.* + credenciales de pasarela cifradas.
 */

require_once __DIR__ . '/../services/AnticipoService.php';
require_once __DIR__ . '/../services/MotorPasarelaService.php';

class MotorReservasController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('motor_reservas');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = $this->hotelIdActual();
        $db = Database::getInstance();

        $pagos = [];
        $resumen = ['por_conciliar' => 0, 'monto_por_conciliar' => 0.0, 'conciliados' => 0, 'con_problema' => 0];

        try {
            $stmt = $db->query(
                "SELECT p.*, r.fecha_entrada, r.fecha_salida, r.estado AS reservacion_estado
                 FROM motor_pagos_online p
                 LEFT JOIN reservaciones r ON r.id = p.reservacion_id AND r.hotel_id = p.hotel_id
                 WHERE p.hotel_id = ?
                 ORDER BY FIELD(p.estado, 'pagado', 'pendiente', 'fallido', 'reembolsado', 'expirado', 'conciliado'), p.id DESC
                 LIMIT 200",
                [$hotelId]
            );
            $pagos = $stmt ? $stmt->fetchAll() : [];

            foreach ($pagos as $pago) {
                switch ($pago['estado']) {
                    case 'pagado':
                        $resumen['por_conciliar']++;
                        $resumen['monto_por_conciliar'] += (float) $pago['monto'];
                        break;
                    case 'conciliado':
                        $resumen['conciliados']++;
                        break;
                    case 'fallido':
                    case 'reembolsado':
                        $resumen['con_problema']++;
                        break;
                }
            }
        } catch (Throwable $e) {
            error_log('Motor interno: error al listar pagos online: ' . $e->getMessage());
        }

        $pasarela = new MotorPasarelaService();
        $credenciales = null;
        try {
            $stmt = $db->query(
                "SELECT proveedor, public_key, modo, activo,
                        (secret_key_encrypted IS NOT NULL AND secret_key_encrypted <> '') AS secret_configurado,
                        (webhook_secret_encrypted IS NOT NULL AND webhook_secret_encrypted <> '') AS webhook_configurado
                 FROM hotel_pasarela_credenciales WHERE hotel_id = ? LIMIT 1",
                [$hotelId]
            );
            $credenciales = $stmt ? ($stmt->fetch() ?: null) : null;
        } catch (Throwable $e) {
            error_log('Motor interno: error al leer credenciales: ' . $e->getMessage());
        }

        $slugHotel = (string) (function_exists('current_hotel_slug') ? current_hotel_slug() : '');

        View::renderTemplate('motor_reservas/index', [
            'title' => 'Motor de reservas - ' . current_hotel_display_name(),
            'pagos' => $pagos,
            'resumen' => $resumen,
            'credenciales' => $credenciales,
            'urlPublica' => url('h/' . $slugHotel . '/reservar'),
            'urlWebhook' => url('h/' . $slugHotel . '/reservar/webhook/' . ($credenciales['proveedor'] ?? 'stripe')),
            'config' => [
                'publico_activo' => ConfiguracionHotelRegistry::getBool('motor.publico_activo', false, $hotelId),
                'anticipo_tipo' => (string) ConfiguracionHotelRegistry::get('motor.anticipo_tipo', 'porcentaje', $hotelId),
                'anticipo_valor' => (float) ConfiguracionHotelRegistry::get('motor.anticipo_valor', 30.0, $hotelId),
                'min_noches' => ConfiguracionHotelRegistry::getInt('motor.min_noches', 1, $hotelId),
                'max_noches' => ConfiguracionHotelRegistry::getInt('motor.max_noches', 30, $hotelId),
                'anticipacion_max_dias' => ConfiguracionHotelRegistry::getInt('motor.anticipacion_max_dias', 180, $hotelId),
                'politica_texto' => (string) ConfiguracionHotelRegistry::get('motor.politica_texto', '', $hotelId),
            ],
        ]);
    }

    /**
     * Conciliar un pago online 'pagado' a Caja como anticipo real de la reservacion.
     * AnticipoService valida corte abierto, saldo y maneja su propia transaccion.
     */
    public function conciliarAction($id) {
        if (!$this->isPost()) {
            $this->redirect('motor-reservas');
        }

        $this->validateCSRF();
        $hotelId = $this->hotelIdActual();
        $db = Database::getInstance();

        $stmt = $db->query(
            "SELECT * FROM motor_pagos_online WHERE id = ? AND hotel_id = ? LIMIT 1",
            [(int) $id, $hotelId]
        );
        $pago = $stmt ? $stmt->fetch() : null;

        if (!$pago) {
            set_mensaje('Pago online no encontrado para este hotel.', 'error');
            $this->redirect('motor-reservas');
        }

        if (($pago['estado'] ?? '') !== 'pagado') {
            set_mensaje('Este pago no esta listo para conciliar (estado: ' . htmlspecialchars((string) $pago['estado'], ENT_QUOTES, 'UTF-8') . ').', 'error');
            $this->redirect('motor-reservas');
        }

        if (empty($pago['reservacion_id'])) {
            set_mensaje('Este pago no tiene reservacion ligada; revisalo manualmente.', 'error');
            $this->redirect('motor-reservas');
        }

        try {
            $servicio = new AnticipoService();
            $resultado = $servicio->registrar(
                $hotelId,
                (int) $pago['reservacion_id'],
                [
                    'monto' => (float) $pago['monto'],
                    'metodo_pago' => 'transferencia',
                    'referencia' => mb_substr((string) $pago['proveedor'] . ':' . (string) $pago['proveedor_pago_id'], 0, 100),
                    'concepto' => 'Anticipo online (motor de reservas)',
                ],
                user_id()
            );

            $db->query(
                "UPDATE motor_pagos_online
                 SET estado = 'conciliado', abono_id = ?, conciliado_por = ?, conciliado_at = NOW(), updated_at = NOW()
                 WHERE id = ? AND hotel_id = ? AND estado = 'pagado'",
                [(int) $resultado['abono_id'], user_id(), (int) $pago['id'], $hotelId]
            );

            set_mensaje(
                'Pago conciliado a Caja: anticipo de $' . number_format((float) $pago['monto'], 2) .
                ' registrado en la reservacion #' . (int) $pago['reservacion_id'] . '.',
                'success'
            );
        } catch (Throwable $e) {
            set_mensaje('No se pudo conciliar: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'), 'error');
        }

        $this->redirect('motor-reservas');
    }

    /** Guarda configuracion del motor (claves motor.*) y credenciales de pasarela. */
    public function guardarConfiguracionAction() {
        if (!$this->isPost()) {
            $this->redirect('motor-reservas');
        }

        $this->validateCSRF();
        $hotelId = $this->hotelIdActual();

        $anticipoTipo = (string) $this->getPost('anticipo_tipo', 'porcentaje');
        if (!in_array($anticipoTipo, ['porcentaje', 'primera_noche', 'monto_fijo'], true)) {
            $anticipoTipo = 'porcentaje';
        }

        $claves = [
            'motor.publico_activo' => ['boolean', (int) $this->getPost('publico_activo', 0) === 1 ? '1' : '0'],
            'motor.anticipo_tipo' => ['string', $anticipoTipo],
            'motor.anticipo_valor' => ['float', (string) max(0, (float) str_replace(',', '', (string) $this->getPost('anticipo_valor', '30')))],
            'motor.min_noches' => ['integer', (string) max(1, (int) $this->getPost('min_noches', 1))],
            'motor.max_noches' => ['integer', (string) max(1, (int) $this->getPost('max_noches', 30))],
            'motor.anticipacion_max_dias' => ['integer', (string) max(1, (int) $this->getPost('anticipacion_max_dias', 180))],
            'motor.politica_texto' => ['string', mb_substr(trim((string) $this->getPost('politica_texto', '')), 0, 500)],
        ];

        try {
            $db = Database::getInstance();
            foreach ($claves as $clave => [$tipo, $valor]) {
                $db->query(
                    "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'motor', 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), activo = 1, updated_at = NOW()",
                    [$hotelId, $clave, $valor, $tipo]
                );
            }
            if (function_exists('hotel_config_cache_invalidar')) {
                hotel_config_cache_invalidar($hotelId);
            }
        } catch (Throwable $e) {
            error_log('Motor interno: error al guardar configuracion: ' . $e->getMessage());
            set_mensaje('No se pudo guardar la configuracion del motor.', 'error');
            $this->redirect('motor-reservas');
        }

        // Credenciales de pasarela (secret vacio = conservar el guardado).
        $pasarela = new MotorPasarelaService();
        $ok = $pasarela->guardarCredenciales(
            $hotelId,
            (string) $this->getPost('proveedor', 'stripe'),
            (string) $this->getPost('public_key', ''),
            (string) $this->getPost('secret_key', ''),
            (string) $this->getPost('webhook_secret', ''),
            (string) $this->getPost('modo', 'test'),
            (int) $this->getPost('pasarela_activa', 1) === 1
        );

        if (!$ok) {
            set_mensaje('Configuracion guardada, pero hubo un problema con las credenciales de la pasarela.', 'error');
            $this->redirect('motor-reservas');
        }

        set_mensaje('Configuracion del motor de reservas guardada correctamente.', 'success');
        $this->redirect('motor-reservas');
    }

    // ───────────── Cupones (bloque promociones) ─────────────

    public function cuponesAction() {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('promociones');
        }

        $hotelId = $this->hotelIdActual();
        $servicio = $this->cuponService();

        View::renderTemplate('motor_reservas/cupones', [
            'title' => 'Cupones - ' . current_hotel_display_name(),
            'cupones' => $servicio->listar($hotelId),
        ]);
    }

    public function crearCuponAction() {
        if (!$this->isPost()) {
            $this->redirect('motor-reservas/cupones');
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('promociones');
        }

        $this->validateCSRF();
        $hotelId = $this->hotelIdActual();

        $resultado = $this->cuponService()->crear($hotelId, [
            'codigo' => (string) $this->getPost('codigo', ''),
            'tipo' => (string) $this->getPost('tipo', 'porcentaje'),
            'valor' => (string) $this->getPost('valor', '0'),
            'vigente_desde' => (string) $this->getPost('vigente_desde', ''),
            'vigente_hasta' => (string) $this->getPost('vigente_hasta', ''),
            'limite_usos' => (string) $this->getPost('limite_usos', ''),
        ], user_id());

        set_mensaje($resultado['message'], $resultado['success'] ? 'success' : 'error');
        $this->redirect('motor-reservas/cupones');
    }

    public function alternarCuponAction($id) {
        if (!$this->isPost()) {
            $this->redirect('motor-reservas/cupones');
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('promociones');
        }

        $this->validateCSRF();
        $hotelId = $this->hotelIdActual();

        $ok = $this->cuponService()->alternar($hotelId, (int) $id);
        set_mensaje($ok ? 'Cupon actualizado.' : 'No se pudo actualizar el cupon.', $ok ? 'success' : 'error');
        $this->redirect('motor-reservas/cupones');
    }

    private function cuponService() {
        if (!class_exists('MotorCuponService')) {
            require_once __DIR__ . '/../services/MotorCuponService.php';
        }

        return new MotorCuponService();
    }

    private function hotelIdActual() {
        return (int) obtenerHotelIdActualCompat();
    }
}
