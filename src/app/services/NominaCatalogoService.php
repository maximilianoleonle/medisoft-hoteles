<?php
/**
 * Catalogos internos de nomina por negocio (Fase 2 nomina core):
 * departamentos, puestos, tipos de contrato, grupos de pago y conceptos.
 *
 * Todos los metodos reciben $hotelId explicito (nunca leen sesion) y todo
 * WHERE incluye hotel_id: un catalogo jamas puede leer o escribir filas de
 * otro tenant. Las escrituras auditan via AuditService.
 */

require_once __DIR__ . '/../../core/Database.php';
require_once __DIR__ . '/AuditService.php';

class NominaCatalogoService {

    private $db;

    /** Definicion de catalogos soportados y sus campos editables. */
    private $catalogos = [
        'departamentos' => ['tabla' => 'nomina_departamentos', 'etiqueta' => 'Departamento'],
        'puestos' => ['tabla' => 'nomina_puestos', 'etiqueta' => 'Puesto'],
        'tipos_contrato' => ['tabla' => 'nomina_tipos_contrato', 'etiqueta' => 'Tipo de contrato'],
        'grupos' => ['tabla' => 'nomina_grupos', 'etiqueta' => 'Grupo de nomina'],
        'conceptos' => ['tabla' => 'nomina_conceptos', 'etiqueta' => 'Concepto'],
    ];

    private $periodicidadesValidas = ['semanal', 'quincenal', 'mensual'];
    private $tiposConceptoValidos = ['percepcion', 'deduccion'];
    private $clasificacionesValidas = ['sueldo', 'bono', 'comision', 'horas_extra', 'propina', 'destajo', 'descuento', 'anticipo', 'prestamo', 'ajuste', 'otro'];
    private $modosCalculoValidos = ['manual', 'monto_fijo', 'por_cantidad'];

    public function __construct(?Database $db = null) {
        $this->db = $db ?: Database::getInstance();
    }

    public function esTipoValido($tipo) {
        return isset($this->catalogos[(string) $tipo]);
    }

    public function etiquetaDe($tipo) {
        return $this->catalogos[$tipo]['etiqueta'] ?? 'Registro';
    }

    /* ------------------------------------------------------------------ */

    public function listar(int $hotelId, string $tipo, bool $soloActivos = false): array {
        $this->assertTipo($tipo);
        $tabla = $this->catalogos[$tipo]['tabla'];

        $sql = "SELECT * FROM {$tabla} WHERE hotel_id = ?";
        if ($soloActivos) {
            $sql .= " AND activo = 1";
        }
        $sql .= " ORDER BY orden ASC, nombre ASC";

        $st = $this->db->query($sql, [$hotelId]);
        return $st !== false ? $st->fetchAll() : [];
    }

    public function obtener(int $hotelId, string $tipo, int $id): ?array {
        $this->assertTipo($tipo);
        $tabla = $this->catalogos[$tipo]['tabla'];

        $st = $this->db->query("SELECT * FROM {$tabla} WHERE id = ? AND hotel_id = ?", [$id, $hotelId]);
        if ($st === false) {
            return null;
        }
        $fila = $st->fetch();
        return $fila ?: null;
    }

    public function crear(int $hotelId, string $tipo, array $datos, ?int $usuarioId = null): int {
        $this->assertTipo($tipo);
        $this->assertHotel($hotelId);
        $tabla = $this->catalogos[$tipo]['tabla'];

        $limpios = $this->normalizarDatos($hotelId, $tipo, $datos);
        $this->assertNombreDisponible($hotelId, $tipo, $limpios['nombre']);

        $columnas = array_keys($limpios);
        $columnas[] = 'hotel_id';
        $columnas[] = 'created_by';
        $valores = array_values($limpios);
        $valores[] = $hotelId;
        $valores[] = $usuarioId;

        $marcadores = implode(', ', array_fill(0, count($columnas), '?'));
        $sql = "INSERT INTO {$tabla} (" . implode(', ', $columnas) . ") VALUES ({$marcadores})";

        $st = $this->db->query($sql, $valores);
        if ($st === false) {
            throw new Exception('No se pudo crear el registro del catalogo.');
        }

        $nuevoId = (int) $this->db->lastInsertId();

        AuditService::record('nomina.catalogo_creado', [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => $tabla,
            'entidad_id' => (string) $nuevoId,
            'descripcion' => 'Creo ' . $this->etiquetaDe($tipo) . ': ' . $limpios['nombre'],
            'datos_despues' => $limpios,
        ]);

        return $nuevoId;
    }

