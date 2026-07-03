<?php

require_once __DIR__ . '/MotorDisponibilidadService.php';
require_once __DIR__ . '/MotorPasarelaService.php';

/**
 * MotorReservaOnlineService
 *
 * Orquesta el flujo publico: hold temporal -> checkout de pasarela -> (webhook)
 * reservacion confirmada. El dinero queda en motor_pagos_online (estado 'pagado')
 * y recepcion lo concilia a Caja despues via AnticipoService (Fase 5).
 *
 * REGLA: aqui NUNCA se insertan movimientos de Caja ni abonos.
 * Requiere TenantContext::setHotel() previo (los modelos se scopean por contexto).
 */
class MotorReservaOnlineService
{
    private const HOLD_MINUTOS = 20;

    private $db;
    private $pdo;
    private $pasarela;
    private $disponibilidad;

    public function __construct(?Database $db = null)
    {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
        $this->pasarela = new MotorPasarelaService($this->db);
        $this->disponibilidad = new MotorDisponibilidadService();
    }

    // ───────────────────────── Paso: iniciar pago ─────────────────────────

    /**
     * Valida la solicitud, aparta una habitacion (hold) y crea el checkout.
     * $input: entrada, salida, personas, tipo, nombre, telefono, email.
     * Devuelve ['success' => bool, 'checkout_url' => ?, 'message' => ?].
     */
    public function iniciarPago(array $hotel, array $input, array $urls): array
    {
        $hotelId = (int) $hotel['id'];

        $nombre = trim((string) ($input['nombre'] ?? ''));
        $telefono = trim((string) ($input['telefono'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $tipo = trim((string) ($input['tipo'] ?? ''));
        $entrada = trim((string) ($input['entrada'] ?? ''));
        $salida = trim((string) ($input['salida'] ?? ''));
        $personas = max(1, (int) ($input['personas'] ?? 1));

        if (mb_strlen($nombre) < 5 || strpos($nombre, ' ') === false) {
            return ['success' => false, 'message' => 'Escribe tu nombre y apellido.'];
        }
        if (strlen(preg_replace('/\D/', '', $telefono)) < 10) {
            return ['success' => false, 'message' => 'Escribe un telefono valido de al menos 10 digitos.'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Escribe un correo electronico valido.'];
        }
        if ($tipo === '') {
            return ['success' => false, 'message' => 'Selecciona un tipo de habitacion.'];
        }

        $rango = $this->disponibilidad->validarRango($hotelId, $entrada, $salida);
        if (!$rango['ok']) {
            return ['success' => false, 'message' => $rango['error']];
        }

        $this->liberarHoldsExpirados($hotelId);

        // Elegir la habitacion concreta: la mas economica disponible del tipo sin hold vigente.
        $habitacion = $this->elegirHabitacion($hotelId, $tipo, $entrada, $salida, $personas);
        if (!$habitacion) {
            return ['success' => false, 'message' => 'Ese tipo de habitacion ya no esta disponible para tus fechas. Elige otro.'];
        }

        // Precio y anticipo SIEMPRE del lado servidor.
        $precios = $this->precioHabitacion($habitacion, $entrada, $salida, max(1, (int) $rango['noches']));
        $anticipo = $this->disponibilidad->calcularAnticipo($hotelId, $precios['total'], $precios['primera_noche']);

        $holdToken = bin2hex(random_bytes(16));

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO motor_holds (hotel_id, token, habitacion_ids_json, fecha_entrada, fecha_salida, expires_at, created_at)
                 VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL ? MINUTE), NOW())"
            );
            $stmt->execute([
                $hotelId,
                $holdToken,
                json_encode([(int) $habitacion['id']]),
                $entrada,
                $salida,
                self::HOLD_MINUTOS,
            ]);
            $holdId = (int) $this->pdo->lastInsertId();
        } catch (Throwable $e) {
            error_log('Motor online: no se pudo crear hold (hotel ' . $hotelId . '): ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo apartar la habitacion. Intenta de nuevo.'];
        }

        // Las URLs de retorno llevan el token del hold ({HOLD} se reemplaza aqui).
        $urls = [
            'success' => str_replace('{HOLD}', $holdToken, (string) ($urls['success'] ?? '')),
            'cancel' => str_replace('{HOLD}', $holdToken, (string) ($urls['cancel'] ?? '')),
        ];

        $checkout = $this->pasarela->crearCheckout($hotelId, [
            'monto' => $anticipo,
            'moneda' => (string) ($hotel['moneda_codigo'] ?? 'MXN'),
            'concepto' => 'Anticipo de reservacion - ' . ($hotel['nombre'] ?? 'Hotel'),
            'hold_token' => $holdToken,
            'email' => $email,
        ], $urls);

        if (empty($checkout['ok'])) {
            $this->pdo->prepare("DELETE FROM motor_holds WHERE id = ?")->execute([$holdId]);
            return ['success' => false, 'message' => $checkout['error'] ?? 'Los pagos en linea no estan disponibles por el momento.'];
        }

        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO motor_pagos_online
                    (hotel_id, proveedor, proveedor_pago_id, monto, moneda, estado,
                     huesped_nombre, huesped_email, huesped_telefono, payload_json, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, 'pendiente', ?, ?, ?, ?, NOW(), NOW())"
            );
            $stmt->execute([
                $hotelId,
                $checkout['proveedor'],
                $checkout['proveedor_pago_id'],
                number_format($anticipo, 2, '.', ''),
                strtoupper((string) ($hotel['moneda_codigo'] ?? 'MXN')),
                mb_substr($nombre, 0, 150),
                mb_substr($email, 0, 120),
                mb_substr($telefono, 0, 30),
                json_encode([
                    'hold_token' => $holdToken,
                    'entrada' => $entrada,
                    'salida' => $salida,
                    'personas' => $personas,
                    'tipo' => $tipo,
                    'habitacion_id' => (int) $habitacion['id'],
                    'precio_total_estancia' => $precios['total'],
                    'anticipo' => $anticipo,
                ], JSON_UNESCAPED_UNICODE),
            ]);
            $pagoId = (int) $this->pdo->lastInsertId();

