<?php
/**
 * Catálogo de países (fuente única) para los selectores de nacionalidad y
 * procedencia internacional. Nombre en español + ISO 3166-1 alfa-2 + gentilicio
 * (femenino, concuerda con "nacionalidad") + alias de búsqueda (inglés/variantes).
 *
 * El ISO2 alimenta la bandera: ms_pais_bandera() la deriva por indicadores
 * regionales, así que no hay assets ni dependencias externas.
 */

/**
 * Catálogo completo, ordenado alfabéticamente por nombre.
 *
 * @return array<string, array{nombre: string, gentilicio: string, alias: string}>
 */
function ms_paises(): array
{
    static $paises = null;
    if ($paises !== null) {
        return $paises;
    }

    $paises = [
        'AF' => ['Afganistán', 'Afgana', 'afghanistan'],
        'AL' => ['Albania', 'Albanesa', 'albania'],
        'DE' => ['Alemania', 'Alemana', 'germany deutschland'],
        'AD' => ['Andorra', 'Andorrana', 'andorra'],
        'AO' => ['Angola', 'Angoleña', 'angola'],
        'AG' => ['Antigua y Barbuda', 'Antiguana', 'antigua barbuda'],
        'SA' => ['Arabia Saudita', 'Saudí', 'saudi arabia'],
        'DZ' => ['Argelia', 'Argelina', 'algeria'],
        'AR' => ['Argentina', 'Argentina', 'argentina'],
        'AM' => ['Armenia', 'Armenia', 'armenia'],
        'AU' => ['Australia', 'Australiana', 'australia'],
        'AT' => ['Austria', 'Austriaca', 'austria'],
        'AZ' => ['Azerbaiyán', 'Azerbaiyana', 'azerbaijan'],
        'BS' => ['Bahamas', 'Bahameña', 'bahamas'],
        'BD' => ['Bangladés', 'Bangladesí', 'bangladesh'],
        'BB' => ['Barbados', 'Barbadense', 'barbados'],
        'BH' => ['Baréin', 'Bareiní', 'bahrain'],
        'BE' => ['Bélgica', 'Belga', 'belgium belgique'],
        'BZ' => ['Belice', 'Beliceña', 'belize'],
        'BJ' => ['Benín', 'Beninesa', 'benin'],
        'BY' => ['Bielorrusia', 'Bielorrusa', 'belarus'],
        'BO' => ['Bolivia', 'Boliviana', 'bolivia'],
        'BA' => ['Bosnia y Herzegovina', 'Bosnia', 'bosnia herzegovina'],
        'BW' => ['Botsuana', 'Botsuana', 'botswana'],
        'BR' => ['Brasil', 'Brasileña', 'brazil brasil'],
        'BN' => ['Brunéi', 'Bruneana', 'brunei'],
        'BG' => ['Bulgaria', 'Búlgara', 'bulgaria'],
        'BF' => ['Burkina Faso', 'Burkinesa', 'burkina faso'],
        'BI' => ['Burundi', 'Burundesa', 'burundi'],
        'BT' => ['Bután', 'Butanesa', 'bhutan'],
        'CV' => ['Cabo Verde', 'Caboverdiana', 'cape verde cabo verde'],
        'KH' => ['Camboya', 'Camboyana', 'cambodia'],
        'CM' => ['Camerún', 'Camerunesa', 'cameroon'],
        'CA' => ['Canadá', 'Canadiense', 'canada'],
        'QA' => ['Catar', 'Catarí', 'qatar'],
        'TD' => ['Chad', 'Chadiana', 'chad'],
        'CZ' => ['Chequia', 'Checa', 'czech republic republica checa czechia'],
        'CL' => ['Chile', 'Chilena', 'chile'],
        'CN' => ['China', 'China', 'china'],
        'CY' => ['Chipre', 'Chipriota', 'cyprus'],
        'VA' => ['Ciudad del Vaticano', 'Vaticana', 'vatican holy see'],
        'CO' => ['Colombia', 'Colombiana', 'colombia'],
        'KM' => ['Comoras', 'Comorense', 'comoros'],
        'CG' => ['Congo', 'Congoleña', 'congo brazzaville'],
        'KP' => ['Corea del Norte', 'Norcoreana', 'north korea'],
        'KR' => ['Corea del Sur', 'Surcoreana', 'south korea'],
        'CI' => ['Costa de Marfil', 'Marfileña', 'ivory coast cote divoire'],
        'CR' => ['Costa Rica', 'Costarricense', 'costa rica tico'],
        'HR' => ['Croacia', 'Croata', 'croatia'],
        'CU' => ['Cuba', 'Cubana', 'cuba'],
        'DK' => ['Dinamarca', 'Danesa', 'denmark'],
        'DM' => ['Dominica', 'Dominiquesa', 'dominica'],
        'EC' => ['Ecuador', 'Ecuatoriana', 'ecuador'],
        'EG' => ['Egipto', 'Egipcia', 'egypt'],
        'SV' => ['El Salvador', 'Salvadoreña', 'el salvador'],
        'AE' => ['Emiratos Árabes Unidos', 'Emiratí', 'united arab emirates uae dubai'],
        'ER' => ['Eritrea', 'Eritrea', 'eritrea'],
        'SK' => ['Eslovaquia', 'Eslovaca', 'slovakia'],
        'SI' => ['Eslovenia', 'Eslovena', 'slovenia'],
        'ES' => ['España', 'Española', 'spain espana'],
        'US' => ['Estados Unidos', 'Estadounidense', 'united states usa eeuu america gringo'],
        'EE' => ['Estonia', 'Estonia', 'estonia'],
        'SZ' => ['Esuatini', 'Suazi', 'eswatini swaziland'],
        'ET' => ['Etiopía', 'Etíope', 'ethiopia'],
        'PH' => ['Filipinas', 'Filipina', 'philippines'],
        'FI' => ['Finlandia', 'Finlandesa', 'finland'],
        'FJ' => ['Fiyi', 'Fiyiana', 'fiji'],
        'FR' => ['Francia', 'Francesa', 'france'],
        'GA' => ['Gabón', 'Gabonesa', 'gabon'],
        'GM' => ['Gambia', 'Gambiana', 'gambia'],
        'GE' => ['Georgia', 'Georgiana', 'georgia'],
        'GH' => ['Ghana', 'Ghanesa', 'ghana'],
        'GD' => ['Granada', 'Granadina', 'grenada'],
        'GR' => ['Grecia', 'Griega', 'greece'],
        'GL' => ['Groenlandia', 'Groenlandesa', 'greenland'],
        'GT' => ['Guatemala', 'Guatemalteca', 'guatemala'],
        'GQ' => ['Guinea Ecuatorial', 'Ecuatoguineana', 'equatorial guinea'],
        'GN' => ['Guinea', 'Guineana', 'guinea'],
        'GW' => ['Guinea-Bisáu', 'Guineana', 'guinea bissau'],
        'GY' => ['Guyana', 'Guyanesa', 'guyana'],
        'HT' => ['Haití', 'Haitiana', 'haiti'],
        'HN' => ['Honduras', 'Hondureña', 'honduras'],
        'HK' => ['Hong Kong', 'Hongkonesa', 'hong kong'],
        'HU' => ['Hungría', 'Húngara', 'hungary'],
        'IN' => ['India', 'India', 'india'],
        'ID' => ['Indonesia', 'Indonesia', 'indonesia'],
        'IQ' => ['Irak', 'Iraquí', 'iraq'],
        'IR' => ['Irán', 'Iraní', 'iran'],
        'IE' => ['Irlanda', 'Irlandesa', 'ireland'],
        'IS' => ['Islandia', 'Islandesa', 'iceland'],
        'IL' => ['Israel', 'Israelí', 'israel'],
        'IT' => ['Italia', 'Italiana', 'italy italia'],
        'JM' => ['Jamaica', 'Jamaiquina', 'jamaica'],
        'JP' => ['Japón', 'Japonesa', 'japan'],
        'JO' => ['Jordania', 'Jordana', 'jordan'],
        'KZ' => ['Kazajistán', 'Kazaja', 'kazakhstan'],
        'KE' => ['Kenia', 'Keniana', 'kenya'],
        'KG' => ['Kirguistán', 'Kirguisa', 'kyrgyzstan'],
        'KI' => ['Kiribati', 'Kiribatiana', 'kiribati'],
        'KW' => ['Kuwait', 'Kuwaití', 'kuwait'],
        'LA' => ['Laos', 'Laosiana', 'laos'],
        'LS' => ['Lesoto', 'Lesotense', 'lesotho'],
        'LV' => ['Letonia', 'Letona', 'latvia'],
        'LB' => ['Líbano', 'Libanesa', 'lebanon'],
        'LR' => ['Liberia', 'Liberiana', 'liberia'],
        'LY' => ['Libia', 'Libia', 'libya'],
        'LI' => ['Liechtenstein', 'Liechtensteiniana', 'liechtenstein'],
        'LT' => ['Lituania', 'Lituana', 'lithuania'],
        'LU' => ['Luxemburgo', 'Luxemburguesa', 'luxembourg'],
        'MK' => ['Macedonia del Norte', 'Macedonia', 'north macedonia'],
        'MG' => ['Madagascar', 'Malgache', 'madagascar'],
        'MY' => ['Malasia', 'Malasia', 'malaysia'],
        'MW' => ['Malaui', 'Malauí', 'malawi'],
        'MV' => ['Maldivas', 'Maldiva', 'maldives'],
        'ML' => ['Malí', 'Maliense', 'mali'],
        'MT' => ['Malta', 'Maltesa', 'malta'],
        'MA' => ['Marruecos', 'Marroquí', 'morocco'],
        'MU' => ['Mauricio', 'Mauriciana', 'mauritius'],
        'MR' => ['Mauritania', 'Mauritana', 'mauritania'],
        'MX' => ['México', 'Mexicana', 'mexico'],
        'FM' => ['Micronesia', 'Micronesia', 'micronesia'],
        'MD' => ['Moldavia', 'Moldava', 'moldova'],
        'MC' => ['Mónaco', 'Monegasca', 'monaco'],
        'MN' => ['Mongolia', 'Mongola', 'mongolia'],
        'ME' => ['Montenegro', 'Montenegrina', 'montenegro'],
        'MZ' => ['Mozambique', 'Mozambiqueña', 'mozambique'],
        'MM' => ['Birmania', 'Birmana', 'myanmar burma'],
        'NA' => ['Namibia', 'Namibia', 'namibia'],
        'NR' => ['Nauru', 'Nauruana', 'nauru'],
        'NP' => ['Nepal', 'Nepalí', 'nepal'],
        'NI' => ['Nicaragua', 'Nicaragüense', 'nicaragua'],
        'NE' => ['Níger', 'Nigerina', 'niger'],
        'NG' => ['Nigeria', 'Nigeriana', 'nigeria'],
        'NO' => ['Noruega', 'Noruega', 'norway'],
        'NZ' => ['Nueva Zelanda', 'Neozelandesa', 'new zealand'],
        'OM' => ['Omán', 'Omaní', 'oman'],
        'NL' => ['Países Bajos', 'Neerlandesa', 'netherlands holanda holland'],
        'PK' => ['Pakistán', 'Pakistaní', 'pakistan'],
        'PW' => ['Palaos', 'Palauana', 'palau'],
        'PS' => ['Palestina', 'Palestina', 'palestine'],
        'PA' => ['Panamá', 'Panameña', 'panama'],
        'PG' => ['Papúa Nueva Guinea', 'Papú', 'papua new guinea'],
        'PY' => ['Paraguay', 'Paraguaya', 'paraguay'],
        'PE' => ['Perú', 'Peruana', 'peru'],
        'PL' => ['Polonia', 'Polaca', 'poland'],
        'PT' => ['Portugal', 'Portuguesa', 'portugal'],
        'PR' => ['Puerto Rico', 'Puertorriqueña', 'puerto rico boricua'],
        'GB' => ['Reino Unido', 'Británica', 'united kingdom uk england inglaterra gran bretana escocia gales'],
        'CF' => ['República Centroafricana', 'Centroafricana', 'central african republic'],
        'CD' => ['República Democrática del Congo', 'Congoleña', 'democratic republic congo'],
        'DO' => ['República Dominicana', 'Dominicana', 'dominican republic'],
        'RW' => ['Ruanda', 'Ruandesa', 'rwanda'],
        'RO' => ['Rumania', 'Rumana', 'romania'],
        'RU' => ['Rusia', 'Rusa', 'russia'],
        'WS' => ['Samoa', 'Samoana', 'samoa'],
        'KN' => ['San Cristóbal y Nieves', 'Sancristobaleña', 'saint kitts nevis'],
        'SM' => ['San Marino', 'Sanmarinense', 'san marino'],
        'VC' => ['San Vicente y las Granadinas', 'Sanvicentina', 'saint vincent grenadines'],
        'LC' => ['Santa Lucía', 'Santalucense', 'saint lucia'],
        'ST' => ['Santo Tomé y Príncipe', 'Santotomense', 'sao tome principe'],
        'SN' => ['Senegal', 'Senegalesa', 'senegal'],
        'RS' => ['Serbia', 'Serbia', 'serbia'],
        'SC' => ['Seychelles', 'Seychelense', 'seychelles'],
        'SL' => ['Sierra Leona', 'Sierraleonesa', 'sierra leone'],
        'SG' => ['Singapur', 'Singapurense', 'singapore'],
        'SY' => ['Siria', 'Siria', 'syria'],
        'SO' => ['Somalia', 'Somalí', 'somalia'],
        'LK' => ['Sri Lanka', 'Ceilanesa', 'sri lanka'],
        'ZA' => ['Sudáfrica', 'Sudafricana', 'south africa'],
        'SD' => ['Sudán', 'Sudanesa', 'sudan'],
        'SS' => ['Sudán del Sur', 'Sursudanesa', 'south sudan'],
        'SE' => ['Suecia', 'Sueca', 'sweden'],
        'CH' => ['Suiza', 'Suiza', 'switzerland'],
        'SR' => ['Surinam', 'Surinamesa', 'suriname'],
        'TH' => ['Tailandia', 'Tailandesa', 'thailand'],
        'TW' => ['Taiwán', 'Taiwanesa', 'taiwan'],
        'TZ' => ['Tanzania', 'Tanzana', 'tanzania'],
        'TJ' => ['Tayikistán', 'Tayika', 'tajikistan'],
        'TL' => ['Timor Oriental', 'Timorense', 'east timor'],
        'TG' => ['Togo', 'Togolesa', 'togo'],
        'TO' => ['Tonga', 'Tongana', 'tonga'],
        'TT' => ['Trinidad y Tobago', 'Trinitense', 'trinidad tobago'],
        'TN' => ['Túnez', 'Tunecina', 'tunisia'],
        'TM' => ['Turkmenistán', 'Turcomana', 'turkmenistan'],
        'TR' => ['Turquía', 'Turca', 'turkey turkiye'],
        'TV' => ['Tuvalu', 'Tuvaluana', 'tuvalu'],
        'UA' => ['Ucrania', 'Ucraniana', 'ukraine'],
        'UG' => ['Uganda', 'Ugandesa', 'uganda'],
        'UY' => ['Uruguay', 'Uruguaya', 'uruguay'],
        'UZ' => ['Uzbekistán', 'Uzbeka', 'uzbekistan'],
        'VU' => ['Vanuatu', 'Vanuatuense', 'vanuatu'],
        'VE' => ['Venezuela', 'Venezolana', 'venezuela'],
        'VN' => ['Vietnam', 'Vietnamita', 'vietnam'],
        'YE' => ['Yemen', 'Yemení', 'yemen'],
        'DJ' => ['Yibuti', 'Yibutiana', 'djibouti'],
        'ZM' => ['Zambia', 'Zambiana', 'zambia'],
        'ZW' => ['Zimbabue', 'Zimbabuense', 'zimbabwe'],
    ];

    foreach ($paises as $iso => $fila) {
        $paises[$iso] = [
            'nombre'     => $fila[0],
            'gentilicio' => $fila[1],
            'alias'      => $fila[2],
        ];
    }

    return $paises;
}

