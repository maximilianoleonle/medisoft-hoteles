-- Microfase 5: branding basico por hotel.
-- Crea una capa controlada de white label sin CSS/JS/HTML libre.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS hotel_branding (
    id INT AUTO_INCREMENT PRIMARY KEY,
    hotel_id INT NOT NULL,
    nombre_visual VARCHAR(150) NULL,
    logo_url VARCHAR(255) NULL,
    favicon_url VARCHAR(255) NULL,
    login_background_url VARCHAR(255) NULL,
    color_primary VARCHAR(7) NULL,
    color_secondary VARCHAR(7) NULL,
    color_accent VARCHAR(7) NULL,
    sidebar_style VARCHAR(30) NULL,
    login_style VARCHAR(30) NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_hotel_branding_hotel (hotel_id),
    KEY idx_hotel_branding_activo (activo),
    CONSTRAINT fk_hotel_branding_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260526_013_create_hotel_branding.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
