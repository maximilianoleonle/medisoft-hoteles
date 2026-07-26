<?php
/**
 * Presentador del feed de Caja ("Movimientos del turno").
 *
 * Hace dos cosas que la vista sola no puede hacer bien:
 *   1) Traduce cada fila de movimientos_caja al idioma de recepcion
 *      (categoria/descripcion crudas -> titulo humano + contexto).
 *   2) EMPAREJA cada cancelacion con el movimiento que anula. Sin esto una
 *      devolucion se lee como un gasto suelto y el turno queda como un
 *      revoltijo: entro $7,000 y mas abajo salieron $7,000 sin relacion visible.
 *
 * TODO aqui es estatico y puro (sin BD, sin sesion, sin helpers de la app) para
 * que la suite CajaFeedTest.php lo cubra sin levantar la aplicacion.
 *
 * CONTRATO con el dinero: esta clase NO calcula totales ni reclasifica montos.
 * Las cifras del corte las sigue derivando Caja::obtenerResumenCaja(). Lo unico
 * que se exige es que toda categoria que el modelo cuenta como "reverso"
 * (Caja::condicionReversoIngresoSql) tambien salga aqui como cancelacion, o el
 * panel "Dinero devuelto" y el feed se contradirian.
 */
class CajaMovimientosFeed
{
    /** Flujos posibles de una fila del feed. */
    public const ENTRADA = 'entrada';
    public const SALIDA  = 'salida';

    /**
     * Catalogo de categorias del sistema.
     * clave normalizada => [titulo humano, es_cancelacion, icono]
     */
    private static function catalogo(): array
    {
        return [
            // ── Operacion normal ──
            'hospedaje'                => ['Pago de hospedaje',          false, 'fas fa-bed'],
            'anticipo reservacion'     => ['Anticipo de reservación',    false, 'fas fa-hand-holding-usd'],
            'anticipo'                 => ['Anticipo de reservación',    false, 'fas fa-hand-holding-usd'],
            'cobro cxc'                => ['Cobro de cuenta pendiente',  false, 'fas fa-file-invoice-dollar'],
            'pago proveedor'           => ['Pago a proveedor',           false, 'fas fa-truck'],
            'pago laboral'             => ['Pago al personal',           false, 'fas fa-users'],
            'lavanderia'               => ['Lavandería',                 false, 'fas fa-soap'],
            // ── Cancelaciones (deshacen otro movimiento) ──
            'devoluciones'             => ['Devolución al huésped',      true,  'fas fa-rotate-left'],
            'devolucion'               => ['Devolución al huésped',      true,  'fas fa-rotate-left'],
            'reverso anticipo'         => ['Anticipo cancelado',         true,  'fas fa-rotate-left'],
            'reverso de anticipo'      => ['Anticipo cancelado',         true,  'fas fa-rotate-left'],
            'reversion cobro cxc'      => ['Cobro cancelado',            true,  'fas fa-rotate-left'],
            'reversion pago proveedor' => ['Pago a proveedor cancelado', true,  'fas fa-rotate-left'],
            'reversion pago laboral'   => ['Pago al personal cancelado', true,  'fas fa-rotate-left'],
        ];
    }

    /**
     * Frases crudas que el usuario nunca deberia leer, con su version humana.
     * Se aplica a descripciones y nombres de concepto.
     */
    public static function humanizar($texto): string
    {
        $texto = trim((string)($texto ?? ''));
        if ($texto === '') {
            return '';
        }

        return str_ireplace(
            [
                'Reversion Cobro CxC', 'Reversion Pago proveedor', 'Reversion Pago laboral',
                'Reverso de anticipo', 'Reverso anticipo',
                'Cobro CxC', 'Anticipo reservacion', 'Devoluciones',
                'CxC', 'CXC', 'CxP', 'CXP',
            ],
            [
                'Cobro cancelado', 'Pago a proveedor cancelado', 'Pago al personal cancelado',
                'Anticipo cancelado', 'Anticipo cancelado',
                'Cobro de cuenta pendiente', 'Anticipo de reservación', 'Dinero devuelto',
                'cuenta pendiente', 'cuenta pendiente', 'cuenta por pagar', 'cuenta por pagar',
            ],
            $texto
        );
    }

    /**
     * Nombre humano de un concepto suelto (para los paneles por categoria).
     */
    public static function etiquetaConcepto($valor): string
    {
        $texto = trim((string)($valor ?? ''));
        if ($texto === '') {
            return 'Sin concepto';
        }

        $catalogo = self::catalogo();
        $clave = self::clave($texto);
        if (isset($catalogo[$clave])) {
            return $catalogo[$clave][0];
        }

        return self::humanizar($texto);
    }

