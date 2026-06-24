<?php

/**
 * Servicio base para centralizar la distribucion de ingresos por propietario.
 *
 * Fase 2C: centraliza Caja y Reportes con configuracion por hotel.
 * Si no existe configuracion guardada, conserva la regla legacy Manolo/Elia.
 */
class PropietarioDistribucionService
{
    public const CONFIG_KEY = 'propietarios.distribucion';

    private const DEFAULT_OWNER_KEY = 'elia';
    private const LEGACY_MANOLO_KEY = 'manolo';

    public function configuracionLegacy(): array
    {
        return [
            'version' => 1,
            'propietario_default' => self::DEFAULT_OWNER_KEY,
            'propietarios' => [
                self::LEGACY_MANOLO_KEY => [
                    'key' => self::LEGACY_MANOLO_KEY,
                    'nombre' => 'Manolo',
                    'activo' => true,
                    'participacion_pct' => 100.0,
                ],
                self::DEFAULT_OWNER_KEY => [
                    'key' => self::DEFAULT_OWNER_KEY,
                    'nombre' => 'Elia',
                    'activo' => true,
                    'participacion_pct' => 100.0,
                ],
            ],
            'reglas_tipo_contiene' => [
                'manolo' => self::LEGACY_MANOLO_KEY,
            ],
            'habitaciones' => [],
        ];
    }

    public function configuracionParaHotel($hotelId = null): array
    {
        $legacy = $this->configuracionLegacy();

        if (!function_exists('hotel_config_get')) {
            return $this->normalizarConfiguracion($legacy);
        }

        try {
            $config = hotel_config_get(self::CONFIG_KEY, $legacy, $hotelId);
        } catch (Throwable $e) {
            error_log('No se pudo cargar la configuracion de propietarios: ' . $e->getMessage());
            $config = $legacy;
        }

        return $this->normalizarConfiguracion($config);
    }

    public function normalizarConfiguracion($config = null): array
    {
        $base = $this->configuracionLegacy();

        if (is_string($config) && trim($config) !== '') {
            $decoded = json_decode($config, true);
            $config = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
        }

        if (!is_array($config)) {
            $config = [];
        }

        $propietarios = $this->normalizarPropietarios(
            $config['propietarios'] ?? $base['propietarios'],
            $base['propietarios']
        );

        $defaultKey = $this->normalizarKey($config['propietario_default'] ?? $base['propietario_default']);
        if (!isset($propietarios[$defaultKey])) {
            $defaultKey = isset($propietarios[$base['propietario_default']])
                ? $base['propietario_default']
                : (string) array_key_first($propietarios);
        }

        return [
            'version' => 1,
            'propietario_default' => $defaultKey,
            'propietarios' => $propietarios,
            'reglas_tipo_contiene' => $this->normalizarReglasTipo(
                $config['reglas_tipo_contiene'] ?? $base['reglas_tipo_contiene'],
                $propietarios
            ),
            'habitaciones' => $this->normalizarAsignaciones(
                $config['habitaciones'] ?? [],
                $propietarios
            ),
        ];
    }

    public function resumenPropietarios(array $datos, $config = null): array
    {
        $config = $this->normalizarConfiguracion($config);
        $resumen = [];

        foreach ($datos as $ownerKey => $valores) {
            if (!is_array($valores) || strpos((string) $ownerKey, '_') === 0) {
                continue;
            }

            $ownerKey = (string) $ownerKey;
            $habitaciones = $this->habitacionesDesdeDetalle($valores['detalle'] ?? []);
            $total = array_key_exists('total', $valores)
                ? (float) $valores['total']
                : $this->totalMetodosPago($valores);
            $cantidad = (int) ($valores['reservas'] ?? ($valores['cantidad_reservas'] ?? count($habitaciones)));

            if ($total <= 0 && $cantidad <= 0 && empty($habitaciones)) {
                continue;
            }

            $resumen[$ownerKey] = [
                'key' => $ownerKey,
                'nombre' => $this->nombrePropietario($ownerKey, $config),
                'total' => $total,
                'cantidad' => $cantidad,
                'habitaciones' => $habitaciones,
                'datos' => $valores,
            ];
        }

        return $resumen;
    }

    public function nombrePropietario(string $ownerKey, $config = null): string
    {
        $config = $this->normalizarConfiguracion($config);

        if (!empty($config['propietarios'][$ownerKey]['nombre'])) {
            return (string) $config['propietarios'][$ownerKey]['nombre'];
        }

        $nombre = str_replace(['_', '-'], ' ', $ownerKey);
        return function_exists('mb_convert_case')
            ? mb_convert_case($nombre, MB_CASE_TITLE, 'UTF-8')
            : ucwords($nombre);
    }

