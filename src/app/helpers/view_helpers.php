<?php
/**
 * Funciones Helper para Vistas - Los Cedros
 * 
 * Este archivo contiene funciones auxiliares para evitar errores comunes
 * en las vistas, especialmente con valores null en PHP 8+
 */

/**
 * Función para escapar HTML de forma segura
 * Maneja valores null sin generar errores de deprecación
 * 
 * @param mixed $value Valor a escapar
 * @param int $flags Flags para htmlspecialchars (por defecto ENT_QUOTES)
 * @param string $encoding Codificación (por defecto UTF-8)
 * @return string Valor escapado y seguro para HTML
 */
if (!function_exists('h')) {
    function h($value, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
        return htmlspecialchars($value ?? '', $flags, $encoding);
    }
}

/**
 * Función para escapar HTML de forma segura (alias largo)
 */
if (!function_exists('safe_html')) {
    function safe_html($value, $flags = ENT_QUOTES, $encoding = 'UTF-8') {
        return htmlspecialchars($value ?? '', $flags, $encoding);
    }
}

/**
 * Función para formatear números de forma segura
 * 
 * @param mixed $value Valor numérico
 * @param int $decimals Número de decimales (por defecto 2)
 * @return string Número formateado
 */
if (!function_exists('safe_number')) {
    function safe_number($value, $decimals = 2) {
        return number_format((float)($value ?? 0), $decimals);
    }
}

/**
 * Función para obtener valor de array de forma segura
 * 
 * @param array $array Array de donde obtener el valor
 * @param string $key Clave a buscar
 * @param mixed $default Valor por defecto si no existe
 * @return mixed Valor encontrado o valor por defecto
 */
if (!function_exists('safe_get')) {
    function safe_get($array, $key, $default = '') {
        if (!is_array($array)) {
            return $default;
        }
        return $array[$key] ?? $default;
    }
}

/**
 * Función para formatear fecha de forma segura
 * 
 * @param mixed $date Fecha a formatear
 * @param string $format Formato de salida
 * @param string $default Valor por defecto si la fecha es inválida
 * @return string Fecha formateada
 */
if (!function_exists('safe_date')) {
    function safe_date($date, $format = 'd/m/Y', $default = '') {
        if (empty($date)) {
            return $default;
        }
        
        $timestamp = is_numeric($date) ? $date : strtotime($date);
        if ($timestamp === false) {
            return $default;
        }
        
        return date($format, $timestamp);
    }
}

/**
 * Función para formatear hora de forma segura
 * 
 * @param mixed $time Hora a formatear
 * @param string $format Formato de salida
 * @param string $default Valor por defecto si la hora es inválida
 * @return string Hora formateada
 */
if (!function_exists('safe_time')) {
    function safe_time($time, $format = 'H:i', $default = '') {
        return safe_date($time, $format, $default);
    }
}

/**
 * Función para formatear moneda de forma segura
 * 
 * @param mixed $amount Cantidad a formatear
 * @param string $symbol Símbolo de moneda (por defecto $)
 * @param int $decimals Número de decimales (por defecto 2)
 * @return string Cantidad formateada con símbolo de moneda
 */
if (!function_exists('safe_money')) {
    function safe_money($amount, $symbol = '$', $decimals = 2) {
        $formatted = number_format((float)($amount ?? 0), $decimals);
        return $symbol . $formatted;
    }
}

/**
 * Función para verificar si un valor está vacío (incluyendo null, '', 0, false)
 * 
 * @param mixed $value Valor a verificar
 * @return bool True si está vacío, false si tiene contenido
 */
if (!function_exists('is_empty')) {
    function is_empty($value) {
        return empty($value) && $value !== '0' && $value !== 0;
    }
}

/**
 * Función para obtener valor con fallback
 * 
 * @param mixed $value Valor principal
 * @param mixed $fallback Valor de respaldo
 * @return mixed Valor principal si no está vacío, sino el fallback
 */
if (!function_exists('fallback')) {
    function fallback($value, $fallback = '') {
        return !is_empty($value) ? $value : $fallback;
    }
}

/**
 * Función para generar clases CSS condicionales
 * 
 * @param array $classes Array asociativo donde la clave es la clase y el valor es la condición
 * @return string Clases CSS concatenadas
 */
if (!function_exists('css_classes')) {
    function css_classes($classes) {
        $result = [];
        foreach ($classes as $class => $condition) {
            if (is_numeric($class)) {
                // Si la clave es numérica, el valor es la clase y siempre se incluye
                $result[] = $condition;
            } elseif ($condition) {
                // Si la condición es verdadera, incluir la clase
                $result[] = $class;
            }
        }
        return implode(' ', $result);
    }
}

/**
 * Función para truncar texto de forma segura
 * 
 * @param mixed $text Texto a truncar
 * @param int $length Longitud máxima
 * @param string $suffix Sufijo a agregar si se trunca (por defecto ...)
 * @return string Texto truncado
 */
if (!function_exists('safe_truncate')) {
    function safe_truncate($text, $length = 100, $suffix = '...') {
        $text = $text ?? '';
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        return mb_substr($text, 0, $length) . $suffix;
    }
}

/**
 * Función para verificar y obtener valor de $_POST de forma segura
 * 
 * @param string $key Clave a buscar en $_POST
 * @param mixed $default Valor por defecto
 * @return mixed Valor encontrado o valor por defecto
 */
if (!function_exists('post')) {
    function post($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
}

/**
 * Función para verificar y obtener valor de $_GET de forma segura
 * 
 * @param string $key Clave a buscar en $_GET
 * @param mixed $default Valor por defecto
 * @return mixed Valor encontrado o valor por defecto
 */
if (!function_exists('get')) {
    function get($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
}

/**
 * Función para verificar y obtener valor de $_REQUEST de forma segura
 * 
 * @param string $key Clave a buscar en $_REQUEST
 * @param mixed $default Valor por defecto
 * @return mixed Valor encontrado o valor por defecto
 */
if (!function_exists('request')) {
    function request($key, $default = null) {
        return $_REQUEST[$key] ?? $default;
    }
}
