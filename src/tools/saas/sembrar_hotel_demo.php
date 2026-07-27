<?php
/**
 * Cuenta de DEMOSTRACION para prospectos (datos 100% ficticios).
 *
 * Motivo: ensenar el sistema en vivo sin abrir la cuenta de un hotel real.
 * Todo lo que siembra este guion vive bajo UN hotel propio, identificado por
 * el slug fijo `hotel-demo-medisoft`; ninguna sentencia toca otro hotel_id.
 *
 * Uso (dentro del contenedor):
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/saas/sembrar_hotel_demo.php
 *   docker exec medisoft_hoteles_app php /var/www/html/tools/saas/sembrar_hotel_demo.php --limpiar
 *
 * Banderas:
 *   --limpiar                  Borra la cuenta demo COMPLETA (solo la suya) y sale.
 *   --confirmo-no-produccion   Permite correrlo cuando APP_ENV no es 'local'.
 *   --password=XXXX            Contrasena del usuario demo (por omision la del guion).
 *
 * Re-ejecutable: si el hotel demo ya existe se REUSA (mismo id, mismo slug) y
 * sus datos operativos se regeneran desde cero. Nunca se duplica.
 */

/* ---------------------------------------------------------------------- */
/* 0) Candados antes de tocar nada                                         */
/* ---------------------------------------------------------------------- */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "Esta herramienta solo puede ejecutarse por CLI.\n";
    exit(1);
}

$opciones = getopt('', ['limpiar', 'confirmo-no-produccion', 'password::']);
$limpiar = array_key_exists('limpiar', $opciones);
$confirmaNoProd = array_key_exists('confirmo-no-produccion', $opciones);

$appEnv = (string) (getenv('APP_ENV') ?: '');

// Un entorno que se declara productivo no se siembra NUNCA, ni con bandera:
// la bandera existe para entornos sin APP_ENV (staging, sandbox), no para
// saltarse el candado del servidor real.
if (in_array(strtolower($appEnv), ['production', 'prod', 'produccion'], true)) {
    echo "[ABORTADO] APP_ENV={$appEnv}: esto es produccion. Este guion jamas siembra datos demo ahi.\n";
    exit(1);
}

if ($appEnv !== 'local' && !$confirmaNoProd) {
    echo "[ABORTADO] APP_ENV=" . ($appEnv === '' ? '(sin definir)' : $appEnv) . " y falta la confirmacion explicita.\n";
    echo "           Corra con --confirmo-no-produccion solo si esta seguro de que NO es la base de produccion.\n";
    exit(1);
}

/* ---------------------------------------------------------------------- */
/* 1) Nucleo de la aplicacion en CLI                                       */
/* ---------------------------------------------------------------------- */

// Espejo de public_html/index.php: los modelos (Rol, Caja, Modulo) se cargan
// por autoloader y esperan estas constantes.
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('CONFIG_PATH', ROOT_PATH . '/config');
define('CORE_PATH', ROOT_PATH . '/core');
define('PUBLIC_PATH', ROOT_PATH . '/public_html');
define('STORAGE_PATH', ROOT_PATH . '/storage');

// Una query rota dentro de la transaccion tiene que EXPLOTAR, no devolver
// false en silencio: si no, el rollback no protege de nada.
putenv('DB_STRICT_ERRORS=true');

date_default_timezone_set('America/Mexico_City');
error_reporting(E_ALL);
ini_set('display_errors', 1);

$_SESSION = []; // helpers que leen sesion no deben tropezar en CLI

require_once APP_PATH . '/helpers/log.php';

spl_autoload_register(function ($class) {
    foreach ([CORE_PATH, APP_PATH . '/controllers', APP_PATH . '/models', APP_PATH . '/services'] as $dir) {
        $file = $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

require_once APP_PATH . '/helpers/cache.php';
require_once APP_PATH . '/helpers/functions.php';
require_once APP_PATH . '/helpers/auth.php';
require_once APP_PATH . '/helpers/hotel_config.php';
require_once APP_PATH . '/helpers/modulos.php';

// La conexion del singleton fija time_zone -06:00: usarla (y no un PDO crudo)
// es lo que hace que CURDATE()/NOW() cuadren con lo que ve la app.
$db = Database::getInstance();
$pdo = $db->getConnection();
$cfgBd = require CONFIG_PATH . '/database.php';

/* ---------------------------------------------------------------------- */
/* 2) Constantes de la cuenta demo                                         */
/* ---------------------------------------------------------------------- */

const DEMO_SLUG      = 'hotel-demo-medisoft';
const DEMO_NOMBRE    = 'Hotel Demo Medisoft';
const DEMO_CODIGO    = 'DEMO-MEDISOFT';
const DEMO_USUARIO   = 'demo_medisoft';
const DEMO_PASSWORD  = 'DemoMedisoft2026*';

// Los 8 bloques a la venta (2026-07-25). Se contratan todos para que el
// recorrido comercial pueda entrar a cualquiera sin tocar el panel SaaS.
const DEMO_BLOQUES = ['inventario', 'facturacion', 'compras', 'documentos',
    'reputacion', 'descuentos', 'tarifas_dinamicas', 'lealtad'];

$password = trim((string) ($opciones['password'] ?? ''));
if ($password === '') {
    $password = DEMO_PASSWORD;
}

/* ---------------------------------------------------------------------- */
/* 3) Utilidades de salida                                                 */
/* ---------------------------------------------------------------------- */

function d_titulo(string $texto): void { echo "\n== {$texto} ==\n"; }
function d_paso(string $texto): void   { echo "  - {$texto}\n"; }
function d_error(string $texto): void  { echo "[ERROR] {$texto}\n"; }

/**
 * Borra archivos del storage privado, SOLO si caen dentro de
 * STORAGE_PATH/documentos. Una ruta que venga de la BD no se sigue a ciegas.
 */
function d_borrar_archivos(array $rutasRelativas): int
{
    $raiz = realpath(rtrim(STORAGE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'documentos');
    if (!$raiz) {
        return 0;
    }
    $prefijo = rtrim($raiz, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $n = 0;

    foreach (array_unique($rutasRelativas) as $rel) {
        $rel = ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, (string) $rel), DIRECTORY_SEPARATOR);
        if ($rel === '' || in_array('..', explode(DIRECTORY_SEPARATOR, $rel), true)) {
            continue;
        }
        $abs = realpath(rtrim(STORAGE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel);
        if ($abs && is_file($abs) && strpos($abs, $prefijo) === 0 && @unlink($abs)) {
            $n++;
        }
    }

    // Los directorios por hotel/anio/mes quedan vacios: se retiran de abajo
    // hacia arriba y solo si nadie mas los ocupa.
    foreach (array_reverse(glob($raiz . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . '*') ?: []) as $dir) {
        if (is_dir($dir)) { @rmdir($dir); @rmdir(dirname($dir)); @rmdir(dirname($dir, 2)); }
    }

    return $n;
}

/**
 * Cede al servidor web lo que este guion crea en el storage privado.
 *
 * Esto NO es cosmetico: el guion corre como root (docker exec) y el sitio
 * corre como www-data. Un archivo 0640 root:root existe pero Apache no puede
 * leerlo -> la descarga y la vista previa contestan "archivo no disponible".
 * Peor: si este guion es el primero en crear la raiz `documentos/`, la deja
 * root:root y rompe la subida REAL de cualquier hotel, no solo la demo.
 */
function d_ceder_al_servidor(string $ruta): void
{
    static $duenio = null;
    if ($duenio === null) {
        $duenio = function_exists('posix_getpwnam') ? (posix_getpwnam('www-data') ?: false) : false;
    }

    if ($duenio) {
        @chown($ruta, (int) $duenio['uid']);
        @chgrp($ruta, (int) $duenio['gid']);
        @chmod($ruta, is_dir($ruta) ? 0750 : 0640);
        return;
    }

    // Sin posix no se puede ceder la propiedad: se abre la lectura para que el
    // sitio al menos pueda servir el archivo (storage vive fuera del webroot).
    @chmod($ruta, is_dir($ruta) ? 0755 : 0644);
}

/** PDF valido de una pagina, generado aqui: la demo necesita archivos REALES,
 *  no filas que apunten a la nada (la vista previa y la descarga los abren). */
function d_pdf_minimo(string $titulo, array $lineas): string
{
    $esc = static function (string $t): string {
        // Solo ASCII: Helvetica sin /Encoding no dibuja acentos de forma fiable.
        $t = iconv('UTF-8', 'ASCII//TRANSLIT', $t) ?: $t;
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $t);
    };

    $flujo = "BT\n/F1 15 Tf\n56 752 Td\n(" . $esc($titulo) . ") Tj\n/F1 10 Tf\n";
    foreach ($lineas as $linea) {
        $flujo .= "0 -22 Td\n(" . $esc((string) $linea) . ") Tj\n";
    }
    $flujo .= "ET\n";

    $objetos = [
        1 => "<< /Type /Catalog /Pages 2 0 R >>",
        2 => "<< /Type /Pages /Kids [3 0 R] /Count 1 >>",
        3 => "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] "
            . "/Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>",
        4 => "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>",
        5 => "<< /Length " . strlen($flujo) . " >>\nstream\n" . $flujo . "endstream",
    ];

    $pdf = "%PDF-1.4\n";
    $offsets = [];
    foreach ($objetos as $num => $cuerpo) {
        $offsets[$num] = strlen($pdf);
        $pdf .= "{$num} 0 obj\n{$cuerpo}\nendobj\n";
    }
    $inicioXref = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objetos) + 1) . "\n0000000000 65535 f \n";
    foreach ($objetos as $num => $cuerpo) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$num]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objetos) + 1) . " /Root 1 0 R >>\n"
        . "startxref\n{$inicioXref}\n%%EOF\n";

    return $pdf;
}

