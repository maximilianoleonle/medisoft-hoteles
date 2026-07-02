-- SEC-001: rate limit de login persistente en DB (no reiniciable con cookie nueva)
-- Migracion ADITIVA, idempotente y multi-hotel.
--
-- Alcance:
--   - Crea la tabla login_intentos: contador de intentos fallidos y bloqueo
--     por combinacion ip + usuario + hotel_slug (clave hasheada), mas un
--     tope global por IP que LoginRateLimiter maneja con la misma tabla.
--   - Sin hotel_id: el bloqueo aplica ANTES de autenticar, cuando aun no hay
--     contexto de sesion confiable; hotel_slug guarda el contexto informativo.
--   - NO toca datos existentes ni otras tablas.
--
-- Rollback manual seguro (solo con autorizacion explicita):
--   DROP TABLE IF EXISTS login_intentos;
--   DELETE FROM migrations WHERE nombre = '20260702_002_login_intentos_rate_limit.sql';

SET @migration_name := '20260702_002_login_intentos_rate_limit.sql';

START TRANSACTION;

CREATE TABLE IF NOT EXISTS login_intentos (
    id INT NOT NULL AUTO_INCREMENT,
    clave CHAR(64) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA2 del alcance: ip|usuario|hotel_slug o ip global',
    ip VARCHAR(45) COLLATE utf8mb4_unicode_ci NOT NULL,
    nombre_usuario VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    hotel_slug VARCHAR(120) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
    intentos INT NOT NULL DEFAULT 0,
    bloqueado_hasta DATETIME NULL DEFAULT NULL,
    ultimo_intento DATETIME NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_login_intentos_clave (clave),
    KEY idx_login_intentos_ultimo_intento (ultimo_intento),
    KEY idx_login_intentos_ip (ip)
) ENGINE = InnoDB DEFAULT CHARSET = utf8mb4 COLLATE = utf8mb4_unicode_ci;

SET @tabla_final := (
    SELECT COUNT(*)
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'login_intentos'
);

SET @validation_sql := IF(
    @tabla_final = 1,
    'SELECT ''OK: tabla login_intentos verificada'' AS resultado',
    'SELECT no_existe_tabla_login_intentos'
);

PREPARE stmt FROM @validation_sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO migrations (nombre, batch, checksum, estado)
SELECT @migration_name, COALESCE(MAX(batch), 0) + 1, SHA2(CONCAT(@migration_name, '|v1'), 256), 'ejecutada'
FROM migrations
ON DUPLICATE KEY UPDATE
    checksum = VALUES(checksum),
    estado = 'ejecutada',
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
