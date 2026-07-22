<?php

/**
 * MotorDisponibilidadService
 *
 * Disponibilidad y precios para la pagina publica de reservas (bloque motor_reservas).
 * Reutiliza Habitacion::disponiblesEntreFechas + IncrementoTarifa::calcularPrecioConIncremento
 * (mismo calculo que la API interna) y agrupa por tipo de habitacion para el publico:
 * nunca expone numeros de habitacion reales.
 *
 * Requiere que el controlador publico haya hecho TenantContext::setHotel($hotel)
 * ANTES de llamar (los modelos se scopean por el hotel del contexto).
 */
class MotorDisponibilidadService
{
    /**
     * Valida el rango de fechas contra la configuracion del hotel.
     * Devuelve ['ok' => bool, 'error' => string|null, 'noches' => int].
     */
    public function validarRango(int $hotelId, string $entrada, string $salida): array
    {
        $formato = '/^\d{4}-\d{2}-\d{2}$/';
        if (!preg_match($formato, $entrada) || !preg_match($formato, $salida)) {
            return ['ok' => false, 'error' => 'Formato de fechas invalido.', 'noches' => 0];
        }

        $tsEntrada = strtotime($entrada . ' 00:00:00');
        $tsSalida = strtotime($salida . ' 00:00:00');
        $tsHoy = strtotime(date('Y-m-d') . ' 00:00:00');

        if ($tsEntrada === false || $tsSalida === false) {
            return ['ok' => false, 'error' => 'Fechas invalidas.', 'noches' => 0];
        }
        if ($tsEntrada < $tsHoy) {
            return ['ok' => false, 'error' => 'La fecha de llegada no puede ser anterior a hoy.', 'noches' => 0];
        }
        if ($tsSalida <= $tsEntrada) {
            return ['ok' => false, 'error' => 'La fecha de salida debe ser posterior a la de llegada.', 'noches' => 0];
        }

        $noches = (int) round(($tsSalida - $tsEntrada) / 86400);
        $minNoches = max(1, ConfiguracionHotelRegistry::getInt('motor.min_noches', 1, $hotelId));
        $maxNoches = max($minNoches, ConfiguracionHotelRegistry::getInt('motor.max_noches', 30, $hotelId));
        $maxDias = max(1, ConfiguracionHotelRegistry::getInt('motor.anticipacion_max_dias', 180, $hotelId));

        if ($noches < $minNoches) {
            return ['ok' => false, 'error' => 'La estancia mínima es de ' . $minNoches . ' ' . ($minNoches === 1 ? 'noche' : 'noches') . '.', 'noches' => $noches];
        }
        if ($noches > $maxNoches) {
            return ['ok' => false, 'error' => 'La estancia máxima es de ' . $maxNoches . ' ' . ($maxNoches === 1 ? 'noche' : 'noches') . '.', 'noches' => $noches];
        }
        if ($tsEntrada > strtotime('+' . $maxDias . ' days', $tsHoy)) {
            return ['ok' => false, 'error' => 'Solo se puede reservar con hasta ' . $maxDias . ' días de anticipación.', 'noches' => $noches];
        }

        return ['ok' => true, 'error' => null, 'noches' => $noches];
    }

