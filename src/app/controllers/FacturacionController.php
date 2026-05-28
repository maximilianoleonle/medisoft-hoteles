<?php
/**
 * Controlador de Facturación
 * Los Cedros
 * 
 * Gestiona las solicitudes de factura generadas durante el check-in
 */
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../models/Reservacion.php';
require_once __DIR__ . '/../models/Huesped.php';
require_once __DIR__ . '/../models/ReservacionNota.php';

class FacturacionController extends Controller {
    
    private $db;
    private $reservacionModel;
    private $huespedModel;
    private $notaModel;

    public function __construct($route_params = []) {
        parent::__construct($route_params);
        $this->db = Database::getInstance()->getConnection();
        $this->reservacionModel = new Reservacion();
        $this->huespedModel = new Huesped();
        $this->notaModel = new ReservacionNota();
    }
    
    protected function before() {
        $this->requireAuth();
        return true;
    }
    
    /**
     * Listado principal de solicitudes de factura
     */
    public function indexAction() {
        // Filtros
        $filtro_tipo = $this->getQuery('tipo', '');       // cliente, uso_interno
        $filtro_estatus = $this->getQuery('estatus', '');  // pendiente, en_proceso, completada, cancelada
        $buscar = $this->getQuery('buscar', '');
        $fecha_desde = $this->getQuery('fecha_desde', '');
        $fecha_hasta = $this->getQuery('fecha_hasta', '');
        $pagina = max(1, intval($this->getQuery('page', 1)));
        $por_pagina = 20;
        $hotel_id = obtenerHotelIdActualCompat();
        
        // Construir query
        $where = [
            "sf.hotel_id = ?",
            "r.hotel_id = ?"
        ];
        $params = [$hotel_id, $hotel_id];
        
        if ($filtro_tipo) {
            $where[] = "sf.tipo = ?";
            $params[] = $filtro_tipo;
        }
        
        if ($filtro_estatus) {
            $where[] = "sf.estatus = ?";
            $params[] = $filtro_estatus;
        }
        
        if ($buscar) {
    $where[] = "(h.nombre_completo LIKE ? OR sf.rfc LIKE ? OR sf.razon_social LIKE ? OR r.id LIKE ?)";
    $buscar_like = "%{$buscar}%";
    $params = array_merge($params, [$buscar_like, $buscar_like, $buscar_like, $buscar_like]);
}

        
        if ($fecha_desde) {
            $where[] = "DATE(sf.created_at) >= ?";
            $params[] = $fecha_desde;
        }
        
        if ($fecha_hasta) {
            $where[] = "DATE(sf.created_at) <= ?";
            $params[] = $fecha_hasta;
        }
        
        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Contar total
        $sql_count = "SELECT COUNT(*) as total 
                      FROM solicitudes_factura sf
                      INNER JOIN reservaciones r
                          ON sf.reservacion_id = r.id
                          AND sf.hotel_id = r.hotel_id
                      INNER JOIN huespedes h ON r.huesped_id = h.id
                      {$where_sql}";
        $stmt = $this->db->prepare($sql_count);
        $stmt->execute($params);
        $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Obtener registros paginados
        $offset = ($pagina - 1) * $por_pagina;
        
        $sql = "SELECT sf.*, 
                       r.id as reservacion_id, 
                       r.precio_total as reservacion_total,
                       r.fecha_entrada, 
                       r.fecha_salida,
                       r.estado as reservacion_estado,
                       r.metodo_pago as reservacion_metodo_pago,
                       h.nombre_completo as huesped_nombre,
                       h.telefono as huesped_telefono,
                       h.email as huesped_email,
                       u.nombre_completo as registrado_por
                FROM solicitudes_factura sf
                INNER JOIN reservaciones r
                    ON sf.reservacion_id = r.id
                    AND sf.hotel_id = r.hotel_id
                INNER JOIN huespedes h ON r.huesped_id = h.id
                LEFT JOIN usuarios u ON sf.usuario_registro_id = u.id
                {$where_sql}
                ORDER BY 
                    CASE sf.estatus 
                        WHEN 'pendiente' THEN 1 
                        WHEN 'en_proceso' THEN 2 
                        WHEN 'completada' THEN 3 
                        WHEN 'cancelada' THEN 4 
                    END,
                    sf.created_at DESC
                LIMIT {$por_pagina} OFFSET {$offset}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Estadísticas
        $estadisticas = $this->obtenerEstadisticas();
        
        View::renderTemplate('facturacion/index', [
            'title' => 'Facturación - Los Cedros',
            'solicitudes' => $solicitudes,
            'estadisticas' => $estadisticas,
            'filtro_tipo' => $filtro_tipo,
            'filtro_estatus' => $filtro_estatus,
            'buscar' => $buscar,
            'fecha_desde' => $fecha_desde,
            'fecha_hasta' => $fecha_hasta,
            'pagina_actual' => $pagina,
            'total_paginas' => ceil($total / $por_pagina),
            'total_registros' => $total
        ]);
    }
    
