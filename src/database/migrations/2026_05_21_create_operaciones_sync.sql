CREATE TABLE IF NOT EXISTS operaciones_sync (
    uuid CHAR(36) NOT NULL,
    tipo VARCHAR(60) NOT NULL,
    usuario_id INT NULL,
    procesado_at DATETIME NOT NULL,
    resultado VARCHAR(20) NOT NULL,
    detalle TEXT NULL,
    PRIMARY KEY (uuid),
    INDEX idx_operaciones_sync_resultado (resultado),
    INDEX idx_operaciones_sync_usuario (usuario_id),
    INDEX idx_operaciones_sync_procesado (procesado_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