    /**
     * Tipos de habitacion disponibles con precio total del periodo y anticipo requerido.
     * $personas <= 0 significa sin filtro de capacidad.
     */
    public function consultar(int $hotelId, string $entrada, string $salida, int $personas = 0): array
    {
        $rango = $this->validarRango($hotelId, $entrada, $salida);
        if (!$rango['ok']) {
            return ['success' => false, 'message' => $rango['error']];
        }

        $noches = max(1, (int) $rango['noches']);

        $habitacionModel = new Habitacion();
        $habitaciones = $habitacionModel->disponiblesEntreFechas($entrada, $salida);

        if ($personas > 0) {
            $habitaciones = array_filter($habitaciones, function ($hab) use ($personas) {
                return (int) ($hab['capacidad_personas'] ?? 0) >= $personas;
            });
        }

        $tarifaModel = new IncrementoTarifa();
        $imagenModel = class_exists('HabitacionImagen') ? new HabitacionImagen() : null;

        // Precio por habitacion para el periodo (mismo calculo que la API interna).
        $porTipo = [];
        foreach ($habitaciones as $hab) {
            $precioPeriodo = 0.0;
            $precioPrimeraNoche = 0.0;
            $fechaActual = new DateTime($entrada);

            for ($i = 0; $i < $noches; $i++) {
                $info = $tarifaModel->calcularPrecioConIncremento(
                    (int) $hab['id'],
                    (string) $hab['tipo'],
                    (float) $hab['precio_base'],
                    $fechaActual->format('Y-m-d')
                );
                $precioNoche = (float) ($info['precio_final'] ?? $hab['precio_base']);
                $precioPeriodo += $precioNoche;
                if ($i === 0) {
                    $precioPrimeraNoche = $precioNoche;
                }
                $fechaActual->modify('+1 day');
            }

            $tipo = (string) ($hab['tipo'] ?? 'Habitacion');

            // El publico ve el tipo con su opcion mas economica disponible.
            if (!isset($porTipo[$tipo]) || $precioPeriodo < $porTipo[$tipo]['precio_total']) {
                $foto = trim((string) ($hab['foto_url'] ?? ''));
                if ($imagenModel) {
                    $principal = $imagenModel->obtenerPrincipal((int) $hab['id']);
                    if (!empty($principal['ruta_imagen'] ?? null)) {
                        $foto = (string) $principal['ruta_imagen'];
                    }
                }

                $porTipo[$tipo] = [
                    'tipo' => $tipo,
                    'capacidad_personas' => (int) ($hab['capacidad_personas'] ?? 0),
                    'caracteristicas' => trim((string) ($hab['caracteristicas'] ?? '')),
                    'foto_url' => $foto,
                    'precio_total' => round($precioPeriodo, 2),
                    'precio_por_noche' => round($precioPeriodo / $noches, 2),
                    'precio_primera_noche' => round($precioPrimeraNoche, 2),
                    'disponibles' => 0,
                    'habitacion_id_referencia' => (int) $hab['id'],
                ];
            }

            $porTipo[$tipo]['disponibles']++;
        }

        // Anticipo requerido por tipo segun la politica del hotel.
        foreach ($porTipo as &$tipoInfo) {
            $tipoInfo['anticipo_requerido'] = $this->calcularAnticipo(
                $hotelId,
                (float) $tipoInfo['precio_total'],
                (float) $tipoInfo['precio_primera_noche']
            );
            unset($tipoInfo['precio_primera_noche']);
        }
        unset($tipoInfo);

        ksort($porTipo);

        return [
            'success' => true,
            'entrada' => $entrada,
            'salida' => $salida,
            'noches' => $noches,
            'tipos' => array_values($porTipo),
            'politica' => (string) ConfiguracionHotelRegistry::get('motor.politica_texto', '', $hotelId),
        ];
    }

    /**
     * Anticipo online segun config del hotel: porcentaje | primera_noche | monto_fijo.
     * Siempre entre 1.00 y el total de la estancia.
     */
    public function calcularAnticipo(int $hotelId, float $total, float $primeraNoche): float
    {
        $tipo = (string) ConfiguracionHotelRegistry::get('motor.anticipo_tipo', 'porcentaje', $hotelId);
        $valor = (float) ConfiguracionHotelRegistry::get('motor.anticipo_valor', 30.0, $hotelId);

        switch ($tipo) {
            case 'primera_noche':
                $anticipo = $primeraNoche;
                break;
            case 'monto_fijo':
                $anticipo = $valor;
                break;
            case 'porcentaje':
            default:
                $anticipo = $total * (max(1.0, min(100.0, $valor)) / 100.0);
                break;
        }

        return round(max(1.0, min($anticipo, $total)), 2);
    }
}
