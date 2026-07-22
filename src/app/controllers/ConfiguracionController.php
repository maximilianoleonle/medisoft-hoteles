<?php
/**
 * Controlador de Configuración
 * Sistema hotelero
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
        
        // Acceso administrativo del hotel actual.
        if (!$this->puedeGestionarConfiguracionHotel()) {
            set_mensaje('No tiene permisos para acceder a esta sección', 'error');
            $this->redirect('dashboard');
            return false;
        }
        
        return true;
    }

    private function puedeGestionarConfiguracionHotel() {
        // RBAC intra-hotel: mismo permiso que gatea la entrada del menu
        // (config/navegacion.php -> 'configuracion.view'), resuelto por el rol
        // del usuario EN ESTE hotel. El gate anterior usaba is_gerente()
        // (usuarios.rol GLOBAL, ajeno al hotel actual).
        return can('configuracion.view');
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

        $hotelSettingDefinitions = function_exists('hotel_config_editable_definitions') ? hotel_config_editable_definitions() : [];
        $hotelSettings = function_exists('hotel_config_editable_values') ? hotel_config_editable_values() : [];
        $hotelSettings = $this->aplicarFallbackLegacyHotelSettings($hotelSettings, $config['hotel']);
        $hotelId = function_exists('current_hotel_id') ? current_hotel_id() : ($_SESSION['hotel_id'] ?? null);
        $pwaPushDevices = $this->obtenerDispositivosPwaPush((int)$hotelId);
        $hotelBranding = function_exists('current_hotel_branding') ? current_hotel_branding() : [];
        $hotelBackgroundStored = function_exists('hotel_config_get')
            ? trim((string) hotel_config_get('apariencia.fondo_sistema', '', $hotelId))
            : '';

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $hotelBackgroundStored)) {
            $hotelBackgroundStored = '';
        } else {
            $hotelBackgroundStored = strtoupper($hotelBackgroundStored);
        }

        $hotelBackgroundColor = function_exists('hotel_branding_system_background')
            ? hotel_branding_system_background($hotelBranding, $hotelId)
            : ($hotelBackgroundStored !== '' ? $hotelBackgroundStored : '#F5F5F7');
        
        // CAMBIAR View::render por View::renderTemplate
        View::renderTemplate('configuracion/index', [
            'title' => 'Configuración del Sistema',
            'config' => $config,
            'hotelSettingDefinitions' => $hotelSettingDefinitions,
            'hotelSettings' => $hotelSettings,
            'hotelBranding' => $hotelBranding,
            'hotelBackgroundColor' => $hotelBackgroundColor,
            'hotelBackgroundStored' => $hotelBackgroundStored,
            'roomTypeCatalog' => function_exists('hotel_room_catalog_type_rows') ? hotel_room_catalog_type_rows($hotelId, true) : [],
            'roomFloorCatalog' => function_exists('hotel_room_catalog_floor_rows') ? hotel_room_catalog_floor_rows($hotelId, true) : [],
            'roomAmenityCatalog' => function_exists('hotel_room_catalog_amenity_rows') ? hotel_room_catalog_amenity_rows($hotelId, true) : [],
            'generalZoneCatalog' => function_exists('hotel_general_catalog_zone_rows') ? hotel_general_catalog_zone_rows($hotelId, true) : [],
            'generalParkingCatalog' => function_exists('hotel_general_catalog_parking_rows') ? hotel_general_catalog_parking_rows($hotelId, true) : [],
            'generalUnitCatalog' => function_exists('hotel_general_catalog_unit_rows') ? hotel_general_catalog_unit_rows($hotelId, true) : [],
            'guestFieldCatalog' => function_exists('hotel_guest_field_catalog') ? hotel_guest_field_catalog() : [],
            'guestFieldPolicy' => function_exists('hotel_guest_field_policy') ? hotel_guest_field_policy($hotelId) : [],
            'ownerDistributionConfig' => function_exists('hotel_owner_distribution_config') ? hotel_owner_distribution_config($hotelId) : [],
            'pwaPushDevices' => $pwaPushDevices,
            'footerNavCatalog' => function_exists('hotel_footer_nav_available_catalog') ? hotel_footer_nav_available_catalog() : [],
            'footerNavSelected' => function_exists('hotel_footer_nav_items') ? array_keys(hotel_footer_nav_items()) : [],
            'footerNavMax' => function_exists('hotel_footer_nav_max') ? hotel_footer_nav_max() : 4,
            'footerNavMin' => function_exists('hotel_footer_nav_min') ? hotel_footer_nav_min() : 2,
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

        // Escritura: exige el permiso de edicion (configuracion.view solo lee).
        require_permission_or_403('configuracion.edit');

        $this->validateCSRF();

        $hotelConfigValues = [];
        $hotelConfigPayload = $_POST['hotel_config'] ?? null;

        if (is_array($hotelConfigPayload) && function_exists('hotel_config_normalize_editable_payload')) {
            $hotelConfigResult = hotel_config_normalize_editable_payload($hotelConfigPayload);

            if (!empty($hotelConfigResult['errors'])) {
                set_mensaje(implode(' ', $hotelConfigResult['errors']), 'error');
                $this->redirect('configuracion');
                return;
            }

            $hotelConfigValues = $hotelConfigResult['values'];
        }

        $roomCatalogValues = null;
        $roomCatalogPayload = $_POST['room_catalog'] ?? null;

        if (is_array($roomCatalogPayload) && function_exists('hotel_room_catalog_normalize_payload')) {
            $roomCatalogResult = hotel_room_catalog_normalize_payload($roomCatalogPayload);

            if (!empty($roomCatalogResult['errors'])) {
                set_mensaje(implode('<br>', $roomCatalogResult['errors']), 'error');
                $this->redirect('configuracion');
                return;
            }

            $roomCatalogValues = $roomCatalogResult['values'];
        }

        $generalCatalogValues = null;
        $generalCatalogPayload = $_POST['general_catalog'] ?? null;

        if (is_array($generalCatalogPayload) && function_exists('hotel_general_catalog_normalize_payload')) {
            $generalCatalogResult = hotel_general_catalog_normalize_payload($generalCatalogPayload);

            if (!empty($generalCatalogResult['errors'])) {
                set_mensaje(implode('<br>', $generalCatalogResult['errors']), 'error');
                $this->redirect('configuracion');
                return;
            }

            $generalCatalogValues = $generalCatalogResult['values'];
        }

        $guestFieldPolicyValues = null;
        $guestFieldPayload = $_POST['guest_fields'] ?? null;

        if (is_array($guestFieldPayload) && function_exists('hotel_guest_field_policy_normalize_payload')) {
            $guestFieldPolicyValues = hotel_guest_field_policy_normalize_payload($guestFieldPayload);
        }

        $ownerDistributionValues = null;
        $ownerDistributionPayload = $_POST['owner_config'] ?? null;

        if (is_array($ownerDistributionPayload) && function_exists('hotel_owner_distribution_normalize_payload')) {
            $ownerDistributionResult = hotel_owner_distribution_normalize_payload($ownerDistributionPayload);

            if (!empty($ownerDistributionResult['errors'])) {
                set_mensaje(implode('<br>', $ownerDistributionResult['errors']), 'error');
                $this->redirect('configuracion');
                return;
            }

            $ownerDistributionValues = $ownerDistributionResult['values'];
        }

        $footerNavValues = null;

        if (isset($_POST['footer_nav_submitted']) && function_exists('hotel_footer_nav_normalize_payload')) {
            $footerNavResult = hotel_footer_nav_normalize_payload($_POST['footer_nav'] ?? []);

            if (!empty($footerNavResult['errors'])) {
                set_mensaje(implode('<br>', $footerNavResult['errors']), 'error');
                $this->redirect('configuracion');
                return;
            }

            $footerNavValues = $footerNavResult['values'];
        }

        $brandingValues = null;
        $brandingPayload = $_POST['hotel_branding'] ?? null;
        $brandingContext = $this->hotelActualParaBranding();

        if (is_array($brandingPayload)) {
            $brandingValues = $this->datosBrandingHotelDesdePayload($brandingPayload);
            $brandingErrors = [];

            if (empty($brandingContext['id'])) {
                $brandingErrors[] = 'No se pudo resolver el hotel activo para guardar la marca.';
            } elseif (!class_exists('HotelBranding')) {
                $brandingErrors[] = 'El modulo de branding no esta disponible.';
            } else {
                $brandingErrors = array_merge(
                    $this->procesarUploadsBrandingHotel($brandingContext, $brandingValues),
                    (new HotelBranding())->validarDatos($brandingValues)
                );
            }

            if (!empty($brandingErrors)) {
                set_mensaje(implode('<br>', $brandingErrors), 'error');
                $this->redirect('configuracion');
                return;
            }
        }

        $appearanceValues = null;
        $appearancePayload = $_POST['hotel_appearance'] ?? null;

        if (is_array($appearancePayload)) {
            $backgroundMode = trim((string) ($appearancePayload['background_mode'] ?? 'default'));
            $backgroundColor = strtoupper(trim((string) ($appearancePayload['background_color'] ?? '')));

            if (!in_array($backgroundMode, ['default', 'custom'], true)) {
                $backgroundMode = 'default';
            }

            if ($backgroundMode === 'custom' && !preg_match('/^#[0-9A-F]{6}$/', $backgroundColor)) {
                set_mensaje('Elige el color con el selector; el valor no es válido.', 'error');
                $this->redirect('configuracion');
                return;
            }

            $appearanceValues = [
                'background_mode' => $backgroundMode,
                'background_color' => $backgroundMode === 'custom' ? $backgroundColor : '',
            ];
        }

        $db = null;

        try {
            $db = Database::getInstance();
            $db->safeBeginTransaction();

            // Hotel
            $hotelNombre = trim((string) $this->getPost('hotel_nombre'));
            if (is_array($brandingValues) && trim((string) ($brandingValues['nombre_visual'] ?? '')) !== '') {
                $hotelNombre = trim((string) $brandingValues['nombre_visual']);
            }

            $hotelDireccion = $hotelConfigValues['contacto.direccion'] ?? $this->getPost('hotel_direccion');
            $hotelTelefono = $hotelConfigValues['contacto.telefono'] ?? $this->getPost('hotel_telefono');
            $hotelEmail = $hotelConfigValues['contacto.email'] ?? $this->getPost('hotel_email');
            $hotelCheckIn = $hotelConfigValues['operacion.checkin_hora'] ?? $this->getPost('hotel_check_in_time');
            $hotelCheckOut = $hotelConfigValues['operacion.checkout_hora'] ?? $this->getPost('hotel_check_out_time');

            $this->configuracionModel->set('hotel.nombre', $hotelNombre);
            $this->configuracionModel->set('hotel.direccion', $hotelDireccion);
            $this->configuracionModel->set('hotel.telefono', $hotelTelefono);
            $this->configuracionModel->set('hotel.email', $hotelEmail);
            $this->configuracionModel->set('hotel.check_in_time', $hotelCheckIn);
            $this->configuracionModel->set('hotel.check_out_time', $hotelCheckOut);
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

            if (!empty($hotelConfigValues) && function_exists('hotel_config_save_editable_values')) {
                hotel_config_save_editable_values($hotelConfigValues);
            }

            if (is_array($roomCatalogValues) && function_exists('hotel_room_catalog_save_values')) {
                hotel_room_catalog_save_values($roomCatalogValues);
            }

            if (is_array($generalCatalogValues) && function_exists('hotel_general_catalog_save_values')) {
                hotel_general_catalog_save_values($generalCatalogValues);
            }

            if (is_array($guestFieldPolicyValues) && function_exists('hotel_guest_field_policy_save')) {
                hotel_guest_field_policy_save($guestFieldPolicyValues);
            }

            if (is_array($ownerDistributionValues) && function_exists('hotel_owner_distribution_save')) {
                hotel_owner_distribution_save($ownerDistributionValues);
            }

            if (is_array($footerNavValues) && function_exists('hotel_footer_nav_save')) {
                hotel_footer_nav_save($footerNavValues);
            }

            if (is_array($appearanceValues) && function_exists('hotel_config_save_value')) {
                hotel_config_save_value(
                    'apariencia.fondo_sistema',
                    $appearanceValues['background_color'],
                    'string',
                    'apariencia',
                    'Color de fondo global de las vistas operativas del hotel en modo claro.'
                );
            }

            if (is_array($brandingValues) && !empty($brandingContext['id'])) {
                $brandingModel = new HotelBranding();

                if (!$brandingModel->guardarParaHotel((int) $brandingContext['id'], $brandingValues)) {
                    throw new RuntimeException('No se pudo guardar el branding del hotel.');
                }
            }
            
            // Registrar en log
            $this->registrarAccion('actualizar_configuracion', 'Configuración del sistema actualizada');

            $db->safeCommit();

            $_SESSION['hotel_nombre'] = $hotelNombre;
            
            set_mensaje('Configuración actualizada exitosamente', 'success');
            
        } catch (Throwable $e) {
            if ($db && method_exists($db, 'safeRollBack')) {
                $db->safeRollBack();
            }

            error_log("Error al actualizar configuración: " . $e->getMessage());
            set_mensaje('Error al actualizar la configuración', 'error');
        }
        
        $this->redirect('configuracion');
    }
    
    /**
     * Mostrar página de backup
     */
    public function backupAction() {
        // Los respaldos vuelcan la BASE COMPLETA (mysqldump --databases), que en
        // este SaaS es multi-tenant: contiene TODOS los hoteles, hashes de
        // credenciales, tokens y PII de huespedes/pagos. Por eso es una operacion
        // del operador Medisoft (SaaS admin), NUNCA de un hotel inquilino. El
        // before() ya exigio ser gestor del hotel; aqui elevamos a SaaS admin.
        requireSaasAdmin();

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
        // Respaldo = dump de la BD multi-tenant completa: solo el operador SaaS.
        requireSaasAdmin();

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
            set_mensaje_error_op($e, 'crear el respaldo');
        }
        
        $this->redirect('configuracion/backup');
    }
    
    /**
     * Descargar backup
     */
    public function descargarBackupAction() {
        // El .sql descargable contiene la BD multi-tenant entera: solo SaaS admin.
        requireSaasAdmin();

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
     * Completa valores editables con la configuracion legacy cuando el hotel aun no tiene filas propias.
     */
    private function aplicarFallbackLegacyHotelSettings(array $hotelSettings, array $configHotel) {
        $fallbacks = [
            'contacto.telefono' => $configHotel['telefono'] ?? '',
            'contacto.email' => $configHotel['email'] ?? '',
            'contacto.direccion' => $configHotel['direccion'] ?? '',
            'operacion.checkin_hora' => $configHotel['check_in_time'] ?? '15:00',
            'operacion.checkout_hora' => $configHotel['check_out_time'] ?? '12:00',
        ];

        foreach ($fallbacks as $key => $value) {
            if (!array_key_exists($key, $hotelSettings) || trim((string) $hotelSettings[$key]) === '') {
                $hotelSettings[$key] = $value;
            }
        }

        return $hotelSettings;
    }

    private function hotelActualParaBranding() {
        return [
            'id' => function_exists('current_hotel_id') ? current_hotel_id() : ($_SESSION['hotel_id'] ?? null),
            'slug' => function_exists('current_hotel_slug') ? current_hotel_slug() : ($_SESSION['hotel_slug'] ?? null),
            'nombre' => function_exists('current_hotel_nombre') ? current_hotel_nombre() : ($_SESSION['hotel_nombre'] ?? null),
        ];
    }

    private function obtenerDispositivosPwaPush(int $hotelId): array {
        if ($hotelId <= 0 || !class_exists('PwaPushSubscription')) {
            return [];
        }

        try {
            return (new PwaPushSubscription())->listarPorHotel($hotelId, 40);
        } catch (Throwable $e) {
            error_log('No se pudieron listar dispositivos PWA Push: ' . $e->getMessage());
            return [];
        }
    }

    private function datosBrandingHotelDesdePayload(array $payload) {
        return [
            'nombre_visual' => trim((string) ($payload['nombre_visual'] ?? '')),
            'logo_url' => trim((string) ($payload['logo_url'] ?? '')),
            'favicon_url' => trim((string) ($payload['favicon_url'] ?? '')),
            'login_background_url' => trim((string) ($payload['login_background_url'] ?? '')),
            'pwa_icon_192_url' => trim((string) ($payload['pwa_icon_192_url'] ?? '')),
            'pwa_icon_512_url' => trim((string) ($payload['pwa_icon_512_url'] ?? '')),
            'color_primary' => trim((string) ($payload['color_primary'] ?? '')),
            'color_secondary' => trim((string) ($payload['color_secondary'] ?? '')),
            'color_accent' => trim((string) ($payload['color_accent'] ?? '')),
            'sidebar_style' => trim((string) ($payload['sidebar_style'] ?? 'default')),
            'login_style' => trim((string) ($payload['login_style'] ?? 'default')),
            'tema' => trim((string) ($payload['tema'] ?? '')),
            'activo' => 1,
        ];
    }

    private function procesarUploadsBrandingHotel(array $hotel, array &$data) {
        $errores = [];
        $mapa = [
            'logo_file' => ['tipo' => 'logo', 'campo' => 'logo_url'],
            'favicon_file' => ['tipo' => 'favicon', 'campo' => 'favicon_url'],
            'login_background_file' => ['tipo' => 'login_bg', 'campo' => 'login_background_url'],
            'pwa_icon_192_file' => ['tipo' => 'pwa_icon_192', 'campo' => 'pwa_icon_192_url'],
            'pwa_icon_512_file' => ['tipo' => 'pwa_icon_512', 'campo' => 'pwa_icon_512_url'],
        ];

        foreach ($mapa as $input => $config) {
            if (empty($_FILES[$input]) || ($_FILES[$input]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            if (!function_exists('hotel_branding_upload_asset')) {
                $errores[] = 'El helper de upload de branding no esta disponible.';
                continue;
            }

            $resultado = hotel_branding_upload_asset($_FILES[$input], $hotel['slug'] ?? '', $config['tipo']);
            if (empty($resultado['success'])) {
                $errores[] = $resultado['error'] ?? 'No se pudo subir la imagen. Intenta con otro archivo.';
                continue;
            }

            if (!empty($resultado['uploaded']) && !empty($resultado['path'])) {
                $data[$config['campo']] = $resultado['path'];
            }
        }

        return $errores;
    }

    private function registrarAccion($tipo, $descripcion) {
        $db = Database::getInstance();
        $db->query(
            "INSERT INTO logs_acceso (tipo, usuario_id, exitoso, ip, user_agent, detalles, created_at) 
             VALUES (?, ?, 1, ?, ?, ?, NOW())",
            [$tipo, user_id(), get_client_ip(), $_SERVER['HTTP_USER_AGENT'] ?? '', $descripcion]
        );
    }
}
