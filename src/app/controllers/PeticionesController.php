<?php
require_once __DIR__ . '/../services/EstanciaService.php';

/**
 * Peticiones de huespedes (QR en la habitacion, dentro del bloque reputacion).
 *
 * - /peticiones: bandeja del personal (lo que piden desde el QR de su cuarto).
 * - /peticiones/qr: contenido de la pagina del huesped (Wi-Fi, info,
 *   reglamento, WhatsApp de recepcion) + los QR de cada habitacion para
 *   imprimir y, si hace falta, regenerar.
 *
 * Permisos (any-of, sin claves nuevas para que funcione con los roles que ya
 * existen en cada hotel):
 * - VER: quien ve habitaciones, limpieza o tareas (recepcion, camaristas,
 *   mantenimiento) o las opiniones del hotel.
 * - ATENDER: lo mismo MENOS el dueño remoto de solo lectura (tiene
 *   habitaciones.view y reputacion.view, pero no opera).
 * - CONFIGURAR: reputacion.configurar (gerencia/administracion): la clave
 *   del Wi-Fi y el QR impreso son decisiones del hotel, no del turno.
 */
class PeticionesController extends Controller {

    private const VER = ['habitaciones.view', 'camarista.view', 'tareas.view', 'reputacion.view'];
    private const ATENDER = ['habitaciones.checkin', 'habitaciones.mantenimiento', 'camarista.view', 'tareas.view', 'reputacion.configurar'];
    private const CONFIGURAR = 'reputacion.configurar';

    private const PERMISOS = [
        'index' => self::VER,
        'foto' => self::VER,
        'estado' => self::ATENDER,
        'qr' => self::CONFIGURAR,
        'guardarConfiguracion' => self::CONFIGURAR,
        'regenerar' => self::CONFIGURAR,
        'imprimir' => self::CONFIGURAR,
    ];

    protected function before() {
        $this->requireAuth();

        if (function_exists('require_hotel_context')) {
            require_hotel_context();
        }

        if (function_exists('require_hotel_module')) {
            require_hotel_module(EstanciaService::MODULO);
        }

        $this->requirePermissionForAction(self::PERMISOS);

        return true;
    }

    public function indexAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $vista = (($_GET['vista'] ?? '') === 'cerradas') ? 'cerradas' : 'abiertas';
        $servicio = new EstanciaService();