    /**
     * Nombre del concepto de UN movimiento, con toda la cadena de respaldo:
     * catalogo del hotel (`categorias_movimientos`) -> concepto del sistema
     * (texto en `movimientos_caja.categoria`) -> "Sin concepto".
     *
     * Los movimientos que crean los SERVICIOS (anticipos, cobros CxC, pagos a
     * personal/proveedor y TODOS los reversos) guardan `categoria_id = NULL` y
     * solo el texto: leer nada mas el JOIN los pintaba "Sin categoria" (queja
     * real jul-25). Cualquier pantalla que muestre el concepto pasa por aqui.
     */
    public static function concepto(array $mov): string
    {
        foreach ([$mov['categoria_nombre'] ?? '', $mov['categoria'] ?? ''] as $valor) {
            if (trim((string)$valor) !== '') {
                return self::etiquetaConcepto($valor);
            }
        }

        return 'Sin concepto';
    }

    /**
     * ¿Este movimiento deshace a otro? (devolucion, reverso, reversion)
     */
    public static function esCancelacion(array $mov): bool
    {
        return (bool)self::resolverCatalogo($mov)[1];
    }

    /**
     * Traduce UN movimiento a la fila que ve el usuario.
     *
     * @return array{flujo:string,es_cancelacion:bool,titulo:string,contexto:string,
     *               detalle_extra:string,etiqueta:string,icono:string,signo:string,monto:float}
     */
    public static function clasificar(array $mov): array
    {
        $esIngreso = self::clave($mov['tipo'] ?? '') === 'ingreso';
        [$titulo, $esCancelacion, $icono] = self::resolverCatalogo($mov);

        $descripcion = self::humanizar((string)($mov['descripcion'] ?? ''));
        $refs = self::referencias($mov);

        if ($titulo === '') {
            // Concepto libre del hotel: manda lo que escribio la persona.
            $titulo = $descripcion !== '' ? $descripcion : 'Movimiento de caja';
            $concepto = self::etiquetaConcepto($mov['categoria_nombre'] ?? ($mov['categoria'] ?? ''));
            $contexto = self::unir(array_merge(
                $concepto !== 'Sin concepto' && !self::yaMencionado($concepto, [$titulo]) ? [$concepto] : [],
                $refs
            ));
        } else {
            // Categoria del sistema: el titulo ya dice el que; el contexto, el quien.
            $contexto = $refs ? self::unir($refs) : self::restoUtil($descripcion, $titulo, $mov);
        }

        // Texto original del movimiento, solo si dice algo que el titulo no dice
        // (el libro de movimientos lo muestra; el panel se queda con el titulo).
        $cruda = trim((string)($mov['descripcion'] ?? ''));
        $extra = ($cruda === '' || self::yaMencionado($titulo, [$cruda])) ? '' : $cruda;

        return [
            'flujo'          => $esIngreso ? self::ENTRADA : self::SALIDA,
            'es_cancelacion' => $esCancelacion,
            'titulo'         => $titulo,
            'contexto'       => $contexto,
            'detalle_extra'  => $extra,
            'etiqueta'       => $esCancelacion ? 'Cancelación' : ($esIngreso ? 'Entrada' : 'Salida'),
            'icono'          => $icono,
            'signo'          => $esIngreso ? '+' : '-',
            'monto'          => round((float)($mov['monto'] ?? 0), 2),
        ];
    }

