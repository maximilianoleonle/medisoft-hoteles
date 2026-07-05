<?php
/**
 * Registro de adaptadores por giro (Fase 9 nomina core).
 *
 * El giro del negocio vive en la configuracion `negocio.giro` (default
 * 'hotel'). Agregar un giro nuevo = implementar NominaAdaptadorGiro y
 * registrarlo aqui: el motor de nomina NO cambia.
 *
 * Giros previstos sin adaptador aun (devuelven null con aviso en UI):
 * - restaurante: propinas, comisiones por venta, turnos partidos.
 * - academia: pagos por clase impartida, sustituciones, cancelaciones.
 * - clinica / lavanderia / otro: por definir con el negocio piloto.
 */

require_once __DIR__ . '/NominaAdaptadorGiro.php';
require_once __DIR__ . '/NominaAdaptadorHotel.php';

class NominaAdaptadorRegistry {

    public const GIROS_VALIDOS = ['hotel', 'restaurante', 'academia', 'clinica', 'lavanderia', 'otro'];

    /** Devuelve el adaptador del giro o null si el giro aun no tiene. */
    public static function paraGiro(string $giro): ?NominaAdaptadorGiro {
        switch ($giro) {
            case 'hotel':
                return new NominaAdaptadorHotel();
            default:
                return null;
        }
    }

    public static function giroDelNegocio(int $hotelId): string {
        $giro = (string) ConfiguracionHotelRegistry::get('negocio.giro', 'hotel', $hotelId);
        return in_array($giro, self::GIROS_VALIDOS, true) ? $giro : 'hotel';
    }
}
