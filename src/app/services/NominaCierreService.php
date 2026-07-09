<?php
/**
 * Cierre de periodos de nomina v2 (Fase 3 nomina core).
 *
 * Escribe el snapshot en las MISMAS tablas de la pre-nomina existente
 * (trabajador_nomina_periodos/_detalles/_eventos) con motor='v2' y
 * grupo_nomina_id, mas las lineas normalizadas en nomina_periodo_conceptos.
 *
 * Al APROBAR (no al cerrar), acredita en el ledger laboral (trabajador_pagos,
 * referencia 'NOMV2-{periodo}-{trabajador}') el BRUTO del snapshot MENOS el
 * neto de las lineas ledger v1 absorbidas: esas filas (bonos/comisiones)
 * siguen activas y contando en el saldo del riel libre, asi que acreditar el
 * neto completo duplicaria el saldo pagable. Con este resto, el saldo
 * disponible del trabajador queda exactamente en el neto del periodo (los
 * anticipos/prestamos, ya restados del neto sugerido, los resta el propio
 * riel de pago). Emitir el credito hasta la aprobacion garantiza que NINGUN
 * riel de pago (ni el libre) pueda pagar un periodo sin aprobar. El motor v2
 * excluye esas referencias de calculos futuros (sin doble conteo).
 *
 * Anulacion v2: exige motivo, bloquea si hay pagos snapshot vigentes y anula
 * los creditos NOMV2 del ledger en la misma transaccion. La reapertura
 * (aprobado -> cerrado) tambien anula los creditos: sin aprobacion no hay
 * saldo pagable. Ambas verifican ademas que los creditos NOMV2 sigan SIN
 * consumir por pagos de Caja (incluido el riel libre del perfil, que no viaja
 * con nomina_periodo_id): si un pago ya salio respaldado por el credito, hay
 * que revertirlo antes de anular/reabrir.
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';
require_once __DIR__ . '/NominaCalculoService.php';

class NominaCierreService {

    private $db;
    private $pdo;
    private $calculo;

    public function __construct(?Database $db = null) {
        $this->db = $db ?: Database::getInstance();
        $this->pdo = $this->db->getConnection();
        $this->calculo = new NominaCalculoService($this->db);
    }

    /**
     * Cierra el periodo del grupo: snapshot inmutable + lineas + credito ledger.
     * Devuelve el id del periodo creado.
     */
    public function cerrar(int $hotelId, int $grupoId, string $fechaInicio, string $fechaFin, ?int $usuarioId = null): int {
        $preview = $this->calculo->preview($hotelId, $grupoId, $fechaInicio, $fechaFin);

        if (!empty($preview['bloqueado'])) {
            throw new Exception('El periodo esta bloqueado: ' . implode(' ', $preview['alertas']));
        }
        if ($preview['trabajadores'] === []) {
            throw new Exception('El grupo no tiene trabajadores activos: no hay nada que cerrar.');
        }

        $grupo = $preview['grupo'];
        $etiqueta = mb_substr($grupo['nombre'] . ' ' . $fechaInicio . ' a ' . $fechaFin, 0, 120);

        $reglasSnapshot = [
            'motor' => 'v2',
            'modo' => $preview['modo'] ?? 'simplificada',
            'reglas_legales_aplicadas' => $preview['reglas_fiscales'] ?? null,
            'grupo' => ['id' => (int) $grupo['id'], 'nombre' => $grupo['nombre'], 'periodicidad' => $grupo['periodicidad']],
            'configuracion' => [
                'modo' => (string) ConfiguracionHotelRegistry::get('nomina.modo', 'simplificada', $hotelId),
                'pais' => (string) ConfiguracionHotelRegistry::get('nomina.pais', 'MX', $hotelId),
                'redondeo' => $preview['redondeo'],
                'permitir_horas_extra' => ConfiguracionHotelRegistry::getBool('nomina.permitir_horas_extra', true, $hotelId),
                'permitir_descuentos_manuales' => ConfiguracionHotelRegistry::getBool('nomina.permitir_descuentos_manuales', true, $hotelId),
                'requiere_aprobacion_cierre' => ConfiguracionHotelRegistry::getBool('nomina.requiere_aprobacion_cierre', true, $hotelId),
                'permitir_reapertura' => ConfiguracionHotelRegistry::getBool('nomina.permitir_reapertura', false, $hotelId),
            ],
            'cerrado_at' => date('Y-m-d H:i:s'),
        ];

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            // Cabecera del periodo (motor v2).
            $st = $this->pdo->prepare(
                "INSERT INTO trabajador_nomina_periodos
                    (hotel_id, tipo_periodo, grupo_nomina_id, motor, etiqueta, fecha_inicio, fecha_fin,
                     estado, filtros_json, resumen_json, reglas_snapshot_json,
                     trabajadores_total, bruto_total, deducciones_total,
                     pagos_caja_aplicados_total, reversiones_detectadas_total,
                     neto_sugerido_total, pendiente_pago_total,
                     cerrado_por, cerrado_at)
                 VALUES (?, ?, ?, 'v2', ?, ?, ?, 'cerrado', ?, ?, ?, ?, ?, ?, 0.00, 0.00, ?, ?, ?, NOW())"
            );
            $st->execute([
                $hotelId,
                $grupo['periodicidad'],
                (int) $grupo['id'],
                $etiqueta,
                $fechaInicio,
                $fechaFin,
                json_encode(['motor' => 'v2', 'grupo_id' => (int) $grupo['id']], JSON_UNESCAPED_UNICODE),
                json_encode(['totales' => $preview['totales'], 'alertas' => $preview['alertas'], 'dias' => $preview['dias']], JSON_UNESCAPED_UNICODE),
                json_encode($reglasSnapshot, JSON_UNESCAPED_UNICODE),
                count($preview['trabajadores']),
                number_format($preview['totales']['bruto'], 2, '.', ''),
                number_format($preview['totales']['deducciones_informativas'], 2, '.', ''),
                number_format($preview['totales']['neto'], 2, '.', ''),
                number_format($preview['totales']['neto'], 2, '.', ''),
                $usuarioId,
            ]);

            $periodoId = (int) $this->pdo->lastInsertId();

            $stDetalle = $this->pdo->prepare(
                "INSERT INTO trabajador_nomina_periodo_detalles
                    (periodo_id, hotel_id, trabajador_id, trabajador_nombre, trabajador_identificacion,
                     trabajador_rol, trabajador_estado, estado_preview_nomina, motivo_bloqueo_nomina,
                     conceptos_count, conceptos_a_favor, conceptos_en_contra, bruto_periodo,
                     anticipos_count, anticipos_saldo, prestamos_count, prestamos_saldo,
                     deducciones_informativas, neto_sugerido, pendiente_pago_sugerido, snapshot_json)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            $stLinea = $this->pdo->prepare(
                "INSERT INTO nomina_periodo_conceptos
                    (hotel_id, periodo_id, detalle_id, trabajador_id, concepto_id, concepto_nombre,
                     tipo, clasificacion, origen, cantidad, base, monto, referencia)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );

            foreach ($preview['trabajadores'] as $fila) {
                $t = $fila['trabajador'];

                $stDetalle->execute([
                    $periodoId,
                    $hotelId,
                    (int) $t['id'],
                    mb_substr((string) $t['nombre_completo'], 0, 160),
                    $t['identificacion'] !== null ? mb_substr((string) $t['identificacion'], 0, 80) : null,
                    $t['rol_laboral'] !== null ? mb_substr((string) $t['rol_laboral'], 0, 100) : null,
                    (string) $t['estado'],
                    mb_substr((string) $fila['estado_preview'], 0, 40),
                    $fila['alertas'] !== [] ? mb_substr(implode(' | ', $fila['alertas']), 0, 255) : null,
                    count($fila['lineas']),
                    number_format($fila['percepciones'], 2, '.', ''),
                    number_format($fila['deducciones_lineas'], 2, '.', ''),
                    number_format($fila['bruto'], 2, '.', ''),
                    $fila['anticipos_count'],
                    number_format($fila['anticipos_saldo'], 2, '.', ''),
                    $fila['prestamos_count'],
                    number_format($fila['prestamos_saldo'], 2, '.', ''),
                    number_format($fila['deducciones_informativas'], 2, '.', ''),
                    number_format($fila['neto'], 2, '.', ''),
                    number_format($fila['neto'], 2, '.', ''),
                    json_encode([
                        'lineas' => $fila['lineas'],
                        'salario_vigente' => $fila['salario_vigente'],
                        'alertas' => $fila['alertas'],
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                $detalleId = (int) $this->pdo->lastInsertId();

                foreach ($fila['lineas'] as $linea) {
                    $stLinea->execute([
                        $hotelId,
                        $periodoId,
                        $detalleId,
                        (int) $t['id'],
                        $linea['concepto_id'],
                        mb_substr((string) $linea['concepto_nombre'], 0, 120),
                        $linea['tipo'],
                        mb_substr((string) $linea['clasificacion'], 0, 30),
                        $linea['origen'],
                        $linea['cantidad'] !== null ? number_format((float) $linea['cantidad'], 2, '.', '') : null,
                        $linea['base'] !== null ? number_format((float) $linea['base'], 2, '.', '') : null,
                        number_format((float) $linea['monto'], 2, '.', ''),
                        $linea['referencia'] !== null ? mb_substr((string) $linea['referencia'], 0, 150) : null,
                    ]);
                }

                // NOTA: el credito NOMV2 del ledger se emite al APROBAR, no aqui:
                // un periodo cerrado sin aprobar no debe ser pagable por ningun riel.
            }

            $this->registrarEvento($periodoId, $hotelId, 'cierre', 'cerrado',
                'Cierre de periodo v2 del grupo ' . $grupo['nombre'], null, $usuarioId);

            AuditService::record('nomina.periodo_v2_cerrado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'trabajador_nomina_periodos',
                'entidad_id' => (string) $periodoId,
                'descripcion' => 'Cerro periodo v2 ' . $etiqueta . ' (' . count($preview['trabajadores']) . ' trabajadores)',
                'datos_despues' => $preview['totales'],
            ]);

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return $periodoId;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    /** Aprueba un periodo v2 cerrado (segundo paso antes de pagar). */
    public function aprobar(int $hotelId, int $periodoId, ?int $usuarioId = null): bool {
        $periodo = $this->obtenerPeriodoV2($hotelId, $periodoId);
        if ($periodo['estado'] !== 'cerrado') {
            throw new Exception('Solo un periodo cerrado puede aprobarse (estado actual: ' . $periodo['estado'] . ').');
        }

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            $st = $this->pdo->prepare(
                "UPDATE trabajador_nomina_periodos
                 SET estado = 'aprobado', aprobado_por = ?, aprobado_at = NOW()
                 WHERE id = ? AND hotel_id = ? AND estado = 'cerrado' AND motor = 'v2'"
            );
            $st->execute([$usuarioId, $periodoId, $hotelId]);
            if ($st->rowCount() !== 1) {
                throw new Exception('No se pudo aprobar el periodo (estado cambiado por otro usuario).');
            }

            // Emitir el credito del periodo en el ledger: desde este momento (y solo
            // desde este momento) el periodo es pagable por los rieles de Caja.
            // El credito es el BRUTO del snapshot MENOS el neto de las lineas
            // ledger v1 absorbidas: esas filas siguen activas y contando en el
            // saldo del riel libre, asi que acreditar el neto completo las
            // duplicaria. Si el ledger absorbido supera al bruto (p.ej. bono v1
            // neutralizado por deducciones del snapshot), se emite el ajuste
            // en_contra para que el saldo pagable quede exactamente en el neto.
            $st = $this->pdo->prepare(
                "SELECT trabajador_id, bruto_periodo FROM trabajador_nomina_periodo_detalles
                 WHERE periodo_id = ? AND hotel_id = ?"
            );
            $st->execute([$periodoId, $hotelId]);
            $detalles = $st->fetchAll();

            $st = $this->pdo->prepare(
                "SELECT trabajador_id,
                        COALESCE(SUM(CASE WHEN tipo = 'percepcion' THEN monto ELSE -monto END), 0) AS neto_ledger
                 FROM nomina_periodo_conceptos
                 WHERE periodo_id = ? AND hotel_id = ? AND origen = 'ledger'
                 GROUP BY trabajador_id"
            );
            $st->execute([$periodoId, $hotelId]);
            $ledgerAbsorbido = [];
            foreach ($st->fetchAll() as $row) {
                $ledgerAbsorbido[(int) $row['trabajador_id']] = (float) $row['neto_ledger'];
            }

            $stLedger = $this->pdo->prepare(
                "INSERT INTO trabajador_pagos
                    (hotel_id, trabajador_id, tipo, efecto, monto, concepto,
                     periodo_inicio, periodo_fin, fecha, referencia, estado, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'activo', ?)"
            );

            $creditosEmitidos = 0;
            foreach ($detalles as $d) {
                $trabajadorId = (int) $d['trabajador_id'];
                $credito = round((float) $d['bruto_periodo'] - ($ledgerAbsorbido[$trabajadorId] ?? 0.0), 2);
                if (abs($credito) < 0.005) {
                    continue;
                }

                $stLedger->execute([
                    $hotelId,
                    $trabajadorId,
                    $credito > 0 ? 'pago' : 'ajuste',
                    $credito > 0 ? 'a_favor' : 'en_contra',
                    number_format(abs($credito), 2, '.', ''),
                    mb_substr(($credito > 0 ? 'Nomina aprobada: ' : 'Nomina aprobada (ajuste por conceptos previos): ') . $periodo['etiqueta'], 0, 160),
                    $periodo['fecha_inicio'],
                    $periodo['fecha_fin'],
                    $periodo['fecha_fin'],
                    'NOMV2-' . $periodoId . '-' . $trabajadorId,
                    $usuarioId,
                ]);
                $creditosEmitidos++;
            }

            $this->registrarEvento($periodoId, $hotelId, 'aprobacion', 'aprobado',
                'Aprobacion de periodo v2 (' . $creditosEmitidos . ' creditos de ledger emitidos)', null, $usuarioId);

            AuditService::record('nomina.periodo_v2_aprobado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'trabajador_nomina_periodos',
                'entidad_id' => (string) $periodoId,
                'descripcion' => 'Aprobo periodo v2 ' . $periodo['etiqueta'],
            ]);

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return true;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    /** Anula un periodo v2: motivo obligatorio, sin pagos vigentes, anula creditos NOMV2. */
    public function anular(int $hotelId, int $periodoId, string $motivo, ?int $usuarioId = null): bool {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new Exception('El motivo de anulacion es obligatorio.');
        }
        $motivo = mb_substr($motivo, 0, 255);

        $periodo = $this->obtenerPeriodoV2($hotelId, $periodoId);
        if ($periodo['estado'] === 'anulado') {
            throw new Exception('El periodo ya esta anulado.');
        }

        // Bloqueo: pagos snapshot vigentes ligados al periodo.
        $st = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM trabajador_pagos_caja
             WHERE hotel_id = ? AND nomina_periodo_id = ? AND estado = 'pagado'"
        );
        $st->execute([$hotelId, $periodoId]);
        $pagosVigentes = (int) ($st->fetch()['total'] ?? 0);
        if ($pagosVigentes > 0) {
            throw new Exception('El periodo tiene ' . $pagosVigentes . ' pago(s) de Caja vigentes: revierte los pagos antes de anular.');
        }

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            $st = $this->pdo->prepare(
                "UPDATE trabajador_nomina_periodos
                 SET estado = 'anulado', anulado_por = ?, anulado_at = NOW(), motivo_anulacion = ?,
                     anulacion_uk = id
                 WHERE id = ? AND hotel_id = ? AND estado != 'anulado' AND motor = 'v2'"
            );
            $st->execute([$usuarioId, $motivo, $periodoId, $hotelId]);
            if ($st->rowCount() !== 1) {
                throw new Exception('No se pudo anular el periodo (estado cambiado por otro usuario).');
            }

            // Candado anti doble pago: los pagos del riel libre (perfil del
            // trabajador) no viajan con nomina_periodo_id y son invisibles al
            // bloqueo de arriba. Si ya consumieron el credito aprobado, anular
            // los creditos dejaria ese dinero sin respaldo y el periodo podria
            // re-cerrarse y volver a pagarse.
            $this->verificarCreditosNoConsumidos($hotelId, $periodoId, 'anular');

            // Anular los creditos del ledger emitidos por este cierre.
            $st = $this->pdo->prepare(
                "UPDATE trabajador_pagos SET estado = 'anulado', updated_by = ?
                 WHERE hotel_id = ? AND referencia LIKE ? AND estado = 'activo'"
            );
            $st->execute([$usuarioId, $hotelId, 'NOMV2-' . $periodoId . '-%']);
            $creditosAnulados = $st->rowCount();

            // Cancelar los recibos internos vigentes del periodo.
            require_once __DIR__ . '/NominaReciboService.php';
            (new NominaReciboService($this->db))->cancelarPorPeriodo($hotelId, $periodoId, 'Anulacion del periodo: ' . $motivo, $usuarioId);

            $this->registrarEvento($periodoId, $hotelId, 'anulacion', 'anulado',
                'Anulacion de periodo v2 (' . $creditosAnulados . ' creditos de ledger anulados)', $motivo, $usuarioId);

            AuditService::record('nomina.periodo_v2_anulado', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'trabajador_nomina_periodos',
                'entidad_id' => (string) $periodoId,
                'descripcion' => 'Anulo periodo v2 ' . $periodo['etiqueta'] . ': ' . $motivo,
                'datos_antes' => ['estado' => $periodo['estado']],
                'datos_despues' => ['estado' => 'anulado', 'creditos_ledger_anulados' => $creditosAnulados],
            ]);

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return true;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    /**
     * Reapertura controlada: aprobado -> cerrado. Solo si la configuracion del
     * negocio lo permite; motivo obligatorio; bloqueada con pagos vigentes;
     * cancela los recibos emitidos (el snapshot NO se recalcula).
     */
    public function reabrir(int $hotelId, int $periodoId, string $motivo, ?int $usuarioId = null): bool {
        $motivo = trim($motivo);
        if ($motivo === '') {
            throw new Exception('El motivo de reapertura es obligatorio.');
        }
        $motivo = mb_substr($motivo, 0, 255);

        if (!ConfiguracionHotelRegistry::getBool('nomina.permitir_reapertura', false, $hotelId)) {
            throw new Exception('Este negocio no permite reabrir periodos (configuracion de nomina).');
        }

        $periodo = $this->obtenerPeriodoV2($hotelId, $periodoId);
        if ($periodo['estado'] !== 'aprobado') {
            throw new Exception('Solo un periodo APROBADO puede reabrirse (estado: ' . $periodo['estado'] . ').');
        }

        $st = $this->pdo->prepare(
            "SELECT COUNT(*) AS total FROM trabajador_pagos_caja
             WHERE hotel_id = ? AND nomina_periodo_id = ? AND estado = 'pagado'"
        );
        $st->execute([$hotelId, $periodoId]);
        if ((int) ($st->fetch()['total'] ?? 0) > 0) {
            throw new Exception('El periodo tiene pagos de Caja vigentes: revierte los pagos antes de reabrir.');
        }

        $ownTransaction = !$this->db->enTransaccion();

        try {
            if ($ownTransaction) {
                $this->db->safeBeginTransaction();
            }

            $st = $this->pdo->prepare(
                "UPDATE trabajador_nomina_periodos
                 SET estado = 'cerrado', aprobado_por = NULL, aprobado_at = NULL
                 WHERE id = ? AND hotel_id = ? AND estado = 'aprobado' AND motor = 'v2'"
            );
            $st->execute([$periodoId, $hotelId]);
            if ($st->rowCount() !== 1) {
                throw new Exception('No se pudo reabrir el periodo (estado cambiado por otro usuario).');
            }

            // Candado anti doble pago: pagos del riel libre (sin
            // nomina_periodo_id) que ya consumieron el credito aprobado deben
            // revertirse antes de reabrir; si no, el periodo re-cerrado con
            // otras fechas volveria a ser pagable y el dinero saldria dos veces.
            $this->verificarCreditosNoConsumidos($hotelId, $periodoId, 'reabrir');

            // Sin aprobacion no hay saldo pagable: anular los creditos NOMV2.
            $st = $this->pdo->prepare(
                "UPDATE trabajador_pagos SET estado = 'anulado', updated_by = ?
                 WHERE hotel_id = ? AND referencia LIKE ? AND estado = 'activo'"
            );
            $st->execute([$usuarioId, $hotelId, 'NOMV2-' . $periodoId . '-%']);
            $creditosAnulados = $st->rowCount();

            require_once __DIR__ . '/NominaReciboService.php';
            $recibosCancelados = (new NominaReciboService($this->db))
                ->cancelarPorPeriodo($hotelId, $periodoId, 'Reapertura del periodo: ' . $motivo, $usuarioId);

            $this->registrarEvento($periodoId, $hotelId, 'reapertura', 'cerrado',
                'Reapertura de periodo v2 (' . $creditosAnulados . ' creditos anulados, ' . $recibosCancelados . ' recibos cancelados)', $motivo, $usuarioId);

            AuditService::record('nomina.periodo_v2_reabierto', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'trabajador_nomina_periodos',
                'entidad_id' => (string) $periodoId,
                'descripcion' => 'Reabrio periodo v2 ' . $periodo['etiqueta'] . ': ' . $motivo,
                'datos_antes' => ['estado' => 'aprobado', 'aprobado_por' => $periodo['aprobado_por']],
                'datos_despues' => ['estado' => 'cerrado', 'recibos_cancelados' => $recibosCancelados],
            ]);

            if ($ownTransaction) {
                $this->db->safeCommit();
            }

            return true;
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $this->db->safeRollBack();
            }
            throw $e;
        }
    }

    /* ------------------------------------------------------------------ */

    /**
     * Candado anti doble pago para anular/reabrir: los creditos NOMV2 activos
     * del periodo solo pueden retirarse si siguen SIN consumir. Los pagos del
     * riel libre no viajan con nomina_periodo_id, asi que el consumo se
     * verifica con el invariante del pool laboral por trabajador:
     * (conceptos activos netos - pagos de Caja vigentes) >= credito a retirar.
     * Si no alcanza, algun pago ya salio respaldado por este credito y hay que
     * revertirlo primero. Debe llamarse DENTRO de la transaccion: bloquea las
     * mismas filas laborales que el riel de pago para serializar contra un
     * pago concurrente.
     */
    private function verificarCreditosNoConsumidos(int $hotelId, int $periodoId, string $accion): void {
        $st = $this->pdo->prepare(
            "SELECT trabajador_id,
                    SUM(CASE WHEN efecto = 'a_favor' THEN monto ELSE -monto END) AS credito
             FROM trabajador_pagos
             WHERE hotel_id = ? AND referencia LIKE ? AND estado = 'activo'
             GROUP BY trabajador_id"
        );
        $st->execute([$hotelId, 'NOMV2-' . $periodoId . '-%']);
        $creditos = $st->fetchAll();
        if (!$creditos) {
            return;
        }

        $stLockPagos = $this->pdo->prepare(
            'SELECT id FROM trabajador_pagos WHERE hotel_id = ? AND trabajador_id = ? FOR UPDATE'
        );
        $stLockCaja = $this->pdo->prepare(
            'SELECT id FROM trabajador_pagos_caja WHERE hotel_id = ? AND trabajador_id = ? FOR UPDATE'
        );
        $stConceptos = $this->pdo->prepare(
            "SELECT COALESCE(SUM(CASE WHEN efecto = 'a_favor' THEN monto ELSE -monto END), 0)
             FROM trabajador_pagos
             WHERE hotel_id = ? AND trabajador_id = ? AND estado = 'activo'"
        );
        $stPagos = $this->pdo->prepare(
            "SELECT COALESCE(SUM(monto), 0)
             FROM trabajador_pagos_caja
             WHERE hotel_id = ? AND trabajador_id = ? AND estado = 'pagado'"
        );

        foreach ($creditos as $c) {
            $credito = (float) $c['credito'];
            if ($credito <= 0.004) {
                continue;
            }
            $trabajadorId = (int) $c['trabajador_id'];

            $stLockPagos->execute([$hotelId, $trabajadorId]);
            $stLockPagos->fetchAll();
            $stLockCaja->execute([$hotelId, $trabajadorId]);
            $stLockCaja->fetchAll();

            $stConceptos->execute([$hotelId, $trabajadorId]);
            $conceptos = (float) $stConceptos->fetchColumn();
            $stPagos->execute([$hotelId, $trabajadorId]);
            $pagos = (float) $stPagos->fetchColumn();

            if ($conceptos - $pagos < $credito - 0.004) {
                throw new Exception(
                    'El trabajador #' . $trabajadorId . ' tiene pagos de Caja que ya consumieron el neto aprobado de este periodo'
                    . ' (incluye pagos hechos desde su perfil): revierte esos pagos antes de ' . $accion . '.'
                );
            }
        }
    }

    private function obtenerPeriodoV2(int $hotelId, int $periodoId): array {
        $st = $this->pdo->prepare(
            "SELECT * FROM trabajador_nomina_periodos WHERE id = ? AND hotel_id = ? AND motor = 'v2'"
        );
        $st->execute([$periodoId, $hotelId]);
        $periodo = $st->fetch();
        if (!$periodo) {
            throw new Exception('El periodo v2 no existe en este negocio.');
        }
        return $periodo;
    }

    private function registrarEvento(int $periodoId, int $hotelId, string $tipo, string $estadoResultante, string $descripcion, ?string $motivo, ?int $usuarioId): void {
        $st = $this->pdo->prepare(
            "INSERT INTO trabajador_nomina_periodo_eventos
                (periodo_id, hotel_id, tipo, estado_resultante, descripcion, motivo, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $st->execute([$periodoId, $hotelId, $tipo, $estadoResultante, mb_substr($descripcion, 0, 180), $motivo, $usuarioId]);
    }
}
