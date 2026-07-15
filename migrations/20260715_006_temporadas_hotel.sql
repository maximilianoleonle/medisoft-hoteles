-- Copiloto IA - Fase 3: calendario de temporadas y eventos por hotel.
-- Alcance:
-- - temporadas_hotel: rangos marcados por el hotel ("Feria del pueblo, 15-20
--   mar, alta, se repite cada anio"). Alimentan el forecast (marcas visuales)
--   y el consejo de tarifa del Copiloto (lineas de contexto en el prompt).
-- - Los festivos MX NO llevan tabla: se calculan en PHP (helpers/festivos_mx.php,
--   incluye computus de Semana Santa).
-- - Solo lectura para la operacion: no toca reservaciones, tarifas ni Caja.
-- - Idempotente: CREATE TABLE IF NOT EXISTS.

SET @migration_name := '20260715_006_temporadas_hotel.sql';

CREATE TABLE IF NOT EXISTS temporadas_hotel (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    nombre VARCHAR(120) NOT NULL COMMENT 'Feria del pueblo, boda grande, temporada de lluvias...',
    desde DATE NOT NULL,
    hasta DATE NOT NULL,
    intensidad ENUM('alta','baja') NOT NULL DEFAULT 'alta' COMMENT 'alta = se llena; baja = se vacia',
    recurrente_anual TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = se repite cada anio (mismo mes-dia)',
    notas VARCHAR(500) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_temporadas_hotel_rango (hotel_id, desde, hasta),
    CONSTRAINT fk_temporadas_hotel FOREIGN KEY (hotel_id) REFERENCES hoteles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Temporadas y eventos marcados por el hotel (alimentan forecast y consejo IA)';
