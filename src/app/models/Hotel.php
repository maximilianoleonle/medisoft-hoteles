<?php
/**
 * Modelo Hotel
 */

class Hotel extends Model {
    protected $table = 'hoteles';
    protected $fillable = [];

    /**
     * Listado read-only para el Panel Medisoft interno SaaS.
     */
    public function listarParaSaasAdmin() {
        return $this->query(
            "SELECT id, nombre, slug, codigo, activo, moneda_codigo, created_at
             FROM {$this->table}
             ORDER BY id ASC"
        );
    }
}
