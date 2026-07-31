<?php
/**
 * Un mantenimiento CONVENCIONAL (el de "ver habitacion" / "ver area") marca el
 * activo en /mantenimientos/activos.
 *
 * Antes no lo hacia: el INSERT de MantenimientoService nunca guardaba activo_id,
 * asi que activosServidosEnProceso() -que filtra por activo_id IS NOT NULL- salia
 * siempre vacio y el preventivo del equipo no avanzaba jamas. Solo el cron
 * preventivo llenaba esa columna. Reportado por el owner (jul-31).
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_PATH . '/services/MantenimientoService.php';

echo "ActivoMantenimientoTest\n";

t_reset_db();
$db = Database::getInstance();
$semilla = t_seed_base('mant-activo');
$hotelId = (int) $semilla['hotel_id'];
$svc = new MantenimientoService();

$db->query(
    "INSERT INTO habitaciones (hotel_id, numero, piso, tipo, precio_base, estado)
     VALUES (?, '901', 9, 'sencilla', 500, 'disponible')",
    [$hotelId]
);
$habId = (int) $db->lastInsertId();

$nuevoActivo = static function (int $hotelId, string $nombre, string $columna, int $unidadId) use ($db): int {
    $db->query(
        "INSERT INTO activos_hotel (hotel_id, nombre, {$columna}, periodicidad_dias, ultimo_servicio, proximo_servicio, activo)
         VALUES (?, ?, ?, 180, '2026-01-01', '2026-06-30', 1)",
        [$hotelId, $nombre, $unidadId]
    );
    return (int) $db->lastInsertId();
};
$proximoDe = static function (int $activoId) use ($db): array {
    return $db->query("SELECT ultimo_servicio, proximo_servicio FROM activos_hotel WHERE id = ?", [$activoId])->fetch();
};

// ── Habitacion: elegir el activo lo marca al cerrar ──
$activoId = $nuevoActivo($hotelId, 'Minisplit 901', 'habitacion_id', $habId);

$r = $svc->iniciarParaHotel($hotelId, $habId, 'correctivo', 'media', 'Fuga del minisplit', 1, true, $activoId);
$ligado = $db->query("SELECT activo_id FROM mantenimientos_habitaciones WHERE id = ?", [(int) $r['mantenimiento_id']])->fetch();
t_eq($activoId, (int) $ligado['activo_id'], 'el mantenimiento convencional queda LIGADO al activo elegido');

$svc->finalizarParaHotel($hotelId, $habId, 1);
$tras = $proximoDe($activoId);
t_eq(date('Y-m-d'), (string) $tras['ultimo_servicio'], 'al cerrar, ultimo_servicio = hoy (SE MARCO en Activos)');
t_eq(date('Y-m-d', strtotime('+180 days')), (string) $tras['proximo_servicio'], 'proximo_servicio recorrido una periodicidad');

// ── Sin elegir activo no se toca nada: el selector es OPCIONAL ──
$db->query("UPDATE activos_hotel SET ultimo_servicio='2026-01-01', proximo_servicio='2026-06-30' WHERE id=?", [$activoId]);
$svc->iniciarParaHotel($hotelId, $habId, 'correctivo', 'media', 'Cambio de foco', 1, true, null);
$svc->finalizarParaHotel($hotelId, $habId, 1);
t_eq('2026-06-30', (string) $proximoDe($activoId)['proximo_servicio'], 'sin elegir activo NO se adelanta el preventivo de nadie');

// ── Tenancy: un activo de otro hotel no se puede ligar ──
$otro = t_seed_base('mant-otro');
$db->query("INSERT INTO activos_hotel (hotel_id, nombre, periodicidad_dias, activo) VALUES (?, 'Ajeno', 90, 1)", [(int) $otro['hotel_id']]);
$ajenoId = (int) $db->lastInsertId();
$r = $svc->iniciarParaHotel($hotelId, $habId, 'correctivo', 'media', 'Intento cruzado', 1, true, $ajenoId);
$ligado = $db->query("SELECT activo_id FROM mantenimientos_habitaciones WHERE id = ?", [(int) $r['mantenimiento_id']])->fetch();
t_eq(null, $ligado['activo_id'], 'un activo de OTRO hotel no se liga (tenancy)');
$svc->finalizarParaHotel($hotelId, $habId, 1);

// Un activo de OTRA habitacion del mismo hotel tampoco.
$db->query("INSERT INTO habitaciones (hotel_id, numero, piso, tipo, precio_base, estado) VALUES (?, '902', 9, 'sencilla', 500, 'disponible')", [$hotelId]);
$otraHabId = (int) $db->lastInsertId();
$activoOtraHab = $nuevoActivo($hotelId, 'Boiler 902', 'habitacion_id', $otraHabId);
$r = $svc->iniciarParaHotel($hotelId, $habId, 'correctivo', 'media', 'Activo de otro cuarto', 1, true, $activoOtraHab);
$ligado = $db->query("SELECT activo_id FROM mantenimientos_habitaciones WHERE id = ?", [(int) $r['mantenimiento_id']])->fetch();
t_eq(null, $ligado['activo_id'], 'un activo de OTRA habitacion del mismo hotel no se liga');
$svc->finalizarParaHotel($hotelId, $habId, 1);

// ── Areas: misma historia, y ademas el cierre de area NUNCA marcaba activos ──
$db->query(
    "INSERT INTO areas_hotel (hotel_id, nombre, tipo, piso, estado, activa) VALUES (?, 'Alberca', 'alberca', 1, 'disponible', 1)",
    [$hotelId]
);
$areaId = (int) $db->lastInsertId();
$activoArea = $nuevoActivo($hotelId, 'Bomba de la alberca', 'area_id', $areaId);

$r = $svc->iniciarParaAreaHotel($hotelId, $areaId, 'correctivo', 'media', 'Bomba haciendo ruido', 1, $activoArea);
$ligado = $db->query("SELECT activo_id FROM mantenimientos_habitaciones WHERE id = ?", [(int) $r['mantenimiento_id']])->fetch();
t_eq($activoArea, (int) $ligado['activo_id'], 'el mantenimiento de AREA queda ligado a su activo');

$res = $svc->finalizarParaAreaHotel($hotelId, $areaId, 1);
t_eq(1, (int) ($res['activos_actualizados'] ?? 0), 'cerrar el area marca su activo (antes no marcaba ninguno)');
t_eq(date('Y-m-d'), (string) $proximoDe($activoArea)['ultimo_servicio'], 'el activo del area quedo servido hoy');

// Un activo del area no se puede colgar de un mantenimiento de habitacion.
$r = $svc->iniciarParaHotel($hotelId, $habId, 'correctivo', 'media', 'Cruce area/habitacion', 1, true, $activoArea);
$ligado = $db->query("SELECT activo_id FROM mantenimientos_habitaciones WHERE id = ?", [(int) $r['mantenimiento_id']])->fetch();
t_eq(null, $ligado['activo_id'], 'un activo de AREA no se liga a un mantenimiento de HABITACION');

t_fin();
