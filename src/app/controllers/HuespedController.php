<?php
require_once __DIR__ . '/../models/Documento.php';
/**
 * Controlador de Huéspedes
 * Los Cedros
 */

class HuespedController extends Controller {
    private $huespedModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->huespedModel = new Huesped();
    }

    private function estacionamientoDefaultVehiculo() {
        if (function_exists('hotel_general_catalog_parking_rows')) {
            foreach (hotel_general_catalog_parking_rows(null, false) as $parkingRow) {
                $parkingCode = trim((string)($parkingRow['codigo'] ?? ''));
                if ($parkingCode !== '') {
                    return $parkingCode;
                }
            }
        }

        return 'coches';
    }

    private function normalizarEstacionamientoVehiculo($value) {
        $estacionamiento = trim((string)($value ?? ''));
        return $estacionamiento !== '' ? $estacionamiento : $this->estacionamientoDefaultVehiculo();
    }

    private function estacionamientoVehiculoDesdePost($key) {
        return $this->normalizarEstacionamientoVehiculo($this->getPost($key, ''));
    }

    private function politicaCamposRegistro() {
        return function_exists('hotel_guest_field_policy') ? hotel_guest_field_policy() : ['fields' => []];
    }

    private function extrasJson(array $extras) {
        return empty($extras) ? null : json_encode($extras, JSON_UNESCAPED_UNICODE);
    }

    private function extrasHuespedDesdePost(array $policy, array $existing = []) {
        $payload = $this->getPost('extras', []);
        $payload = is_array($payload) ? $payload : [];

        return function_exists('hotel_guest_collect_extra_values')
            ? hotel_guest_collect_extra_values($payload, 'guest', $policy, $existing)
            : $existing;
    }

    private function extrasVehiculoDesdePayload(array $payload, array $policy, array $existing = []) {
        $extrasPayload = is_array($payload['extras'] ?? null) ? $payload['extras'] : $payload;

        return function_exists('hotel_guest_collect_extra_values')
            ? hotel_guest_collect_extra_values($extrasPayload, 'vehicle', $policy, $existing)
            : $existing;
    }

    private function erroresCamposRegistro(array $data, array $extras, string $scope, array $policy, string $contextLabel = '') {
        return function_exists('hotel_guest_field_validation_errors')
            ? hotel_guest_field_validation_errors($data, $extras, $scope, $policy, $contextLabel)
            : [];
    }

    private function hotelIdActual(): int {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function usuarioIdActual(): ?int {
        $usuarioId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        return $usuarioId ? (int)$usuarioId : null;
    }

    private function identificacionVisible(array $policy): bool {
        return function_exists('hotel_guest_field_visible')
            ? hotel_guest_field_visible('identificacion_archivo', $policy)
            : false;
    }

    private function identificacionRequerida(array $policy): bool {
        return function_exists('hotel_guest_field_required')
            ? hotel_guest_field_required('identificacion_archivo', $policy)
            : false;
    }

    private function archivoIdentificacionDesdeRequest(): array {
        $archivo = $_FILES['identificacion_archivo'] ?? [];
        return is_array($archivo) ? $archivo : [];
    }

    private function hayArchivoIdentificacion(array $archivo): bool {
        $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        return $error !== UPLOAD_ERR_NO_FILE;
    }

    private function mensajeUploadIdentificacion(int $error): string {
        $mensajes = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamano maximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamano permitido.',
            UPLOAD_ERR_PARTIAL => 'El archivo se cargo de forma incompleta.',
            UPLOAD_ERR_NO_FILE => 'No se selecciono ningun archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay carpeta temporal disponible para la carga.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el servidor.',
            UPLOAD_ERR_EXTENSION => 'Una extension del servidor bloqueo la carga.',
        ];

        return $mensajes[$error] ?? 'No se pudo cargar el archivo de identificacion.';
    }

    private function validarArchivoIdentificacion(array $archivo): array {
        if (!$this->hayArchivoIdentificacion($archivo)) {
            return [];
        }

        $errores = [];
        $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return [$this->mensajeUploadIdentificacion($error)];
        }

        $tmpName = (string)($archivo['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['El archivo de identificacion no es valido.'];
        }

        $size = (int)($archivo['size'] ?? 0);
        if ($size <= 0) {
            $errores[] = 'El archivo de identificacion esta vacio.';
        }

        if ($size > 10485760) {
            $errores[] = 'El archivo de identificacion no puede superar 10 MB.';
        }

        $nombreOriginal = (string)($archivo['name'] ?? '');
        $extension = strtolower((string)pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if ($extension === '' || !in_array($extension, $extensionesPermitidas, true)) {
            $errores[] = 'La identificacion debe ser PDF, JPG, PNG o WEBP.';
        }

        $mimePermitidos = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        $mime = '';
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mime = (string)finfo_file($finfo, $tmpName);
                finfo_close($finfo);
            }
        } elseif (function_exists('mime_content_type')) {
            $mime = (string)mime_content_type($tmpName);
        }

        if ($mime !== '' && !in_array($mime, $mimePermitidos, true)) {
            $errores[] = 'El archivo de identificacion tiene un tipo no permitido.';
        }

        return $errores;
    }

    private function erroresArchivoIdentificacion(array $policy, ?int $huespedId = null): array {
        if (!$this->identificacionVisible($policy)) {
            return [];
        }

        $archivo = $this->archivoIdentificacionDesdeRequest();
        $hayArchivo = $this->hayArchivoIdentificacion($archivo);
        $tieneDocumento = $huespedId ? $this->huespedTieneDocumentoIdentificacion((int)$huespedId) : false;
        $errores = [];

        if ($this->identificacionRequerida($policy) && !$hayArchivo && !$tieneDocumento) {
            $errores[] = 'La imagen o PDF de identificacion es obligatorio.';
        }

        if ($hayArchivo) {
            $errores = array_merge($errores, $this->validarArchivoIdentificacion($archivo));
        }

        return $errores;
    }

    private function huespedTieneDocumentoIdentificacion(int $huespedId): bool {
        if ($huespedId <= 0) {
            return false;
        }

        try {
            $documentoModel = new Documento();
            $documentos = $documentoModel->documentosPorEntidad($this->hotelIdActual(), 'huesped', $huespedId, 100);
        } catch (Throwable $e) {
            return false;
        }

        foreach ($documentos as $documento) {
            $estado = trim((string)($documento['estado'] ?? ''));
            if ($estado !== '' && $estado !== 'activo') {
                continue;
            }

            $texto = strtolower(trim(implode(' ', [
                (string)($documento['relacion'] ?? ''),
                (string)($documento['titulo'] ?? ''),
                (string)($documento['nombre_original'] ?? ''),
                (string)($documento['descripcion'] ?? ''),
                (string)($documento['tipo_nombre'] ?? ''),
            ])));

            if (
                strpos($texto, 'identificaci') !== false
                || strpos($texto, 'identidad') !== false
                || strpos($texto, 'ine') !== false
                || strpos($texto, 'pasaporte') !== false
                || strpos($texto, 'licencia') !== false
            ) {
                return true;
            }
        }

        return false;
    }

    private function guardarArchivoIdentificacionSiAplica(int $huespedId, string $nombreHuesped, array $policy, bool $usarTransaccionExterna = false): ?int {
        if (!$this->identificacionVisible($policy)) {
            return null;
        }

        $archivo = $this->archivoIdentificacionDesdeRequest();
        if (!$this->hayArchivoIdentificacion($archivo)) {
            return null;
        }

        $documentoModel = new Documento();
        $documentoDatos = [
            'documento_tipo_id' => 0,
            'titulo' => 'Identificacion de huesped - ' . trim($nombreHuesped),
            'descripcion' => 'Documento capturado desde el registro del huesped.',
            'etiquetas' => 'huesped,identificacion,ine',
            'entidad_tipo' => 'huesped',
            'entidad_id' => $huespedId,
            'relacion' => 'identificacion_huesped',
        ];

        if ($usarTransaccionExterna) {
            $documentoDatos['_usar_transaccion_externa'] = true;
        }

        $resultado = $documentoModel->crearDesdeUpload(
            $this->hotelIdActual(),
            $archivo,
            $documentoDatos,
            $this->usuarioIdActual()
        );

        return (int)($resultado['documento_id'] ?? 0) ?: null;
    }

    private function vehiculoTieneDatos(array $vehiculo, array $policy) {
        if (function_exists('hotel_guest_has_vehicle_payload')) {
            return hotel_guest_has_vehicle_payload($vehiculo, $policy);
        }

        foreach (['marca', 'modelo', 'placas', 'color'] as $field) {
            if (trim((string)($vehiculo[$field] ?? '')) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        require_hotel_module('huespedes');
        return true;
    }
    
    /**
     * Listado de huéspedes
     */
    
    
    public function indexAction() {
    // Obtener parámetros de búsqueda y filtros
    $buscar = $this->getQuery('buscar');
    $estado = $this->getQuery('estado');
    $pagina = intval($this->getQuery('page', 1));
    $por_pagina = 20;
    $hotelId = $this->hotelIdActual();
    
    // Construir condiciones
    $conditions = [];
    if ($estado) {
        $conditions['procedencia_estado'] = $estado;
    }
    
    // Obtener huéspedes
    if ($buscar) {
        $huespedes = $this->huespedModel->buscarPorHotel($buscar, $hotelId);
        $total = count($huespedes);
        // Paginar manualmente los resultados de búsqueda
        $offset = ($pagina - 1) * $por_pagina;
        $huespedes = array_slice($huespedes, $offset, $por_pagina);
    } else {
        $resultado = $this->huespedModel->paginatePorHotel($por_pagina, $pagina, $conditions, $hotelId);
        $huespedes = $resultado['data'];
        $total = $resultado['total'];
    }
    
    // Agregar conteo de reservaciones a cada huésped
    foreach ($huespedes as &$huesped) {
        $huesped['total_reservaciones'] = $this->huespedModel->contarReservacionesPorHotel($huesped['id'], $hotelId);
    }
    
    // Calcular paginación
    $total_paginas = ceil($total / $por_pagina);
    
    // Obtener estadísticas usando el nuevo método
    $estadisticas = $this->huespedModel->obtenerEstadisticasPorHotel($hotelId);
    
    View::renderTemplate('huespedes/index', [
        'title' => 'Huéspedes - ' . current_hotel_display_name(),
        'huespedes' => $huespedes,
        'buscar' => $buscar,
        'estado_filtro' => $estado,
        'estados' => Huesped::getEstados(),
        'pagina_actual' => $pagina,
        'total_paginas' => $total_paginas,
        'total_huespedes' => $estadisticas['total_huespedes'] ?? 0,
        'huespedes_con_vehiculo' => $estadisticas['con_vehiculo'] ?? 0,
        'estadisticas' => $estadisticas
    ]);
}

public function debugMovimientosAction() {
    $db = Database::getInstance();
    
    echo "<h3>Debug de Movimientos</h3>";
    
    // 1. Verificar total de movimientos
    $sql = "SELECT COUNT(*) as total FROM movimientos_inventario";
    $stmt = $db->query($sql);
    $total = $stmt->fetch();
    echo "<p><strong>Total de movimientos:</strong> " . $total['total'] . "</p>";
    
    // 2. Verificar tablas relacionadas
    echo "<h4>Verificando tablas relacionadas:</h4>";
    
    // Verificar inventario_productos
    $sql = "SELECT COUNT(*) as total FROM inventario_productos";
    $stmt = $db->query($sql);
    $result = $stmt->fetch();
    echo "<p>inventario_productos: " . $result['total'] . " registros</p>";
    
    // Verificar usuarios
    $sql = "SELECT COUNT(*) as total FROM usuarios";
    $stmt = $db->query($sql);
    $result = $stmt->fetch();
    echo "<p>usuarios: " . $result['total'] . " registros</p>";
    
    // Verificar habitaciones
    $sql = "SELECT COUNT(*) as total FROM habitaciones";
    $stmt = $db->query($sql);
    $result = $stmt->fetch();
    echo "<p>habitaciones: " . $result['total'] . " registros</p>";
    
    // 3. Probar JOIN simple con productos
    echo "<h4>Probando JOIN con productos:</h4>";
    $sql = "SELECT m.id, m.tipo_movimiento, m.cantidad, p.nombre as producto_nombre
            FROM movimientos_inventario m
            LEFT JOIN inventario_productos p ON m.producto_id = p.id
            LIMIT 3";
    
    try {
        $stmt = $db->query($sql);
        if ($stmt) {
            $results = $stmt->fetchAll();
            echo "<pre>";
            print_r($results);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>Error en JOIN con productos</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Excepción: " . $e->getMessage() . "</p>";
    }
    
    // 4. Llamar al método del modelo
    echo "<h4>Probando método getMovimientosDetallados():</h4>";
    try {
        $movimientos = $this->movimientoModel->getMovimientosDetallados(5);
        echo "<pre>";
        print_r($movimientos);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<p style='color:red;'>Error en getMovimientosDetallados: " . $e->getMessage() . "</p>";
    }
    
    die();
}

/**
 * Actualizar vehículo (AJAX)
 */
/**
 * Actualizar vehículo (AJAX)
 */
/**
 * Actualizar vehículo (AJAX)
 */
public function actualizarVehiculoAction() {
    if (!$this->isAjax() || !$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
        return;
    }
    
    $this->validateCSRF();
    
    $vehiculo_id = intval($this->getPost('vehiculo_id'));
    $hotelId = $this->hotelIdActual();
    
    // Validar que el vehículo existe
    $vehiculoModel = new HuespedVehiculo();
    $vehiculoActual = $vehiculoModel->findForHotel($vehiculo_id, $hotelId);
    
    if (!$vehiculoActual) {
        View::renderJSON(['success' => false, 'message' => 'Vehículo no encontrado']);
        return;
    }
    
    $fieldPolicy = $this->politicaCamposRegistro();
    $extrasActuales = function_exists('hotel_guest_decode_extra_json')
        ? hotel_guest_decode_extra_json($vehiculoActual['datos_extra_json'] ?? null)
        : [];
    $extrasVehiculo = $this->extrasVehiculoDesdePayload([
        'extras' => $this->getPost('extras', [])
    ], $fieldPolicy, $extrasActuales);

    // Recopilar datos
    $vehiculoData = [
        'marca' => trim($this->getPost('marca')),
        'modelo' => trim($this->getPost('modelo')),
        'placas' => strtoupper(trim($this->getPost('placas'))),
        'color' => trim($this->getPost('color')),
        'estacionamiento' => $this->estacionamientoVehiculoDesdePost('estacionamiento'),
        'datos_extra_json' => $this->extrasJson($extrasVehiculo)
    ];
    
    // Validaciones configurables
    $erroresVehiculo = $this->erroresCamposRegistro($vehiculoData, $extrasVehiculo, 'vehicle', $fieldPolicy);
    if (!empty($erroresVehiculo)) {
        View::renderJSON(['success' => false, 'message' => implode(' ', $erroresVehiculo)]);
        return;
    }
    
    // Si cambió las placas, verificar que no existan
    if (!empty($vehiculoData['placas']) && $vehiculoData['placas'] !== $vehiculoActual['placas']) {
        if ($vehiculoModel->existenPlacasPorHotel($vehiculoData['placas'], $hotelId, $vehiculo_id)) {
            View::renderJSON(['success' => false, 'message' => 'Las placas ya están registradas en otro vehículo']);
            return;
        }
    }
    
    // Actualizar
    $result = $vehiculoModel->update($vehiculo_id, $vehiculoData);
    
    if ($result) {
        // Obtener el vehículo actualizado
        $vehiculoActualizado = $vehiculoModel->findForHotel($vehiculo_id, $hotelId);
        
        View::renderJSON([
            'success' => true, 
            'message' => 'Vehículo actualizado exitosamente',
            'vehiculo' => $vehiculoActualizado
        ]);
    } else {
        View::renderJSON(['success' => false, 'message' => 'Error al actualizar vehículo']);
    }
}

/**
 * Actualizar huésped (sin vehículos - se gestionan por separado)
 */
public function actualizarAction() {
    if (!$this->isPost()) {
        $this->redirect('huespedes');
    }
    
    $this->validateCSRF();
    
    $id = $this->route_params['id'] ?? 0;
    $hotelId = $this->hotelIdActual();
    
    // Verificar que existe
    $huesped = $this->huespedModel->findForHotel($id, $hotelId);
    if (!$huesped) {
        set_mensaje('Huésped no encontrado', 'error');
        $this->redirect('huespedes');
    }

    $fieldPolicy = $this->politicaCamposRegistro();
    $extrasActuales = function_exists('hotel_guest_decode_extra_json')
        ? hotel_guest_decode_extra_json($huesped['datos_extra_json'] ?? null)
        : [];
    $extrasHuesped = $this->extrasHuespedDesdePost($fieldPolicy, $extrasActuales);
    // Recopilar datos (sin campos de vehículo)
    $data = [
        'hotel_id' => $hotelId,
        'nombre_completo' => trim($this->getPost('nombre_completo')),
        'telefono' => trim($this->getPost('telefono')),
        'email' => trim($this->getPost('email')),
        'procedencia_estado' => $this->getPost('procedencia_estado'),
        'procedencia_ciudad' => trim($this->getPost('procedencia_ciudad')),
        'notas' => trim($this->getPost('notas')),
        'datos_extra_json' => $this->extrasJson($extrasHuesped)
    ];
    
    // Validar datos
    $errores = $this->huespedModel->validar($data, $id);
    $errores = array_merge($errores, $this->erroresCamposRegistro($data, $extrasHuesped, 'guest', $fieldPolicy));
    $errores = array_merge($errores, $this->erroresArchivoIdentificacion($fieldPolicy, (int)$id));
    
    // Verificar teléfono único (excluyendo el actual)
    if (!empty($data['telefono']) && $data['telefono'] !== $huesped['telefono']) {
        if ($this->huespedModel->existeTelefonoPorHotel($data['telefono'], $hotelId, $id)) {
            $errores[] = 'Ya existe otro huésped con ese número de teléfono';
        }
    }
    
    if (!empty($errores)) {
        set_mensaje(implode('<br>', $errores), 'error');
        $this->redirect('huespedes/' . $id . '/edit');
    }
    
    $db = Database::getInstance();
    $db->beginTransaction();

    try {
        if (!$this->huespedModel->update($id, $data)) {
            throw new Exception('Error al actualizar el huesped');
        }
        $documentoIdentificacionId = $this->guardarArchivoIdentificacionSiAplica((int)$id, $data['nombre_completo'], $fieldPolicy, true);
        $db->commit();

        $mensaje = 'Huésped actualizado exitosamente';
        if (!empty($documentoIdentificacionId)) {
            $mensaje .= '. Identificacion vinculada al expediente.';
        }

        set_mensaje($mensaje, 'success');
        $this->redirect('huespedes/' . $id);
    } catch (Throwable $e) {
        $db->safeRollBack();
        error_log('No se pudo guardar cambios del huesped #' . (int)$id . ': ' . $e->getMessage());
        set_mensaje('Error al actualizar el huésped', 'error');
        $this->redirect('huespedes/' . $id . '/edit');
    }
}
    
    /**
     * Ver detalle de huésped
     */
    public function verAction() {
    $id = $this->route_params['id'] ?? 0;
    $hotelId = $this->hotelIdActual();
    
    $huesped = $this->huespedModel->findForHotel($id, $hotelId);
    
    if (!$huesped) {
        set_mensaje('Huésped no encontrado', 'error');
        $this->redirect('huespedes');
    }
    
    // Obtener historial de reservaciones
    $reservaciones = $this->huespedModel->getReservacionesPorHotel($id, $hotelId);
    
    // Obtener vehículos del huésped
    $vehiculos = $this->huespedModel->getVehiculosPorHotel($id, $hotelId);
    $perfilOperativo = $this->huespedModel->perfilOperativoReadOnlyPorHotel($id, $hotelId);

    $documentosEntidad = [];
    try {
        $documentoModel = new Documento();
        if ($documentoModel->entidadExisteEnHotel($hotelId, 'huesped', (int)$id)) {
            $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'huesped', (int)$id, 10);
        }
    } catch (Throwable $e) {
        $documentosEntidad = [];
    }
    
    // Calcular estadísticas
    // Calcular estadísticas del huésped (solo completadas)
$total_reservaciones = 0;
$total_gastado = 0;
$estados_validos = ['checked_in', 'checked_out', 'completada'];
$ultima_visita = null;

foreach ($reservaciones as $reservacion) {
    // Solo contar reservaciones que estén en estados válidos
    if (in_array($reservacion['estado'], $estados_validos)) {
        $total_reservaciones++;
        $total_gastado += $reservacion['precio_total'];
    }
}

if (!empty($perfilOperativo['reservaciones']) && is_array($perfilOperativo['reservaciones'])) {
    $total_reservaciones = (int)($perfilOperativo['reservaciones']['validas'] ?? $total_reservaciones);
    $total_gastado = (float)($perfilOperativo['reservaciones']['total_gastado'] ?? $total_gastado);
    $ultima_visita = $perfilOperativo['reservaciones']['ultima_visita'] ?? $ultima_visita;
}
    
    View::renderTemplate('huespedes/ver', [
        'title' => 'Huésped: ' . $huesped['nombre_completo'] . ' - ' . current_hotel_display_name(),
        'huesped' => $huesped,
        'vehiculos' => $vehiculos,
        'reservaciones' => $reservaciones,
        'perfilOperativo' => $perfilOperativo,
        'total_reservaciones' => $total_reservaciones,
        'total_gastado' => $total_gastado,
        'ultima_visita' => $ultima_visita,
        'documentosEntidad' => $documentosEntidad,
        'guestFieldPolicy' => $this->politicaCamposRegistro(),
        'documentosEntidadContexto' => [
            'tipo' => 'huesped',
            'id' => (int)$id,
            'label' => 'Huesped',
        ],
    ]);
}
    
    /**
     * Mostrar formulario para crear huésped
     */
    public function crearAction() {
        error_log("=== DEBUG CREAR RESERVACIÓN ===");
    error_log("GET params: " . print_r($_GET, true));
    error_log("POST params: " . print_r($_POST, true));
    error_log("Session data: " . print_r($_SESSION, true));
        View::renderTemplate('huespedes/crear', [
            'title' => 'Nuevo Huésped - ' . current_hotel_display_name(),
            'estados' => Huesped::getEstados(),
            'guestFieldPolicy' => $this->politicaCamposRegistro()
        ]);
    }
    
    public function agregarVehiculoAction() {
    if (!$this->isAjax() || !$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
    }
    
    $this->validateCSRF();
    
    $huesped_id = intval($this->getPost('huesped_id'));
    $hotelId = $this->hotelIdActual();
    $huesped = $this->huespedModel->findForHotel($huesped_id, $hotelId);
    if (!$huesped) {
        json_response(['success' => false, 'message' => 'Huesped no encontrado']);
    }

    $fieldPolicy = $this->politicaCamposRegistro();
    $extrasVehiculo = $this->extrasVehiculoDesdePayload([
        'extras' => $this->getPost('extras', [])
    ], $fieldPolicy);
    $vehiculoData = [
        'huesped_id' => $huesped_id,
        'marca' => trim($this->getPost('marca')),
        'modelo' => trim($this->getPost('modelo')),
        'placas' => strtoupper(trim($this->getPost('placas'))),
        'color' => trim($this->getPost('color')),
        'estacionamiento' => $this->estacionamientoVehiculoDesdePost('estacionamiento'),
        'datos_extra_json' => $this->extrasJson($extrasVehiculo)
    ];
    
    $payloadPresencia = $vehiculoData;
    $payloadPresencia['extras'] = $this->getPost('extras', []);
    if (!$this->vehiculoTieneDatos($payloadPresencia, $fieldPolicy)) {
        json_response(['success' => false, 'message' => 'Capture al menos un dato del vehiculo']);
    }

    // Validaciones configurables
    $erroresVehiculo = $this->erroresCamposRegistro($vehiculoData, $extrasVehiculo, 'vehicle', $fieldPolicy);
    if (!empty($erroresVehiculo)) {
        json_response(['success' => false, 'message' => implode(' ', $erroresVehiculo)]);
    }
    
    $vehiculoModel = new HuespedVehiculo();
    
    if (!empty($vehiculoData['placas']) && $vehiculoModel->existenPlacasPorHotel($vehiculoData['placas'], $hotelId)) {
        json_response(['success' => false, 'message' => 'Las placas ya están registradas']);
    }
    
    $result = $vehiculoModel->create($vehiculoData);
    
    if ($result) {
        json_response([
            'success' => true, 
            'message' => 'Vehículo agregado exitosamente',
            'vehiculo' => $result
        ]);
    } else {
        json_response(['success' => false, 'message' => 'Error al agregar vehículo']);
    }
}

/**
 * Eliminar vehículo (soft delete - AJAX)
 */
/**
 * Eliminar vehículo (soft delete - AJAX)
 */
/**
 * Eliminar vehículo (soft delete - AJAX) - CON LOGGING PARA DEBUG
 */
public function eliminarVehiculoAction() {
    // LOG 1: Verificar que la función se está ejecutando
    error_log("=== ELIMINACIÓN VEHÍCULO DEBUG ===");
    error_log("1. Método eliminarVehiculoAction() ejecutándose");
    error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
    error_log("REQUEST_URI: " . $_SERVER['REQUEST_URI']);
    
    // LOG 2: Verificar headers
    error_log("2. Headers importantes:");
    error_log("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'No definido'));
    error_log("X-Requested-With: " . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'No definido'));
    
    // LOG 3: Verificar datos POST
    error_log("3. Datos POST recibidos:");
    error_log("POST data: " . print_r($_POST, true));
    
    // LOG 4: Verificar verificaciones del controlador
    error_log("4. Verificaciones del controlador:");
    error_log("isAjax(): " . ($this->isAjax() ? 'true' : 'false'));
    error_log("isPost(): " . ($this->isPost() ? 'true' : 'false'));
    
    if (!$this->isAjax() || !$this->isPost()) {
        error_log("5. ERROR: Fallo en verificación AJAX o POST");
        error_log("Enviando respuesta de método no permitido");
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
        return;
    }
    
    error_log("5. Verificaciones AJAX y POST pasaron correctamente");
    
    try {
        // LOG 6: CSRF
        error_log("6. Validando CSRF...");
        $this->validateCSRF();
        error_log("CSRF validado correctamente");
        
        // LOG 7: Obtener ID
        $vehiculo_id = intval($this->getPost('vehiculo_id'));
        error_log("7. ID del vehículo: " . $vehiculo_id);
        
        if (!$vehiculo_id) {
            error_log("ERROR: ID de vehículo vacío o inválido");
            View::renderJSON(['success' => false, 'message' => 'ID de vehículo requerido']);
            return;
        }
        
        // LOG 8: Verificar modelo
        error_log("8. Creando instancia del modelo...");
        $vehiculoModel = new HuespedVehiculo();
        error_log("Modelo creado correctamente");
        
        // LOG 9: Buscar vehículo
        error_log("9. Buscando vehículo con ID: " . $vehiculo_id);
        $hotelId = $this->hotelIdActual();
        $vehiculo = $vehiculoModel->findForHotel($vehiculo_id, $hotelId);
        error_log("Resultado de búsqueda: " . ($vehiculo ? 'Encontrado' : 'No encontrado'));
        
        if (!$vehiculo) {
            error_log("ERROR: Vehículo no encontrado en la base de datos");
            View::renderJSON(['success' => false, 'message' => 'Vehículo no encontrado']);
            return;
        }
        
        // LOG 10: Intentar desactivar
        error_log("10. Intentando desactivar vehículo...");
        $result = $vehiculoModel->desactivarParaHotel($vehiculo_id, $hotelId);
        error_log("Resultado de desactivación: " . ($result ? 'Éxito' : 'Fallo'));
        
        if ($result) {
            error_log("11. ÉXITO: Vehículo eliminado correctamente");
            View::renderJSON(['success' => true, 'message' => 'Vehículo eliminado exitosamente']);
        } else {
            error_log("11. ERROR: Fallo al desactivar vehículo");
            View::renderJSON(['success' => false, 'message' => 'Error al eliminar vehículo']);
        }
        
    } catch (Exception $e) {
        error_log("EXCEPCIÓN CAPTURADA: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        View::renderJSON(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    }
    
    error_log("=== FIN DEBUG ELIMINACIÓN VEHÍCULO ===");
}
    
    /**
     * Guardar nuevo huésped
     */
    /**
 * Guardar nuevo huésped
 */
    private function rutaCrearConContexto(): string {
        $params = [];
        foreach (['return_to', 'habitacion_id', 'fecha_entrada', 'fecha_salida', 'hora_llegada'] as $key) {
            $value = $_GET[$key] ?? null;
            if (is_scalar($value) && trim((string)$value) !== '') {
                $params[$key] = trim((string)$value);
            }
        }

        return 'huespedes/create' . (!empty($params) ? '?' . http_build_query($params) : '');
    }

    private function etiquetaCampoRegistro(string $campo): string {
        $labels = [
            'hotel_id' => 'hotel activo',
            'huesped_id' => 'huesped asociado',
            'nombre_completo' => 'nombre completo',
            'telefono' => 'telefono',
            'email' => 'email',
            'procedencia_estado' => 'estado de procedencia',
            'procedencia_ciudad' => 'ciudad de procedencia',
            'notas' => 'notas',
            'marca' => 'marca del vehiculo',
            'modelo' => 'modelo del vehiculo',
            'placas' => 'placas del vehiculo',
            'color' => 'color del vehiculo',
            'estacionamiento' => 'estacionamiento',
            'datos_extra_json' => 'datos adicionales',
            'created_at' => 'fecha de registro',
            'updated_at' => 'fecha de actualizacion',
        ];

        return $labels[$campo] ?? str_replace('_', ' ', $campo);
    }

    private function mensajeErrorRegistro(string $entidad, Throwable $e): string {
        $detalle = trim((string)$e->getMessage());
        $detalleLower = strtolower($detalle);

        if (preg_match("/Data too long for column '([^']+)'/i", $detalle, $match)) {
            return 'El campo ' . $this->etiquetaCampoRegistro($match[1]) . ' es demasiado largo. Reduce el texto e intenta de nuevo.';
        }

        if (preg_match("/Column '([^']+)' cannot be null/i", $detalle, $match)
            || preg_match("/Field '([^']+)' doesn't have a default value/i", $detalle, $match)) {
            return 'Falta completar el campo obligatorio: ' . $this->etiquetaCampoRegistro($match[1]) . '.';
        }

        if (strpos($detalleLower, 'duplicate entry') !== false || (string)$e->getCode() === '23000') {
            if (strpos($detalleLower, 'telefono') !== false || strpos($detalleLower, 'phone') !== false) {
                return 'El telefono ya esta registrado para otro huesped de este hotel.';
            }
            if (strpos($detalleLower, 'placas') !== false || strpos($detalleLower, 'plate') !== false) {
                return 'Las placas del vehiculo ya estan registradas en este hotel.';
            }
            return 'Ya existe un registro con los mismos datos. Revisa telefono, email o placas antes de guardar.';
        }

        if (strpos($detalleLower, 'foreign key constraint fails') !== false) {
            return 'La referencia asociada no es valida. Recarga la pagina y verifica hotel, huesped o vehiculo seleccionado.';
        }

        if (strpos($detalleLower, 'unknown column') !== false) {
            return 'La base de datos no tiene una columna que el formulario intenta guardar. Revisa que el esquema este actualizado.';
        }

        if (strpos($detalleLower, "doesn't exist") !== false || strpos($detalleLower, 'base table or view not found') !== false) {
            return 'La tabla requerida para guardar ' . $entidad . ' no existe o no esta disponible en la base de datos.';
        }

        if (strpos($detalleLower, 'server has gone away') !== false || strpos($detalleLower, 'lost connection') !== false) {
            return 'Se perdio la conexion con la base de datos durante el guardado. Intenta nuevamente.';
        }

        if ($detalle !== '') {
            return 'Detalle tecnico: ' . substr($detalle, 0, 260);
        }

        return 'La base de datos no regreso un detalle del fallo. Revisa el log del servidor con el codigo mostrado.';
    }

    private function crearHuespedConDetalle(array $data): int {
        $pdo = Database::getInstance()->getConnection();
        $now = date('Y-m-d H:i:s');
        $payload = [
            'hotel_id' => (int)($data['hotel_id'] ?? 0),
            'nombre_completo' => (string)($data['nombre_completo'] ?? ''),
            'telefono' => (string)($data['telefono'] ?? ''),
            'email' => (string)($data['email'] ?? ''),
            'procedencia_estado' => (string)($data['procedencia_estado'] ?? ''),
            'procedencia_ciudad' => (string)($data['procedencia_ciudad'] ?? ''),
            'notas' => (string)($data['notas'] ?? ''),
            'datos_extra_json' => $data['datos_extra_json'] ?? null,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        try {
            $fields = array_keys($payload);
            $sql = 'INSERT INTO huespedes (' . implode(', ', $fields) . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($payload));
            $id = (int)$pdo->lastInsertId();
            if ($id <= 0) {
                throw new RuntimeException('La base de datos no devolvio el ID del huesped creado.');
            }
            return $id;
        } catch (Throwable $e) {
            throw new RuntimeException($this->mensajeErrorRegistro('el huesped', $e), 0, $e);
        }
    }

    private function crearVehiculoConDetalle(array $data, int $numeroVehiculo): int {
        $pdo = Database::getInstance()->getConnection();
        $now = date('Y-m-d H:i:s');
        $payload = [
            'huesped_id' => (int)($data['huesped_id'] ?? 0),
            'marca' => (string)($data['marca'] ?? ''),
            'modelo' => (string)($data['modelo'] ?? ''),
            'placas' => (string)($data['placas'] ?? ''),
            'color' => (string)($data['color'] ?? ''),
            'estacionamiento' => (string)($data['estacionamiento'] ?? ''),
            'datos_extra_json' => $data['datos_extra_json'] ?? null,
            'activo' => (int)($data['activo'] ?? 1),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        try {
            $fields = array_keys($payload);
            $sql = 'INSERT INTO huesped_vehiculos (' . implode(', ', $fields) . ') VALUES (' . implode(', ', array_fill(0, count($fields), '?')) . ')';
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($payload));
            $id = (int)$pdo->lastInsertId();
            if ($id <= 0) {
                throw new RuntimeException('La base de datos no devolvio el ID del vehiculo creado.');
            }
            return $id;
        } catch (Throwable $e) {
            throw new RuntimeException('Vehiculo ' . $numeroVehiculo . ': ' . $this->mensajeErrorRegistro('el vehiculo', $e), 0, $e);
        }
    }

    private function erroresCamposHuesped(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (strpos($lower, 'nombre') !== false) {
                $campo = 'nombre_completo';
            } elseif (strpos($lower, 'telefono') !== false || strpos($lower, 'tel') !== false) {
                $campo = 'telefono';
            } elseif (strpos($lower, 'email') !== false || strpos($lower, 'correo') !== false) {
                $campo = 'email';
            } elseif (strpos($lower, 'estado') !== false) {
                $campo = 'procedencia_estado';
            } elseif (strpos($lower, 'ciudad') !== false) {
                $campo = 'procedencia_ciudad';
            } elseif (strpos($lower, 'nota') !== false) {
                $campo = 'notas';
            } elseif (strpos($lower, 'identificacion') !== false || strpos($lower, 'archivo') !== false) {
                $campo = 'identificacion_archivo';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

public function guardarAction() {
    // LOGGING PARA DEBUG
    error_log("=== DEBUG GUARDAR HUÉSPED ===");
    error_log("POST completo: " . print_r($_POST, true));
    
    if (!$this->isPost()) {
        $this->redirect('huespedes');
    }
    
    $this->validateCSRF();
    
    // Obtener return_to directamente
    $return_to = $_GET['return_to'] ?? null;
    $fieldPolicy = $this->politicaCamposRegistro();
    $extrasHuesped = $this->extrasHuespedDesdePost($fieldPolicy);
    $hotelId = $this->hotelIdActual();
    
    // Recopilar datos del huésped
    $data = [
        'hotel_id' => $hotelId,
        'nombre_completo' => trim($this->getPost('nombre_completo')),
        'telefono' => trim($this->getPost('telefono')),
        'email' => trim($this->getPost('email')),
        'procedencia_estado' => $this->getPost('procedencia_estado'),
        'procedencia_ciudad' => trim($this->getPost('procedencia_ciudad')),
        'notas' => trim($this->getPost('notas')),
        'datos_extra_json' => $this->extrasJson($extrasHuesped)
    ];
    
    // Validar datos
    $errores = $this->huespedModel->validar($data);
    $errores = array_merge($errores, $this->erroresCamposRegistro($data, $extrasHuesped, 'guest', $fieldPolicy));
    $errores = array_merge($errores, $this->erroresArchivoIdentificacion($fieldPolicy));
    
    // Verificar si ya existe un huésped con el mismo teléfono
    if (!empty($data['telefono']) && $this->huespedModel->existeTelefonoPorHotel($data['telefono'], $hotelId)) {
        $errores[] = 'Ya existe un huésped registrado con ese número de teléfono';
    }
    
    // Obtener vehículos del formulario
    $fieldErrors = [];
    $vehiculos = $this->getPost('vehiculos', []);
    $vehiculosValidos = [];
    
    // VALIDACION MEJORADA DE VEHICULOS
    if (is_array($vehiculos)) {
        $vehiculoModel = new HuespedVehiculo();

        foreach ($vehiculos as $index => $vehiculo) {
            if (!is_array($vehiculo)) {
                error_log("Vehiculo en indice $index no es array: " . print_r($vehiculo, true));
                continue;
            }

            $placas = isset($vehiculo['placas']) ? strtoupper(trim($vehiculo['placas'])) : '';
            $vehiculoData = [
                'marca' => isset($vehiculo['marca']) ? trim($vehiculo['marca']) : '',
                'modelo' => isset($vehiculo['modelo']) ? trim($vehiculo['modelo']) : '',
                'placas' => $placas,
                'color' => isset($vehiculo['color']) ? trim($vehiculo['color']) : '',
                'estacionamiento' => $this->normalizarEstacionamientoVehiculo($vehiculo['estacionamiento'] ?? ''),
            ];
            $extrasVehiculo = $this->extrasVehiculoDesdePayload($vehiculo, $fieldPolicy);
            $payloadPresencia = $vehiculoData;
            $payloadPresencia['extras'] = is_array($vehiculo['extras'] ?? null) ? $vehiculo['extras'] : [];

            if (!$this->vehiculoTieneDatos($payloadPresencia, $fieldPolicy)) {
                continue;
            }

            if (!empty($placas) && $vehiculoModel->existenPlacasPorHotel($placas, $hotelId)) {
                $errores[] = "Las placas {$placas} ya estan registradas";
                $fieldErrors['vehiculos[' . $index . '][placas]'][] = "Las placas {$placas} ya estan registradas";
                continue;
            }

            $errores = array_merge(
                $errores,
                $this->erroresCamposRegistro($vehiculoData, $extrasVehiculo, 'vehicle', $fieldPolicy, 'Vehiculo ' . ((int)$index + 1))
            );

            $vehiculosValidos[] = array_merge($vehiculoData, [
                'datos_extra_json' => $this->extrasJson($extrasVehiculo),
            ]);
        }
    }
    
    $fieldErrors = array_merge_recursive($this->erroresCamposHuesped($errores), $fieldErrors);

    if (!empty($errores)) {
        set_mensaje(implode(' ', $errores), 'error');
        save_old_input($_POST);
        save_form_errors($fieldErrors);
        $this->redirect($this->rutaCrearConContexto());
    }
    
    // Iniciar transacción
    $db = Database::getInstance();
    $db->beginTransaction();
    
    try {
        // Crear huesped con diagnostico de errores de base de datos
        $huesped_id = $this->crearHuespedConDetalle($data);
        
        // Guardar vehículos
        foreach ($vehiculosValidos as $index => $vehiculo) {
            $vehiculoData = [
                'huesped_id' => $huesped_id,
                'marca' => $vehiculo['marca'],
                'modelo' => $vehiculo['modelo'],
                'placas' => $vehiculo['placas'],
                'color' => $vehiculo['color'],
                'estacionamiento' => $vehiculo['estacionamiento'],
                'datos_extra_json' => $vehiculo['datos_extra_json'],
                'activo' => 1
            ];
            
            $this->crearVehiculoConDetalle($vehiculoData, (int)$index + 1);
        }
        
        // Confirmar transacción
        $documentoIdentificacionId = $this->guardarArchivoIdentificacionSiAplica((int)$huesped_id, $data['nombre_completo'], $fieldPolicy, true);

        $db->commit();
        
        clear_old_input();
        $mensaje = 'Huésped registrado exitosamente';
        if (!empty($documentoIdentificacionId)) {
            $mensaje .= '. Identificacion vinculada al expediente.';
        }

        set_mensaje($mensaje, 'success');
        
        // Redirección basada en return_to
if ($return_to == 'reservacion') {
    $this->redirect('reservaciones/crear?huesped_id=' . $huesped_id);
} else if ($return_to == 'reservacion_rapida') {
    // Recuperar datos de la reservación rápida
    $habitacion_id = $this->getQuery('habitacion_id');
    $fecha_entrada = $this->getQuery('fecha_entrada');
    $fecha_salida = $this->getQuery('fecha_salida');
    $hora_llegada = $this->getQuery('hora_llegada');
    
    $params = [
        'huesped_id' => $huesped_id,
        'from_rapid' => 'true',
        'preseleccion' => 'true'
    ];
    
    if ($habitacion_id) $params['habitacion_id'] = $habitacion_id;
    if ($fecha_entrada) $params['fecha_entrada'] = $fecha_entrada;
    if ($fecha_salida) $params['fecha_salida'] = $fecha_salida;
    if ($hora_llegada) $params['hora_llegada'] = $hora_llegada;
    
    $query_string = http_build_query($params);
    $this->redirect('reservaciones/crear?' . $query_string);
} else {
    $this->redirect('huespedes/' . $huesped_id);
}
        
    } catch (Throwable $e) {
        $db->safeRollBack();
        $codigoError = 'HSP-' . date('YmdHis') . '-' . substr(sha1($e->getMessage()), 0, 6);
        $detalleLog = $e->getPrevious() ? $e->getPrevious()->getMessage() : $e->getMessage();
        error_log('[' . $codigoError . '] Error al registrar huesped: ' . $detalleLog);
        set_mensaje('No se pudo registrar el huesped. ' . $e->getMessage() . ' Codigo: ' . $codigoError, 'error');
        save_old_input($_POST);
        save_form_errors($this->erroresCamposHuesped([$e->getMessage()]));
        $this->redirect($this->rutaCrearConContexto());
    }
}
    
    /**
     * Mostrar formulario para editar huésped
     */
    public function editarAction() {
        $id = $this->route_params['id'] ?? 0;
        $hotelId = $this->hotelIdActual();
        
        $huesped = $this->huespedModel->findForHotel($id, $hotelId);
        
        if (!$huesped) {
            set_mensaje('Huésped no encontrado', 'error');
            $this->redirect('huespedes');
        }
        
        View::renderTemplate('huespedes/editar', [
            'title' => 'Editar Huésped - ' . current_hotel_display_name(),
            'huesped' => $huesped,
            'estados' => Huesped::getEstados(),
            'guestFieldPolicy' => $this->politicaCamposRegistro(),
            'identificacionDocumentoPresente' => $this->huespedTieneDocumentoIdentificacion((int)$id)
        ]);
    }
    
   
    /**
     * Buscar huéspedes (AJAX)
     */
    public function buscarAction() {
        if (!$this->isAjax()) {
            $this->redirect('huespedes');
        }
        
        $termino = $this->getQuery('q', '');
        
        if (strlen($termino) < 2) {
            View::renderJSON([
                'success' => true,
                'data' => []
            ]);
            return;
        }
        
        $huespedes = $this->huespedModel->buscarAutocompletadoPorHotel($termino, $this->hotelIdActual());
        
        View::renderJSON([
            'success' => true,
            'data' => $huespedes
        ]);
    }
}