    public function propietarioParaHabitacion(array $habitacion, $config = null): string
    {
        $config = $this->normalizarConfiguracion($config);

        $asignado = $this->propietarioPorAsignacionHabitacion($habitacion, $config);
        if ($asignado !== null) {
            return $asignado;
        }

        foreach (['propietario_key', 'propietario', 'owner_key', 'owner'] as $field) {
            if (!array_key_exists($field, $habitacion)) {
                continue;
            }

            $key = $this->normalizarKey($habitacion[$field]);
            if (isset($config['propietarios'][$key])) {
                return $key;
            }
        }

        $tipo = trim((string) ($habitacion['tipo'] ?? ''));
        foreach ($config['reglas_tipo_contiene'] as $needle => $ownerKey) {
            if ($needle !== '' && strpos($tipo, $needle) !== false) {
                return $ownerKey;
            }
        }

        return $config['propietario_default'];
    }

    public function distribuirPorPrecio(float $monto, array $habitaciones, $config = null): array
    {
        $config = $this->normalizarConfiguracion($config);
        $resultado = $this->crearPartesVacias($config);

        foreach ($habitaciones as $habitacion) {
            if (!is_array($habitacion)) {
                continue;
            }

            $ownerKey = $this->propietarioParaHabitacion($habitacion, $config);
            $precio = (float) ($habitacion['precio_base'] ?? ($habitacion['precio'] ?? 0));
            $numero = trim((string) ($habitacion['numero'] ?? ($habitacion['habitacion'] ?? '')));

            $resultado[$ownerKey]['precio'] += $precio;
            if ($numero !== '' && !in_array($numero, $resultado[$ownerKey]['habitaciones'], true)) {
                $resultado[$ownerKey]['habitaciones'][] = $numero;
            }
        }

        $precioTotal = 0.0;
        foreach ($resultado as $parte) {
            $precioTotal += (float) $parte['precio'];
        }

        if ($precioTotal > 0) {
            $asignado = 0.0;
            $ownerMayorMonto = $config['propietario_default'];
            $mayorMonto = null;

            foreach ($resultado as $ownerKey => $parte) {
                if ((float) $parte['precio'] <= 0) {
                    continue;
                }

                $montoOwner = round($monto * ((float) $parte['precio'] / $precioTotal), 2);
                $resultado[$ownerKey]['monto'] = $montoOwner;
                $asignado += $montoOwner;

                if ($mayorMonto === null || $montoOwner > $mayorMonto) {
                    $mayorMonto = $montoOwner;
                    $ownerMayorMonto = $ownerKey;
                }
            }

            $diff = $monto - $asignado;
            if ($diff != 0) {
                $resultado[$ownerMayorMonto]['monto'] += $diff;
            }

            return $this->aplicarParticipacionPropietarios($resultado, $config);
        }

        $resultado[$config['propietario_default']]['monto'] = $monto;
        return $resultado;
    }

    public function crearResultadoCaja($config = null): array
    {
        $config = $this->normalizarConfiguracion($config);
        $resultado = [];

        foreach ($config['propietarios'] as $ownerKey => $propietario) {
            $resultado[$ownerKey] = [
                'efectivo' => 0,
                'tarjeta' => 0,
                'transferencia' => 0,
                'cantidad_reservas' => 0,
                'detalle' => [],
            ];
        }

        return $resultado;
    }

    public function aplicarMovimientoCaja(
        array $resultado,
        array $movimiento,
        array $habitaciones,
        array &$reservacionesContadas,
        $config = null
    ): array {
        $config = $this->normalizarConfiguracion($config);
        $partes = $this->distribuirPorPrecio((float) ($movimiento['monto'] ?? 0), $habitaciones, $config);
        $metodo = (string) ($movimiento['metodo_pago'] ?? '');
        $resId = (string) ($movimiento['reservacion_id'] ?? '');

        foreach ($partes as $ownerKey => $parte) {
            $montoOwner = (float) ($parte['monto'] ?? 0);
            if ($montoOwner <= 0) {
                continue;
            }

            if (!isset($resultado[$ownerKey])) {
                $resultado[$ownerKey] = $this->crearResultadoCaja($config)[$ownerKey];
            }

            if (isset($resultado[$ownerKey][$metodo])) {
                $resultado[$ownerKey][$metodo] += $montoOwner;
            }

            $claveRes = $resId . '-' . $ownerKey;
            if ($resId !== '' && !isset($reservacionesContadas[$claveRes])) {
                $resultado[$ownerKey]['cantidad_reservas']++;
                $reservacionesContadas[$claveRes] = true;
            }

            $resultado[$ownerKey]['detalle'][] = [
                'huesped' => $movimiento['huesped_nombre'] ?? null,
                'habitacion' => implode(', ', $parte['habitaciones'] ?? []),
                'metodo_pago' => $metodo,
                'monto' => $montoOwner,
            ];
        }

        return $resultado;
    }

