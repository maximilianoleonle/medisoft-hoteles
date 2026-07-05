<?php
/**
 * Motor legal/fiscal MX (Fase 6 nomina core).
 *
 * Calcula ISR retenido (tabla por periodicidad), subsidio al empleo e IMSS
 * obrero para UN trabajador del periodo, consumiendo EXCLUSIVAMENTE el
 * catalogo versionado nomina_reglas_legales. Cero valores legales en codigo.
 *
 * Politica de fallas:
 * - Sin tabla ISR de la periodicidad => BLOQUEANTE (la nomina legal no cierra).
 * - Sin cuotas IMSS o sin factor de integracion => alerta informativa, la
 *   linea se omite (algunos negocios solo retienen ISR internamente).
 * - Es CALCULO INTERNO AUDITABLE: no emite CFDI, no timbra, no dispersa.
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/NominaReglasLegalesService.php';

class NominaFiscalService {

    private $reglas;

    /** Dias base por periodicidad para bases diarias. */
    private $diasBase = ['semanal' => 7, 'quincenal' => 15, 'mensual' => 30];

    public function __construct(?NominaReglasLegalesService $reglas = null) {
        $this->reglas = $reglas ?: new NominaReglasLegalesService();
    }

    /**
     * Resuelve las reglas necesarias para un periodo legal.
     * Devuelve ['reglas' => [...], 'alertas' => [], 'bloqueado' => bool].
     */
    public function resolverReglas(string $pais, string $periodicidad, string $fechaFin): array {
        $tipos = [
            'isr' => 'isr_tabla_' . $periodicidad,
            'subsidio' => 'subsidio_empleo_tabla',
            'imss' => 'imss_cuotas_obrero',
            'uma' => 'uma_diaria',
            'factor' => 'factor_integracion_minimo',
        ];

        $resueltas = [];
        foreach ($tipos as $clave => $tipo) {
            $resueltas[$clave] = $this->reglas->vigente($pais, $tipo, $fechaFin);
        }

        $alertas = [];
        $bloqueado = false;

        if ($resueltas['isr'] === null) {
            $alertas[] = 'MODO LEGAL: no hay tabla ISR ' . $periodicidad . ' (' . $pais . ') vigente al ' . $fechaFin . '. Capturala en el panel Medisoft antes de cerrar.';
            $bloqueado = true;
        }
        if ($resueltas['imss'] === null) {
            $alertas[] = 'Modo legal: sin cuotas IMSS obrero vigentes; la retencion IMSS se omite.';
        }
        if ($resueltas['imss'] !== null && $resueltas['factor'] === null) {
            $alertas[] = 'Modo legal: sin factor de integracion minimo; la retencion IMSS se omite.';
        }

        return ['reglas' => $resueltas, 'alertas' => $alertas, 'bloqueado' => $bloqueado];
    }

    /**
     * Lineas fiscales (deducciones) para un trabajador.
     *
     * @param float  $baseGravableIsr  Percepciones gravables ISR del periodo.
     * @param float  $salarioDiario    Salario diario nominal (para SBC).
     * @param int    $dias             Dias del periodo.
     * @param array  $reglas           Resultado de resolverReglas()['reglas'].
     */
    public function lineasFiscales(float $baseGravableIsr, float $salarioDiario, int $dias, string $periodicidad, array $reglas): array {
        $lineas = [];
        $alertas = [];

        // --- ISR retenido por tabla de tramos. ---
        $reglaIsr = $reglas['isr'] ?? null;
        if ($reglaIsr !== null && $baseGravableIsr > 0) {
            $isr = $this->isrPorTabla($baseGravableIsr, $reglaIsr);
            if ($isr === null) {
                $alertas[] = 'Tabla ISR malformada: no se pudo calcular la retencion.';
            } else {
                $subsidio = 0.0;
                $reglaSub = $reglas['subsidio'] ?? null;
                if ($reglaSub !== null) {
                    $subsidio = $this->subsidioPorTabla($baseGravableIsr, $reglaSub);
                }

                $isrNeto = round(max(0.0, $isr - $subsidio), 2);
                if ($isrNeto > 0) {
                    $lineas[] = [
                        'concepto_id' => null,
                        'concepto_nombre' => 'ISR retenido (tabla ' . $periodicidad . ')',
                        'tipo' => 'deduccion',
                        'clasificacion' => 'otro',
                        'origen' => 'fiscal',
                        'cantidad' => null,
                        'base' => round($baseGravableIsr, 2),
                        'monto' => $isrNeto,
                        'referencia' => 'ISR-' . (int) $reglaIsr['id'],
                    ];
                } elseif ($subsidio > $isr) {
                    $alertas[] = 'Subsidio al empleo supera el ISR: retencion 0 (subsidio entregable no automatizado en v1).';
                }
            }
        }

        // --- IMSS obrero sobre SBC. ---
        $reglaImss = $reglas['imss'] ?? null;
        $reglaFactor = $reglas['factor'] ?? null;
        if ($reglaImss !== null && $reglaFactor !== null && $salarioDiario > 0) {
            $factor = (float) $reglaFactor['valor'];
            $sbcDiario = $salarioDiario * ($factor > 0 ? $factor : 1.0);

            // Tope legal: 25 UMA diarias (si la UMA esta disponible).
            $reglaUma = $reglas['uma'] ?? null;
            if ($reglaUma !== null) {
                $tope = 25.0 * (float) $reglaUma['valor'];
                if ($sbcDiario > $tope) {
                    $sbcDiario = $tope;
                }
            }

            $imss = $this->imssObrero($sbcDiario, $dias, $reglaImss, $reglaUma);
            if ($imss === null) {
                $alertas[] = 'Cuotas IMSS malformadas: retencion omitida.';
            } elseif ($imss > 0) {
                $lineas[] = [
                    'concepto_id' => null,
                    'concepto_nombre' => 'IMSS obrero (SBC $' . number_format($sbcDiario, 2) . '/dia)',
                    'tipo' => 'deduccion',
                    'clasificacion' => 'otro',
                    'origen' => 'fiscal',
                    'cantidad' => null,
                    'base' => round($sbcDiario * $dias, 2),
                    'monto' => $imss,
                    'referencia' => 'IMSS-' . (int) $reglaImss['id'],
                ];
            }
        }

        return ['lineas' => $lineas, 'alertas' => $alertas];
    }

    /* ------------------------------------------------------------------ */

    /**
     * ISR por tabla de tramos:
     * {"tramos":[{"limite_inferior":0.01,"limite_superior":368.10,
     *             "cuota_fija":0.00,"porcentaje_excedente":1.92}, ...]}
     */
    private function isrPorTabla(float $base, array $regla): ?float {
        $tabla = json_decode((string) ($regla['valores_json'] ?? ''), true);
        if (!is_array($tabla) || !isset($tabla['tramos']) || !is_array($tabla['tramos'])) {
            return null;
        }

        foreach ($tabla['tramos'] as $tramo) {
            $inferior = (float) ($tramo['limite_inferior'] ?? 0);
            $superior = isset($tramo['limite_superior']) && $tramo['limite_superior'] !== null
                ? (float) $tramo['limite_superior']
                : null;

            if ($base >= $inferior && ($superior === null || $base <= $superior)) {
                $cuota = (float) ($tramo['cuota_fija'] ?? 0);
                $pct = (float) ($tramo['porcentaje_excedente'] ?? 0);
                return round($cuota + ($base - $inferior) * ($pct / 100.0), 2);
            }
        }

        return null;
    }

    /**
     * Subsidio al empleo por tabla:
     * {"tramos":[{"hasta":1768.96,"subsidio":407.02}, ...]} (hasta null = resto)
     */
    private function subsidioPorTabla(float $base, array $regla): float {
        $tabla = json_decode((string) ($regla['valores_json'] ?? ''), true);
        if (!is_array($tabla) || !isset($tabla['tramos']) || !is_array($tabla['tramos'])) {
            return 0.0;
        }

        foreach ($tabla['tramos'] as $tramo) {
            $hasta = isset($tramo['hasta']) && $tramo['hasta'] !== null ? (float) $tramo['hasta'] : null;
            if ($hasta === null || $base <= $hasta) {
                return round((float) ($tramo['subsidio'] ?? 0), 2);
            }
        }

        return 0.0;
    }

    /**
     * IMSS obrero por cuotas:
     * {"conceptos":[{"nombre":"Enfermedad y maternidad (excedente)",
     *                "porcentaje":0.40,"base":"sbc_excedente_3uma"},
     *               {"nombre":"Invalidez y vida","porcentaje":0.625,"base":"sbc"}, ...]}
     */
    private function imssObrero(float $sbcDiario, int $dias, array $regla, ?array $reglaUma): ?float {
        $tabla = json_decode((string) ($regla['valores_json'] ?? ''), true);
        if (!is_array($tabla) || !isset($tabla['conceptos']) || !is_array($tabla['conceptos'])) {
            return null;
        }

        $umaDiaria = $reglaUma !== null ? (float) $reglaUma['valor'] : null;
        $total = 0.0;

        foreach ($tabla['conceptos'] as $concepto) {
            $pct = (float) ($concepto['porcentaje'] ?? 0);
            if ($pct <= 0) {
                continue;
            }

            $tipoBase = (string) ($concepto['base'] ?? 'sbc');
            $baseDiaria = $sbcDiario;

            if ($tipoBase === 'sbc_excedente_3uma') {
                if ($umaDiaria === null) {
                    continue; // sin UMA no se puede calcular el excedente
                }
                $baseDiaria = max(0.0, $sbcDiario - 3.0 * $umaDiaria);
            }

            $total += $baseDiaria * $dias * ($pct / 100.0);
        }

        return round($total, 2);
    }
}
