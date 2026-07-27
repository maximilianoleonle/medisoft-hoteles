<?php
/**
 * DocumentoTest — Centro Documental (modulo `documentos`).
 *
 * Cubre las tres familias que la auditoria fable jul-2026 dejo SIN pruebas:
 *  - AISLAMIENTO por hotel: ningun metodo del modelo devuelve/muta documentos
 *    de otro hotel (lectura, descarga, listado, resumen, vinculos, estado,
 *    metadata).
 *  - SEGURIDAD de archivos: blocklist de extensiones peligrosas, allowlist de
 *    MIME, limite de tamano, saneo del nombre original y confinamiento
 *    anti-path-traversal de la ruta de descarga.
 *  - PERMISOS: la matriz de presets (subir exige documentos.all, no basta
 *    documentos.view) y que los gates de escritura del controller siguen en su
 *    lugar; ademas el gate que oculta la metadata documental en las 7 areas
 *    host cuando el modulo no esta contratado o falta permiso.
 *
 * No instancia el HTTP: los gates del controller se caracterizan a nivel de
 * fuente + matriz de presets (la resolucion real de can() ya la cubre
 * LegacyRbacCompatTest). Todo determinista, sin artefactos en disco.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "DocumentoTest\n";

t_reset_db();

$base = t_seed_base('doc-hotel-a');
$hotelA = (int) $base['hotel_id'];
$usuarioA = (int) $base['usuario_id'];

$db = Database::getInstance();
$db->query("INSERT INTO hoteles (nombre, slug, activo, created_at) VALUES ('Hotel Doc B', 'doc-hotel-b', 1, NOW())");
$hotelB = (int) $db->lastInsertId();

$model = new Documento();

/** Inserta un documento (y su vinculo opcional) via PDO, sin pasar por el upload real. */
function doc_insert(int $hotelId, string $titulo, string $estado = 'activo', ?string $entTipo = null, ?int $entId = null, ?int $usuarioId = null): int
{
    $db = Database::getInstance();
    $db->query(
        "INSERT INTO documentos
            (hotel_id, nombre_original, nombre_archivo, storage_path, mime_type,
             size_bytes, titulo, estado, subido_por_usuario_id, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'application/pdf', 1234, ?, ?, ?, NOW(), NOW())",
        [
            $hotelId,
            $titulo . '.pdf',
            'doc_' . bin2hex(random_bytes(4)) . '.pdf',
            'documentos/hotel_' . $hotelId . '/2026/07/doc_' . bin2hex(random_bytes(4)) . '.pdf',
            $titulo,
            $estado,
            $usuarioId,
        ]
    );
    $id = (int) $db->lastInsertId();

    if ($entTipo !== null && $entId !== null && $entId > 0) {
        $db->query(
            "INSERT INTO documento_entidades
                (hotel_id, documento_id, entidad_tipo, entidad_id, relacion, created_at, updated_at)
             VALUES (?, ?, ?, ?, 'general', NOW(), NOW())",
            [$hotelId, $id, $entTipo, $entId]
        );
    }

    return $id;
}

/* ── 1. Aislamiento por hotel: lectura / descarga / listado / resumen ────── */

$docA = doc_insert($hotelA, 'Contrato Hotel A', 'activo', null, null, $usuarioA);
$docB = doc_insert($hotelB, 'Contrato Hotel B', 'activo');

t_ok($model->buscarPorIdHotel($docA, $hotelA) !== null, 'A ve su propio documento (buscarPorIdHotel)');
t_ok($model->buscarPorIdHotel($docA, $hotelB) === null, 'AISLAMIENTO: doc de A invisible desde B (buscarPorIdHotel)');
t_ok($model->buscarDescargablePorIdHotel($docA, $hotelA) !== null, 'A puede resolver su doc para descarga');
t_ok($model->buscarDescargablePorIdHotel($docA, $hotelB) === null, 'AISLAMIENTO: doc de A NO descargable desde B');

$idsA = array_map('intval', array_column($model->listarPorHotel($hotelA), 'id'));
t_ok(in_array($docA, $idsA, true), 'A: el listado incluye el doc propio');
t_ok(!in_array($docB, $idsA, true), 'AISLAMIENTO: el listado de A NO incluye el doc de B');