    /**
     * Detalle de una solicitud de factura
     */
    public function verAction() {
        $id = $this->route_params['id'] ?? 0;
        
        if (!$id) {
            set_mensaje('Solicitud no encontrada', 'error');
            $this->redirect('facturacion');
            return;
        }
        
        $solicitud = $this->obtenerSolicitudCompleta($id);
        
        if (!$solicitud) {
            set_mensaje('Solicitud de factura no encontrada', 'error');
            $this->redirect('facturacion');
            return;
        }
        
        // Obtener habitaciones de la reservación
        $habitaciones = $this->reservacionModel->getHabitaciones($solicitud['reservacion_id']);

        // Obtener notas de la reservación
        $notas_reservacion = $this->notaModel->obtenerPorReservacion($solicitud['reservacion_id']);

        // Obtener pagos de la reservación
        $pagos = [];
        try {
            $sql = "SELECT * FROM movimientos_caja WHERE reservacion_id = ? AND tipo = 'ingreso' ORDER BY created_at DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$solicitud['reservacion_id']]);
            $pagos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("Error al obtener pagos de facturación: " . $e->getMessage());
        }
        
        // Catálogos SAT
        $regimenes_fiscales = $this->getRegimenesFiscales();
        $usos_cfdi = $this->getUsosCFDI();
        
        View::renderTemplate('facturacion/detalle', [
            'title' => 'Factura #' . $id . ' - Los Cedros',
            'solicitud' => $solicitud,
            'habitaciones' => $habitaciones,
            'pagos' => $pagos,
            'notas_reservacion' => $notas_reservacion,
            'regimenes_fiscales' => $regimenes_fiscales,
            'usos_cfdi' => $usos_cfdi
        ]);
    }
    
