<?php
/**
 * Reglas legales de nomina versionadas por ejercicio (Fase 5 nomina core).
 *
 * Catalogo GLOBAL por pais (sin hotel_id). La ESCRITURA es exclusiva del
 * panel Medisoft (saas_admins); los negocios solo resuelven la regla vigente
 * a una fecha. Todo cambio queda en nomina_reglas_legales_eventos.
 *
 * Regla de oro: ningun valor legal vive en codigo. El motor fiscal (Fase 6)
 * consume este servicio y congela lo aplicado en el snapshot del periodo.
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class NominaReglasLegalesService {

    private $db;
    private $pdo;

    /** Tipos de regla reconocidos (extensible sin migracion: VARCHAR). */
    public const TIPOS_SUGERIDOS = [
        'uma_diaria', 'salario_minimo_general', 'salario_minimo_frontera',
        'aguinaldo_dias_minimo', 'prima_vacacional_pct', 'vacaciones_tabla',
        'isr_tabla_mensual', 'isr_tabla_quincenal', 'isr_tabla_semanal',
        'subsidio_empleo_tabla', 'imss_cuotas_obrero', 'imss_cuotas_patron',
    ];

    public function __construct(?Database $db = null) {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    public function listar(array $filtros = []): array {
        $sql = "SELECT * FROM nomina_reglas_legales WHERE 1=1";
        $params = [];

        if (!empty($filtros['pais'])) {
            $sql .= " AND pais = ?";
            $params[] = strtoupper((string) $filtros['pais']);
        }
        if (!empty($filtros['tipo_regla'])) {
            $sql .= " AND tipo_regla = ?";
            $params[] = (string) $filtros['tipo_regla'];
        }
        if (!empty($filtros['ejercicio'])) {
            $sql .= " AND ejercicio = ?";
            $params[] = (int) $filtros['ejercicio'];
        }

        $sql .= " ORDER BY tipo_regla ASC, vigente_desde DESC";

        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** Resuelve la regla vigente de un tipo a una fecha (o null). */
    public function vigente(string $pais, string $tipoRegla, ?string $fecha = null): ?array {
        $fecha = $fecha ?: date('Y-m-d');

        $st = $this->pdo->prepare(
            "SELECT * FROM nomina_reglas_legales
             WHERE pais = ? AND tipo_regla = ? AND estado = 'activo'
               AND vigente_desde <= ?
               AND (vigente_hasta IS NULL OR vigente_hasta >= ?)
             ORDER BY vigente_desde DESC, id DESC
             LIMIT 1"
        );
        $st->execute([strtoupper($pais), $tipoRegla, $fecha, $fecha]);
        $regla = $st->fetch();
        return $regla ?: null;
    }

    /** Resuelve varias reglas de golpe: ['uma_diaria' => row|null, ...]. */
    public function vigentes(string $pais, array $tipos, ?string $fecha = null): array {
        $resultado = [];
        foreach ($tipos as $tipo) {
            $resultado[$tipo] = $this->vigente($pais, (string) $tipo, $fecha);
        }
        return $resultado;
    }

    /**
     * Crea una version nueva de una regla (SOLO panel SaaS). Cierra la
     * vigencia abierta anterior del mismo tipo/pais (desde - 1 dia).
     */
    public function crear(array $datos, ?int $usuarioId = null): int {
        $pais = strtoupper(trim((string) ($datos['pais'] ?? 'MX')));
        if (strlen($pais) !== 2) {
            throw new Exception('Pais no valido (codigo de 2 letras).');
        }

        $tipo = trim((string) ($datos['tipo_regla'] ?? ''));
        if ($tipo === '' || !preg_match('/^[a-z0-9_]{3,40}$/', $tipo)) {
            throw new Exception('Tipo de regla no valido (snake_case, 3-40 caracteres).');
        }

        $desde = $this->normalizarFecha((string) ($datos['vigente_desde'] ?? ''));
        $ejercicio = (int) ($datos['ejercicio'] ?? (int) substr($desde, 0, 4));
        if ($ejercicio < 2000 || $ejercicio > 2100) {
            throw new Exception('Ejercicio fiscal no valido.');
        }

        $valor = null;
        if (isset($datos['valor']) && $datos['valor'] !== '') {
            $valor = round((float) $datos['valor'], 4);
            if ($valor < 0) {
                throw new Exception('El valor no puede ser negativo.');
            }
        }

        $valoresJson = null;
        if (isset($datos['valores_json']) && trim((string) $datos['valores_json']) !== '') {
            $decodificado = json_decode((string) $datos['valores_json'], true);
            if (!is_array($decodificado)) {
                throw new Exception('valores_json no es JSON valido.');
            }
            $valoresJson = json_encode($decodificado, JSON_UNESCAPED_UNICODE);
        }

        if ($valor === null && $valoresJson === null) {
            throw new Exception('Captura un valor escalar o una tabla JSON.');
        }

        $fuente = trim((string) ($datos['fuente'] ?? ''));
        if ($fuente === '') {
            throw new Exception('La fuente oficial es obligatoria (ej. DOF con fecha).');
        }

        $descripcion = trim((string) ($datos['descripcion'] ?? ''));

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            // Cerrar la vigencia abierta anterior del mismo tipo.
            $st = $this->pdo->prepare(
                "SELECT * FROM nomina_reglas_legales
                 WHERE pais = ? AND tipo_regla = ? AND estado = 'activo' AND vigente_hasta IS NULL
                 ORDER BY vigente_desde DESC LIMIT 1 FOR UPDATE"
            );
            $st->execute([$pais, $tipo]);
            $anterior = $st->fetch();

            if ($anterior) {
                if ($desde <= $anterior['vigente_desde']) {
                    throw new Exception('La nueva vigencia debe ser posterior a la vigente (' . $anterior['vigente_desde'] . ').');
                }
                $st = $this->pdo->prepare(
                    "UPDATE nomina_reglas_legales SET vigente_hasta = ?, updated_by = ? WHERE id = ?"
                );
                $st->execute([date('Y-m-d', strtotime($desde . ' -1 day')), $usuarioId, $anterior['id']]);
            }

            $st = $this->pdo->prepare(
                "INSERT INTO nomina_reglas_legales
                    (pais, tipo_regla, ejercicio, vigente_desde, valor, valores_json, descripcion, fuente, estado, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?)"
            );
            $st->execute([
                $pais, $tipo, $ejercicio, $desde,
                $valor !== null ? number_format($valor, 4, '.', '') : null,
                $valoresJson,
                $descripcion !== '' ? mb_substr($descripcion, 0, 200) : null,
                mb_substr($fuente, 0, 200),
                $usuarioId,
            ]);

            $reglaId = (int) $this->pdo->lastInsertId();

            $this->registrarEvento($reglaId, 'creada',
                $anterior ? ['regla_anterior_id' => (int) $anterior['id'], 'valor' => $anterior['valor']] : null,
                ['tipo_regla' => $tipo, 'ejercicio' => $ejercicio, 'vigente_desde' => $desde, 'valor' => $valor, 'fuente' => $fuente],
                $usuarioId);

            AuditService::record('nomina.regla_legal_creada', [
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'nomina_reglas_legales',
                'entidad_id' => (string) $reglaId,
                'descripcion' => 'Regla legal ' . $tipo . ' (' . $pais . ' ' . $ejercicio . ') vigente desde ' . $desde,
            ]);

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return $reglaId;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    public function alternarEstado(int $reglaId, ?int $usuarioId = null): bool {
        $st = $this->pdo->prepare("SELECT * FROM nomina_reglas_legales WHERE id = ?");
        $st->execute([$reglaId]);
        $regla = $st->fetch();
        if (!$regla) {
            throw new Exception('La regla no existe.');
        }

        $nuevoEstado = $regla['estado'] === 'activo' ? 'inactivo' : 'activo';

        $st = $this->pdo->prepare(
            "UPDATE nomina_reglas_legales SET estado = ?, updated_by = ? WHERE id = ?"
        );
        $st->execute([$nuevoEstado, $usuarioId, $reglaId]);

        $this->registrarEvento($reglaId, $nuevoEstado === 'activo' ? 'reactivada' : 'desactivada',
            ['estado' => $regla['estado']], ['estado' => $nuevoEstado], $usuarioId);

        AuditService::record('nomina.regla_legal_' . ($nuevoEstado === 'activo' ? 'reactivada' : 'desactivada'), [
            'usuario_id' => $usuarioId,
            'entidad_tipo' => 'nomina_reglas_legales',
            'entidad_id' => (string) $reglaId,
            'descripcion' => 'Regla ' . $regla['tipo_regla'] . ' (' . $regla['pais'] . ') ' . $nuevoEstado,
        ]);

        return true;
    }

    public function eventos(int $reglaId): array {
        $st = $this->pdo->prepare(
            "SELECT e.*, u.nombre_completo AS usuario_nombre
             FROM nomina_reglas_legales_eventos e
             LEFT JOIN usuarios u ON u.id = e.created_by
             WHERE e.regla_id = ? ORDER BY e.created_at DESC, e.id DESC"
        );
        $st->execute([$reglaId]);
        return $st->fetchAll();
    }

    /* ------------------------------------------------------------------ */

    private function registrarEvento(int $reglaId, string $accion, ?array $antes, ?array $despues, ?int $usuarioId): void {
        $st = $this->pdo->prepare(
            "INSERT INTO nomina_reglas_legales_eventos (regla_id, accion, datos_antes, datos_despues, created_by)
             VALUES (?, ?, ?, ?, ?)"
        );
        $st->execute([
            $reglaId,
            $accion,
            $antes !== null ? json_encode($antes, JSON_UNESCAPED_UNICODE) : null,
            $despues !== null ? json_encode($despues, JSON_UNESCAPED_UNICODE) : null,
            $usuarioId,
        ]);
    }

    private function normalizarFecha(string $valor): string {
        $fecha = DateTime::createFromFormat('Y-m-d', $valor);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            throw new Exception('Fecha no valida (formato requerido: AAAA-MM-DD).');
        }
        return $valor;
    }
}
