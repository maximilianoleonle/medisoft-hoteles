<?php
/**
 * Helper Functions para Habitaciones - Los Cedros
 * Actualizado con nuevos tipos de habitación
 */

/**
 * Obtener información completa del tipo de habitación
 */
function get_tipo_habitacion_info($tipo) {
    $tipos_info = [
        'sencilla' => [
            'label' => 'Sencilla',
            'descripcion' => '1 cama matrimonial (2 personas)',
            'icon' => 'bed',
            'color' => 'blue',
            'capacidad' => 2,
            'camas' => 1
        ],
        'doble' => [
            'label' => 'Doble',
            'descripcion' => '2 camas matrimoniales (4 personas)',
            'icon' => 'bed',
            'color' => 'green',
            'capacidad' => 4,
            'camas' => 2
        ],
        'triple' => [
            'label' => 'Triple',
            'descripcion' => '3 camas matrimoniales (6 personas)',
            'icon' => 'bed',
            'color' => 'yellow',
            'capacidad' => 6,
            'camas' => 3
        ],
        'cuadruple' => [
            'label' => 'Cuádruple',
            'descripcion' => '4 camas matrimoniales (8 personas)',
            'icon' => 'crown',
            'color' => 'purple',
            'capacidad' => 8,
            'camas' => 4
        ],
        'doble_jacuzzi' => [
            'label' => 'Doble con Jacuzzi',
            'descripcion' => '2 camas matrimoniales con jacuzzi (4 personas)',
            'icon' => 'hot-tub',
            'color' => 'indigo',
            'capacidad' => 4,
            'camas' => 2
        ],
        'sencilla_jacuzzi' => [
            'label' => 'Sencilla con Jacuzzi',
            'descripcion' => '1 cama matrimonial con jacuzzi (2 personas)',
            'icon' => 'bath',
            'color' => 'cyan',
            'capacidad' => 2,
            'camas' => 1
        ]
    ];
    
    return $tipos_info[$tipo] ?? [
        'label' => ucfirst(str_replace('_', ' ', $tipo)),
        'descripcion' => 'Tipo especial',
        'icon' => 'bed',
        'color' => 'gray',
        'capacidad' => 2,
        'camas' => 1
    ];
}

/**
 * Obtener nombre del piso del Los Cedros
 */
function get_nombre_piso_san_nicolas($piso) {
    $pisos = [
        -4 => '4 niveles abajo',
        -2 => '2 niveles abajo',
        -1 => 'Un nivel abajo',
        1 => 'Nivel de piso',
        2 => '2º Nivel',
        3 => '3º Nivel'
    ];
    
    return $pisos[$piso] ?? "Piso {$piso}";
}

/**
 * Obtener icono para piso
 */
function get_icono_piso($piso) {
    if ($piso < 0) {
        return 'arrow-down'; // Sótanos
    } elseif ($piso == 1) {
        return 'home'; // Planta baja
    } else {
        return 'building'; // Pisos superiores
    }
}

/**
 * Obtener color CSS para piso
 */
function get_color_piso($piso) {
    $colores = [
        -4 => 'indigo',
        -2 => 'blue',
        -1 => 'cyan',
        1 => 'green',
        2 => 'yellow',
        3 => 'orange'
    ];
    
    return $colores[$piso] ?? 'gray';
}

/**
 * Parsear características especiales del Los Cedros
 */
function parse_caracteristicas_san_nicolas($caracteristicas) {
    $texto = strtolower($caracteristicas);
    $especiales = [];
    
    $caracteristicas_especiales = [
        'pantalla' => [
            'icon' => 'tv',
            'label' => 'Pantalla',
            'color' => 'purple',
            'keywords' => ['pantalla']
        ],
        'balcon' => [
            'icon' => 'home',
            'label' => 'Balcón',
            'color' => 'green',
            'keywords' => ['balcón', 'balcon']
        ],
        'jacuzzi' => [
            'icon' => 'bath',
            'label' => 'Jacuzzi',
            'color' => 'blue',
            'keywords' => ['jacuzzi']
        ],
        'amplia' => [
            'icon' => 'expand-arrows-alt',
            'label' => 'Más Amplia',
            'color' => 'yellow',
            'keywords' => ['más amplia', 'amplia', 'grande']
        ]
    ];
    
    foreach ($caracteristicas_especiales as $key => $especial) {
        foreach ($especial['keywords'] as $keyword) {
            if (strpos($texto, $keyword) !== false) {
                $especiales[$key] = $especial;
                break;
            }
        }
    }
    
    return $especiales;
}