t_eq(1, (int) $model->resumenPorHotel($hotelA)['total'], 'A: el resumen cuenta solo documentos propios');
t_ok($model->entidadesPorDocumento($docB, $hotelA) === [], 'AISLAMIENTO: entidades del doc de B invisibles desde A');

/* ── 2. Aislamiento de vinculos (entidad) + whitelist de tipo ────────────── */

$db->query("INSERT INTO huespedes (hotel_id, nombre_completo, created_at) VALUES (?, 'Huesped A', NOW())", [$hotelA]);
$huespedA = (int) $db->lastInsertId();

t_ok($model->entidadExisteEnHotel($hotelA, 'huesped', $huespedA) === true, 'la entidad existe en su hotel');
t_ok($model->entidadExisteEnHotel($hotelB, 'huesped', $huespedA) === false, 'AISLAMIENTO: la entidad de A no existe para B');
t_ok($model->entidadExisteEnHotel($hotelA, 'huesped', 999999) === false, 'entidad inexistente => false');

$docHu = doc_insert($hotelA, 'INE Huesped', 'activo', 'huesped', $huespedA, $usuarioA);
$porEnt = $model->documentosPorEntidad($hotelA, 'huesped', $huespedA);
t_ok(count($porEnt) === 1 && (int) $porEnt[0]['id'] === $docHu, 'documentosPorEntidad devuelve el vinculado');
t_ok($model->documentosPorEntidad($hotelB, 'huesped', $huespedA) === [], 'AISLAMIENTO: documentosPorEntidad de A vacio desde B');

t_ok($model->normalizarEntidadTipo('huesped') === 'huesped', 'normaliza un tipo de entidad valido');
t_ok($model->normalizarEntidadTipo('hackers') === null, 'rechaza un tipo de entidad fuera de la whitelist');
t_ok($model->normalizarEntidadTipo("huesped'; DROP TABLE") === null, 'rechaza inyeccion en el tipo de entidad');

/* ── 3. Descarga: confinamiento anti path-traversal (resolverRutaPrivada) ── */

t_throws(fn() => $model->resolverRutaPrivada(['storage_path' => 'documentos/hotel_1/../../../../etc/passwd']), 'valida', 'TRAVERSAL: segmento .. rechazado');
t_throws(fn() => $model->resolverRutaPrivada(['storage_path' => '..\\..\\windows\\system32\\config']), 'valida', 'TRAVERSAL: .. con backslash rechazado');
t_throws(fn() => $model->resolverRutaPrivada(['storage_path' => '']), 'disponible', 'ruta de storage vacia rechazada');

/* ── 4. Seguridad de archivos: blocklist / allowlist / limite / saneo ────── */

$refDoc = new ReflectionClass('Documento');
$peligrosas = $refDoc->getConstant('EXTENSIONES_PELIGROSAS');
foreach (['php', 'phtml', 'phar', 'js', 'html', 'htm', 'svg', 'exe', 'bat', 'cmd', 'sh', 'ps1'] as $ext) {
    t_ok(is_array($peligrosas) && in_array($ext, $peligrosas, true), "extension peligrosa en la blocklist: .$ext");
}
$mimes = $refDoc->getConstant('MIME_PERMITIDOS');
t_eq(['application/pdf', 'image/jpeg', 'image/png', 'image/webp'], array_keys((array) $mimes), 'MIME permitidos = solo pdf/jpeg/png/webp');
t_eq(10485760, $refDoc->getConstant('MAX_UPLOAD_BYTES'), 'limite de subida = 10 MB');

$mLimpiar = new ReflectionMethod('Documento', 'limpiarNombreOriginal');
$mLimpiar->setAccessible(true);
$n1 = (string) $mLimpiar->invoke($model, '../../etc/passwd');
t_ok(strpos($n1, '/') === false && strpos($n1, '..') === false, 'nombre original: basename elimina rutas y ..');
$n2 = (string) $mLimpiar->invoke($model, str_repeat('a', 400) . '.pdf');
t_ok(mb_strlen($n2) <= 255, 'nombre original truncado a 255');
$n3 = (string) $mLimpiar->invoke($model, "archivo\x00.pdf");
t_ok(strpos($n3, "\x00") === false, 'nombre original: se eliminan bytes de control/NUL');

