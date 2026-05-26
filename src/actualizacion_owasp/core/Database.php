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
            error_log("Error de conexion a base de datos: " . $e->getMessage());
            http_response_code(500);
            die("Error interno del servidor. Intente nuevamente mas tarde.");
            die("Error de conexión: " . $e->getMessage());
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
            error_log("Error en query: " . $e->getMessage());
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