    /**
     * Guardar datos fiscales de una solicitud
     */
    public function guardarAction() {
        if (!$this->isPost()) {
            $this->redirect('facturacion');
            return;
        }
        
        $this->validateCSRF();
        
        $id = $this->getPost('solicitud_id');
        
        if (!$id) {
            set_mensaje('ID de solicitud inválido', 'error');
            $this->redirect('facturacion');
            return;
        }
        
        try {
            $datos = [
                'rfc' => strtoupper(trim($this->getPost('rfc', ''))),
                'razon_social' => trim($this->getPost('razon_social', '')),
                'regimen_fiscal' => $this->getPost('regimen_fiscal', ''),
                'uso_cfdi' => $this->getPost('uso_cfdi', ''),
                'codigo_postal_fiscal' => trim($this->getPost('codigo_postal_fiscal', '')),
                'email_factura' => trim($this->getPost('email_factura', '')),
                'notas' => trim($this->getPost('notas', ''))
            ];
            
            // Validar RFC si se proporcionó
            if (!empty($datos['rfc']) && !$this->validarRFC($datos['rfc'])) {
                set_mensaje('El RFC ingresado no tiene un formato válido', 'error');
                $this->redirect('facturacion/ver/' . $id);
                return;
            }
            
            // Validar código postal si se proporcionó
            if (!empty($datos['codigo_postal_fiscal']) && !preg_match('/^\d{5}$/', $datos['codigo_postal_fiscal'])) {
                set_mensaje('El código postal fiscal debe tener 5 dígitos', 'error');
                $this->redirect('facturacion/ver/' . $id);
                return;
            }
            
            // Si tiene todos los datos fiscales requeridos, marcar como en_proceso
            if (!empty($datos['rfc']) && !empty($datos['razon_social']) && 
                !empty($datos['regimen_fiscal']) && !empty($datos['uso_cfdi']) && 
                !empty($datos['codigo_postal_fiscal'])) {
                $datos['estatus'] = 'en_proceso';
            }
            
            $resultado = $this->reservacionModel->actualizarSolicitudFactura($id, $datos);
            
            if ($resultado) {
                set_mensaje('Datos fiscales guardados correctamente', 'success');
            } else {
                set_mensaje('No se pudieron guardar los datos. Intente de nuevo.', 'error');
            }
            
        } catch (Exception $e) {
            error_log("Error al guardar datos fiscales: " . $e->getMessage());
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('facturacion/ver/' . $id);
    }
    
    /**
     * Marcar solicitud como facturada (completada)
     */
    public function completarAction() {
        if (!$this->isPost()) {
            $this->redirect('facturacion');
            return;
        }
        
        $this->validateCSRF();
        
        $id = $this->getPost('solicitud_id');
        $numero_factura = trim($this->getPost('numero_factura', ''));
        
        try {
            $datos = [
                'estatus' => 'completada',
                'fecha_facturada' => date('Y-m-d H:i:s'),
                'numero_factura' => $numero_factura ?: null
            ];
            
            $resultado = $this->reservacionModel->actualizarSolicitudFactura($id, $datos);
            
            if ($resultado) {
                set_mensaje('Solicitud marcada como facturada exitosamente', 'success');
            } else {
                set_mensaje('No se pudo actualizar el estatus', 'error');
            }
            
        } catch (Exception $e) {
            error_log("Error al completar factura: " . $e->getMessage());
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('facturacion/ver/' . $id);
    }
    
    /**
     * Cancelar una solicitud de factura
     */
    public function cancelarAction() {
        if (!$this->isPost()) {
            $this->redirect('facturacion');
            return;
        }
        
        $this->validateCSRF();
        
        $id = $this->getPost('solicitud_id');
        $motivo = trim($this->getPost('motivo', ''));
        
        try {
            $solicitud = $this->obtenerSolicitudCompleta($id);
            $notas_actuales = $solicitud['notas'] ?? '';
            $notas_nuevas = $notas_actuales . "\n[CANCELADA] " . date('d/m/Y H:i') . " - " . ($motivo ?: 'Sin motivo especificado');
            
            $datos = [
                'estatus' => 'cancelada',
                'notas' => trim($notas_nuevas)
            ];
            
            $resultado = $this->reservacionModel->actualizarSolicitudFactura($id, $datos);
            
            if ($resultado) {
                set_mensaje('Solicitud de factura cancelada', 'success');
            } else {
                set_mensaje('No se pudo cancelar la solicitud', 'error');
            }
            
        } catch (Exception $e) {
            error_log("Error al cancelar factura: " . $e->getMessage());
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('facturacion');
    }
    
    /**
     * Cambiar estatus a "en_proceso"
     */
    public function enProcesoAction() {
        if (!$this->isPost()) {
            $this->redirect('facturacion');
            return;
        }
        
        $this->validateCSRF();
        
        $id = $this->getPost('solicitud_id');
        
        try {
            $resultado = $this->reservacionModel->actualizarSolicitudFactura($id, [
                'estatus' => 'en_proceso'
            ]);
            
            if ($resultado) {
                set_mensaje('Solicitud marcada como "En proceso"', 'success');
            }
        } catch (Exception $e) {
            set_mensaje('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('facturacion/ver/' . $id);
    }
    
    // ======================================================================
    // MÉTODOS PRIVADOS
    // ======================================================================
    
    /**
     * Obtener solicitud completa con datos del huésped y reservación
     */
    private function obtenerSolicitudCompleta($id) {
        $hotel_id = obtenerHotelIdActualCompat();

        $sql = "SELECT sf.*, 
                       r.id as reservacion_id,
                       r.precio_total as reservacion_total,
                       r.fecha_entrada, 
                       r.fecha_salida,
                       r.hora_entrada,
                       r.hora_salida,
                       r.estado as reservacion_estado,
                       r.metodo_pago as reservacion_metodo_pago,
                       r.total_habitaciones,
                       r.habitaciones_cortesia,
                       r.notas as reservacion_notas,
                       h.id as huesped_id,
                       h.nombre_completo as huesped_nombre_raw,
h.nombre_completo as huesped_nombre,

                       h.telefono as huesped_telefono,
                       h.email as huesped_email,
                       u.nombre_completo as registrado_por
                FROM solicitudes_factura sf
                INNER JOIN reservaciones r
                    ON sf.reservacion_id = r.id
                    AND sf.hotel_id = r.hotel_id
                INNER JOIN huespedes h ON r.huesped_id = h.id
                LEFT JOIN usuarios u ON sf.usuario_registro_id = u.id
                WHERE sf.id = ?
                  AND sf.hotel_id = ?
                  AND r.hotel_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id, $hotel_id, $hotel_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Estadísticas para el dashboard de facturación
     */
    private function obtenerEstadisticas() {
        $stats = [];
        
        // Total pendientes
        $sql = "SELECT COUNT(*) as total FROM solicitudes_factura WHERE estatus = 'pendiente'";
        $stmt = $this->db->query($sql);
        $stats['pendientes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total en proceso
        $sql = "SELECT COUNT(*) as total FROM solicitudes_factura WHERE estatus = 'en_proceso'";
        $stmt = $this->db->query($sql);
        $stats['en_proceso'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Total completadas (este mes)
        $sql = "SELECT COUNT(*) as total FROM solicitudes_factura 
                WHERE estatus = 'completada' AND MONTH(fecha_facturada) = MONTH(NOW()) AND YEAR(fecha_facturada) = YEAR(NOW())";
        $stmt = $this->db->query($sql);
        $stats['completadas_mes'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Pendientes de cliente
        $sql = "SELECT COUNT(*) as total FROM solicitudes_factura WHERE estatus = 'pendiente' AND tipo = 'cliente'";
        $stmt = $this->db->query($sql);
        $stats['pendientes_cliente'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Pendientes uso interno
        $sql = "SELECT COUNT(*) as total FROM solicitudes_factura WHERE estatus = 'pendiente' AND tipo = 'uso_interno'";
        $stmt = $this->db->query($sql);
        $stats['pendientes_interno'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Monto total pendiente
        $sql = "SELECT COALESCE(SUM(monto_total), 0) as total FROM solicitudes_factura WHERE estatus IN ('pendiente', 'en_proceso')";
        $stmt = $this->db->query($sql);
        $stats['monto_pendiente'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        return $stats;
    }
    
    /**
     * Validar formato de RFC mexicano
     */
    private function validarRFC($rfc) {
        // RFC persona física: 4 letras + 6 números + 3 homoclave = 13
        // RFC persona moral: 3 letras + 6 números + 3 homoclave = 12
        $patron = '/^[A-ZÑ&]{3,4}\d{6}[A-Z0-9]{3}$/';
        return preg_match($patron, strtoupper($rfc));
    }
    
    /**
     * Catálogo de Regímenes Fiscales SAT
     */
    private function getRegimenesFiscales() {
        return [
            '601' => 'General de Ley Personas Morales',
            '603' => 'Personas Morales con Fines no Lucrativos',
            '605' => 'Sueldos y Salarios e Ingresos Asimilados a Salarios',
            '606' => 'Arrendamiento',
            '607' => 'Régimen de Enajenación o Adquisición de Bienes',
            '608' => 'Demás ingresos',
            '610' => 'Residentes en el Extranjero sin Establecimiento Permanente en México',
            '611' => 'Ingresos por Dividendos (socios y accionistas)',
            '612' => 'Personas Físicas con Actividades Empresariales y Profesionales',
            '614' => 'Ingresos por intereses',
            '615' => 'Régimen de los ingresos por obtención de premios',
            '616' => 'Sin obligaciones fiscales',
            '620' => 'Sociedades Cooperativas de Producción',
            '621' => 'Incorporación Fiscal',
            '622' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras',
            '623' => 'Opcional para Grupos de Sociedades',
            '624' => 'Coordinados',
            '625' => 'Régimen de las Actividades Empresariales con ingresos a través de Plataformas Tecnológicas',
            '626' => 'Régimen Simplificado de Confianza'
        ];
    }
    
    /**
     * Catálogo de Usos de CFDI SAT
     */
    private function getUsosCFDI() {
        return [
            'G01' => 'Adquisición de mercancías',
            'G02' => 'Devoluciones, descuentos o bonificaciones',
            'G03' => 'Gastos en general',
            'I01' => 'Construcciones',
            'I02' => 'Mobiliario y equipo de oficina por inversiones',
            'I03' => 'Equipo de transporte',
            'I04' => 'Equipo de cómputo y accesorios',
            'I08' => 'Otra maquinaria y equipo',
            'D01' => 'Honorarios médicos, dentales y gastos hospitalarios',
            'D02' => 'Gastos médicos por incapacidad o discapacidad',
            'D03' => 'Gastos funerales',
            'D04' => 'Donativos',
            'D05' => 'Intereses reales efectivamente pagados por créditos hipotecarios',
            'D06' => 'Aportaciones voluntarias al SAR',
            'D07' => 'Primas por seguros de gastos médicos',
            'D08' => 'Gastos de transportación escolar obligatoria',
            'D09' => 'Depósitos en cuentas para el ahorro',
            'D10' => 'Pagos por servicios educativos (colegiaturas)',
            'S01' => 'Sin efectos fiscales',
            'CP01' => 'Pagos'
        ];
    }
}