    /**
     * Empareja cancelaciones con el movimiento que anulan.
     *
     * Devuelve la MISMA lista (mismo orden, mismas claves) con dos campos extra:
     *   'anulado_por' => [id, hora, monto]  en el movimiento original
     *   'anula'       => [id, hora, titulo, monto] en la cancelacion
     *
     * No hay columna que ligue ambos en la BD, asi que se resuelve por evidencia,
     * de la mas fuerte a la mas debil (ver puntaje() ). Es informacion de PANTALLA:
     * un empate mal resuelto no mueve un solo peso.
     */
    public static function emparejar(array $movimientos): array
    {
        $filas = array_values($movimientos);
        $total = count($filas);
        if ($total < 2) {
            return $filas;
        }

        // Escaneo cronologico ascendente: la cancelacion mas vieja elige primero.
        $orden = range(0, $total - 1);
        usort($orden, static function ($a, $b) use ($filas) {
            $cmp = strcmp((string)($filas[$a]['created_at'] ?? ''), (string)($filas[$b]['created_at'] ?? ''));
            return $cmp !== 0 ? $cmp : ((int)($filas[$a]['id'] ?? 0) <=> (int)($filas[$b]['id'] ?? 0));
        });

        $tomados = [];

        foreach ($orden as $i) {
            $cancelacion = $filas[$i];
            if (!self::esCancelacion($cancelacion)) {
                continue;
            }

            $mejor = null;
            $mejorPuntaje = 0;

            foreach ($orden as $j) {
                if ($j === $i || isset($tomados[$j])) {
                    continue;
                }
                $candidato = $filas[$j];
                if (self::esCancelacion($candidato)) {
                    continue; // una cancelacion no cancela a otra cancelacion
                }
                if (!self::flujosOpuestos($cancelacion, $candidato)) {
                    continue;
                }
                if (!self::mismoMonto($cancelacion, $candidato)) {
                    continue;
                }
                if (strcmp((string)($candidato['created_at'] ?? ''), (string)($cancelacion['created_at'] ?? '')) > 0) {
                    continue; // el original nunca es posterior a su cancelacion
                }

                $puntaje = self::puntaje($cancelacion, $candidato);
                if ($puntaje > 0 && $puntaje >= $mejorPuntaje) {
                    // A igual evidencia gana el mas cercano en el tiempo (el ultimo).
                    $mejor = $j;
                    $mejorPuntaje = $puntaje;
                }
            }

            if ($mejor === null) {
                continue;
            }

            $tomados[$mejor] = true;
            $original = $filas[$mejor];

            $filas[$mejor]['anulado_por'] = [
                'id'    => (int)($cancelacion['id'] ?? 0),
                'hora'  => self::hora($cancelacion['created_at'] ?? null),
                'monto' => round((float)($cancelacion['monto'] ?? 0), 2),
            ];
            $filas[$i]['anula'] = [
                'id'     => (int)($original['id'] ?? 0),
                'hora'   => self::hora($original['created_at'] ?? null),
                'titulo' => self::clasificar($original)['titulo'],
                'monto'  => round((float)($original['monto'] ?? 0), 2),
            ];
        }

        return $filas;
    }

