<?php

/**
 * MotorCuponService (bloque promociones, $129)
 *
 * Cupones de descuento del motor de reservas online. Reglas de dinero:
 * - El descuento se valida y aplica SIEMPRE en servidor (iniciarPago).
 * - El cupon aplicado se congela en motor_pagos_online.payload_json y se
 *   re-aplica en la confirmacion sobre el precio recalculado en servidor.
 * - El uso se consume UNA sola vez, al crear la reservacion (dentro del
 *   claim atomico del webhook); el limite se valida al iniciar el pago.
 * - Total minimo con descuento: $50 (protege el minimo de cobro de pasarela).
 */
class MotorCuponService
{
    public const TOTAL_MINIMO = 50.0;

    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /** Normaliza un codigo capturado; devuelve '' si el formato no es valido. */
    public function normalizar(string $codigo): string
    {
        $codigo = strtoupper(trim($codigo));

        return preg_match('/^[A-Z0-9][A-Z0-9_-]{2,29}$/', $codigo) ? $codigo : '';
    }

    /**
     * Valida un codigo para uso HOY. Devuelve
     * ['ok' => bool, 'cupon' => ?array, 'motivo' => ?string].
     */
    public function validar(int $hotelId, string $codigo): array
    {
        $codigo = $this->normalizar($codigo);
        if ($codigo === '') {
            return ['ok' => false, 'cupon' => null, 'motivo' => 'El codigo no es valido.'];
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM motor_cupones WHERE hotel_id = ? AND codigo = ? LIMIT 1"
            );
            $stmt->execute([$hotelId, $codigo]);
            $cupon = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('Cupones: error al validar codigo (hotel ' . $hotelId . '): ' . $e->getMessage());
            return ['ok' => false, 'cupon' => null, 'motivo' => 'No se pudo validar el codigo. Intenta de nuevo.'];
        }

        if (!$cupon || !(int) $cupon['activo']) {
            return ['ok' => false, 'cupon' => null, 'motivo' => 'Ese codigo no existe o ya no esta activo.'];
        }

        $hoy = date('Y-m-d');
        if (!empty($cupon['vigente_desde']) && $hoy < $cupon['vigente_desde']) {
            return ['ok' => false, 'cupon' => null, 'motivo' => 'Ese codigo todavia no esta vigente.'];
        }
        if (!empty($cupon['vigente_hasta']) && $hoy > $cupon['vigente_hasta']) {
            return ['ok' => false, 'cupon' => null, 'motivo' => 'Ese codigo ya expiro.'];
        }
        if ($cupon['limite_usos'] !== null && (int) $cupon['usos'] >= (int) $cupon['limite_usos']) {
            return ['ok' => false, 'cupon' => null, 'motivo' => 'Ese codigo ya alcanzo su limite de usos.'];
        }

