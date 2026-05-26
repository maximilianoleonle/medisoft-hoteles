<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class ConfiguracionHabitacionService
{
    private $db;
    
    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Obtiene todos los tipos de habitación con su cantidad
     */
    public function getTiposHabitacion()
    {
        $sql = "SELECT 
                    th.id,
                    th.codigo,
                    th.nombre,
                    th.descripcion,
                    COUNT(h.id) as total_habitaciones
                FROM tipos_habitacion th
                LEFT JOIN habitaciones h ON h.tipo = th.codigo
                WHERE th.activo = TRUE
                GROUP BY th.id
                ORDER BY th.orden";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene todos los productos disponibles para configuración
     */
    public function getProductosParaConfiguracion()
    {
        $sql = "SELECT 
                    p.id,
                    p.codigo,
                    p.nombre,
                    p.stock_actual,
                    p.stock_minimo,
                    p.descuento_automatico_checkin,
                    p.descuento_automatico_limpieza,
                    c.nombre as categoria,
                    um.abreviatura as unidad
                FROM productos p
                INNER JOIN categorias_producto c ON p.categoria_id = c.id
                INNER JOIN unidades_medida um ON p.unidad_medida_id = um.id
                WHERE p.activo = TRUE
                ORDER BY c.orden, p.nombre";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene la configuración actual para todos los tipos de habitación
     */
    public function getConfiguraciones()
    {
        $sql = "SELECT 
                    ich.id,
                    ich.tipo_habitacion_id,
                    ich.producto_id,
                    ich.cantidad_checkin,
                    ich.cantidad_limpieza,
                    ich.activo
                FROM inventario_config_habitacion ich";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organizar por tipo_habitacion_id y producto_id
        $configuraciones = [];
        foreach ($results as $config) {
            $tipoId = $config['tipo_habitacion_id'];
            $productoId = $config['producto_id'];
            
            if (!isset($configuraciones[$tipoId])) {
                $configuraciones[$tipoId] = [];
            }
            
            $configuraciones[$tipoId][$productoId] = $config;
        }
        
        return $configuraciones;
    }
    
    /**
     * Guarda la configuración de inventario por habitación
     */
    public function guardarConfiguracion($configuraciones)
    {
        try {
            $this->db->beginTransaction();
            
            foreach ($configuraciones as $tipoHabitacionId => $productos) {
                foreach ($productos as $productoId => $config) {
                    // Verificar si ya existe una configuración
                    if (!empty($config['id'])) {
                        // Actualizar configuración existente
                        $sql = "UPDATE inventario_config_habitacion SET
                                cantidad_checkin = :cantidad_checkin,
                                cantidad_limpieza = :cantidad_limpieza,
                                activo = :activo
                                WHERE id = :id";
                                
                        $stmt = $this->db->prepare($sql);
                        $stmt->execute([
                            ':cantidad_checkin' => $config['cantidad_checkin'],
                            ':cantidad_limpieza' => $config['cantidad_limpieza'],
                            ':activo' => $config['activo'],
                            ':id' => $config['id']
                        ]);
                    } else {
                        // Solo insertar si hay cantidades configuradas
                        if ($config['cantidad_checkin'] > 0 || $config['cantidad_limpieza'] > 0) {
                            // Obtener el código del tipo de habitación
                            $sqlTipo = "SELECT codigo FROM tipos_habitacion WHERE id = :id";
                            $stmtTipo = $this->db->prepare($sqlTipo);
                            $stmtTipo->execute([':id' => $tipoHabitacionId]);
                            $tipoHabitacion = $stmtTipo->fetchColumn();
                            
                            // Insertar nueva configuración
                            $sql = "INSERT INTO inventario_config_habitacion 
                                    (tipo_habitacion_id, tipo_habitacion, producto_id, 
                                     cantidad_checkin, cantidad_limpieza, activo)
                                    VALUES (:tipo_habitacion_id, :tipo_habitacion, :producto_id,
                                            :cantidad_checkin, :cantidad_limpieza, :activo)";
                                            
                            $stmt = $this->db->prepare($sql);
                            $stmt->execute([
                                ':tipo_habitacion_id' => $tipoHabitacionId,
                                ':tipo_habitacion' => $tipoHabitacion,
                                ':producto_id' => $productoId,
                                ':cantidad_checkin' => $config['cantidad_checkin'],
                                ':cantidad_limpieza' => $config['cantidad_limpieza'],
                                ':activo' => $config['activo']
                            ]);
                        }
                    }
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log("Error guardando configuración de inventario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ejecuta el descuento automático de inventario al hacer check-in
     */
    public function ejecutarDescuentoCheckin($habitacionId, $huespedId, $usuarioId)
    {
        try {
            // Obtener el tipo de habitación
            $sql = "SELECT h.tipo, th.id as tipo_habitacion_id 
                    FROM habitaciones h
                    INNER JOIN tipos_habitacion th ON h.tipo = th.codigo
                    WHERE h.id = :habitacion_id";
                    
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':habitacion_id' => $habitacionId]);
            $habitacion = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$habitacion) {
                throw new \Exception("Habitación no encontrada");
            }
            
            // Llamar al procedimiento almacenado
            $sql = "CALL sp_descontar_inventario_checkin(:habitacion_id, :tipo_habitacion_id, :huesped_id, :usuario_id)";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':habitacion_id' => $habitacionId,
                ':tipo_habitacion_id' => $habitacion['tipo_habitacion_id'],
                ':huesped_id' => $huespedId,
                ':usuario_id' => $usuarioId
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            error_log("Error ejecutando descuento de inventario: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtiene el resumen de productos que se descontarán para una habitación
     */
    public function getProductosDescuentoHabitacion($habitacionId)
    {
        $sql = "SELECT 
                    p.nombre as producto,
                    ich.cantidad_checkin,
                    p.stock_actual,
                    um.abreviatura as unidad,
                    (p.stock_actual >= ich.cantidad_checkin) as disponible
                FROM habitaciones h
                INNER JOIN tipos_habitacion th ON h.tipo = th.codigo
                INNER JOIN inventario_config_habitacion ich ON ich.tipo_habitacion_id = th.id
                INNER JOIN productos p ON ich.producto_id = p.id
                INNER JOIN unidades_medida um ON p.unidad_medida_id = um.id
                WHERE h.id = :habitacion_id
                AND ich.cantidad_checkin > 0
                AND ich.activo = TRUE
                AND p.descuento_automatico_checkin = TRUE";
                
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':habitacion_id' => $habitacionId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}