// Archivos del demo anterior que hay que retirar del disco TRAS el commit, y
// archivos nuevos que hay que retirar si la transaccion se cae.
$archivosViejos = [];
$archivosCreados = [];

/* ---------------------------------------------------------------------- */
/* 4) Resolucion del hotel demo (por slug, nunca por id suelto)            */
/* ---------------------------------------------------------------------- */

echo "Base de datos objetivo: {$cfgBd['database']} en {$cfgBd['host']} (APP_ENV=" . ($appEnv === '' ? 'sin definir' : $appEnv) . ")\n";

$st = $pdo->prepare("SELECT id, nombre, slug, activo FROM hoteles WHERE slug = ? LIMIT 1");
$st->execute([DEMO_SLUG]);
$hotelDemo = $st->fetch(PDO::FETCH_ASSOC);
$hotelId = $hotelDemo ? (int) $hotelDemo['id'] : 0;

/**
 * Doble verificacion antes de cualquier DELETE: el id tiene que seguir siendo
 * el del slug demo. Es barato y es lo unico que separa "limpiar la demo" de
 * "destruir un hotel real" si alguien edita la constante o la fila cambia.
 */
$asegurarEsDemo = function (int $id) use ($pdo): void {
    if ($id <= 0) {
        throw new RuntimeException('Id de hotel demo invalido.');
    }
    $st = $pdo->prepare("SELECT slug FROM hoteles WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $slug = (string) $st->fetchColumn();
    if ($slug !== DEMO_SLUG) {
        throw new RuntimeException("El hotel id {$id} tiene slug '{$slug}', no '" . DEMO_SLUG . "'. Se aborta para no tocar datos reales.");
    }
};

/* ---------------------------------------------------------------------- */
/* 5) Purga (compartida por --limpiar y por la re-siembra)                 */
/* ---------------------------------------------------------------------- */

/**
 * Borra los datos del hotel demo en orden de dependencia (hijos primero).
 * TODA sentencia lleva el hotel_id demo; las dos tablas sin esa columna
 * (control_llaves, denominaciones_efectivo) se acotan por subconsulta a
 * tablas que si lo tienen.
 *
 * $borrarCuenta=true elimina ademas el hotel, su usuario y su marca: es lo
 * que hace --limpiar. En la re-siembra se conserva el cascaron para no
 * cambiar de hotel_id (los enlaces de una demo previa siguen sirviendo).
 */
$purgar = function (int $hid, bool $borrarCuenta) use ($pdo, $asegurarEsDemo, &$archivosViejos): array {
    $asegurarEsDemo($hid);

    // Los PDF del Centro documental viven en DISCO: se anotan ANTES de borrar
    // sus filas y se retiran DESPUES del commit. Si la transaccion se cae, el
    // archivo tiene que seguir donde estaba.
    $st = $pdo->prepare("SELECT storage_path FROM documentos WHERE hotel_id = ?");
    $st->execute([$hid]);
    foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $ruta) {
        $archivosViejos[] = (string) $ruta;
    }

    $porHotel = [
        // Operacion ligada a reservaciones
        'reputacion_encuestas', 'solicitudes_factura', 'checkin_digital_links',
        'lealtad_cupones', 'mensajes_whatsapp',
        'cuentas_por_cobrar_movimientos', 'cuentas_por_cobrar',
        'cuentas_por_pagar_movimientos', 'cuentas_por_pagar',
        'reservacion_pagos', 'reservacion_abonos', 'reservacion_habitaciones', 'reservacion_notas',
        // Operacion de piso
        'tarea_eventos', 'tarea_trabajadores', 'tareas_operativas',
        'mantenimiento_fotos', 'mantenimientos_habitaciones',
        'notificaciones', 'ical_bloqueos', 'ical_feeds',
        // Inventario y compras
        'movimientos_inventario', 'compra_detalles', 'compras',
        'inventario_config_habitacion', 'inventario_productos', 'inventario_categorias',
        'proveedores',
        // Dinero
        'movimientos_caja', 'cortes_caja',
        // Reservaciones y su entorno
        'reservaciones', 'huesped_vehiculos', 'huespedes',
        'habitacion_imagenes', 'habitaciones', 'areas_hotel', 'activos_hotel',
        'tipos_habitacion', 'temporadas_hotel', 'incrementos_tarifas',
        'categorias_movimientos', 'cajas',
        // Rastro y configuracion del hotel
        'documento_entidades', 'documentos', 'documento_tipos',
        'logs_auditoria', 'auditoria_eventos', 'ia_resumenes',
        'copiloto_mensajes', 'copiloto_ia_generaciones',
        'night_audit_cierres', 'reporte_link_envios', 'reporte_links',
        'saas_cobros', 'hotel_modulos', 'hotel_configuracion', 'hotel_branding',
        'hotel_usuarios', 'roles',
    ];

    $borrados = [];

    // Sin columna hotel_id: se acotan por sus padres, que si la tienen.
    foreach ([
        ['historial_llaves', "DELETE FROM historial_llaves WHERE habitacion_id IN (SELECT id FROM habitaciones WHERE hotel_id = ?)"],
        ['control_llaves',   "DELETE FROM control_llaves   WHERE habitacion_id IN (SELECT id FROM habitaciones WHERE hotel_id = ?)"],
        ['historial_remotos',"DELETE FROM historial_remotos WHERE habitacion_id IN (SELECT id FROM habitaciones WHERE hotel_id = ?)"],
        ['control_remotos',  "DELETE FROM control_remotos  WHERE habitacion_id IN (SELECT id FROM habitaciones WHERE hotel_id = ?)"],
        ['denominaciones_efectivo', "DELETE FROM denominaciones_efectivo WHERE corte_id IN (SELECT id FROM cortes_caja WHERE hotel_id = ?)"],
    ] as [$tabla, $sql]) {
        $st = $pdo->prepare($sql);
        $st->execute([$hid]);
        if ($st->rowCount() > 0) {
            $borrados[$tabla] = $st->rowCount();
        }
    }

    foreach ($porHotel as $tabla) {
        $st = $pdo->prepare("DELETE FROM `{$tabla}` WHERE hotel_id = ?");
        $st->execute([$hid]);
        if ($st->rowCount() > 0) {
            $borrados[$tabla] = $st->rowCount();
        }
    }

    // Barrido final: la lista de arriba cubre lo que SIEMBRA el guion, pero una
    // demo que se USA deja rastro en tablas que nadie previo (preferencias de
    // menu, tokens de sesion, suscripciones push...). Sin esto quedan filas
    // huerfanas apuntando a un hotel_id que ya no existe. Se recorre cualquier
    // tabla con columna hotel_id y se reintenta por pasadas: si una FK impide
    // vaciar al padre, la siguiente pasada ya encuentra vacio al hijo.
    $tablasConHotel = $pdo->query(
        "SELECT t.TABLE_NAME
           FROM information_schema.TABLES t
           JOIN information_schema.COLUMNS c
             ON c.TABLE_SCHEMA = t.TABLE_SCHEMA AND c.TABLE_NAME = t.TABLE_NAME
          WHERE t.TABLE_SCHEMA = DATABASE()
            AND t.TABLE_TYPE = 'BASE TABLE'
            AND c.COLUMN_NAME = 'hotel_id'
          ORDER BY t.TABLE_NAME"
    )->fetchAll(PDO::FETCH_COLUMN);

    $pendientes = $tablasConHotel;
    for ($pasada = 1; $pasada <= 8 && !empty($pendientes); $pasada++) {
        $quedan = [];
        foreach ($pendientes as $tabla) {
            $pdo->exec('SAVEPOINT barrido');
            try {
                $st = $pdo->prepare("DELETE FROM `{$tabla}` WHERE hotel_id = ?");
                $st->execute([$hid]);
                $pdo->exec('RELEASE SAVEPOINT barrido');
                if ($st->rowCount() > 0) {
                    $borrados[$tabla] = ($borrados[$tabla] ?? 0) + $st->rowCount();
                }
            } catch (PDOException $e) {
                // Tipicamente 1451 (hijo aun apunta al padre): se reintenta.
                $pdo->exec('ROLLBACK TO SAVEPOINT barrido');
                $quedan[] = $tabla;
            }
        }
        if (count($quedan) === count($pendientes)) {
            throw new RuntimeException('No se pudo vaciar por dependencias: ' . implode(', ', $quedan));
        }
        $pendientes = $quedan;
    }
    if (!empty($pendientes)) {
        throw new RuntimeException('Quedaron tablas del hotel demo sin vaciar: ' . implode(', ', $pendientes));
    }

    if ($borrarCuenta) {
        // El usuario demo solo se borra si ya no pertenece a ningun hotel:
        // si alguien lo reutilizo en otra parte, se conserva.
        $st = $pdo->prepare("SELECT id FROM usuarios WHERE nombre_usuario = ? LIMIT 1");
        $st->execute([DEMO_USUARIO]);
        $usuarioDemoId = (int) $st->fetchColumn();

        // Bitacoras de acceso: no tienen hotel_id, se acotan por el usuario demo.
        $st = $pdo->prepare("DELETE FROM login_intentos WHERE nombre_usuario = ? OR hotel_slug = ?");
        $st->execute([DEMO_USUARIO, DEMO_SLUG]);
        if ($st->rowCount() > 0) {
            $borrados['login_intentos'] = $st->rowCount();
        }
        if ($usuarioDemoId > 0) {
            $st = $pdo->prepare("DELETE FROM logs_acceso WHERE usuario_id = ?");
            $st->execute([$usuarioDemoId]);
            if ($st->rowCount() > 0) {
                $borrados['logs_acceso'] = $st->rowCount();
            }
        }

        if ($usuarioDemoId > 0) {
            $st = $pdo->prepare("SELECT COUNT(*) FROM hotel_usuarios WHERE usuario_id = ?");
            $st->execute([$usuarioDemoId]);
            if ((int) $st->fetchColumn() === 0) {
                $st = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
                $st->execute([$usuarioDemoId]);
                $borrados['usuarios'] = $st->rowCount();
            }
        }

        $asegurarEsDemo($hid); // ultima comprobacion antes de borrar la fila del hotel
        $st = $pdo->prepare("DELETE FROM hoteles WHERE id = ? AND slug = ?");
        $st->execute([$hid, DEMO_SLUG]);
        $borrados['hoteles'] = $st->rowCount();
    }

    return $borrados;
};

