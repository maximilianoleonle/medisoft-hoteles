<?php

/**
 * MotorExtraService (bloque upsells, $149)
 *
 * Extras vendibles en el motor de reservas (desayuno, late checkout, etc.).
 * Reglas de dinero (mismo patron que MotorCuponService):
 * - Los importes se calculan SIEMPRE en servidor con los precios del catalogo
 *   y las noches/personas validadas; el navegador solo manda ids.
 * - Lo aplicado se congela en motor_pagos_online.payload_json y la
 *   confirmacion usa los importes congelados (escritos por el servidor).
 * - El descuento de cupones NO aplica a extras: el cupon descuenta hospedaje.
 */
class MotorExtraService
{
    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /** Extras activos del hotel para la pagina publica. */
    public function activos(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, nombre, descripcion, precio, tipo_cobro
                 FROM motor_extras
                 WHERE hotel_id = ? AND activo = 1
                 ORDER BY orden ASC, id ASC
                 LIMIT 30"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Extras: error al listar activos (hotel ' . $hotelId . '): ' . $e->getMessage());
            return [];
        }
    }

    /** Multiplicador del tipo de cobro con noches/personas validadas en servidor. */
    public static function factor(string $tipoCobro, int $noches, int $personas): int
    {
        switch ($tipoCobro) {
            case 'por_noche':
                return max(1, $noches);
            case 'por_persona':
                return max(1, $personas);
            case 'por_persona_noche':
                return max(1, $noches) * max(1, $personas);
            case 'por_reserva':
            default:
                return 1;
        }
    }

    /**
     * Resuelve los extras elegidos (ids del navegador) contra el catalogo del
     * hotel y calcula importes en servidor. Ids desconocidos o inactivos se
     * ignoran en silencio. Devuelve ['extras' => [...congelables], 'total' => float].
     */
    public function calcular(int $hotelId, array $idsElegidos, int $noches, int $personas): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $idsElegidos), static function ($id) {
            return $id > 0;
        })));

        if (empty($ids)) {
            return ['extras' => [], 'total' => 0.0];
        }

        $catalogo = [];
        foreach ($this->activos($hotelId) as $extra) {
            $catalogo[(int) $extra['id']] = $extra;
        }

        $aplicados = [];
        $total = 0.0;

        foreach ($ids as $id) {
            if (!isset($catalogo[$id])) {
                continue;
            }
            $extra = $catalogo[$id];
            $factor = self::factor((string) $extra['tipo_cobro'], $noches, $personas);
            $importe = round((float) $extra['precio'] * $factor, 2);

            $aplicados[] = [
                'id' => $id,
                'nombre' => (string) $extra['nombre'],
                'precio' => (float) $extra['precio'],
                'tipo_cobro' => (string) $extra['tipo_cobro'],
                'factor' => $factor,
                'importe' => $importe,
            ];
            $total += $importe;
        }

        return ['extras' => $aplicados, 'total' => round($total, 2)];
    }

    /** Texto corto del tipo de cobro, para vistas. */
    public static function etiquetaTipo(string $tipoCobro): string
    {
        $map = [
            'por_reserva' => 'por reserva',
            'por_noche' => 'por noche',
            'por_persona' => 'por persona',
            'por_persona_noche' => 'por persona / noche',
        ];

        return $map[$tipoCobro] ?? $tipoCobro;
    }

    // ───────────────────────── CRUD interno ─────────────────────────

    public function listar(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM motor_extras WHERE hotel_id = ? ORDER BY activo DESC, orden ASC, id DESC LIMIT 100"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Extras: error al listar (hotel ' . $hotelId . '): ' . $e->getMessage());
            return [];
        }
    }

    /** Crea un extra. Devuelve ['success', 'message']. */
    public function crear(int $hotelId, array $datos, ?int $usuarioId = null): array
    {
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        if (mb_strlen($nombre) < 3) {
            return ['success' => false, 'message' => 'Escribe el nombre del extra (minimo 3 letras).'];
        }

        $precio = (float) str_replace(',', '', (string) ($datos['precio'] ?? 0));
        if ($precio < 1) {
            return ['success' => false, 'message' => 'El precio del extra debe ser mayor a cero.'];
        }

        $tipoCobro = (string) ($datos['tipo_cobro'] ?? 'por_reserva');
        if (!in_array($tipoCobro, ['por_reserva', 'por_noche', 'por_persona', 'por_persona_noche'], true)) {
            $tipoCobro = 'por_reserva';
        }

        $descripcion = trim((string) ($datos['descripcion'] ?? ''));

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO motor_extras
                    (hotel_id, nombre, descripcion, precio, tipo_cobro, orden, activo, creado_por, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $hotelId,
                mb_substr($nombre, 0, 100),
                $descripcion !== '' ? mb_substr($descripcion, 0, 255) : null,
                number_format($precio, 2, '.', ''),
                $tipoCobro,
                max(0, (int) ($datos['orden'] ?? 0)),
                $usuarioId,
            ]);
        } catch (Throwable $e) {
            error_log('Extras: error al crear (hotel ' . $hotelId . '): ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo crear el extra.'];
        }

        return ['success' => true, 'message' => 'Extra "' . $nombre . '" creado y visible en tu pagina de reservas.'];
    }

    /** Activa/desactiva un extra del hotel. */
    public function alternar(int $hotelId, int $extraId): bool
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE motor_extras SET activo = 1 - activo, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?"
            );
            $stmt->execute([$extraId, $hotelId]);
            return $stmt->rowCount() > 0;
        } catch (Throwable $e) {
            error_log('Extras: error al alternar extra ' . $extraId . ': ' . $e->getMessage());
            return false;
        }
    }
}
