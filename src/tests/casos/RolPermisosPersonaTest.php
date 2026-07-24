<?php
/**
 * Permisos ajustados por PERSONA (hotel_usuarios.permisos_json).
 *
 * Contrato: una persona hereda los permisos de su rol y encima puede tener
 * diferencias propias — 'extra' (le sobran) y 'quitados' (le faltan). Quitar
 * gana siempre; el rol sigue mandando en todo lo que no se toco, de modo que
 * editar el rol despues sigue alcanzando a la persona ajustada.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "RolPermisosPersonaTest\n";

/* ── normalizar_ajustes_permisos(): pura ─────────────────────────────── */

t_eq(null, normalizar_ajustes_permisos(null), 'sin ajustes: null');
t_eq(null, normalizar_ajustes_permisos(''), 'cadena vacia: null');
t_eq(null, normalizar_ajustes_permisos('no-es-json'), 'JSON invalido: null');
t_eq(null, normalizar_ajustes_permisos('{"extra":[],"quitados":[]}'), 'ajustes vacios: null');

$a = normalizar_ajustes_permisos('{"extra":["caja.corte"],"quitados":["tareas.view"]}');
t_eq(['caja.corte'], $a['extra'], 'extra se conserva');
t_eq(['tareas.view'], $a['quitados'], 'quitados se conserva');

$a = normalizar_ajustes_permisos('["caja.corte","caja.corte"]');
t_eq(['caja.corte'], $a['extra'], 'lista suelta se lee como extra y deduplica');
t_eq([], $a['quitados'], 'lista suelta no quita nada');

$a = normalizar_ajustes_permisos(['extra' => ['caja.corte'], 'quitados' => ['caja.corte']]);
t_eq([], $a['extra'], 'un permiso en ambos lados no queda concedido');
t_eq(['caja.corte'], $a['quitados'], 'un permiso en ambos lados queda quitado (criterio cerrado)');

$a = normalizar_ajustes_permisos(['extra' => ['caja.corte', '', null], 'quitados' => []]);
t_eq(['caja.corte'], $a['extra'], 'valores vacios se descartan');

/* ── permisos_con_ajustes_personales(): pura ─────────────────────────── */

$rolRecepcion = ['habitaciones.view', 'habitaciones.checkin', 'caja.view'];

t_ok(permisos_con_ajustes_personales('caja.view', $rolRecepcion, null),
    'sin ajustes: el rol concede');
t_ok(!permisos_con_ajustes_personales('caja.corte', $rolRecepcion, null),
    'sin ajustes: lo que el rol no trae se niega');

$soloUno = ['extra' => ['caja.corte'], 'quitados' => []];
t_ok(permisos_con_ajustes_personales('caja.corte', $rolRecepcion, $soloUno),
    'extra concede un permiso que el rol no trae');
t_ok(permisos_con_ajustes_personales('caja.view', $rolRecepcion, $soloUno),
    'lo demas se sigue heredando del rol');

$recortada = ['extra' => [], 'quitados' => ['habitaciones.checkin']];
t_ok(!permisos_con_ajustes_personales('habitaciones.checkin', $rolRecepcion, $recortada),
    'quitados niega aunque el rol lo conceda');
t_ok(permisos_con_ajustes_personales('habitaciones.view', $rolRecepcion, $recortada),
    'quitar uno no toca a los hermanos');

// Comodines: el rol concede por modulo, el ajuste recorta una accion suelta.
$rolTareas = ['tareas.all'];
$sinVer = ['extra' => [], 'quitados' => ['tareas.view']];
t_ok(permisos_con_ajustes_personales('tareas.editar', $rolTareas, $sinVer),
    'tareas.all sigue concediendo el resto del modulo');
t_ok(!permisos_con_ajustes_personales('tareas.view', $rolTareas, $sinVer),
    'quitados le gana al comodin del modulo');

$extraTotal = ['extra' => ['inventarios.all'], 'quitados' => []];
t_ok(permisos_con_ajustes_personales('inventarios.ajustar', [], $extraTotal),
    'un comodin en extra concede todo su modulo');

// El comodin global del rol tambien se puede recortar por persona.
$sinCaja = ['extra' => [], 'quitados' => ['caja.corte']];
t_ok(permisos_con_ajustes_personales('reservaciones.view', ['*'], $sinCaja),
    'acceso total sigue concediendo lo no tocado');