/* ---------------------------------------------------------------------- */
/* 6) Modo --limpiar                                                       */
/* ---------------------------------------------------------------------- */

if ($limpiar) {
    if (!$hotelDemo) {
        echo "No existe ningun hotel con slug '" . DEMO_SLUG . "'. Nada que limpiar.\n";
        exit(0);
    }

    d_titulo('Limpieza de la cuenta demo');
    d_paso("Hotel: {$hotelDemo['nombre']} (id {$hotelId}, slug " . DEMO_SLUG . ")");

    try {
        $pdo->beginTransaction();
        $borrados = $purgar($hotelId, true);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        d_error($e->getMessage());
        echo "No se borro nada (rollback completo).\n";
        exit(1);
    }

    $total = 0;
    foreach ($borrados as $tabla => $n) {
        d_paso(str_pad($tabla, 34) . $n);
        $total += $n;
    }
    $archivos = d_borrar_archivos($archivosViejos);
    if (!empty($archivosViejos)) {
        d_paso(str_pad('archivos en disco', 34) . $archivos . ' de ' . count($archivosViejos));
    }
    echo "\nListo: {$total} filas eliminadas, todas del hotel demo.\n";
    exit(0);
}

/* ---------------------------------------------------------------------- */
/* 7) Siembra                                                              */
/* ---------------------------------------------------------------------- */

d_titulo('Siembra de la cuenta de demostracion');