        View::renderTemplate('peticiones/index', [
            'title' => 'Peticiones de huéspedes - ' . current_hotel_display_name(),
            'vista' => $vista,
            'peticiones' => $servicio->bandeja($hotelId, $vista),
            'resumen' => $servicio->resumen($hotelId),
            'tipos' => EstanciaService::TIPOS,
            'hayQr' => $this->hotelTieneQr($hotelId),
            'puedeAtender' => can_any(self::ATENDER),
            'puedeConfigurar' => can(self::CONFIGURAR),
            'puedeVerHabitacion' => can('habitaciones.view'),
        ]);
    }

    public function estadoAction($id = null) {
        if (!$this->isPost()) {
            $this->redirect('peticiones');
        }

        $this->validateCSRF();

        $hotelId = (int) obtenerHotelIdActualCompat();
        $accion = (string) ($_POST['accion'] ?? '');
        $nota = is_string($_POST['nota'] ?? null) ? (string) $_POST['nota'] : '';
        $usuarioId = function_exists('user_id') ? (int) user_id() : 0;

        $resultado = (new EstanciaService())->cambiarEstado($hotelId, (int) $id, $accion, $usuarioId > 0 ? $usuarioId : null, $nota);

        $textos = [
            'atender' => 'Listo: la petición es tuya. El huésped ya la ve "En camino".',
            'resolver' => 'Petición resuelta.',
            'descartar' => 'Petición descartada.',
        ];

        set_mensaje(
            !empty($resultado['success']) ? ($textos[$accion] ?? 'Listo.') : (string) ($resultado['mensaje'] ?? 'No se pudo actualizar la petición.'),
            !empty($resultado['success']) ? 'success' : 'error'
        );

        $this->redirect((($_POST['vista'] ?? '') === 'cerradas') ? 'peticiones?vista=cerradas' : 'peticiones');
    }

    /** Foto adjunta por el huesped (storage privado, solo del hotel de la sesion). */
    public function fotoAction($id = null) {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $foto = (new EstanciaService())->foto($hotelId, (int) $id);

        if (!$foto) {
            http_response_code(404);
            header('Content-Type: text/plain; charset=utf-8');
            echo 'La foto ya no está disponible.';
            exit;
        }

        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$foto['mime']] ?? 'img';

        header('Content-Type: ' . $foto['mime']);
        header('Content-Length: ' . (string) filesize($foto['ruta']));
        header('Content-Disposition: inline; filename="peticion-' . (int) $id . '.' . $extension . '"');
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'");
        header('Cache-Control: private, max-age=3600');
        readfile($foto['ruta']);
        exit;
    }

    public function qrAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new EstanciaService();
        $usuarioId = function_exists('user_id') ? (int) user_id() : 0;
        $slug = $this->slugHotel($hotelId);

        $habitaciones = [];
        foreach ($servicio->habitacionesConToken($hotelId, true, $usuarioId > 0 ? $usuarioId : null) as $fila) {
            if (empty($fila['token'])) {
                continue;
            }
            $fila['link'] = EstanciaService::urlAbsoluta(EstanciaService::rutaPublica($slug, (string) $fila['token']));
            $habitaciones[] = $fila;
        }

        $muestra = $habitaciones[0] ?? null;

        // Si el guardado anterior fallo, se repinta lo capturado (no se pierde).
        $borrador = $_SESSION['estancia_cfg_borrador'] ?? null;
        unset($_SESSION['estancia_cfg_borrador']);

        View::renderTemplate('peticiones/qr', [
            'title' => 'QR de habitaciones - ' . current_hotel_display_name(),
            'config' => is_array($borrador) ? array_merge($servicio->configuracion($hotelId), $borrador) : $servicio->configuracion($hotelId),
            'habitaciones' => $habitaciones,
            'pisos' => array_values(array_unique(array_map(static function ($f) { return (string) $f['piso']; }, $habitaciones))),
            'muestra' => $muestra,
            'muestraSvg' => $muestra ? EstanciaService::qrSvg((string) $muestra['link'], 'QR de la habitación ' . $muestra['numero']) : '',
        ]);
    }

    public function guardarConfiguracionAction() {
        if (!$this->isPost()) {
            $this->redirect('peticiones/qr');
        }

        $this->validateCSRF();

        $hotelId = (int) obtenerHotelIdActualCompat();
        // POST crudo: el servicio normaliza y la vista escapa al pintar.
        $resultado = (new EstanciaService())->guardarConfiguracion($hotelId, $_POST);

        if (empty($resultado['success'])) {
            $_SESSION['estancia_cfg_borrador'] = array_intersect_key(
                array_map(static function ($v) { return is_string($v) ? $v : ''; }, $_POST),
                array_flip(['wifi_red', 'wifi_clave', 'contacto_whatsapp', 'info', 'reglamento'])
            );
            set_mensaje(implode(' ', $resultado['errores'] ?? ['No se pudo guardar.']), 'error');
        } else {
            set_mensaje('Listo: así lo verán tus huéspedes al escanear el QR.', 'success');
        }

        $this->redirect('peticiones/qr');
    }

    public function regenerarAction($id = null) {
        if (!$this->isPost()) {
            $this->redirect('peticiones/qr');
        }

        $this->validateCSRF();

        $hotelId = (int) obtenerHotelIdActualCompat();
        $usuarioId = function_exists('user_id') ? (int) user_id() : 0;
        $token = (new EstanciaService())->regenerarToken($hotelId, (int) $id, $usuarioId > 0 ? $usuarioId : null);

        if ($token === null) {
            set_mensaje('No encontramos esa habitación en tu hotel.', 'error');
            $this->redirect('peticiones/qr');
        }

        set_mensaje('Nuevo QR generado. Imprímelo y pégalo en la habitación: el anterior ya no funciona.', 'success');
        $this->redirect('peticiones/qr#hab-' . (int) $id);
    }

    /** Hoja imprimible (standalone): todas, un piso o una habitacion. */
    public function imprimirAction() {
        $hotelId = (int) obtenerHotelIdActualCompat();
        $servicio = new EstanciaService();
        $usuarioId = function_exists('user_id') ? (int) user_id() : 0;
        $slug = $this->slugHotel($hotelId);

        $soloHabitacion = (int) ($_GET['habitacion'] ?? 0);
        $soloPiso = isset($_GET['piso']) && $_GET['piso'] !== '' ? (string) $_GET['piso'] : null;
        $conWifi = ($_GET['wifi'] ?? '1') !== '0';

        $tarjetas = [];
        foreach ($servicio->habitacionesConToken($hotelId, true, $usuarioId > 0 ? $usuarioId : null) as $fila) {
            if (empty($fila['token'])
                || ($soloHabitacion > 0 && (int) $fila['id'] !== $soloHabitacion)
                || ($soloPiso !== null && (string) $fila['piso'] !== $soloPiso)) {
                continue;
            }

            $link = EstanciaService::urlAbsoluta(EstanciaService::rutaPublica($slug, (string) $fila['token']));
            $tarjetas[] = [
                'numero' => (string) $fila['numero'],
                'link' => $link,
                'svg' => EstanciaService::qrSvg($link, 'QR de la habitación ' . $fila['numero']),
            ];
        }

        $branding = function_exists('hotel_branding') ? hotel_branding($hotelId) : [];

        View::renderTemplate('peticiones/imprimir', [
            'title' => 'QR de habitaciones - ' . current_hotel_display_name(),
            'tarjetas' => $tarjetas,
            'branding' => $branding,
            'nombreHotel' => (string) ($branding['nombre_visual'] ?? current_hotel_display_name()),
            'config' => $servicio->configuracion($hotelId),
            'conWifi' => $conWifi,
        ]);
    }

    // ───────────────────────── Helpers ─────────────────────────

    private function slugHotel(int $hotelId): string {
        $stmt = Database::getInstance()->query("SELECT slug FROM hoteles WHERE id = ? LIMIT 1", [$hotelId]);
        $fila = $stmt ? $stmt->fetch() : null;
        return (string) ($fila['slug'] ?? '');
    }

    private function hotelTieneQr(int $hotelId): bool {
        $stmt = Database::getInstance()->query("SELECT 1 FROM estancia_qr WHERE hotel_id = ? LIMIT 1", [$hotelId]);
        return (bool) ($stmt ? $stmt->fetch() : false);
    }
}
