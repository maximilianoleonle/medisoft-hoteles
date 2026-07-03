<?php
/**
 * Clase Database - Manejo de conexiones a Base de Datos
 * Los Cedros
 * VERSIÓN CORREGIDA - Arreglado problema con $pdo vs $connection
 */

class Database {
    private static $instance = null;
    private $connection;
    private $config;
    
    private function __construct() {
        $this->config = require_once __DIR__ . '/../config/database.php';
        $this->connect();
        $this->query("SET time_zone = '-06:00'");
    }
    
    /**
     * Obtener instancia única (Singleton)
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Verificar si hay una transacción activa
     * CORREGIDO: Usar $this->connection en lugar de $this->pdo
     */
    public function enTransaccion() {
        return $this->connection->inTransaction();
    }

    /**
     * RollBack seguro - solo si hay transacción activa
     * CORREGIDO: Usar $this->connection en lugar de $this->pdo
     */
    public function safeRollBack() {
        if ($this->connection->inTransaction()) {
            return $this->connection->rollBack();
        }
        return true; // No hay transacción que revertir
    }

    /**
     * Commit seguro - solo si hay transacción activa
     * CORREGIDO: Usar $this->connection en lugar de $this->pdo
     */
    public function safeCommit() {
        if ($this->connection->inTransaction()) {
            return $this->connection->commit();
        }
        return true; // No hay transacción que confirmar
    }

    /**
     * Iniciar transacción solo si no hay una activa
     * CORREGIDO: Usar $this->connection en lugar de $this->pdo
     */
    public function safeBeginTransaction() {
        if (!$this->connection->inTransaction()) {
            return $this->connection->beginTransaction();
        }
        return true; // Ya hay transacción activa
    }
    
    /**
     * Establecer conexión con la base de datos
     */
    private function connect() {
        try {
            $dsn = "mysql:host={$this->config['host']};dbname={$this->config['database']};charset={$this->config['charset']}";
            
            $this->connection = new PDO(
                $dsn,
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
            
        } catch (PDOException $e) {
            if (function_exists('ms_log')) {
                ms_log('critical', 'Error de conexion a base de datos: ' . $e->getMessage());
            } else {
                error_log("Error de conexion a base de datos: " . $e->getMessage());
            }

            // Mensaje generico sin credenciales ni host; el manejador global
            // (ms_manejar_throwable) muestra la pagina 500 con request_id.
            throw new RuntimeException('No hay conexion con la base de datos.', 0, $e);
        }
    }
    
    /**
     * Obtener conexión PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Ejecutar query con prepared statements
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            // Registrar con contexto (hotel, usuario, ruta) y el SQL truncado.
            // Nunca se registran los parametros: pueden contener datos sensibles.
            if (function_exists('ms_log')) {
                ms_log('error', 'Error SQL: ' . $e->getMessage(), [
                    'sql' => substr(preg_replace('/\s+/', ' ', (string) $sql), 0, 300),
                    'codigo' => $e->getCode(),
                ]);
            } else {
                error_log("Error en query: " . $e->getMessage());
            }

            // Modo transicion: se conserva el retorno false historico.
            // Con DB_STRICT_ERRORS=true la query rota deja de disfrazarse de
            // "sin datos" y pasa al manejador global de errores.
            if (filter_var(getenv('DB_STRICT_ERRORS') ?: false, FILTER_VALIDATE_BOOLEAN)) {
                throw $e;
            }

            return false;
        }
    }
    
    /**
     * Obtener el último ID insertado
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Iniciar transacción
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Confirmar transacción
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Revertir transacción
     */
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    /**
     * Prevenir clonación
     */
    private function __clone() {}
    
    /**
     * Prevenir deserialización
     */
    public function __wakeup() {
        throw new Exception("No se puede deserializar un singleton");
    }
}
