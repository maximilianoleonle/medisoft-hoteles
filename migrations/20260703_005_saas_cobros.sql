-- Cobros SaaS - V1: facturacion mensual de Medisoft a cada hotel.
-- Alcance:
-- - saas_cobros: un cobro por hotel y periodo (YYYY-MM) con desglose congelado
--   del paquete basico + bloques activos al momento de generar.
-- - Pago via link de Stripe Checkout (cuenta de Medisoft, llaves SAAS_STRIPE_*
--   en .env) o marcado manual. No es un modulo por hotel: es de plataforma.

START TRANSACTION;

CREATE TABLE IF NOT EXISTS saas_cobros (
    id INT NOT NULL AUTO_INCREMENT,
    hotel_id INT NOT NULL,
    periodo CHAR(7) NOT NULL,
    monto DECIMAL(10,2) NOT NULL,
    desglose_json JSON DEFAULT NULL,
    estado ENUM('pendiente','pagado','cancelado') NOT NULL DEFAULT 'pendiente',
    metodo VARCHAR(30) DEFAULT NULL,
    proveedor_pago_id VARCHAR(120) DEFAULT NULL,
    checkout_url VARCHAR(500) DEFAULT NULL,
    pagado_at DATETIME DEFAULT NULL,
    notas VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_saas_cobro_periodo (hotel_id, periodo),
    KEY idx_saas_cobros_estado (estado, periodo),
    CONSTRAINT fk_saas_cobros_hotel
        FOREIGN KEY (hotel_id) REFERENCES hoteles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES ('20260703_005_saas_cobros.sql', 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;