t_ok(!permisos_con_ajustes_personales('caja.corte', ['*'], $sinCaja),
    'quitados le gana incluso al acceso total');

/* ── Rol::derivarAjustes(): pura ─────────────────────────────────────── */

$ambito = ['habitaciones.view', 'habitaciones.checkin', 'caja.view', 'caja.corte'];

$d = Rol::derivarAjustes($rolRecepcion, $rolRecepcion, $ambito);
t_eq([], $d['extra'], 'marcar exactamente el rol no genera extra');
t_eq([], $d['quitados'], 'marcar exactamente el rol no genera quitados');

$d = Rol::derivarAjustes($rolRecepcion, ['habitaciones.view', 'habitaciones.checkin', 'caja.view', 'caja.corte'], $ambito);
t_eq(['caja.corte'], $d['extra'], 'marcar de mas genera extra');
t_eq([], $d['quitados'], 'marcar de mas no genera quitados');

$d = Rol::derivarAjustes($rolRecepcion, ['habitaciones.view', 'caja.view'], $ambito);
t_eq([], $d['extra'], 'desmarcar no genera extra');
t_eq(['habitaciones.checkin'], $d['quitados'], 'desmarcar genera quitados');

// El ambito acota: un permiso fuera de la matriz jamas se pierde por omision.
$rolConLegacy = ['habitaciones.view', 'llaves.control'];
$d = Rol::derivarAjustes($rolConLegacy, ['habitaciones.view'], ['habitaciones.view', 'caja.view']);
t_eq([], $d['quitados'], 'un permiso fuera del ambito no se quita por no venir marcado');

// Un comodin del rol se refleja como "ya concedido" en cada permiso del modulo.
$d = Rol::derivarAjustes(['caja.all'], ['caja.view'], ['caja.view', 'caja.corte']);
t_eq([], $d['extra'], 'lo que el comodin ya concedia no cuenta como extra');
t_eq(['caja.corte'], $d['quitados'], 'desmarcar bajo un comodin si genera quitados');

/* ── El interruptor "control total" del area ─────────────────────────
 * La pantalla lo separa de la lista y, al ponerlo, BLOQUEA el detalle: los
 * hijos van deshabilitados y NO viajan en el POST. Solo llega '<modulo>.all'.
 */
$ambitoTareas = ['tareas.view', 'tareas.all'];

// Ponerlo cuando el rol no daba nada: 'extra' guarda la clave total SOLA.
$d = Rol::derivarAjustes([], ['tareas.all'], $ambitoTareas);
t_eq(['tareas.all'], $d['extra'], 'control total puesto: extra guarda solo la clave total');
t_eq([], $d['quitados'], 'control total puesto: no quita nada');

// El caso que rompia todo: el rol da el total y la pantalla manda solo el
// total (los hijos bloqueados no viajan). Sin cubrirlos, cada hijo caeria en
// 'quitados' y la persona perderia el modulo entero al guardar sin tocar nada.
$d = Rol::derivarAjustes(['tareas.all'], ['tareas.all'], $ambitoTareas);
t_eq([], $d['quitados'], 'el control total cubre a sus hijos: no los manda a quitados');
t_eq([], $d['extra'], 'guardar sin tocar nada no inventa diferencias');

// Rol con permisos sueltos y el usuario sube el interruptor.
$d = Rol::derivarAjustes(['tareas.view'], ['tareas.all'], $ambitoTareas);
t_eq(['tareas.all'], $d['extra'], 'subir el interruptor sobre permisos sueltos: solo la clave total');
t_eq([], $d['quitados'], 'subir el interruptor no quita lo que ya tenia');

// Bajar el interruptor y recortar: el detalle vuelve a decidir.
$d = Rol::derivarAjustes(['tareas.all'], ['tareas.view'], $ambitoTareas);
t_eq(['tareas.all'], $d['quitados'], 'bajar el interruptor lo registra como quitado');
t_eq([], $d['extra'], 'bajar el interruptor no agrega extras');

// Y lo concedido por el comodin del rol sigue en pie salvo lo quitado a mano.
$ajustes = normalizar_ajustes_permisos(['extra' => ['tareas.all'], 'quitados' => []]);
t_ok(permisos_con_ajustes_personales('tareas.editar', [], $ajustes),
    'un control total en extra concede todo el modulo a la persona');

