-- Fase 1 - Roles configurables por hotel: base de datos.
-- Alcance:
-- - Crea la tabla `roles` (roles por hotel, con permisos_json).
-- - Agrega hotel_usuarios.role_id (FK a roles), nullable, sin tocar el ENUM `rol`.
-- - Siembra los roles base (presets) para cada hotel existente.
-- - Backfill: asigna role_id a cada hotel_usuario mapeando su rol ENUM -> rol base.
-- - No modifica can(), el sidebar ni ningun controlador (comportamiento identico).
--
-- Los permisos sembrados deben coincidir con config/permisos.php ('presets').

SET @migration_name := '20260628_001_create_roles_base.sql';

DROP PROCEDURE IF EXISTS migrar_roles_base;

DELIMITER $$

CREATE PROCEDURE migrar_roles_base()
BEGIN
    DECLARE v_has_hoteles INT DEFAULT 0;
    DECLARE v_has_hotel_usuarios INT DEFAULT 0;
    DECLARE v_has_role_id INT DEFAULT 0;
    DECLARE v_has_index INT DEFAULT 0;
    DECLARE v_has_fk INT DEFAULT 0;

    SELECT COUNT(*)
    INTO v_has_hoteles
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hoteles';

    SELECT COUNT(*)
    INTO v_has_hotel_usuarios
    FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hotel_usuarios';

    IF v_has_hoteles = 0 OR v_has_hotel_usuarios = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Faltan tablas base (hoteles / hotel_usuarios) para crear roles';
    END IF;

    -- 1) Tabla de roles por hotel.
    CREATE TABLE IF NOT EXISTS roles (
        id INT NOT NULL AUTO_INCREMENT,
        hotel_id INT NOT NULL,
        clave VARCHAR(60) NOT NULL,
        nombre VARCHAR(120) NOT NULL,
        descripcion VARCHAR(255) DEFAULT NULL,
        es_sistema TINYINT(1) NOT NULL DEFAULT 0,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        permisos_json JSON DEFAULT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY uk_roles_hotel_clave (hotel_id, clave),
        KEY idx_roles_hotel_activo (hotel_id, activo),
        CONSTRAINT fk_roles_hotel
            FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
            ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- 2) Columna role_id en hotel_usuarios (nullable; el ENUM `rol` se mantiene).
    SELECT COUNT(*)
    INTO v_has_role_id
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hotel_usuarios'
      AND COLUMN_NAME = 'role_id';

    IF v_has_role_id = 0 THEN
        ALTER TABLE hotel_usuarios
            ADD COLUMN role_id INT NULL AFTER rol;
    END IF;

    SELECT COUNT(DISTINCT INDEX_NAME)
    INTO v_has_index
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hotel_usuarios'
      AND INDEX_NAME = 'idx_hotel_usuarios_role';

    IF v_has_index = 0 THEN
        ALTER TABLE hotel_usuarios
            ADD INDEX idx_hotel_usuarios_role (role_id);
    END IF;

    SELECT COUNT(*)
    INTO v_has_fk
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hotel_usuarios'
      AND CONSTRAINT_NAME = 'fk_hotel_usuarios_role'
      AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF v_has_fk = 0 THEN
        ALTER TABLE hotel_usuarios
            ADD CONSTRAINT fk_hotel_usuarios_role
                FOREIGN KEY (role_id) REFERENCES roles(id)
                ON UPDATE CASCADE
                ON DELETE SET NULL;
    END IF;

    -- 3) Siembra de roles base por hotel (idempotente: solo si no existen).
    INSERT INTO roles (hotel_id, clave, nombre, descripcion, es_sistema, activo, permisos_json, created_at, updated_at)
    SELECT h.id, 'superadmin', 'Superadministrador', 'Acceso total. Rol tecnico reservado.', 1, 1,
           '["*"]', NOW(), NOW()
    FROM hoteles h
    WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.hotel_id = h.id AND r.clave = 'superadmin');

    INSERT INTO roles (hotel_id, clave, nombre, descripcion, es_sistema, activo, permisos_json, created_at, updated_at)
    SELECT h.id, 'propietario', 'Propietario', 'Dueno del hotel. Acceso total, incluida la gestion de roles.', 1, 1,
           '["*"]', NOW(), NOW()
    FROM hoteles h
    WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.hotel_id = h.id AND r.clave = 'propietario');

    INSERT INTO roles (hotel_id, clave, nombre, descripcion, es_sistema, activo, permisos_json, created_at, updated_at)
    SELECT h.id, 'gerente', 'Gerente', 'Direccion operativa y financiera del hotel.', 1, 1,
           '["usuarios.view","usuarios.create","usuarios.edit","usuarios.delete","roles.manage","configuracion.view","configuracion.edit","reportes.all","caja.view","caja.movimientos","caja.cobros","caja.corte","caja.ajustes","habitaciones.all","reservaciones.all","huespedes.all","inventarios.all","compras.all","proveedores.all","facturacion.view","cuentas_por_cobrar.all","cuentas_por_pagar.all","documentos.all","tareas.all","personal.view","tarifas.view","tarifas.edit","notificaciones.view"]',
           NOW(), NOW()
    FROM hoteles h
    WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.hotel_id = h.id AND r.clave = 'gerente');

    INSERT INTO roles (hotel_id, clave, nombre, descripcion, es_sistema, activo, permisos_json, created_at, updated_at)
    SELECT h.id, 'administrador', 'Administrador', 'Administracion operativa del hotel.', 1, 1,
           '["usuarios.view","usuarios.create","usuarios.edit","reportes.view","reportes.export","caja.view","caja.movimientos","caja.cobros","caja.corte","habitaciones.all","reservaciones.all","huespedes.all","inventarios.all","compras.all","proveedores.all","facturacion.view","cuentas_por_cobrar.view","cuentas_por_pagar.view","documentos.all","tareas.all","notificaciones.view"]',
           NOW(), NOW()
    FROM hoteles h
    WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.hotel_id = h.id AND r.clave = 'administrador');

    INSERT INTO roles (hotel_id, clave, nombre, descripcion, es_sistema, activo, permisos_json, created_at, updated_at)
    SELECT h.id, 'recepcionista', 'Recepcionista', 'Operacion de recepcion: check-in/out, huespedes y cobros.', 1, 1,
           '["habitaciones.view","habitaciones.checkin","habitaciones.checkout","habitaciones.mantenimiento","reservaciones.view","reservaciones.create","reservaciones.edit","huespedes.view","huespedes.create","huespedes.edit","caja.view","caja.cobros","documentos.view","tareas.view","notificaciones.view","llaves.control"]',
           NOW(), NOW()
    FROM hoteles h
    WHERE NOT EXISTS (SELECT 1 FROM roles r WHERE r.hotel_id = h.id AND r.clave = 'recepcionista');

    -- 4) Backfill: vincular cada usuario del hotel con su rol base correspondiente.
    UPDATE hotel_usuarios hu
    INNER JOIN roles r
        ON r.hotel_id = hu.hotel_id
       AND r.clave = hu.rol
    SET hu.role_id = r.id
    WHERE hu.role_id IS NULL;

    -- 5) Registrar migracion.
    INSERT INTO migrations (nombre, batch, checksum, estado, ejecutada_en)
    SELECT
        @migration_name,
        COALESCE(MAX(batch), 0) + 1,
        NULL,
        'ejecutada',
        NOW()
    FROM migrations
    ON DUPLICATE KEY UPDATE
        estado = VALUES(estado),
        ejecutada_en = CURRENT_TIMESTAMP;
END$$

CALL migrar_roles_base()$$

DROP PROCEDURE IF EXISTS migrar_roles_base$$

DELIMITER ;

-- Rollback manual:
-- ALTER TABLE hotel_usuarios DROP FOREIGN KEY fk_hotel_usuarios_role;
-- ALTER TABLE hotel_usuarios DROP INDEX idx_hotel_usuarios_role;
-- ALTER TABLE hotel_usuarios DROP COLUMN role_id;
-- DROP TABLE IF EXISTS roles;
-- DELETE FROM migrations WHERE nombre = '20260628_001_create_roles_base.sql';
