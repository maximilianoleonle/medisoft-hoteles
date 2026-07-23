<?php
/**
 * Catalogo de paises (helper puro, sin base): integridad del catalogo, bandera
 * derivada del ISO2, resolucion de valores guardados (nombre y gentilicio legacy)
 * y el <select> que consume ms-combo.
 */

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../../app/helpers/paises.php';

echo "PaisesCatalogoTest\n";

$catalogo = ms_paises();

// ── Integridad del catalogo ──
t_ok(count($catalogo) > 180, 'el catalogo trae los paises del mundo (>180)');
$isoMalos = [];
$camposFaltantes = [];
$nombres = [];
foreach ($catalogo as $iso => $pais) {
    if (!preg_match('/^[A-Z]{2}$/', (string) $iso)) {
        $isoMalos[] = $iso;
    }
    if (trim((string) ($pais['nombre'] ?? '')) === '' || trim((string) ($pais['gentilicio'] ?? '')) === '') {
        $camposFaltantes[] = $iso;
    }
    $nombres[] = $pais['nombre'] ?? '';
}
t_eq(0, count($isoMalos), 'todos los codigos son ISO 3166-1 alfa-2 en mayusculas');
t_eq(0, count($camposFaltantes), 'todos los paises traen nombre y gentilicio');
t_eq(count($nombres), count(array_unique($nombres)), 'no hay nombres de pais duplicados');

// ── Los frecuentes existen en el catalogo (si no, el grupo de arriba saldria vacio) ──
foreach (ms_paises_frecuentes() as $iso) {
    t_ok(isset($catalogo[$iso]), "el pais frecuente {$iso} existe en el catalogo");
}

// ── Bandera derivada del ISO2 (indicadores regionales U+1F1E6..U+1F1FF) ──
t_eq("\u{1F1F2}\u{1F1FD}", ms_pais_bandera('MX'), 'MX -> bandera de Mexico');
t_eq("\u{1F1FA}\u{1F1F8}", ms_pais_bandera('us'), 'us (minusculas) -> bandera de Estados Unidos');
t_eq('', ms_pais_bandera('XYZ'), 'codigo invalido no devuelve bandera');
t_eq('', ms_pais_bandera(''), 'codigo vacio no devuelve bandera');

// ── Resolver valores guardados ──
t_eq('US', ms_pais_resolver('Estados Unidos')['iso'] ?? null, 'resuelve por nombre exacto');
t_eq('US', ms_pais_resolver('estados unidos')['iso'] ?? null, 'resuelve sin importar mayusculas');
t_eq('US', ms_pais_resolver('Estadounidense')['iso'] ?? null, 'resuelve un gentilicio legacy');
t_eq('ES', ms_pais_resolver('Espana')['iso'] ?? null, 'resuelve sin acentos ("Espana" -> Espana)');
t_eq('MX', ms_pais_resolver('MX')['iso'] ?? null, 'resuelve por codigo ISO');
t_eq('Estados Unidos', ms_pais_resolver('estadounidense')['nombre'] ?? null, 'devuelve el nombre canonico');
t_eq(null, ms_pais_resolver('Wakanda'), 'un valor desconocido no se inventa');
t_eq(null, ms_pais_resolver(''), 'vacio no resuelve');
t_eq(null, ms_pais_resolver(null), 'null no resuelve');

// ── Normalizacion (base comun de busqueda) ──
t_eq('mexico', ms_pais_normalizar('  MÉXICO  '), 'normaliza acentos, mayusculas y espacios');
t_eq('reino unido', ms_pais_normalizar("Reino\t Unido"), 'colapsa espacios repetidos');

// ── El <select> que consume ms-combo ──
$html = ms_select_paises(['name' => 'extras[nacionalidad]', 'value' => 'Estados Unidos', 'class' => 'gc-control']);
t_ok(strpos($html, 'name="extras[nacionalidad]"') !== false, 'el select conserva el name recibido');
t_ok(strpos($html, 'data-ms-combo') !== false, 'el select se marca para ms-combo (buscador)');
t_ok(strpos($html, 'data-ms-combo-free') !== false, 'permite escribir un pais fuera del catalogo');
t_ok(strpos($html, 'data-iso="US"') !== false, 'cada opcion viaja con su ISO (bandera en el navegador)');
t_ok(strpos($html, 'data-hint="Estadounidense"') !== false, 'el gentilicio viaja como texto secundario y buscable');
t_eq(1, substr_count($html, 'value="Estados Unidos" data-iso="US" data-hint="Estadounidense" data-search="united states usa eeuu america gringo" selected'), 'el valor guardado queda seleccionado UNA sola vez (no en frecuentes y en todos)');
t_ok(strpos($html, 'required') === false, 'sin required por defecto');

$htmlReq = ms_select_paises(['name' => 'p', 'required' => true]);
t_ok(strpos($htmlReq, 'required') !== false, 'required se propaga al select');

// Valor legacy fuera del catalogo: no se pierde, se conserva como opcion propia.
$htmlLibre = ms_select_paises(['name' => 'p', 'value' => 'Wakandiana']);
t_ok(strpos($htmlLibre, '<option value="Wakandiana" data-libre="1"') !== false, 'un valor desconocido se conserva como opcion escrita a mano');
t_ok(substr_count($htmlLibre, 'selected') === 1, 'y es la unica opcion seleccionada');

// Un gentilicio legacy se muestra ya como pais del catalogo (no duplica opcion).
$htmlLegacy = ms_select_paises(['name' => 'p', 'value' => 'Canadiense']);
t_ok(strpos($htmlLegacy, 'data-libre="1"') === false, 'un gentilicio conocido NO genera opcion suelta');
t_eq(1, substr_count($htmlLegacy, 'selected'), 'el gentilicio legacy selecciona su pais una sola vez');

// Escape: el valor libre no puede inyectar HTML.
$htmlXss = ms_select_paises(['name' => 'p', 'value' => '"><script>alert(1)</script>']);
t_ok(strpos($htmlXss, '<script>') === false, 'el valor guardado se escapa (sin inyeccion)');

t_fin();
