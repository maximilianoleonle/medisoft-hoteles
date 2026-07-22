<?php
/**
 * TarifaImpactoService — Recalculo opcional de reservaciones existentes al
 * crear/editar un incremento de tarifa (clase 'incremento').
 *
 * Contexto: el precio de una reservacion queda CONGELADO al crearla
 * (reservacion_habitaciones.precio = estancia completa por habitacion;
 * reservaciones.precio_total = suma de no-cortesias - descuento_total).
 * Este servicio permite, con confirmacion humana, re-derivar esos precios
 * del motor de tarifas vigente (IncrementoTarifa::calcularPrecioConIncremento,
 * noche por noche, desde el precio_base actual de cada habitacion).
 *
 * Reglas:
 * - Solo reservaciones 'confirmada'/'checked_in' con noches por venir
 *   (jamas canceladas/checked_out ni estancias ya concluidas).
 * - Candidatas = las que tienen >=1 habitacion cobrable dentro del alcance
 *   de la tarifa (global / tipo_habitacion / habitacion) y cuya estancia se
 *   solapa con la vigencia.
 * - Cortesias (es_cortesia=1) NUNCA se tocan (siguen en 0).
 * - descuento_total se CONSERVA tal cual (solo se topa al nuevo subtotal
 *   para que precio_total no quede negativo). No se recalculan descuentos.
 * - Guard "detalle desincronizado" (caso prod #1867, 2026-07-22): antes de
 *   aplicar se valida si el precio_total actual cuadra con su detalle por
 *   habitacion; si no cuadra se avisa en la previsualizacion (al aplicar,
 *   header y detalle quedan sincronizados porque ambos se re-derivan del
 *   motor de noches, nunca del detalle viejo).
 * - aplicar() corre TODO en una transaccion, con SELECT ... FOR UPDATE por
 *   reservacion (orden ascendente por id, disciplina de locks del repo) y
 *   re-verificacion de estado: una reservacion cancelada/cerrada entre la
 *   previsualizacion y la confirmacion se omite, no truena.
 */

require_once __DIR__ . '/../models/IncrementoTarifa.php';
require_once __DIR__ . '/../models/Reservacion.php';

class TarifaImpactoService {

    const ESTADOS_RECALCULABLES = ['confirmada', 'checked_in'];
    const TOLERANCIA = 0.01;

