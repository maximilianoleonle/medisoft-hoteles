<?php
/**
 * Modelo Sync — Procesamiento de operaciones offline
 * Los Cedros
 *
 * Aplica operaciones encoladas en el cliente (IndexedDB) cuando
 * el dispositivo recupera conexión. Usa UUID para idempotencia:
 * si la misma operación llega dos veces, la segunda se ignora.
 */
class Sync
{
    private Database $db;
    private array $reservacionesTemporales = [];

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->asegurarTablaOperacionesSync();
    }

    // =========================================================================
    // PUNTO DE ENTRADA PRINCIPAL
    // =========================================================================

    /**
     * Procesa un lote de operaciones enviadas desde el cliente.
     *
     * @param  array $operaciones  Array de operaciones con formato:
     *   [{ uuid, tipo, payload, timestamp, usuario_id }, ...]
     * @param  int|null $usuarioActualId Usuario autenticado que solicita la sincronizacion.
     * @return array  { exitosas: [...uuids], fallidas: [{uuid, error}] }
     */
    public function procesarLote(array $operaciones, ?int $usuarioActualId = null): array
    {
        // Ordenar por timestamp del cliente para respetar el orden real
        usort($operaciones, fn($a, $b) => ($a['timestamp'] ?? 0) <=> ($b['timestamp'] ?? 0));

        $exitosas = [];
        $fallidas = [];

        foreach ($operaciones as $op) {
            $uuid = $op['uuid'] ?? null;

            if (!$uuid || !$this->uuidValido($uuid)) {
                $fallidas[] = ['uuid' => $uuid ?? 'sin-uuid', 'error' => 'UUID inválido o ausente'];
                continue;
            }

            // Idempotencia: si ya fue procesada, la contamos como exitosa y seguimos
            if ($this->yaFueProcesada($uuid)) {
                $exitosas[] = $uuid;
                continue;
            }

            try {
                $this->db->safeBeginTransaction();
                $this->validarOperacionAutorizada($op, $usuarioActualId);
                $resultado = $this->despachar($op);
                $this->registrarResultado($uuid, $op['tipo'] ?? 'desconocido', $op['usuario_id'] ?? null, 'ok', $resultado);
                $this->db->safeCommit();
                $exitosas[] = $uuid;
            } catch (Throwable $e) {
                $this->db->safeRollBack();
                $error = $e->getMessage();
                $this->registrarResultado($uuid, $op['tipo'] ?? 'desconocido', $op['usuario_id'] ?? null, 'error', $error);
                $fallidas[] = ['uuid' => $uuid, 'error' => $error];
                error_log("[Sync] Error en operación $uuid ({$op['tipo']}): $error");
            }
        }

        return ['exitosas' => $exitosas, 'fallidas' => $fallidas];
    }

    // =========================================================================
    // DESPACHADOR DE TIPOS
    // =========================================================================

    /**
     * Decide qué método ejecutar según el tipo de operación.
     */
    private function despachar(array $op): string
    {
        $tipo    = $op['tipo']    ?? '';
        $payload = $op['payload'] ?? [];
        $payload = $this->resolverReservacionTemporal($payload);

        return match ($tipo) {
            'crear_reservacion'        => $this->crearReservacion($payload, $op['usuario_id'] ?? null),
            'cambiar_estado_habitacion' => $this->cambiarEstadoHabitacion($payload),
            'checkin'                   => $this->hacerCheckin($payload),
            'checkout'                  => $this->hacerCheckout($payload),
            'pago_caja'                 => $this->registrarMovimientoCaja($payload, $op['usuario_id'] ?? null, 'ingreso'),
            'gasto_caja'                => $this->registrarMovimientoCaja($payload, $op['usuario_id'] ?? null, 'gasto'),
            default                     => throw new InvalidArgumentException("Tipo de operación desconocido: '$tipo'"),
        };
    }

    private function validarOperacionAutorizada(array $op, ?int $usuarioActualId): void
    {
        $tipo = $op['tipo'] ?? '';
        $usuarioOperacion = isset($op['usuario_id']) ? (int) $op['usuario_id'] : null;

        if (!$usuarioActualId || !$usuarioOperacion || $usuarioOperacion !== (int) $usuarioActualId) {
            throw new RuntimeException('Operacion offline rechazada por usuario invalido');
        }

        $permisos = [
            'crear_reservacion' => 'huespedes.create',
            'cambiar_estado_habitacion' => 'habitaciones.mantenimiento',
            'checkin' => 'habitaciones.checkin',
            'checkout' => 'habitaciones.checkout',
            'pago_caja' => 'caja.cobros',
            'gasto_caja' => 'caja.movimientos',
        ];

        if (!isset($permisos[$tipo])) {
            throw new InvalidArgumentException("Tipo de operacion desconocido: '$tipo'");
        }

        if (!function_exists('can') || !can($permisos[$tipo])) {
            throw new RuntimeException("No tiene permiso para sincronizar la operacion '$tipo'");
        }
    }

    // =========================================================================
    // OPERACIONES INDIVIDUALES
    // =========================================================================

    /**
     * Cambia el estado de una habitación.
     * Payload: { habitacion_id, estado_nuevo, motivo? }
     * Estados válidos: disponible | ocupada | mantenimiento | limpieza
     */
    /**
     * Crea una reservacion capturada offline.
     * Payload: {
     *   client_temp_id?, huesped_id, fecha_entrada, fecha_salida, hora_llegada?,
     *   habitaciones: [ids], cortesias?: [ids], notas?, estado?
     * }
     */
    private function crearReservacion(array $p, ?int $usuario_id): string
    {
        $this->requerir($p, ['huesped_id', 'fecha_entrada', 'fecha_salida', 'habitaciones']);

        $habitaciones_ids = $this->normalizarListaIds($p['habitaciones']);
        if (empty($habitaciones_ids)) {
            throw new InvalidArgumentException('Debe seleccionar al menos una habitacion');
        }

        $cortesias_ids = $this->normalizarListaIds($p['cortesias'] ?? []);

        if (!class_exists('Reservacion')) {
            require_once __DIR__ . '/Reservacion.php';
        }

        $reservacionModel = new Reservacion();
        $disponible = $reservacionModel->verificarDisponibilidadMultiple(
            $habitaciones_ids,
            $p['fecha_entrada'],
            $p['fecha_salida']
        );

        if (!$disponible) {
            throw new RuntimeException('Una o mas habitaciones ya no estan disponibles para esas fechas');
        }

        $placeholders = implode(',', array_fill(0, count($habitaciones_ids), '?'));
        $stmt = $this->db->query(
            "SELECT * FROM habitaciones WHERE id IN ($placeholders) AND activa = 1",
            $habitaciones_ids
        );
        $habitaciones = $stmt ? $stmt->fetchAll() : [];

        if (count($habitaciones) !== count($habitaciones_ids)) {
            throw new RuntimeException('Una o mas habitaciones no existen o estan inactivas');
        }

        $hora_llegada = $p['hora_llegada'] ?? $p['hora_llegada_estimada'] ?? '14:00';
        $calculo = $reservacionModel->calcularPrecioMultiple(
            $habitaciones,
            $p['fecha_entrada'],
            $p['fecha_salida'],
            $hora_llegada
        );

        $habitaciones_con_precio = $calculo['habitaciones_detalle'] ?? $habitaciones;
        $ids_cortesia = array_map('strval', $cortesias_ids);

        $precio_total = 0.0;
        foreach ($habitaciones_con_precio as $habitacion) {
            if (!in_array((string) $habitacion['id'], $ids_cortesia, true)) {
                $precio_total += (float) ($habitacion['precio_calculado'] ?? 0);
            }
        }

        $notas = trim((string) ($p['notas'] ?? ''));
        if (!empty($calculo['es_madrugada']) && $p['fecha_entrada'] === $p['fecha_salida']) {
            $nota_horario = "[LLEGADA EN MADRUGADA] Check-in estimado a las $hora_llegada. Check-out el mismo dia a las 12:00 PM.";
            $notas = $notas === '' ? $nota_horario : $notas . "\n\n" . $nota_horario;
        }

        $stmt = $this->db->query(
            "INSERT INTO reservaciones (
                huesped_id, fecha_entrada, fecha_salida, hora_llegada_estimada,
                precio_total, estado, notas, usuario_registro_id,
                total_habitaciones, habitaciones_cortesia, created_at, updated_at
             ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())",
            [
                (int) $p['huesped_id'],
                $p['fecha_entrada'],
                $p['fecha_salida'],
                $hora_llegada,
                $precio_total,
                $p['estado'] ?? 'confirmada',
                $notas,
                $usuario_id ?: 1,
                count($habitaciones_con_precio),
                count($cortesias_ids),
            ]
        );

        if (!$stmt) {
            throw new RuntimeException('No se pudo crear la reservacion offline en el servidor');
        }

        $reservacion_id = (int) $this->db->lastInsertId();
        if ($reservacion_id <= 0) {
            throw new RuntimeException('No se pudo obtener el ID de la reservacion creada');
        }

        foreach ($habitaciones_con_precio as $habitacion) {
            $es_cortesia = in_array((string) $habitacion['id'], $ids_cortesia, true) ? 1 : 0;
            $precio = $es_cortesia ? 0 : (float) ($habitacion['precio_calculado'] ?? 0);

            $ok = $this->db->query(
                "INSERT INTO reservacion_habitaciones
                 (reservacion_id, habitacion_id, precio, es_cortesia)
                 VALUES (?, ?, ?, ?)",
                [$reservacion_id, (int) $habitacion['id'], $precio, $es_cortesia]
            );

            if (!$ok) {
                throw new RuntimeException("No se pudo asociar la habitacion {$habitacion['id']} a la reservacion");
            }
        }

        $temp_id = $p['client_temp_id'] ?? $p['id_temporal'] ?? null;
        if ($temp_id) {
            $this->reservacionesTemporales[(string) $temp_id] = $reservacion_id;
        }

        return "Reservacion offline creada #{$reservacion_id}";
    }

    private function cambiarEstadoHabitacion(array $p): string
    {
        $this->requerir($p, ['habitacion_id', 'estado_nuevo']);

        $estados_validos = ['disponible', 'ocupada', 'mantenimiento', 'limpieza'];
        if (!in_array($p['estado_nuevo'], $estados_validos, true)) {
            throw new InvalidArgumentException("Estado inválido: '{$p['estado_nuevo']}'");
        }

        $stmt = $this->db->query(
            "SELECT id, estado FROM habitaciones WHERE id = ? AND activa = 1",
            [(int) $p['habitacion_id']]
        );
        $hab = $stmt ? $stmt->fetch() : null;

        if (!$hab) {
            throw new RuntimeException("Habitación {$p['habitacion_id']} no encontrada o inactiva");
        }

        $this->db->query(
            "UPDATE habitaciones SET estado = ?, updated_at = NOW() WHERE id = ?",
            [$p['estado_nuevo'], (int) $p['habitacion_id']]
        );

        return "Habitación {$p['habitacion_id']}: {$hab['estado']} → {$p['estado_nuevo']}";
    }

    /**
     * Registra el check-in de una reservación.
     * Payload: { reservacion_id, hora_real? }
     */
    private function hacerCheckin(array $p): string
    {
        $this->requerir($p, ['reservacion_id']);

        $stmt = $this->db->query(
            "SELECT id, estado FROM reservaciones WHERE id = ?",
            [(int) $p['reservacion_id']]
        );
        $res = $stmt ? $stmt->fetch() : null;

        if (!$res) {
            throw new RuntimeException("Reservación {$p['reservacion_id']} no encontrada");
        }

        // Si ya hizo check-in (sincronización duplicada), lo aceptamos silenciosamente
        if ($res['estado'] === 'checked_in') {
            return "Reservación {$p['reservacion_id']} ya tenía check-in — ignorado";
        }

        if ($res['estado'] !== 'confirmada') {
            throw new RuntimeException(
                "Reservación {$p['reservacion_id']} no está en estado 'confirmada' (estado actual: {$res['estado']})"
            );
        }

        $this->db->query(
            "UPDATE reservaciones SET estado = 'checked_in', updated_at = NOW() WHERE id = ?",
            [(int) $p['reservacion_id']]
        );

        // Marcar habitaciones como ocupadas
        $this->db->query(
            "UPDATE habitaciones h
             INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
             SET h.estado = 'ocupada', h.updated_at = NOW()
             WHERE rh.reservacion_id = ?",
            [(int) $p['reservacion_id']]
        );

        return "Check-in reservación {$p['reservacion_id']} aplicado";
    }

    /**
     * Registra el check-out de una reservación.
     * Payload: { reservacion_id, estado_habitacion_destino? }
     *   estado_habitacion_destino: 'limpieza' (default) | 'disponible'
     */
    private function hacerCheckout(array $p): string
    {
        $this->requerir($p, ['reservacion_id']);

        $stmt = $this->db->query(
            "SELECT id, estado FROM reservaciones WHERE id = ?",
            [(int) $p['reservacion_id']]
        );
        $res = $stmt ? $stmt->fetch() : null;

        if (!$res) {
            throw new RuntimeException("Reservación {$p['reservacion_id']} no encontrada");
        }

        if ($res['estado'] === 'completada') {
            return "Reservación {$p['reservacion_id']} ya tenía check-out — ignorado";
        }

        if (!in_array($res['estado'], ['checked_in', 'confirmada'], true)) {
            throw new RuntimeException(
                "Reservación {$p['reservacion_id']} no puede hacer check-out (estado: {$res['estado']})"
            );
        }

        $this->db->query(
            "UPDATE reservaciones SET estado = 'completada', updated_at = NOW() WHERE id = ?",
            [(int) $p['reservacion_id']]
        );

        // El estado destino de la habitación tras el checkout
        $estado_destino = in_array($p['estado_habitacion_destino'] ?? '', ['disponible', 'limpieza'], true)
            ? $p['estado_habitacion_destino']
            : 'limpieza';

        $this->db->query(
            "UPDATE habitaciones h
             INNER JOIN reservacion_habitaciones rh ON h.id = rh.habitacion_id
             SET h.estado = ?, h.updated_at = NOW()
             WHERE rh.reservacion_id = ?",
            [$estado_destino, (int) $p['reservacion_id']]
        );

        return "Check-out reservación {$p['reservacion_id']} — habitaciones → $estado_destino";
    }

    /**
     * Registra un pago en caja.
     * Payload: {
     *   monto, metodo_pago, descripcion,
     *   reservacion_id?, categoria?, referencia?
     * }
     */
    /**
     * Registra un ingreso o gasto en caja.
     * Payload: { monto, metodo_pago, descripcion, categoria?, referencia?, reservacion_id? }
     *
     * @param string $tipo_movimiento  'ingreso' | 'gasto'
     */
    private function registrarMovimientoCaja(array $p, ?int $usuario_id, string $tipo_movimiento): string
    {
        $this->requerir($p, ['monto', 'metodo_pago', 'descripcion']);

        $monto = (float) $p['monto'];
        if ($monto <= 0) {
            throw new InvalidArgumentException("El monto debe ser mayor a 0 (recibido: $monto)");
        }

        $metodos_validos = ['efectivo', 'tarjeta', 'transferencia'];
        if (!in_array($p['metodo_pago'], $metodos_validos, true)) {
            throw new InvalidArgumentException("Método de pago inválido: '{$p['metodo_pago']}'");
        }

        // Obtener el corte de caja abierto
        $stmt = $this->db->query(
            "SELECT id FROM cortes_caja WHERE estado = 'abierto' ORDER BY fecha_apertura DESC LIMIT 1"
        );
        $corte = $stmt ? $stmt->fetch() : null;

        if (!$corte) {
            throw new RuntimeException("No hay corte de caja abierto. Abre la caja antes de sincronizar.");
        }

        $categoria_default = $tipo_movimiento === 'ingreso' ? 'Hospedaje' : 'Gastos Generales';
        $categoria_id = !empty($p['categoria_id']) ? (int) $p['categoria_id'] : null;
        $categoria = $p['categoria'] ?? $categoria_default;

        if ($categoria_id) {
            $stmt = $this->db->query(
                "SELECT nombre FROM categorias_movimientos WHERE id = ? AND activa = 1 LIMIT 1",
                [$categoria_id]
            );
            $cat = $stmt ? $stmt->fetch() : null;
            if ($cat) {
                $categoria = $cat['nombre'];
            } else {
                $categoria_id = null;
            }
        }

        $this->db->query(
            "INSERT INTO movimientos_caja
             (tipo, categoria, categoria_id, descripcion, monto, metodo_pago, referencia,
              comprobante, proveedor, reservacion_id, usuario_id, corte_id, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
            [
                $tipo_movimiento,
                $categoria,
                $categoria_id,
                $p['descripcion'],
                $monto,
                $p['metodo_pago'],
                $p['referencia']    ?? null,
                $p['comprobante']   ?? null,
                $p['proveedor']     ?? null,
                isset($p['reservacion_id']) ? (int) $p['reservacion_id'] : null,
                $usuario_id,
                (int) $corte['id'],
            ]
        );

        $simbolo = $tipo_movimiento === 'ingreso' ? '+' : '-';
        return "{$simbolo}\${$monto} ({$p['metodo_pago']}) → {$tipo_movimiento} en corte {$corte['id']}";
    }

    // =========================================================================
    // HELPERS INTERNOS
    // =========================================================================

    /** Crea la tabla de idempotencia si la instalacion aun no la tiene. */
    private function asegurarTablaOperacionesSync(): void
    {
        $this->db->query(
            "CREATE TABLE IF NOT EXISTS operaciones_sync (
                uuid CHAR(36) NOT NULL,
                tipo VARCHAR(60) NOT NULL,
                usuario_id INT NULL,
                procesado_at DATETIME NOT NULL,
                resultado VARCHAR(20) NOT NULL,
                detalle TEXT NULL,
                PRIMARY KEY (uuid),
                INDEX idx_operaciones_sync_resultado (resultado),
                INDEX idx_operaciones_sync_usuario (usuario_id),
                INDEX idx_operaciones_sync_procesado (procesado_at)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    /** Verifica que el UUID no haya sido procesado antes */
    private function yaFueProcesada(string $uuid): bool
    {
        $stmt = $this->db->query(
            "SELECT uuid FROM operaciones_sync WHERE uuid = ? AND resultado = 'ok' LIMIT 1",
            [$uuid]
        );
        return $stmt && $stmt->fetch() !== false;
    }

    /** Guarda el resultado en el registro de operaciones */
    private function registrarResultado(
        string  $uuid,
        string  $tipo,
        ?int    $usuario_id,
        string  $resultado,
        string  $detalle
    ): void {
        $this->db->query(
            "INSERT INTO operaciones_sync (uuid, tipo, usuario_id, procesado_at, resultado, detalle)
             VALUES (?, ?, ?, NOW(), ?, ?)
             ON DUPLICATE KEY UPDATE
                tipo = VALUES(tipo),
                usuario_id = VALUES(usuario_id),
                procesado_at = VALUES(procesado_at),
                resultado = VALUES(resultado),
                detalle = VALUES(detalle)",
            [$uuid, $tipo, $usuario_id, $resultado, $detalle]
        );
    }

    /** Valida que el UUID tenga formato UUIDv4 */
    private function uuidValido(string $uuid): bool
    {
        return (bool) preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    /** Lanza excepción si faltan campos requeridos en el payload */
    private function requerir(array $payload, array $campos): void
    {
        foreach ($campos as $campo) {
            if (!isset($payload[$campo]) || $payload[$campo] === '' || $payload[$campo] === null) {
                throw new InvalidArgumentException("Campo requerido ausente en payload: '$campo'");
            }
        }
    }

    private function normalizarListaIds(mixed $valor): array
    {
        if ($valor === null || $valor === '') {
            return [];
        }

        if (!is_array($valor)) {
            $valor = [$valor];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn($id) => (int) $id,
            $valor
        ))));
    }

    private function resolverReservacionTemporal(array $payload): array
    {
        if (!isset($payload['reservacion_id'])) {
            return $payload;
        }

        $reservacion_id = $payload['reservacion_id'];
        if (is_numeric($reservacion_id)) {
            return $payload;
        }

        $clave = (string) $reservacion_id;
        if (!isset($this->reservacionesTemporales[$clave])) {
            throw new RuntimeException("Reservacion temporal {$clave} aun no fue creada en este lote");
        }

        $payload['reservacion_id'] = $this->reservacionesTemporales[$clave];
        return $payload;
    }
}