            $this->pdo->prepare("UPDATE motor_holds SET pago_online_id = ? WHERE id = ?")->execute([$pagoId, $holdId]);
        } catch (Throwable $e) {
            error_log('Motor online: no se pudo registrar pago pendiente (hotel ' . $hotelId . '): ' . $e->getMessage());
            $this->pdo->prepare("DELETE FROM motor_holds WHERE id = ?")->execute([$holdId]);
            return ['success' => false, 'message' => 'No se pudo iniciar el pago. Intenta de nuevo.'];
        }

        return [
            'success' => true,
            'checkout_url' => $checkout['checkout_url'],
            'hold_token' => $holdToken,
        ];
    }

    // ───────────────────────── Paso: confirmar (webhook) ─────────────────────────

    /**
     * Confirma el pago identificado por hold_token: re-verifica disponibilidad,
     * crea huesped + reservacion y marca el ledger como 'pagado'.
     * Idempotente: si el pago ya no esta 'pendiente', responde ok sin duplicar.
     * $referenciaCobro: id cobrable en la pasarela (payment_intent / payment id) para reembolsos.
     */
    public function confirmarPagoPorHold(int $hotelId, string $holdToken, string $referenciaCobro, array $evento): array
    {
        $pago = $this->pagoPorHoldToken($hotelId, $holdToken);
        if (!$pago) {
            return ['success' => false, 'message' => 'Pago no encontrado para este hotel.'];
        }

        if (($pago['estado'] ?? '') !== 'pendiente') {
            return ['success' => true, 'message' => 'Pago ya procesado.', 'reservacion_id' => (int) ($pago['reservacion_id'] ?? 0)];
        }

        // Claim atomico: solo un webhook concurrente puede pasar de aqui. Evita que
        // dos entregas simultaneas de la pasarela creen dos reservaciones para el mismo pago.
        $claim = $this->pdo->prepare(
            "UPDATE motor_pagos_online SET estado = 'procesando', updated_at = NOW()
             WHERE id = ? AND estado = 'pendiente'"
        );
        $claim->execute([(int) $pago['id']]);
        if ($claim->rowCount() !== 1) {
            return ['success' => true, 'message' => 'Pago en proceso o ya procesado.', 'reservacion_id' => (int) ($pago['reservacion_id'] ?? 0)];
        }

        $payload = json_decode((string) ($pago['payload_json'] ?? ''), true) ?: [];
        $habitacionId = (int) ($payload['habitacion_id'] ?? 0);
        $entrada = (string) ($payload['entrada'] ?? '');
        $salida = (string) ($payload['salida'] ?? '');

        if ($habitacionId <= 0 || $entrada === '' || $salida === '') {
            $this->actualizarEstadoPago((int) $pago['id'], 'fallido', $evento);
            return ['success' => false, 'message' => 'Pago con datos incompletos.'];
        }

        // Defensa en profundidad: verificar que lo realmente cobrado por la pasarela no
        // sea menor al anticipo esperado (el monto lo fija el servidor; una discrepancia
        // a la baja es sospechosa). Sobrepago se acepta; formato desconocido no bloquea.
        $pagado = $this->montoPagadoDelEvento($evento);
        $esperado = (float) $pago['monto'];
        if ($pagado !== null && $pagado + 0.01 < $esperado) {
            $this->actualizarEstadoPago((int) $pago['id'], 'fallido', $evento + [
                'motivo' => 'Monto cobrado ($' . number_format($pagado, 2) . ') menor al anticipo esperado ($' . number_format($esperado, 2) . ').',
                'referencia_cobro' => $referenciaCobro,
            ]);
            $this->eliminarHold($holdToken);
            error_log('Motor online: pago ' . $pago['id'] . ' con monto insuficiente (cobrado ' . $pagado . ' < esperado ' . $esperado . '); atencion manual.');
            return ['success' => false, 'message' => 'Monto cobrado insuficiente; requiere atencion manual.'];
        }

        // Re-verificacion final de disponibilidad (carrera entre dos pagos).
        $reservacionModel = new Reservacion();
        $sigueDisponible = $reservacionModel->verificarDisponibilidadMultiple([$habitacionId], $entrada, $salida);

        if (!$sigueDisponible) {
            $cred = $this->pasarela->credenciales($hotelId);
            $reembolsado = $cred ? $this->pasarela->reembolsar($cred, $referenciaCobro) : false;
            $this->actualizarEstadoPago((int) $pago['id'], $reembolsado ? 'reembolsado' : 'fallido', $evento + [
                'motivo' => 'Habitacion ya no disponible al confirmar el pago.',
                'reembolso_automatico' => $reembolsado,
                'referencia_cobro' => $referenciaCobro,
            ]);
            $this->eliminarHold($holdToken);
            error_log('Motor online: pago ' . $pago['id'] . ' sin disponibilidad al confirmar; reembolso ' . ($reembolsado ? 'OK' : 'PENDIENTE MANUAL'));
            return ['success' => false, 'message' => 'Habitacion no disponible; pago marcado para reembolso.'];
        }

        try {
            $huespedId = $this->buscarOCrearHuesped($hotelId, $pago);
            $habitacion = $this->habitacionDelHotel($hotelId, $habitacionId);
            if (!$habitacion) {
                throw new Exception('Habitacion ' . $habitacionId . ' no encontrada para el hotel.');
            }

            $noches = max(1, (int) round((strtotime($salida) - strtotime($entrada)) / 86400));
            $precios = $this->precioHabitacion($habitacion, $entrada, $salida, $noches);
            $habitacion['precio_calculado'] = $precios['total'];

            $notas = sprintf(
                'Reserva online (motor). Pago %s ref %s. Anticipo pagado $%s via pasarela, PENDIENTE DE CONCILIAR EN CAJA.',
                (string) $pago['proveedor'],
                (string) $pago['proveedor_pago_id'],
                number_format((float) $pago['monto'], 2)
            );

            // crearConHabitaciones maneja su PROPIA transaccion: no anidar otra aqui.
            // Devuelve ['success' => true, 'id' => N] o false.
            $creacion = $reservacionModel->crearConHabitaciones(
                [
                    'huesped_id' => $huespedId,
                    'fecha_entrada' => $entrada,
                    'fecha_salida' => $salida,
                    'precio_total' => $precios['total'],
                    'estado' => 'confirmada',
                    'notas' => $notas,
                ],
                [$habitacion],
                []
            );

            $reservacionId = is_array($creacion) ? (int) ($creacion['id'] ?? 0) : 0;
            if (empty($creacion['success']) || $reservacionId <= 0) {
                throw new Exception('crearConHabitaciones no devolvio id de reservacion.');
            }

            $stmt = $this->pdo->prepare(
                "UPDATE motor_pagos_online
                 SET estado = 'pagado', reservacion_id = ?,
                     payload_json = ?, updated_at = NOW()
                 WHERE id = ? AND estado = 'procesando'"
            );
            $stmt->execute([
                (int) $reservacionId,
                json_encode($payload + ['evento_pasarela' => $this->resumirEvento($evento), 'referencia_cobro' => $referenciaCobro], JSON_UNESCAPED_UNICODE),
                (int) $pago['id'],
            ]);

            $this->eliminarHold($holdToken);

            return ['success' => true, 'reservacion_id' => (int) $reservacionId];
        } catch (Throwable $e) {
            error_log('Motor online: error al confirmar pago ' . $pago['id'] . ': ' . $e->getMessage());
            // Devolver el pago a 'pendiente' (solo si seguimos duenos del claim) para que
            // el tablero interno (F5) lo muestre y un reintento del webhook lo reprocese.
            try {
                $this->pdo->prepare(
                    "UPDATE motor_pagos_online SET estado = 'pendiente', updated_at = NOW()
                     WHERE id = ? AND estado = 'procesando'"
                )->execute([(int) $pago['id']]);
            } catch (Throwable $e2) {
                error_log('Motor online: no se pudo revertir el claim del pago ' . $pago['id'] . ': ' . $e2->getMessage());
            }
            return ['success' => false, 'message' => 'Error al crear la reservacion; requiere atencion manual.'];
        }
    }

    // ───────────────────────── Consultas publicas ─────────────────────────

    /** Estado del pago para la pagina de confirmacion (por hold token). */
    public function estadoPorHoldToken(int $hotelId, string $holdToken): ?array
    {
        $pago = $this->pagoPorHoldToken($hotelId, $holdToken);
        if (!$pago) {
            return null;
        }

        $payload = json_decode((string) ($pago['payload_json'] ?? ''), true) ?: [];

        return [
            'estado' => (string) $pago['estado'],
            'reservacion_id' => (int) ($pago['reservacion_id'] ?? 0),
            'monto' => (float) $pago['monto'],
            'moneda' => (string) $pago['moneda'],
            'nombre' => (string) ($pago['huesped_nombre'] ?? ''),
            'entrada' => (string) ($payload['entrada'] ?? ''),
            'salida' => (string) ($payload['salida'] ?? ''),
            'tipo' => (string) ($payload['tipo'] ?? ''),
            'precio_total_estancia' => (float) ($payload['precio_total_estancia'] ?? 0),
        ];
    }

    public function pagoPorHoldToken(int $hotelId, string $holdToken)
    {
        if (!preg_match('/^[a-f0-9]{32}$/', $holdToken)) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                "SELECT * FROM motor_pagos_online
                 WHERE hotel_id = ?
                   AND JSON_UNQUOTE(JSON_EXTRACT(payload_json, '$.hold_token')) = ?
                 ORDER BY id DESC
                 LIMIT 1"
            );
            $stmt->execute([$hotelId, $holdToken]);
            $pago = $stmt->fetch(PDO::FETCH_ASSOC);
            return $pago ?: null;
        } catch (Throwable $e) {
            error_log('Motor online: error al buscar pago por hold: ' . $e->getMessage());
            return null;
        }
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function elegirHabitacion(int $hotelId, string $tipo, string $entrada, string $salida, int $personas): ?array
    {
        $habitacionModel = new Habitacion();
        $disponibles = $habitacionModel->disponiblesEntreFechas($entrada, $salida);

        $candidatas = array_filter($disponibles, function ($hab) use ($tipo, $personas) {
            return (string) ($hab['tipo'] ?? '') === $tipo
                && (int) ($hab['capacidad_personas'] ?? 0) >= $personas;
        });

        if (empty($candidatas)) {
            return null;
        }

        // Excluir habitaciones con hold vigente de otras personas para las mismas fechas.
        $conHold = [];
        try {
            $stmt = $this->pdo->prepare(
                "SELECT habitacion_ids_json FROM motor_holds
                 WHERE hotel_id = ? AND expires_at > NOW()
                   AND fecha_entrada < ? AND fecha_salida > ?"
            );
            $stmt->execute([$hotelId, $salida, $entrada]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $jsonIds) {
                foreach ((array) json_decode((string) $jsonIds, true) as $id) {
                    $conHold[(int) $id] = true;
                }
            }
        } catch (Throwable $e) {
            error_log('Motor online: no se pudieron leer holds vigentes: ' . $e->getMessage());
        }

        $candidatas = array_filter($candidatas, function ($hab) use ($conHold) {
            return !isset($conHold[(int) $hab['id']]);
        });

        if (empty($candidatas)) {
            return null;
        }

        usort($candidatas, function ($a, $b) {
            return (float) $a['precio_base'] <=> (float) $b['precio_base'];
        });

        return $candidatas[0];
    }

    /** Precio de la estancia por habitacion con tarifas dinamicas (mismo calculo interno). */
    private function precioHabitacion(array $habitacion, string $entrada, string $salida, int $noches): array
    {
        $tarifaModel = new IncrementoTarifa();
        $total = 0.0;
        $primera = 0.0;
        $fecha = new DateTime($entrada);

        for ($i = 0; $i < $noches; $i++) {
            $info = $tarifaModel->calcularPrecioConIncremento(
                (int) $habitacion['id'],
                (string) $habitacion['tipo'],
                (float) $habitacion['precio_base'],
                $fecha->format('Y-m-d')
            );
            $precioNoche = (float) ($info['precio_final'] ?? $habitacion['precio_base']);
            $total += $precioNoche;
            if ($i === 0) {
                $primera = $precioNoche;
            }
            $fecha->modify('+1 day');
        }

        return ['total' => round($total, 2), 'primera_noche' => round($primera, 2)];
    }

    private function buscarOCrearHuesped(int $hotelId, array $pago): int
    {
        $telefono = trim((string) ($pago['huesped_telefono'] ?? ''));
        $email = trim((string) ($pago['huesped_email'] ?? ''));

        $stmt = $this->pdo->prepare(
            "SELECT id FROM huespedes
             WHERE hotel_id = ? AND (telefono = ? OR (email <> '' AND email IS NOT NULL AND email = ?))
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$hotelId, $telefono, $email]);
        $existente = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existente) {
            return (int) $existente['id'];
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO huespedes (hotel_id, nombre_completo, telefono, email, notas, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'Creado desde el motor de reservas online.', NOW(), NOW())"
        );
        $stmt->execute([
            $hotelId,
            mb_substr((string) ($pago['huesped_nombre'] ?? 'Huesped online'), 0, 150),
            mb_substr($telefono, 0, 20),
            mb_substr($email, 0, 120),
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function habitacionDelHotel(int $hotelId, int $habitacionId): ?array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, numero, tipo, precio_base FROM habitaciones WHERE id = ? AND hotel_id = ? LIMIT 1"
        );
        $stmt->execute([$habitacionId, $hotelId]);
        $hab = $stmt->fetch(PDO::FETCH_ASSOC);
        return $hab ?: null;
    }

    private function actualizarEstadoPago(int $pagoId, string $estado, array $evento): void
    {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE motor_pagos_online
                 SET estado = ?, payload_json = JSON_MERGE_PATCH(COALESCE(payload_json, '{}'), ?), updated_at = NOW()
                 WHERE id = ?"
            );
            $stmt->execute([$estado, json_encode(['evento_pasarela' => $this->resumirEvento($evento)], JSON_UNESCAPED_UNICODE), $pagoId]);
        } catch (Throwable $e) {
            error_log('Motor online: no se pudo actualizar estado del pago ' . $pagoId . ': ' . $e->getMessage());
        }
    }

    private function eliminarHold(string $holdToken): void
    {
        try {
            $this->pdo->prepare("DELETE FROM motor_holds WHERE token = ?")->execute([$holdToken]);
        } catch (Throwable $e) {
            error_log('Motor online: no se pudo eliminar hold ' . $holdToken . ': ' . $e->getMessage());
        }
    }

    private function liberarHoldsExpirados(int $hotelId): void
    {
        try {
            $this->pdo->prepare("DELETE FROM motor_holds WHERE hotel_id = ? AND expires_at < NOW()")->execute([$hotelId]);
        } catch (Throwable $e) {
            error_log('Motor online: no se pudieron liberar holds expirados: ' . $e->getMessage());
        }
    }

    /**
     * Monto realmente cobrado segun el evento de la pasarela, en unidades mayores
     * (pesos, no centavos). Devuelve null si el formato no se reconoce (no bloquear).
     * Stripe: data.object.amount_total en centavos. MercadoPago: transaction_amount ya en pesos.
     */
    private function montoPagadoDelEvento(array $evento): ?float
    {
        $stripe = $evento['data']['object']['amount_total'] ?? null;
        if ($stripe !== null && is_numeric($stripe)) {
            return round(((float) $stripe) / 100, 2);
        }

        $mp = $evento['transaction_amount'] ?? null;
        if ($mp !== null && is_numeric($mp)) {
            return round((float) $mp, 2);
        }

        return null;
    }

    /** Solo lo esencial del evento (los payloads de pasarela son enormes). */
    private function resumirEvento(array $evento): array
    {
        return [
            'id' => (string) ($evento['id'] ?? ''),
            'type' => (string) ($evento['type'] ?? ($evento['action'] ?? '')),
            'status' => (string) ($evento['data']['object']['payment_status'] ?? ($evento['status'] ?? '')),
            'recibido_at' => date('Y-m-d H:i:s'),
        ];
    }
}
