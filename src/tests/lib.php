<?php
/**
 * Mini-framework de aserciones (cero dependencias).
 * Convencion: cada *Test.php llama t_ok/t_eq/t_throws y termina con t_fin().
 */

$GLOBALS['__t'] = ['pass' => 0, 'fail' => 0, 'fallas' => []];

function t_ok($condicion, string $nombre): void
{
    if ($condicion) {
        $GLOBALS['__t']['pass']++;
        echo "  PASS  $nombre\n";
    } else {
        $GLOBALS['__t']['fail']++;
        $GLOBALS['__t']['fallas'][] = $nombre;
        echo "  FAIL  $nombre\n";
    }
}

function t_eq($esperado, $obtenido, string $nombre): void
{
    if ($esperado == $obtenido) {
        t_ok(true, $nombre);
    } else {
        t_ok(false, $nombre . ' (esperado: ' . var_export($esperado, true)
            . ', obtenido: ' . var_export($obtenido, true) . ')');
    }
}

/** Asegura que $fn lance una excepcion cuyo mensaje contenga $fragmento. */
function t_throws(callable $fn, string $fragmento, string $nombre): void
{
    try {
        $fn();
        t_ok(false, $nombre . ' (no lanzo excepcion)');
    } catch (Throwable $e) {
        $contiene = $fragmento === '' || stripos($e->getMessage(), $fragmento) !== false;
        t_ok($contiene, $nombre . ($contiene ? '' : ' (mensaje: ' . $e->getMessage() . ')'));
    }
}

function t_fin(): void
{
    $t = $GLOBALS['__t'];
    echo "  -- {$t['pass']} pass, {$t['fail']} fail --\n";
    exit($t['fail'] > 0 ? 1 : 0);
}
