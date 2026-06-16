<?php

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../../core/View.php';
require_once __DIR__ . '/../helpers/hotel_config.php';
require_once __DIR__ . '/../helpers/modulos.php';
require_once __DIR__ . '/../models/Trabajador.php';
require_once __DIR__ . '/../services/AuditService.php';

class TrabajadorController extends Controller
{
    private $trabajadorModel;

    public function __construct($route_params = [])
    {
        parent::__construct($route_params);
        $this->trabajadorModel = new Trabajador();
    }

    protected function before()
    {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('usuarios');
        }

        if (function_exists('require_permission')) {
            require_permission('usuarios.view');
        }

        return true;
    }

    public function indexAction(): void
    {
        $hotelId = $this->hotelIdActual();
        $filtros = [
            'buscar' => $this->getQuery('buscar', ''),
            'estado' => $this->getQuery('estado', 'activos'),
        ];

        $tablaDisponible = $this->trabajadorModel->tablaDisponible();

        View::renderTemplate('trabajadores/index', [
            'title' => 'Personal - ' . current_hotel_display_name(),
            'trabajadores' => $tablaDisponible ? $this->trabajadorModel->listarPorHotel($hotelId, $filtros, 200) : [],
            'resumen' => $tablaDisponible ? $this->trabajadorModel->resumenPorHotel($hotelId) : $this->resumenVacio(),
            'filtros' => $filtros,
            'tablaDisponible' => $tablaDisponible,
        ]);
    }

    public function verAction(): void
    {
        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();
        $trabajador = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);

        if (!$trabajador) {
            set_mensaje('Trabajador no encontrado para el hotel actual.', 'error');
            $this->redirect('trabajadores');
            return;
        }

        View::renderTemplate('trabajadores/ver', [
            'title' => 'Trabajador #' . $id . ' - ' . current_hotel_display_name(),
            'trabajador' => $trabajador,
            'resumenLedger' => $this->trabajadorModel->resumenLedgerPorTrabajador($id, $hotelId),
            'asistenciasRecientes' => $this->trabajadorModel->ultimosMovimientosPorTrabajador($id, $hotelId, 20),
            'ledgerDisponible' => $this->trabajadorModel->tablasLedgerDisponibles(),
        ]);
    }

    public function crearAction(): void
    {
        $this->requireWritePermission('usuarios.create');
        $hotelId = $this->hotelIdActual();

        View::renderTemplate('trabajadores/form', [
            'title' => 'Nuevo trabajador - ' . current_hotel_display_name(),
            'modo' => 'crear',
            'trabajador' => [],
            'usuariosVinculables' => $this->trabajadorModel->usuariosVinculablesPorHotel($hotelId),
        ]);
    }

    public function guardarAction(): void
    {
        $this->requireWritePermission('usuarios.create');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        try {
            $hotelId = $this->hotelIdActual();
            $trabajadorId = $this->trabajadorModel->crearParaHotel(
                $hotelId,
                $this->datosFormulario(),
                $this->usuarioIdActual()
            );
            $despues = $this->trabajadorModel->buscarPorIdHotel($trabajadorId, $hotelId);
            $this->auditar('trabajadores.creado', null, $despues, $trabajadorId);

            set_mensaje('Trabajador creado correctamente.', 'success');
            $this->redirect('trabajadores/' . $trabajadorId);
        } catch (Throwable $e) {
            set_mensaje('No se pudo crear el trabajador: ' . $e->getMessage(), 'error');
            $this->redirect('trabajadores/crear');
        }
    }

    public function editarAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

        $hotelId = $this->hotelIdActual();
        $trabajador = $this->trabajadorActual($hotelId);
        if (!$trabajador) {
            set_mensaje('Trabajador no encontrado para el hotel actual.', 'error');
            $this->redirect('trabajadores');
            return;
        }

        View::renderTemplate('trabajadores/form', [
            'title' => 'Editar trabajador - ' . current_hotel_display_name(),
            'modo' => 'editar',
            'trabajador' => $trabajador,
            'usuariosVinculables' => $this->trabajadorModel->usuariosVinculablesPorHotel($hotelId),
        ]);
    }

    public function actualizarAction(): void
    {
        $this->requireWritePermission('usuarios.edit');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $antes = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new Exception('Trabajador no encontrado para el hotel actual');
            }

            $this->trabajadorModel->actualizarParaHotel(
                $id,
                $hotelId,
                $this->datosFormulario(),
                $this->usuarioIdActual()
            );

            $despues = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar('trabajadores.actualizado', $antes, $despues, $id);

            set_mensaje('Trabajador actualizado correctamente.', 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje('No se pudo actualizar el trabajador: ' . $e->getMessage(), 'error');
            $this->redirect($id > 0 ? 'trabajadores/' . $id . '/editar' : 'trabajadores');
        }
    }

    public function bajaLogicaAction(): void
    {
        $this->cambiarEstado('baja', 'Trabajador dado de baja correctamente.', 'trabajadores.baja_logica');
    }

    public function reactivarAction(): void
    {
        $this->cambiarEstado('activo', 'Trabajador reactivado correctamente.', 'trabajadores.reactivado');
    }

    private function cambiarEstado(string $estado, string $mensaje, string $accion): void
    {
        $this->requireWritePermission('usuarios.edit');

        if (!$this->isPost()) {
            $this->redirect('trabajadores');
            return;
        }

        $this->validateCSRF();

        $id = (int)($this->route_params['id'] ?? 0);
        $hotelId = $this->hotelIdActual();

        try {
            $antes = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            if (!$antes) {
                throw new Exception('Trabajador no encontrado para el hotel actual');
            }

            $this->trabajadorModel->cambiarEstadoParaHotel($id, $hotelId, $estado, $this->usuarioIdActual());
            $despues = $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
            $this->auditar($accion, $antes, $despues, $id);

            set_mensaje($mensaje, 'success');
            $this->redirect('trabajadores/' . $id);
        } catch (Throwable $e) {
            set_mensaje('No se pudo cambiar el estado del trabajador: ' . $e->getMessage(), 'error');
            $this->redirect($id > 0 ? 'trabajadores/' . $id : 'trabajadores');
        }
    }

    private function resumenVacio(): array
    {
        return [
            'total' => 0,
            'activos' => 0,
            'inactivos' => 0,
            'baja' => 0,
        ];
    }

    private function hotelIdActual(): int
    {
        return function_exists('obtenerHotelIdActualCompat')
            ? (int)obtenerHotelIdActualCompat()
            : (int)($_SESSION['hotel_id'] ?? 0);
    }

    private function trabajadorActual(int $hotelId): ?array
    {
        $id = (int)($this->route_params['id'] ?? 0);
        return $this->trabajadorModel->buscarPorIdHotel($id, $hotelId);
    }

    private function datosFormulario(): array
    {
        return [
            'usuario_id' => $this->getPost('usuario_id', null),
            'nombre_completo' => $this->getPost('nombre_completo', ''),
            'identificacion' => $this->getPost('identificacion', ''),
            'rol_laboral' => $this->getPost('rol_laboral', ''),
            'telefono' => $this->getPost('telefono', ''),
            'email' => $this->getPost('email', ''),
            'fecha_alta' => $this->getPost('fecha_alta', ''),
            'salario_base' => $this->getPost('salario_base', ''),
            'periodicidad_pago' => $this->getPost('periodicidad_pago', ''),
            'notas' => $this->getPost('notas', ''),
        ];
    }

    private function usuarioIdActual(): ?int
    {
        $usuarioId = $_SESSION['user_id'] ?? $_SESSION['usuario_id'] ?? null;
        return $usuarioId ? (int)$usuarioId : null;
    }

    private function requireWritePermission(string $permission): void
    {
        if (function_exists('require_permission')) {
            require_permission($permission);
        }
    }

    private function auditar(string $accion, ?array $antes, ?array $despues, int $trabajadorId): void
    {
        try {
            AuditService::record($accion, [
                'hotel_id' => $this->hotelIdActual(),
                'usuario_id' => $this->usuarioIdActual(),
                'entidad_tipo' => 'trabajador',
                'entidad_id' => (string)$trabajadorId,
                'descripcion' => 'Cambio en catalogo de trabajadores',
                'datos_antes' => $antes,
                'datos_despues' => $despues,
            ]);
        } catch (Throwable $e) {
            error_log('No se pudo auditar trabajador: ' . $e->getMessage());
        }
    }
}