try {
    $pdo->beginTransaction();

    /* --- 7.1 Hotel ---------------------------------------------------- */

    $planId = $pdo->query("SELECT id FROM planes WHERE clave = 'personalizado' LIMIT 1")->fetchColumn();
    $planId = $planId !== false ? (int) $planId : null;

    if ($hotelDemo) {
        $asegurarEsDemo($hotelId);
        $borrados = $purgar($hotelId, false);
        d_paso('Hotel demo existente reutilizado (id ' . $hotelId . '); ' . array_sum($borrados) . ' filas viejas retiradas.');

        $pdo->prepare(
            "UPDATE hoteles
                SET nombre = ?, codigo = ?, razon_social = ?, rfc = ?, telefono = ?, email = ?,
                    direccion = ?, ciudad = ?, estado = ?, pais = 'Mexico',
                    zona_horaria = 'America/Mexico_City', moneda_codigo = 'MXN', moneda_simbolo = '$',
                    activo = 1, plan_id = ?, deleted_at = NULL, updated_at = NOW()
              WHERE id = ? AND slug = ?"
        )->execute([
            DEMO_NOMBRE, DEMO_CODIGO, 'Demostraciones Medisoft SA de CV', 'DME260101AB1',
            '55 0000 0000', 'demo@medisoft-hoteles.com',
            'Av. Ejemplo 100, Col. Muestra', 'Ciudad Ficticia', 'Jalisco',
            $planId, $hotelId, DEMO_SLUG,
        ]);
    } else {
        $pdo->prepare(
            "INSERT INTO hoteles
                (nombre, slug, codigo, razon_social, rfc, telefono, email, direccion, ciudad, estado,
                 pais, zona_horaria, moneda_codigo, moneda_simbolo, activo, plan_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Mexico', 'America/Mexico_City', 'MXN', '$', 1, ?, NOW(), NOW())"
        )->execute([
            DEMO_NOMBRE, DEMO_SLUG, DEMO_CODIGO, 'Demostraciones Medisoft SA de CV', 'DME260101AB1',
            '55 0000 0000', 'demo@medisoft-hoteles.com',
            'Av. Ejemplo 100, Col. Muestra', 'Ciudad Ficticia', 'Jalisco', $planId,
        ]);
        $hotelId = (int) $pdo->lastInsertId();
        d_paso('Hotel demo creado (id ' . $hotelId . ').');
    }

    $asegurarEsDemo($hotelId);

    // Fechas SIEMPRE del reloj de la BD que usa la app (time_zone -06:00),
    // no del reloj de PHP: de noche difieren un dia.
    $hoy = (string) $pdo->query('SELECT CURDATE()')->fetchColumn();
    $dia = static function (int $offset) use ($hoy): string {
        return date('Y-m-d', strtotime($hoy . ' ' . ($offset >= 0 ? '+' : '') . $offset . ' day'));
    };

    /* --- 7.2 Marca propia --------------------------------------------- */

    $pdo->prepare(
        "INSERT INTO hotel_branding
            (hotel_id, nombre_visual, color_primary, color_secondary, color_accent,
             sidebar_style, login_style, tema, activo, created_at, updated_at)
         VALUES (?, ?, '#1F4E79', '#F3F6FA', '#C9A227', 'clasico', 'clasico', 'cupertino', 1, NOW(), NOW())"
    )->execute([$hotelId, DEMO_NOMBRE]);
    d_paso('Marca propia (azul/dorado, tema cupertino).');

    /* --- 7.3 Roles y caja: los servicios reales del alta de hotel ------ */

    if (!(new Rol())->sembrarPresetsParaHotel($hotelId)) {
        throw new RuntimeException('No se pudieron sembrar los roles del hotel demo.');
    }
    $rolesSembrados = (int) $pdo->query("SELECT COUNT(*) FROM roles WHERE hotel_id = {$hotelId}")->fetchColumn();
    d_paso("Roles sembrados desde los presets: {$rolesSembrados}.");

    if (!(new Caja())->ensureDefaultCajaForHotel($hotelId)) {
        throw new RuntimeException('No se pudo crear la caja principal del hotel demo.');
    }
    $cajaId = (int) $pdo->query("SELECT id FROM cajas WHERE hotel_id = {$hotelId} ORDER BY id LIMIT 1")->fetchColumn();
    d_paso("Caja principal lista (id {$cajaId}).");

    /* --- 7.4 Usuario gerente demo ------------------------------------- */

    $st = $pdo->prepare("SELECT id FROM usuarios WHERE nombre_usuario = ? LIMIT 1");
    $st->execute([DEMO_USUARIO]);
    $usuarioId = (int) $st->fetchColumn();
    $hash = password_hash($password, PASSWORD_DEFAULT);

    if ($usuarioId > 0) {
        // Si el nombre ya existe pero pertenece a OTRO hotel, no es nuestro:
        // mejor abortar que reescribirle la contrasena a alguien.
        $st = $pdo->prepare("SELECT COUNT(*) FROM hotel_usuarios WHERE usuario_id = ? AND hotel_id <> ?");
        $st->execute([$usuarioId, $hotelId]);
        if ((int) $st->fetchColumn() > 0) {
            throw new RuntimeException("El usuario '" . DEMO_USUARIO . "' ya pertenece a otro hotel. Se aborta.");
        }
        $pdo->prepare(
            "UPDATE usuarios SET password = ?, nombre_completo = ?, email = ?, telefono = ?,
                    rol = 'gerente', activo = 1, updated_at = NOW()
              WHERE id = ?"
        )->execute([$hash, 'Gerente Demo Medisoft', 'demo@medisoft-hoteles.com', '55 0000 0001', $usuarioId]);
    } else {
        $pdo->prepare(
            "INSERT INTO usuarios (nombre_usuario, password, nombre_completo, email, telefono, rol, activo, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'gerente', 1, NOW(), NOW())"
        )->execute([DEMO_USUARIO, $hash, 'Gerente Demo Medisoft', 'demo@medisoft-hoteles.com', '55 0000 0001']);
        $usuarioId = (int) $pdo->lastInsertId();
    }

    $roleGerenteId = $pdo->query("SELECT id FROM roles WHERE hotel_id = {$hotelId} AND clave = 'gerente' LIMIT 1")->fetchColumn();
    $roleGerenteId = $roleGerenteId !== false ? (int) $roleGerenteId : null;

    $pdo->prepare(
        "INSERT INTO hotel_usuarios (hotel_id, usuario_id, rol, role_id, es_principal, activo, created_at, updated_at)
         VALUES (?, ?, 'gerente', ?, 1, 1, NOW(), NOW())"
    )->execute([$hotelId, $usuarioId, $roleGerenteId]);
    d_paso("Usuario gerente demo listo (usuarios.id {$usuarioId}).");

    /* --- 7.5 Bloques a la venta contratados --------------------------- */

    $moduloModel = new Modulo();
    $idsBloques = [];
    $st = $pdo->prepare("SELECT id, clave FROM modulos WHERE clave IN ('" . implode("','", DEMO_BLOQUES) . "')");
    $st->execute();
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $fila) {
        $idsBloques[$fila['clave']] = (int) $fila['id'];
    }
    $faltantes = array_diff(DEMO_BLOQUES, array_keys($idsBloques));
    if (!empty($faltantes)) {
        throw new RuntimeException('Faltan bloques en el catalogo: ' . implode(', ', $faltantes));
    }

    // Se usa el motor real (respeta core, internos y bloqueados globalmente);
    // sembrar hotel_modulos a mano se saltaria esas reglas.
    if (!$moduloModel->actualizarModulosHotel($hotelId, array_values($idsBloques), $usuarioId)) {
        throw new RuntimeException('No se pudieron contratar los bloques opcionales del hotel demo.');
    }
    $nActivos = (int) $pdo->query("SELECT COUNT(*) FROM hotel_modulos WHERE hotel_id = {$hotelId} AND activo = 1")->fetchColumn();
    d_paso('Bloques a la venta contratados: ' . implode(', ', DEMO_BLOQUES) . " (+core: {$nActivos} filas activas).");

    /* --- 7.6 Catalogo de tipos de habitacion -------------------------- */

    $tipos = [
        ['sencilla',   'Sencilla',   'Una cama matrimonial, ideal para 1 o 2 personas.', 2,  850.00, 1],
        ['doble',      'Doble',      'Dos camas matrimoniales.',                          4, 1200.00, 2],
        ['triple',     'Triple',     'Tres camas, pensada para grupos pequenos.',         6, 1500.00, 3],
        ['cuadruple',  'Cuadruple',  'Cuatro camas, la mas amplia del hotel.',            8, 1900.00, 4],
    ];
    $insTipo = $pdo->prepare(
        "INSERT INTO tipos_habitacion (hotel_id, codigo, nombre, descripcion, capacidad_default, precio_base_default, activo, orden, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW(), NOW())"
    );
    foreach ($tipos as $t) {
        $insTipo->execute([$hotelId, $t[0], $t[1], $t[2], $t[3], $t[4], $t[5]]);
    }
    d_paso('Catalogo de tipos de habitacion: ' . count($tipos) . '.');

    /* --- 7.7 Habitaciones --------------------------------------------- */

    // [numero, tipo, piso, precio, capacidad, indiv, matrim, estado]
    // El estado es la foto de HOY y tiene que cuadrar con las reservaciones
    // de mas abajo: las 'ocupada' son exactamente las de los checked_in.
    $habitacionesDef = [
        ['101', 'sencilla',  1,  850.00, 2, 0, 1, 'disponible'],
        ['102', 'sencilla',  1,  850.00, 2, 0, 1, 'limpieza'],
        ['103', 'doble',     1, 1200.00, 4, 2, 1, 'ocupada'],
        ['104', 'doble',     1, 1200.00, 4, 2, 1, 'disponible'],
        ['105', 'triple',    1, 1500.00, 6, 2, 2, 'ocupada'],
        ['106', 'cuadruple', 1, 1900.00, 8, 2, 3, 'disponible'],
        ['201', 'sencilla',  2,  900.00, 2, 0, 1, 'ocupada'],
        ['202', 'doble',     2, 1250.00, 4, 2, 1, 'disponible'],
        ['203', 'doble',     2, 1250.00, 4, 2, 1, 'disponible'],
        ['204', 'triple',    2, 1550.00, 6, 2, 2, 'limpieza'],
        ['205', 'cuadruple', 2, 1950.00, 8, 2, 3, 'ocupada'],
        ['206', 'doble',     2, 1250.00, 4, 2, 1, 'mantenimiento'],
    ];

    $insHab = $pdo->prepare(
        "INSERT INTO habitaciones
            (hotel_id, numero, tipo, capacidad_personas, camas_individuales, camas_matrimoniales,
             piso, precio_base, estado, caracteristicas, tiene_aire_acondicionado, tiene_tv,
             tiene_bano_privado, activa, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 1, 1, 1, NOW(), NOW())"
    );
    $insLlave = $pdo->prepare(
        "INSERT INTO control_llaves (habitacion_id, estado, tiene_llave, created_at) VALUES (?, 'disponible', 1, NOW())"
    );

    $hab = []; // numero => id
    foreach ($habitacionesDef as [$num, $tipo, $piso, $precio, $cap, $ind, $mat, $estado]) {
        $insHab->execute([
            $hotelId, $num, $tipo, $cap, $ind, $mat, $piso, $precio, $estado,
            'Aire acondicionado, TV, bano privado, wifi y caja de seguridad.',
        ]);
        $hab[$num] = (int) $pdo->lastInsertId();
        $insLlave->execute([$hab[$num]]);
    }
    d_paso('Habitaciones: ' . count($hab) . ' en 2 pisos, con su control de llaves.');

    /* --- 7.8 Huespedes ------------------------------------------------- */

    // Nombres inventados a proposito; ninguno corresponde a persona real.
    // Dos extranjeros con nacionalidad en datos_extra_json para que el
    // reporte de Procedencia tenga algo que mostrar. El huesped 3 lleva regla
    // de descuento en su perfil: es el "cliente de siempre" del guion §5.
    // [nombre, tel, mail, estado, ciudad, nacionalidad, descuento_tipo, descuento_valor]
    $huespedesDef = [
        ['Maria Fernanda Ruiz Alcantara', '33 1122 3344', 'mf.ruiz@correo-demo.mx',      'Jalisco',        'Guadalajara', null, null, null],
        ['Jorge Alberto Cadena Prieto',   '55 2233 4455', 'j.cadena@correo-demo.mx',     'Ciudad de Mexico','Coyoacan',   null, null, null],
        ['Rocio Estrada Villalpando',     '81 3344 5566', 'r.estrada@correo-demo.mx',    'Nuevo Leon',     'Monterrey',   null, 'porcentaje', 10.00],
        ['Luis Ernesto Barragan Sepulveda','44 4455 6677','l.barragan@correo-demo.mx',   'Guanajuato',     'Leon',        null, null, null],
        ['Fernanda Nieto Salcedo',        '99 5566 7788', 'f.nieto@correo-demo.mx',      'Yucatan',        'Merida',      null, null, null],
        ['Adrian Covarrubias Pena',       '22 6677 8899', 'a.covarrubias@correo-demo.mx','Puebla',         'Puebla',      null, null, null],
        ['Norma Angelica Zaldivar Fierro','66 7788 9900', 'n.zaldivar@correo-demo.mx',   'Michoacan',      'Morelia',     null, null, null],
        ['Sergio Damian Olvera Mancilla', '77 8899 0011', 's.olvera@correo-demo.mx',     'Oaxaca',         'Oaxaca',      null, null, null],
        ['Karla Michelle Anguiano Bustos','33 9900 1122', 'k.anguiano@correo-demo.mx',   null,             'Chicago',     'Estados Unidos', null, null],
        ['Ivan Alejandro Zepeda Carranza','55 1011 1213', 'i.zepeda@correo-demo.mx',     null,             'Toronto',     'Canada', null, null],
    ];

    $insHuesped = $pdo->prepare(
        "INSERT INTO huespedes
            (hotel_id, nombre_completo, telefono, email, procedencia_estado, procedencia_ciudad,
             descuento_tipo, descuento_valor, notas, datos_extra_json, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
    );
    $hue = [];
    $descuentoDe = []; // clave de huesped => ['tipo' => ..., 'valor' => ...]
    foreach ($huespedesDef as $i => [$nombre, $tel, $mail, $edo, $ciudad, $nacionalidad, $descTipo, $descValor]) {
        $insHuesped->execute([
            $hotelId, $nombre, $tel, $mail, $edo, $ciudad, $descTipo, $descValor,
            $descTipo
                ? 'Huesped ficticio de la demo. Cliente frecuente: descuento acordado en su perfil.'
                : 'Huesped ficticio de la cuenta de demostracion.',
            $nacionalidad ? json_encode(['nacionalidad' => $nacionalidad], JSON_UNESCAPED_UNICODE) : null,
        ]);
        $hue[$i + 1] = (int) $pdo->lastInsertId();
        if ($descTipo) {
            $descuentoDe[$i + 1] = ['tipo' => $descTipo, 'valor' => (float) $descValor];
        }
    }
    d_paso('Huespedes ficticios: ' . count($hue) . ' (2 extranjeros; ' . count($descuentoDe) . ' con regla de descuento en su perfil).');

    /* --- 7.8-bis Tarifas dinamicas: temporada vigente ------------------ */

    // Vigente DESDE HOY: el guion §6 pide cotizar en vivo, y una regla que
    // empieza pasado manana obliga al vendedor a inventar fechas. Se corta a 8
    // dias a proposito: la reservacion del huesped con descuento cae fuera
    // (dia +10) y asi cada bloque se demuestra por separado, sin apilar
    // incremento y descuento en el mismo desglose.
    $tarifaRegla = [
        'nombre' => 'Temporada alta - Fin de semana largo',
        'pct'    => 25.00,
        'desde'  => $hoy,
        'hasta'  => $dia(7),
    ];

    $pdo->prepare(
        "INSERT INTO temporadas_hotel (hotel_id, nombre, `desde`, `hasta`, intensidad, recurrente_anual, notas, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'alta', 0, ?, NOW(), NOW())"
    )->execute([
        $hotelId, 'Fin de semana largo', $tarifaRegla['desde'], $tarifaRegla['hasta'],
        'Puente con alta demanda: la tarifa sube un 25%.',
    ]);

    $pdo->prepare(
        "INSERT INTO incrementos_tarifas
            (hotel_id, nombre, descripcion, tipo_incremento, clase, valor_incremento, alcance,
             tipos_habitacion, habitaciones, es_permanente, fecha_inicio, fecha_fin, activo,
             prioridad, usuario_id, created_at, updated_at)
         VALUES (?, ?, ?, 'porcentaje', 'incremento', ?, 'global', NULL, NULL, 0, ?, ?, 1, 10, ?, NOW(), NOW())"
    )->execute([
        $hotelId, $tarifaRegla['nombre'],
        'Incremento de temporada para el fin de semana largo. Aplica a todas las habitaciones.',
        number_format($tarifaRegla['pct'], 2, '.', ''),
        $tarifaRegla['desde'], $tarifaRegla['hasta'], $usuarioId,
    ]);
    d_paso(sprintf(
        'Tarifas dinamicas: regla "%s" +%s%% vigente %s a %s (global, activa).',
        $tarifaRegla['nombre'], rtrim(rtrim(number_format($tarifaRegla['pct'], 2), '0'), '.'),
        $tarifaRegla['desde'], $tarifaRegla['hasta']
    ));

    /* --- 7.9 Reservaciones --------------------------------------------- */

    // [huesped, [habitaciones], entradaOffset, salidaOffset, estado, pagadoPct, metodo]
    // Ninguna habitacion se solapa consigo misma: la disponibilidad que
    // calculan los 4 predicados de solapamiento tiene que salir coherente o
    // el picker de la demo miente.
    $reservasDef = [
        // Pasadas (checked_out), liquidadas
        [1,  ['101'],        -12, -10, 'checked_out', 1.0,  'efectivo'],
        [2,  ['104'],         -8,  -6, 'checked_out', 1.0,  'tarjeta'],
        [3,  ['202', '203'],  -5,  -3, 'checked_out', 1.0,  'transferencia'],
        // En casa hoy (checked_in) -> son las 4 habitaciones 'ocupada'
        [4,  ['103'],         -2,   1, 'checked_in',  0.5,  'efectivo'],
        [5,  ['105'],         -1,   2, 'checked_in',  1.0,  'tarjeta'],
        [6,  ['201'],          0,   2, 'checked_in',  0.4,  'efectivo'],
        [7,  ['205'],         -3,   1, 'checked_in',  1.0,  'transferencia'],
        // Llegan hoy (una con el cuarto aun en limpieza: caso real del tablero)
        [8,  ['106'],          0,   2, 'confirmada',  0.3,  'transferencia'],
        [9,  ['102'],          0,   1, 'confirmada',  0.0,  'efectivo'],
        // Futuras
        [10, ['104'],          3,   6, 'confirmada',  0.25, 'tarjeta'],
        [1,  ['202'],          5,   8, 'confirmada',  0.0,  'efectivo'],
        [3,  ['203', '204'],  10,  13, 'confirmada',  0.2,  'transferencia'],
        // Cancelada: el tablero necesita ver tambien lo que no se concreto
        [2,  ['101'],          2,   4, 'cancelada',   0.0,  'efectivo'],
    ];

    $insRes = $pdo->prepare(
        "INSERT INTO reservaciones
            (hotel_id, huesped_id, total_habitaciones, habitaciones_cortesia, vehiculos_estimados,
             fecha_entrada, hora_llegada_estimada, hora_entrada, fecha_salida, hora_salida,
             precio_total, descuento_total, descuento_detalle_json, metodo_pago, estado, notas,
             usuario_registro_id, created_at, updated_at)
         VALUES (?, ?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $insResHab = $pdo->prepare(
        "INSERT INTO reservacion_habitaciones (hotel_id, reservacion_id, habitacion_id, precio, es_cortesia)
         VALUES (?, ?, ?, ?, 0)"
    );
    $insPago = $pdo->prepare(
        "INSERT INTO reservacion_pagos (hotel_id, reservacion_id, metodo_pago, monto, referencia, created_at)
         VALUES (?, ?, ?, ?, ?, ?)"
    );

    $precioDe = [];
    foreach ($habitacionesDef as [$num, , , $precio]) {
        $precioDe[$num] = (float) $precio;
    }

    // El precio de la noche lo manda la regla de temporada: si la ENTRADA cae
    // en la ventana, sube el porcentaje. Es el mismo criterio del motor real
    // (calcularPrecioConIncremento se evalua con fecha_entrada, no noche a
    // noche), asi el tablero no contradice lo que cotiza la pantalla.
    $precioNocheDe = static function (string $num, string $entrada) use ($precioDe, $tarifaRegla): array {
        $base = (float) $precioDe[$num];
        if ($entrada >= $tarifaRegla['desde'] && $entrada <= $tarifaRegla['hasta']) {
            $aumento = round($base * ($tarifaRegla['pct'] / 100), 2);
            return ['base' => $base, 'noche' => round($base + $aumento, 2), 'aumento' => $aumento];
        }
        return ['base' => $base, 'noche' => $base, 'aumento' => 0.0];
    };

    $reservas = [];   // indice => ['id'=>, 'estado'=>, 'total'=>, 'pagado'=>, 'huesped'=>]
    $conIncremento = 0;
    $conDescuento = 0;
    foreach ($reservasDef as $idx => [$huespedKey, $nums, $offEnt, $offSal, $estado, $pagadoPct, $metodo]) {
        $entrada = $dia($offEnt);
        $salida  = $dia($offSal);
        $noches  = max(1, (int) round((strtotime($salida) - strtotime($entrada)) / 86400));

        // 1) Subtotal por habitacion, ya con el incremento de temporada.
        $lineas = [];
        $subtotal = 0.0;
        $aumentoTotal = 0.0;
        foreach ($nums as $n) {
            $p = $precioNocheDe($n, $entrada);
            $lineas[$n] = round($p['noche'] * $noches, 2);
            $subtotal += $lineas[$n];
            $aumentoTotal += round($p['aumento'] * $noches, 2);
        }
        $subtotal = round($subtotal, 2);

        // 2) Descuento del perfil del huesped sobre ese subtotal (mismo orden
        //    que Reservacion::crear: primero tarifa, luego descuento).
        $reglaDesc = $descuentoDe[$huespedKey] ?? null;
        $descuento = 0.0;
        $detalleHuesped = ['descuento' => 0, 'tipo' => null, 'valor' => 0];
        if ($reglaDesc) {
            $descuento = $reglaDesc['tipo'] === 'porcentaje'
                ? round($subtotal * ($reglaDesc['valor'] / 100), 2)
                : min((float) $reglaDesc['valor'], $subtotal);
            $detalleHuesped = ['descuento' => $descuento, 'tipo' => $reglaDesc['tipo'], 'valor' => $reglaDesc['valor']];
        }

        // 3) Invariante del proyecto: precio_total = Suma(detalle) - descuento_total.
        $total = round($subtotal - $descuento, 2);

        $detalleJson = json_encode([
            'subtotal' => $subtotal,
            'descuento_tipo' => 0,
            'descuento_huesped' => $descuento,
            'descuento_auto' => $descuento,
            'descuento_aplicado' => $descuento,
            'tipo' => [],
            'huesped' => $detalleHuesped,
        ]);

        $notas = 'Reservacion de demostracion.';
        if ($aumentoTotal > 0) {
            $notas .= ' Tarifa de temporada aplicada: ' . $tarifaRegla['nombre'] . ' (+' . (int) $tarifaRegla['pct'] . '%).';
            $conIncremento++;
        }
        if ($descuento > 0) {
            $notas .= ' Descuento de cliente frecuente aplicado desde su perfil.';
            $conDescuento++;
        }

        $horaEntrada = in_array($estado, ['checked_in', 'checked_out'], true) ? '15:10:00' : null;
        $horaSalida  = $estado === 'checked_out' ? '11:40:00' : null;

        $insRes->execute([
            $hotelId, $hue[$huespedKey], count($nums), ($idx % 3 === 0 ? 1 : 0),
            $entrada, '15:00:00', $horaEntrada, $salida, $horaSalida,
            number_format($total, 2, '.', ''), number_format($descuento, 2, '.', ''), $detalleJson,
            $metodo, $estado, $notas, $usuarioId,
            $entrada . ' 09:30:00',
        ]);
        $resId = (int) $pdo->lastInsertId();

        foreach ($nums as $n) {
            $insResHab->execute([$hotelId, $resId, $hab[$n], number_format($lineas[$n], 2, '.', '')]);
        }

        // El dinero cobrado vive en reservacion_pagos: es de ahi (no de
        // reservaciones.metodo_pago) de donde resumenPagos deriva el saldo.
        $pagado = round($total * $pagadoPct, 2);
        if ($pagado > 0) {
            $insPago->execute([
                $hotelId, $resId, $metodo, number_format($pagado, 2, '.', ''),
                $metodo === 'efectivo' ? null : 'DEMO-' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                $entrada . ' 15:20:00',
            ]);
        }

        $reservas[$idx] = [
            'id' => $resId, 'estado' => $estado, 'total' => $total,
            'pagado' => $pagado, 'huesped' => $huespedKey, 'metodo' => $metodo,
        ];
    }
    d_paso('Reservaciones: ' . count($reservas) . ' (3 con checkout, 4 hospedadas, 2 llegan hoy, 3 futuras, 1 cancelada).');
    d_paso("   de esas, {$conIncremento} con incremento de temporada y {$conDescuento} con descuento de huesped.");

    /* --- 7.10 Caja: turno abierto con movimientos ---------------------- */

    $categoriasDef = [
        ['Hospedaje',                 'ingreso', 'fas fa-bed',        '#1F4E79'],
        ['Consumo en habitacion',     'ingreso', 'fas fa-wine-glass', '#8039D0'],
        ['Otros ingresos',            'ingreso', 'fas fa-hand-holding-dollar', '#2E7D5B'],
        ['Insumos de limpieza',       'gasto',   'fas fa-broom',      '#2F77E0'],
        ['Servicios (luz, gas, agua)','gasto',   'fas fa-bolt',       '#E08A2F'],
        ['Mantenimiento',             'gasto',   'fas fa-screwdriver-wrench', '#C0392B'],
    ];
    $insCat = $pdo->prepare(
        "INSERT INTO categorias_movimientos (hotel_id, nombre, tipo, descripcion, icono, color, activa, orden, created_at)
         VALUES (?, ?, ?, ?, ?, ?, 1, ?, NOW())"
    );
    $cat = [];
    foreach ($categoriasDef as $i => [$nombre, $tipo, $icono, $color]) {
        $insCat->execute([$hotelId, $nombre, $tipo, 'Concepto de la cuenta demo.', $icono, $color, $i + 1]);
        $cat[$nombre] = (int) $pdo->lastInsertId();
    }

    $pdo->prepare(
        "INSERT INTO cortes_caja
            (hotel_id, caja_id, fecha_apertura, monto_inicial, estado, observaciones, usuario_apertura_id, created_at, updated_at)
         VALUES (?, ?, ?, 2000.00, 'abierto', 'Turno de demostracion.', ?, NOW(), NOW())"
    )->execute([$hotelId, $cajaId, $hoy . ' 07:00:00', $usuarioId]);
    $corteId = (int) $pdo->lastInsertId();

    // [tipo, categoriaNombre|null, descripcion, monto|null, metodo, referencia, reservacionIdx|null, hora]
    // Los de categoria NULL imitan a los movimientos de SERVICIO del sistema
    // (anticipos, cobros CxC...): el nombre viaja solo en el texto.
    // Solo llevan reservacion_id los dos cuyo cobro ocurrio HOY: ese campo es
    // CONTABLE (una cancelacion devuelve todo ingreso ligado), no una etiqueta.
    // Su monto va en NULL a proposito = "lo que realmente pago esa reserva":
    // hardcodearlo se desincroniza en cuanto una regla de tarifa cambia el total.
    $movimientosDef = [
        ['ingreso', 'Hospedaje',             'Check-in habitacion 201 - Adrian Covarrubias',     null, 'efectivo',      null,          5,    '08:15:00'],
        ['ingreso', null,                    'Anticipo de reservacion habitacion 106',           null, 'transferencia', 'TRF-771204',  7,    '09:05:00'],
        ['ingreso', 'Consumo en habitacion', 'Consumo de minibar habitacion 105',              465.00, 'efectivo',      null,          null, '10:40:00'],
        ['ingreso', 'Otros ingresos',        'Renta de sala de juntas (medio dia)',            950.00, 'transferencia', 'TRF-771233',  null, '13:10:00'],
        ['ingreso', 'Otros ingresos',        'Lavanderia express habitacion 205',              380.00, 'efectivo',      null,          null, '16:05:00'],
        ['gasto',   'Insumos de limpieza',   'Compra de cloro, jabon y bolsas',                640.00, 'efectivo',      null,          null, '09:45:00'],
        ['gasto',   'Servicios (luz, gas, agua)', 'Recarga de gas estacionario',              1250.00, 'transferencia', 'TRF-771240',  null, '11:15:00'],
        ['gasto',   'Mantenimiento',         'Reparacion de bomba de la alberca',              780.00, 'efectivo',      null,          null, '14:05:00'],
        ['gasto',   'Insumos de limpieza',   'Reposicion de amenidades de bano',               430.00, 'tarjeta',       'AUT-449188',  null, '16:30:00'],
    ];

    $insMov = $pdo->prepare(
        "INSERT INTO movimientos_caja
            (hotel_id, tipo, categoria, categoria_id, descripcion, monto, metodo_pago, referencia,
             reservacion_id, usuario_id, corte_id, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $ingresosTurno = 0.0;
    $gastosTurno = 0.0;
    foreach ($movimientosDef as [$tipo, $catNombre, $desc, $monto, $metodo, $ref, $resIdx, $hora]) {
        // Monto NULL = el cobro real de la reservacion ligada (ver nota arriba).
        if ($monto === null) {
            if ($resIdx === null || ($reservas[$resIdx]['pagado'] ?? 0) <= 0) {
                throw new RuntimeException('Movimiento de caja sin monto y sin reservacion pagada de referencia.');
            }
            $monto = (float) $reservas[$resIdx]['pagado'];
        }
        $insMov->execute([
            $hotelId, $tipo,
            $catNombre ?? 'Anticipo reservacion',
            $catNombre !== null ? $cat[$catNombre] : null,
            $desc, number_format($monto, 2, '.', ''), $metodo, $ref,
            $resIdx !== null ? $reservas[$resIdx]['id'] : null,
            $usuarioId, $corteId, $hoy . ' ' . $hora,
        ]);
        if ($tipo === 'ingreso') {
            $ingresosTurno += $monto;
        } else {
            $gastosTurno += $monto;
        }
    }
    d_paso(sprintf(
        'Caja: corte abierto (id %d) con %d movimientos; ingresos $%s / gastos $%s.',
        $corteId, count($movimientosDef), number_format($ingresosTurno, 2), number_format($gastosTurno, 2)
    ));

    /* --- 7.11 Inventario (uno bajo minimo) ----------------------------- */

    $insCatInv = $pdo->prepare(
        "INSERT INTO inventario_categorias (hotel_id, nombre, descripcion, orden, activo, created_at)
         VALUES (?, ?, ?, ?, 1, NOW())"
    );
    $catInv = [];
    foreach ([
        ['Amenidades', 'Articulos de cortesia para el huesped'],
        ['Limpieza', 'Quimicos y consumibles de ama de llaves'],
        ['Blancos', 'Toallas, sabanas y cubrecamas'],
        ['Minibar', 'Bebidas y botanas de venta en habitacion'],
    ] as $i => [$nombre, $desc]) {
        $insCatInv->execute([$hotelId, $nombre, $desc, $i + 1]);
        $catInv[$nombre] = (int) $pdo->lastInsertId();
    }

    // [codigo, nombre, categoria, unidad, stock, minimo, costo]
    // AME-003 y LIM-002 quedan por DEBAJO del minimo a proposito: la pantalla
    // de inventario necesita algo que gritar en la demo.
    $productosDef = [
        ['AME-001', 'Shampoo individual 30 ml',   'Amenidades', 'pieza',  180, 60,  6.50],
        ['AME-002', 'Jabon de tocador 20 g',      'Amenidades', 'pieza',  240, 80,  4.20],
        ['AME-003', 'Kit dental de cortesia',     'Amenidades', 'pieza',   12, 40, 11.00],
        ['LIM-001', 'Cloro concentrado 5 L',      'Limpieza',   'galon',   14,  6, 92.00],
        ['LIM-002', 'Bolsa negra jumbo (paq 50)', 'Limpieza',   'paquete',  3, 10, 74.50],
        ['BLA-001', 'Toalla de bano blanca',      'Blancos',    'pieza',   96, 40, 145.00],
        ['BLA-002', 'Juego de sabanas matrimonial','Blancos',   'juego',   52, 24, 320.00],
        ['MIN-001', 'Agua embotellada 600 ml',    'Minibar',    'pieza',  310, 96,  7.80],
        ['MIN-002', 'Refresco lata 355 ml',       'Minibar',    'pieza',  144, 60, 12.40],
        ['MIN-003', 'Botana salada 45 g',         'Minibar',    'pieza',   88, 48, 14.90],
    ];
    $insProd = $pdo->prepare(
        "INSERT INTO inventario_productos
            (hotel_id, codigo, nombre, descripcion, categoria_id, unidad_medida,
             stock_actual, stock_minimo, costo_unitario, descuento_automatico, activo, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 1, NOW(), NOW())"
    );
    $bajoMinimo = 0;
    foreach ($productosDef as [$codigo, $nombre, $catNombre, $unidad, $stock, $minimo, $costo]) {
        $insProd->execute([
            $hotelId, $codigo, $nombre, 'Producto ficticio de la cuenta demo.',
            $catInv[$catNombre], $unidad,
            number_format($stock, 2, '.', ''), number_format($minimo, 2, '.', ''), number_format($costo, 2, '.', ''),
        ]);
        if ($stock < $minimo) {
            $bajoMinimo++;
        }
    }
    d_paso('Inventario: ' . count($productosDef) . ' productos en ' . count($catInv) . " categorias ({$bajoMinimo} bajo minimo).");

    /* --- 7.12 Facturacion: solicitudes en distintos estados ------------ */

    $insFac = $pdo->prepare(
        "INSERT INTO solicitudes_factura
            (hotel_id, reservacion_id, requiere_factura, tipo, estatus, rfc, razon_social,
             regimen_fiscal, uso_cfdi, codigo_postal_fiscal, email_factura, metodo_pago_principal,
             monto_total, notas, usuario_registro_id, fecha_facturada, numero_factura, created_at, updated_at)
         VALUES (?, ?, 'si', 'cliente', ?, ?, ?, '601', 'G03', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $facturasDef = [
        // [reservacionIdx, estatus, rfc, razon social, cp, correo, facturada?, folio]
        [0, 'completada', 'RUA900312J45', 'Servicios Ruiz Alcantara SA de CV', '44100', 'facturas@ruizalcantara-demo.mx', true,  'A-1041'],
        [2, 'en_proceso', 'EVI850720K12', 'Estrada Villalpando y Asociados',   '64000', 'admin@estradavilla-demo.mx',    false, null],
        [4, 'pendiente',  null,           null,                                 null,   'f.nieto@correo-demo.mx',        false, null],
    ];
    foreach ($facturasDef as [$resIdx, $estatus, $rfc, $razon, $cp, $mail, $facturada, $folio]) {
        $r = $reservas[$resIdx];
        $insFac->execute([
            $hotelId, $r['id'], $estatus, $rfc, $razon, $cp, $mail, $r['metodo'],
            number_format($r['total'], 2, '.', ''),
            'Solicitud de demostracion.', $usuarioId,
            $facturada ? $hoy . ' 10:00:00' : null, $folio,
            $hoy . ' 08:00:00',
        ]);
    }
    d_paso('Facturacion: ' . count($facturasDef) . ' solicitudes (completada, en proceso, pendiente).');

    /* --- 7.13 Reputacion: encuestas respondidas, notas variadas -------- */

    // Se incluyen calificaciones BAJAS a proposito: desde jul-26 el flujo ya
    // no filtra por calificacion y la demo tiene que mostrar como se ve una
    // queja, no solo los cincos.
    $insEnc = $pdo->prepare(
        "INSERT INTO reputacion_encuestas
            (hotel_id, reservacion_id, token, estado, calificacion, nps, comentario, canal_envio,
             enviada_at, respondida_at, expires_at, creado_por, created_at, updated_at)
         VALUES (?, ?, ?, 'respondida', ?, ?, ?, 'whatsapp', ?, ?, ?, ?, ?, NOW())"
    );
    $encuestasDef = [
        [0, 5, 10, 'Todo excelente. El personal de recepcion muy atento y el cuarto impecable.', -9],
        [1, 3,  6, 'La habitacion estaba bien, pero el aire acondicionado hacia mucho ruido de madrugada.', -5],
        [2, 2,  3, 'Tardaron casi una hora en entregarnos la habitacion y nadie nos aviso por que.', -2],
    ];
    foreach ($encuestasDef as [$resIdx, $calif, $nps, $comentario, $offResp]) {
        $r = $reservas[$resIdx];
        $insEnc->execute([
            $hotelId, $r['id'], bin2hex(random_bytes(16)), $calif, $nps, $comentario,
            $dia($offResp - 1) . ' 12:00:00', $dia($offResp) . ' 19:30:00',
            $dia($offResp + 30) . ' 23:59:59', $usuarioId, $dia($offResp - 1) . ' 12:00:00',
        ]);
    }
    d_paso('Reputacion: ' . count($encuestasDef) . ' encuestas respondidas (5, 3 y 2 estrellas).');

    /* --- 7.14 Centro documental: PDFs reales, vinculados -------------- */

    // Los tipos son globales (hotel_id NULL, migracion 20260615_005): no se
    // siembran por hotel, se referencian.
    $tipoDocId = [];
    foreach ($pdo->query("SELECT id, clave FROM documento_tipos WHERE hotel_id IS NULL")->fetchAll(PDO::FETCH_ASSOC) as $t) {
        $tipoDocId[$t['clave']] = (int) $t['id'];
    }

    // Ruta identica a la que arma Documento::prepararStoragePrivado, o la
    // descarga y la vista previa no encuentran el archivo.
    $anio = date('Y');
    $mes  = date('m');
    $dirRelativo = 'documentos/hotel_' . $hotelId . '/' . $anio . '/' . $mes;
    $raizDocs = rtrim(STORAGE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'documentos';
    $dirAbsoluto = rtrim(STORAGE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR
        . str_replace('/', DIRECTORY_SEPARATOR, $dirRelativo);
    if (!is_dir($dirAbsoluto) && !mkdir($dirAbsoluto, 0750, true) && !is_dir($dirAbsoluto)) {
        throw new RuntimeException('No se pudo preparar el storage privado de la demo: ' . $dirAbsoluto);
    }
    // Cada nivel, empezando por la raiz compartida: si este guion la creo, el
    // sitio tiene que poder seguir escribiendo ahi (subidas reales de OTROS hoteles).
    d_ceder_al_servidor($raizDocs);
    foreach (['hotel_' . $hotelId, 'hotel_' . $hotelId . '/' . $anio, 'hotel_' . $hotelId . '/' . $anio . '/' . $mes] as $tramo) {
        d_ceder_al_servidor($raizDocs . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $tramo));
    }

    // [tipo, titulo, descripcion, etiquetas, estado, entidad_tipo, clave, relacion, cuerpo del PDF]
    $documentosDef = [
        [
            'contrato', 'Contrato de grupo - Rocio Estrada Villalpando',
            'Convenio de tarifa para grupo de 2 habitaciones.',
            'contrato, grupo, tarifa', 'activo', 'huesped', ['huesped', 3], 'titular del contrato',
            ['Documento de DEMOSTRACION. Datos ficticios.', '', 'Hotel Demo Medisoft', 'Convenio de tarifa de grupo',
             'Titular: Rocio Estrada Villalpando', 'Vigencia: temporada 2026', 'Descuento acordado: 10% sobre tarifa publicada'],
        ],
        [
            'comprobante', 'Comprobante de transferencia - estancia de grupo',
            'Transferencia bancaria por la estancia liquidada.',
            'pago, transferencia, liquidado', 'activo', 'reservacion', ['reservacion', 2], 'comprobante de pago',
            ['Documento de DEMOSTRACION. Datos ficticios.', '', 'Comprobante de transferencia',
             'Concepto: hospedaje, 2 habitaciones', 'Referencia: TRF-DEMO-0043', 'Banco emisor: Banco Ficticio SA'],
        ],
        [
            'identificacion', 'Identificacion de huesped (muestra archivada)',
            'Copia de identificacion de una estancia ya cerrada.',
            'identificacion, historico, archivado', 'archivado', 'huesped', ['huesped', 1], 'identificacion del titular',
            ['Documento de DEMOSTRACION. Persona inventada, no corresponde a nadie real.', '',
             'Identificacion del huesped', 'Nombre: Maria Fernanda Ruiz Alcantara', 'Folio interno: DEMO-ID-0001',
             'Estado: archivado (se recupera desde el Centro documental)'],
        ],
    ];

    $insDoc = $pdo->prepare(
        "INSERT INTO documentos
            (hotel_id, documento_tipo_id, nombre_original, nombre_archivo, storage_path, mime_type,
             size_bytes, sha256, titulo, descripcion, etiquetas, estado, subido_por_usuario_id,
             created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, 'application/pdf', ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
    );
    $insDocEnt = $pdo->prepare(
        "INSERT INTO documento_entidades (hotel_id, documento_id, entidad_tipo, entidad_id, relacion, created_at, updated_at)
         VALUES (?, ?, ?, ?, ?, NOW(), NOW())"
    );

    $bytesDocumentos = 0;
    foreach ($documentosDef as $i => [$tipoClave, $titulo, $descripcion, $etiquetas, $estadoDoc, $entidadTipo, $ref, $relacion, $cuerpo]) {
        $bytes = d_pdf_minimo($titulo, $cuerpo);
        $nombreArchivo = 'doc_' . date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.pdf';
        $rutaRelativa = $dirRelativo . '/' . $nombreArchivo;
        $rutaAbsoluta = $dirAbsoluto . DIRECTORY_SEPARATOR . $nombreArchivo;

        if (file_put_contents($rutaAbsoluta, $bytes) === false) {
            throw new RuntimeException('No se pudo escribir el PDF de demostracion: ' . $rutaAbsoluta);
        }
        d_ceder_al_servidor($rutaAbsoluta);
        $archivosCreados[] = $rutaRelativa; // se retira solo si la transaccion se cae

        $insDoc->execute([
            $hotelId, $tipoDocId[$tipoClave] ?? null,
            preg_replace('/[^A-Za-z0-9._-]+/', '_', $titulo) . '.pdf',
            $nombreArchivo, $rutaRelativa,
            strlen($bytes), hash('sha256', $bytes),
            $titulo, $descripcion, $etiquetas, $estadoDoc, $usuarioId,
            $dia(-6 + $i) . ' 11:0' . $i . ':00',
        ]);
        $docId = (int) $pdo->lastInsertId();
        $bytesDocumentos += strlen($bytes);

        // Vinculo a la ficha: el valor del bloque es que el papel viva PEGADO
        // al registro, no suelto en una carpeta.
        $entidadId = $ref[0] === 'huesped' ? $hue[$ref[1]] : $reservas[$ref[1]]['id'];
        $insDocEnt->execute([$hotelId, $docId, $entidadTipo, $entidadId, $relacion]);
    }
    d_paso(sprintf(
        'Centro documental: %d PDF reales en disco (%s), 2 activos + 1 archivado, todos vinculados.',
        count($documentosDef), number_format($bytesDocumentos) . ' bytes'
    ));

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // El disco no entra en la transaccion: los PDF que alcanzamos a escribir
    // se retiran a mano o quedarian huerfanos sin fila que los nombre.
    $limpios = d_borrar_archivos($archivosCreados);
    d_error($e->getMessage());
    echo "Rollback completo: la base quedo exactamente como estaba"
        . ($limpios > 0 ? " ({$limpios} archivo(s) nuevo(s) retirado(s) del disco)." : ".") . "\n";
    exit(1);
}

// Ya con el commit firme, se retiran del disco los PDF de la demo anterior.
if (!empty($archivosViejos)) {
    d_paso('Archivos de la demo anterior retirados del disco: ' . d_borrar_archivos($archivosViejos));
}

// El cache APCu de modulos por hotel se invalida al contratar, pero el de
// configuracion puede quedar tibio en procesos largos.
if (function_exists('hotel_config_cache_invalidar')) {
    hotel_config_cache_invalidar($hotelId);
}

/* ---------------------------------------------------------------------- */
/* 8) Verificacion post-siembra (lo que quedo, contado en la BD)           */
/* ---------------------------------------------------------------------- */

d_titulo('Lo que quedo sembrado');

$conteos = [
    'habitaciones'          => "SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ?",
    '  ocupadas'            => "SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND estado = 'ocupada'",
    '  en limpieza'         => "SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND estado = 'limpieza'",
    '  en mantenimiento'    => "SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND estado = 'mantenimiento'",
    '  disponibles'         => "SELECT COUNT(*) FROM habitaciones WHERE hotel_id = ? AND estado = 'disponible'",
    'huespedes'             => "SELECT COUNT(*) FROM huespedes WHERE hotel_id = ?",
    'reservaciones'         => "SELECT COUNT(*) FROM reservaciones WHERE hotel_id = ?",
    '  checked_in'          => "SELECT COUNT(*) FROM reservaciones WHERE hotel_id = ? AND estado = 'checked_in'",
    '  checked_out'         => "SELECT COUNT(*) FROM reservaciones WHERE hotel_id = ? AND estado = 'checked_out'",
    '  llegan hoy'          => "SELECT COUNT(*) FROM reservaciones WHERE hotel_id = ? AND estado = 'confirmada' AND fecha_entrada = CURDATE()",
    'pagos registrados'     => "SELECT COUNT(*) FROM reservacion_pagos WHERE hotel_id = ?",
    'movimientos del turno' => "SELECT COUNT(*) FROM movimientos_caja WHERE hotel_id = ?",
    'productos inventario'  => "SELECT COUNT(*) FROM inventario_productos WHERE hotel_id = ?",
    '  bajo minimo'         => "SELECT COUNT(*) FROM inventario_productos WHERE hotel_id = ? AND stock_actual < stock_minimo",
    'solicitudes factura'   => "SELECT COUNT(*) FROM solicitudes_factura WHERE hotel_id = ?",
    'encuestas respondidas' => "SELECT COUNT(*) FROM reputacion_encuestas WHERE hotel_id = ? AND estado = 'respondida'",
    'reglas de tarifa'      => "SELECT COUNT(*) FROM incrementos_tarifas WHERE hotel_id = ? AND activo = 1",
    '  reservas con alza'   => "SELECT COUNT(*) FROM reservaciones r WHERE r.hotel_id = ? AND EXISTS (SELECT 1 FROM reservacion_habitaciones rh JOIN habitaciones h ON h.id = rh.habitacion_id WHERE rh.reservacion_id = r.id AND rh.precio > h.precio_base * DATEDIFF(r.fecha_salida, r.fecha_entrada))",
    'huespedes con descuento' => "SELECT COUNT(*) FROM huespedes WHERE hotel_id = ? AND descuento_tipo IS NOT NULL",
    '  reservas con desc.'  => "SELECT COUNT(*) FROM reservaciones WHERE hotel_id = ? AND descuento_total > 0",
    'documentos'            => "SELECT COUNT(*) FROM documentos WHERE hotel_id = ?",
    '  archivados'          => "SELECT COUNT(*) FROM documentos WHERE hotel_id = ? AND estado = 'archivado'",
    '  vinculos a fichas'   => "SELECT COUNT(*) FROM documento_entidades WHERE hotel_id = ?",
    'roles del hotel'       => "SELECT COUNT(*) FROM roles WHERE hotel_id = ?",
    'bloques activos'       => "SELECT COUNT(*) FROM hotel_modulos WHERE hotel_id = ? AND activo = 1",
];
foreach ($conteos as $etiqueta => $sql) {
    $st = $pdo->prepare($sql);
    $st->execute([$hotelId]);
    echo '  ' . str_pad($etiqueta, 26) . (int) $st->fetchColumn() . "\n";
}

$st = $pdo->prepare(
    "SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) AS ing,
            COALESCE(SUM(CASE WHEN tipo = 'gasto'   THEN monto ELSE 0 END), 0) AS gas
       FROM movimientos_caja WHERE hotel_id = ?"
);
$st->execute([$hotelId]);
$caja = $st->fetch(PDO::FETCH_ASSOC);
echo '  ' . str_pad('turno: ingresos', 26) . '$' . number_format((float) $caja['ing'], 2) . "\n";
echo '  ' . str_pad('turno: gastos', 26) . '$' . number_format((float) $caja['gas'], 2) . "\n";

$st = $pdo->prepare(
    "SELECT m.clave FROM hotel_modulos hm
       JOIN modulos m ON m.id = hm.modulo_id
      WHERE hm.hotel_id = ? AND hm.activo = 1 AND m.tipo_comercial = 'opcional'
      ORDER BY m.clave"
);
$st->execute([$hotelId]);
echo '  ' . str_pad('bloques contratados', 26) . implode(', ', $st->fetchAll(PDO::FETCH_COLUMN)) . "\n";

// Espacio del Centro documental: es el indicador que se ensena en el guion §3.
$st = $pdo->prepare("SELECT COALESCE(SUM(size_bytes), 0) FROM documentos WHERE hotel_id = ? AND estado <> 'eliminado'");
$st->execute([$hotelId]);
$usado = (int) $st->fetchColumn();
echo '  ' . str_pad('espacio documental', 26) . number_format($usado) . ' bytes de 2 GB ('
    . rtrim(rtrim(number_format(($usado / 2147483648) * 100, 6), '0'), '.') . "%)\n";

// El invariante de dinero, comprobado en la BD y no de memoria.
$st = $pdo->prepare(
    "SELECT COUNT(*) FROM reservaciones r
      WHERE r.hotel_id = ?
        AND ROUND(r.precio_total, 2) <> ROUND(
              (SELECT COALESCE(SUM(rh.precio), 0) FROM reservacion_habitaciones rh WHERE rh.reservacion_id = r.id)
              - r.descuento_total, 2)"
);
$st->execute([$hotelId]);
echo '  ' . str_pad('descuadres de precio', 26) . (int) $st->fetchColumn() . " (debe ser 0)\n";

/* ---------------------------------------------------------------------- */
/* 9) Credenciales                                                         */
/* ---------------------------------------------------------------------- */

d_titulo('Acceso a la cuenta de demostracion');
echo "  Hotel     : " . DEMO_NOMBRE . " (id {$hotelId}, slug " . DEMO_SLUG . ")\n";
echo "  Usuario   : " . DEMO_USUARIO . "\n";
echo "  Contrasena: {$password}\n";
echo "  Rol       : gerente del hotel demo\n";
echo "  Entrar en : http://localhost:8080/login\n";
echo "\n  Para borrarla por completo (solo esta cuenta):\n";
echo "    php tools/saas/sembrar_hotel_demo.php --limpiar\n";
exit(0);
