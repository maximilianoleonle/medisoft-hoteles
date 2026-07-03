<?php
/**
 * App de camarista (bloque camarista): tablero movil de limpieza.
 * Cualquier usuario del hotel puede usarlo (la camarista entra con su usuario);
 * solo alterna limpieza <-> disponible, nunca toca ocupada ni mantenimiento.
 */

class CamaristaController extends Controller {

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module('camarista');
        }

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $db = Database::getInstance();

        $stmt = $db->query(
            "SELECT id, numero, tipo, piso, estado FROM habitaciones
             WHERE hotel_id = ? AND activa = 1
             ORDER BY piso, CAST(numero AS UNSIGNED)",
            [$hotelId]
        );
        $habitaciones = $stmt ? $stmt->fetchAll() : [];

        // Salidas de hoy: al hacer checkout esas habitaciones necesitaran limpieza.
        $stmt = $db->query(
            "SELECT rh.habitacion_id, r.estado AS reservacion_estado
             FROM reservacion_habitaciones rh
             INNER JOIN reservaciones r ON r.id = rh.reservacion_id AND r.hotel_id = rh.hotel_id
             WHERE rh.hotel_id = ?
               AND r.fecha_salida = CURDATE()
               AND r.estado IN ('checked_in', 'checked_out')",
            [$hotelId]
        );
        $salidasHoy = [];
        foreach (($stmt ? $stmt->fetchAll() : []) as $fila) {
            $salidasHoy[(int) $fila['habitacion_id']] = (string) $fila['reservacion_estado'];
        }

        View::renderTemplate('camarista/index', [
            'title' => 'Limpieza - ' . current_hotel_display_name(),
            'habitaciones' => $habitaciones,
            'salidasHoy' => $salidasHoy,
        ]);
    }

    public function marcarAction($habitacionId = null) {
        if (!$this->isPost()) {
            $this->redirect('camarista');
        }

        $this->validateCSRF();
        $hotelId = (int) obtenerHotelIdActualCompat();
        $habitacionId = (int) $habitacionId;
        $nuevoEstado = (string) $this->getPost('estado', '');

        // Solo el par limpieza <-> disponible; el resto es de recepcion/mantenimiento.
        if (!in_array($nuevoEstado, ['limpieza', 'disponible'], true)) {
            set_mensaje('Accion no permitida desde el tablero de limpieza.', 'error');
            $this->redirect('camarista');
        }

        $db = Database::getInstance();
        $stmt = $db->query(
            "SELECT estado FROM habitaciones WHERE id = ? AND hotel_id = ? AND activa = 1 LIMIT 1",
            [$habitacionId, $hotelId]
        );
        $habitacion = $stmt ? $stmt->fetch() : null;

        if (!$habitacion) {
            set_mensaje('Habitacion no encontrada.', 'error');
            $this->redirect('camarista');
        }

        $estadoActual = (string) $habitacion['estado'];

        // Nunca pisar ocupada ni mantenimiento desde aqui.
        if (!in_array($estadoActual, ['limpieza', 'disponible'], true)) {
            set_mensaje('Esa habitacion esta ' . $estadoActual . '; recepcion debe liberarla primero.', 'error');
            $this->redirect('camarista');
        }

        $ok = $db->query(
            "UPDATE habitaciones SET estado = ?, updated_at = NOW() WHERE id = ? AND hotel_id = ?",
            [$nuevoEstado, $habitacionId, $hotelId]
        ) !== false;

        set_mensaje(
            $ok
                ? ($nuevoEstado === 'disponible' ? 'Habitacion marcada como limpia. ✨' : 'Habitacion marcada en limpieza.')
                : 'No se pudo actualizar la habitacion.',
            $ok ? 'success' : 'error'
        );
        $this->redirect('camarista');
    }
}
