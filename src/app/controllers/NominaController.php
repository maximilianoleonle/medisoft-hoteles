<?php
/**
 * Nomina core (bloque nomina_avanzada) - Fase 1: base tecnica.
 *
 * Dashboard informativo y configuracion de nomina por negocio (tenant).
 * El motor de calculo, catalogos e incidencias llegan en fases posteriores.
 * Este controlador NO toca Caja, snapshots de pre-nomina ni pagos: la
 * operacion laboral vigente sigue viviendo en /trabajadores (bloque personal).
 */

require_once __DIR__ . '/../services/AuditService.php';

class NominaController extends Controller {

    /** Valores permitidos de la configuracion (whitelist de servidor). */
    private $modosValidos = ['simplificada', 'hibrida', 'legal'];
    private $paisesValidos = ['MX'];
    private $redondeosValidos = ['centavos', 'pesos'];

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('nomina_avanzada');
        }

        if (function_exists('require_permission')) {
            require_permission('nomina.view');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = $this->hotelIdActual();
        $config = $this->configuracionActual($hotelId);

        $personalActivo = function_exists('hotel_has_module') && hotel_has_module('personal', $hotelId);
        $stats = $this->estadisticasBase($hotelId, $personalActivo);

        View::renderTemplate('nomina/index', [
            'title' => 'Nomina - ' . current_hotel_display_name(),
            'config' => $config,
            'personalActivo' => $personalActivo,
            'stats' => $stats,
            'puedeConfigurar' => can('nomina.configurar'),
        ]);
    }

    public function configuracionAction() {
        if (function_exists('require_permission')) {
            require_permission('nomina.configurar');
        }

        $hotelId = $this->hotelIdActual();

        View::renderTemplate('nomina/configuracion', [
            'title' => 'Configuracion de nomina - ' . current_hotel_display_name(),
            'config' => $this->configuracionActual($hotelId),
            'modos' => $this->modosValidos,
            'redondeos' => $this->redondeosValidos,
        ]);
    }

    public function guardarConfiguracionAction() {
        if (!$this->isPost()) {
            $this->redirect('nomina/configuracion');
        }

        $this->validateCSRF();

        if (function_exists('require_permission')) {
            require_permission('nomina.configurar');
        }

        $hotelId = $this->hotelIdActual();
        $antes = $this->configuracionActual($hotelId);

        $modo = (string) $this->getPost('modo', $antes['modo']);
        $pais = strtoupper(trim((string) $this->getPost('pais', $antes['pais'])));
        $redondeo = (string) $this->getPost('redondeo', $antes['redondeo']);

        $nuevos = [
            'modo' => in_array($modo, $this->modosValidos, true) ? $modo : 'simplificada',
            'pais' => in_array($pais, $this->paisesValidos, true) ? $pais : 'MX',
            'redondeo' => in_array($redondeo, $this->redondeosValidos, true) ? $redondeo : 'centavos',
            'permitir_horas_extra' => $this->getPost('permitir_horas_extra') ? true : false,
            'permitir_descuentos_manuales' => $this->getPost('permitir_descuentos_manuales') ? true : false,
            'requiere_aprobacion_cierre' => $this->getPost('requiere_aprobacion_cierre') ? true : false,
            'permitir_reapertura' => $this->getPost('permitir_reapertura') ? true : false,
        ];

        $claves = [
            'nomina.modo' => ['valor' => $nuevos['modo'], 'tipo' => 'string'],
            'nomina.pais' => ['valor' => $nuevos['pais'], 'tipo' => 'string'],
            'nomina.redondeo' => ['valor' => $nuevos['redondeo'], 'tipo' => 'string'],
            'nomina.permitir_horas_extra' => ['valor' => $nuevos['permitir_horas_extra'] ? '1' : '0', 'tipo' => 'boolean'],
            'nomina.permitir_descuentos_manuales' => ['valor' => $nuevos['permitir_descuentos_manuales'] ? '1' : '0', 'tipo' => 'boolean'],
            'nomina.requiere_aprobacion_cierre' => ['valor' => $nuevos['requiere_aprobacion_cierre'] ? '1' : '0', 'tipo' => 'boolean'],
            'nomina.permitir_reapertura' => ['valor' => $nuevos['permitir_reapertura'] ? '1' : '0', 'tipo' => 'boolean'],
        ];

        $ok = true;

        try {
            $db = Database::getInstance();

            foreach ($claves as $clave => $dato) {
                $resultado = $db->query(
                    "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
                     VALUES (?, ?, ?, ?, 'nomina', 1, NOW(), NOW())
                     ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), updated_at = NOW()",
                    [$hotelId, $clave, $dato['valor'], $dato['tipo']]
                );

                if ($resultado === false) {
                    throw new Exception('No se pudo guardar la clave ' . $clave);
                }
            }

            if (function_exists('hotel_config_cache_invalidar')) {
                hotel_config_cache_invalidar($hotelId);
            }
        } catch (Throwable $e) {
            if (function_exists('ms_log')) {
                ms_log('error', 'Nomina: error al guardar configuracion', ['error' => $e->getMessage()]);
            }
            $ok = false;
        }

        if ($ok && $antes !== $nuevos) {
            AuditService::record('nomina.configuracion_actualizada', [
                'hotel_id' => $hotelId,
                'usuario_id' => user_id(),
                'entidad_tipo' => 'nomina_configuracion',
                'entidad_id' => (string) $hotelId,
                'descripcion' => 'Actualizo la configuracion de nomina del negocio',
                'datos_antes' => $antes,
                'datos_despues' => $nuevos,
            ]);
        }

        set_mensaje(
            $ok ? 'Configuracion de nomina guardada.' : 'No se pudo guardar la configuracion de nomina.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('nomina/configuracion');
    }

    /* ------------------------------------------------------------------ */

    private function hotelIdActual() {
        return (int) obtenerHotelIdActualCompat();
    }

    /**
     * Lee la configuracion vigente de nomina del tenant (con defaults del
     * registry). Siempre con hotelId explicito: nunca confiar en el request.
     */
    private function configuracionActual($hotelId) {
        return [
            'modo' => (string) ConfiguracionHotelRegistry::get('nomina.modo', 'simplificada', $hotelId),
            'pais' => (string) ConfiguracionHotelRegistry::get('nomina.pais', 'MX', $hotelId),
            'redondeo' => (string) ConfiguracionHotelRegistry::get('nomina.redondeo', 'centavos', $hotelId),
            'permitir_horas_extra' => ConfiguracionHotelRegistry::getBool('nomina.permitir_horas_extra', true, $hotelId),
            'permitir_descuentos_manuales' => ConfiguracionHotelRegistry::getBool('nomina.permitir_descuentos_manuales', true, $hotelId),
            'requiere_aprobacion_cierre' => ConfiguracionHotelRegistry::getBool('nomina.requiere_aprobacion_cierre', true, $hotelId),
            'permitir_reapertura' => ConfiguracionHotelRegistry::getBool('nomina.permitir_reapertura', false, $hotelId),
        ];
    }

    /**
     * Metricas informativas del dashboard leidas del subsistema laboral
     * existente (bloque personal). Defensivo: si las tablas no estan o la
     * consulta falla, el dashboard muestra estado vacio sin romper.
     */
    private function estadisticasBase($hotelId, $personalActivo) {
        $stats = [
            'trabajadores_activos' => null,
            'periodos_registrados' => null,
            'ultimo_periodo' => null,
        ];

        if (!$personalActivo) {
            return $stats;
        }

        try {
            $db = Database::getInstance();

            $st = $db->query(
                "SELECT COUNT(*) AS total FROM trabajadores WHERE hotel_id = ? AND estado = 'activo'",
                [$hotelId]
            );
            if ($st !== false) {
                $fila = $st->fetch();
                $stats['trabajadores_activos'] = (int) ($fila['total'] ?? 0);
            }

            $st = $db->query(
                "SELECT COUNT(*) AS total FROM trabajador_nomina_periodos WHERE hotel_id = ?",
                [$hotelId]
            );
            if ($st !== false) {
                $fila = $st->fetch();
                $stats['periodos_registrados'] = (int) ($fila['total'] ?? 0);
            }

            $st = $db->query(
                "SELECT etiqueta, tipo_periodo, fecha_inicio, fecha_fin, estado
                 FROM trabajador_nomina_periodos
                 WHERE hotel_id = ?
                 ORDER BY fecha_fin DESC, id DESC
                 LIMIT 1",
                [$hotelId]
            );
            if ($st !== false) {
                $fila = $st->fetch();
                $stats['ultimo_periodo'] = $fila ?: null;
            }
        } catch (Throwable $e) {
            if (function_exists('ms_log')) {
                ms_log('warning', 'Nomina: no se pudieron leer estadisticas base', ['error' => $e->getMessage()]);
            }
        }

        return $stats;
    }
}
