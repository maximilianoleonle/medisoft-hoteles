<?php
/**
 * Contexto de tenant para la futura arquitectura multi-hotel.
 *
 * Fase 1: esta clase no se integra automaticamente con login, sesiones ni
 * consultas operativas. Solo centraliza el contrato que usaran fases futuras.
 */

class TenantContext
{
    private static $hotel = null;
    private static $usuarioId = null;
    private static $roles = [];
    private static $permisos = [];
    private static $hotelesDisponibles = [];
    private static $superadmin = false;

    public static function reset()
    {
        self::$hotel = null;
        self::$usuarioId = null;
        self::$roles = [];
        self::$permisos = [];
        self::$hotelesDisponibles = [];
        self::$superadmin = false;
    }

    public static function boot(array $contexto)
    {
        self::$hotel = $contexto['hotel'] ?? null;
        self::$usuarioId = $contexto['usuario_id'] ?? null;
        self::$roles = $contexto['roles'] ?? [];
        self::$permisos = $contexto['permisos'] ?? [];
        self::$hotelesDisponibles = $contexto['hoteles_disponibles'] ?? [];
        self::$superadmin = !empty($contexto['superadmin']);
    }

    public static function setHotel(array $hotel)
    {
        self::$hotel = $hotel;
    }

    public static function hotel()
    {
        return self::$hotel;
    }

    public static function hotelId()
    {
        return self::$hotel['id'] ?? null;
    }

    public static function requireHotelId()
    {
        $hotelId = self::hotelId();

        if (!$hotelId) {
            throw new RuntimeException('No hay hotel activo en TenantContext.');
        }

        return (int) $hotelId;
    }

    public static function setUsuario($usuarioId, array $roles = [], array $permisos = [])
    {
        self::$usuarioId = $usuarioId ? (int) $usuarioId : null;
        self::$roles = $roles;
        self::$permisos = $permisos;
        self::$superadmin = in_array('superadmin', $roles, true);
    }

    public static function usuarioId()
    {
        return self::$usuarioId;
    }

    public static function roles()
    {
        return self::$roles;
    }

    public static function permisos()
    {
        return self::$permisos;
    }

    public static function esSuperadmin()
    {
        return self::$superadmin;
    }

    public static function tieneRol($rol)
    {
        return self::$superadmin || in_array($rol, self::$roles, true);
    }

    public static function tienePermiso($permiso)
    {
        return self::$superadmin || in_array($permiso, self::$permisos, true);
    }

    public static function setHotelesDisponibles(array $hoteles)
    {
        self::$hotelesDisponibles = $hoteles;
    }

    public static function hotelesDisponibles()
    {
        return self::$hotelesDisponibles;
    }

    public static function puedeAccederHotel($hotelId)
    {
        if (self::$superadmin) {
            return true;
        }

        foreach (self::$hotelesDisponibles as $hotel) {
            if ((int) ($hotel['id'] ?? 0) === (int) $hotelId) {
                return true;
            }
        }

        return false;
    }

    public static function assertPuedeAccederHotel($hotelId)
    {
        if (!self::puedeAccederHotel($hotelId)) {
            throw new RuntimeException('El usuario no tiene acceso al hotel solicitado.');
        }

        return true;
    }
}