/**
 * ISO2 de los países que un hotel mexicano ve todos los días: se muestran
 * arriba de la lista para no obligar a escribir en el caso típico.
 *
 * @return string[]
 */
function ms_paises_frecuentes(): array
{
    return ['US', 'CA', 'MX', 'ES', 'FR', 'DE', 'GB', 'IT', 'AR', 'CO', 'BR'];
}

/**
 * Bandera como emoji, derivada del ISO2 (indicadores regionales U+1F1E6..).
 * Devuelve '' si el código no es un par de letras válido.
 */
function ms_pais_bandera(string $iso): string
{
    $iso = strtoupper(trim($iso));
    if (!preg_match('/^[A-Z]{2}$/', $iso)) {
        return '';
    }

    $bandera = '';
    foreach (str_split($iso) as $letra) {
        $bandera .= mb_chr(0x1F1E6 + (ord($letra) - 65), 'UTF-8');
    }

    return $bandera;
}

/**
 * Resuelve un valor guardado (nombre del país o gentilicio legacy tipo
 * "Estadounidense") contra el catálogo. Devuelve null si no hay match.
 *
 * @return array{iso: string, nombre: string, gentilicio: string}|null
 */
function ms_pais_resolver(?string $valor): ?array
{
    $valor = ms_pais_normalizar((string) $valor);
    if ($valor === '') {
        return null;
    }

    static $indice = null;
    if ($indice === null) {
        $indice = [];
        foreach (ms_paises() as $iso => $pais) {
            $indice[ms_pais_normalizar($pais['nombre'])] = $iso;
            $indice[ms_pais_normalizar($pais['gentilicio'])] = $iso;
            $indice[ms_pais_normalizar($iso)] = $iso;
        }
    }

    if (!isset($indice[$valor])) {
        return null;
    }

    $iso = $indice[$valor];
    $pais = ms_paises()[$iso];

    return ['iso' => $iso, 'nombre' => $pais['nombre'], 'gentilicio' => $pais['gentilicio']];
}

