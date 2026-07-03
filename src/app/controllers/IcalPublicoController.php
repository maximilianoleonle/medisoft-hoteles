<?php
/**
 * Feeds iCal publicos (bloque canales_ical, sin login).
 * El token de exportacion del hotel ES la credencial (32 hex, generado por
 * hotel); la URL se pega directamente en Airbnb/Booking, que la consultan
 * periodicamente sin sesion.
 */

require_once __DIR__ . '/../services/IcalCanalesService.php';

class IcalPublicoController extends Controller {

    public function feedAction($slug, $token, $habitacionId) {
        $slug = strtolower(trim((string) $slug));
        $hotel = null;

        if (preg_match('/^[a-z0-9](?:[a-z0-9-]{0,118}[a-z0-9])?$/', $slug)) {
            try {
                $db = Database::getInstance();
                $stmt = $db->query(
                    "SELECT id, nombre, slug, activo FROM hoteles WHERE slug = ? AND activo = 1 LIMIT 1",
                    [$slug]
                );
                $hotel = $stmt ? ($stmt->fetch() ?: null) : null;
            } catch (Throwable $e) {
                error_log('iCal publico: error al resolver hotel: ' . $e->getMessage());
            }
        }

        if (!$hotel
            || !function_exists('hotel_has_module')
            || !hotel_has_module('canales_ical', (int) $hotel['id'])) {
            $this->negar();
        }

        $servicio = new IcalCanalesService();

        if (!$servicio->validarToken((int) $hotel['id'], (string) $token)) {
            $this->negar();
        }

        $ics = $servicio->generarIcs((int) $hotel['id'], (int) $habitacionId);

        if ($ics === null) {
            $this->negar();
        }

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: inline; filename="medisoft-hab-' . (int) $habitacionId . '.ics"');
        header('Cache-Control: no-cache');
        echo $ics;
        exit;
    }

    private function negar() {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'No encontrado';
        exit;
    }
}
