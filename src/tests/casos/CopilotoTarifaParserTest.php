<?php
/**
 * Parser de sugerencias accionables del consejo de tarifa (Copiloto IA).
 * Puro: no toca la base. Valida que los limites duros los aplica PHP y que
 * ninguna entrada invalida tira el consejo completo.
 */

require_once __DIR__ . '/../bootstrap.php';

echo "CopilotoTarifaParserTest\n";

$hoy = '2026-07-15';
$texto = "**Lectura rapida** — La demanda pinta bien.\n**Recomendacion por ventana** — Subir 8-12% en 30 dias.\n**Ojo con** — El puente de septiembre.";

function ctp_bloque(array $sugerencias): string
{
    return "\n<sugerencias>" . json_encode($sugerencias) . '</sugerencias>';
}

// ── JSON perfecto ──
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '30', 'accion' => 'subir', 'pct' => 8, 'desde' => '2026-07-16', 'hasta' => '2026-08-14', 'motivo' => 'Ocupacion 82% proyectada'],
    ['ventana' => '60', 'accion' => 'mantener', 'pct' => 0, 'desde' => '2026-08-15', 'hasta' => '2026-09-13', 'motivo' => 'Ritmo estable'],
]), $hoy);
t_eq(2, count($r['sugerencias']), 'JSON perfecto: 2 sugerencias validas');
t_eq('subir', $r['sugerencias'][0]['accion'], 'JSON perfecto: accion subir');
t_eq(8.0, $r['sugerencias'][0]['pct'], 'JSON perfecto: pct 8');
t_eq(0.0, $r['sugerencias'][1]['pct'], 'mantener: pct forzado a 0');
t_ok(strpos($r['texto'], '<sugerencias>') === false, 'el texto visible no contiene el bloque');
t_ok(strpos($r['texto'], 'Lectura rapida') !== false, 'el texto visible conserva el consejo');

// ── pct fuera de rango (>15) se descarta sin tirar las demas ──
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '30', 'accion' => 'subir', 'pct' => 25, 'desde' => '2026-07-16', 'hasta' => '2026-07-31', 'motivo' => 'x'],
    ['ventana' => '60', 'accion' => 'bajar', 'pct' => 10, 'desde' => '2026-08-15', 'hasta' => '2026-08-31', 'motivo' => 'x'],
]), $hoy);
t_eq(1, count($r['sugerencias']), 'pct 25 descartado, la otra sobrevive');
t_eq('60', $r['sugerencias'][0]['ventana'], 'sobrevive la ventana 60');

// pct negativo se normaliza a positivo (la accion da la direccion)
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '30', 'accion' => 'bajar', 'pct' => -7, 'desde' => '2026-07-16', 'hasta' => '2026-07-31', 'motivo' => 'x'],
]), $hoy);
t_eq(7.0, $r['sugerencias'][0]['pct'] ?? null, 'pct -7 se normaliza a 7');

// pct 0 o no numerico en subir/bajar se descarta
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '30', 'accion' => 'subir', 'pct' => 0, 'desde' => '2026-07-16', 'hasta' => '2026-07-31', 'motivo' => 'x'],
    ['ventana' => '60', 'accion' => 'subir', 'pct' => 'diez', 'desde' => '2026-08-15', 'hasta' => '2026-08-31', 'motivo' => 'x'],
]), $hoy);
t_eq(0, count($r['sugerencias']), 'pct 0 y pct no numerico se descartan');

// ── fechas invertidas ──
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '30', 'accion' => 'subir', 'pct' => 8, 'desde' => '2026-08-10', 'hasta' => '2026-07-20', 'motivo' => 'x'],
]), $hoy);
t_eq(0, count($r['sugerencias']), 'fechas invertidas se descartan');

// ── fechas a mas de 90 dias ──
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '90', 'accion' => 'subir', 'pct' => 8, 'desde' => '2026-10-01', 'hasta' => '2026-10-20', 'motivo' => 'x'],
]), $hoy);
t_eq(0, count($r['sugerencias']), 'hasta > hoy+90 se descarta');

// fechas en el pasado tambien
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '30', 'accion' => 'subir', 'pct' => 8, 'desde' => '2026-07-01', 'hasta' => '2026-07-20', 'motivo' => 'x'],
]), $hoy);
t_eq(0, count($r['sugerencias']), 'desde < hoy se descarta');

// fecha imposible (31 de febrero)
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '30', 'accion' => 'subir', 'pct' => 8, 'desde' => '2026-02-31', 'hasta' => '2026-08-01', 'motivo' => 'x'],
]), $hoy);
t_eq(0, count($r['sugerencias']), 'fecha inexistente se descarta');

// ── bloque ausente: texto plano como hoy ──
$r = CopilotoIaService::parsearSugerenciasTarifa($texto, $hoy);
t_eq(0, count($r['sugerencias']), 'sin bloque: 0 sugerencias');
t_eq($texto, $r['texto'], 'sin bloque: el texto queda intacto');

// ── JSON malformado: no explota, texto limpio ──
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . "\n<sugerencias>[{ventana: 30, rota</sugerencias>", $hoy);
t_eq(0, count($r['sugerencias']), 'JSON malformado: 0 sugerencias');
t_ok(strpos($r['texto'], '<sugerencias>') === false, 'JSON malformado: el bloque no se muestra al usuario');

// ── extras: ventana duplicada o desconocida, accion desconocida ──
$r = CopilotoIaService::parsearSugerenciasTarifa($texto . ctp_bloque([
    ['ventana' => '30', 'accion' => 'subir', 'pct' => 8, 'desde' => '2026-07-16', 'hasta' => '2026-07-31', 'motivo' => 'a'],
    ['ventana' => '30', 'accion' => 'bajar', 'pct' => 5, 'desde' => '2026-07-16', 'hasta' => '2026-07-31', 'motivo' => 'duplicada'],
    ['ventana' => '45', 'accion' => 'subir', 'pct' => 8, 'desde' => '2026-07-16', 'hasta' => '2026-07-31', 'motivo' => 'ventana rara'],
    ['ventana' => '60', 'accion' => 'duplicar', 'pct' => 8, 'desde' => '2026-08-15', 'hasta' => '2026-08-31', 'motivo' => 'accion rara'],
]), $hoy);
t_eq(1, count($r['sugerencias']), 'duplicadas/ventana rara/accion rara: solo 1 valida');
t_eq('a', $r['sugerencias'][0]['motivo'], 'gana la primera entrada de la ventana');

t_fin();
