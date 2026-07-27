<?php
/**
 * Invariantes del bloque reputacion (encuestas post-estancia).
 *
 * El caso que motiva esta suite: hasta jul-26 la invitacion a dejar resena en
 * Google solo se ofrecia a quien calificaba 4-5 estrellas. Eso es "review
 * gating" y la politica de contenido de Google Maps lo prohibe expresamente
 * (solicitar resenas de forma selectiva / desalentar las negativas), con riesgo
 * de sancion para el perfil del hotel. El modulo NO tenia suite propia, asi que
 * nada impedia que la condicion volviera a colarse.
 *
 * Lo que se fija aqui:
 *  1. Quien responde ve la invitacion a Google con CUALQUIER calificacion (1-5),
 *     siempre que el hotel haya configurado su enlace.
 *  2. Sin enlace configurado no se invita a nadie (no hay a donde mandarlo).
 *  3. La alerta interna por calificacion baja SIGUE viva y sigue dependiendo
 *     de su propio umbral: esa parte si puede discriminar, es privada.
 *  4. Los dos umbrales son independientes: mover el de alerta no cambia a
 *     quien se le ofrece Google.
 *  5. Tenancy: un token jamas cruza de hotel.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "ReputacionTest\n";

t_reset_db();

$db = Database::getInstance();

// El servicio no consulta hotel_modulos (el gate vive en el controller), pero
// la alerta interna llama a NotificacionService, que si exige el bloque
// notificaciones. Se siembran ambos como core para no depender de fixtures.
$db->query(
    "INSERT INTO modulos (clave, nombre, es_core, activo_global, orden, created_at)
     VALUES ('reputacion', 'Reputacion', 1, 1, 1, NOW()),
            ('notificaciones', 'Notificaciones', 1, 1, 2, NOW())"
);

$base = t_seed_base('rep-a');
$hotelId = $base['hotel_id'];
$usuarioId = $base['usuario_id'];

/** Deja la reservacion lista para encuesta (el servicio exige checkout). */
$seedCheckout = function (int $hotel) use ($db): int {
    $reservacionId = t_seed_reservacion($hotel, 1500.00);
    $db->query("UPDATE reservaciones SET estado = 'checked_out' WHERE id = ?", [$reservacionId]);
    return $reservacionId;
};

/**
 * Escribe una clave de configuracion del hotel (mismo upsert del controller)
 * y limpia el cache estatico del registry, que es privado y se llena en la
 * primera lectura de cada hotel: sin esto, cambiar un umbral a mitad del caso
 * no se ve y el test mide el valor viejo.
 */
$setConfig = function (int $hotel, string $clave, string $valor, string $tipo = 'string') use ($db): void {
    $db->query(
        "INSERT INTO hotel_configuracion (hotel_id, clave, valor, tipo, grupo, activo, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'reputacion', 1, NOW(), NOW())
         ON DUPLICATE KEY UPDATE valor = VALUES(valor), tipo = VALUES(tipo), updated_at = NOW()",
        [$hotel, $clave, $valor, $tipo]
    );
    if (function_exists('hotel_config_cache_invalidar')) {
        hotel_config_cache_invalidar($hotel);
    }
    $ref = new ReflectionProperty('ConfiguracionHotelRegistry', 'cache');
    $ref->setAccessible(true);
    $ref->setValue(null, []);
};

$servicio = new ReputacionService();

// ── 1. Con enlace configurado: TODA calificacion recibe la invitacion ─────
$setConfig($hotelId, 'reputacion.google_review_url', 'https://g.page/r/ejemplo/review');

foreach ([1, 2, 3, 4, 5] as $estrellas) {
    $reservacionId = $seedCheckout($hotelId);
    $token = $servicio->generarLink($hotelId, $reservacionId, $usuarioId);
    t_ok(is_string($token) && $token !== '', "genera token para reservacion con checkout ({$estrellas}*)");

    $resultado = $servicio->responder($hotelId, $token, ['calificacion' => $estrellas]);

    t_ok($resultado['success'], "responder con {$estrellas} estrellas guarda la respuesta");
    t_ok(
        $resultado['mostrar_google'] === true,
        "calificacion {$estrellas}: se ofrece la resena de Google (sin review gating)"
    );
}

// ── 2. Sin enlace configurado no se invita a nadie ────────────────────────
$base2 = t_seed_base('rep-sin-url');
$hotelSinUrl = $base2['hotel_id'];

