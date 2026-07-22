<?php
/**
 * El Guardian (vigilancia financiera): vista producto para el dueno.
 *  - Semaforo del dia: "Hoy: todo en orden" (el dia verde ES el producto) o
 *    "N patrones a revisar".
 *  - Pestanas: Hoy (tarjetas de hallazgo + informe del analista) · Por persona
 *    (radiografia neutral con el promedio del hotel al lado) · Historico
 *    (hallazgos con estado nuevo/revisado/resuelto).
 *  - Lenguaje NO acusatorio en toda la superficie.
 * Tema: tokens --vgf-* (Cupertino los remapea en claro/oscuro en cupertino.css).
 */
$resultado = $resultado ?? ['success' => false, 'message' => 'Sin datos.'];
$totales = $totales ?? ['error' => 0, 'warning' => 0, 'hallazgos' => 0];
$patrones = $patrones ?? null;
$estadosPorClave = $estadosPorClave ?? [];
$historico = $historico ?? [];
$conteosEstado = $conteosEstado ?? ['nuevo' => 0, 'revisado' => 0, 'resuelto' => 0];
$configurado = $configurado ?? false;

$vgfSafe = static function ($v) {
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
};

/**
 * Render minimo de Markdown a HTML seguro (mismo enfoque que el asesor
 * inteligente): escapa todo primero y luego aplica solo negritas,
 * encabezados y listas. Sin HTML libre del modelo.
 */
$vgfMarkdown = static function ($texto) {
    $lineas = explode("\n", (string) $texto);
    $html = '';
    $enLista = false;

    foreach ($lineas as $linea) {
        $linea = htmlspecialchars(rtrim($linea), ENT_QUOTES, 'UTF-8');
        $linea = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $linea);

        if (preg_match('/^#{1,4}\s*(.+)$/', $linea, $m)) {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
            $html .= '<h3>' . $m[1] . '</h3>';
        } elseif (preg_match('/^\s*[-*]\s+(.+)$/', $linea, $m)) {
            if (!$enLista) { $html .= '<ul>'; $enLista = true; }
            $html .= '<li>' . $m[1] . '</li>';
        } elseif (preg_match('/^\s*\d+\.\s+(.+)$/', $linea, $m)) {
            if (!$enLista) { $html .= '<ul>'; $enLista = true; }
            $html .= '<li>' . $m[1] . '</li>';
        } elseif (preg_match('/^\s*(---+|___+)\s*$/', $linea)) {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
            $html .= '<hr>';
        } elseif (trim($linea) === '') {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
        } else {
            if ($enLista) { $html .= '</ul>'; $enLista = false; }
            $html .= '<p>' . $linea . '</p>';
        }
    }

    if ($enLista) {
        $html .= '</ul>';
    }

    return $html;
};

$hayInforme = !empty($resultado['success']);
$informe = (string) ($resultado['informe'] ?? '');
$esPlantilla = ($resultado['modelo'] ?? '') === 'plantilla';

// ── Semaforo del dia: se calcula con los datos FRESCOS (integridad +
// patrones), no con el texto del informe cacheado. ──
$pTotales = (array) ($patrones['totales'] ?? ['alta' => 0, 'media' => 0, 'hallazgos' => 0]);
$patronesAltos = (int) ($pTotales['alta'] ?? 0);
$patronesMedios = (int) ($pTotales['media'] ?? 0);
$patronesHallazgos = (int) ($pTotales['hallazgos'] ?? 0);
$errores = (int) ($totales['error'] ?? 0);
$warnings = (int) ($totales['warning'] ?? 0);

if ($errores > 0 || $patronesAltos > 0) {
    $semaforo = 'rojo';
} elseif ($warnings > 0 || $patronesMedios > 0) {
    $semaforo = 'amarillo';
} else {
    $semaforo = 'verde';
}

$semaforoTexto = ['verde' => 'Todo en orden', 'amarillo' => 'Revisar', 'rojo' => 'Atender hoy'][$semaforo];
$semaforoIcono = ['verde' => 'fa-circle-check', 'amarillo' => 'fa-triangle-exclamation', 'rojo' => 'fa-circle-exclamation'][$semaforo];

$reglasVigiladas = count((array) ($patrones['alertas'] ?? []));
$verificaciones = 26; // referencia visual; el numero real viene abajo
$volumen = (array) ($patrones['volumen'] ?? []);
$operaciones = (int) ($volumen['movimientos'] ?? 0) + (int) ($volumen['reservaciones'] ?? 0);
$ventanaDias = (int) ($patrones['ventana']['dias'] ?? 30);
$volumenInsuficiente = $patrones !== null && empty($volumen['suficiente']);

// Tarjetas de hallazgo (regla x usuario) ordenadas alta > media.
$tarjetas = [];
foreach ((array) ($patrones['alertas'] ?? []) as $alerta) {
    if ((int) ($alerta['conteo'] ?? 0) === 0) {
        continue;
    }
    foreach ((array) ($alerta['usuarios'] ?? []) as $u) {
        $clave = GuardianHallazgoEstado::clavePara((string) $alerta['codigo'], (int) ($u['usuario_id'] ?? 0) ?: null);
        $tarjetas[] = [
            'codigo' => (string) $alerta['codigo'],
            'titulo' => (string) $alerta['titulo'],
            'detalle' => (string) $alerta['detalle'],
            'destino' => (string) ($alerta['destino'] ?? ''),
            'usuario' => $u,
            'estado_row' => $estadosPorClave[$clave] ?? null,
        ];
    }
}
usort($tarjetas, static function (array $a, array $b): int {
    $peso = ['alta' => 1, 'media' => 2];
    return ($peso[$a['usuario']['severidad'] ?? 'media'] ?? 3) <=> ($peso[$b['usuario']['severidad'] ?? 'media'] ?? 3);
});

$porPersona = (array) ($patrones['por_persona'] ?? []);
$personas = (array) ($porPersona['usuarios'] ?? []);
$promHotel = (array) ($porPersona['promedio_hotel'] ?? []);

