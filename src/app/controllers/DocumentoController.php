<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/Documento.php';

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
            set_mensaje('Documento no disponible para descarga en el hotel actual.', 'error');
            $this->redirect('documentos');
            return;
        }

        try {
            $ruta = $this->documentoModel->resolverRutaPrivada($documento);
        } catch (Throwable $e) {
            set_mensaje('El archivo privado no esta disponible para descarga.', 'error');
            $this->redirect('documentos/' . $id);
            return;
        }

        $this->servirArchivoPrivado($documento, $ruta);
    }

    public function subirAction(): void
    {
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
        if (!$this->isPost()) {
            $this->redirect('documentos/subir');
            return;
        }

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
            set_mensaje('Documento #' . $documentoId . ' cargado correctamente en storage privado.', 'success');
            $this->redirect('documentos/' . $documentoId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo cargar el documento: ' . $e->getMessage(), 'error');
            $this->redirect('documentos/subir' . $this->queryContexto($datos));
        }
    }

    public function entidadAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $entidadTipo = $this->documentoModel->normalizarEntidadTipo($this->route_params['entidad_tipo'] ?? $this->route_params['tipo'] ?? null);
        $entidadId = (int)($this->route_params['entidad_id'] ?? $this->route_params['id'] ?? 0);

        if ($entidadTipo === null || $entidadId <= 0) {
            set_mensaje('Entidad documental no valida.', 'error');
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
        if (!function_exists('hotel_menu_module_enabled')) {
            return;
        }

        foreach (['inventario', 'huespedes', 'reservaciones'] as $clave) {
            if (hotel_menu_module_enabled($clave)) {
                return;
            }
        }

        set_mensaje('Centro documental no disponible para este hotel.', 'error');
        $this->redirect('dashboard');
    }

    private function etiquetaEntidad(string $entidadTipo): string
    {
        $labels = [
            'proveedor' => 'Proveedor',
            'compra' => 'Compra',
            'cuenta_por_pagar' => 'Cuenta por pagar',
            'huesped' => 'Huesped',
            'reservacion' => 'Reservacion',
        ];

        return $labels[$entidadTipo] ?? 'Entidad';
    }

    private function servirArchivoPrivado(array $documento, string $ruta): void
    {
        if (!is_file($ruta) || !is_readable($ruta)) {
            set_mensaje('El archivo privado no esta disponible para descarga.', 'error');
            $this->redirect('documentos/' . (int)($documento['id'] ?? 0));
            return;
        }

        $mimeType = $this->mimeDescargaPermitido($documento['mime_type'] ?? null);
        $nombre = $this->nombreDescargaSeguro($documento['nombre_original'] ?? ('documento-' . (int)($documento['id'] ?? 0)));
        $tamano = filesize($ruta);

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        header_remove('Cache-Control');
        header_remove('Pragma');
        header_remove('Expires');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . $tamano);
        header('X-Content-Type-Options: nosniff');
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

    private function nombreDescargaSeguro($nombre): string
    {
        $nombre = basename(str_replace('\\', '/', (string)$nombre));
        $nombre = preg_replace('/[\x00-\x1F\x7F"]+/', '', $nombre);
        $nombre = trim((string)$nombre);

        return $nombre !== '' ? $nombre : 'documento';
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
