<?php
/**
 * Controlador de Tarifas
 * Los Cedros
 */
require_once __DIR__ . '/../helpers/hotel_config.php';

class TarifasController extends Controller {
    
    private $tarifaModel;
    private $habitacionModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->tarifaModel = new IncrementoTarifa();
        $this->habitacionModel = new Habitacion();
    }
    
    protected function before() {
        $this->requireAuth();
        
        // Solo gerente y administrador pueden gestionar tarifas
        if (!is_gerente() && !is_admin()) {
            set_mensaje('No tiene permisos para gestionar tarifas', 'error');
            $this->redirect('dashboard');
            return false;
        }

        return true;
    }

    private function tiposHabitacionCatalogo(array $tiposActuales = []) {
        $tipos = [];

        if (function_exists('hotel_room_catalog_types')) {
            foreach (hotel_room_catalog_types() as $codigo => $nombre) {
                $codigo = trim((string)$codigo);
                if ($codigo === '') {
                    continue;
                }

                $tipos[$codigo] = [
                    'tipo' => $codigo,
                    'nombre' => trim((string)$nombre) !== '' ? (string)$nombre : ucwords(str_replace('_', ' ', $codigo))
                ];
            }
        }

        if (empty($tipos)) {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT DISTINCT tipo FROM habitaciones WHERE activa = 1 ORDER BY tipo");
            foreach (($stmt ? $stmt->fetchAll() : []) as $row) {
                $codigo = trim((string)($row['tipo'] ?? ''));
                if ($codigo === '') {
                    continue;
                }

                $tipos[$codigo] = [
                    'tipo' => $codigo,
                    'nombre' => function_exists('get_tipo_habitacion') ? get_tipo_habitacion($codigo) : ucwords(str_replace('_', ' ', $codigo))
                ];
            }
        }

        foreach ($tiposActuales as $codigo) {
            $codigo = trim((string)$codigo);
            if ($codigo === '' || isset($tipos[$codigo])) {
                continue;
            }

            $tipos[$codigo] = [
                'tipo' => $codigo,
                'nombre' => function_exists('get_tipo_habitacion') ? get_tipo_habitacion($codigo) : ucwords(str_replace('_', ' ', $codigo))
            ];
        }

        return array_values($tipos);
    }

    private function erroresCamposTarifa(array $errores): array {
        $fieldErrors = [];

        foreach ($errores as $mensaje) {
            $mensaje = trim((string)$mensaje);
            if ($mensaje === '') {
                continue;
            }

            $lower = strtolower($mensaje);
            $campo = null;

            if (strpos($lower, 'nombre') !== false) {
                $campo = 'nombre';
            } elseif (strpos($lower, 'valor') !== false || strpos($lower, 'incremento') !== false || strpos($lower, 'monto') !== false || strpos($lower, 'porcentaje') !== false) {
                $campo = 'valor_incremento';
            } elseif (strpos($lower, 'fecha de inicio') !== false || strpos($lower, 'inicio') !== false) {
                $campo = 'fecha_inicio';
            } elseif (strpos($lower, 'fecha de fin') !== false || strpos($lower, 'posterior') !== false || strpos($lower, 'temporales') !== false) {
                $campo = 'fecha_fin';
            } elseif (strpos($lower, 'tipo de habit') !== false) {
                $campo = 'tipos_habitacion[]';
            } elseif (strpos($lower, 'habitaci') !== false) {
                $campo = 'habitaciones[]';
            }

            if ($campo !== null) {
                $fieldErrors[$campo][] = $mensaje;
            }
        }

        return $fieldErrors;
    }
    
    /**
     * Lista de incrementos de tarifas
     */
    public function indexAction() {
        $incrementos = $this->tarifaModel->getAllConInfo();
        $estadisticas = $this->tarifaModel->getEstadisticas();
        
        View::renderTemplate('configuracion/tarifas/index', [
            'title' => 'Gestión de Tarifas Dinámicas',
            'incrementos' => $incrementos,
            'estadisticas' => $estadisticas
        ]);
    }
    
    /**
     * Crear nuevo incremento
     */
    public function crearAction() {
        if ($this->isPost()) {
            $this->procesarCreacion();
            return;
        }
        
        $tipos_habitacion = $this->tiposHabitacionCatalogo();
        
        // Todas las habitaciones
        $habitaciones = $this->habitacionModel->where(['activa' => 1], ['id', 'numero', 'tipo', 'precio_base']);
        
        View::renderTemplate('configuracion/tarifas/crear', [
            'title' => 'Nuevo Incremento de Tarifa',
            'tipos_habitacion' => $tipos_habitacion,
            'habitaciones' => $habitaciones
        ]);
    }
    
    private function procesarCreacion() {
        $this->validateCSRF();
        
        try {
            // Validar datos básicos
            $nombre = trim($this->getPost('nombre'));
            if (empty($nombre)) {
                throw new Exception('El nombre es obligatorio');
            }
            
            $valor_incremento = floatval($this->getPost('valor_incremento'));
            if ($valor_incremento <= 0) {
                throw new Exception('El valor del incremento debe ser mayor a 0');
            }
            
            $fecha_inicio = $this->getPost('fecha_inicio');
            if (empty($fecha_inicio)) {
                throw new Exception('La fecha de inicio es obligatoria');
            }
            
            $es_permanente = $this->getPost('es_permanente') ? 1 : 0;
            $fecha_fin = null;
            
            if (!$es_permanente) {
                $fecha_fin = $this->getPost('fecha_fin');
                if (empty($fecha_fin)) {
                    throw new Exception('La fecha de fin es obligatoria para incrementos temporales');
                }
                if ($fecha_fin <= $fecha_inicio) {
                    throw new Exception('La fecha de fin debe ser posterior a la fecha de inicio');
                }
            }
            
            $datos = [
                'nombre' => $nombre,
                'descripcion' => $this->getPost('descripcion'),
                'tipo_incremento' => $this->getPost('tipo_incremento'),
                'valor_incremento' => $valor_incremento,
                'alcance' => $this->getPost('alcance'),
                'es_permanente' => $es_permanente,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'prioridad' => intval($this->getPost('prioridad', 0)),
                'activo' => 1,
                'usuario_id' => user_id()
            ];
            
            // Procesar elementos según alcance
            $elementos = [];
            if ($datos['alcance'] == 'tipo_habitacion') {
                $tipos = $this->getPost('tipos_habitacion', []);
                if (empty($tipos)) {
                    throw new Exception('Debe seleccionar al menos un tipo de habitación');
                }
                $datos['tipos_habitacion'] = json_encode($tipos);
                $elementos = $tipos;
            } elseif ($datos['alcance'] == 'habitacion') {
                $habitaciones = $this->getPost('habitaciones', []);
                if (empty($habitaciones)) {
                    throw new Exception('Debe seleccionar al menos una habitación');
                }
                $datos['habitaciones'] = json_encode($habitaciones);
                $elementos = $habitaciones;
            }
            
            // NOTA: Se permite el solapamiento de tarifas para que se acumulen
            // El método calcularPrecioConIncremento() aplica TODOS los incrementos activos
            // Esto permite tener una tarifa permanente base + incrementos temporales adicionales
            
            // Crear incremento
            $incremento = $this->tarifaModel->create($datos);
            
            if (!$incremento) {
                throw new Exception('Error al crear el incremento de tarifa');
            }
            
            // Registrar en log
            $this->registrarAccion('crear_incremento_tarifa', "Incremento creado: {$nombre}");
            
            set_mensaje('Incremento de tarifa creado exitosamente', 'success');
            $this->redirect('configuracion/tarifas');
            
        } catch (Exception $e) {
            error_log("Error al crear incremento: " . $e->getMessage());
            set_mensaje($e->getMessage(), 'error');
            
            // Guardar datos del formulario para no perderlos
            save_old_input($_POST);
            save_form_errors($this->erroresCamposTarifa([$e->getMessage()]));
            $this->redirect('configuracion/tarifas/crear');
        }
    }
    
    /**
     * Editar incremento
     */
    public function editarAction() {
        $id = $this->route_params['id'] ?? null;
        
        if (!$id) {
            set_mensaje('ID no especificado', 'error');
            $this->redirect('configuracion/tarifas');
        }
        
        $incremento = $this->tarifaModel->find($id);
        
        if (!$incremento) {
            set_mensaje('Incremento no encontrado', 'error');
            $this->redirect('configuracion/tarifas');
        }
        
        if ($this->isPost()) {
            $this->procesarEdicion($id);
            return;
        }
        
        // Decodificar JSON - Compatible con PHP 8+
        $incremento['tipos_habitacion_array'] = !empty($incremento['tipos_habitacion']) ? json_decode($incremento['tipos_habitacion'], true) : [];
        $incremento['habitaciones_array'] = !empty($incremento['habitaciones']) ? json_decode($incremento['habitaciones'], true) : [];
        $tipos_habitacion = $this->tiposHabitacionCatalogo($incremento['tipos_habitacion_array']);

        // Todas las habitaciones
        $habitaciones = $this->habitacionModel->where(['activa' => 1], ['id', 'numero', 'tipo', 'precio_base']);
        
        View::renderTemplate('configuracion/tarifas/editar', [
            'title' => 'Editar Incremento de Tarifa',
            'incremento' => $incremento,
            'tipos_habitacion' => $tipos_habitacion,
            'habitaciones' => $habitaciones
        ]);
    }
    
    private function procesarEdicion($id) {
        $this->validateCSRF();
        
        try {
            // Validaciones similares a crear
            $nombre = trim($this->getPost('nombre'));
            if (empty($nombre)) {
                throw new Exception('El nombre es obligatorio');
            }
            
            $valor_incremento = floatval($this->getPost('valor_incremento'));
            if ($valor_incremento <= 0) {
                throw new Exception('El valor del incremento debe ser mayor a 0');
            }
            
            $es_permanente = $this->getPost('es_permanente') ? 1 : 0;
            $fecha_fin = null;
            
            if (!$es_permanente) {
                $fecha_fin = $this->getPost('fecha_fin');
                if (empty($fecha_fin)) {
                    throw new Exception('La fecha de fin es obligatoria para incrementos temporales');
                }
            }
            
            $datos = [
                'nombre' => $nombre,
                'descripcion' => $this->getPost('descripcion'),
                'tipo_incremento' => $this->getPost('tipo_incremento'),
                'valor_incremento' => $valor_incremento,
                'alcance' => $this->getPost('alcance'),
                'es_permanente' => $es_permanente,
                'fecha_inicio' => $this->getPost('fecha_inicio'),
                'fecha_fin' => $fecha_fin,
                'prioridad' => intval($this->getPost('prioridad', 0))
            ];
            
            // Procesar elementos según alcance
            $elementos = [];
            if ($datos['alcance'] == 'tipo_habitacion') {
                $tipos = $this->getPost('tipos_habitacion', []);
                if (empty($tipos)) {
                    throw new Exception('Debe seleccionar al menos un tipo de habitación');
                }
                $datos['tipos_habitacion'] = json_encode($tipos);
                $datos['habitaciones'] = null;
                $elementos = $tipos;
            } elseif ($datos['alcance'] == 'habitacion') {
                $habitaciones = $this->getPost('habitaciones', []);
                if (empty($habitaciones)) {
                    throw new Exception('Debe seleccionar al menos una habitación');
                }
                $datos['habitaciones'] = json_encode($habitaciones);
                $datos['tipos_habitacion'] = null;
                $elementos = $habitaciones;
            } else {
                $datos['tipos_habitacion'] = null;
                $datos['habitaciones'] = null;
            }
            
            // NOTA: Se permite el solapamiento de tarifas para que se acumulen
            // El método calcularPrecioConIncremento() aplica TODOS los incrementos activos
            
            // Actualizar incremento
            $resultado = $this->tarifaModel->update($id, $datos);
            
            if (!$resultado) {
                throw new Exception('Error al actualizar el incremento de tarifa');
            }
            
            // Registrar en log
            $this->registrarAccion('editar_incremento_tarifa', "Incremento editado: {$nombre}");
            
            set_mensaje('Incremento de tarifa actualizado exitosamente', 'success');
            $this->redirect('configuracion/tarifas');
            
        } catch (Exception $e) {
            error_log("Error al editar incremento: " . $e->getMessage());
            set_mensaje($e->getMessage(), 'error');
            save_old_input($_POST);
            save_form_errors($this->erroresCamposTarifa([$e->getMessage()]));
            $this->redirect('configuracion/tarifas/editar/' . $id);
        }
    }
    
    /**
     * Previsualizar precios con incrementos
     */
    public function previsualizarAction() {
        if (!$this->isAjax()) {
            $this->redirect('configuracion/tarifas');
        }
        
        $fecha = $this->getPost('fecha', date('Y-m-d'));
        $habitaciones = $this->habitacionModel->where(['activa' => 1]);
        $precios = [];
        
        foreach ($habitaciones as $hab) {
            $calculo = $this->tarifaModel->calcularPrecioConIncremento(
                $hab['id'],
                $hab['tipo'],
                $hab['precio_base'],
                $fecha
            );
            
            $precios[] = [
                'habitacion' => $hab['numero'],
                'tipo' => get_tipo_habitacion($hab['tipo']),
                'precio_base' => $calculo['precio_base'],
                'precio_final' => $calculo['precio_final'],
                'incremento' => $calculo['incremento_total'],
                'incrementos' => $calculo['incrementos_aplicados']
            ];
        }
        
        json_response([
            'success' => true,
            'fecha' => $fecha,
            'fecha_formateada' => format_date($fecha, 'd/m/Y'),
            'precios' => $precios
        ]);
    }
    
    /**
     * Activar/Desactivar incremento
     */
    public function toggleAction() {
        if (!$this->isAjax() || !$this->isPost()) {
            $this->redirect('configuracion/tarifas');
        }
        
        $this->validateCSRF();
        
        $id = $this->getPost('id');
        $incremento = $this->tarifaModel->find($id);
        
        if (!$incremento) {
            json_response(['success' => false, 'message' => 'Incremento no encontrado'], 404);
        }
        
        $nuevo_estado = !$incremento['activo'];
        $this->tarifaModel->update($id, ['activo' => $nuevo_estado]);
        
        $this->registrarAccion('toggle_incremento_tarifa', 
            "Incremento " . ($nuevo_estado ? 'activado' : 'desactivado') . ": {$incremento['nombre']}");
        
        json_response([
            'success' => true,
            'activo' => $nuevo_estado,
            'message' => $nuevo_estado ? 'Incremento activado' : 'Incremento desactivado'
        ]);
    }
    
    /**
     * Eliminar incremento
     */
    public function eliminarAction() {
        if (!$this->isAjax() || !$this->isPost()) {
            $this->redirect('configuracion/tarifas');
        }
        
        $this->validateCSRF();
        
        $id = $this->getPost('id');
        $incremento = $this->tarifaModel->find($id);
        
        if (!$incremento) {
            json_response(['success' => false, 'message' => 'Incremento no encontrado'], 404);
        }
        
        // Verificar que no esté vigente
        $hoy = date('Y-m-d');
        if ($incremento['activo'] && 
            $incremento['fecha_inicio'] <= $hoy && 
            ($incremento['fecha_fin'] >= $hoy || $incremento['es_permanente'])) {
            json_response(['success' => false, 
                'message' => 'No se puede eliminar un incremento vigente. Desactívelo primero.'], 400);
        }
        
        if ($this->tarifaModel->delete($id)) {
            $this->registrarAccion('eliminar_incremento_tarifa', "Incremento eliminado: {$incremento['nombre']}");
            json_response(['success' => true, 'message' => 'Incremento eliminado exitosamente']);
        } else {
            json_response(['success' => false, 'message' => 'Error al eliminar el incremento'], 500);
        }
    }
    
    /**
     * Ver detalle de incremento
     */
    public function detalleAction() {
        if (!$this->isAjax()) {
            $this->redirect('configuracion/tarifas');
        }
        
        $id = $this->getQuery('id');
        $incremento = $this->tarifaModel->find($id);
        
        if (!$incremento) {
            json_response(['success' => false, 'message' => 'Incremento no encontrado'], 404);
        }
        
        // Obtener información adicional
        $db = Database::getInstance();
        $stmt = $db->query("SELECT nombre_completo FROM usuarios WHERE id = ?", [$incremento['usuario_id']]);
        $usuario = $stmt->fetch();
        
        $incremento['usuario_nombre'] = $usuario['nombre_completo'] ?? 'Usuario desconocido';
        
        // Decodificar JSON - Compatible con PHP 8+
        $incremento['tipos_habitacion_array'] = !empty($incremento['tipos_habitacion']) ? json_decode($incremento['tipos_habitacion'], true) : [];
        $incremento['habitaciones_array'] = !empty($incremento['habitaciones']) ? json_decode($incremento['habitaciones'], true) : [];
        
        // Si son habitaciones específicas, obtener sus números
        if ($incremento['alcance'] == 'habitacion' && !empty($incremento['habitaciones_array'])) {
            $placeholders = str_repeat('?,', count($incremento['habitaciones_array']) - 1) . '?';
            $sql = "SELECT numero FROM habitaciones WHERE id IN ($placeholders)";
            $stmt = $db->query($sql, $incremento['habitaciones_array']);
            $incremento['numeros_habitaciones'] = array_column($stmt->fetchAll(), 'numero');
        }
        
        json_response([
            'success' => true,
            'incremento' => $incremento
        ]);
    }
    
    /**
     * Registrar acción en log
     */
    private function registrarAccion($tipo, $descripcion) {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO logs_acceso (tipo, usuario_id, exitoso, ip, user_agent, detalles, created_at) 
             VALUES (?, ?, 1, ?, ?, ?, NOW())",
            [$tipo, user_id(), get_client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? '', $descripcion]
        );
    }
}