/* ── 5. Transiciones de estado + alcance de metadata ─────────────────────── */

$docS = doc_insert($hotelA, 'Estado doc', 'activo', null, null, $usuarioA);
t_eq('archivado', $model->actualizarEstado($docS, $hotelA, 'archivado', $usuarioA)['estado_despues'], 'transicion activo -> archivado');
t_eq('activo', $model->actualizarEstado($docS, $hotelA, 'activo', $usuarioA)['estado_despues'], 'transicion archivado -> activo');
t_throws(fn() => $model->actualizarEstado($docS, $hotelB, 'archivado', $usuarioA), '', 'AISLAMIENTO: no se cambia el estado de un doc de otro hotel');
$model->actualizarEstado($docS, $hotelA, 'eliminado', $usuarioA);
t_throws(fn() => $model->actualizarEstado($docS, $hotelA, 'activo', $usuarioA), '', "'eliminado' es terminal (no se restaura desde la app)");

$docM = doc_insert($hotelA, 'Meta doc', 'activo', null, null, $usuarioA);
t_ok($model->actualizarMetadata($docM, $hotelA, ['titulo' => str_repeat('T', 300), 'descripcion' => 'ok', 'etiquetas' => 'a,b'], $usuarioA)['changed'] === true, 'metadata actualizada');
t_eq(180, mb_strlen((string) $model->buscarPorIdHotel($docM, $hotelA)['titulo']), 'titulo truncado a 180');
t_throws(fn() => $model->actualizarMetadata($docM, $hotelB, ['titulo' => 'x'], $usuarioA), 'no encontrado', 'AISLAMIENTO: no se edita metadata de un doc de otro hotel');

/* ── 6. Permisos: matriz de presets (subir exige documentos.all) ─────────── */

$perm = require CONFIG_PATH . '/permisos.php';
$presets = $perm['presets'] ?? [];
$catDoc = $perm['catalogo']['documentos']['permisos'] ?? [];
t_ok(isset($catDoc['documentos.view'], $catDoc['documentos.all']), 'el catalogo declara documentos.view y documentos.all');

$recep = $presets['recepcionista']['permisos'] ?? [];
t_ok(in_array('documentos.view', $recep, true), 'recepcionista tiene documentos.view (lectura)');
t_ok(!in_array('documentos.all', $recep, true), 'PERMISO: recepcionista NO tiene documentos.all => no puede subir');
t_ok(in_array('documentos.all', $presets['gerente']['permisos'] ?? [], true), 'gerente tiene documentos.all');
t_ok(in_array('documentos.all', $presets['administrador']['permisos'] ?? [], true), 'administrador tiene documentos.all');
$dueno = $presets['dueno_remoto']['permisos'] ?? [];
t_ok(!in_array('documentos.view', $dueno, true) && !in_array('documentos.all', $dueno, true), 'dueno_remoto sin acceso documental');

/* ── 7. Gates en el codigo: subida exige .all + partial oculta metadata ──── */

$gateEnMetodo = static function (string $src, string $metodo, string $perm): bool {
    $pos = strpos($src, 'function ' . $metodo . '(');
    if ($pos === false) {
        return false;
    }
    return strpos(substr($src, $pos, 700), "require_permission_or_403('" . $perm . "')") !== false;
};
$ctrl = (string) file_get_contents(APP_PATH . '/controllers/DocumentoController.php');
t_ok($gateEnMetodo($ctrl, 'guardarAction', 'documentos.all'), 'GATE: guardarAction (subir POST) exige documentos.all');
t_ok($gateEnMetodo($ctrl, 'subirAction', 'documentos.all'), 'GATE: subirAction (form GET) exige documentos.all');
t_ok($gateEnMetodo($ctrl, 'actualizarAction', 'documentos.all'), 'GATE: actualizarAction exige documentos.all');

$partial = (string) file_get_contents(APP_PATH . '/views/partials/documentos_entidad.php');
t_ok(strpos($partial, 'documentos_entidad_visible') !== false, 'GATE: el partial host oculta la metadata via documentos_entidad_visible');

/* ── 8. Helper de visibilidad host (punto 1) ─────────────────────────────── */

t_ok(function_exists('documentos_entidad_visible'), 'existe el helper documentos_entidad_visible()');
t_ok(documentos_entidad_visible() === false, 'sin el modulo documentos contratado => bloque host OCULTO (false)');

