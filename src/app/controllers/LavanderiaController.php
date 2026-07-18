<?php

require_once __DIR__ . '/../models/LavanderiaBlanco.php';
require_once __DIR__ . '/../models/LavanderiaLote.php';
require_once __DIR__ . '/../models/LavanderiaPedido.php';
require_once __DIR__ . '/../models/LavanderiaServicio.php';
require_once __DIR__ . '/../models/MovimientoCaja.php';
require_once __DIR__ . '/../helpers/modulos.php';

/**
 * Modulo Lavanderia (bloque comercial 'lavanderia'): blancos con stock por
 * estado, ciclos de lavado por lote y pedidos de ropa de huesped.
 *
 * DINERO (invariantes de Caja intactos):
 *  - Cobro de pedido  = MovimientoCaja ingreso, categoria lazy 'Lavanderia',
 *    referencia LAV-{id}, idempotente via cobro_movimiento_id (FOR UPDATE).
 *  - Gasto de lote    = MovimientoCaja gasto, referencia LAVLOTE-{id},
 *    idempotente via gasto_movimiento_id. Caja cerrada NUNCA truena: el
 *    importe queda "por registrar" y se reintenta desde el detalle.
 *  - JAMAS reservacion_abonos ni CxC ligadas a la reserva (el vinculo del
 *    pedido con la reservacion es solo informativo).
 */
