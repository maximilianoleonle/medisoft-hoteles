-- Fase 1 - Base tecnica SaaS multi-hotel
-- Migracion aditiva: crea tablas nuevas sin modificar tablas operativas existentes.
-- No agrega hotel_id a tablas operativas.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS migrations (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(191) NOT NULL,
    batch INT NOT NULL DEFAULT 1,
    checksum VARCHAR(64) DEFAULT NULL,
    estado ENUM('ejecutada', 'fallida', 'revertida') NOT NULL DEFAULT 'ejecutada',
    ejecutada_en TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_migrations_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hoteles (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(150) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    codigo VARCHAR(50) DEFAULT NULL,
    razon_social VARCHAR(180) DEFAULT NULL,
    rfc VARCHAR(20) DEFAULT NULL,
    telefono VARCHAR(30) DEFAULT NULL,
    email VARCHAR(120) DEFAULT NULL,
    direccion VARCHAR(255) DEFAULT NULL,
    ciudad VARCHAR(100) DEFAULT NULL,
    estado VARCHAR(100) DEFAULT NULL,
    pais VARCHAR(100) NOT NULL DEFAULT 'Mexico',
    zona_horaria VARCHAR(80) NOT NULL DEFAULT 'America/Mexico_City',
    moneda_codigo CHAR(3) NOT NULL DEFAULT 'MXN',
    moneda_simbolo VARCHAR(8) NOT NULL DEFAULT '$',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_hoteles_slug (slug),
    UNIQUE KEY uk_hoteles_codigo (codigo),
    KEY idx_hoteles_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hotel_configuracion (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    clave VARCHAR(120) NOT NULL,
    valor TEXT DEFAULT NULL,
    tipo ENUM('string', 'integer', 'float', 'boolean', 'json') NOT NULL DEFAULT 'string',
    grupo VARCHAR(80) DEFAULT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    es_feature_flag TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_hotel_configuracion_clave (hotel_id, clave),
    KEY idx_hotel_configuracion_hotel (hotel_id),
    KEY idx_hotel_configuracion_grupo (hotel_id, grupo),
    KEY idx_hotel_configuracion_feature (hotel_id, es_feature_flag, activo),
    CONSTRAINT fk_hotel_configuracion_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS hotel_usuarios (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    usuario_id INT NOT NULL,
    rol ENUM('superadmin', 'propietario', 'gerente', 'administrador', 'recepcionista') NOT NULL DEFAULT 'recepcionista',
    es_principal TINYINT(1) NOT NULL DEFAULT 0,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    permisos_json JSON DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_hotel_usuarios_usuario (hotel_id, usuario_id),
    KEY idx_hotel_usuarios_hotel (hotel_id),
    KEY idx_hotel_usuarios_usuario (usuario_id),
    KEY idx_hotel_usuarios_rol (hotel_id, rol, activo),
    CONSTRAINT fk_hotel_usuarios_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_hotel_usuarios_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS logs_auditoria (
    id BIGINT NOT NULL AUTO_INCREMENT,
    hotel_id INT DEFAULT NULL,
    usuario_id INT DEFAULT NULL,
    accion VARCHAR(80) NOT NULL,
    entidad_tipo VARCHAR(80) DEFAULT NULL,
    entidad_id VARCHAR(80) DEFAULT NULL,
    descripcion TEXT DEFAULT NULL,
    datos_antes JSON DEFAULT NULL,
    datos_despues JSON DEFAULT NULL,
    ip VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_logs_auditoria_hotel_fecha (hotel_id, created_at),
    KEY idx_logs_auditoria_usuario_fecha (usuario_id, created_at),
    KEY idx_logs_auditoria_entidad (entidad_tipo, entidad_id),
    KEY idx_logs_auditoria_accion (accion),
    CONSTRAINT fk_logs_auditoria_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE SET NULL,
    CONSTRAINT fk_logs_auditoria_usuario
        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_001_crear_base_saas_multihotel.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