$generadoHumano = '';
if (!empty($resultado['generado_en'])) {
    $generadoTs = strtotime((string) $resultado['generado_en']);
    $generadoHumano = $generadoTs ? date('d/m/Y H:i', $generadoTs) : '';
}

$tabInicial = in_array($_GET['tab'] ?? '', ['hoy', 'persona', 'historico'], true) ? $_GET['tab'] : 'hoy';

$estadoChip = static function (?array $row) use ($vgfSafe): string {
    $estado = (string) ($row['estado'] ?? 'nuevo');
    $textos = ['nuevo' => 'Nuevo', 'revisado' => 'Revisado', 'resuelto' => 'Resuelto'];
    return '<span class="vgf-estado is-' . $vgfSafe($estado) . '">' . $vgfSafe($textos[$estado] ?? $estado) . '</span>';
};

$linkCaso = static function (array $caso) use ($vgfSafe): string {
    if (!empty($caso['reservacion_id'])) {
        return '<a class="vgf-caso-link" href="' . url('reservaciones/ver/' . (int) $caso['reservacion_id']) . '">Reservación #' . (int) $caso['reservacion_id'] . '</a>';
    }
    if (!empty($caso['corte_id'])) {
        return '<a class="vgf-caso-link" href="' . url('caja/corte/' . (int) $caso['corte_id']) . '">Corte #' . (int) $caso['corte_id'] . '</a>';
    }
    if (!empty($caso['movimiento_id'])) {
        return '<a class="vgf-caso-link" href="' . url('caja') . '">Mov. #' . (int) $caso['movimiento_id'] . '</a>';
    }
    return '';
};
?>

<style>
@import url('<?= asset('vendor/fonts/marca.css') ?>');