        return ['ok' => true, 'cupon' => $cupon, 'motivo' => null];
    }

    /**
     * Aplica el descuento a los precios de la estancia. La primera noche se
     * descuenta proporcionalmente para que el calculo del anticipo (que puede
     * basarse en ella) sea consistente. Devuelve
     * ['ok' => bool, 'total' => float, 'primera_noche' => float, 'descuento' => float, 'motivo' => ?string].
     */
    public function aplicar(array $cupon, float $total, float $primeraNoche): array
    {
        $tipo = (string) ($cupon['tipo'] ?? 'porcentaje');
        $valor = (float) ($cupon['valor'] ?? 0);

        if ($tipo === 'porcentaje') {
            $valor = max(0.0, min(100.0, $valor));
            $descuento = round($total * $valor / 100, 2);
        } else {
            $descuento = round(min(max(0.0, $valor), $total), 2);
        }

        $totalDesc = round($total - $descuento, 2);
        if ($descuento <= 0) {
            return ['ok' => false, 'total' => $total, 'primera_noche' => $primeraNoche, 'descuento' => 0.0, 'motivo' => 'El codigo no genera descuento para esta reserva.'];
        }
        if ($totalDesc < self::TOTAL_MINIMO) {
            return ['ok' => false, 'total' => $total, 'primera_noche' => $primeraNoche, 'descuento' => 0.0, 'motivo' => 'El codigo no aplica a esta reserva.'];
        }

        $factor = $total > 0 ? $totalDesc / $total : 1.0;

        return [
            'ok' => true,
            'total' => $totalDesc,
            'primera_noche' => round($primeraNoche * $factor, 2),
            'descuento' => $descuento,
            'motivo' => null,
        ];
    }

    /** Texto amable del beneficio, para la pagina publica. */
    public function descripcion(array $cupon): string
    {
        if ((string) $cupon['tipo'] === 'porcentaje') {
            return rtrim(rtrim(number_format((float) $cupon['valor'], 2), '0'), '.') . '% de descuento';
        }

        return '$' . number_format((float) $cupon['valor'], 2) . ' de descuento';
    }

    /**
     * Consume un uso de forma ATOMICA: solo incrementa si aun queda cupo
     * (`usos < limite_usos`). Sin esta guardia, N pagos concurrentes del mismo
     * codigo que validaron cuando `usos` aun estaba bajo se confirmaban todos y
     * canjeaban un cupon de `limite_usos=1` N veces (sobre-redencion). Mismo
     * patron que LealtadService/CopilotoService. Devuelve true si se consumio un
     * uso; false si el cupon ya estaba agotado o hubo error.
     */
    public function consumir(int $cuponId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE motor_cupones
                 SET usos = usos + 1, updated_at = NOW()
                 WHERE id = ? AND (limite_usos IS NULL OR usos < limite_usos)"
            );
            $stmt->execute([$cuponId]);
            return $stmt->rowCount() === 1;
        } catch (Throwable $e) {
            error_log('Cupones: no se pudo consumir uso del cupon ' . $cuponId . ': ' . $e->getMessage());
            return false;
        }
    }

    // ───────────────────────── CRUD interno ─────────────────────────

    public function listar(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM motor_cupones WHERE hotel_id = ? ORDER BY activo DESC, id DESC LIMIT 200"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Cupones: error al listar (hotel ' . $hotelId . '): ' . $e->getMessage());
            return [];
        }
    }

    /** Crea un cupon. Devuelve ['success', 'message']. */
    public function crear(int $hotelId, array $datos, ?int $usuarioId = null): array
    {
        $codigo = $this->normalizar((string) ($datos['codigo'] ?? ''));
        if ($codigo === '') {
            return ['success' => false, 'message' => 'El codigo debe tener de 3 a 30 letras, numeros o guiones.'];
        }

        $tipo = (string) ($datos['tipo'] ?? 'porcentaje');
        if (!in_array($tipo, ['porcentaje', 'monto'], true)) {
            $tipo = 'porcentaje';
        }

        $valor = (float) str_replace(',', '', (string) ($datos['valor'] ?? 0));
        if ($tipo === 'porcentaje' && ($valor < 1 || $valor > 100)) {
            return ['success' => false, 'message' => 'El porcentaje debe estar entre 1 y 100.'];
        }
        if ($tipo === 'monto' && $valor < 1) {
            return ['success' => false, 'message' => 'El monto del descuento debe ser mayor a cero.'];
        }

        $desde = trim((string) ($datos['vigente_desde'] ?? ''));
        $hasta = trim((string) ($datos['vigente_hasta'] ?? ''));
        $desde = preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) ? $desde : null;
        $hasta = preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta) ? $hasta : null;
        if ($desde && $hasta && $hasta < $desde) {
            return ['success' => false, 'message' => 'La fecha de fin de vigencia no puede ser antes del inicio.'];
        }

        $limite = trim((string) ($datos['limite_usos'] ?? ''));
        $limite = ($limite !== '' && ctype_digit($limite) && (int) $limite > 0) ? (int) $limite : null;

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO motor_cupones
                    (hotel_id, codigo, tipo, valor, vigente_desde, vigente_hasta, limite_usos, activo, creado_por, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $hotelId,
                $codigo,
                $tipo,
                number_format($valor, 2, '.', ''),
                $desde,
                $hasta,
                $limite,
                $usuarioId,
            ]);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'uk_motor_cupones_codigo') !== false || strpos($e->getMessage(), 'Duplicate') !== false) {
                return ['success' => false, 'message' => 'Ya existe un cupon con el codigo ' . $codigo . '.'];
            }
            error_log('Cupones: error al crear (hotel ' . $hotelId . '): ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo crear el cupon.'];
        }

        return ['success' => true, 'message' => 'Cupon ' . $codigo . ' creado y activo.'];
    }

    /** Activa/desactiva un cupon del hotel. */
    public function alternar(int $hotelId, int $cuponId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE motor_cupones SET activo = 1 - activo, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?"
            );
            $stmt->execute([$cuponId, $hotelId]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('Cupones: error al alternar cupon ' . $cuponId . ': ' . $e->getMessage());
            return false;
        }
    }
}
