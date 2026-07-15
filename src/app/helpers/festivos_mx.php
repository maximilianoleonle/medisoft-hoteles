<?php
/**
 * Festivos y puentes de Mexico — helper PURO (sin tabla, sin captura).
 *
 * Cubre lo que mueve ocupacion hotelera:
 *  - Fijos: 1 ene, 1 may, 16 sep, 12 dic, 25 dic.
 *  - Puentes por ley (LFT art. 74, se corren a lunes): 5 feb -> primer lunes
 *    de febrero, 21 mar -> tercer lunes de marzo, 20 nov -> tercer lunes de
 *    noviembre.
 *  - Semana Santa: el domingo de Pascua se calcula con el computus (algoritmo
 *    de Butcher); el periodo vacacional son las 2 semanas alrededor
 *    (Domingo de Ramos a Domingo de Pascua + la semana de Pascua).
 *
 * Todas las funciones son deterministas y testeables (tests/casos/FestivosMxTest).
 */

if (!function_exists('festivos_mx_pascua')) {
    /** Domingo de Pascua del anio (computus de Butcher). Devuelve 'YYYY-MM-DD'. */
    function festivos_mx_pascua(int $anio): string
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return sprintf('%04d-%02d-%02d', $anio, $mes, $dia);
    }
}

if (!function_exists('festivos_mx_del_anio')) {
    /**
     * Festivos/puentes del anio como rangos [desde, hasta, nombre]
     * (los de un solo dia llevan desde == hasta). Orden cronologico.
     */
    function festivos_mx_del_anio(int $anio): array
    {
        $primerLunesFeb = date('Y-m-d', strtotime('first monday of february ' . $anio));
        $tercerLunesMar = date('Y-m-d', strtotime('third monday of march ' . $anio));
        $tercerLunesNov = date('Y-m-d', strtotime('third monday of november ' . $anio));

        $pascua = festivos_mx_pascua($anio);
        $ramos = date('Y-m-d', strtotime($pascua . ' -7 days'));
        $finPascua = date('Y-m-d', strtotime($pascua . ' +7 days'));

        $festivos = [
            [$anio . '-01-01', $anio . '-01-01', 'Ano Nuevo'],
            [$primerLunesFeb, $primerLunesFeb, 'Puente Dia de la Constitucion'],
            [$tercerLunesMar, $tercerLunesMar, 'Puente Natalicio de Benito Juarez'],
            [$ramos, $finPascua, 'Semana Santa y Pascua (vacaciones)'],
            [$anio . '-05-01', $anio . '-05-01', 'Dia del Trabajo'],
            [$anio . '-09-16', $anio . '-09-16', 'Dia de la Independencia'],
            [$tercerLunesNov, $tercerLunesNov, 'Puente Revolucion Mexicana'],
            [$anio . '-12-12', $anio . '-12-12', 'Dia de la Virgen de Guadalupe'],
            [$anio . '-12-25', $anio . '-12-25', 'Navidad'],
        ];

        usort($festivos, static function ($x, $y) {
            return strcmp($x[0], $y[0]);
        });

        return array_map(static function ($f) {
            return ['desde' => $f[0], 'hasta' => $f[1], 'nombre' => $f[2]];
        }, $festivos);
    }
}

if (!function_exists('festivos_mx_en_rango')) {
    /**
     * Festivos/puentes que tocan el rango [desde, hasta] (fechas 'YYYY-MM-DD',
     * inclusive; el rango puede cruzar el fin de anio). Devuelve rangos
     * ['desde','hasta','nombre'] en orden cronologico.
     */
    function festivos_mx_en_rango(string $desde, string $hasta): array
    {
        if ($desde > $hasta) {
            return [];
        }

        $resultado = [];
        for ($anio = (int) substr($desde, 0, 4); $anio <= (int) substr($hasta, 0, 4); $anio++) {
            foreach (festivos_mx_del_anio($anio) as $f) {
                if ($f['desde'] <= $hasta && $f['hasta'] >= $desde) {
                    $resultado[] = $f;
                }
            }
        }

        return $resultado;
    }
}
