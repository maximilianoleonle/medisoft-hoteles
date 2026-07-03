<?php

/**
 * CheckinDigitalService (bloque checkin_digital, $199)
 *
 * Pre-registro del huesped: recepcion (o el motor de reservas) genera un link
 * con token por reservacion; el huesped llena sus datos y sube su ID antes de
 * llegar. Al completarse se actualiza el huesped de la reservacion y el ID
 * queda en storage PRIVADO (no accesible por web), descargable solo con sesion.
 */
class CheckinDigitalService
{
    private $db;
    private $pdo;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
    }

    /** Genera (o reusa) el link de una reservacion. Devuelve el token o null. */
    public function generarLink(int $hotelId, int $reservacionId, ?int $usuarioId = null): ?string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, fecha_salida, estado FROM reservaciones WHERE id = ? AND hotel_id = ? LIMIT 1"
            );
            $stmt->execute([$reservacionId, $hotelId]);
            $reservacion = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$reservacion || in_array((string) $reservacion['estado'], ['cancelada', 'completada'], true)) {
                return null;
            }

            // Reusar el link existente si sigue vigente.
            $stmt = $this->pdo->prepare(
                "SELECT token, estado, expires_at FROM checkin_digital_links
                 WHERE hotel_id = ? AND reservacion_id = ? LIMIT 1"
            );
            $stmt->execute([$hotelId, $reservacionId]);
            $existente = $stmt->fetch(PDO::FETCH_ASSOC);

            $expira = date('Y-m-d 23:59:59', strtotime((string) $reservacion['fecha_salida']));

            if ($existente) {
                // Refrescar expiracion y reactivar si estaba expirado (no si ya se completo).
                if ($existente['estado'] !== 'completado') {
                    $this->pdo->prepare(
                        "UPDATE checkin_digital_links SET estado = 'pendiente', expires_at = ?, updated_at = NOW()
                         WHERE hotel_id = ? AND reservacion_id = ?"
                    )->execute([$expira, $hotelId, $reservacionId]);
                }
                return (string) $existente['token'];
            }

            $token = bin2hex(random_bytes(16));
            $this->pdo->prepare(
                "INSERT INTO checkin_digital_links
                    (hotel_id, reservacion_id, token, estado, expires_at, creado_por, created_at, updated_at)
                 VALUES (?, ?, ?, 'pendiente', ?, ?, NOW(), NOW())"
            )->execute([$hotelId, $reservacionId, $token, $expira, $usuarioId]);

            return $token;
        } catch (Throwable $e) {
            error_log('Checkin digital: error al generar link (reservacion ' . $reservacionId . '): ' . $e->getMessage());
            return null;
        }
    }

    /** Link + datos de la reservacion para la pagina publica, o null. */
    public function porToken(int $hotelId, string $token): ?array
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $token)) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT l.*, r.fecha_entrada, r.fecha_salida, r.estado AS reservacion_estado,
                        h.nombre_completo, h.telefono, h.email, h.procedencia_estado, h.procedencia_ciudad,
                        h.id AS huesped_id
                 FROM checkin_digital_links l
                 INNER JOIN reservaciones r ON r.id = l.reservacion_id AND r.hotel_id = l.hotel_id
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 WHERE l.hotel_id = ? AND l.token = ?
                 LIMIT 1"
            );
            $stmt->execute([$hotelId, $token]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$fila) {
                return null;
            }

            if ($fila['estado'] === 'pendiente' && strtotime((string) $fila['expires_at']) < time()) {
                $this->pdo->prepare("UPDATE checkin_digital_links SET estado = 'expirado' WHERE id = ?")
                    ->execute([(int) $fila['id']]);
                $fila['estado'] = 'expirado';
            }

            return $fila;
        } catch (Throwable $e) {
            error_log('Checkin digital: error al leer token: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Completa el pre-registro: valida datos, guarda el ID en storage privado
     * y actualiza el huesped. Devuelve ['success', 'message'].
     */
    public function completar(int $hotelId, string $token, array $datos, ?array $archivo): array
    {
        $link = $this->porToken($hotelId, $token);
        if (!$link) {
            return ['success' => false, 'message' => 'Link no valido.'];
        }
        if ($link['estado'] === 'completado') {
            return ['success' => true, 'message' => 'Tu registro ya estaba completo.'];
        }
        if ($link['estado'] === 'expirado') {
            return ['success' => false, 'message' => 'Este link ya expiro. Pide uno nuevo al hotel.'];
        }

        $nombre = trim((string) ($datos['nombre'] ?? ''));
        $telefono = trim((string) ($datos['telefono'] ?? ''));
        $email = strtolower(trim((string) ($datos['email'] ?? '')));
        $procedenciaEstado = trim((string) ($datos['procedencia_estado'] ?? ''));
        $procedenciaCiudad = trim((string) ($datos['procedencia_ciudad'] ?? ''));

        if (mb_strlen($nombre) < 5 || strpos($nombre, ' ') === false) {
            return ['success' => false, 'message' => 'Escribe tu nombre completo (nombre y apellido).'];
        }
        if (strlen(preg_replace('/\D/', '', $telefono)) < 10) {
            return ['success' => false, 'message' => 'Escribe un telefono valido de 10 digitos.'];
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'El correo no es valido.'];
        }

        // ID obligatoria en V1 (es el valor del pre-registro).
        $rutaId = $this->guardarIdentificacion($hotelId, (int) $link['reservacion_id'], $archivo);
        if ($rutaId === null) {
            return ['success' => false, 'message' => 'Sube una foto legible de tu identificacion (JPG, PNG o PDF, maximo 5 MB).'];
        }

        try {
            $this->pdo->prepare(
                "UPDATE huespedes
                 SET nombre_completo = ?, telefono = ?, email = ?,
                     procedencia_estado = ?, procedencia_ciudad = ?, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?"
            )->execute([
                mb_substr($nombre, 0, 150),
                mb_substr($telefono, 0, 20),
                $email !== '' ? mb_substr($email, 0, 120) : null,
                $procedenciaEstado !== '' ? mb_substr($procedenciaEstado, 0, 80) : null,
                $procedenciaCiudad !== '' ? mb_substr($procedenciaCiudad, 0, 80) : null,
                (int) $link['huesped_id'],
                $hotelId,
            ]);

            $this->pdo->prepare(
                "UPDATE checkin_digital_links
                 SET estado = 'completado', datos_json = ?, id_documento_path = ?, completado_at = NOW(), updated_at = NOW()
                 WHERE id = ?"
            )->execute([
                json_encode([
                    'nombre' => $nombre,
                    'telefono' => $telefono,
                    'email' => $email,
                    'procedencia_estado' => $procedenciaEstado,
                    'procedencia_ciudad' => $procedenciaCiudad,
                ], JSON_UNESCAPED_UNICODE),
                $rutaId,
                (int) $link['id'],
            ]);

            return ['success' => true, 'message' => 'Listo. Tu registro quedo completo; al llegar solo confirmas y recibes tu llave.'];
        } catch (Throwable $e) {
            error_log('Checkin digital: error al completar (link ' . $link['id'] . '): ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo guardar tu registro. Intenta de nuevo.'];
        }
    }

    /** Reservaciones proximas del hotel con el estado de su link. */
    public function tablero(int $hotelId): array
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT r.id AS reservacion_id, r.fecha_entrada, r.fecha_salida, r.estado AS reservacion_estado,
                        h.nombre_completo, h.telefono,
                        l.token, l.estado AS link_estado, l.completado_at, l.id_documento_path
                 FROM reservaciones r
                 INNER JOIN huespedes h ON h.id = r.huesped_id
                 LEFT JOIN checkin_digital_links l ON l.reservacion_id = r.id AND l.hotel_id = r.hotel_id
                 WHERE r.hotel_id = ?
                   AND r.estado IN ('confirmada', 'pendiente')
                   AND r.fecha_entrada >= CURDATE() - INTERVAL 1 DAY
                 ORDER BY r.fecha_entrada ASC, r.id ASC
                 LIMIT 100"
            );
            $stmt->execute([$hotelId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log('Checkin digital: error al listar tablero: ' . $e->getMessage());
            return [];
        }
    }

    /** Ruta absoluta del ID de una reservacion (para descarga interna), o null. */
    public function rutaIdentificacion(int $hotelId, int $reservacionId): ?string
    {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id_documento_path FROM checkin_digital_links
                 WHERE hotel_id = ? AND reservacion_id = ? AND estado = 'completado' LIMIT 1"
            );
            $stmt->execute([$hotelId, $reservacionId]);
            $ruta = $stmt->fetchColumn();

            if (!$ruta) {
                return null;
            }

            $absoluta = $this->baseStorage() . '/' . ltrim((string) $ruta, '/');
            $real = realpath($absoluta);

            // Confinar al directorio de storage (path traversal).
            return ($real && strpos($real, realpath($this->baseStorage())) === 0 && is_file($real)) ? $real : null;
        } catch (Throwable $e) {
            return null;
        }
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function guardarIdentificacion(int $hotelId, int $reservacionId, ?array $archivo): ?string
    {
        if (!$archivo || ($archivo['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return null;
        }
        if ((int) $archivo['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $mime = function_exists('mime_content_type') ? mime_content_type($archivo['tmp_name']) : '';
        $extensiones = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'application/pdf' => 'pdf',
        ];
        if (!isset($extensiones[$mime])) {
            return null;
        }

        $dir = $this->baseStorage() . '/checkin_ids/hotel_' . $hotelId;
        if (!is_dir($dir) && !mkdir($dir, 0750, true)) {
            error_log('Checkin digital: no se pudo crear directorio de IDs: ' . $dir);
            return null;
        }

        $nombre = 'id_r' . $reservacionId . '_' . bin2hex(random_bytes(6)) . '.' . $extensiones[$mime];
        $destino = $dir . '/' . $nombre;

        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            error_log('Checkin digital: fallo move_uploaded_file a ' . $destino);
            return null;
        }

        // Ruta relativa al storage (privado, fuera de public_html).
        return 'checkin_ids/hotel_' . $hotelId . '/' . $nombre;
    }

    private function baseStorage(): string
    {
        if (defined('STORAGE_PATH')) {
            return STORAGE_PATH;
        }

        return dirname(__DIR__, 2) . '/storage';
    }
}
