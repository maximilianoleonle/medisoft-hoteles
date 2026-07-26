<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/Documento.php';
require_once __DIR__ . '/../services/AuditService.php';

class DocumentoController extends Controller
{
    private $documentoModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->documentoModel = new Documento();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        $this->requireModuloRelacionado();

        // Permiso base del centro documental (PII/expedientes): mismo contrato
        // que el menu (config/navegacion.php -> 'documentos.view'). Las
        // escrituras (subida, metadata y estado) exigen ademas 'documentos.all'.
        require_permission_or_403('documentos.view');

        return true;
    }

    public function indexAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = $this->filtros();
        $tablaDisponible = $this->documentoModel->tablasDisponibles();

        View::renderTemplate('documentos/index', [
            'title' => 'Centro documental - ' . current_hotel_display_name(),
            'documentos' => $tablaDisponible ? $this->documentoModel->listarPorHotel($hotelId, $filtros, 100) : [],
            'resumen' => $tablaDisponible ? $this->documentoModel->resumenPorHotel($hotelId) : $this->resumenVacio(),
            'tipos' => $tablaDisponible ? $this->documentoModel->tiposActivosPorHotel($hotelId) : [],
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
            'contextoEntidad' => null,
        ]);
    }

    public function verAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $documento = $this->documentoModel->buscarPorIdHotel($id, $hotelId);

        if (!$documento) {
            set_mensaje('Documento no encontrado para el hotel actual.', 'error');
            $this->redirect('documentos');
            return;
        }

        View::renderTemplate('documentos/ver', [
            'title' => 'Documento #' . $id . ' - ' . current_hotel_display_name(),
            'documento' => $documento,
            'entidades' => $this->documentoModel->entidadesPorDocumento($id, $hotelId),
        ]);
    }

    public function descargarAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $documento = $this->documentoModel->buscarDescargablePorIdHotel($id, $hotelId);

        if (!$documento) {
            $this->auditarDescargaBloqueada($hotelId, $id, 'documento_no_disponible');
            set_mensaje('Documento no disponible para descarga en el hotel actual.', 'error');
            $this->redirect('documentos');
            return;
        }

        try {
            $ruta = $this->documentoModel->resolverRutaPrivada($documento);
        } catch (Throwable $e) {
            $this->auditarDescargaBloqueada($hotelId, $id, 'archivo_privado_no_disponible');
            set_mensaje('El archivo privado no esta disponible para descarga.', 'error');
            $this->redirect('documentos/' . $id);
            return;
        }

        $this->servirArchivoPrivado($documento, $ruta, $this->getQuery('preview', '') === '1');
    }

    public function editarAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $documento = $this->documentoModel->buscarPorIdHotel($id, $hotelId);

        if (!$documento) {
            set_mensaje('Documento no encontrado para el hotel actual.', 'error');
            $this->redirect('documentos');
            return;
        }

        if (($documento['estado'] ?? '') === 'eliminado') {
            set_mensaje('No se puede editar la información de documentos eliminados.', 'error');
            $this->redirect('documentos/' . $id);
            return;
        }

        View::renderTemplate('documentos/editar', [
            'title' => 'Editar información del documento #' . $id . ' - ' . current_hotel_display_name(),
            'documento' => $documento,
            'tipos' => $this->documentoModel->tiposActivosPorHotel($hotelId),
        ]);
    }

    public function actualizarAction(): void
    {
        if (!$this->isPost()) {
            $this->redirect('documentos');
            return;
        }

        // Editar metadata: control total del modulo (documentos.view solo lee;
        // no existe clave documentos.edit en el catalogo de permisos).
        require_permission_or_403('documentos.all');

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $resultado = $this->documentoModel->actualizarMetadata(
                $id,
                $hotelId,
                $this->datosMetadata(),
                $this->usuarioIdActual()
            );

            if (!($resultado['changed'] ?? false)) {
                clear_old_input();
                set_mensaje('No cambiaste nada; el documento quedó igual.', 'info');
            } else {
                clear_old_input();
                set_mensaje('Información del documento actualizada.', 'success');
            }

            $this->redirect('documentos/' . $id);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'actualizar la información del documento');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposDocumento([$e->getMessage()]));
            $this->redirect($id > 0 ? 'documentos/' . $id . '/editar' : 'documentos');
        }
    }

    public function archivarAction(): void
    {
        $this->cambiarEstadoAction('archivado', 'Documento archivado correctamente.');
    }

    public function restaurarAction(): void
    {
        $this->cambiarEstadoAction('activo', 'Documento restaurado correctamente.');
    }

    public function eliminarAction(): void
    {
        $this->cambiarEstadoAction('eliminado', 'Documento dado de baja logicamente correctamente.');
    }

    public function subirAction(): void
    {
        // Cargar un archivo al expediente es escritura, no consulta: mismo
        // contrato que actualizarAction() y cambiarEstadoAction(). Antes solo
        // heredaba 'documentos.view' del before() y un rol de solo lectura
        // (p.ej. Recepcion) podia subir documentos al hotel.
        require_permission_or_403('documentos.all');

        $hotelId = $this->hotelIdActual();
        $contextoEntidad = $this->contextoEntidadDesdeRequest($hotelId, true);

        if ($contextoEntidad === false) {
            set_mensaje('Entidad documental no encontrada para el hotel actual.', 'error');
            $this->redirect('documentos');
            return;
        }

        View::renderTemplate('documentos/subir', [
            'title' => 'Subir documento - ' . current_hotel_display_name(),
            'tipos' => $this->documentoModel->tiposActivosPorHotel($hotelId),
            'contextoEntidad' => $contextoEntidad,
        ]);
    }

    public function guardarAction(): void
    {
        // Gate propio: subirAction() solo pinta el formulario, el POST entra
        // aqui directo y debe exigir lo mismo.
        require_permission_or_403('documentos.all');

        if (!$this->isPost()) {
            $this->redirect('documentos/subir');
            return;
        }

        // Subir/crear un documento es ESCRITURA: exige control total del modulo.
        // documentos.view solo lee; subir con solo-lectura era un hueco de RBAC.
        require_permission_or_403('documentos.all');

        $this->validateCSRF();

        $datos = $this->datosUpload();
        try {
            $resultado = $this->documentoModel->crearDesdeUpload(
                $this->hotelIdActual(),
                $_FILES['archivo'] ?? [],
                $datos,
                $this->usuarioIdActual()
            );

            $documentoId = (int)($resultado['documento_id'] ?? 0);
            clear_old_input();
            set_mensaje('Documento #' . $documentoId . ' guardado. El archivo queda en el espacio privado del hotel.', 'success');
            $this->redirect('documentos/' . $documentoId);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'cargar el documento');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposDocumento([$e->getMessage()]));
            $this->redirect('documentos/subir' . $this->queryContexto($datos));
        }
    }

    public function entidadAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $entidadTipo = $this->documentoModel->normalizarEntidadTipo($this->route_params['entidad_tipo'] ?? $this->route_params['tipo'] ?? null);
        $entidadId = (int)($this->route_params['entidad_id'] ?? $this->route_params['id'] ?? 0);

        if ($entidadTipo === null || $entidadId <= 0) {
            set_mensaje('No encontramos el registro al que quieres ligar el documento. Regresa e inténtalo de nuevo.', 'error');
            $this->redirect('documentos');
            return;
        }

        $tablaDisponible = $this->documentoModel->tablasDisponibles();
        if (!$this->documentoModel->entidadExisteEnHotel($hotelId, $entidadTipo, $entidadId)) {
            set_mensaje('Entidad documental no encontrada para el hotel actual.', 'error');
            $this->redirect('documentos');
            return;
        }

        $documentos = $tablaDisponible
            ? $this->documentoModel->documentosPorEntidad($hotelId, $entidadTipo, $entidadId, 100)
            : [];

        View::renderTemplate('documentos/index', [
            'title' => 'Documentos de ' . $this->etiquetaEntidad($entidadTipo) . ' #' . $entidadId . ' - ' . current_hotel_display_name(),
            'documentos' => $documentos,
            'resumen' => $this->resumenDesdeDocumentos($documentos),
            'tipos' => [],
            'filtros' => [],
            'tablaDisponible' => $tablaDisponible,
            'contextoEntidad' => [
                'tipo' => $entidadTipo,
                'id' => $entidadId,
                'label' => $this->etiquetaEntidad($entidadTipo),
            ],
        ]);
    }

    private function filtros(): array
    {
        return [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'todos'),
            'documento_tipo_id' => (int)$this->getQuery('documento_tipo_id', 0),
        ];
    }

    private function erroresCamposDocumento(array $errores): array
    {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (
                strpos($lower, 'archivo') !== false
                || strpos($lower, 'file') !== false
                || strpos($lower, 'mime') !== false
                || strpos($lower, 'tamano') !== false
                || strpos($lower, 'extension') !== false
                || strpos($lower, 'formato') !== false
                || strpos($lower, 'carga') !== false
                || strpos($lower, 'peso') !== false
                || strpos($lower, 'storage') !== false
            ) {
                $campo = 'archivo';
            } elseif (strpos($lower, 'tipo') !== false) {
                $campo = 'documento_tipo_id';
            } elseif (strpos($lower, 'titulo') !== false) {
                $campo = 'titulo';
            } elseif (strpos($lower, 'descripcion') !== false) {
                $campo = 'descripcion';
            } elseif (strpos($lower, 'etiqueta') !== false) {
                $campo = 'etiquetas';
            } elseif (strpos($lower, 'relacion') !== false || strpos($lower, 'vinculo') !== false || strpos($lower, 'entidad') !== false) {
                $campo = 'relacion';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }

    private function datosUpload(): array
    {
        return [
            'documento_tipo_id' => (int)$this->getPost('documento_tipo_id', 0),
            'titulo' => $this->getPost('titulo', ''),
            'descripcion' => $this->getPost('descripcion', ''),
            'etiquetas' => $this->getPost('etiquetas', ''),
            'entidad_tipo' => $this->getPost('entidad_tipo', ''),
            'entidad_id' => (int)$this->getPost('entidad_id', 0),
            'relacion' => $this->getPost('relacion', ''),
        ];
    }

    private function datosMetadata(): array
    {
        return [
            'documento_tipo_id' => (int)$this->getPost('documento_tipo_id', 0),
            'titulo' => $this->getPost('titulo', ''),
            'descripcion' => $this->getPost('descripcion', ''),
            'etiquetas' => $this->getPost('etiquetas', ''),
        ];
    }

    private function cambiarEstadoAction(string $nuevoEstado, string $mensajeExito): void
    {
        if (!$this->isPost()) {
            $this->redirect('documentos');
            return;
        }

        // Archivar/restaurar/eliminar: control total del modulo.
        require_permission_or_403('documentos.all');

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $this->documentoModel->actualizarEstado(
                $id,
                $hotelId,
                $nuevoEstado,
                $this->usuarioIdActual()
            );
            set_mensaje($mensajeExito, 'success');
            $this->redirect('documentos/' . $id);
        } catch (Throwable $e) {
            set_mensaje_error_op($e, 'cambiar el estado del documento');
            $this->redirect($id > 0 ? 'documentos/' . $id : 'documentos');
        }
    }

    private function contextoEntidadDesdeRequest(int $hotelId, bool $validarExistencia): array|false|null
    {
        $entidadTipo = $this->documentoModel->normalizarEntidadTipo($this->getQuery('entidad_tipo', ''));
        $entidadId = (int)$this->getQuery('entidad_id', 0);

        if ($entidadTipo === null && $entidadId <= 0) {
            return null;
        }

        if ($entidadTipo === null || $entidadId <= 0) {
            return false;
        }

        if ($validarExistencia && !$this->documentoModel->entidadExisteEnHotel($hotelId, $entidadTipo, $entidadId)) {
            return false;
        }

        return [
            'tipo' => $entidadTipo,
            'id' => $entidadId,
            'label' => $this->etiquetaEntidad($entidadTipo),
        ];
    }

    private function queryContexto(array $datos): string
    {
        $entidadTipo = $this->documentoModel->normalizarEntidadTipo($datos['entidad_tipo'] ?? '');
        $entidadId = (int)($datos['entidad_id'] ?? 0);

        if ($entidadTipo === null || $entidadId <= 0) {
            return '';
        }

        return '?entidad_tipo=' . rawurlencode($entidadTipo) . '&entidad_id=' . $entidadId;
    }

    private function resumenVacio(): array
    {
        return [
            'total' => 0,
            'activos' => 0,
            'archivados' => 0,
            'eliminados' => 0,
            'bytes_total' => 0,
            'bytes_pendientes_purga' => 0,
        ];
    }

    private function resumenDesdeDocumentos(array $documentos): array
    {
        $resumen = $this->resumenVacio();
        $resumen['total'] = count($documentos);

        foreach ($documentos as $documento) {
            $estado = (string)($documento['estado'] ?? '');
            if ($estado === 'activo') {
                $resumen['activos']++;
            } elseif ($estado === 'archivado') {
                $resumen['archivados']++;
            } elseif ($estado === 'eliminado') {
                $resumen['eliminados']++;
            }

            $resumen['bytes_total'] += (int)($documento['size_bytes'] ?? 0);
        }

        return $resumen;
    }

    private function requireModuloRelacionado(): void
    {
        if (function_exists('require_hotel_module')) {
            require_hotel_module('documentos');
        }
    }

    private function etiquetaEntidad(string $entidadTipo): string
    {
        $labels = [
            'proveedor' => 'Proveedor',
            'compra' => 'Compra',
            'cuenta_por_pagar' => 'Cuenta por pagar',
            'huesped' => 'Huesped',
            'reservacion' => 'Reservacion',
            'trabajador' => 'Trabajador',
            'tarea' => 'Tarea',
        ];

        return $labels[$entidadTipo] ?? 'Entidad';
    }

    private function servirArchivoPrivado(array $documento, string $ruta, bool $preview = false): void
    {
        if (!is_file($ruta) || !is_readable($ruta)) {
            $this->auditarDescargaBloqueada(
                (int)($documento['hotel_id'] ?? $this->hotelIdActual()),
                (int)($documento['id'] ?? 0),
                'archivo_no_legible'
            );
            set_mensaje('El archivo privado no esta disponible para descarga.', 'error');
            $this->redirect('documentos/' . (int)($documento['id'] ?? 0));
            return;
        }

        $mimeType = $this->mimeDescargaPermitido($documento['mime_type'] ?? null);
        if ($preview && !$this->mimePreviewPermitido($mimeType)) {
            while (ob_get_level() > 0) {
                @ob_end_clean();
            }

            http_response_code(415);
            header('Content-Type: text/plain; charset=UTF-8');
            header('X-Content-Type-Options: nosniff');
            echo 'Este archivo no tiene previsualizacion disponible.';
            exit;
        }

        $nombre = $this->nombreDescargaSeguro($documento['nombre_original'] ?? ('documento-' . (int)($documento['id'] ?? 0)));
        $tamano = filesize($ruta);

        $this->auditarArchivoServido($documento, $mimeType, $tamano, $preview);

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header_remove('Cache-Control');
        header_remove('Pragma');
        header_remove('Expires');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: ' . ($preview ? 'inline' : 'attachment') . '; filename="' . $nombre . '"');
        header('Content-Length: ' . $tamano);
        header('X-Content-Type-Options: nosniff');
        if ($preview) {
            header('X-Frame-Options: SAMEORIGIN');
            header("Content-Security-Policy: frame-ancestors 'self'");
        }
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: private');
        readfile($ruta);
        exit;
    }

    private function mimeDescargaPermitido($mimeType): string
    {
        $mimeType = trim((string)($mimeType ?? ''));
        $permitidos = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ];

        return in_array($mimeType, $permitidos, true) ? $mimeType : 'application/octet-stream';
    }

    private function mimePreviewPermitido(string $mimeType): bool
    {
        return $mimeType === 'application/pdf' || strpos($mimeType, 'image/') === 0;
    }

    private function nombreDescargaSeguro($nombre): string
    {
        $nombre = basename(str_replace('\\', '/', (string)$nombre));
        $nombre = preg_replace('/[\x00-\x1F\x7F"]+/', '', $nombre);
        $nombre = trim((string)$nombre);

        return $nombre !== '' ? $nombre : 'documento';
    }

    private function auditarArchivoServido(array $documento, string $mimeType, int|false $tamano, bool $preview): void
    {
        $documentoId = (int)($documento['id'] ?? 0);
        $hotelId = (int)($documento['hotel_id'] ?? $this->hotelIdActual());

        if ($documentoId <= 0 || $hotelId <= 0) {
            return;
        }

        $this->registrarAuditoriaDocumento($preview ? 'documentos.previsualizado' : 'documentos.descargado', $hotelId, $documentoId, [
            'descripcion' => $preview
                ? 'Documento previsualizado (archivo privado)'
                : 'Documento descargado (archivo privado)',
            'datos_despues' => [
                'documento_id' => $documentoId,
                'nombre_original' => $documento['nombre_original'] ?? null,
                'mime_type' => $mimeType,
                'size_bytes' => $tamano !== false ? (int)$tamano : (int)($documento['size_bytes'] ?? 0),
                'estado' => $documento['estado'] ?? null,
                'storage_privado' => true,
                'preview' => $preview,
            ],
        ]);
    }

    private function auditarDescargaBloqueada(int $hotelId, int $documentoId, string $motivo): void
    {
        if ($hotelId <= 0 || $documentoId <= 0) {
            return;
        }

        $this->registrarAuditoriaDocumento('documentos.descarga_bloqueada', $hotelId, $documentoId, [
            'descripcion' => 'Descarga documental bloqueada: ' . $motivo,
            'datos_despues' => [
                'documento_id' => $documentoId,
                'motivo' => $motivo,
                'storage_privado' => true,
            ],
        ]);
    }

    private function registrarAuditoriaDocumento(string $accion, int $hotelId, int $documentoId, array $contexto): void
    {
        try {
            AuditService::record($accion, [
                'hotel_id' => $hotelId,
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => 'documento',
                'entidad_id' => (string)$documentoId,
                'descripcion' => $contexto['descripcion'] ?? null,
                'datos_despues' => $contexto['datos_despues'] ?? [],
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar descarga documental: ' . $e->getMessage());
        }
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function usuarioIdActual(): ?int
    {
        $usuarioId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        return $usuarioId ? (int)$usuarioId : null;
    }
}