    public function actualizar(int $hotelId, string $tipo, int $id, array $datos, ?int $usuarioId = null): bool {
        $this->assertTipo($tipo);
        $tabla = $this->catalogos[$tipo]['tabla'];

        $actual = $this->obtener($hotelId, $tipo, $id);
        if (!$actual) {
            throw new Exception('El registro del catalogo no existe en este negocio.');
        }

        $limpios = $this->normalizarDatos($hotelId, $tipo, $datos);
        $this->assertNombreDisponible($hotelId, $tipo, $limpios['nombre'], $id);

        $sets = [];
        $valores = [];
        foreach ($limpios as $columna => $valor) {
            $sets[] = "{$columna} = ?";
            $valores[] = $valor;
        }
        $sets[] = 'updated_by = ?';
        $valores[] = $usuarioId;
        $valores[] = $id;
        $valores[] = $hotelId;

        $st = $this->db->query(
            "UPDATE {$tabla} SET " . implode(', ', $sets) . " WHERE id = ? AND hotel_id = ?",
            $valores
        );
        if ($st === false) {
            throw new Exception('No se pudo actualizar el registro del catalogo.');
        }

        AuditService::record('nomina.catalogo_actualizado', [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => $tabla,
            'entidad_id' => (string) $id,
            'descripcion' => 'Actualizo ' . $this->etiquetaDe($tipo) . ': ' . $limpios['nombre'],
            'datos_antes' => array_intersect_key($actual, $limpios),
            'datos_despues' => $limpios,
        ]);

        return true;
    }

    public function alternar(int $hotelId, string $tipo, int $id, ?int $usuarioId = null): bool {
        $this->assertTipo($tipo);
        $tabla = $this->catalogos[$tipo]['tabla'];

        $actual = $this->obtener($hotelId, $tipo, $id);
        if (!$actual) {
            throw new Exception('El registro del catalogo no existe en este negocio.');
        }

        $nuevoEstado = ((int) $actual['activo']) === 1 ? 0 : 1;

        $st = $this->db->query(
            "UPDATE {$tabla} SET activo = ?, updated_by = ? WHERE id = ? AND hotel_id = ?",
            [$nuevoEstado, $usuarioId, $id, $hotelId]
        );
        if ($st === false) {
            throw new Exception('No se pudo cambiar el estado del registro.');
        }

        AuditService::record('nomina.catalogo_alternado', [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => $tabla,
            'entidad_id' => (string) $id,
            'descripcion' => ($nuevoEstado ? 'Activo ' : 'Desactivo ') . $this->etiquetaDe($tipo) . ': ' . ($actual['nombre'] ?? $id),
            'datos_antes' => ['activo' => (int) $actual['activo']],
            'datos_despues' => ['activo' => $nuevoEstado],
        ]);

        return true;
    }

    /**
     * Siembra conceptos base (es_sistema = 1) para un negocio sin conceptos.
     * Idempotente: si ya existe cualquier concepto, no hace nada.
     */
    public function sembrarConceptosBase(int $hotelId, ?int $usuarioId = null): int {
        $this->assertHotel($hotelId);

        $st = $this->db->query("SELECT COUNT(*) AS total FROM nomina_conceptos WHERE hotel_id = ?", [$hotelId]);
        if ($st === false) {
            return 0;
        }
        $fila = $st->fetch();
        if ((int) ($fila['total'] ?? 0) > 0) {
            return 0;
        }

        $base = [
            ['Sueldo base', 'percepcion', 'sueldo', 'manual', 10],
            ['Bono', 'percepcion', 'bono', 'manual', 20],
            ['Comision', 'percepcion', 'comision', 'manual', 30],
            ['Horas extra', 'percepcion', 'horas_extra', 'por_cantidad', 40],
            ['Ajuste a favor', 'percepcion', 'ajuste', 'manual', 50],
            ['Descuento', 'deduccion', 'descuento', 'manual', 60],
            ['Anticipo', 'deduccion', 'anticipo', 'manual', 70],
            ['Prestamo (abono)', 'deduccion', 'prestamo', 'manual', 80],
            ['Ajuste en contra', 'deduccion', 'ajuste', 'manual', 90],
        ];

        $creados = 0;
        foreach ($base as [$nombre, $tipoConcepto, $clasificacion, $modo, $orden]) {
            $st = $this->db->query(
                "INSERT INTO nomina_conceptos
                    (hotel_id, nombre, tipo, clasificacion, modo_calculo, es_sistema, orden, activo, created_by)
                 VALUES (?, ?, ?, ?, ?, 1, ?, 1, ?)",
                [$hotelId, $nombre, $tipoConcepto, $clasificacion, $modo, $orden, $usuarioId]
            );
            if ($st !== false) {
                $creados++;
            }
        }

        if ($creados > 0) {
            AuditService::record('nomina.conceptos_base_sembrados', [
                'hotel_id' => $hotelId,
                'usuario_id' => $usuarioId,
                'entidad_tipo' => 'nomina_conceptos',
                'entidad_id' => (string) $hotelId,
                'descripcion' => 'Sembro ' . $creados . ' conceptos base de nomina',
            ]);
        }

        return $creados;
    }

