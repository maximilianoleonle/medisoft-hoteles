<?php
/**
 * Motor de calculo de nomina v2 (Fase 3 nomina core).
 *
 * Calcula la previsualizacion de un periodo por GRUPO DE PAGO:
 *   sueldo del periodo (salario vigente por dia, segun esquema)
 * + incidencias aprobadas del rango (conceptos del catalogo)
 * + conceptos del ledger v1 con solape (compatibilidad, excluye NOMV2-*)
 * - deducciones informativas (saldos de anticipos y prestamos)
 *
 * REGLA DE ROBUSTEZ: este servicio usa PDO en modo estricto (las consultas
 * lanzan excepcion). Un SELECT roto NUNCA debe producir una nomina en ceros.
 * Solo lectura: la escritura del snapshot vive en NominaCierreService.
 */

require_once __DIR__ . '/../../core/Database.php';

class NominaCalculoService {

    private $db;
    private $pdo;

    /** Dias base por esquema para prorrateo diario. */
    private $diasBase = ['semanal' => 7, 'quincenal' => 15, 'mensual' => 30];

    public function __construct(?Database $db = null) {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
        if (!$this->pdo instanceof PDO) {
            throw new Exception('Sin conexion a base de datos para el motor de nomina.');
        }
    }

    /**
     * Previsualizacion completa del periodo de un grupo.
     * Devuelve trabajadores con lineas, totales, alertas y bandera bloqueado.
     */
    public function preview(int $hotelId, int $grupoId, string $fechaInicio, string $fechaFin): array {
        if ($hotelId <= 0) {
            throw new Exception('Contexto de negocio no valido.');
        }
        $this->assertFecha($fechaInicio);
        $this->assertFecha($fechaFin);
        if ($fechaFin < $fechaInicio) {
            throw new Exception('La fecha fin no puede ser anterior a la fecha inicio.');
        }

        $grupo = $this->obtenerGrupo($hotelId, $grupoId);
        if (!$grupo) {
            throw new Exception('El grupo de pago no existe o esta inactivo en este negocio.');
        }

        $dias = (int) ((strtotime($fechaFin) - strtotime($fechaInicio)) / 86400) + 1;
        $redondeo = (string) ConfiguracionHotelRegistry::get('nomina.redondeo', 'centavos', $hotelId);
        $modo = (string) ConfiguracionHotelRegistry::get('nomina.modo', 'simplificada', $hotelId);
        $pais = (string) ConfiguracionHotelRegistry::get('nomina.pais', 'MX', $hotelId);

        $alertas = [];
        $bloqueado = false;

        // Modo legal: resolver reglas fiscales vigentes UNA vez para el periodo.
        $contextoFiscal = null;
        $reglasFiscales = null;
        if ($modo === 'legal') {
            require_once __DIR__ . '/NominaFiscalService.php';
            $fiscal = new NominaFiscalService();
            $resolucion = $fiscal->resolverReglas($pais, (string) $grupo['periodicidad'], $fechaFin);
            $alertas = array_merge($alertas, $resolucion['alertas']);
            if (!empty($resolucion['bloqueado'])) {
                $bloqueado = true;
            }
            $contextoFiscal = ['servicio' => $fiscal, 'reglas' => $resolucion['reglas']];
            $reglasFiscales = [];
            foreach ($resolucion['reglas'] as $claveRegla => $regla) {
                $reglasFiscales[$claveRegla] = $regla !== null ? [
                    'id' => (int) $regla['id'],
                    'tipo_regla' => $regla['tipo_regla'],
                    'ejercicio' => (int) $regla['ejercicio'],
                    'vigente_desde' => $regla['vigente_desde'],
                    'valor' => $regla['valor'],
                    'fuente' => $regla['fuente'],
                ] : null;
            }
        }

        // Solape con otros periodos NO anulados del mismo grupo: bloqueante.
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM trabajador_nomina_periodos
             WHERE hotel_id = ? AND grupo_nomina_id = ? AND estado != 'anulado'
               AND fecha_inicio <= ? AND fecha_fin >= ?"
        );
        $st->execute([$hotelId, $grupoId, $fechaFin, $fechaInicio]);
        $solapes = (int) ($st->fetch()['total'] ?? 0);
        if ($solapes > 0) {
            $alertas[] = 'El rango se solapa con ' . $solapes . ' periodo(s) existente(s) del grupo: los conceptos se contarian DOS veces.';
            $bloqueado = true;
        }