    public function crearResultadoReporte($config = null): array
    {
        $config = $this->normalizarConfiguracion($config);
        $resultado = [];

        foreach ($config['propietarios'] as $ownerKey => $propietario) {
            $resultado[$ownerKey] = [
                'efectivo' => 0,
                'tarjeta' => 0,
                'transferencia' => 0,
                'total' => 0,
                'reservas' => 0,
            ];
        }

        return $resultado;
    }

    public function aplicarMovimientoReporte(
        array $resultado,
        array $movimiento,
        array $habitaciones,
        array &$reservacionesContadas,
        $config = null
    ): array {
        $config = $this->normalizarConfiguracion($config);
        $partes = $this->distribuirPorPrecio((float) ($movimiento['monto'] ?? 0), $habitaciones, $config);
        $metodo = (string) ($movimiento['metodo_pago'] ?? '');
        $resId = (string) ($movimiento['reservacion_id'] ?? '');

        foreach ($partes as $ownerKey => $parte) {
            $montoOwner = (float) ($parte['monto'] ?? 0);
            if ($montoOwner <= 0) {
                continue;
            }

            if (!isset($resultado[$ownerKey])) {
                $resultado[$ownerKey] = $this->crearResultadoReporte($config)[$ownerKey];
            }

            if (!isset($resultado[$ownerKey][$metodo])) {
                $resultado[$ownerKey][$metodo] = 0;
            }
            $resultado[$ownerKey][$metodo] += $montoOwner;
            $resultado[$ownerKey]['total'] += $montoOwner;

            $claveRes = $resId . '-' . $ownerKey;
            if ($resId !== '' && !isset($reservacionesContadas[$claveRes])) {
                $resultado[$ownerKey]['reservas']++;
                $reservacionesContadas[$claveRes] = true;
            }
        }

        return $resultado;
    }

    private function crearPartesVacias(array $config): array
    {
        $resultado = [];

        foreach ($config['propietarios'] as $ownerKey => $propietario) {
            $resultado[$ownerKey] = [
                'nombre' => $propietario['nombre'],
                'precio' => 0.0,
                'monto' => 0.0,
                'habitaciones' => [],
            ];
        }

        return $resultado;
    }

    private function aplicarParticipacionPropietarios(array $resultado, array $config): array
    {
        $defaultKey = (string) ($config['propietario_default'] ?? '');

        if ($defaultKey === '' || !isset($resultado[$defaultKey])) {
            return $resultado;
        }

        foreach ($resultado as $ownerKey => $parte) {
            if ($ownerKey === $defaultKey) {
                continue;
            }

            $montoOriginal = round((float) ($parte['monto'] ?? 0), 2);
            if ($montoOriginal <= 0) {
                continue;
            }

            $porcentaje = $this->normalizarPorcentaje($config['propietarios'][$ownerKey]['participacion_pct'] ?? 100);
            if ($porcentaje >= 100.0) {
                continue;
            }

            $montoPropietario = round($montoOriginal * ($porcentaje / 100), 2);
            $montoResidual = round($montoOriginal - $montoPropietario, 2);

            $resultado[$ownerKey]['monto'] = $montoPropietario;

            if ($montoResidual <= 0) {
                continue;
            }

            $resultado[$defaultKey]['monto'] += $montoResidual;
            foreach (($parte['habitaciones'] ?? []) as $habitacion) {
                if ($habitacion !== '' && !in_array($habitacion, $resultado[$defaultKey]['habitaciones'], true)) {
                    $resultado[$defaultKey]['habitaciones'][] = $habitacion;
                }
            }
        }

        return $resultado;
    }

    private function totalMetodosPago(array $valores): float
    {
        $total = 0.0;

        foreach (['efectivo', 'tarjeta', 'transferencia'] as $metodo) {
            $total += (float) ($valores[$metodo] ?? 0);
        }

        return $total;
    }

    private function habitacionesDesdeDetalle($detalle): array
    {
        if (!is_array($detalle)) {
            return [];
        }

        $habitaciones = [];

        foreach ($detalle as $row) {
            if (!is_array($row)) {
                continue;
            }

            $raw = trim((string) ($row['habitacion'] ?? ''));
            if ($raw === '') {
                continue;
            }

            foreach (array_map('trim', explode(',', $raw)) as $habitacion) {
                if ($habitacion !== '' && !in_array($habitacion, $habitaciones, true)) {
                    $habitaciones[] = $habitacion;
                }
            }
        }

        return $habitaciones;
    }