    /**
     * Asigna puesto/departamento/contrato/grupo a un trabajador validando que
     * cada catalogo pertenezca al MISMO hotel (aislamiento de tenant).
     */
    public function asignarATrabajador(int $hotelId, int $trabajadorId, array $asignaciones, ?int $usuarioId = null): bool {
        $st = $this->db->query(
            "SELECT id, nombre_completo, puesto_id, departamento_id, tipo_contrato_id, grupo_nomina_id
             FROM trabajadores WHERE id = ? AND hotel_id = ?",
            [$trabajadorId, $hotelId]
        );
        $trabajador = $st !== false ? $st->fetch() : null;
        if (!$trabajador) {
            throw new Exception('El trabajador no existe en este negocio.');
        }

        $mapa = [
            'puesto_id' => 'puestos',
            'departamento_id' => 'departamentos',
            'tipo_contrato_id' => 'tipos_contrato',
            'grupo_nomina_id' => 'grupos',
        ];

        $limpias = [];
        foreach ($mapa as $columna => $tipoCatalogo) {
            if (!array_key_exists($columna, $asignaciones)) {
                continue;
            }
            $valor = $asignaciones[$columna];
            if ($valor === '' || $valor === null || (int) $valor === 0) {
                $limpias[$columna] = null;
                continue;
            }
            $registro = $this->obtener($hotelId, $tipoCatalogo, (int) $valor);
            if (!$registro) {
                throw new Exception('La asignacion de ' . $this->etiquetaDe($tipoCatalogo) . ' no pertenece a este negocio.');
            }
            $limpias[$columna] = (int) $valor;
        }

        if ($limpias === []) {
            return true;
        }

        $sets = [];
        $valores = [];
        foreach ($limpias as $columna => $valor) {
            $sets[] = "{$columna} = ?";
            $valores[] = $valor;
        }
        $sets[] = 'updated_by = ?';
        $valores[] = $usuarioId;
        $valores[] = $trabajadorId;
        $valores[] = $hotelId;

        $st = $this->db->query(
            "UPDATE trabajadores SET " . implode(', ', $sets) . " WHERE id = ? AND hotel_id = ?",
            $valores
        );
        if ($st === false) {
            throw new Exception('No se pudieron guardar las asignaciones.');
        }

        AuditService::record('nomina.trabajador_asignaciones', [
            'hotel_id' => $hotelId,
            'usuario_id' => $usuarioId,
            'entidad_tipo' => 'trabajadores',
            'entidad_id' => (string) $trabajadorId,
            'descripcion' => 'Actualizo asignaciones de nomina de ' . ($trabajador['nombre_completo'] ?? $trabajadorId),
            'datos_antes' => array_intersect_key($trabajador, $limpias),
            'datos_despues' => $limpias,
        ]);

        return true;
    }

    public function conteos(int $hotelId): array {
        $resultado = [];
        foreach ($this->catalogos as $tipo => $def) {
            $st = $this->db->query(
                "SELECT COUNT(*) AS total, SUM(activo = 1) AS activos FROM {$def['tabla']} WHERE hotel_id = ?",
                [$hotelId]
            );
            $fila = $st !== false ? $st->fetch() : null;
            $resultado[$tipo] = [
                'total' => (int) ($fila['total'] ?? 0),
                'activos' => (int) ($fila['activos'] ?? 0),
            ];
        }
        return $resultado;
    }

    /* ------------------------------------------------------------------ */

    private function assertTipo($tipo): void {
        if (!$this->esTipoValido($tipo)) {
            throw new Exception('Tipo de catalogo no valido.');
        }
    }