class LavanderiaController extends Controller {
    private $blancoModel;
    private $loteModel;
    private $pedidoModel;
    private $servicioModel;

    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->blancoModel = new LavanderiaBlanco();
        $this->loteModel = new LavanderiaLote();
        $this->pedidoModel = new LavanderiaPedido();
        $this->servicioModel = new LavanderiaServicio();
    }

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('lavanderia');
        }

        if (function_exists('require_permission')) {
            require_permission('lavanderia.view');
        }

        return true;
    }

    private function hotelIdActual(): int {
        return (int)obtenerHotelIdActualCompat();
    }

    private function usuarioIdActual(): ?int {
        $id = (int)($_SESSION['user_id'] ?? 0);
        return $id > 0 ? $id : null;
    }

    private function puedeOperar(): bool {
        return function_exists('can') ? can('lavanderia.operar') : true;
    }

    private function puedeCobrar(): bool {
        return function_exists('can') ? can('lavanderia.cobrar') : true;
    }

    /* ====================================================================
     * Panel (blancos + resumen)
     * ================================================================== */

    public function indexAction() {
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('lavanderia/index', [
            'title' => 'Lavandería - ' . current_hotel_display_name(),
            'blancos' => $this->blancoModel->listar($hotelId),
            'resumen_stock' => $this->blancoModel->resumenStock($hotelId),
            'resumen_lotes' => $this->loteModel->resumen($hotelId),
            'resumen_pedidos' => $this->pedidoModel->resumen($hotelId),
            'movimientos' => $this->blancoModel->movimientosRecientes($hotelId, 10),
            'categorias' => LavanderiaBlanco::catalogoCategorias(),
            'tipos_movimiento' => LavanderiaBlanco::catalogoTiposManuales(),
            'puede_operar' => $this->puedeOperar(),
        ]);
    }

    public function blancoGuardarAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $hotelId = $this->hotelIdActual();
        $id = (int)$this->getPost('id', 0);

        try {
            $this->blancoModel->guardar([
                'nombre' => (string)$this->getPost('nombre', ''),
                'categoria' => (string)$this->getPost('categoria', 'otro'),
                'stock_minimo' => (int)$this->getPost('stock_minimo', 0),
                'stock_limpio' => (int)$this->getPost('stock_limpio', 0),
                'notas' => (string)$this->getPost('notas', ''),
                'usuario_id' => $this->usuarioIdActual(),
            ], $id > 0 ? $id : null, $hotelId);

            set_mensaje($id > 0 ? 'Blanco actualizado.' : 'Blanco dado de alta.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('lavanderia');
    }

    public function blancoToggleAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $id = (int)($this->route_params['id'] ?? 0);
        $ok = $this->blancoModel->toggle($id, $this->hotelIdActual());
        set_mensaje($ok ? 'Blanco actualizado.' : 'No se pudo actualizar el blanco.', $ok ? 'success' : 'error');
        $this->redirect('lavanderia');
    }

    public function blancoMovimientoAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $hotelId = $this->hotelIdActual();
        $blancoId = (int)$this->getPost('blanco_id', 0);
        $tipo = (string)$this->getPost('tipo', '');
        $cantidad = (int)$this->getPost('cantidad', 0);
        $notas = trim((string)$this->getPost('notas', ''));

        try {
            $resultado = $this->blancoModel->registrarMovimiento($hotelId, $blancoId, $tipo, $cantidad, $this->usuarioIdActual(), $notas);
            $etiquetas = LavanderiaBlanco::catalogoTiposManuales();
            set_mensaje('Movimiento registrado: ' . ($etiquetas[$tipo] ?? $tipo) . ' × ' . $cantidad . ' de "' . $resultado['blanco'] . '".', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('lavanderia');
    }

    /* ====================================================================
     * Ciclos de lavado (lotes)
     * ================================================================== */

    public function lotesAction() {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'estado' => (string)$this->getQuery('estado', 'todos'),
            'tipo' => (string)$this->getQuery('tipo', 'todos'),
        ];

        View::renderTemplate('lavanderia/lotes', [
            'title' => 'Ciclos de lavado - ' . current_hotel_display_name(),
            'lotes' => $this->loteModel->listar($hotelId, $filtros),
            'filtros' => $filtros,
            'resumen_lotes' => $this->loteModel->resumen($hotelId),
            'puede_operar' => $this->puedeOperar(),
            'puede_cobrar' => $this->puedeCobrar(),
        ]);
    }

    public function loteNuevoAction() {
        $this->requirePermission('lavanderia.operar');
        $hotelId = $this->hotelIdActual();

        $blancos = array_values(array_filter(
            $this->blancoModel->listar($hotelId, true),
            static function ($b) {
                return (int)$b['stock_sucio'] > 0;
            }
        ));

        View::renderTemplate('lavanderia/lote_nuevo', [
            'title' => 'Enviar a lavar - ' . current_hotel_display_name(),
            'blancos' => $blancos,
            'hay_blancos' => count($this->blancoModel->listar($hotelId, true)) > 0,
        ]);
    }

    public function loteGuardarAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/lotes');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $hotelId = $this->hotelIdActual();
        $tipo = (string)$this->getPost('tipo', 'interno');
        $proveedor = (string)$this->getPost('proveedor', '');
        $notas = (string)$this->getPost('notas', '');

        // cantidades[] llega como mapa blanco_id => cantidad (arrays crudos de $_POST).
        $cantidades = $_POST['cantidades'] ?? [];
        if (!is_array($cantidades)) {
            $cantidades = [];
        }

        try {
            $loteId = $this->loteModel->crear($hotelId, $tipo, $proveedor, $notas, $cantidades, $this->usuarioIdActual());
            set_mensaje('Lote #' . $loteId . ' enviado a lavar.', 'success');
            $this->redirect('lavanderia/lotes/' . $loteId);
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
            $this->redirect('lavanderia/lotes/nuevo');
        }
    }

    public function loteVerAction() {
        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);

        $lote = $this->loteModel->obtenerPorId($id, $hotelId);
        if (!$lote) {
            set_mensaje('Lote no encontrado.', 'error');
            $this->redirect('lavanderia/lotes');
            return;
        }

        View::renderTemplate('lavanderia/lote_ver', [
            'title' => 'Lote de lavado #' . $id . ' - ' . current_hotel_display_name(),
            'lote' => $lote,
            'items' => $this->loteModel->items($id, $hotelId),
            'metodos_pago' => MovimientoCaja::getMetodosPago(),
            'puede_operar' => $this->puedeOperar(),
            'puede_cobrar' => $this->puedeCobrar(),
        ]);
    }

    public function loteRecibirAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/lotes');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);

        $recibidas = $_POST['recibidas'] ?? [];
        if (!is_array($recibidas)) {
            $recibidas = [];
        }

        $costo = $this->parseMonto($this->getPost('costo', ''));
        if ($costo !== null && $costo <= 0) {
            $costo = null;
        }

        try {
            $resultado = $this->loteModel->recibir($hotelId, $id, $recibidas, $costo, $this->usuarioIdActual());

            $mensaje = 'Lote #' . $id . ' recibido: ' . (int)$resultado['recibidas'] . ' piezas de vuelta a limpio'
                . ((int)$resultado['merma'] > 0 ? ' y ' . (int)$resultado['merma'] . ' de merma' : '') . '.';

            // Costo capturado: intenta el gasto en Caja de una vez (si el
            // usuario puede). Caja cerrada = queda "por registrar", sin tronar.
            if ($costo !== null && $this->puedeCobrar()) {
                $metodoPago = $this->metodoPagoValido((string)$this->getPost('metodo_pago', 'efectivo'));
                $gasto = $this->registrarGastoDeLote($id, $hotelId, $metodoPago);
                $mensaje .= ' ' . $gasto['message'];
                set_mensaje($mensaje, $gasto['success'] ? 'success' : 'warning');
            } else {
                set_mensaje($mensaje, 'success');
            }
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('lavanderia/lotes/' . $id);
    }

    public function loteCancelarAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/lotes');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);

        try {
            $this->loteModel->cancelar($hotelId, $id, $this->usuarioIdActual());
            set_mensaje('Lote #' . $id . ' cancelado: las piezas volvieron a sucio.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('lavanderia/lotes/' . $id);
    }

    public function loteGastoAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/lotes');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.cobrar');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $metodoPago = $this->metodoPagoValido((string)$this->getPost('metodo_pago', 'efectivo'));

        $resultado = $this->registrarGastoDeLote($id, $hotelId, $metodoPago);
        set_mensaje($resultado['message'], $resultado['success'] ? 'success' : 'warning');
        $this->redirect('lavanderia/lotes/' . $id);
    }

    /* ====================================================================
     * Pedidos de huesped
     * ================================================================== */

    public function pedidosAction() {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'estado' => (string)$this->getQuery('estado', 'activos'),
            'buscar' => (string)$this->getQuery('buscar', ''),
        ];

        View::renderTemplate('lavanderia/pedidos', [
            'title' => 'Pedidos de lavandería - ' . current_hotel_display_name(),
            'pedidos' => $this->pedidoModel->listar($hotelId, $filtros),
            'filtros' => $filtros,
            'resumen_pedidos' => $this->pedidoModel->resumen($hotelId),
            'servicios' => $this->servicioModel->listar($hotelId),
            'estados' => LavanderiaPedido::catalogoEstados(),
            'puede_operar' => $this->puedeOperar(),
            'puede_cobrar' => $this->puedeCobrar(),
        ]);
    }

    public function pedidoNuevoAction() {
        $this->requirePermission('lavanderia.operar');
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('lavanderia/pedido_nuevo', [
            'title' => 'Nuevo pedido de lavandería - ' . current_hotel_display_name(),
            'reservaciones' => $this->pedidoModel->reservacionesEnCasa($hotelId),
            'servicios' => $this->servicioModel->listar($hotelId, true),
        ]);
    }

    public function pedidoGuardarAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/pedidos');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $hotelId = $this->hotelIdActual();

        // Partidas: arrays paralelos crudos de $_POST (getPost sanitiza a
        // htmlspecialchars por valor, suficiente para strings; los numeros se castean).
        $descripciones = $_POST['item_descripcion'] ?? [];
        $cantidades = $_POST['item_cantidad'] ?? [];
        $precios = $_POST['item_precio'] ?? [];
        if (!is_array($descripciones)) {
            $descripciones = [];
        }

        $items = [];
        foreach ($descripciones as $i => $descripcion) {
            $items[] = [
                'descripcion' => htmlspecialchars(trim((string)$descripcion), ENT_QUOTES, 'UTF-8'),
                'cantidad' => (int)($cantidades[$i] ?? 0),
                'precio' => $this->parseMonto((string)($precios[$i] ?? '')) ?? 0.0,
            ];
        }

        $vinculo = (string)$this->getPost('vinculo', 'externo');

        try {
            $pedidoId = $this->pedidoModel->crear($hotelId, [
                'reservacion_id' => $vinculo === 'huesped' ? (int)$this->getPost('reservacion_id', 0) : 0,
                'cliente_nombre' => (string)$this->getPost('cliente_nombre', ''),
                'notas' => (string)$this->getPost('notas', ''),
            ], $items, $this->usuarioIdActual());

            set_mensaje('Pedido #' . $pedidoId . ' registrado.', 'success');
            $this->redirect('lavanderia/pedidos/' . $pedidoId);
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
            $this->redirect('lavanderia/pedidos/nuevo');
        }
    }

    public function pedidoVerAction() {
        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);

        $pedido = $this->pedidoModel->obtenerPorId($id, $hotelId);
        if (!$pedido) {
            set_mensaje('Pedido no encontrado.', 'error');
            $this->redirect('lavanderia/pedidos');
            return;
        }

        View::renderTemplate('lavanderia/pedido_ver', [
            'title' => 'Pedido de lavandería #' . $id . ' - ' . current_hotel_display_name(),
            'pedido' => $pedido,
            'items' => $this->pedidoModel->items($id, $hotelId),
            'estados' => LavanderiaPedido::catalogoEstados(),
            'metodos_pago' => MovimientoCaja::getMetodosPago(),
            'puede_operar' => $this->puedeOperar(),
            'puede_cobrar' => $this->puedeCobrar(),
        ]);
    }

    public function pedidoEstadoAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/pedidos');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $accion = (string)$this->getPost('accion', '');

        try {
            $resultado = $this->pedidoModel->cambiarEstado($hotelId, $id, $accion, $this->usuarioIdActual());
            $labels = LavanderiaPedido::catalogoEstados();
            set_mensaje('Pedido #' . $id . ': ' . ($labels[$resultado['nuevo']]['label'] ?? $resultado['nuevo']) . '.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('lavanderia/pedidos/' . $id);
    }

    public function pedidoCobrarAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/pedidos');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.cobrar');

        $hotelId = $this->hotelIdActual();
        $id = (int)($this->route_params['id'] ?? 0);
        $metodoPago = $this->metodoPagoValido((string)$this->getPost('metodo_pago', 'efectivo'));

        $resultado = $this->registrarCobroDePedido($id, $hotelId, $metodoPago);
        set_mensaje($resultado['message'], $resultado['success'] ? 'success' : 'warning');
        $this->redirect('lavanderia/pedidos/' . $id);
    }

    /* ====================================================================
     * Catalogo de servicios/precios
     * ================================================================== */

    public function servicioGuardarAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/pedidos');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        try {
            $this->servicioModel->guardar([
                'nombre' => (string)$this->getPost('nombre', ''),
                'precio' => $this->parseMonto($this->getPost('precio', '')) ?? 0.0,
            ], $this->hotelIdActual());
            set_mensaje('Servicio guardado en el catálogo.', 'success');
        } catch (Throwable $e) {
            set_mensaje($e->getMessage(), 'error');
        }

        $this->redirect('lavanderia/pedidos');
    }

    public function servicioToggleAction() {
        if (!$this->isPost()) {
            $this->redirect('lavanderia/pedidos');
            return;
        }

        $this->validateCSRF();
        $this->requirePermission('lavanderia.operar');

        $id = (int)($this->route_params['id'] ?? 0);
        $ok = $this->servicioModel->toggle($id, $this->hotelIdActual());
        set_mensaje($ok ? 'Servicio actualizado.' : 'No se pudo actualizar el servicio.', $ok ? 'success' : 'error');
        $this->redirect('lavanderia/pedidos');
    }

    /* ====================================================================
     * Dinero (calco del patron MantenimientoController::registrarGastoDeMantenimiento)
     * ================================================================== */

    /**
     * Cobro del pedido por Caja: ingreso idempotente (FOR UPDATE del pedido
     * + guard cobro_movimiento_id IS NULL). Caja cerrada = "por cobrar".
     */
    private function registrarCobroDePedido(int $id, int $hotelId, string $metodoPago): array {
        if ($id <= 0 || $hotelId <= 0) {
            return ['success' => false, 'message' => 'Pedido no valido.'];
        }

        $db = Database::getInstance();

        try {
            $db->safeBeginTransaction();

            $stmt = $db->query(
                "SELECT * FROM lavanderia_pedidos
                 WHERE id = ? AND hotel_id = ?
                 LIMIT 1
                 FOR UPDATE",
                [$id, $hotelId]
            );
            $pedido = $stmt ? $stmt->fetch() : null;

            if (!$pedido) {
                throw new RuntimeException('Pedido no encontrado para el hotel actual.');
            }

            if (!empty($pedido['cobro_movimiento_id'])) {
                $db->safeRollBack();
                return ['success' => true, 'message' => 'Este pedido ya estaba cobrado en Caja.'];
            }

            if ((string)$pedido['estado'] === 'cancelado') {
                throw new RuntimeException('No se cobra un pedido cancelado.');
            }

            $total = (float)($pedido['total'] ?? 0);
            if ($total <= 0) {
                throw new RuntimeException('El pedido no tiene importe a cobrar.');
            }

            $categoriaId = $this->asegurarCategoriaLavanderia($hotelId, 'ingreso');
            $cliente = trim((string)($pedido['cliente_nombre'] ?? ''));
            $habitacion = trim((string)($pedido['habitacion_etiqueta'] ?? ''));

            $movimientoModel = new MovimientoCaja();
            $resultado = $movimientoModel->registrarMovimiento([
                'tipo' => 'ingreso',
                'categoria_id' => $categoriaId,
                'descripcion' => 'Lavandería pedido #' . $id . ' (' . mb_substr($cliente, 0, 80)
                    . ($habitacion !== '' ? ', hab. ' . $habitacion : '') . ')',
                'monto' => $total,
                'metodo_pago' => $metodoPago,
                'referencia' => 'LAV-' . $id,
                'reservacion_id' => !empty($pedido['reservacion_id']) ? (int)$pedido['reservacion_id'] : null,
            ]);

            if (empty($resultado['success'])) {
                // Caja cerrada u otro candado: el cobro queda pendiente.
                if ($db->enTransaccion()) {
                    $db->safeRollBack();
                }
                $motivo = (string)($resultado['message'] ?? 'No se pudo registrar el movimiento');
                return [
                    'success' => false,
                    'message' => 'El cobro quedo PENDIENTE: ' . $motivo . '. Reintenta al abrir caja.',
                ];
            }

            $db->query(
                "UPDATE lavanderia_pedidos
                 SET cobro_movimiento_id = ?, cobrado_en = NOW(), metodo_pago = ?, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ? AND cobro_movimiento_id IS NULL",
                [(int)$resultado['movimiento_id'], $metodoPago, $id, $hotelId]
            );

            $db->safeCommit();

            return [
                'success' => true,
                'message' => 'Cobro de $' . number_format($total, 2) . ' registrado en Caja (referencia LAV-' . $id . ').',
            ];
        } catch (Throwable $e) {
            if ($db->enTransaccion()) {
                $db->safeRollBack();
            }
            return ['success' => false, 'message' => 'No se registro el cobro: ' . $e->getMessage()];
        }
    }

    /**
     * Gasto del lote (lavado externo) por Caja: egreso idempotente
     * (FOR UPDATE + gasto_movimiento_id IS NULL). Caja cerrada = por registrar.
     */
    private function registrarGastoDeLote(int $id, int $hotelId, string $metodoPago): array {
        if ($id <= 0 || $hotelId <= 0) {
            return ['success' => false, 'message' => 'Lote no valido.'];
        }

        $db = Database::getInstance();

        try {
            $db->safeBeginTransaction();

            $stmt = $db->query(
                "SELECT * FROM lavanderia_lotes
                 WHERE id = ? AND hotel_id = ?
                 LIMIT 1
                 FOR UPDATE",
                [$id, $hotelId]
            );
            $lote = $stmt ? $stmt->fetch() : null;

            if (!$lote) {
                throw new RuntimeException('Lote no encontrado para el hotel actual.');
            }

            if (!empty($lote['gasto_movimiento_id'])) {
                $db->safeRollBack();
                return ['success' => true, 'message' => 'El gasto de este lote ya estaba registrado en Caja.'];
            }

            if ((string)$lote['estado'] !== 'recibido') {
                throw new RuntimeException('Solo se registra el gasto de un lote recibido.');
            }

            $costo = (float)($lote['costo'] ?? 0);
            if ($costo <= 0) {
                throw new RuntimeException('Este lote no tiene costo mayor a cero.');
            }

            $categoriaId = $this->asegurarCategoriaLavanderia($hotelId, 'gasto');

            $movimientoModel = new MovimientoCaja();
            $resultado = $movimientoModel->registrarMovimiento([
                'tipo' => 'gasto',
                'categoria_id' => $categoriaId,
                'descripcion' => 'Lavandería lote #' . $id . ' (' . (int)$lote['piezas_enviadas'] . ' piezas'
                    . ((string)$lote['tipo'] === 'externo' ? ', servicio externo' : '') . ')',
                'monto' => $costo,
                'metodo_pago' => $metodoPago,
                'referencia' => 'LAVLOTE-' . $id,
                'proveedor' => (string)($lote['proveedor'] ?? '') ?: null,
            ]);

            if (empty($resultado['success'])) {
                if ($db->enTransaccion()) {
                    $db->safeRollBack();
                }
                $motivo = (string)($resultado['message'] ?? 'No se pudo registrar el movimiento');
                return [
                    'success' => false,
                    'message' => 'El gasto quedo POR REGISTRAR: ' . $motivo . '. Reintenta al abrir caja.',
                ];
            }

            $db->query(
                "UPDATE lavanderia_lotes
                 SET gasto_movimiento_id = ?, gasto_registrado_en = NOW(), updated_at = NOW()
                 WHERE id = ? AND hotel_id = ? AND gasto_movimiento_id IS NULL",
                [(int)$resultado['movimiento_id'], $id, $hotelId]
            );

            $db->safeCommit();

            return [
                'success' => true,
                'message' => 'Gasto de $' . number_format($costo, 2) . ' registrado en Caja (referencia LAVLOTE-' . $id . ').',
            ];
        } catch (Throwable $e) {
            if ($db->enTransaccion()) {
                $db->safeRollBack();
            }
            return ['success' => false, 'message' => 'No se registro el gasto: ' . $e->getMessage()];
        }
    }

    /**
     * Categoria 'Lavanderia' del hotel por tipo (ingreso/gasto), creada lazy
     * respetando el catalogo por hotel de categorias_movimientos.
     */
    private function asegurarCategoriaLavanderia(int $hotelId, string $tipo): ?int {
        $tipo = $tipo === 'gasto' ? 'gasto' : 'ingreso';
        $db = Database::getInstance();

        $stmt = $db->query(
            "SELECT id FROM categorias_movimientos
             WHERE hotel_id = ? AND tipo = ? AND nombre = 'Lavanderia' AND activa = 1
             ORDER BY id ASC LIMIT 1",
            [$hotelId, $tipo]
        );
        $row = $stmt ? $stmt->fetch() : null;
        if ($row) {
            return (int)$row['id'];
        }

        $descripcion = $tipo === 'gasto'
            ? 'Costos de lavanderia (servicio externo, insumos del lavado)'
            : 'Cobros de lavanderia de huespedes';

        $db->query(
            "INSERT INTO categorias_movimientos (hotel_id, nombre, tipo, descripcion, icono, color, activa, orden)
             VALUES (?, 'Lavanderia', ?, ?, 'fas fa-shirt', '#2F77E0', 1, 0)",
            [$hotelId, $tipo, $descripcion]
        );

        $nuevoId = (int)$db->lastInsertId();
        return $nuevoId > 0 ? $nuevoId : null;
    }

    private function metodoPagoValido(string $metodoPago): string {
        return array_key_exists($metodoPago, MovimientoCaja::getMetodosPago()) ? $metodoPago : 'efectivo';
    }

    /**
     * Acepta montos con formato de MedisoftMoneyInput ("1,250.50").
     */
    private function parseMonto($valor): ?float {
        $valor = trim((string)$valor);
        if ($valor === '') {
            return null;
        }

        $valor = str_replace([',', '$', ' '], '', $valor);
        return is_numeric($valor) ? (float)$valor : null;
    }
}
