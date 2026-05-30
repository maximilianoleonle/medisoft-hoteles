-- SaaS Admin - Capa separada para administradores internos de Medisoft.
-- Alcance:
-- - Crea tabla saas_admins.
-- - No modifica usuarios.rol ni roles operativos hoteleros.
-- - No inserta administradores automaticamente.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS saas_admins (
    id INT NOT NULL AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    rol VARCHAR(30) NOT NULL DEFAULT 'admin',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    permisos_json JSON DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_saas_admins_usuario (usuario_id),
    KEY idx_saas_admins_activo (activo),
    KEY idx_saas_admins_rol (rol),
    CONSTRAINT fk_saas_admins_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_010_create_saas_admins.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
