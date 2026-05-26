<?php
/**
 * Controlador de Configuración
 * Los Cedros
 */

class ConfiguracionController extends Controller {
    
    private $configuracionModel;
    
    public function __construct($route_params) {
        parent::__construct($route_params);
        $this->configuracionModel = new Configuracion();
    }
    
    /**
     * Verificar permisos antes de cada acción
     */
    protected function before() {
        $this->requireAuth();
        
        // Solo el gerente puede acceder a configuración
        if (!is_gerente()) {
            set_mensaje('No tiene permisos para acceder a esta sección', 'error');
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }
    
    /**
     * Mostrar página de configuración
     */
    public function indexAction() {
        // Obtener configuración actual
        $config = [
            'hotel' => $this->configuracionModel->getByGroup('hotel'),
            'tarifas' => $this->configuracionModel->getByGroup('tarifas'),
            'inventario' => $this->configuracionModel->getByGroup('inventario'),
            'sistema' => $this->configuracionModel->getByGroup('sistema'),
            'upload' => $this->configuracionModel->getByGroup('upload')
        ];
        
        // Obtener información adicional
        $db = Database::getInstance();
        
        // Último backup
        $ultimo_backup = null;
        $backup_dir = STORAGE_PATH . '/backups';
        if (is_dir($backup_dir)) {
            $files = glob($backup_dir . '/*.sql');
            if (!empty($files)) {
                $ultimo_backup = [
                    'archivo' => basename(end($files)),
                    'fecha' => date('Y-m-d H:i:s', filemtime(end($files))),
                    'tamano' => format_file_size(filesize(end($files)))
                ];
            }
        }
        
        // Espacio en disco
        $espacio = [
            'total' => disk_total_space(STORAGE_PATH),
            'libre' => disk_free_space(STORAGE_PATH),
            'usado' => disk_total_space(STORAGE_PATH) - disk_free_space(STORAGE_PATH)
        ];
        
        // CAMBIAR View::render por View::renderTemplate
        View::renderTemplate('configuracion/index', [
            'title' => 'Configuración del Sistema',
            'config' => $config,
            'ultimo_backup' => $ultimo_backup,
            'espacio' => $espacio
        ]);
    }
    
    /**
     * Actualizar configuración
     */
    public function actualizarAction() {
        if (!$this->isPost()) {
            $this->redirect('configuracion');
        }
        
        $this->validateCSRF();
        
        try {
            // Hotel
            $this->configuracionModel->set('hotel.nombre', $this->getPost('hotel_nombre'));
            $this->configuracionModel->set('hotel.direccion', $this->getPost('hotel_direccion'));
            $this->configuracionModel->set('hotel.telefono', $this->getPost('hotel_telefono'));
            $this->configuracionModel->set('hotel.email', $this->getPost('hotel_email'));
            $this->configuracionModel->set('hotel.check_in_time', $this->getPost('hotel_check_in_time'));
            $this->configuracionModel->set('hotel.check_out_time', $this->getPost('hotel_check_out_time'));
            $this->configuracionModel->set('hotel.horas_estancia', $this->getPost('hotel_horas_estancia'), 'integer');
            
            // Tarifas
            $this->configuracionModel->set('tarifas.incremento_fin_semana', $this->getPost('tarifas_incremento_fin_semana'), 'float');
            $this->configuracionModel->set('tarifas.descuento_grupo_minimo', $this->getPost('tarifas_descuento_grupo_minimo'), 'integer');
            $this->configuracionModel->set('tarifas.descuento_grupo_gratis', $this->getPost('tarifas_descuento_grupo_gratis'), 'integer');
            
            // Inventario
            $this->configuracionModel->set('inventario.auto_papel_higienico', $this->getPost('inventario_auto_papel_higienico'), 'integer');
            $this->configuracionModel->set('inventario.auto_jabon', $this->getPost('inventario_auto_jabon'), 'integer');
            
            // Sistema
            $this->configuracionModel->set('sistema.session_lifetime', $this->getPost('sistema_session_lifetime'), 'integer');
            $this->configuracionModel->set('sistema.backup_enabled', $this->getPost('sistema_backup_enabled') ? 1 : 0, 'boolean');
            $this->configuracionModel->set('sistema.backup_frequency', $this->getPost('sistema_backup_frequency'));
            $this->configuracionModel->set('sistema.backup_keep_last', $this->getPost('sistema_backup_keep_last'), 'integer');
            
            // Registrar en log
            $this->registrarAccion('actualizar_configuracion', 'Configuración del sistema actualizada');
            
            set_mensaje('Configuración actualizada exitosamente', 'success');
            
        } catch (Exception $e) {
            error_log("Error al actualizar configuración: " . $e->getMessage());
            set_mensaje('Error al actualizar la configuración', 'error');
        }
        
        $this->redirect('configuracion');
    }
    
    /**
     * Mostrar página de backup
     */
    public function backupAction() {
        $backup_dir = STORAGE_PATH . '/backups';
        $backups = [];
        
        if (is_dir($backup_dir)) {
            $files = glob($backup_dir . '/*.sql');
            foreach ($files as $file) {
                $backups[] = [
                    'archivo' => basename($file),
                    'fecha' => date('Y-m-d H:i:s', filemtime($file)),
                    'tamano' => format_file_size(filesize($file)),
                    'path' => $file
                ];
            }
            
            // Ordenar por fecha descendente
            usort($backups, function($a, $b) {
                return filemtime($b['path']) - filemtime($a['path']);
            });
        }
        
        // CAMBIAR View::render por View::renderTemplate
        View::renderTemplate('configuracion/backup', [
            'title' => 'Respaldos del Sistema',
            'backups' => $backups
        ]);
    }
    
    /**
     * Crear backup manual
     */
    public function crearBackupAction() {
        if (!$this->isPost()) {
            $this->redirect('configuracion/backup');
        }
        
        $this->validateCSRF();
        
        try {
            $backup_dir = STORAGE_PATH . '/backups';
            
            // Crear directorio si no existe
            if (!is_dir($backup_dir)) {
                if (!mkdir($backup_dir, 0755, true)) {
                    throw new Exception('No se pudo crear el directorio de respaldos');
                }
            }
            
            // Generar nombre de archivo
            $filename = 'backup_' . date('Ymd_His') . '_manual.sql';
            $filepath = $backup_dir . '/' . $filename;
            
            // Obtener configuración de base de datos
            $db_config = require CONFIG_PATH . '/database.php';
            
            // Comando mysqldump
            $command = sprintf(
                'mysqldump --host=%s --user=%s --password=%s --databases %s > %s 2>&1',
                escapeshellarg($db_config['host']),
                escapeshellarg($db_config['username']),
                escapeshellarg($db_config['password']),
                escapeshellarg($db_config['database']),
                escapeshellarg($filepath)
            );
            
            // Ejecutar comando
            exec($command, $output, $return_var);
            
            if ($return_var !== 0) {
                throw new Exception('Error al crear el respaldo: ' . implode("\n", $output));
            }
            
            // Verificar que el archivo se creó
            if (!file_exists($filepath) || filesize($filepath) == 0) {
                throw new Exception('El respaldo se creó vacío o no se pudo crear');
            }
            
            // Limpiar backups antiguos según configuración
            $this->limpiarBackupsAntiguos();
            
            // Registrar en log
            $this->registrarAccion('crear_backup', "Respaldo manual creado: $filename");
            
            set_mensaje('Respaldo creado exitosamente', 'success');
            
        } catch (Exception $e) {
            error_log("Error al crear backup: " . $e->getMessage());
            set_mensaje('Error al crear el respaldo: ' . $e->getMessage(), 'error');
        }
        
        $this->redirect('configuracion/backup');
    }
    
    /**
     * Descargar backup
     */
    public function descargarBackupAction() {
        $archivo = $this->getQuery('archivo');
        
        if (empty($archivo)) {
            set_mensaje('Archivo no especificado', 'error');
            $this->redirect('configuracion/backup');
        }
        
        $filepath = STORAGE_PATH . '/backups/' . basename($archivo);
        
        if (!file_exists($filepath)) {
            set_mensaje('El archivo no existe', 'error');
            $this->redirect('configuracion/backup');
        }
        
        // Enviar archivo
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: private');
        
        readfile($filepath);
        
        // Registrar en log
        $this->registrarAccion('descargar_backup', "Respaldo descargado: " . basename($filepath));
        
        exit;
    }
    
    /**
     * Limpiar backups antiguos
     */
    private function limpiarBackupsAntiguos() {
        $mantener = $this->configuracionModel->get('sistema.backup_keep_last', 4);
        $backup_dir = STORAGE_PATH . '/backups';
        
        if (!is_dir($backup_dir)) {
            return;
        }
        
        $files = glob($backup_dir . '/*.sql');
        
        // Si hay más archivos de los permitidos
        if (count($files) > $mantener) {
            // Ordenar por fecha de modificación (más antiguos primero)
            usort($files, function($a, $b) {
                return filemtime($a) - filemtime($b);
            });
            
            // Eliminar los más antiguos
            $eliminar = count($files) - $mantener;
            for ($i = 0; $i < $eliminar; $i++) {
                @unlink($files[$i]);
            }
        }
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