.vgf {
    --vgf-brand: var(--brand-primary, #1B2746);
    --vgf-gold: var(--brand-accent, #BD9441);
    --vgf-gold-soft: color-mix(in srgb, var(--vgf-gold) 15%, #FFFFFF);
    --vgf-gold-line: color-mix(in srgb, var(--vgf-gold) 42%, #E4D4B0);
    --vgf-gold-ink: color-mix(in srgb, var(--vgf-gold) 58%, var(--vgf-brand));
    --vgf-ivory: #F5F5F7;
    --vgf-ivory-2: #FAFAFC;
    --vgf-surface: #FFFFFF;
    --vgf-surface-warm: #FAFAFC;
    --vgf-border: color-mix(in srgb, var(--vgf-brand) 6%, #E9E1D6);
    --vgf-text: color-mix(in srgb, var(--vgf-brand) 46%, #707B8C);
    --vgf-muted: #8791A2;
    --vgf-heading: color-mix(in srgb, var(--vgf-brand) 66%, #566172);
    --vgf-serif: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --vgf-sans: 'Manrope', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
    --vgf-success: #1E9E63;
    --vgf-success-bg: #E7F4EC;
    --vgf-success-line: #BEE3CD;
    --vgf-warning: #C2841C;
    --vgf-warning-bg: #FAF0DC;
    --vgf-warning-line: #EBD8AC;
    --vgf-danger: #B4392B;
    --vgf-danger-bg: #F8EAE5;
    --vgf-danger-line: #EFC9BE;
    --vgf-info: #3E6FA8;
    --vgf-info-bg: #EAF1F8;
    width: 100%;
    min-height: 100%;
    margin: 0;
    padding: 18px 16px 44px;
    color: var(--vgf-text);
    font-family: var(--vgf-sans);
    font-size: .92rem;
    
}
.vgf * { box-sizing: border-box; }
.vgf-shell { display: grid; gap: 14px; width: 100%; max-width: 1040px; min-width: 0; margin: 0 auto; }

/* ── Hero ── */
.vgf-hero-section {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    align-items: center;
    gap: 16px 28px;
    padding: 2px 0 6px;
}
.vgf-title-lockup {
    display: grid;
    grid-template-columns: 48px minmax(0, 1fr);
    align-items: center;
    column-gap: 14px;
    min-width: 0;
    max-width: min(100%, 760px);
}
.vgf-title-lockup > div:last-child { min-width: 0; }
.vgf-hero-icon {
    width: 48px; height: 48px; border-radius: 15px;
    display: grid; place-items: center;
    color: #fff; font-size: 1.15rem;
    background:
        radial-gradient(circle at 30% 24%, rgba(255,255,255,.24), transparent 34%),
        linear-gradient(145deg, var(--vgf-gold), var(--vgf-brand) 54%, color-mix(in srgb, var(--vgf-brand) 68%, var(--vgf-gold)));
    box-shadow: 0 14px 26px -14px color-mix(in srgb, var(--vgf-brand) 72%, transparent);
}
.vgf-kicker {
    margin: 0 0 2px; color: var(--vgf-muted);
    font-size: .72rem; font-weight: 650; letter-spacing: .11em; line-height: 1;
    text-transform: uppercase;
}
.vgf-title {
    margin: 0; color: var(--vgf-heading);
    font-family: var(--vgf-serif);
    font-size: clamp(2.1rem, 4vw, 3rem);
    font-weight: 650; line-height: .98; overflow-wrap: anywhere;
}
.vgf-subtitle { max-width: 48rem; margin: 9px 0 0; color: var(--vgf-muted); font-size: .94rem; font-weight: 500; line-height: 1.5; }
.vgf-status-pill {
    display: inline-flex; align-items: center; gap: 7px;
    min-height: 36px; padding: 0 12px; border-radius: 999px;
    border: 1px solid var(--vgf-gold-line); background: var(--vgf-gold-soft);
    color: var(--vgf-gold-ink); font-size: .78rem; font-weight: 650; white-space: nowrap;
}
.vgf-status-pill.is-verde { border-color: var(--vgf-success-line); background: var(--vgf-success-bg); color: var(--vgf-success); }
.vgf-status-pill.is-amarillo { border-color: var(--vgf-warning-line); background: var(--vgf-warning-bg); color: var(--vgf-warning); }
.vgf-status-pill.is-rojo { border-color: var(--vgf-danger-line); background: var(--vgf-danger-bg); color: var(--vgf-danger); }

.vgf-alert { padding: 12px 14px; border-radius: 12px; border: 1px solid var(--vgf-border); background: var(--vgf-surface); font-size: .88rem; }
.vgf-alert.is-success { border-color: var(--vgf-success-line); background: var(--vgf-success-bg); color: var(--vgf-success); }
.vgf-alert.is-error { border-color: var(--vgf-danger-line); background: var(--vgf-danger-bg); color: var(--vgf-danger); }

/* ── Banda del dia (el momento verde ES el producto) ── */
.vgf-dia {
    display: grid;
    grid-template-columns: 56px minmax(0, 1fr);
    align-items: center;
    column-gap: 16px;
    padding: 20px 18px;
    border-radius: 18px;
    border: 1px solid var(--vgf-border);
    background: var(--vgf-surface);
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 14px 30px -26px rgba(27,39,70,.25);
}
.vgf-dia.is-verde { border-color: var(--vgf-success-line); background: linear-gradient(160deg, var(--vgf-success-bg), var(--vgf-surface) 70%); }
.vgf-dia.is-amarillo { border-color: var(--vgf-warning-line); background: linear-gradient(160deg, var(--vgf-warning-bg), var(--vgf-surface) 70%); }
.vgf-dia.is-rojo { border-color: var(--vgf-danger-line); background: linear-gradient(160deg, var(--vgf-danger-bg), var(--vgf-surface) 70%); }
.vgf-dia-icon {
    width: 56px; height: 56px; border-radius: 999px;
    display: grid; place-items: center; font-size: 1.5rem;
    position: relative;
}
.vgf-dia.is-verde .vgf-dia-icon { color: var(--vgf-success); background: color-mix(in srgb, var(--vgf-success) 14%, transparent); }
.vgf-dia.is-amarillo .vgf-dia-icon { color: var(--vgf-warning); background: color-mix(in srgb, var(--vgf-warning) 14%, transparent); }
.vgf-dia.is-rojo .vgf-dia-icon { color: var(--vgf-danger); background: color-mix(in srgb, var(--vgf-danger) 14%, transparent); }
.vgf-dia.is-verde .vgf-dia-icon::after {
    content: ""; position: absolute; inset: -4px; border-radius: 999px;
    border: 1px solid color-mix(in srgb, var(--vgf-success) 35%, transparent);
    animation: vgf-anillo 2.6s ease-out infinite;
}
@keyframes vgf-anillo {
    0% { transform: scale(.92); opacity: .9; }
    70% { transform: scale(1.18); opacity: 0; }
    100% { transform: scale(1.18); opacity: 0; }
}
.vgf-dia-titulo { margin: 0; color: var(--vgf-heading); font-family: var(--vgf-serif); font-size: 1.35rem; font-weight: 700; line-height: 1.15; }
.vgf-dia-sub { margin: 4px 0 0; color: var(--vgf-muted); font-size: .86rem; line-height: 1.5; }

/* ── Tabs ── */
.vgf-tabs { display: flex; gap: 6px; flex-wrap: wrap; padding: 4px; border-radius: 14px; border: 1px solid var(--vgf-border); background: var(--vgf-surface); }
.vgf-tab {
    appearance: none; border: 0; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px;
    min-height: 38px; padding: 0 14px; border-radius: 11px;
    background: transparent; color: var(--vgf-muted);
    font-family: var(--vgf-sans); font-size: .85rem; font-weight: 650;
}
.vgf-tab .n {
    display: inline-grid; place-items: center; min-width: 20px; height: 20px;
    padding: 0 6px; border-radius: 999px;
    background: color-mix(in srgb, var(--vgf-muted) 14%, transparent);
    font-size: .7rem; font-weight: 700;
}
.vgf-tab.is-active { background: var(--vgf-gold-soft); color: var(--vgf-gold-ink); box-shadow: inset 0 0 0 1px var(--vgf-gold-line); }
.vgf-tab.is-active .n { background: color-mix(in srgb, var(--vgf-gold) 22%, transparent); }
.vgf-panel-tab[hidden] { display: none; }

/* ── Tarjetas de hallazgo ── */
.vgf-cards { display: grid; gap: 12px; }
.vgf-card {
    border: 1px solid var(--vgf-border); border-radius: 16px;
    background: var(--vgf-surface); padding: 16px;
    display: grid; gap: 10px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.vgf-card.is-alta { border-left: 4px solid var(--vgf-danger); }
.vgf-card.is-media { border-left: 4px solid var(--vgf-warning); }
.vgf-card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.vgf-card-titulo { margin: 0; color: var(--vgf-heading); font-family: var(--vgf-serif); font-size: 1.05rem; font-weight: 700; line-height: 1.25; }
.vgf-card-quien { margin: 2px 0 0; color: var(--vgf-muted); font-size: .8rem; }
.vgf-card-quien strong { color: var(--vgf-heading); font-weight: 650; }
.vgf-sev {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700;
    white-space: nowrap;
}
.vgf-sev.is-alta { background: var(--vgf-danger-bg); color: var(--vgf-danger); border: 1px solid var(--vgf-danger-line); }
.vgf-sev.is-media { background: var(--vgf-warning-bg); color: var(--vgf-warning); border: 1px solid var(--vgf-warning-line); }
.vgf-card-valor { margin: 0; color: var(--vgf-text); font-size: .92rem; line-height: 1.5; }
.vgf-card-contexto {
    margin: 0; padding: 9px 12px; border-radius: 10px;
    background: var(--vgf-surface-warm); border: 1px dashed var(--vgf-border);
    color: var(--vgf-muted); font-size: .8rem; line-height: 1.5;
}
.vgf-card-contexto i { color: var(--vgf-gold-ink); margin-right: 6px; }
.vgf-casos { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; font-size: .78rem; color: var(--vgf-muted); }
.vgf-caso-link {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 999px;
    border: 1px solid var(--vgf-gold-line); background: var(--vgf-gold-soft);
    color: var(--vgf-gold-ink); font-weight: 650; text-decoration: none;
}
.vgf-card-pie { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.vgf-estado {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 3px 10px; border-radius: 999px; font-size: .72rem; font-weight: 700;
    border: 1px solid var(--vgf-border); background: var(--vgf-surface-warm); color: var(--vgf-muted);
}
.vgf-estado.is-nuevo { border-color: var(--vgf-info); background: var(--vgf-info-bg); color: var(--vgf-info); }
.vgf-estado.is-revisado { border-color: var(--vgf-warning-line); background: var(--vgf-warning-bg); color: var(--vgf-warning); }
.vgf-estado.is-resuelto { border-color: var(--vgf-success-line); background: var(--vgf-success-bg); color: var(--vgf-success); }
.vgf-acciones { display: flex; gap: 8px; flex-wrap: wrap; }
.vgf-btn-mini {
    appearance: none; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    min-height: 32px; padding: 0 12px; border-radius: 9px;
    border: 1px solid var(--vgf-border); background: var(--vgf-surface);
    color: var(--vgf-heading); font-family: var(--vgf-sans); font-size: .76rem; font-weight: 650;
}
.vgf-btn-mini:hover { border-color: var(--vgf-gold-line); background: var(--vgf-gold-soft); color: var(--vgf-gold-ink); }

/* ── Por persona ── */
.vgf-nota-neutral {
    padding: 10px 14px; border-radius: 12px;
    border: 1px solid var(--vgf-info); background: var(--vgf-info-bg);
    color: var(--vgf-info); font-size: .82rem; line-height: 1.5;
}
.vgf-nota-ambar {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 11px 14px; border-radius: 12px;
    border: 1px solid var(--vgf-warning-line); background: var(--vgf-warning-bg);
    color: var(--vgf-warning); font-size: .82rem; line-height: 1.5;
}
.vgf-nota-ambar i { margin-top: 2px; }
.vgf-nota-ambar a { color: inherit; font-weight: 700; text-decoration: underline; }
.vgf-personas { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
.vgf-persona {
    border: 1px solid var(--vgf-border); border-radius: 16px;
    background: var(--vgf-surface); padding: 14px 16px;
    display: grid; gap: 10px;
}
.vgf-persona-head { display: flex; align-items: center; gap: 10px; }
.vgf-persona-avatar {
    width: 38px; height: 38px; border-radius: 999px;
    display: grid; place-items: center;
    background: var(--vgf-gold-soft); border: 1px solid var(--vgf-gold-line);
    color: var(--vgf-gold-ink); font-weight: 700; font-size: .9rem;
}
.vgf-persona-nombre { margin: 0; color: var(--vgf-heading); font-size: .95rem; font-weight: 700; }
.vgf-persona-sub { margin: 1px 0 0; color: var(--vgf-muted); font-size: .74rem; }
.vgf-indicadores { display: grid; gap: 7px; margin: 0; padding: 0; list-style: none; }
.vgf-indicadores li {
    display: flex; align-items: baseline; justify-content: space-between; gap: 10px;
    padding: 6px 10px; border-radius: 9px; background: var(--vgf-surface-warm);
    font-size: .8rem;
}
.vgf-indicadores .k { color: var(--vgf-muted); }
.vgf-indicadores .v { color: var(--vgf-heading); font-weight: 700; white-space: nowrap; }
.vgf-indicadores .prom { color: var(--vgf-muted); font-weight: 500; font-size: .72rem; }

/* ── Historico ── */
.vgf-hist { display: grid; gap: 10px; }
.vgf-hist-item {
    display: grid; gap: 8px;
    border: 1px solid var(--vgf-border); border-radius: 14px;
    background: var(--vgf-surface); padding: 12px 14px;
}
.vgf-hist-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
.vgf-hist-titulo { margin: 0; color: var(--vgf-heading); font-size: .9rem; font-weight: 700; }
.vgf-hist-meta { color: var(--vgf-muted); font-size: .75rem; }
.vgf-hist-resumen { margin: 0; color: var(--vgf-text); font-size: .82rem; line-height: 1.45; }
.vgf-hist-chips { display: flex; gap: 8px; flex-wrap: wrap; font-size: .74rem; color: var(--vgf-muted); }

/* ── Paneles / informe (heredado del v1) ── */
.vgf-summary { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.vgf-summary-item {
    background: var(--vgf-surface); border: 1px solid var(--vgf-border);
    border-radius: 14px; padding: 12px 14px;
    box-shadow: 0 1px 2px rgba(27,39,70,.03), 0 10px 22px -21px rgba(27,39,70,.18);
}
.vgf-summary-label { color: var(--vgf-muted); font-size: .68rem; font-weight: 650; letter-spacing: .045em; text-transform: uppercase; }
.vgf-summary-value { margin-top: 4px; color: var(--vgf-heading); font-size: 1.02rem; font-weight: 650; }
.vgf-summary-value.is-verde { color: var(--vgf-success); }
.vgf-summary-value.is-amarillo { color: var(--vgf-warning); }
.vgf-summary-value.is-rojo { color: var(--vgf-danger); }
.vgf-panel { background: var(--vgf-surface); border: 1px solid var(--vgf-border); border-radius: 16px; padding: 16px; display: grid; gap: 12px; }
.vgf-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.vgf-panel-title { margin: 0; color: var(--vgf-heading); font-family: var(--vgf-serif); font-size: 1.35rem; font-weight: 650; }
.vgf-panel-sub { margin: 3px 0 0; color: var(--vgf-muted); font-size: .85rem; }
.vgf-toolbar { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.vgf-btn {
    position: relative; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
    min-height: 40px; padding: 0 16px; border: 0; border-radius: 12px; cursor: pointer;
    color: #fff; font-family: var(--vgf-sans); font-size: .85rem; font-weight: 650;
    background: linear-gradient(145deg, var(--vgf-gold), var(--vgf-brand) 60%);
    box-shadow: 0 12px 22px -14px color-mix(in srgb, var(--vgf-brand) 70%, transparent);
    text-decoration: none;
}
.vgf-btn[disabled] { opacity: .55; cursor: not-allowed; }
.vgf-btn.sec { color: var(--vgf-gold-ink); background: var(--vgf-gold-soft); border: 1px solid var(--vgf-gold-line); box-shadow: none; }
.vgf-reading { background: var(--vgf-surface); border: 1px solid var(--vgf-border); border-radius: 16px; overflow: hidden; }
.vgf-reading-head {
    display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;
    padding: 14px 22px; border-bottom: 1px solid var(--vgf-border); background: var(--vgf-surface-warm);
}
.vgf-meta { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; margin: 0; color: var(--vgf-muted); font-size: .8rem; }
.vgf-chip {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 999px;
    border: 1px solid var(--vgf-gold-line); background: var(--vgf-gold-soft);
    color: var(--vgf-gold-ink); font-weight: 650;
}
.vgf-chip.is-plantilla { border-color: var(--vgf-success-line); background: var(--vgf-success-bg); color: var(--vgf-success); }
.vgf-card-body { padding: 24px 22px 28px; }
.vgf-informe { max-width: 46rem; color: var(--vgf-text); font-size: .96rem; line-height: 1.65; }
.vgf-informe h3 { margin: 20px 0 9px; color: var(--vgf-heading); font-family: var(--vgf-serif); font-size: 1.3rem; font-weight: 650; line-height: 1.12; }
.vgf-informe h3:first-child { margin-top: 0; }
.vgf-informe p { margin: 0 0 13px; }
.vgf-informe hr { border: 0; border-top: 1px solid var(--vgf-border); margin: 18px 0; }
.vgf-informe ul { display: grid; gap: 7px; margin: 0 0 14px; padding: 0; list-style: none; }
.vgf-informe li { position: relative; padding-left: 22px; }
.vgf-informe li::before {
    content: ""; position: absolute; left: 1px; top: .7em;
    width: 7px; height: 7px; border-radius: 999px;
    background: var(--vgf-gold); box-shadow: 0 0 0 4px var(--vgf-gold-soft);
}
.vgf-informe strong { color: var(--vgf-heading); font-weight: 650; }
.vgf-vacio {
    display: grid; justify-items: center; gap: 8px;
    padding: 48px 18px; text-align: center; color: var(--vgf-muted);
    background: var(--vgf-surface-warm);
}
.vgf-vacio .ico {
    width: 58px; height: 58px; border-radius: 18px;
    display: grid; place-items: center;
    background: var(--vgf-gold-soft); color: var(--vgf-gold-ink);
    border: 1px solid var(--vgf-gold-line); font-size: 1.25rem;
}
.vgf-vacio strong { color: var(--vgf-heading); font-size: 1rem; font-weight: 650; }
.vgf-vacio p { max-width: 34rem; margin: 0; font-size: .88rem; line-height: 1.55; }

@media (max-width: 900px) {
    .vgf-hero-section { grid-template-columns: minmax(0, 1fr); align-items: start; gap: 12px; }
    .vgf-status-pill { justify-self: start; }
    .vgf-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 640px) {
    .vgf { padding: 14px 12px 34px; }
    .vgf-summary { grid-template-columns: 1fr; }
    .vgf-title { font-size: 2rem; }
    .vgf-title-lockup { grid-template-columns: 42px minmax(0, 1fr); column-gap: 12px; }
    .vgf-hero-icon { width: 42px; height: 42px; border-radius: 14px; font-size: 1rem; }
    .vgf-toolbar, .vgf-toolbar form, .vgf-btn { width: 100%; }
    .vgf-card-body { padding: 20px 16px; }
    .vgf-dia { grid-template-columns: 46px minmax(0, 1fr); column-gap: 12px; padding: 16px 14px; }
    .vgf-dia-icon { width: 46px; height: 46px; font-size: 1.25rem; }
    .vgf-tabs { position: sticky; top: 0; z-index: 5; }
    .vgf-personas { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) {
    .vgf *, .vgf *::before, .vgf *::after {
        transition-duration: .01ms !important;
        animation-duration: .01ms !important;
    }
}
</style>

<div class="vgf">
    <div class="vgf-shell">
        <section class="vgf-hero-section">
            <div class="vgf-title-lockup">
                <div class="vgf-hero-icon" aria-hidden="true">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <p class="vgf-kicker">Auditor&iacute;a continua</p>
                    <h1 class="vgf-title">Guardi&aacute;n financiero</h1>
                    <p class="vgf-subtitle">Cruza tus libros y observa los patrones de operaci&oacute;n del equipo. Cuando algo se sale del patr&oacute;n del hotel, te dice qu&eacute; revisar y con qui&eacute;n confirmarlo &mdash; sin acusar a nadie.</p>
                </div>
            </div>
            <span class="vgf-status-pill is-<?= $vgfSafe($semaforo) ?>">
                <i class="fa-solid <?= $vgfSafe($semaforoIcono) ?>" aria-hidden="true"></i>
                <?= $vgfSafe($semaforoTexto) ?>
            </span>
        </section>

        <?php if ($mensaje = get_mensaje()): ?>
            <?php
                $tipo = (string) ($mensaje['tipo'] ?? 'info');
                $tipo = in_array($tipo, ['success', 'error', 'info'], true) ? $tipo : 'info';
            ?>
            <div class="vgf-alert is-<?= $vgfSafe($tipo) ?>">
                <?= $mensaje['texto'] ?? '' ?>
            </div>
        <?php endif; ?>

        <!-- Semaforo del dia -->
        <section class="vgf-dia is-<?= $vgfSafe($semaforo) ?>" aria-live="polite">
            <div class="vgf-dia-icon" aria-hidden="true">
                <i class="fa-solid <?= $vgfSafe($semaforoIcono) ?>"></i>
            </div>
            <div>
                <?php if ($semaforo === 'verde'): ?>
                    <h2 class="vgf-dia-titulo">Hoy: todo en orden</h2>
                    <p class="vgf-dia-sub">
                        El Guardi&aacute;n revis&oacute; la integridad de tus libros y
                        <?= (int) $reglasVigiladas ?> patrones de comportamiento sobre
                        <?= (int) $operaciones ?> operaciones de los &uacute;ltimos <?= (int) $ventanaDias ?> d&iacute;as.
                        Nada se sale del patr&oacute;n de tu hotel.
                    </p>
                <?php else: ?>
                    <h2 class="vgf-dia-titulo">
                        <?= (int) $patronesHallazgos + $errores + $warnings ?> se&ntilde;al(es) a revisar
                    </h2>
                    <?php
                        $partesDia = [];
                        if ($patronesHallazgos > 0) {
                            $partesDia[] = (int) $patronesHallazgos . ' patrón(es) de comportamiento';
                        }
                        if (($errores + $warnings) > 0) {
                            $partesDia[] = (int) ($errores + $warnings) . ' señal(es) de integridad de libros';
                        }
                    ?>
                    <p class="vgf-dia-sub">
                        <?= $vgfSafe(implode(' y ', $partesDia)) ?>.
                        Ninguna es una acusaci&oacute;n: son puntos donde conviene confirmar con el equipo.
                    </p>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($patrones !== null && empty($patrones['config']['horario_configurado'])): ?>
            <!-- Aviso ambar (patron "configuracion incompleta"): trampa silenciosa,
                 el Guardian esta usando el horario operativo default. -->
            <div class="vgf-nota-ambar">
                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                <span>
                    <strong>Horario operativo sin configurar.</strong>
                    El Guardi&aacute;n est&aacute; usando el horario estándar
                    (<?= $vgfSafe($patrones['config']['horario_inicio'] ?? '06:00') ?> a <?= $vgfSafe($patrones['config']['horario_fin'] ?? '23:59') ?>).
                    Si tu recepci&oacute;n opera de noche, movimientos nocturnos leg&iacute;timos aparecer&aacute;n como patr&oacute;n a revisar.
                    P&iacute;delo en <a href="<?= url('configuracion') ?>">Configuraci&oacute;n</a> o a tu asesor Medisoft
                   .
                </span>
            </div>
        <?php endif; ?>

        <?php if ($volumenInsuficiente): ?>
            <div class="vgf-nota-neutral">
                <i class="fa-solid fa-seedling" aria-hidden="true"></i>
                Tu hotel registr&oacute; pocas operaciones en la ventana (<?= (int) $operaciones ?> de <?= (int) ($volumen['minimo'] ?? 10) ?> m&iacute;nimas):
                las reglas estad&iacute;sticas quedan en pausa para no generar falsas alarmas. Se activan solas al crecer el movimiento.
            </div>
        <?php endif; ?>

        <!-- Pestanas -->
        <nav class="vgf-tabs" role="tablist" aria-label="Secciones del Guardián">
            <button type="button" class="vgf-tab" data-vgf-tab="hoy" role="tab">
                <i class="fa-solid fa-sun" aria-hidden="true"></i> Hoy
                <?php if (count($tarjetas) > 0): ?><span class="n"><?= count($tarjetas) ?></span><?php endif; ?>
            </button>
            <button type="button" class="vgf-tab" data-vgf-tab="persona" role="tab">
                <i class="fa-solid fa-user-group" aria-hidden="true"></i> Por persona
            </button>
            <button type="button" class="vgf-tab" data-vgf-tab="historico" role="tab">
                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Hist&oacute;rico
                <?php if ((int) $conteosEstado['nuevo'] > 0): ?><span class="n"><?= (int) $conteosEstado['nuevo'] ?></span><?php endif; ?>
            </button>
        </nav>

        <!-- ══ TAB: HOY ══ -->
        <section class="vgf-panel-tab" data-vgf-panel="hoy" role="tabpanel">
            <div class="vgf-shell" style="gap: 12px;">

                <?php if (!empty($tarjetas)): ?>
                <div class="vgf-cards">
                    <?php foreach ($tarjetas as $t):
                        $u = $t['usuario'];
                        $sev = in_array($u['severidad'] ?? '', ['alta', 'media'], true) ? $u['severidad'] : 'media';
                        $row = $t['estado_row'];
                        $casos = array_slice((array) ($u['casos'] ?? []), 0, 3);
                        $masCasos = max(0, count((array) ($u['casos'] ?? [])) - 3);
                    ?>
                    <article class="vgf-card is-<?= $vgfSafe($sev) ?>">
                        <div class="vgf-card-head">
                            <div>
                                <h3 class="vgf-card-titulo"><?= $vgfSafe($t['titulo']) ?></h3>
                                <p class="vgf-card-quien">Conviene confirmar con <strong><?= $vgfSafe($u['nombre'] ?? 'el equipo') ?></strong></p>
                            </div>
                            <span class="vgf-sev is-<?= $vgfSafe($sev) ?>">
                                <i class="fa-solid <?= $sev === 'alta' ? 'fa-circle-exclamation' : 'fa-triangle-exclamation' ?>" aria-hidden="true"></i>
                                <?= $sev === 'alta' ? 'Revisar hoy' : 'Revisar esta semana' ?>
                            </span>
                        </div>

                        <p class="vgf-card-valor"><?= $vgfSafe($u['valor'] ?? '') ?></p>

                        <?php if (!empty($u['contexto'])): ?>
                        <p class="vgf-card-contexto">
                            <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i><?= $vgfSafe($u['contexto']) ?>
                        </p>
                        <?php endif; ?>

                        <?php if (!empty($casos)): ?>
                        <div class="vgf-casos">
                            <span>Ver de cerca:</span>
                            <?php foreach ($casos as $caso): $link = $linkCaso((array) $caso); if ($link !== '') { echo $link; } ?>
                            <?php endforeach; ?>
                            <?php if ($masCasos > 0): ?><span>y <?= (int) $masCasos ?> m&aacute;s</span><?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <div class="vgf-card-pie">
                            <?= $estadoChip($row) ?>
                            <?php if ($row): ?>
                            <div class="vgf-acciones">
                                <?php if (($row['estado'] ?? '') !== 'revisado' && ($row['estado'] ?? '') !== 'resuelto'): ?>
                                <form method="POST" action="<?= url('ia/vigilancia-financiera/hallazgos/' . (int) $row['id'] . '/estado') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="revisado">
                                    <button type="submit" class="vgf-btn-mini"><i class="fa-solid fa-eye" aria-hidden="true"></i> Marcar revisado</button>
                                </form>
                                <?php endif; ?>
                                <?php if (($row['estado'] ?? '') !== 'resuelto'): ?>
                                <form method="POST" action="<?= url('ia/vigilancia-financiera/hallazgos/' . (int) $row['id'] . '/estado') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="resuelto">
                                    <button type="submit" class="vgf-btn-mini"><i class="fa-solid fa-check" aria-hidden="true"></i> Resuelto</button>
                                </form>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <section class="vgf-summary" aria-label="Integridad de libros">
                    <div class="vgf-summary-item">
                        <div class="vgf-summary-label">Patrones a revisar</div>
                        <div class="vgf-summary-value <?= $patronesHallazgos > 0 ? ($patronesAltos > 0 ? 'is-rojo' : 'is-amarillo') : 'is-verde' ?>"><?= (int) $patronesHallazgos ?></div>
                    </div>
                    <div class="vgf-summary-item">
                        <div class="vgf-summary-label">Errores de libros</div>
                        <div class="vgf-summary-value <?= $errores > 0 ? 'is-rojo' : 'is-verde' ?>"><?= $errores ?></div>
                    </div>
                    <div class="vgf-summary-item">
                        <div class="vgf-summary-label">Avisos de libros</div>
                        <div class="vgf-summary-value <?= $warnings > 0 ? 'is-amarillo' : 'is-verde' ?>"><?= $warnings ?></div>
                    </div>
                    <div class="vgf-summary-item">
                        <div class="vgf-summary-label">Operaciones vigiladas</div>
                        <div class="vgf-summary-value"><?= (int) $operaciones ?></div>
                    </div>
                </section>

                <section class="vgf-panel">
                    <div class="vgf-panel-head">
                        <div>
                            <h2 class="vgf-panel-title">La lectura del analista</h2>
                            <p class="vgf-panel-sub">Se genera una vez al d&iacute;a y se guarda. Reg&eacute;nerala si registraste movimientos nuevos.</p>
                        </div>
                    </div>
                    <div class="vgf-toolbar">
                        <form method="POST" action="<?= url('ia/vigilancia-financiera/regenerar') ?>"
                              onsubmit="var b=this.querySelector('button'); b.disabled=true; b.textContent='Analizando...'; return true;">
                            <?= csrf_field() ?>
                            <button type="submit" class="vgf-btn">
                                <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i>
                                <?= $hayInforme ? 'Regenerar con datos actuales' : 'Generar informe de hoy' ?>
                            </button>
                        </form>
                        <a class="vgf-btn sec" href="<?= url('operacion/conciliacion-financiera') ?>">
                            <i class="fa-solid fa-table-list" aria-hidden="true"></i>
                            Ver conciliaci&oacute;n completa
                        </a>
                    </div>
                </section>

                <article class="vgf-reading">
                    <div class="vgf-reading-head">
                        <p class="vgf-meta">
                            <span class="vgf-chip <?= $esPlantilla ? 'is-plantilla' : '' ?>">
                                <i class="fa-solid <?= $esPlantilla ? 'fa-circle-check' : 'fa-user-secret' ?>" aria-hidden="true"></i>
                                <?= $esPlantilla ? 'Verificaci&oacute;n autom&aacute;tica' : 'Analista forense' ?>
                            </span>
                            <?php if ($generadoHumano !== ''): ?>
                                <span>Generado el <?= $vgfSafe($generadoHumano) ?></span>
                            <?php endif; ?>
                        </p>
                    </div>

                    <?php if ($hayInforme): ?>
                        <div class="vgf-card-body">
                            <div class="vgf-informe"><?= $vgfMarkdown($informe) ?></div>
                        </div>
                    <?php else: ?>
                        <div class="vgf-vacio">
                            <div class="ico" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></div>
                            <strong>A&uacute;n no hay informe del Guardi&aacute;n</strong>
                            <p><?= $vgfSafe($resultado['message'] ?? 'Usa el boton "Generar informe" para revisar tus libros.') ?></p>
                        </div>
                    <?php endif; ?>
                </article>
            </div>
        </section>

        <!-- ══ TAB: POR PERSONA ══ -->
        <section class="vgf-panel-tab" data-vgf-panel="persona" role="tabpanel" hidden>
            <div class="vgf-shell" style="gap: 12px;">
                <div class="vgf-nota-neutral">
                    <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                    Esta es una radiograf&iacute;a del equipo con el promedio del hotel al lado, no una lista negra.
                    Un n&uacute;mero distinto al promedio casi siempre tiene una explicaci&oacute;n operativa normal.
                </div>

                <?php if (empty($personas)): ?>
                    <div class="vgf-panel">
                        <p class="vgf-panel-sub" style="margin: 0;">Sin actividad de usuarios en la ventana de <?= (int) $ventanaDias ?> d&iacute;as.</p>
                    </div>
                <?php else: ?>
                <div class="vgf-personas">
                    <?php foreach ($personas as $p):
                        $iniciales = mb_strtoupper(mb_substr((string) ($p['nombre'] ?? '?'), 0, 1));
                    ?>
                    <article class="vgf-persona">
                        <div class="vgf-persona-head">
                            <div class="vgf-persona-avatar" aria-hidden="true"><?= $vgfSafe($iniciales) ?></div>
                            <div>
                                <h3 class="vgf-persona-nombre"><?= $vgfSafe($p['nombre'] ?? '') ?></h3>
                                <p class="vgf-persona-sub"><?= (int) ($p['reservas'] ?? 0) ?> reservas · <?= (int) ($p['movimientos'] ?? 0) ?> movimientos en <?= (int) $ventanaDias ?> d&iacute;as</p>
                            </div>
                        </div>
                        <ul class="vgf-indicadores">
                            <li>
                                <span class="k">Descuento otorgado</span>
                                <span class="v"><?= number_format((float) ($p['descuento_pct'] ?? 0), 1) ?>%
                                    <span class="prom">· hotel <?= number_format((float) ($promHotel['descuento_pct'] ?? 0), 1) ?>%</span>
                                </span>
                            </li>
                            <li>
                                <span class="k">Cancelaciones</span>
                                <span class="v"><?= (int) ($p['canceladas'] ?? 0) ?>
                                    <span class="prom">· hotel <?= number_format((float) ($promHotel['canceladas'] ?? 0), 1) ?></span>
                                </span>
                            </li>
                            <li>
                                <span class="k">Reversiones / ajustes</span>
                                <span class="v"><?= (int) ($p['reversiones'] ?? 0) ?>
                                    <span class="prom">· hotel <?= number_format((float) ($promHotel['reversiones'] ?? 0), 1) ?></span>
                                </span>
                            </li>
                            <li>
                                <span class="k">Cortes con diferencia</span>
                                <span class="v"><?= (int) ($p['cortes_con_diferencia'] ?? 0) ?> de <?= (int) ($p['cortes'] ?? 0) ?>
                                    <span class="prom">· hotel <?= number_format((float) ($promHotel['cortes_con_diferencia'] ?? 0), 1) ?></span>
                                </span>
                            </li>
                        </ul>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>

        <!-- ══ TAB: HISTORICO ══ -->
        <section class="vgf-panel-tab" data-vgf-panel="historico" role="tabpanel" hidden>
            <div class="vgf-shell" style="gap: 12px;">
                <div class="vgf-hist-chips">
                    <span class="vgf-estado is-nuevo"><?= (int) $conteosEstado['nuevo'] ?> nuevos</span>
                    <span class="vgf-estado is-revisado"><?= (int) $conteosEstado['revisado'] ?> revisados</span>
                    <span class="vgf-estado is-resuelto"><?= (int) $conteosEstado['resuelto'] ?> resueltos</span>
                </div>

                <?php if (empty($historico)): ?>
                    <div class="vgf-panel">
                        <p class="vgf-panel-sub" style="margin: 0;">
                            Sin hallazgos registrados todav&iacute;a. Cuando el Guardi&aacute;n detecte un patr&oacute;n,
                            quedar&aacute; aqu&iacute; con su estado para que nada se pierda.
                        </p>
                    </div>
                <?php else: ?>
                <div class="vgf-hist">
                    <?php foreach ($historico as $h): ?>
                    <article class="vgf-hist-item">
                        <div class="vgf-hist-head">
                            <div>
                                <h3 class="vgf-hist-titulo"><?= $vgfSafe($h['titulo']) ?></h3>
                                <p class="vgf-hist-meta">
                                    <?= $vgfSafe($h['usuario_nombre'] ?? 'Sin usuario') ?>
                                    · detectado <?= $vgfSafe(date('d/m/Y', strtotime((string) $h['detectado_en']))) ?>
                                    <?php if ($h['ultima_vez_en'] !== $h['detectado_en']): ?>
                                        · visto por &uacute;ltima vez <?= $vgfSafe(date('d/m/Y', strtotime((string) $h['ultima_vez_en']))) ?>
                                    <?php endif; ?>
                                    <?php if (!empty($h['revisado_por_nombre'])): ?>
                                        · atendido por <?= $vgfSafe($h['revisado_por_nombre']) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?= $estadoChip($h) ?>
                        </div>
                        <?php if (!empty($h['resumen'])): ?>
                            <p class="vgf-hist-resumen"><?= $vgfSafe($h['resumen']) ?></p>
                        <?php endif; ?>
                        <div class="vgf-acciones">
                            <?php foreach (['revisado' => 'Marcar revisado', 'resuelto' => 'Resuelto', 'nuevo' => 'Reabrir'] as $accion => $labelAccion): ?>
                                <?php if (($h['estado'] ?? '') === $accion) { continue; } ?>
                                <?php if ($accion === 'nuevo' && ($h['estado'] ?? '') === 'nuevo') { continue; } ?>
                                <form method="POST" action="<?= url('ia/vigilancia-financiera/hallazgos/' . (int) $h['id'] . '/estado') ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="estado" value="<?= $vgfSafe($accion) ?>">
                                    <input type="hidden" name="volver_a" value="historico">
                                    <button type="submit" class="vgf-btn-mini"><?= $vgfSafe($labelAccion) ?></button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script>
(function () {
    var tabs = document.querySelectorAll('.vgf-tab');
    var panels = document.querySelectorAll('.vgf-panel-tab');

    function activar(nombre, empujarUrl) {
        tabs.forEach(function (t) {
            var activo = t.getAttribute('data-vgf-tab') === nombre;
            t.classList.toggle('is-active', activo);
            t.setAttribute('aria-selected', activo ? 'true' : 'false');
        });
        panels.forEach(function (p) {
            p.hidden = p.getAttribute('data-vgf-panel') !== nombre;
        });
        if (empujarUrl && window.history && window.history.replaceState) {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', nombre);
            window.history.replaceState(null, '', url.toString());
        }
    }

    tabs.forEach(function (t) {
        t.addEventListener('click', function () {
            activar(t.getAttribute('data-vgf-tab'), true);
        });
    });

    activar(<?= json_encode($tabInicial) ?>, false);
})();
</script>
