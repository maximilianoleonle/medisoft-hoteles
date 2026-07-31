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

    private function estacionamientosCompatiblesVehiculo(): array {
        return function_exists('hotel_general_catalog_parking_code_set')
            ? hotel_general_catalog_parking_code_set()
            : [
                'coches' => true,
                'camionetas' => true,
                'discos' => true,
                'nikkos' => true,
            ];
    }

    private function estacionamientoDefaultVehiculo() {
        $compatibles = $this->estacionamientosCompatiblesVehiculo();

        if (function_exists('hotel_general_catalog_parking_rows')) {
            foreach (hotel_general_catalog_parking_rows(null, false) as $parkingRow) {
                $parkingCode = trim((string)($parkingRow['codigo'] ?? ''));
                if ($parkingCode !== '' && isset($compatibles[$parkingCode])) {
                    return $parkingCode;
                }
            }
        }

        return 'coches';
    }

    private function normalizarEstacionamientoVehiculo($value) {
        $estacionamiento = trim((string)($value ?? ''));
        $compatibles = $this->estacionamientosCompatiblesVehiculo();

        return $estacionamiento !== '' && isset($compatibles[$estacionamiento])
            ? $estacionamiento
            : $this->estacionamientoDefaultVehiculo();
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
            'titulo' => 'Identificación de huésped - ' . trim($nombreHuesped),
            'descripcion' => 'Documento capturado desde el registro del huésped.',
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

    private function listaDocumentalDesdePayload($value): array {
        if (is_array($value)) {
            return array_values($value);
        }

        if ($value === null) {
            return [];
        }

        return [$value];
    }

    private function normalizarTipoDocumentoInicial($tipo): string {
        $tipo = strtolower(trim((string)$tipo));
        return in_array($tipo, ['identificacion', 'comprobante', 'otro'], true) ? $tipo : 'otro';
    }

    private function etiquetaDocumentoInicial(string $tipo): string {
        $etiquetas = [
            'identificacion' => 'INE / identificacion',
            'comprobante' => 'Comprobante',
            'otro' => 'Otro documento',
        ];

        return $etiquetas[$tipo] ?? $etiquetas['otro'];
    }

    private function tituloDocumentoInicial(string $tipo, string $nombreHuesped): string {
        $nombreHuesped = trim($nombreHuesped);
        $sufijo = $nombreHuesped !== '' ? ' - ' . $nombreHuesped : '';

        if ($tipo === 'identificacion') {
            return 'Identificación de huésped' . $sufijo;
        }

        if ($tipo === 'comprobante') {
            return 'Comprobante de huesped' . $sufijo;
        }

        return 'Documento de huesped' . $sufijo;
    }

    private function relacionDocumentoInicial(string $tipo): string {
        if ($tipo === 'identificacion') {
            return 'identificacion_huesped';
        }

        if ($tipo === 'comprobante') {
            return 'comprobante_huesped';
        }

        return 'documento_huesped';
    }

    private function documentosInicialesDesdeRequest(): array {
        $files = $_FILES['documentos_huesped'] ?? [];
        if (!is_array($files)) {
            return [];
        }

        $nombres = $this->listaDocumentalDesdePayload($files['name']['archivo'] ?? null);
        $types = $this->listaDocumentalDesdePayload($files['type']['archivo'] ?? null);
        $tmpNames = $this->listaDocumentalDesdePayload($files['tmp_name']['archivo'] ?? null);
        $errores = $this->listaDocumentalDesdePayload($files['error']['archivo'] ?? null);
        $sizes = $this->listaDocumentalDesdePayload($files['size']['archivo'] ?? null);

        $post = is_array($_POST['documentos_huesped'] ?? null) ? $_POST['documentos_huesped'] : [];
        $tipos = $this->listaDocumentalDesdePayload($post['tipo'] ?? null);
        $titulos = $this->listaDocumentalDesdePayload($post['titulo'] ?? null);
        $descripciones = $this->listaDocumentalDesdePayload($post['descripcion'] ?? null);

        $documentos = [];
        foreach ($nombres as $index => $nombre) {
            $error = (int)($errores[$index] ?? UPLOAD_ERR_NO_FILE);
            if ($error === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $tipo = $this->normalizarTipoDocumentoInicial($tipos[$index] ?? 'otro');
            $documentos[] = [
                'tipo' => $tipo,
                'etiqueta' => $this->etiquetaDocumentoInicial($tipo),
                'titulo' => trim((string)($titulos[$index] ?? '')),
                'descripcion' => trim((string)($descripciones[$index] ?? '')),
                'archivo' => [
                    'name' => (string)$nombre,
                    'type' => (string)($types[$index] ?? ''),
                    'tmp_name' => (string)($tmpNames[$index] ?? ''),
                    'error' => $error,
                    'size' => (int)($sizes[$index] ?? 0),
                ],
            ];
        }

        return $documentos;
    }

    private function mensajeUploadDocumentoInicial(int $error): string {
        $mensajes = [
            UPLOAD_ERR_INI_SIZE => 'El archivo excede el tamano maximo permitido por el servidor.',
            UPLOAD_ERR_FORM_SIZE => 'El archivo excede el tamano permitido.',
            UPLOAD_ERR_PARTIAL => 'El archivo se cargo de forma incompleta.',
            UPLOAD_ERR_NO_FILE => 'No se selecciono ningun archivo.',
            UPLOAD_ERR_NO_TMP_DIR => 'No hay carpeta temporal disponible para la carga.',
            UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en el servidor.',
            UPLOAD_ERR_EXTENSION => 'Una extension del servidor bloqueo la carga.',
        ];

        return $mensajes[$error] ?? 'No se pudo cargar el documento.';
    }

    private function validarArchivoDocumentoInicial(array $archivo): array {
        $error = (int)($archivo['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return [$this->mensajeUploadDocumentoInicial($error)];
        }

        $tmpName = (string)($archivo['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['El archivo no es valido.'];
        }

        $errores = [];
        $size = (int)($archivo['size'] ?? 0);
        if ($size <= 0) {
            $errores[] = 'El archivo esta vacio.';
        }

        if ($size > 10485760) {
            $errores[] = 'El archivo no puede superar 10 MB.';
        }

        $nombreOriginal = (string)($archivo['name'] ?? '');
        $extension = strtolower((string)pathinfo($nombreOriginal, PATHINFO_EXTENSION));
        $extensionesPermitidas = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if ($extension === '' || !in_array($extension, $extensionesPermitidas, true)) {
            $errores[] = 'El documento debe ser PDF, JPG, PNG o WEBP.';
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
            $errores[] = 'El documento tiene un tipo no permitido.';
        }

        return $errores;
    }

    private function erroresDocumentosIniciales(array $documentos): array {
        $errores = [];
        foreach ($documentos as $documento) {
            $etiqueta = (string)($documento['etiqueta'] ?? 'Documento');
            foreach ($this->validarArchivoDocumentoInicial($documento['archivo'] ?? []) as $error) {
                $errores[] = $etiqueta . ': ' . $error;
            }
        }

        return $errores;
    }

    private function guardarDocumentosInicialesSiAplica(int $huespedId, string $nombreHuesped, array $documentos, bool $usarTransaccionExterna = false): array {
        if (empty($documentos)) {
            return [];
        }

        $documentoModel = new Documento();
        $creados = [];

        foreach ($documentos as $documento) {
            $tipo = $this->normalizarTipoDocumentoInicial($documento['tipo'] ?? 'otro');
            $titulo = trim((string)($documento['titulo'] ?? ''));
            $descripcion = trim((string)($documento['descripcion'] ?? ''));

            $documentoDatos = [
                'documento_tipo_id' => 0,
                'titulo' => $titulo !== '' ? $titulo : $this->tituloDocumentoInicial($tipo, $nombreHuesped),
                'descripcion' => $descripcion !== '' ? $descripcion : 'Documento cargado desde el alta inicial del huesped.',
                'etiquetas' => 'huesped,alta_inicial,' . $tipo,
                'entidad_tipo' => 'huesped',
                'entidad_id' => $huespedId,
                'relacion' => $this->relacionDocumentoInicial($tipo),
            ];

            if ($usarTransaccionExterna) {
                $documentoDatos['_usar_transaccion_externa'] = true;
            }

            $creados[] = $documentoModel->crearDesdeUpload(
                $this->hotelIdActual(),
                $documento['archivo'] ?? [],
                $documentoDatos,
                $this->usuarioIdActual()
            );
        }

        return $creados;
    }

    private function eliminarArchivosDocumentosCreados(array $documentos): void {
        foreach ($documentos as $documento) {
            $path = (string)($documento['_absolute_path'] ?? '');
            if ($path !== '' && is_file($path)) {
                @unlink($path);
            }
        }
    }

    private function vehiculoTieneDatos(array $vehiculo, array $policy) {
        // El estacionamiento tiene valor por defecto; por si solo no significa
        // que el usuario quiera registrar un vehiculo.
        $vehiculo['estacionamiento'] = '';

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

    private function generarPlacasInternasVehiculo(int $hotelId): string {
        $vehiculoModel = new HuespedVehiculo();

        for ($i = 0; $i < 6; $i++) {
            try {
                $token = 'SINPLACA' . strtoupper(bin2hex(random_bytes(6)));
            } catch (Throwable $e) {
                $token = 'SINPLACA' . strtoupper(substr(sha1(uniqid('', true) . $i), 0, 12));
            }

            if (!$vehiculoModel->existenPlacasPorHotel($token, $hotelId)) {
                return $token;
            }
        }

        return 'SINPLACA' . strtoupper(substr(sha1(uniqid('', true)), 0, 12));
    }

    private function placasVehiculoParaGuardar(string $placas, array $policy, int $hotelId, ?string $placasActuales = null): string {
        $placas = strtoupper(trim($placas));

        if ($placas !== '') {
            return $placas;
        }

        $placasActuales = strtoupper(trim((string)$placasActuales));
        if ($placasActuales !== '') {
            return $placasActuales;
        }

        return $this->generarPlacasInternasVehiculo($hotelId);
    }

    /**
     * Verificar autenticación antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        require_hotel_module('huespedes');

        // Permiso base del modulo (PII de huespedes): mismo contrato que el
        // menu (config/navegacion.php -> 'huespedes.view'). Las acciones de
        // escritura exigen ademas huespedes.create / huespedes.edit.
        require_permission_or_403('huespedes.view');

        return true;
    }
    
    /**
     * Listado de huéspedes
     */
    
    
    public function indexAction() {
    // Obtener parámetros de búsqueda y filtros
    $buscar = $this->getQuery('buscar');
    $pagina = intval($this->getQuery('page', 1));
    $por_pagina = 20;
    $hotelId = $this->hotelIdActual();
    
    // Construir condiciones
    $conditions = [];
    
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
    
    // Conteo de reservaciones en UNA query para toda la pagina (antes: N+1)
    $totalesReservaciones = $this->huespedModel->contarReservacionesPorHotelLote(
        array_column($huespedes, 'id'),
        $hotelId
    );
    foreach ($huespedes as &$huesped) {
        $huesped['total_reservaciones'] = $totalesReservaciones[(int)$huesped['id']] ?? 0;
    }
    unset($huesped);
    
    // Calcular paginación
    $total_paginas = ceil($total / $por_pagina);
    
    // Obtener estadísticas usando el nuevo método
    $estadisticas = $this->huespedModel->obtenerEstadisticasPorHotel($hotelId);
    
    View::renderTemplate('huespedes/index', [
        'title' => 'Huéspedes - ' . current_hotel_display_name(),
        'huespedes' => $huespedes,
        'buscar' => $buscar,
        'pagina_actual' => $pagina,
        'total_paginas' => $total_paginas,
        'total_huespedes' => $estadisticas['total_huespedes'] ?? 0,
        'huespedes_con_vehiculo' => $estadisticas['con_vehiculo'] ?? 0,
        'estadisticas' => $estadisticas
    ]);
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
    require_hotel_module('vehiculos');
    if (!$this->isAjax() || !$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
        return;
    }

    require_permission_or_403('huespedes.edit');
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
    $placasCapturadas = strtoupper(trim($this->getPost('placas')));

    // Recopilar datos
    $vehiculoData = [
        'hotel_id' => $hotelId,
        'marca' => trim($this->getPost('marca')),
        'modelo' => trim($this->getPost('modelo')),
        'placas' => $placasCapturadas,
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
    
    $vehiculoData['placas'] = $this->placasVehiculoParaGuardar(
        $placasCapturadas,
        $fieldPolicy,
        $hotelId,
        $vehiculoActual['placas'] ?? null
    );

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
            'vehiculo' => HuespedVehiculo::paraMostrar($vehiculoActualizado)
        ]);
    } else {
        View::renderJSON(['success' => false, 'message' => 'Error al actualizar vehículo']);
    }
}

/**
 * Actualizar huésped (sin vehículos - se gestionan por separado)
 */
/**
 * Sanitiza el descuento por huesped desde el POST.
 * Devuelve [descuento_tipo, descuento_valor] o NULL/NULL si no hay descuento valido.
 */
private function descuentoHuespedDesdePost() {
    // Sin el bloque 'descuentos' no se capturan ni modifican descuentos;
    // devolver [] preserva el valor existente del huesped al editar.
    if (function_exists('current_hotel_has_module') && !current_hotel_has_module('descuentos')) {
        return [];
    }

    $tipo = trim((string)$this->getPost('descuento_tipo', ''));
    $valorRaw = str_replace(',', '', (string)$this->getPost('descuento_valor', ''));
    $valor = is_numeric($valorRaw) ? (float)$valorRaw : 0;

    if (($tipo !== 'porcentaje' && $tipo !== 'monto') || $valor <= 0) {
        return ['descuento_tipo' => null, 'descuento_valor' => null];
    }
    if ($tipo === 'porcentaje' && $valor > 100) {
        $valor = 100;
    }

    return ['descuento_tipo' => $tipo, 'descuento_valor' => $valor];
}

public function actualizarAction() {
    if (!$this->isPost()) {
        $this->redirect('huespedes');
    }

    require_permission_or_403('huespedes.edit');
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
    $data = array_merge($data, $this->descuentoHuespedDesdePost());

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
        save_old_input($_POST);
        save_form_errors($this->erroresCamposHuesped($errores));
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
            $mensaje .= '. Identificación vinculada al expediente.';
        }

        clear_old_input();
        set_mensaje($mensaje, 'success');
        $this->redirect('huespedes/' . $id);
    } catch (Throwable $e) {
        $db->safeRollBack();
        error_log('No se pudo guardar cambios del huesped #' . (int)$id . ': ' . $e->getMessage());
        save_old_input($_POST);
        save_form_errors($this->erroresCamposHuesped([$e->getMessage()]));
        set_mensaje('Error al actualizar el huésped', 'error');
        $this->redirect('huespedes/' . $id . '/edit');
    }
}
    
    /**
     * Actualización inline de un solo campo del huésped (AJAX).
     * Pensado para editar nombre / teléfono / email desde otras vistas
     * (p.ej. el detalle de reservación) sin pasar por el formulario completo.
     */
    public function actualizarInlineAction() {
        if (!$this->isAjax() || !$this->isPost()) {
            View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
            return;
        }

        require_permission_or_403('huespedes.edit');
        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        $huesped = $this->huespedModel->findForHotel($id, $hotelId);
        if (!$huesped) {
            View::renderJSON(['success' => false, 'message' => 'Huésped no encontrado']);
            return;
        }

        $camposPermitidos = ['nombre_completo', 'telefono', 'email'];
        $campo = (string)$this->getPost('campo');
        if (!in_array($campo, $camposPermitidos, true)) {
            View::renderJSON(['success' => false, 'message' => 'Campo no editable']);
            return;
        }

        $valor = trim((string)$this->getPost('valor'));

        // Validación por campo (espejo de las reglas de Huesped::validar,
        // aislada para no bloquear por otros datos previos del registro).
        $error = '';
        if ($campo === 'nombre_completo') {
            $len = function_exists('mb_strlen') ? mb_strlen($valor) : strlen($valor);
            if ($valor === '') {
                $error = 'El nombre completo es obligatorio';
            } elseif ($len < 3) {
                $error = 'El nombre debe tener al menos 3 caracteres';
            } elseif ($len > 200) {
                $error = 'El nombre no puede exceder 200 caracteres';
            }
        } elseif ($campo === 'telefono') {
            if ($valor !== '') {
                $telefonoLimpio = preg_replace('/[\s\-\(\)]/', '', $valor);
                if (!preg_match('/^[0-9]{10,15}$/', $telefonoLimpio)) {
                    $error = 'El teléfono debe contener entre 10 y 15 dígitos';
                }
            }
        } elseif ($campo === 'email') {
            if ($valor !== '' && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                $error = 'El email no es válido';
            }
        }

        if ($error !== '') {
            View::renderJSON(['success' => false, 'message' => $error]);
            return;
        }

        // Teléfono único por hotel (igual que en actualizarAction).
        if ($campo === 'telefono' && $valor !== '' && $valor !== (string)($huesped['telefono'] ?? '')) {
            if ($this->huespedModel->existeTelefonoPorHotel($valor, $hotelId, $id)) {
                View::renderJSON(['success' => false, 'message' => 'Ya existe otro huésped con ese número de teléfono']);
                return;
            }
        }

        if (!$this->huespedModel->update($id, [$campo => $valor])) {
            View::renderJSON(['success' => false, 'message' => 'No se pudo guardar el cambio']);
            return;
        }

        View::renderJSON([
            'success' => true,
            'message' => 'Cambio guardado',
            'campo'   => $campo,
            'valor'   => $valor
        ]);
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
    
    // Obtener vehículos del huésped (bloque 'vehiculos': sin contratar no se
    // consultan placas, que es PII que la ficha ya no pinta — el panel de
    // vehículos de la vista lleva su propio gate de módulo).
    $vehiculos = (!function_exists('hotel_parking_visible') || hotel_parking_visible($hotelId))
        ? $this->huespedModel->getVehiculosPorHotel($id, $hotelId)
        : [];
    $perfilOperativo = $this->huespedModel->perfilOperativoReadOnlyPorHotel($id, $hotelId);

    $documentosEntidad = [];
    try {
        if (puede_ver_documentos_vinculados()) {
            $documentoModel = new Documento();
            if ($documentoModel->entidadExisteEnHotel($hotelId, 'huesped', (int)$id)) {
                $documentosEntidad = $documentoModel->documentosPorEntidad($hotelId, 'huesped', (int)$id, 10);
            }
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
        require_permission_or_403('huespedes.create');

        View::renderTemplate('huespedes/crear', [
            'title' => 'Nuevo Huésped - ' . current_hotel_display_name(),
            'estados' => Huesped::getEstados(),
            'guestFieldPolicy' => $this->politicaCamposRegistro()
        ]);
    }
    
    public function agregarVehiculoAction() {
    require_hotel_module('vehiculos');
    if (!$this->isAjax() || !$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
    }

    require_permission_or_403('huespedes.edit');
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
    $placasCapturadas = strtoupper(trim($this->getPost('placas')));
    $vehiculoData = [
        'hotel_id' => $hotelId,
        'huesped_id' => $huesped_id,
        'marca' => trim($this->getPost('marca')),
        'modelo' => trim($this->getPost('modelo')),
        'placas' => $placasCapturadas,
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
    
    $vehiculoData['placas'] = $this->placasVehiculoParaGuardar($placasCapturadas, $fieldPolicy, $hotelId);

    $result = $vehiculoModel->create($vehiculoData);
    
    if ($result) {
        json_response([
            'success' => true,
            'message' => 'Vehículo agregado exitosamente',
            'vehiculo' => HuespedVehiculo::paraMostrar($result)
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
public function eliminarVehiculoAction() {
    require_hotel_module('vehiculos');
    if (!$this->isAjax() || !$this->isPost()) {
        View::renderJSON(['success' => false, 'message' => 'Método no permitido']);
        return;
    }

    require_permission_or_403('huespedes.edit');

    try {
        $this->validateCSRF();
        $vehiculo_id = intval($this->getPost('vehiculo_id'));

        if (!$vehiculo_id) {
            View::renderJSON(['success' => false, 'message' => 'ID de vehículo requerido']);
            return;
        }

        $vehiculoModel = new HuespedVehiculo();
        $hotelId = $this->hotelIdActual();
        $vehiculo = $vehiculoModel->findForHotel($vehiculo_id, $hotelId);

        if (!$vehiculo) {
            View::renderJSON(['success' => false, 'message' => 'Vehículo no encontrado']);
            return;
        }

        $result = $vehiculoModel->desactivarParaHotel($vehiculo_id, $hotelId);

        if ($result) {
            View::renderJSON(['success' => true, 'message' => 'Vehículo eliminado exitosamente']);
        } else {
            View::renderJSON(['success' => false, 'message' => 'Error al eliminar vehículo']);
        }

    } catch (Exception $e) {
        error_log('eliminarVehiculoAction: ' . $e->getMessage());
        View::renderJSON(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()]);
    }
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
            'hotel_id' => (int)($data['hotel_id'] ?? 0),
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

            $mensajeAscii = function_exists('iconv')
                ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $mensaje)
                : false;
            $normalizado = strtolower(is_string($mensajeAscii) && $mensajeAscii !== '' ? $mensajeAscii : $mensaje);
            $campo = null;
            $indiceVehiculo = null;

            if (preg_match('/veh.?culo\s+(\d+)/', $normalizado, $match)) {
                $indiceVehiculo = max(0, (int)$match[1] - 1);
            }

            if ($indiceVehiculo !== null && preg_match('/(^|[^a-z])placas?([^a-z]|$)/', $normalizado)) {
                $campo = 'vehiculos[' . $indiceVehiculo . '][placas]';
            } elseif (strpos($normalizado, 'nombre') !== false) {
                $campo = 'nombre_completo';
            } elseif (preg_match('/(^|[^a-z])(telefono|celular|phone|tel)([^a-z]|$)/', $normalizado)) {
                $campo = 'telefono';
            } elseif (strpos($normalizado, 'email') !== false || strpos($normalizado, 'correo') !== false) {
                $campo = 'email';
            } elseif (strpos($normalizado, 'estado') !== false) {
                $campo = 'procedencia_estado';
            } elseif (strpos($normalizado, 'ciudad') !== false) {
                $campo = 'procedencia_ciudad';
            } elseif (strpos($normalizado, 'nota') !== false) {
                $campo = 'notas';
            } elseif (strpos($normalizado, 'identificacion') !== false || strpos($normalizado, 'archivo') !== false) {
                $campo = 'identificacion_archivo';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

public function guardarAction() {
    if (!$this->isPost()) {
        $this->redirect('huespedes');
    }

    require_permission_or_403('huespedes.create');
    $this->validateCSRF();
    
    // Obtener return_to directamente
    $return_to = $_GET['return_to'] ?? null;
    $fieldPolicy = $this->politicaCamposRegistro();
    $extrasHuesped = $this->extrasHuespedDesdePost($fieldPolicy);
    $documentosIniciales = $this->documentosInicialesDesdeRequest();
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
    $data = array_merge($data, $this->descuentoHuespedDesdePost());

    // Validar datos
    $errores = $this->huespedModel->validar($data);
    $errores = array_merge($errores, $this->erroresCamposRegistro($data, $extrasHuesped, 'guest', $fieldPolicy));
    $errores = array_merge($errores, $this->erroresArchivoIdentificacion($fieldPolicy));
    $errores = array_merge($errores, $this->erroresDocumentosIniciales($documentosIniciales));
    
    // Verificar si ya existe un huésped con el mismo teléfono
    if (!empty($data['telefono']) && $this->huespedModel->existeTelefonoPorHotel($data['telefono'], $hotelId)) {
        $errores[] = 'Ya existe un huésped registrado con ese número de teléfono';
    }
    
    // Obtener vehículos del formulario. CUARTO punto de escritura del bloque
    // 'vehiculos' y el único que estaba sin candado: los otros 3 (agregar,
    // actualizar, eliminar) son AJAX y ya cortan con require_hotel_module. Aquí
    // el alta es del paquete base, así que el campo se IGNORA en silencio en vez
    // de tronar — sin el bloque la vista no renderiza inputs vehiculos[], de
    // modo que solo puede llegar por un POST fabricado.
    $fieldErrors = [];
    $vehiculos = (!function_exists('hotel_parking_visible') || hotel_parking_visible($hotelId))
        ? $this->getPost('vehiculos', [])
        : [];
    $vehiculosValidos = [];
    
    // VALIDACION MEJORADA DE VEHICULOS
    if (is_array($vehiculos)) {
        $vehiculoModel = new HuespedVehiculo();

        foreach ($vehiculos as $index => $vehiculo) {
            if (!is_array($vehiculo)) {
                error_log("Vehiculo en indice $index no es array: " . print_r($vehiculo, true));
                continue;
            }

            $placasCapturadas = isset($vehiculo['placas']) ? strtoupper(trim($vehiculo['placas'])) : '';
            $vehiculoData = [
                'marca' => isset($vehiculo['marca']) ? trim($vehiculo['marca']) : '',
                'modelo' => isset($vehiculo['modelo']) ? trim($vehiculo['modelo']) : '',
                'placas' => $placasCapturadas,
                'color' => isset($vehiculo['color']) ? trim($vehiculo['color']) : '',
                'estacionamiento' => $this->normalizarEstacionamientoVehiculo($vehiculo['estacionamiento'] ?? ''),
            ];
            $extrasVehiculo = $this->extrasVehiculoDesdePayload($vehiculo, $fieldPolicy);
            $payloadPresencia = $vehiculoData;
            $payloadPresencia['extras'] = is_array($vehiculo['extras'] ?? null) ? $vehiculo['extras'] : [];

            if (!$this->vehiculoTieneDatos($payloadPresencia, $fieldPolicy)) {
                continue;
            }

            if (!empty($placasCapturadas) && $vehiculoModel->existenPlacasPorHotel($placasCapturadas, $hotelId)) {
                $errores[] = "Las placas {$placasCapturadas} ya estan registradas";
                $fieldErrors['vehiculos[' . $index . '][placas]'][] = "Las placas {$placasCapturadas} ya estan registradas";
                continue;
            }

            $errores = array_merge(
                $errores,
                $this->erroresCamposRegistro($vehiculoData, $extrasVehiculo, 'vehicle', $fieldPolicy, 'Vehiculo ' . ((int)$index + 1))
            );

            $vehiculoData['placas'] = $this->placasVehiculoParaGuardar($placasCapturadas, $fieldPolicy, $hotelId);

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
    $documentosInicialesCreados = [];
    $transaccionConfirmada = false;
    
    try {
        // Crear huesped con diagnostico de errores de base de datos
        $huesped_id = $this->crearHuespedConDetalle($data);
        
        // Guardar vehículos
        foreach ($vehiculosValidos as $index => $vehiculo) {
            $vehiculoData = [
                'hotel_id' => $hotelId,
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
        $documentosInicialesCreados = $this->guardarDocumentosInicialesSiAplica((int)$huesped_id, $data['nombre_completo'], $documentosIniciales, true);
        $documentoIdentificacionId = $this->guardarArchivoIdentificacionSiAplica((int)$huesped_id, $data['nombre_completo'], $fieldPolicy, true);

        $db->commit();
        $transaccionConfirmada = true;
        
        clear_old_input();
        $mensaje = 'Huésped registrado exitosamente';
        $documentosVinculados = count($documentosInicialesCreados) + (!empty($documentoIdentificacionId) ? 1 : 0);
        if ($documentosVinculados > 0) {
            $mensaje .= '. ' . $documentosVinculados . ' documento' . ($documentosVinculados === 1 ? '' : 's') . ' vinculado' . ($documentosVinculados === 1 ? '' : 's') . ' al expediente.';
        }

        set_mensaje($mensaje, 'success');
        
        // Redirección basada en return_to
if ($return_to == 'reservacion') {
    $params = ['huesped_id' => $huesped_id];
    foreach (['habitacion_id', 'fecha_entrada', 'fecha_salida', 'hora_llegada', 'preseleccion'] as $key) {
        $value = $this->getQuery($key);
        if ($value !== null && trim((string)$value) !== '') {
            $params[$key] = trim((string)$value);
        }
    }
    $this->redirect('reservaciones/crear?' . http_build_query($params));
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
        if (!$transaccionConfirmada) {
            $db->safeRollBack();
            $this->eliminarArchivosDocumentosCreados($documentosInicialesCreados);
        }
        $codigoError = 'HSP-' . date('YmdHis') . '-' . substr(sha1($e->getMessage()), 0, 6);
        $detalleLog = $e->getPrevious() ? $e->getPrevious()->getMessage() : $e->getMessage();
        error_log('[' . $codigoError . '] Error al registrar huesped: ' . $detalleLog);
        // El detalle tecnico va al log con el codigo; al usuario solo el mensaje
        // de negocio (si lo hay) y el codigo corto para reportar a soporte.
        set_mensaje_error_op($e, 'registrar al huésped (código ' . $codigoError . ')');
        save_old_input($_POST);
        save_form_errors($this->erroresCamposHuesped([$e->getMessage()]));
        $this->redirect($this->rutaCrearConContexto());
    }
}
    
    /**
     * Mostrar formulario para editar huésped
     */
    public function editarAction() {
        require_permission_or_403('huespedes.edit');

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
