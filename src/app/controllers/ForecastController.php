<?php
/**
 * Forecast de ocupacion (bloque forecast): proyeccion 30/60/90 dias,
 * pickup report y comparativa contra el anio anterior. Solo lectura sobre la
 * operacion; lo unico que escribe es el calendario de temporadas del hotel
 * (temporadas_hotel), que alimenta al forecast y al consejo del Copiloto.
 */

require_once __DIR__ . '/../services/ForecastService.php';
require_once __DIR__ . '/../helpers/festivos_mx.php';

class ForecastController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('forecast');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new ForecastService();

        $totalHabitaciones = $servicio->habitacionesActivas($hotelId);
        $porDia = $servicio->ocupacionPorDia($hotelId, date('Y-m-d'), 90, true);

        // Temporadas del hotel + festivos MX de los proximos 90 dias
        $hoy = date('Y-m-d');
        $tope90 = date('Y-m-d', strtotime('+90 days'));
        $temporadaModel = new TemporadaHotel();
        $temporadas = $temporadaModel->delHotel($hotelId);
        $eventos = array_merge(
            array_map(static function ($t) {
                return ['tipo' => 'temporada', 'desde' => $t['desde'], 'hasta' => $t['hasta'], 'nombre' => $t['nombre'], 'intensidad' => $t['intensidad']];
            }, $temporadaModel->enRango($hoy, $tope90, $hotelId)),
            array_map(static function ($f) {
                return $f + ['tipo' => 'festivo', 'intensidad' => 'alta'];
            }, festivos_mx_en_rango($hoy, $tope90))
        );
        usort($eventos, static function ($a, $b) {
            return strcmp($a['desde'], $b['desde']);
        });

        // Marca discreta por dia para las barras de los proximos 30 dias
        $eventosPorDia = [];
        foreach ($eventos as $e) {
            $d = max(strtotime($e['desde']), strtotime($hoy));
            $fin = min(strtotime($e['hasta']), strtotime('+29 days'));
            for (; $d <= $fin; $d = strtotime('+1 day', $d)) {
                $eventosPorDia[date('Y-m-d', $d)][] = $e['nombre'];
            }
        }

        View::renderTemplate('forecast/index', [
            'title' => 'Pronóstico de ocupación - ' . current_hotel_display_name(),
            'totalHabitaciones' => $totalHabitaciones,
            'porDia' => $porDia,
            'kpis' => [
                'ocupacion_30' => ForecastService::promedio($porDia, 30, $totalHabitaciones),
                'ocupacion_60' => ForecastService::promedio($porDia, 60, $totalHabitaciones),
                'ocupacion_90' => ForecastService::promedio($porDia, 90, $totalHabitaciones),
            ],
            'pickup' => $servicio->pickup($hotelId),
            'semanas' => $servicio->resumenSemanal($hotelId, 12, $totalHabitaciones),
            'temporadas' => $temporadas,
            'eventos' => $eventos,
            'eventosPorDia' => $eventosPorDia,
            'puedeEditarTemporadas' => function_exists('can') && can('tarifas.edit'),
            // Arranque en frio: sin historico de hace 1 anio NI temporadas
            // capturadas, el consejo de tarifa vuela a ciegas -> aviso ambar.
            'arranqueFrio' => empty($temporadas) && !$servicio->tieneHistoricoAnual($hotelId),
        ]);
    }

    /**
     * Captura expres del arranque en frio: meses fuertes/flojos elegidos en
     * chips se convierten en temporadas recurrentes (mes completo, cada anio).
     */
    public function temporadaExpressAction() {
        $this->soloPostTemporadas();

        $meses = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
            'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];

        $limpiar = static function ($lista) {
            $out = [];
            foreach ((array) $lista as $m) {
                $m = (int) $m;
                if ($m >= 1 && $m <= 12) {
                    $out[$m] = true;
                }
            }
            return $out;
        };

        $altas = $limpiar($this->getPost('meses_alta', []));
        $bajas = array_diff_key($limpiar($this->getPost('meses_baja', [])), $altas);

        if (empty($altas) && empty($bajas)) {
            set_mensaje('Elige al menos un mes fuerte o flojo.', 'error');
            $this->redirect('forecast');
        }

        $modelo = new TemporadaHotel();
        $anio = (int) date('Y');
        $creadas = 0;

        foreach ([['alta', $altas], ['baja', $bajas]] as [$intensidad, $lista]) {
            foreach (array_keys($lista) as $mes) {
                $desde = sprintf('%04d-%02d-01', $anio, $mes);
                $hasta = date('Y-m-t', strtotime($desde));
                $ok = $modelo->create([
                    'nombre' => 'Temporada ' . $intensidad . ' de ' . $meses[$mes],
                    'desde' => $desde,
                    'hasta' => $hasta,
                    'intensidad' => $intensidad,
                    'recurrente_anual' => 1,
                    'notas' => 'Captura expres',
                ]);
                if ($ok) {
                    $creadas++;
                }
            }
        }

        set_mensaje(
            $creadas > 0
                ? "Listo: {$creadas} temporada(s) guardadas. El Copiloto ya las toma en cuenta y puedes afinarlas cuando quieras."
                : 'No se pudo guardar la captura expres.',
            $creadas > 0 ? 'success' : 'error'
        );
        $this->redirect('forecast');
    }

    /** Alta/edicion de una temporada del hotel (id vacio = crear). */
    public function temporadaGuardarAction() {
        $this->soloPostTemporadas();

        $id = (int) $this->getPost('id', 0);
        $nombre = trim((string) $this->getPost('nombre', ''));
        $desde = trim((string) $this->getPost('desde', ''));
        $hasta = trim((string) $this->getPost('hasta', ''));
        $intensidad = (string) $this->getPost('intensidad', 'alta');
        $recurrente = $this->getPost('recurrente_anual') ? 1 : 0;
        $notas = trim((string) $this->getPost('notas', ''));

        $fechaOk = static function ($f) {
            return preg_match('/^\d{4}-\d{2}-\d{2}$/', $f) && strtotime($f) !== false;
        };

        if ($nombre === '' || mb_strlen($nombre) > 120) {
            set_mensaje('Ponle un nombre a la temporada (maximo 120 caracteres).', 'error');
        } elseif (!$fechaOk($desde) || !$fechaOk($hasta) || $desde > $hasta) {
            set_mensaje('Revisa las fechas: "desde" debe ser una fecha valida anterior o igual a "hasta".', 'error');
        } else {
            $datos = [
                'nombre' => $nombre,
                'desde' => $desde,
                'hasta' => $hasta,
                'intensidad' => in_array($intensidad, ['alta', 'baja'], true) ? $intensidad : 'alta',
                'recurrente_anual' => $recurrente,
                'notas' => mb_substr($notas, 0, 500),
            ];

            $modelo = new TemporadaHotel();
            $ok = $id > 0 ? $modelo->update($id, $datos) : $modelo->create($datos);
            set_mensaje(
                $ok ? 'Temporada guardada. El Copiloto ya la toma en cuenta.' : 'No se pudo guardar la temporada.',
                $ok ? 'success' : 'error'
            );
        }

        $this->redirect('forecast');
    }

    /** Eliminar una temporada del hotel. */
    public function temporadaEliminarAction() {
        $this->soloPostTemporadas();

        $id = (int) $this->getPost('id', 0);
        $ok = $id > 0 && (new TemporadaHotel())->delete($id);
        set_mensaje($ok ? 'Temporada eliminada.' : 'No se pudo eliminar la temporada.', $ok ? 'success' : 'error');
        $this->redirect('forecast');
    }

    /** Guardas comunes del CRUD de temporadas: POST + CSRF + permiso de tarifas. */
    private function soloPostTemporadas() {
        if (!$this->isPost()) {
            $this->redirect('forecast');
        }
        $this->validateCSRF();

        if (!function_exists('can') || !can('tarifas.edit')) {
            set_mensaje('No tienes permiso para editar las temporadas del hotel.', 'error');
            $this->redirect('forecast');
        }
    }
}