/* ── Contrato del catalogo: toda area tiene encabezado ───────────────
 * Las dos pantallas de permisos suben una opcion al bloque de arriba: el
 * control total del area, o —si el area tiene UNA sola opcion— esa misma.
 * Un area con 2+ opciones y sin comodin se quedaria SIN encabezado y
 * ademas sin forma de concederla completa de un golpe.
 */

$cfgPermisos = require dirname(__DIR__, 2) . '/config/permisos.php';
$sinEncabezado = [];

foreach ($cfgPermisos['catalogo'] as $claveGrupo => $grupo) {
    $permisos = $grupo['permisos'] ?? [];
    $tieneComodin = false;

    foreach ($permisos as $def) {
        if (($def['tipo'] ?? '') === 'wildcard') {
            $tieneComodin = true;
            break;
        }
    }

    if (!$tieneComodin && count($permisos) > 1) {
        $sinEncabezado[] = $claveGrupo;
    }
}

t_eq([], $sinEncabezado, 'toda area del catalogo con 2+ permisos tiene su control total');

// Y ese comodin debe llamarse '<modulo>.all', que es lo unico que permission_in_list
// entiende: uno mal nombrado se dibujaria como control total sin conceder nada.
$comodinesMalNombrados = [];

foreach ($cfgPermisos['catalogo'] as $claveGrupo => $grupo) {
    foreach ($grupo['permisos'] ?? [] as $clave => $def) {
        if (($def['tipo'] ?? '') !== 'wildcard') {
            continue;
        }

        $modulo = strstr($clave, '.', true);
        if (!permission_in_list($modulo . '.view', [$clave])) {
            $comodinesMalNombrados[] = $clave;
        }
    }
}

t_eq([], $comodinesMalNombrados, 'todo control total concede de verdad su modulo');

// Los comodines nuevos (caja/tarifas/usuarios/configuracion) conceden su area.
t_ok(permission_in_list('caja.ajustes', ['caja.all']), 'caja.all concede los ajustes de caja');
t_ok(permission_in_list('usuarios.delete', ['usuarios.all']), 'usuarios.all concede eliminar usuarios');
t_ok(permission_in_list('tarifas.edit', ['tarifas.all']), 'tarifas.all concede editar tarifas');
t_ok(permission_in_list('configuracion.edit', ['configuracion.all']), 'configuracion.all concede editar configuracion');
t_ok(!permission_in_list('caja.view', ['tarifas.all']), 'un control total no se desborda a otra area');

/* ── can() de punta a punta contra la BD ─────────────────────────────── */

t_reset_db();
$db = Database::getInstance();

$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Permisos', 'hotel-permisos', 1, NOW())");
$hotelId = (int) $db->lastInsertId();

$db->query(
    "INSERT INTO roles (hotel_id, clave, nombre, es_sistema, activo, permisos_json, created_at, updated_at)
     VALUES (?, 'limpieza', 'Limpieza', 0, 1, ?, NOW(), NOW())",
    [$hotelId, json_encode(['camarista.view', 'tareas.view', 'habitaciones.view'])]
);
$rolId = (int) $db->lastInsertId();

/** Crea una persona del hotel con ese rol y esos ajustes personales. */
$sembrarPersona = function (string $usuario, $permisosJson) use ($db, $hotelId, $rolId) {
    $db->query(
        "INSERT INTO usuarios (nombre_usuario, password, nombre_completo, rol, activo, created_at)
         VALUES (?, 'x', ?, 'recepcionista', 1, NOW())",
        [$usuario, ucfirst($usuario)]
    );
    $usuarioId = (int) $db->lastInsertId();

    $db->query(
        "INSERT INTO hotel_usuarios (hotel_id, usuario_id, rol, role_id, es_principal, activo, permisos_json, created_at, updated_at)
         VALUES (?, ?, 'recepcionista', ?, 0, 1, ?, NOW(), NOW())",
        [$hotelId, $usuarioId, $rolId, $permisosJson]
    );

    return $usuarioId;
};

$idNormal = $sembrarPersona('limpieza_normal', null);
$idExtra = $sembrarPersona('limpieza_extra', json_encode(['extra' => ['habitaciones.checkin'], 'quitados' => []]));
$idRecorte = $sembrarPersona('limpieza_recorte', json_encode(['extra' => [], 'quitados' => ['habitaciones.view']]));

