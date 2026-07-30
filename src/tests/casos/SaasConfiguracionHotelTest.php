<?php
/**
 * Configuracion del hotel operada desde el Panel Medisoft.
 *
 * Contrato (jul-28): la pantalla de ajustes dejo de ser del hotel inquilino.
 * Medisoft la abre en /admin/saas/hoteles/{id}/configuracion y la MISMA vista
 * se guarda contra un hotel EXPLICITO, nunca contra el de la sesion.
 *
 * Lo que se cuida aqui:
 *   - normalizar/guardar por hotel (HotelConfiguracionService)
 *   - tenancy: guardar el hotel A jamas toca al hotel B
 *   - la tabla legacy `configuracion` (global, sin hotel_id) NO se escribe
 *   - el menu del hotel ya no ofrece la pantalla (solo_medisoft + config_hotel_url)
 */

require_once __DIR__ . '/../bootstrap.php';
require_once APP_PATH . '/services/HotelConfiguracionService.php';

echo "SaasConfiguracionHotelTest\n";

// ── Apariencia: solo dos modos y un hex de 6 digitos ───────────────────────
$r = HotelConfiguracionService::normalizarApariencia(['background_mode' => 'custom', 'background_color' => '#aabbcc']);
t_eq([], $r['errors'], 'color valido no genera error');
t_eq('#AABBCC', $r['values']['background_color'], 'el hex se normaliza a mayusculas');

$r = HotelConfiguracionService::normalizarApariencia(['background_mode' => 'default', 'background_color' => '#AABBCC']);
t_eq('', $r['values']['background_color'], 'modo default descarta el color elegido');

$r = HotelConfiguracionService::normalizarApariencia(['background_mode' => 'custom', 'background_color' => 'rojo']);
t_ok(!empty($r['errors']), 'un color invalido en modo custom es error, no un guardado silencioso');

$r = HotelConfiguracionService::normalizarApariencia(['background_mode' => 'javascript:alert(1)', 'background_color' => '#AABBCC']);
t_eq('', $r['values']['background_color'], 'un modo desconocido cae a default (no personaliza)');

// ── El payload vacio no inventa bloques ────────────────────────────────────
$r = HotelConfiguracionService::normalizarPayload([]);
t_eq([], $r['errors'], 'POST vacio no da errores');
t_eq([], $r['values'], 'POST vacio no guarda nada');

// footer_nav solo se toca si el form lo declara (un form viejo no borra atajos)
$r = HotelConfiguracionService::normalizarPayload(['footer_nav' => ['dashboard', 'habitaciones']]);
t_ok(!isset($r['values']['footer_nav']), 'sin footer_nav_submitted la barra inferior no se toca');

$r = HotelConfiguracionService::normalizarPayload([
    'footer_nav_submitted' => '1',
    'footer_nav' => ['dashboard'],
]);
t_ok(!empty($r['errors']), 'un solo atajo no alcanza el minimo de la barra inferior');

// ── Guardado contra un hotel explicito ─────────────────────────────────────
t_reset_db();
$base = t_seed_base('saascfg-a');
$hotelA = $base['hotel_id'];

$db = Database::getInstance();
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel B', 'saascfg-b', 1, NOW())");
$hotelB = (int) $db->lastInsertId();

// El form manda TODOS los ajustes (los obligatorios se validan juntos, igual
// que en /configuracion): partimos de los defaults y cambiamos dos.
$ajustes = [];
foreach (hotel_config_editable_definitions() as $clave => $definicion) {
    $valor = $definicion['default'] ?? '';
    $ajustes[$clave] = is_bool($valor) ? ($valor ? '1' : '0') : (string) $valor;
}
$ajustes['contacto.telefono'] = '555 111 2233';
$ajustes['operacion.checkin_hora'] = '14:00';

$payload = [
    'hotel_config' => $ajustes,
    'guest_fields' => ['nacionalidad' => ['visible' => '1']],
    'hotel_appearance' => ['background_mode' => 'custom', 'background_color' => '#123456'],
];

$normalizado = HotelConfiguracionService::normalizarPayload($payload);
t_eq([], $normalizado['errors'], 'el payload de ejemplo normaliza sin errores');

// La sesion apunta al hotel A; guardamos al B a proposito.
HotelConfiguracionService::guardar($normalizado['values'], $hotelB);

t_eq('#123456', strtolower(hotel_config_get('apariencia.fondo_sistema', '', $hotelB)), 'el fondo se guardo en el hotel indicado');
t_eq('', (string) hotel_config_get('apariencia.fondo_sistema', '', $hotelA), 'el hotel de la sesion NO se contamino');
t_eq('555 111 2233', (string) hotel_config_get('contacto.telefono', '', $hotelB), 'el telefono viajo al hotel indicado');
t_eq('', (string) hotel_config_get('contacto.telefono', '', $hotelA), 'el telefono no toco al otro hotel');

$filasA = (int) $db->query("SELECT COUNT(*) c FROM hotel_configuracion WHERE hotel_id = ?", [$hotelA])->fetch()['c'];
t_eq(0, $filasA, 'guardar el hotel B no escribio ni una fila del hotel A');

// La tabla legacy es GLOBAL (UNIQUE por clave, sin hotel_id): nadie la escribe desde aqui.
$legacy = (int) $db->query("SELECT COUNT(*) c FROM configuracion")->fetch()['c'];
t_eq(0, $legacy, 'el guardado por hotel jamas toca la tabla legacy `configuracion`');