        // Trabajadores activos del grupo.
        $st = $this->pdo->prepare(
            "SELECT id, nombre_completo, identificacion, rol_laboral, estado
             FROM trabajadores
             WHERE hotel_id = ? AND grupo_nomina_id = ? AND estado = 'activo'
             ORDER BY nombre_completo ASC
             LIMIT 300"
        );
        $st->execute([$hotelId, $grupoId]);
        $trabajadores = $st->fetchAll();

        if ($trabajadores === []) {
            $alertas[] = 'El grupo no tiene trabajadores activos asignados.';
            $bloqueado = true;
        }

        $filas = [];
        $totales = [
            'percepciones' => 0.0, 'deducciones_lineas' => 0.0, 'bruto' => 0.0,
            'deducciones_informativas' => 0.0, 'neto' => 0.0,
        ];

        foreach ($trabajadores as $t) {
            $fila = $this->calcularTrabajador($hotelId, $t, $grupo, $fechaInicio, $fechaFin, $dias, $redondeo, $contextoFiscal);
            $filas[] = $fila;
            $totales['percepciones'] += $fila['percepciones'];
            $totales['deducciones_lineas'] += $fila['deducciones_lineas'];
            $totales['bruto'] += $fila['bruto'];
            $totales['deducciones_informativas'] += $fila['deducciones_informativas'];
            $totales['neto'] += $fila['neto'];
        }

        foreach ($totales as $k => $v) {
            $totales[$k] = round($v, 2);
        }