/** Entra al sistema como esa persona (sin role_id en sesion: se resuelve de BD). */
$entrarComo = function (int $usuarioId) use ($hotelId) {
    $_SESSION = [
        'user_id' => $usuarioId,
        'user' => ['id' => $usuarioId, 'rol' => 'recepcionista'],
        'hotel_id' => $hotelId,
        'hotel_slug' => 'hotel-permisos',
        'hotel_usuario' => ['id' => $usuarioId, 'rol' => 'recepcionista'],
    ];
};

$entrarComo($idNormal);
t_ok(can('camarista.view'), 'sin ajustes: hereda camarista.view del rol');
t_ok(can('habitaciones.view'), 'sin ajustes: hereda habitaciones.view del rol');
t_ok(!can('habitaciones.checkin'), 'sin ajustes: no tiene lo que el rol no da');

$entrarComo($idExtra);
t_ok(can('habitaciones.checkin'), 'con extra: gana el permiso que su rol no trae');
t_ok(can('camarista.view'), 'con extra: conserva lo del rol');

$entrarComo($idRecorte);
t_ok(!can('habitaciones.view'), 'con quitados: pierde el permiso que su rol si trae');
t_ok(can('tareas.view'), 'con quitados: conserva el resto del rol');

// Compañeros de rol intactos: el ajuste es de la persona, no del rol.
$entrarComo($idNormal);
t_ok(can('habitaciones.view'), 'ajustar a una persona no toca a sus compañeros de rol');

/* ── Modelo: lectura para la vista de roles ──────────────────────────── */

$rolModel = new Rol();

$porRol = $rolModel->usuariosPorRolDeHotel($hotelId);
t_eq(3, count($porRol[$rolId] ?? []), 'la vista de roles lista a las 3 personas del rol');

$conAjustes = array_values(array_filter($porRol[$rolId], static function ($p) {
    return $p['ajustes'] !== null;
}));
t_eq(2, count($conAjustes), 'dos de las tres salen marcadas como ajustadas a la medida');

$persona = $rolModel->usuarioDelHotel($hotelId, $idRecorte);
t_eq($rolId, (int) $persona['role_id'], 'usuarioDelHotel resuelve el rol de la persona');
t_eq(['habitaciones.view'], $persona['ajustes']['quitados'], 'usuarioDelHotel trae sus ajustes normalizados');

t_eq(null, $rolModel->usuarioDelHotel($hotelId + 999, $idRecorte), 'usuarioDelHotel no cruza de hotel');

// Guardar y restablecer.
t_ok($rolModel->guardarAjustesUsuario($hotelId, $idNormal, ['extra' => ['caja.view'], 'quitados' => []]),
    'guardarAjustesUsuario escribe');
$persona = $rolModel->usuarioDelHotel($hotelId, $idNormal);
t_eq(['caja.view'], $persona['ajustes']['extra'], 'lo guardado se relee');

t_ok($rolModel->guardarAjustesUsuario($hotelId, $idNormal, null), 'guardarAjustesUsuario borra con null');
$persona = $rolModel->usuarioDelHotel($hotelId, $idNormal);
t_eq(null, $persona['ajustes'], 'restablecer deja a la persona igual que su rol');

t_ok($rolModel->guardarAjustesUsuario($hotelId, $idNormal, ['extra' => [], 'quitados' => []]),
    'guardarAjustesUsuario acepta ajustes vacios');
$persona = $rolModel->usuarioDelHotel($hotelId, $idNormal);
t_eq(null, $persona['ajustes'], 'ajustes vacios no dejan basura en la columna');

// Quien no tiene rol configurable sale por su propia puerta.
$db->query(
    "INSERT INTO usuarios (nombre_usuario, password, nombre_completo, rol, activo, created_at)
     VALUES ('sin_rol_qa', 'x', 'Sin Rol QA', 'administrador', 1, NOW())"
);
$idSinRol = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO hotel_usuarios (hotel_id, usuario_id, rol, es_principal, activo, created_at, updated_at)
     VALUES (?, ?, 'administrador', 0, 1, NOW(), NOW())",
    [$hotelId, $idSinRol]
);

$sinRol = $rolModel->usuariosSinRol($hotelId);
t_eq(1, count($sinRol), 'usuariosSinRol detecta a quien quedo con permisos antiguos');
t_eq($idSinRol, (int) $sinRol[0]['usuario_id'], 'usuariosSinRol devuelve a la persona correcta');

$porRol = $rolModel->usuariosPorRolDeHotel($hotelId);
t_eq(3, count($porRol[$rolId] ?? []), 'quien no tiene rol no se cuela en las tarjetas de rol');

t_fin();