$reservacionSinUrl = $seedCheckout($hotelSinUrl);
$tokenSinUrl = $servicio->generarLink($hotelSinUrl, $reservacionSinUrl, $base2['usuario_id']);
$resSinUrl = $servicio->responder($hotelSinUrl, $tokenSinUrl, ['calificacion' => 5]);

t_ok($resSinUrl['success'], 'la encuesta se guarda aunque el hotel no tenga enlace de Google');
t_eq(false, $resSinUrl['mostrar_google'], 'sin enlace configurado no se invita (ni con 5 estrellas)');

// ── 3. La alerta interna por calificacion baja sigue viva ─────────────────
// Umbral default = 3: una de 2 estrellas alerta, una de 5 no.
$contarAlertas = function (int $hotel) use ($db): int {
    $filas = $db->query(
        "SELECT COUNT(*) AS n FROM notificaciones
         WHERE hotel_id = ? AND tipo = 'calificacion_baja'",
        [$hotel]
    )->fetchAll();
    return (int) ($filas[0]['n'] ?? 0);
};

$base3 = t_seed_base('rep-alertas');
$hotelAlertas = $base3['hotel_id'];
$setConfig($hotelAlertas, 'reputacion.google_review_url', 'https://g.page/r/otro/review');

$resBaja = $servicio->responder(
    $hotelAlertas,
    $servicio->generarLink($hotelAlertas, $seedCheckout($hotelAlertas), $base3['usuario_id']),
    ['calificacion' => 2]
);
t_eq(1, $contarAlertas($hotelAlertas), 'calificacion 2 (<= umbral 3) dispara la alerta interna');
t_eq(true, $resBaja['mostrar_google'], 'la misma calificacion baja TAMBIEN recibe la invitacion a Google');

$servicio->responder(
    $hotelAlertas,
    $servicio->generarLink($hotelAlertas, $seedCheckout($hotelAlertas), $base3['usuario_id']),
    ['calificacion' => 5]
);
t_eq(1, $contarAlertas($hotelAlertas), 'calificacion 5 (> umbral) NO dispara alerta interna');

// ── 4. Los dos umbrales son independientes ────────────────────────────────
// Subir el umbral de alerta a 4 hace que una de 4 estrellas alerte, pero no
// altera a quien se le ofrece Google (a todos).
$setConfig($hotelAlertas, 'reputacion.umbral_alerta', '4', 'integer');

$resCuatro = $servicio->responder(
    $hotelAlertas,
    $servicio->generarLink($hotelAlertas, $seedCheckout($hotelAlertas), $base3['usuario_id']),
    ['calificacion' => 4]
);
t_eq(2, $contarAlertas($hotelAlertas), 'con umbral 4, una calificacion de 4 dispara alerta');
t_eq(true, $resCuatro['mostrar_google'], 'mover el umbral de alerta no cambia la invitacion a Google');

// ── 5. Respuesta repetida: conserva la invitacion y no duplica la respuesta ─
$reservacionRepetida = $seedCheckout($hotelId);
$tokenRepetido = $servicio->generarLink($hotelId, $reservacionRepetida, $usuarioId);
$servicio->responder($hotelId, $tokenRepetido, ['calificacion' => 1]);
$segundaVez = $servicio->responder($hotelId, $tokenRepetido, ['calificacion' => 5]);

t_eq(true, $segundaVez['mostrar_google'], 'quien reabre su liga ya respondida sigue viendo la invitacion');
$califFinal = $db->query(
    "SELECT calificacion FROM reputacion_encuestas WHERE token = ?",
    [$tokenRepetido]
)->fetchAll();
t_eq(1, (int) ($califFinal[0]['calificacion'] ?? 0), 'la segunda respuesta NO pisa la calificacion original');

// ── 6. Tenancy: el token no cruza de hotel ────────────────────────────────
t_eq(null, $servicio->porToken($hotelSinUrl, $tokenRepetido), 'un token no se lee desde otro hotel');
$resCruzado = $servicio->responder($hotelSinUrl, $tokenRepetido, ['calificacion' => 5]);
t_eq(false, $resCruzado['success'], 'no se puede responder un token de otro hotel');

// ── 7. Calificacion invalida no guarda ni invita ──────────────────────────
$reservacionInvalida = $seedCheckout($hotelId);
$tokenInvalido = $servicio->generarLink($hotelId, $reservacionInvalida, $usuarioId);
$resInvalido = $servicio->responder($hotelId, $tokenInvalido, ['calificacion' => 9]);
t_eq(false, $resInvalido['success'], 'calificacion fuera de 1-5 se rechaza');
t_eq(false, $resInvalido['mostrar_google'], 'una respuesta rechazada no invita a Google');

t_fin();
