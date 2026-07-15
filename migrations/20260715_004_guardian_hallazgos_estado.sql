-- Guardian (vigilancia financiera): registro e historial de hallazgos de
-- patrones de comportamiento con estado de revision (nuevo/revisado/resuelto).
-- Es la UNICA tabla donde el Guardian escribe: jamas toca caja, cortes,
-- movimientos ni reservaciones. La clave identifica el hallazgo de forma
-- estable (regla + usuario observado) para que persista entre dias.

CREATE TABLE IF NOT EXISTS guardian_hallazgos_estado (
    id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    clave VARCHAR(120) NOT NULL,
    codigo_regla VARCHAR(40) NOT NULL,
    usuario_id INT NULL,
    severidad ENUM('alta', 'media') NOT NULL DEFAULT 'media',
    titulo VARCHAR(200) NOT NULL,
    resumen VARCHAR(500) NULL,
    casos_conteo INT NOT NULL DEFAULT 0,
    detalle_json MEDIUMTEXT NULL,
    estado ENUM('nuevo', 'revisado', 'resuelto') NOT NULL DEFAULT 'nuevo',
    detectado_en DATE NOT NULL,
    ultima_vez_en DATE NOT NULL,
    revisado_por INT NULL,
    revisado_en DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_guardian_hallazgo (hotel_id, clave),
    KEY idx_guardian_hotel_estado (hotel_id, estado, ultima_vez_en),
    KEY idx_guardian_usuario (hotel_id, usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