/* ── 9. Cuota de almacenamiento por hotel (2 GB) ─────────────────────────── */
// Antes de jul-26 solo existia el tope de 10 MB por ARCHIVO: nada impedia que
// un hotel llenara el disco del servidor subiendo miles. La cuota se vende
// como "2 GB incluidos", asi que el numero es un compromiso comercial.

t_eq(2147483648, $refDoc->getConstant('CUOTA_HOTEL_BYTES'), 'cuota por hotel = 2 GB');

// El contador ignora las bajas logicas (valvula del hotelero para liberar) y
// SI cuenta lo archivado (archivar no borra el archivo del disco).
$hotelCuota = $base['hotel_id'];
$insertarDoc = function (string $estado, int $bytes) use ($db, $hotelCuota): void {
    $db->query(
        "INSERT INTO documentos
            (hotel_id, nombre_original, nombre_archivo, storage_path, mime_type,
             size_bytes, estado, created_at)
         VALUES (?, 'x.pdf', 'x.pdf', 'docs/x.pdf', 'application/pdf', ?, ?, NOW())",
        [$hotelCuota, $bytes, $estado]
    );
};

// Este hotel ya trae documentos de las secciones anteriores del caso, asi que
// se mide el INCREMENTO, no un absoluto: un assert contra 0 se rompe en cuanto
// alguien agrega un fixture mas arriba.
$usadoAntes = $model->espacioUsado($hotelCuota);

$insertarDoc('activo', 1048576);      // 1 MB
$insertarDoc('archivado', 2097152);   // 2 MB — archivar NO libera disco
$insertarDoc('eliminado', 5242880);   // 5 MB — baja logica: no se cobra

$usadoDespues = $model->espacioUsado($hotelCuota);
t_eq(3145728, $usadoDespues - $usadoAntes, 'suma activos + archivados, ignora eliminados');

$resumen = $model->resumenAlmacenamiento($hotelCuota);
t_eq(2147483648, $resumen['cuota_bytes'], 'el resumen reporta la cuota completa');
t_eq(2147483648 - $usadoDespues, $resumen['disponible_bytes'], 'el resumen calcula el disponible');
t_ok($resumen['cerca_del_limite'] === false, 'con 3 MB usados no esta cerca del limite');
t_ok($resumen['lleno'] === false, 'con 3 MB usados no esta lleno');
t_ok(strpos($resumen['cuota_legible'], 'GB') !== false, 'la cuota se lee en GB, no en 2048 MB');

// Hotel al tope: el aviso del 80% y el bloqueo deben encenderse.
$baseTope = t_seed_base('doc-tope');
$hotelTope = $baseTope['hotel_id'];
$db->query(
    "INSERT INTO documentos
        (hotel_id, nombre_original, nombre_archivo, storage_path, mime_type,
         size_bytes, estado, created_at)
     VALUES (?, 'lleno.pdf', 'lleno.pdf', 'docs/lleno.pdf', 'application/pdf', ?, 'activo', NOW())",
    [$hotelTope, 2147483648]
);
$resumenTope = $model->resumenAlmacenamiento($hotelTope);
t_ok($resumenTope['lleno'] === true, 'hotel en la cuota exacta se reporta lleno');
t_ok($resumenTope['cerca_del_limite'] === true, 'hotel lleno tambien esta cerca del limite');
t_eq(0, $resumenTope['disponible_bytes'], 'hotel lleno no tiene bytes disponibles');

// El candado real: validarCuotaHotel corta la subida antes de escribir nada.
$mCuota = new ReflectionMethod('Documento', 'validarCuotaHotel');
$mCuota->setAccessible(true);
t_throws(
    fn() => $mCuota->invoke($model, $hotelTope, 1024),
    'espacio',
    'subir con la cuota agotada se rechaza con mensaje de espacio'
);
$mCuota->invoke($model, $hotelCuota, 1024);
t_ok(true, 'subir dentro de la cuota no lanza');

// El aislamiento por hotel importa: la cuota de uno no consume la del otro.
t_eq($usadoDespues, $model->espacioUsado($hotelCuota), 'el hotel lleno no altero el consumo del otro hotel');

t_fin();