        return [
            'grupo' => $grupo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'dias' => $dias,
            'redondeo' => $redondeo,
            'modo' => $modo,
            'pais' => $pais,
            'reglas_fiscales' => $reglasFiscales,
            'trabajadores' => $filas,
            'totales' => $totales,
            'alertas' => $alertas,
            'bloqueado' => $bloqueado,
        ];
    }

    /** Sugiere el siguiente rango del grupo segun su periodicidad e historial. */
    public function sugerirRango(int $hotelId, array $grupo): array {
        $st = $this->pdo->prepare(
            "SELECT MAX(fecha_fin) AS ultima FROM trabajador_nomina_periodos
             WHERE hotel_id = ? AND grupo_nomina_id = ? AND estado != 'anulado'"
        );
        $st->execute([$hotelId, (int) $grupo['id']]);
        $ultima = $st->fetch()['ultima'] ?? null;

        $inicio = $ultima ? date('Y-m-d', strtotime($ultima . ' +1 day')) : date('Y-m-d');

        switch ($grupo['periodicidad']) {
            case 'semanal':
                $fin = date('Y-m-d', strtotime($inicio . ' +6 days'));
                break;
            case 'quincenal':
                $dia = (int) date('j', strtotime($inicio));
                if ($dia <= 15) {
                    $fin = date('Y-m-15', strtotime($inicio));
                    if ($fin < $inicio) {
                        $fin = date('Y-m-t', strtotime($inicio));
                    }
                } else {
                    $fin = date('Y-m-t', strtotime($inicio));
                }
                break;
            case 'mensual':
            default:
                $fin = date('Y-m-t', strtotime($inicio));
                break;
        }

        if ($fin < $inicio) {
            $fin = $inicio;
        }

        return ['inicio' => $inicio, 'fin' => $fin];
    }

    public function obtenerGrupo(int $hotelId, int $grupoId): ?array {
        $st = $this->pdo->prepare(
            "SELECT * FROM nomina_grupos WHERE id = ? AND hotel_id = ? AND activo = 1"
        );
        $st->execute([$grupoId, $hotelId]);
        $grupo = $st->fetch();
        return $grupo ?: null;
    }

    /* ------------------------------------------------------------------ */

    private function calcularTrabajador(int $hotelId, array $t, array $grupo, string $inicio, string $fin, int $dias, string $redondeo, ?array $contextoFiscal = null): array {
        $trabajadorId = (int) $t['id'];
        $lineas = [];
        $alertas = [];

        // 1) Sueldo del periodo segun salario vigente y esquema.
        $st = $this->pdo->prepare(
            "SELECT salario, esquema, vigente_desde FROM trabajador_salarios
             WHERE hotel_id = ? AND trabajador_id = ?
               AND vigente_desde <= ?
               AND (vigente_hasta IS NULL OR vigente_hasta >= ?)
             ORDER BY vigente_desde DESC, id DESC LIMIT 1"
        );
        $st->execute([$hotelId, $trabajadorId, $fin, $fin]);
        $vigencia = $st->fetch();

        if ($vigencia) {
            $sueldo = $this->sueldoDelPeriodo((float) $vigencia['salario'], (string) $vigencia['esquema'], (string) $grupo['periodicidad'], $dias, $redondeo);
            if ($sueldo['monto'] > 0) {
                $lineas[] = [
                    'concepto_id' => null,
                    'concepto_nombre' => 'Sueldo base (' . $vigencia['esquema'] . ')',
                    'tipo' => 'percepcion',
                    'clasificacion' => 'sueldo',
                    'origen' => 'salario',
                    'cantidad' => null,
                    'base' => (float) $vigencia['salario'],
                    'monto' => $sueldo['monto'],
                    'referencia' => 'SAL-' . $vigencia['vigente_desde'],
                ];
            }
            if ($sueldo['alerta'] !== null) {
                $alertas[] = $sueldo['alerta'];
            }
        } else {
            $alertas[] = 'Sin salario vigente registrado.';
        }

        // 2) Incidencias aprobadas del rango.
        $st = $this->pdo->prepare(
            "SELECT i.id, i.fecha, i.cantidad, i.monto, i.descripcion,
                    c.id AS concepto_id, c.nombre, c.tipo, c.clasificacion, c.modo_calculo, c.monto_default, c.gravable_isr
             FROM nomina_incidencias i
             INNER JOIN nomina_conceptos c ON c.id = i.concepto_id
             WHERE i.hotel_id = ? AND i.trabajador_id = ? AND i.estado = 'aprobada'
               AND i.fecha BETWEEN ? AND ?
             ORDER BY i.fecha ASC, i.id ASC"
        );
        $st->execute([$hotelId, $trabajadorId, $inicio, $fin]);
        foreach ($st->fetchAll() as $inc) {
            $monto = $this->montoIncidencia($inc);
            $lineas[] = [
                'concepto_id' => (int) $inc['concepto_id'],
                'concepto_nombre' => $inc['nombre'],
                'tipo' => $inc['tipo'],
                'clasificacion' => $inc['clasificacion'],
                'origen' => 'incidencia',
                'cantidad' => $inc['cantidad'] !== null ? (float) $inc['cantidad'] : null,
                'base' => $inc['monto_default'] !== null ? (float) $inc['monto_default'] : null,
                'monto' => $this->redondear($monto, $redondeo),
                'referencia' => 'INC-' . (int) $inc['id'],
                'gravable_isr' => ((int) ($inc['gravable_isr'] ?? 0)) === 1,
            ];
        }

        // 3) Ledger v1 con solape (compatibilidad; excluye creditos NOMV2-*).
        $st = $this->pdo->prepare(
            "SELECT id, tipo, efecto, monto, concepto, fecha
             FROM trabajador_pagos
             WHERE hotel_id = ? AND trabajador_id = ? AND estado = 'activo'
               AND (referencia IS NULL OR referencia NOT LIKE 'NOMV2-%')
               AND COALESCE(periodo_fin, fecha) >= ? AND COALESCE(periodo_inicio, fecha) <= ?
             ORDER BY fecha ASC, id ASC"
        );
        $st->execute([$hotelId, $trabajadorId, $inicio, $fin]);
        foreach ($st->fetchAll() as $lp) {
            $lineas[] = [
                'concepto_id' => null,
                'concepto_nombre' => mb_substr('Ledger: ' . ($lp['concepto'] ?: $lp['tipo']), 0, 120),
                'tipo' => $lp['efecto'] === 'a_favor' ? 'percepcion' : 'deduccion',
                'clasificacion' => in_array($lp['tipo'], ['bono', 'comision', 'descuento', 'ajuste'], true) ? $lp['tipo'] : 'otro',
                'origen' => 'ledger',
                'cantidad' => null,
                'base' => null,
                'monto' => round((float) $lp['monto'], 2),
                'referencia' => 'LED-' . (int) $lp['id'],
            ];
        }

        // 3b) Modo legal: lineas fiscales (ISR retenido, IMSS obrero).
        if ($contextoFiscal !== null) {
            $baseGravable = 0.0;
            $hayLedger = false;
            foreach ($lineas as $l) {
                if ($l['origen'] === 'ledger') {
                    $hayLedger = true;
                    continue;
                }
                if ($l['tipo'] !== 'percepcion') {
                    continue;
                }
                // El sueldo siempre grava; las incidencias segun su concepto.
                if ($l['origen'] === 'salario' || !empty($l['gravable_isr'])) {
                    $baseGravable += $l['monto'];
                }
            }
            if ($hayLedger) {
                $alertas[] = 'Modo legal: las lineas del ledger v1 NO gravan ISR en esta version.';
            }

            $salarioDiario = 0.0;
            if ($vigencia) {
                $esquemaVig = (string) $vigencia['esquema'];
                if ($esquemaVig === 'diario') {
                    $salarioDiario = (float) $vigencia['salario'];
                } elseif (isset($this->diasBase[$esquemaVig])) {
                    $salarioDiario = (float) $vigencia['salario'] / $this->diasBase[$esquemaVig];
                } else {
                    $alertas[] = 'Modo legal: esquema ' . $esquemaVig . ' sin salario diario derivable; IMSS omitido.';
                }
            }

            $fiscal = $contextoFiscal['servicio']->lineasFiscales(
                $baseGravable,
                $salarioDiario,
                $dias,
                (string) $grupo['periodicidad'],
                $contextoFiscal['reglas']
            );
            foreach ($fiscal['lineas'] as $lf) {
                $lineas[] = $lf;
            }
            $alertas = array_merge($alertas, $fiscal['alertas']);
        }

        // 4) Deducciones informativas: saldos vivos de anticipos y prestamos.
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) AS n, COALESCE(SUM(saldo_pendiente), 0) AS saldo
             FROM trabajador_anticipos WHERE hotel_id = ? AND trabajador_id = ? AND estado = 'pendiente'"
        );
        $st->execute([$hotelId, $trabajadorId]);
        $anticipos = $st->fetch();

        $st = $this->pdo->prepare(
            "SELECT COUNT(*) AS n, COALESCE(SUM(saldo_pendiente), 0) AS saldo
             FROM trabajador_prestamos WHERE hotel_id = ? AND trabajador_id = ? AND estado = 'vigente'"
        );
        $st->execute([$hotelId, $trabajadorId]);
        $prestamos = $st->fetch();

        $percepciones = 0.0;
        $deduccionesLineas = 0.0;
        foreach ($lineas as $l) {
            if ($l['tipo'] === 'percepcion') {
                $percepciones += $l['monto'];
            } else {
                $deduccionesLineas += $l['monto'];
            }
        }

        $bruto = max(0.0, round($percepciones - $deduccionesLineas, 2));
        if ($percepciones - $deduccionesLineas < 0) {
            $alertas[] = 'Las deducciones superan las percepciones: el bruto se ajusto a cero.';
        }

        $dedInformativas = round((float) ($anticipos['saldo'] ?? 0) + (float) ($prestamos['saldo'] ?? 0), 2);
        $neto = max(0.0, round($bruto - $dedInformativas, 2));

        $estadoPreview = 'por_pagar';
        if ($lineas === [] || ($percepciones <= 0 && $deduccionesLineas <= 0)) {
            $estadoPreview = 'sin_movimientos';
        } elseif ($neto <= 0) {
            $estadoPreview = 'sin_saldo';
        }

        return [
            'trabajador' => $t,
            'lineas' => $lineas,
            'percepciones' => round($percepciones, 2),
            'deducciones_lineas' => round($deduccionesLineas, 2),
            'bruto' => $bruto,
            'anticipos_count' => (int) ($anticipos['n'] ?? 0),
            'anticipos_saldo' => round((float) ($anticipos['saldo'] ?? 0), 2),
            'prestamos_count' => (int) ($prestamos['n'] ?? 0),
            'prestamos_saldo' => round((float) ($prestamos['saldo'] ?? 0), 2),
            'deducciones_informativas' => $dedInformativas,
            'neto' => $neto,
            'estado_preview' => $estadoPreview,
            'alertas' => $alertas,
            'salario_vigente' => $vigencia ?: null,
        ];
    }

    /**
     * Sueldo del periodo segun esquema del salario y periodicidad del grupo.
     * - esquema == periodicidad: salario completo (sin drift de redondeo).
     * - diario: salario x dias del periodo.
     * - por_hora / por_evento: 0 (el pago sale de incidencias), con alerta.
     * - resto: prorrateo diario (salario / dias_base_del_esquema x dias).
     */
    private function sueldoDelPeriodo(float $salario, string $esquema, string $periodicidad, int $dias, string $redondeo): array {
        if ($esquema === $periodicidad) {
            return ['monto' => $this->redondear($salario, $redondeo), 'alerta' => null];
        }
        if ($esquema === 'diario') {
            return ['monto' => $this->redondear($salario * $dias, $redondeo), 'alerta' => null];
        }
        if ($esquema === 'por_hora' || $esquema === 'por_evento') {
            return ['monto' => 0.0, 'alerta' => 'Esquema ' . $esquema . ': el pago debe capturarse via incidencias.'];
        }

        $base = $this->diasBase[$esquema] ?? null;
        if ($base === null) {
            return ['monto' => 0.0, 'alerta' => 'Esquema de salario no reconocido: ' . $esquema . '.'];
        }

        $monto = $this->redondear(($salario / $base) * $dias, $redondeo);
        return [
            'monto' => $monto,
            'alerta' => 'Prorrateo: salario ' . $esquema . ' en grupo ' . $periodicidad . ' (' . $dias . ' dias a razon diaria).',
        ];
    }

    private function montoIncidencia(array $inc): float {
        if ($inc['modo_calculo'] === 'por_cantidad') {
            $unitario = $inc['monto'] !== null ? (float) $inc['monto'] : (float) ($inc['monto_default'] ?? 0);
            return max(0.0, $unitario * (float) ($inc['cantidad'] ?? 1));
        }
        if ($inc['monto'] !== null) {
            return max(0.0, (float) $inc['monto']);
        }
        return max(0.0, (float) ($inc['monto_default'] ?? 0));
    }

    private function redondear(float $monto, string $redondeo): float {
        return $redondeo === 'pesos' ? round($monto, 0) : round($monto, 2);
    }

    private function assertFecha(string $valor): void {
        $fecha = DateTime::createFromFormat('Y-m-d', $valor);
        if (!$fecha || $fecha->format('Y-m-d') !== $valor) {
            throw new Exception('Fecha no valida (formato requerido: AAAA-MM-DD).');
        }
    }
}