// Hotel invalido: falla ruidoso, no escribe "al hotel actual" por descuido.
$exploto = false;
try {
    HotelConfiguracionService::guardar($normalizado['values'], 0);
} catch (Throwable $e) {
    $exploto = true;
}
t_ok($exploto, 'guardar sin hotel valido lanza en vez de caer al de la sesion');

// ── Lectura para la vista, tambien por hotel ───────────────────────────────
$datos = HotelConfiguracionService::datosDeVista($hotelB);
t_eq('555 111 2233', (string) ($datos['hotelSettings']['contacto.telefono'] ?? ''), 'datosDeVista lee el hotel pedido');
t_eq('#123456', strtolower(HotelConfiguracionService::fondoGuardado($hotelB)), 'fondoGuardado devuelve el color del hotel pedido');
t_eq('', HotelConfiguracionService::fondoGuardado($hotelA), 'sin fondo propio devuelve vacio (usa el del tema)');

// ── Barra inferior: el catalogo responde por el HOTEL OBJETIVO ─────────────
// Bug jul-29: hotel_footer_nav_available_catalog() no aceptaba hotel y filtraba
// con hotel_menu_module_enabled, que devuelve true para TODA ruta /admin/saas y
// ademas resuelve por current_hotel_id() ($_SESSION) ⇒ el panel ofrecia atajos
// de bloques que el hotel cliente no tiene, y los GUARDABA (el sanitizador
// valida contra el catalogo completo). Resultado: atajo hacia un 403.
// Los 4 atajos predeterminados son paquete base (es_core): se siembran para que
// el catalogo de prueba se parezca al real, donde siempre hay atajos disponibles.
foreach (['dashboard', 'reservaciones', 'habitaciones', 'caja'] as $ordenBase => $claveBase) {
    $db->query(
        "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
         VALUES (?, ?, 'test', 1, 'base', 0.00, 1, ?, NOW())",
        [$claveBase, ucfirst($claveBase), 20 + $ordenBase]
    );
}

$db->query(
    "INSERT INTO modulos (clave, nombre, categoria, es_core, tipo_comercial, precio_mensual, activo_global, orden, created_at)
     VALUES ('documentos', 'Centro documental', 'test', 0, 'opcional', 149.00, 1, 16, NOW())"
);
$idDocumentos = (int) $db->lastInsertId();

// GOTCHA: hotel_active_module_keys memoiza por proceso, y los hoteles A/B ya
// fueron consultados arriba ⇒ hoteles NUEVOS para este bloque.
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel con docs', 'saascfg-docs', 1, NOW())");
$hotelConDocs = (int) $db->lastInsertId();
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel sin docs', 'saascfg-nodocs', 1, NOW())");
$hotelSinDocs = (int) $db->lastInsertId();
$db->query(
    "INSERT INTO hotel_modulos (hotel_id, modulo_id, activo, fuente, enabled_at, created_at)
     VALUES (?, ?, 1, 'manual', NOW(), NOW())",
    [$hotelConDocs, $idDocumentos]
);

$_SERVER['REQUEST_URI'] = '/admin/saas/hoteles/' . $hotelConDocs . '/configuracion';

$catalogoCon = hotel_footer_nav_available_catalog($hotelConDocs);
$catalogoSin = hotel_footer_nav_available_catalog($hotelSinDocs);
t_ok(isset($catalogoCon['documentos']),
    'el hotel que SI contrato documentos conserva su atajo en el panel');
t_ok(!isset($catalogoSin['documentos']),
    'el hotel que NO lo contrato ya no puede recibir ese atajo desde el panel');
t_ok(isset($catalogoSin['dashboard']),
    'los atajos del paquete base siguen disponibles para todos');

$catalogoServicio = HotelConfiguracionService::datosDeVista($hotelSinDocs)['footerNavCatalog'] ?? [];
t_ok(!isset($catalogoServicio['documentos']),
    'datosDeVista pasa el hotel al catalogo (antes decia "todo disponible" bajo /admin/saas)');

// El relleno de respaldo debe alcanzar el MINIMO, no solo evitar el vacio: si
// tras filtrar queda 1 atajo, hotel_footer_nav_normalize_payload aborta TODO el
// guardado de la pantalla.
t_ok(count(hotel_footer_nav_items($hotelSinDocs)) >= hotel_footer_nav_min(),
    'la barra filtrada nunca queda por debajo del minimo que exige el guardado');

$_SERVER['REQUEST_URI'] = '/configuracion';

// ── El hotel inquilino ya no ve la pantalla ────────────────────────────────
$_SESSION['saas_admin'] = null;
unset($_SESSION['saas_admin']);

t_ok(!isSaasAdmin(), 'la sesion sembrada no es admin SaaS');
t_eq(null, config_hotel_url('#hc-rooms'), 'sin ser Medisoft no hay URL de configuracion que enlazar');

$pantalla = ['ruta' => 'configuracion', 'permiso' => null, 'modulo' => null, 'solo_medisoft' => true];
t_ok(!nav_pantalla_visible($pantalla), 'la entrada solo_medisoft queda fuera del menu del hotel');

$pantallaNormal = ['ruta' => 'reservaciones', 'permiso' => null, 'modulo' => null];
t_ok(nav_pantalla_visible($pantallaNormal), 'una pantalla sin solo_medisoft sigue visible');

t_fin();