    /** @var Database */
    private $db;
    /** @var IncrementoTarifa */
    private $tarifaModel;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->tarifaModel = new IncrementoTarifa();
    }

    /**
     * Previsualiza el impacto de la tarifa sobre reservaciones existentes.
     * No escribe nada.
     *
     * @return array{tarifa: array, aplicable: bool, motivo: ?string, hoy: string,
     *               afectadas: array, omitidas: array}
     */
    public function previsualizar(int $tarifaId, int $hotelId): array {
        $tarifa = $this->cargarTarifa($tarifaId, $hotelId);
        $hoy = date('Y-m-d'); // reloj de la app, igual que el motor de tarifas

        $resultado = [
            'tarifa' => $tarifa,
            'aplicable' => true,
            'motivo' => null,
            'hoy' => $hoy,
            'afectadas' => [],
            'omitidas' => [],
        ];

        $noAplicable = $this->motivoNoAplicable($tarifa);
        if ($noAplicable !== null) {
            $resultado['aplicable'] = false;
            $resultado['motivo'] = $noAplicable;
            return $resultado;
        }

        $candidatas = $this->candidatas($tarifa, $hotelId, $hoy);
        if (empty($candidatas)) {
            return $resultado;
        }

        $detalles = $this->detallePorReservacion(array_column($candidatas, 'id'), $hotelId);

        foreach ($candidatas as $reservacion) {
            $calculo = $this->calcularReservacion($reservacion, $detalles[(int)$reservacion['id']] ?? [], $hotelId);

            if (!$calculo['recalculable']) {
                $resultado['omitidas'][] = [
                    'id' => (int)$reservacion['id'],
                    'huesped' => $reservacion['huesped'] ?? null,
                    'motivo' => $calculo['motivo'],
                ];
                continue;
            }

            if ($calculo['cambia']) {
                $resultado['afectadas'][] = $calculo;
            }
        }

        // Pagos ya registrados: mostrar saldo resultante (fuente: resumenPagosLote,
        // mismo criterio que el resto del dinero de reservaciones).
        if (!empty($resultado['afectadas'])) {
            $reservacionModel = new Reservacion();
            $resumenes = $reservacionModel->resumenPagosLote(array_column($resultado['afectadas'], 'id'), $hotelId);

            foreach ($resultado['afectadas'] as $i => $af) {
                $resumen = $resumenes[$af['id']] ?? null;
                $pagado = $resumen ? (float)$resumen['pagado'] : 0.0;
                $resultado['afectadas'][$i]['pagado'] = round($pagado, 2);
                $resultado['afectadas'][$i]['saldo_actual'] = $resumen ? (float)$resumen['saldo'] : max(0, round($af['precio_actual'] - $pagado, 2));
                $resultado['afectadas'][$i]['saldo_nuevo'] = max(0, round($af['precio_nuevo'] - $pagado, 2));
                $resultado['afectadas'][$i]['sobrepago'] = $pagado > $af['precio_nuevo'] + self::TOLERANCIA;
            }
        }

        return $resultado;
    }

    /**
     * Aplica el recalculo a las reservaciones seleccionadas (interseccion con
     * las candidatas reales del servidor: jamas se confia en montos ni ids
     * arbitrarios del cliente). Todo en UNA transaccion.
     *
     * @param int[] $reservacionIds ids seleccionados por el operador
     * @return array{actualizadas: array, omitidas: array, delta_total: float}
     */
    public function aplicar(int $tarifaId, array $reservacionIds, int $hotelId): array {
        $tarifa = $this->cargarTarifa($tarifaId, $hotelId);

        $noAplicable = $this->motivoNoAplicable($tarifa);
        if ($noAplicable !== null) {
            throw new Exception($noAplicable);
        }

        $hoy = date('Y-m-d');
        $candidatas = $this->candidatas($tarifa, $hotelId, $hoy);
        $candidatasPorId = [];
        foreach ($candidatas as $c) {
            $candidatasPorId[(int)$c['id']] = $c;
        }

        // Interseccion seleccion ∩ candidatas, orden ascendente (disciplina de locks).
        // Lo seleccionado que ya no es candidata (cancelada, cerrada o fuera de
        // alcance entre previsualizar y confirmar) se reporta como omitido.
        $actualizadas = [];
        $omitidas = [];
        $deltaTotal = 0.0;

        $seleccion = [];
        foreach ($reservacionIds as $rid) {
            $rid = (int)$rid;
            if ($rid <= 0 || isset($seleccion[$rid])) {
                continue;
            }
            if (isset($candidatasPorId[$rid])) {
                $seleccion[$rid] = $rid;
            } else {
                $omitidas[] = ['id' => $rid, 'motivo' => 'Ya no es candidata (cambio de estado, fechas o alcance); no se toco'];
            }
        }
        sort($seleccion);

        if (empty($seleccion)) {
            return ['actualizadas' => [], 'omitidas' => $omitidas, 'delta_total' => 0.0];
        }

        $pdo = $this->db->getConnection();
        $enTransaccion = $pdo->inTransaction();
        if (!$enTransaccion) {
            $pdo->beginTransaction();
        }

        try {
            $stmtLock = $pdo->prepare(
                "SELECT id, huesped_id, fecha_entrada, fecha_salida, estado, precio_total, descuento_total
                 FROM reservaciones
                 WHERE id = ? AND hotel_id = ?
                 FOR UPDATE"
            );
            $stmtUpdHab = $pdo->prepare(
                "UPDATE reservacion_habitaciones
                 SET precio = ?
                 WHERE id = ? AND hotel_id = ? AND reservacion_id = ?"
            );
            $stmtUpdRes = $pdo->prepare(
                "UPDATE reservaciones
                 SET precio_total = ?, updated_at = NOW()
                 WHERE id = ? AND hotel_id = ?"
            );

            foreach ($seleccion as $rid) {
                // Lock + re-verificacion de estado: pudo cambiar entre previsualizar y confirmar.
                $stmtLock->execute([$rid, $hotelId]);
                $reservacion = $stmtLock->fetch(PDO::FETCH_ASSOC);

                if (!$reservacion || !in_array($reservacion['estado'], self::ESTADOS_RECALCULABLES, true)) {
                    $omitidas[] = ['id' => $rid, 'motivo' => 'Cambio de estado antes de confirmar; no se toco'];
                    continue;
                }

                $detalle = $this->detallePorReservacion([$rid], $hotelId);
                $calculo = $this->calcularReservacion($reservacion, $detalle[$rid] ?? [], $hotelId);

                if (!$calculo['recalculable']) {
                    $omitidas[] = ['id' => $rid, 'motivo' => $calculo['motivo']];
                    continue;
                }
                if (!$calculo['cambia']) {
                    $omitidas[] = ['id' => $rid, 'motivo' => 'Sin cambio de precio'];
                    continue;
                }

                foreach ($calculo['habitaciones'] as $hab) {
                    if ($hab['es_cortesia']) {
                        continue; // las cortesias siguen en 0, jamas se tocan
                    }
                    if (abs($hab['precio_nuevo'] - $hab['precio_actual']) <= self::TOLERANCIA) {
                        continue;
                    }
                    $stmtUpdHab->execute([$hab['precio_nuevo'], $hab['rh_id'], $hotelId, $rid]);
                }

                // precio_total re-derivado del detalle NUEVO (noches x motor),
                // jamas del detalle viejo: el guard #1867 queda resuelto de raiz.
                $stmtUpdRes->execute([$calculo['precio_nuevo'], $rid, $hotelId]);

                $deltaTotal += $calculo['delta'];
                $actualizadas[] = [
                    'id' => $rid,
                    'precio_anterior' => $calculo['precio_actual'],
                    'precio_nuevo' => $calculo['precio_nuevo'],
                    'delta' => $calculo['delta'],
                ];
            }

            if (!$enTransaccion) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if (!$enTransaccion && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('TarifaImpactoService::aplicar fallo: ' . $e->getMessage());
            throw new Exception('No se pudo aplicar el recalculo; no se modifico ninguna reservacion. ' . $e->getMessage());
        }

        return [
            'actualizadas' => $actualizadas,
            'omitidas' => $omitidas,
            'delta_total' => round($deltaTotal, 2),
        ];
    }

    // ── Internos ─────────────────────────────────────────────────────────

    private function cargarTarifa(int $tarifaId, int $hotelId): array {
        $stmt = $this->db->query(
            "SELECT * FROM incrementos_tarifas WHERE id = ? AND hotel_id = ? LIMIT 1",
            [$tarifaId, $hotelId]
        );
        $tarifa = $stmt ? $stmt->fetch() : false;
        if (!$tarifa) {
            throw new Exception('Incremento de tarifa no encontrado');
        }
        return $tarifa;
    }

    /** Motivo por el que el recalculo no aplica a esta tarifa, o null si aplica. */
    private function motivoNoAplicable(array $tarifa): ?string {
        if (($tarifa['clase'] ?? 'incremento') === 'descuento') {
            return 'Los descuentos se aplican al crear cada reservacion; las existentes conservan su descuento actual y no se recalculan desde aqui.';
        }
        if (empty($tarifa['activo'])) {
            return 'La tarifa esta desactivada; activala antes de recalcular reservaciones.';
        }
        return null;
    }

    /**
     * Reservaciones candidatas: confirmada/checked_in, con noches por venir,
     * estancia solapada con la vigencia y >=1 habitacion cobrable en el alcance.
     * La salida efectiva se extiende a entrada+1 para estancias de 0 noches
     * (mismo criterio de "1 noche minima" del resto del modulo).
     */
    private function candidatas(array $tarifa, int $hotelId, string $hoy): array {
        $fin = (!empty($tarifa['es_permanente']) || empty($tarifa['fecha_fin'])) ? null : $tarifa['fecha_fin'];

        $sqlScope = '';
        $scopeParams = [];
        if ($tarifa['alcance'] === 'tipo_habitacion') {
            $tipos = json_decode((string)$tarifa['tipos_habitacion'], true) ?: [];
            $tipos = array_values(array_filter(array_map('strval', $tipos), 'strlen'));
            if (empty($tipos)) {
                return [];
            }
            $sqlScope = ' AND h.tipo IN (' . implode(',', array_fill(0, count($tipos), '?')) . ')';
            $scopeParams = $tipos;
        } elseif ($tarifa['alcance'] === 'habitacion') {
            $habs = array_values(array_filter(array_map('intval', json_decode((string)$tarifa['habitaciones'], true) ?: [])));
            if (empty($habs)) {
                return [];
            }
            $sqlScope = ' AND h.id IN (' . implode(',', array_fill(0, count($habs), '?')) . ')';
            $scopeParams = $habs;
        }

        $sqlFin = '';
        $params = [$hotelId, $hoy, $tarifa['fecha_inicio']];
        if ($fin !== null) {
            $sqlFin = ' AND r.fecha_entrada <= ?';
            $params[] = $fin;
        }
        $params = array_merge($params, $scopeParams);

        $sql = "SELECT r.id, r.huesped_id, r.fecha_entrada, r.fecha_salida, r.estado,
                       r.precio_total, r.descuento_total,
                       hu.nombre_completo AS huesped
                FROM reservaciones r
                LEFT JOIN huespedes hu ON hu.id = r.huesped_id AND hu.hotel_id = r.hotel_id
                WHERE r.hotel_id = ?
                  AND r.estado IN ('confirmada','checked_in')
                  AND GREATEST(r.fecha_salida, DATE_ADD(r.fecha_entrada, INTERVAL 1 DAY)) > ?
                  AND GREATEST(r.fecha_salida, DATE_ADD(r.fecha_entrada, INTERVAL 1 DAY)) > ?
                  {$sqlFin}
                  AND EXISTS (
                      SELECT 1
                      FROM reservacion_habitaciones rh
                      INNER JOIN habitaciones h ON h.id = rh.habitacion_id AND h.hotel_id = rh.hotel_id
                      WHERE rh.reservacion_id = r.id
                        AND rh.hotel_id = r.hotel_id
                        AND rh.es_cortesia = 0
                        {$sqlScope}
                  )
                ORDER BY r.fecha_entrada ASC, r.id ASC";

        $stmt = $this->db->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /** Detalle por habitacion de un lote de reservaciones: mapa id => filas. */
    private function detallePorReservacion(array $reservacionIds, int $hotelId): array {
        $ids = array_values(array_filter(array_map('intval', $reservacionIds)));
        if (empty($ids)) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->db->query(
            "SELECT rh.id, rh.reservacion_id, rh.habitacion_id, rh.precio, rh.es_cortesia,
                    h.numero, h.tipo, h.precio_base
             FROM reservacion_habitaciones rh
             LEFT JOIN habitaciones h ON h.id = rh.habitacion_id AND h.hotel_id = rh.hotel_id
             WHERE rh.hotel_id = ? AND rh.reservacion_id IN ({$placeholders})
             ORDER BY rh.reservacion_id ASC, rh.id ASC",
            array_merge([$hotelId], $ids)
        );

        $mapa = [];
        foreach (($stmt ? $stmt->fetchAll() : []) as $fila) {
            $mapa[(int)$fila['reservacion_id']][] = $fila;
        }
        return $mapa;
    }

    /**
     * Re-deriva el precio de UNA reservacion desde el motor vigente.
     * Devuelve siempre 'recalculable' (bool) y, si lo es, el desglose completo
     * con 'cambia' (bool) indicando si algo se moveria al aplicar.
     */
    private function calcularReservacion(array $reservacion, array $filas, int $hotelId): array {
        $rid = (int)$reservacion['id'];
        $base = [
            'id' => $rid,
            'huesped' => $reservacion['huesped'] ?? null,
            'recalculable' => false,
            'motivo' => null,
        ];

        if (empty($filas)) {
            $base['motivo'] = 'No tiene detalle de habitaciones; revisala a mano.';
            return $base;
        }

        $noches = $this->noches($reservacion['fecha_entrada'], $reservacion['fecha_salida']);

        $habitaciones = [];
        $subtotalActual = 0.0;
        $subtotalNuevo = 0.0;
        $hayCobrable = false;

        foreach ($filas as $fila) {
            $esCortesia = (int)$fila['es_cortesia'] === 1;

            if ($esCortesia) {
                $habitaciones[] = [
                    'rh_id' => (int)$fila['id'],
                    'numero' => $fila['numero'] ?? '—',
                    'es_cortesia' => true,
                    'precio_actual' => round((float)$fila['precio'], 2),
                    'precio_nuevo' => round((float)$fila['precio'], 2),
                ];
                continue;
            }

            $hayCobrable = true;

            // Habitacion borrada / de otro hotel / sin precio base: no se puede
            // re-derivar con seguridad — se omite la reservacion completa.
            if ($fila['habitacion_id'] === null || $fila['precio_base'] === null || (float)$fila['precio_base'] <= 0) {
                $base['motivo'] = 'Una de sus habitaciones ya no existe o no tiene precio base valido; recalculala a mano.';
                return $base;
            }

            $precioNuevo = $this->precioEstanciaHabitacion(
                (int)$fila['habitacion_id'],
                (string)$fila['tipo'],
                (float)$fila['precio_base'],
                (string)$reservacion['fecha_entrada'],
                $noches,
                $hotelId
            );

            $subtotalActual += (float)$fila['precio'];
            $subtotalNuevo += $precioNuevo;

            $habitaciones[] = [
                'rh_id' => (int)$fila['id'],
                'numero' => $fila['numero'] ?? ('#' . $fila['habitacion_id']),
                'es_cortesia' => false,
                'precio_actual' => round((float)$fila['precio'], 2),
                'precio_nuevo' => $precioNuevo,
            ];
        }

        if (!$hayCobrable) {
            $base['motivo'] = 'Todas sus habitaciones son cortesia; no hay nada que recalcular.';
            return $base;
        }

        $subtotalActual = round($subtotalActual, 2);
        $subtotalNuevo = round($subtotalNuevo, 2);

        // descuento_total se CONSERVA; solo se topa al nuevo subtotal para
        // que el total jamas quede negativo.
        $descuento = max(0.0, (float)($reservacion['descuento_total'] ?? 0));
        $descuentoAplicado = round(min($descuento, $subtotalNuevo), 2);

        $precioActual = round((float)$reservacion['precio_total'], 2);
        $precioNuevoTotal = round($subtotalNuevo - $descuentoAplicado, 2);

        // Guard #1867: ¿el total actual cuadra con su detalle - descuento?
        // Si no cuadra, el detalle esta desincronizado de las noches reales;
        // se avisa (al aplicar queda corregido porque todo se re-deriva).
        $totalSegunDetalle = round(max(0, $subtotalActual - min($descuento, $subtotalActual)), 2);
        $cuadraDetalle = abs($totalSegunDetalle - $precioActual) <= self::TOLERANCIA;

        $delta = round($precioNuevoTotal - $precioActual, 2);

        $cambia = abs($delta) > self::TOLERANCIA;
        if (!$cambia) {
            foreach ($habitaciones as $hab) {
                if (!$hab['es_cortesia'] && abs($hab['precio_nuevo'] - $hab['precio_actual']) > self::TOLERANCIA) {
                    $cambia = true; // el total no se mueve pero el desglose si
                    break;
                }
            }
        }

        return [
            'id' => $rid,
            'huesped' => $reservacion['huesped'] ?? null,
            'estado' => $reservacion['estado'],
            'fecha_entrada' => $reservacion['fecha_entrada'],
            'fecha_salida' => $reservacion['fecha_salida'],
            'noches' => $noches,
            'recalculable' => true,
            'cambia' => $cambia,
            'habitaciones' => $habitaciones,
            'subtotal_actual' => $subtotalActual,
            'subtotal_nuevo' => $subtotalNuevo,
            'descuento_total' => round($descuento, 2),
            'descuento_aplicado' => $descuentoAplicado,
            'descuento_topado' => $descuentoAplicado + self::TOLERANCIA < $descuento,
            'precio_actual' => $precioActual,
            'precio_nuevo' => $precioNuevoTotal,
            'delta' => $delta,
            'cuadra_detalle' => $cuadraDetalle,
            'total_segun_detalle' => $totalSegunDetalle,
            'motivo' => null,
        ];
    }

    /** Noches de la estancia (minimo 1, mismo criterio del resto del modulo). */
    private function noches(string $entrada, string $salida): int {
        $dias = (new DateTime($entrada))->diff(new DateTime($salida))->days;
        return max(1, (int)$dias);
    }

    /**
     * Precio de la estancia completa de una habitacion, noche por noche, con
     * el motor vigente (todas las tarifas activas de clase 'incremento' que
     * apliquen a CADA fecha — identico criterio a crear/actualizar reservacion).
     */
    private function precioEstanciaHabitacion(int $habitacionId, string $tipo, float $precioBase, string $entrada, int $noches, int $hotelId): float {
        $precio = 0.0;
        $fecha = new DateTime($entrada);

        for ($i = 0; $i < $noches; $i++) {
            $calculo = $this->tarifaModel->calcularPrecioConIncremento(
                $habitacionId,
                $tipo,
                $precioBase,
                $fecha->format('Y-m-d'),
                $hotelId
            );
            $precio += (float)$calculo['precio_final'];
            $fecha->modify('+1 day');
        }

        return round($precio, 2);
    }
}