/**
 * Generar badge HTML para característica especial
 */
function caracteristica_especial_badge($caracteristica, $info) {
    return sprintf(
        '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-%s-100 text-%s-800">
            <i class="fas fa-%s mr-1"></i>%s
        </span>',
        $info['color'],
        $info['color'],
        $info['icon'],
        $info['label']
    );
}

/**
 * Obtener rango de precios recomendado por tipo - ACTUALIZADO
 */
function get_rango_precio_tipo($tipo) {
    $rangos = [
        'sencilla' => ['min' => 550, 'max' => 550, 'promedio' => 550],
        'doble' => ['min' => 700, 'max' => 1000, 'promedio' => 850],
        'triple' => ['min' => 1100, 'max' => 1200, 'promedio' => 1150],
        'cuadruple' => ['min' => 1400, 'max' => 1400, 'promedio' => 1400],
        'doble_jacuzzi' => ['min' => 1600, 'max' => 1600, 'promedio' => 1600],
        'sencilla_jacuzzi' => ['min' => 1000, 'max' => 1000, 'promedio' => 1000]
    ];
    
    return $rangos[$tipo] ?? ['min' => 500, 'max' => 2000, 'promedio' => 1000];
}

/**
 * Verificar si el precio está en rango para el tipo
 */
function precio_en_rango($precio, $tipo) {
    $rango = get_rango_precio_tipo($tipo);
    return $precio >= $rango['min'] && $precio <= ($rango['max'] + 500); // +500 de tolerancia
}

/**
 * Obtener estadísticas de habitaciones del Los Cedros
 */
function get_estadisticas_hotel_san_nicolas() {
    $habitacionModel = new Habitacion();
    $estadisticas = $habitacionModel->estadisticas();
    
    return [
        'total' => $estadisticas['total'] ?? 0,
        'por_estado' => [
            'disponible' => $estadisticas['disponibles'] ?? 0,
            'ocupada' => $estadisticas['ocupadas'] ?? 0,
            'mantenimiento' => $estadisticas['mantenimiento'] ?? 0,
            'limpieza' => $estadisticas['limpieza'] ?? 0
        ],
        'por_tipo' => [
            'sencilla' => $estadisticas['sencillas'] ?? 0,
            'doble' => $estadisticas['dobles'] ?? 0,
            'triple' => $estadisticas['triples'] ?? 0,
            'cuadruple' => $estadisticas['cuadruples'] ?? 0,
            'doble_jacuzzi' => $estadisticas['dobles_jacuzzi'] ?? 0,
            'sencilla_jacuzzi' => $estadisticas['sencillas_jacuzzi'] ?? 0
        ],
        'especiales' => [
            'con_jacuzzi' => $estadisticas['con_jacuzzi'] ?? 0,
            'con_pantalla' => $estadisticas['con_pantalla'] ?? 0,
            'con_balcon' => $estadisticas['con_balcon'] ?? 0
        ],
        'precios' => [
            'minimo' => $estadisticas['precio_minimo'] ?? 0,
            'maximo' => $estadisticas['precio_maximo'] ?? 0,
            'promedio' => $estadisticas['precio_promedio'] ?? 0
        ]
    ];
}

/**
 * Generar descripción automática de habitación - ACTUALIZADA
 */