/**
 * Renderiza el <select> de países listo para ms-combo (buscador + banderas).
 * Guarda el NOMBRE del país; con data-ms-combo-free el usuario puede escribir
 * uno que no esté en el catálogo (y un valor viejo tipo "Estadounidense" se
 * conserva tal cual como opción propia).
 *
 * @param array{name?: string, id?: string, value?: string, class?: string,
 *              required?: bool, placeholder?: string, buscador?: string,
 *              extra?: string} $opciones
 */
function ms_select_paises(array $opciones = []): string
{
    $name = (string) ($opciones['name'] ?? 'pais');
    $id = (string) ($opciones['id'] ?? '');
    $valor = trim((string) ($opciones['value'] ?? ''));
    $clase = (string) ($opciones['class'] ?? '');
    $requerido = !empty($opciones['required']);
    $placeholder = (string) ($opciones['placeholder'] ?? 'Sin especificar');
    $buscador = (string) ($opciones['buscador'] ?? 'Escribe el país o la nacionalidad...');
    $extra = (string) ($opciones['extra'] ?? '');

    $e = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

    $catalogo = ms_paises();
    $resuelto = ms_pais_resolver($valor);
    $isoSeleccionado = $resuelto['iso'] ?? '';
    // Un valor guardado que ya no vive en el catálogo (o un gentilicio legacy) no se pierde.
    $valorFueraDeCatalogo = $valor !== '' && $resuelto === null;
    $seleccionado = $resuelto['nombre'] ?? $valor;

    $opcion = static function (string $iso, array $pais, bool $activo) use ($e): string {
        return '<option value="' . $e($pais['nombre']) . '"'
            . ' data-iso="' . $e($iso) . '"'
            . ' data-hint="' . $e($pais['gentilicio']) . '"'
            . ' data-search="' . $e($pais['alias']) . '"'
            . ($activo ? ' selected' : '') . '>'
            . $e($pais['nombre']) . '</option>';
    };

    $html = '<select name="' . $e($name) . '"'
        . ($id !== '' ? ' id="' . $e($id) . '"' : '')
        . ($clase !== '' ? ' class="' . $e($clase) . '"' : '')
        . ' data-ms-combo="' . $e($buscador) . '"'
        . ' data-ms-combo-free'
        . ' data-ms-combo-empty="' . $e($placeholder) . '"'
        . ($requerido ? ' required' : '')
        . ($extra !== '' ? ' ' . $extra : '')
        . '>';

    $html .= '<option value="">' . $e($placeholder) . '</option>';

    $frecuentes = array_values(array_filter(ms_paises_frecuentes(), static fn ($iso) => isset($catalogo[$iso])));
    if ($frecuentes) {
        $html .= '<optgroup label="Más frecuentes">';
        foreach ($frecuentes as $iso) {
            $html .= $opcion($iso, $catalogo[$iso], $iso === $isoSeleccionado);
        }
        $html .= '</optgroup>';
    }

    $html .= '<optgroup label="Todos los países">';
    foreach ($catalogo as $iso => $pais) {
        // Los frecuentes ya van arriba; aquí solo se repiten si no estaban.
        $activo = $iso === $isoSeleccionado && !in_array($iso, $frecuentes, true);
        $html .= $opcion($iso, $pais, $activo);
    }
    $html .= '</optgroup>';

    if ($valorFueraDeCatalogo) {
        $html .= '<option value="' . $e($seleccionado) . '" data-libre="1" data-hint="Escrito a mano" selected>'
            . $e($seleccionado) . '</option>';
    }

    return $html . '</select>';
}

/**
 * Minúsculas sin acentos ni dobles espacios: base común de búsqueda y match.
 */
function ms_pais_normalizar(string $texto): string
{
    $texto = mb_strtolower(trim($texto), 'UTF-8');
    $texto = strtr($texto, [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'ç' => 'c',
    ]);

    return preg_replace('/\s+/', ' ', $texto) ?? $texto;
}
