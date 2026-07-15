<?php
/**
 * Modo Dueno (bloque modo_dueno): resumen remoto del hotel, 100% LECTURA.
 *
 * Pensado para el dueno que NO opera el hotel: abre el telefono y entiende
 * en segundos como va su negocio. Sin sidebar, sin tabs: la vista ES la app
 * para este rol. Cero endpoints de escritura en este controlador.
 *
 * Gates: sesion + contexto de hotel + bloque modo_dueno + permiso dueno.view.
 * Cada tarjeta de la vista se gatea ademas por su propio permiso de lectura
 * (caja.view, habitaciones.view, guardian.view, reputacion.view) y por el
 * modulo correspondiente del hotel: la vista se arma con cualquier subconjunto.
 */

class DuenoController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('modo_dueno');
        }

        $this->requirePermission('dueno.view');

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();

        View::renderTemplate('dueno/index', [
            'title' => 'Mi hotel - ' . (current_hotel_nombre() ?: 'Medisoft'),
            'resumen' => $this->armarResumen($hotelId),
        ]);
    }

    /**
     * Datos frescos en JSON para el boton "Actualizar" de la vista.
     * GET y solo lectura.
     */
    public function datosAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();

        View::renderJSON([
            'success' => true,
            'resumen' => $this->armarResumen($hotelId),
        ]);
    }

    /**
     * Resumen del dia por tarjeta. Cada bloque llega null si el usuario no
     * tiene el permiso o el hotel no tiene el modulo: la vista simplemente
     * no pinta esa tarjeta.
     */
    private function armarResumen(int $hotelId): array {
        return [
            'generado_en' => date('c'),
            'saludo' => $this->saludo(),
            'hotel' => [
                'nombre' => (string) (current_hotel_nombre() ?: ''),
            ],
        ];
    }

    private function saludo(): string {
        $hora = (int) date('G');

        if ($hora < 12) {
            return 'Buenos días';
        }

        return $hora < 19 ? 'Buenas tardes' : 'Buenas noches';
    }
}