function generar_descripcion_habitacion($tipo, $caracteristicas_especiales = []) {
    $camas_info = [
        'sencilla' => '1 cama matrimonial',
        'doble' => '2 camas matrimoniales',
        'triple' => '3 camas matrimoniales',
        'cuadruple' => '4 camas matrimoniales',
        'doble_jacuzzi' => '2 camas matrimoniales',
        'sencilla_jacuzzi' => '1 cama matrimonial'
    ];
    
    $descripcion = [$camas_info[$tipo] ?? ''];
    
    // Si el tipo ya incluye jacuzzi, agregarlo automáticamente
    if (strpos($tipo, 'jacuzzi') !== false) {
        $descripcion[] = 'jacuzzi';
    }
    
    // Características especiales
    $especiales_labels = [
        'pantalla' => 'pantalla',
        'balcon' => 'balcón',
        'jacuzzi' => 'jacuzzi',
        'amplia' => 'habitación más amplia'
    ];
    
    // Agregar especiales en orden de importancia
    $orden_especiales = ['jacuzzi', 'pantalla', 'balcon', 'amplia'];
    foreach ($orden_especiales as $especial) {
        if (in_array($especial, $caracteristicas_especiales)) {
            // No duplicar jacuzzi si ya está en el tipo
            if ($especial === 'jacuzzi' && strpos($tipo, 'jacuzzi') !== false) {
                continue;
            }
            $descripcion[] = $especiales_labels[$especial];
        }
    }
    
    // Características base del hotel
    $base = ['baño', 'ventilador', 'agua caliente', 'Wifi', 'Cablevisión', 'estacionamiento'];
    
    // TV según si tiene pantalla
    if (in_array('pantalla', $caracteristicas_especiales)) {
        // Ya se agregó pantalla, no agregar TV
    } else {
        array_unshift($base, 'TV normal');
    }
    
    $descripcion = array_merge($descripcion, $base);
    
    return implode(', ', $descripcion);
}

/**
 * Verificar capacidad del hotel
 */
function verificar_capacidad_hotel() {
    $stats = get_estadisticas_hotel_san_nicolas();
    
    return [
        'total_habitaciones' => 66, // Los Cedros tiene 66 habitaciones
        'habitaciones_registradas' => $stats['total'],
        'faltantes' => 66 - $stats['total'],
        'porcentaje_completo' => round(($stats['total'] / 66) * 100, 1)
    ];
}

/**
 * Obtener distribución por pisos
 */
function get_distribucion_pisos() {
    $habitacionModel = new Habitacion();
    $estadisticas_pisos = $habitacionModel->estadisticasPorPiso();
    
    $distribucion = [];
    foreach ($estadisticas_pisos as $piso_data) {
        $distribucion[$piso_data['piso']] = [
            'nombre' => $piso_data['nombre_piso'],
            'total' => $piso_data['total'],
            'disponibles' => $piso_data['disponibles'],
            'ocupadas' => $piso_data['ocupadas'],
            'precio_promedio' => $piso_data['precio_promedio']
        ];
    }
    
    return $distribucion;
}

/**
 * Formato de precio mexicano
 */
function format_precio_mexicano($precio) {
    return '$' . number_format($precio, 0, '.', ',') . ' MXN';
}

/**
 * Obtener clase CSS para estado de habitación (actualizada)
 */
function get_estado_habitacion_class($estado) {
    $clases = [
        'disponible' => 'bg-green-50 border-green-200 hover:border-green-400 text-green-800',
        'ocupada' => 'bg-red-50 border-red-200 hover:border-red-400 text-red-800',
        'mantenimiento' => 'bg-yellow-50 border-yellow-200 hover:border-yellow-400 text-yellow-800',
        'limpieza' => 'bg-blue-50 border-blue-200 hover:border-blue-400 text-blue-800'
    ];
    
    return $clases[$estado] ?? 'bg-gray-50 border-gray-200 text-gray-800';
}

/**
 * Generar badge para tipo de habitación
 */
function tipo_habitacion_badge($tipo) {
    $info = get_tipo_habitacion_info($tipo);
    
    return sprintf(
        '<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-%s-100 text-%s-800">
            <i class="fas fa-%s mr-2"></i>%s
        </span>',
        $info['color'],
        $info['color'],
        $info['icon'],
        $info['label']
    );
}

/**
 * Validar si una habitación puede cambiar de estado
 */
function puede_cambiar_estado_habitacion($estado_actual, $nuevo_estado) {
    $transiciones_permitidas = [
        'disponible' => ['ocupada', 'mantenimiento', 'limpieza'],
        'ocupada' => ['limpieza'],
        'mantenimiento' => ['disponible'],
        'limpieza' => ['disponible', 'mantenimiento']
    ];
    
    return isset($transiciones_permitidas[$estado_actual]) && 
           in_array($nuevo_estado, $transiciones_permitidas[$estado_actual]);
}

?>