    /**
     * Feed completo listo para pintar: empareja, traduce y agrupa por turno.
     *
     * @param array  $movimientos    filas crudas (orden libre; se ordena desc aqui)
     * @param int    $corteActualId  corte abierto (su grupo va expandido)
     * @param string $hoy            'Y-m-d' de referencia (inyectable para tests)
     */
    public static function agrupar(array $movimientos, int $corteActualId = 0, string $hoy = ''): array
    {
        $hoy = $hoy !== '' ? $hoy : date('Y-m-d');
        $filas = self::emparejar($movimientos);

        usort($filas, static function ($a, $b) {
            $cmp = strcmp((string)($b['created_at'] ?? ''), (string)($a['created_at'] ?? ''));
            return $cmp !== 0 ? $cmp : ((int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
        });

        $grupos = [];

        foreach ($filas as $mov) {
            $corteId = (int)($mov['corte_id'] ?? 0);
            $clave = $corteId > 0 ? 'c' . $corteId : 'sin-corte';
            $esActual = $corteId > 0 && $corteId === $corteActualId;

            if (!isset($grupos[$clave])) {
                $grupos[$clave] = [
                    'corte_id'      => $corteId,
                    'actual'        => $esActual,
                    'titulo'        => $corteId > 0 ? 'Corte #' . $corteId : 'Sin turno asignado',
                    'eyebrow'       => $esActual ? 'Turno abierto ahora' : ($corteId > 0 ? 'Turno anterior' : 'Fuera de turno'),
                    'chip'          => $esActual ? 'En curso' : ($corteId > 0 ? 'Cerrado' : 'Sin turno'),
                    'colapsado'     => !$esActual,
                    'fecha'         => '',
                    'fecha_texto'   => '',
                    'movimientos'   => [],
                    'entradas'      => ['cantidad' => 0, 'total' => 0.0],
                    'salidas'       => ['cantidad' => 0, 'total' => 0.0],
                    'cancelaciones' => ['cantidad' => 0, 'total' => 0.0],
                ];
            }

            $fila = self::clasificar($mov);
            $fila['id'] = (int)($mov['id'] ?? 0);
            $fila['hora'] = self::hora($mov['created_at'] ?? null);
            $fila['metodo'] = self::metodo($mov['metodo_pago'] ?? '');
            $fila['usuario'] = trim((string)($mov['usuario_nombre'] ?? ''));
            $fila['reservacion_id'] = (int)($mov['reservacion_id'] ?? 0);
            $fila['trabajador_id'] = (int)($mov['trabajador_id'] ?? 0);
            $fila['descripcion_original'] = trim((string)($mov['descripcion'] ?? ''));
            $fila['anulado'] = !empty($mov['anulado_por']);
            $fila['anulado_por'] = $mov['anulado_por'] ?? null;
            $fila['anula'] = $mov['anula'] ?? null;
            $fila['nota'] = self::nota($fila);

            $grupos[$clave]['movimientos'][] = $fila;

            $bucket = $fila['es_cancelacion']
                ? 'cancelaciones'
                : ($fila['flujo'] === self::ENTRADA ? 'entradas' : 'salidas');
            $grupos[$clave][$bucket]['cantidad']++;
            $grupos[$clave][$bucket]['total'] += $fila['monto'];

            $fecha = substr((string)($mov['created_at'] ?? ''), 0, 10);
            if ($fecha !== '' && $grupos[$clave]['fecha'] === '') {
                $grupos[$clave]['fecha'] = $fecha;
                $grupos[$clave]['fecha_texto'] = self::fechaTexto($fecha, $hoy);
            }
        }

        return array_values($grupos);
    }

    /**
     * Frase del par para un movimiento YA pasado por emparejar() (la usa el
     * libro de movimientos, que pinta filas sueltas y no grupos por turno).
     */
    public static function notaDePar(array $mov): string
    {
        $fila = self::clasificar($mov);
        $fila['anulado_por'] = $mov['anulado_por'] ?? null;
        $fila['anula'] = $mov['anula'] ?? null;

        return self::nota($fila);
    }

    // ─────────────────────────── internos ───────────────────────────

    /**
     * Frase que explica la fila cuando forma parte de un par cancelado.
     */
    private static function nota(array $fila): string
    {
        if (!empty($fila['anulado_por'])) {
            return 'Se canceló a las ' . $fila['anulado_por']['hora'] . ': este dinero ya no cuenta.';
        }

        if ($fila['es_cancelacion'] && !empty($fila['anula'])) {
            $verbo = $fila['flujo'] === self::SALIDA ? 'Devuelve' : 'Recupera';
            return $verbo . ' el movimiento de las ' . $fila['anula']['hora'] . ' (' . $fila['anula']['titulo'] . ').';
        }

        if ($fila['es_cancelacion']) {
            return $fila['flujo'] === self::SALIDA
                ? 'Sale dinero porque se canceló un cobro anterior.'
                : 'Regresa dinero porque se canceló un pago anterior.';
        }

        return '';
    }

    /**
     * @return array{0:string,1:bool,2:string} [titulo|'', es_cancelacion, icono]
     */
    private static function resolverCatalogo(array $mov): array
    {
        $catalogo = self::catalogo();
        $esIngreso = self::clave($mov['tipo'] ?? '') === 'ingreso';
        $iconoPropio = self::iconoClase($mov['categoria_icono'] ?? '');

        foreach ([$mov['categoria'] ?? '', $mov['categoria_nombre'] ?? ''] as $valor) {
            $clave = self::clave($valor);
            if ($clave === '') {
                continue;
            }
            if (isset($catalogo[$clave])) {
                [$titulo, $cancela, $icono] = $catalogo[$clave];
                return [$titulo, $cancela, $cancela ? $icono : ($iconoPropio ?: $icono)];
            }
            // Prefijos: 'Devoluciones por cancelacion', 'Reverso anticipo #12'...
            // Solo si termina en frontera de palabra, o un concepto propio del
            // hotel ('Anticipos de eventos') heredaria el titulo equivocado.
            foreach (self::clavesPorLargo($catalogo) as $prefijo) {
                if (strpos($clave, $prefijo) === 0 && self::fronteraDePalabra($clave, strlen($prefijo))) {
                    [$titulo, $cancela, $icono] = $catalogo[$prefijo];
                    return [$titulo, $cancela, $cancela ? $icono : ($iconoPropio ?: $icono)];
                }
            }
        }

        // Red de seguridad: mismos patrones de descripcion que usa el modelo para
        // contar reversos (Caja::condicionReversoIngresoSql).
        $desc = self::clave($mov['descripcion'] ?? '');
        if (!$esIngreso && (strpos($desc, 'reverso de anticipo') === 0 || strpos($desc, 'reversion cobro cxc') === 0)) {
            return ['Cobro cancelado', true, 'fas fa-rotate-left'];
        }

        return ['', false, $iconoPropio ?: ($esIngreso ? 'fas fa-plus' : 'fas fa-minus')];
    }

    /** ¿La posicion $pos corta la cadena en frontera de palabra (fin, espacio o signo)? */
    private static function fronteraDePalabra(string $texto, int $pos): bool
    {
        if ($pos >= strlen($texto)) {
            return true;
        }
        return !ctype_alnum($texto[$pos]);
    }

    /** Claves del catalogo de mas larga a mas corta (para que gane la mas especifica). */
    private static function clavesPorLargo(array $catalogo): array
    {
        $claves = array_keys($catalogo);
        usort($claves, static fn($a, $b) => strlen($b) <=> strlen($a));
        return $claves;
    }

    /**
     * Evidencia que liga una cancelacion con su original. Mas alto = mas seguro.
     */
    private static function puntaje(array $cancelacion, array $original): int
    {
        $refCancelacion = trim((string)($cancelacion['referencia'] ?? ''));
        $refOriginal = trim((string)($original['referencia'] ?? ''));

        // 3 · Referencia canonica: la reversion es 'REV-' + la del original (CxC/CxP).
        if ($refCancelacion !== '' && $refOriginal !== ''
            && strcasecmp($refCancelacion, 'REV-' . $refOriginal) === 0) {
            return 3;
        }

        $mismoMetodo = self::clave($cancelacion['metodo_pago'] ?? '') === self::clave($original['metodo_pago'] ?? '');
        $reservacion = (int)($cancelacion['reservacion_id'] ?? 0);
        $mismaReservacion = $reservacion > 0 && $reservacion === (int)($original['reservacion_id'] ?? 0);

        $proveedor = self::clave($cancelacion['proveedor'] ?? '');
        $mismoProveedor = $proveedor !== '' && $proveedor === self::clave($original['proveedor'] ?? '');

        // 2 · Misma reservacion/proveedor + mismo metodo (asi las genera el sistema).
        if (($mismaReservacion || $mismoProveedor) && $mismoMetodo) {
            return 2;
        }

        // 1 · Misma reservacion/proveedor con metodo distinto (pago mixto reacomodado).
        if ($mismaReservacion || $mismoProveedor) {
            return 1;
        }

        return 0;
    }

    private static function flujosOpuestos(array $a, array $b): bool
    {
        return self::clave($a['tipo'] ?? '') !== self::clave($b['tipo'] ?? '');
    }

    private static function mismoMonto(array $a, array $b): bool
    {
        return abs(round((float)($a['monto'] ?? 0), 2) - round((float)($b['monto'] ?? 0), 2)) < 0.005;
    }

    /** Referencias estructuradas del movimiento (a quien/que pertenece). */
    private static function referencias(array $mov): array
    {
        $refs = [];

        $reservacion = (int)($mov['reservacion_id'] ?? 0);
        if ($reservacion > 0) {
            $refs[] = 'Reservación #' . $reservacion;
        }

        $habitaciones = self::habitaciones($mov['habitaciones_detalle'] ?? '');
        if ($habitaciones !== '') {
            $refs[] = $habitaciones;
        }

        $proveedor = trim((string)($mov['proveedor'] ?? ''));
        if ($proveedor !== '') {
            // 'Trabajador: Juan Perez' -> 'Juan Perez'
            $proveedor = trim(preg_replace('/^(trabajador|proveedor)\s*:\s*/i', '', $proveedor));
            if ($proveedor !== '') {
                $refs[] = $proveedor;
            }
        }

        return $refs;
    }

    /** 'Hab. 12 - suite, Hab. 13 - doble' -> 'Hab. 12 y 13' */
    private static function habitaciones($valor): string
    {
        $texto = trim((string)($valor ?? ''));
        if ($texto === '') {
            return '';
        }

        $numeros = [];
        foreach (explode(',', $texto) as $parte) {
            if (preg_match('/hab\.?\s*([^\s-]+)/i', trim($parte), $m)) {
                $numeros[] = $m[1];
            }
        }
        $numeros = array_values(array_unique($numeros));
        if (!$numeros) {
            return '';
        }
        if (count($numeros) === 1) {
            return 'Hab. ' . $numeros[0];
        }
        if (count($numeros) <= 3) {
            $ultimo = array_pop($numeros);
            return 'Hab. ' . implode(', ', $numeros) . ' y ' . $ultimo;
        }

        return count($numeros) . ' habitaciones';
    }

    /**
     * Lo que queda de la descripcion cuando el titulo ya dijo el concepto.
     * Se limpia lo que ya viaja en otras columnas (reservacion, metodo, id).
     */
    private static function restoUtil(string $descripcion, string $titulo, array $mov): string
    {
        $resto = $descripcion;
        if ($titulo !== '' && stripos($resto, $titulo) === 0) {
            $resto = substr($resto, strlen($titulo));
        }
        $categoria = trim((string)($mov['categoria'] ?? ''));
        if ($categoria !== '' && stripos($resto, $categoria) === 0) {
            $resto = substr($resto, strlen($categoria));
        }

        $resto = preg_replace('/\((efectivo|tarjeta|transferencia)\)/iu', '', $resto);
        $resto = preg_replace('/(reservaci[oó]n|reserva)\s*#?\s*\d+/iu', '', $resto);
        $resto = preg_replace('/\bmov\.?\s*#?\s*\d+/iu', '', $resto);
        $resto = preg_replace('/\s+/u', ' ', (string)$resto);
        $resto = trim((string)$resto, " \t-–—·:,;#");
        $resto = preg_replace('/^#?\d+\s*[-–—·:]?\s*/u', '', $resto);
        $resto = trim((string)$resto, " \t-–—·:,;");

        // Si ya no queda nada con letras, no vale la pena mostrarlo.
        return preg_match('/\p{L}{3,}/u', $resto) ? $resto : '';
    }

    private static function yaMencionado(string $texto, array $partes): bool
    {
        $clave = self::clave($texto);
        if ($clave === '') {
            return true;
        }
        foreach ($partes as $parte) {
            $otra = self::clave($parte);
            if ($otra !== '' && (strpos($otra, $clave) !== false || strpos($clave, $otra) !== false)) {
                return true;
            }
        }
        return false;
    }

    private static function unir(array $partes): string
    {
        $partes = array_values(array_filter(array_map('trim', $partes), static fn($p) => $p !== ''));
        return implode(' · ', array_slice($partes, 0, 3));
    }

    private static function metodo($valor): string
    {
        $clave = self::clave($valor);
        $etiquetas = [
            'efectivo'      => 'Efectivo',
            'tarjeta'       => 'Tarjeta',
            'transferencia' => 'Transferencia',
        ];
        return $etiquetas[$clave] ?? ($clave !== '' ? ucfirst($clave) : '');
    }

    private static function hora($valor): string
    {
        $texto = trim((string)($valor ?? ''));
        if ($texto === '') {
            return '';
        }
        $ts = strtotime($texto);
        return $ts ? date('H:i', $ts) : '';
    }

    private static function fechaTexto(string $fecha, string $hoy): string
    {
        if ($fecha === '') {
            return '';
        }
        if ($fecha === $hoy) {
            return 'Hoy';
        }
        if ($fecha === date('Y-m-d', strtotime($hoy . ' -1 day'))) {
            return 'Ayer';
        }

        $ts = strtotime($fecha);
        if (!$ts) {
            return $fecha;
        }
        $meses = ['', 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
                  'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
        return (int)date('j', $ts) . ' de ' . $meses[(int)date('n', $ts)];
    }

    /** Normaliza el icono guardado ('fas fa-undo' o 'undo') a clase usable. */
    private static function iconoClase($valor): string
    {
        $texto = trim((string)($valor ?? ''));
        if ($texto === '') {
            return '';
        }
        if (strpos($texto, 'fa-') !== false) {
            return strpos($texto, ' ') !== false ? $texto : 'fas ' . $texto;
        }
        return 'fas fa-' . $texto;
    }

    /** minusculas, sin acentos, espacios colapsados. */
    private static function clave($valor): string
    {
        $texto = strtolower(trim((string)($valor ?? '')));
        $texto = strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u',
            'à' => 'a', 'è' => 'e', 'ì' => 'i', 'ò' => 'o', 'ù' => 'u',
            'ä' => 'a', 'ë' => 'e', 'ï' => 'i', 'ö' => 'o', 'ü' => 'u',
            'ñ' => 'n', 'ç' => 'c',
        ]);
        return trim(preg_replace('/\s+/u', ' ', $texto));
    }
}