    private function assertHotel(int $hotelId): void {
        if ($hotelId <= 0) {
            throw new Exception('Contexto de negocio no valido.');
        }
    }

    private function assertNombreDisponible(int $hotelId, string $tipo, string $nombre, int $exceptoId = 0): void {
        $tabla = $this->catalogos[$tipo]['tabla'];
        $st = $this->db->query(
            "SELECT id FROM {$tabla} WHERE hotel_id = ? AND nombre = ? AND id != ? LIMIT 1",
            [$hotelId, $nombre, $exceptoId]
        );
        if ($st !== false && $st->fetch()) {
            throw new Exception('Ya existe un registro con ese nombre en este catalogo.');
        }
    }

    private function normalizarDatos(int $hotelId, string $tipo, array $datos): array {
        $nombre = trim((string) ($datos['nombre'] ?? ''));
        if ($nombre === '') {
            throw new Exception('El nombre es obligatorio.');
        }
        $limpios = [
            'nombre' => mb_substr($nombre, 0, $tipo === 'conceptos' ? 120 : 100),
            'descripcion' => $this->textoNullable($datos['descripcion'] ?? null, 200),
            'orden' => max(0, (int) ($datos['orden'] ?? 0)),
        ];

        if ($tipo === 'puestos') {
            $limpios['departamento_id'] = $this->catalogoIdNullable($hotelId, 'departamentos', $datos['departamento_id'] ?? null);
            $limpios['salario_sugerido'] = $this->montoNullable($datos['salario_sugerido'] ?? null);
        }

        if ($tipo === 'grupos') {
            $periodicidad = (string) ($datos['periodicidad'] ?? 'quincenal');
            if (!in_array($periodicidad, $this->periodicidadesValidas, true)) {
                throw new Exception('Periodicidad no valida.');
            }
            $limpios['periodicidad'] = $periodicidad;
            $limpios['dia_corte'] = $this->diaNullable($datos['dia_corte'] ?? null);
            $limpios['dia_pago'] = $this->diaNullable($datos['dia_pago'] ?? null);
        }

        if ($tipo === 'conceptos') {
            unset($limpios['descripcion']);
            $tipoConcepto = (string) ($datos['tipo'] ?? '');
            if (!in_array($tipoConcepto, $this->tiposConceptoValidos, true)) {
                throw new Exception('El concepto debe ser percepcion o deduccion.');
            }
            $clasificacion = (string) ($datos['clasificacion'] ?? 'otro');
            if (!in_array($clasificacion, $this->clasificacionesValidas, true)) {
                throw new Exception('Clasificacion de concepto no valida.');
            }
            $modo = (string) ($datos['modo_calculo'] ?? 'manual');
            if (!in_array($modo, $this->modosCalculoValidos, true)) {
                throw new Exception('Modo de calculo no valido.');
            }
            $limpios['tipo'] = $tipoConcepto;
            $limpios['clasificacion'] = $clasificacion;
            $limpios['modo_calculo'] = $modo;
            $limpios['monto_default'] = $this->montoNullable($datos['monto_default'] ?? null);
            $limpios['gravable_isr'] = !empty($datos['gravable_isr']) ? 1 : 0;
            $limpios['gravable_imss'] = !empty($datos['gravable_imss']) ? 1 : 0;
        }

        return $limpios;
    }

    private function textoNullable($valor, int $limite): ?string {
        $texto = trim((string) ($valor ?? ''));
        return $texto === '' ? null : mb_substr($texto, 0, $limite);
    }

    private function montoNullable($valor): ?string {
        if ($valor === null || $valor === '') {
            return null;
        }
        $monto = round((float) $valor, 2);
        if ($monto < 0) {
            throw new Exception('Los montos no pueden ser negativos.');
        }
        return number_format($monto, 2, '.', '');
    }

    private function diaNullable($valor): ?int {
        if ($valor === null || $valor === '') {
            return null;
        }
        $dia = (int) $valor;
        if ($dia < 1 || $dia > 31) {
            throw new Exception('El dia debe estar entre 1 y 31.');
        }
        return $dia;
    }

    private function catalogoIdNullable(int $hotelId, string $tipo, $valor): ?int {
        if ($valor === null || $valor === '' || (int) $valor === 0) {
            return null;
        }
        $registro = $this->obtener($hotelId, $tipo, (int) $valor);
        if (!$registro) {
            throw new Exception('El ' . strtolower($this->etiquetaDe($tipo)) . ' seleccionado no pertenece a este negocio.');
        }
        return (int) $valor;
    }
}