    private function propietarioPorAsignacionHabitacion(array $habitacion, array $config): ?string
    {
        $id = isset($habitacion['id']) ? (int) $habitacion['id'] : (isset($habitacion['habitacion_id']) ? (int) $habitacion['habitacion_id'] : null);
        $numero = strtolower(trim((string) ($habitacion['numero'] ?? ($habitacion['habitacion'] ?? ''))));
        $tipo = strtolower(trim((string) ($habitacion['tipo'] ?? '')));

        foreach ($config['habitaciones'] as $asignacion) {
            if (!empty($asignacion['habitacion_id']) && $id !== null && (int) $asignacion['habitacion_id'] === $id) {
                return $asignacion['propietario_key'];
            }

            if (!empty($asignacion['numero']) && $numero !== '' && strtolower((string) $asignacion['numero']) === $numero) {
                return $asignacion['propietario_key'];
            }

            if (!empty($asignacion['tipo']) && $tipo !== '' && strtolower((string) $asignacion['tipo']) === $tipo) {
                return $asignacion['propietario_key'];
            }
        }

        return null;
    }

    private function normalizarPropietarios($rawPropietarios, array $fallback): array
    {
        if (!is_array($rawPropietarios) || empty($rawPropietarios)) {
            $rawPropietarios = $fallback;
        }

        $propietarios = [];

        foreach ($rawPropietarios as $key => $data) {
            if (!is_array($data)) {
                $data = ['nombre' => (string) $data];
            }

            $ownerKey = $this->normalizarKey($data['key'] ?? $key);
            if ($ownerKey === '') {
                continue;
            }

            $nombre = trim((string) ($data['nombre'] ?? $data['label'] ?? $ownerKey));
            $propietarios[$ownerKey] = [
                'key' => $ownerKey,
                'nombre' => $nombre !== '' ? $nombre : $ownerKey,
                'activo' => array_key_exists('activo', $data) ? (bool) $data['activo'] : true,
                'participacion_pct' => $this->normalizarPorcentaje(
                    $data['participacion_pct'] ?? ($data['porcentaje'] ?? ($data['participacion'] ?? 100))
                ),
            ];
        }

        if (empty($propietarios)) {
            return $fallback;
        }

        $activos = array_filter($propietarios, static function (array $propietario): bool {
            return !empty($propietario['activo']);
        });

        return !empty($activos) ? $activos : $fallback;
    }

    private function normalizarReglasTipo($rawReglas, array $propietarios): array
    {
        if (!is_array($rawReglas)) {
            return [];
        }

        $reglas = [];
        foreach ($rawReglas as $needle => $ownerKey) {
            $needle = strtolower(trim((string) $needle));
            $ownerKey = $this->normalizarKey($ownerKey);

            if ($needle !== '' && isset($propietarios[$ownerKey])) {
                $reglas[$needle] = $ownerKey;
            }
        }

        return $reglas;
    }

    private function normalizarAsignaciones($rawAsignaciones, array $propietarios): array
    {
        if (!is_array($rawAsignaciones)) {
            return [];
        }

        $asignaciones = [];

        foreach ($rawAsignaciones as $asignacion) {
            if (!is_array($asignacion)) {
                continue;
            }

            $ownerKey = $this->normalizarKey($asignacion['propietario_key'] ?? ($asignacion['propietario'] ?? ''));
            if (!isset($propietarios[$ownerKey])) {
                continue;
            }

            $row = [
                'propietario_key' => $ownerKey,
            ];

            foreach (['habitacion_id', 'numero', 'tipo'] as $field) {
                if (!array_key_exists($field, $asignacion)) {
                    continue;
                }

                $value = is_string($asignacion[$field]) ? trim($asignacion[$field]) : $asignacion[$field];
                if ($value !== '' && $value !== null) {
                    $row[$field] = $value;
                }
            }

            if (count($row) > 1) {
                $asignaciones[] = $row;
            }
        }

        return $asignaciones;
    }

    private function normalizarKey($value): string
    {
        $key = strtolower(trim((string) $value));
        $key = preg_replace('/[^a-z0-9_-]+/', '_', $key);
        $key = trim((string) $key, '_-');

        return $key;
    }

    private function normalizarPorcentaje($value): float
    {
        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
        }

        $porcentaje = is_numeric($value) ? (float) $value : 100.0;

        return max(0.0, min(100.0, round($porcentaje, 2)));
    }
}
