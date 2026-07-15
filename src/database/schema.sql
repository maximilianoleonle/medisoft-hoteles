
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `alertas_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `alertas_inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `tipo_alerta` enum('STOCK_BAJO','STOCK_CRITICO','SIN_STOCK','STOCK_MAXIMO','CADUCIDAD_PROXIMA') COLLATE utf8mb4_general_ci NOT NULL,
  `mensaje` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `nivel_urgencia` enum('baja','media','alta','critica') COLLATE utf8mb4_general_ci DEFAULT 'media',
  `leida` tinyint(1) DEFAULT '0',
  `fecha_lectura` datetime DEFAULT NULL,
  `usuario_lectura_id` int DEFAULT NULL,
  `resuelta` tinyint(1) DEFAULT '0',
  `fecha_resolucion` datetime DEFAULT NULL,
  `notas_resolucion` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_producto_tipo` (`producto_id`,`tipo_alerta`),
  KEY `idx_no_leidas` (`leida`,`nivel_urgencia`),
  KEY `idx_urgencia` (`nivel_urgencia`,`leida`),
  KEY `usuario_lectura_id` (`usuario_lectura_id`),
  CONSTRAINT `alertas_inventario_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `alertas_inventario_ibfk_2` FOREIGN KEY (`usuario_lectura_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `auditoria_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `auditoria_eventos` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `usuario_nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modulo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `accion` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ruta` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_id` int DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_auditoria_hotel_fecha` (`hotel_id`,`created_at`),
  KEY `idx_auditoria_hotel_modulo` (`hotel_id`,`modulo`,`created_at`),
  KEY `idx_auditoria_hotel_usuario` (`hotel_id`,`usuario_id`,`created_at`),
  CONSTRAINT `fk_auditoria_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cajas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cajas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ubicacion` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto_inicial` decimal(10,2) DEFAULT '0.00',
  `activa` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cajas_hotel_id` (`hotel_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categorias_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('ingreso','gasto','ambos') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `icono` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'fas fa-tag',
  `color` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT '#6B7280',
  `activa` tinyint(1) DEFAULT '1',
  `orden` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_activa` (`activa`),
  KEY `idx_categorias_movimientos_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_categorias_movimientos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `categorias_producto`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `categorias_producto` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `orden` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `checkin_digital_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `checkin_digital_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `reservacion_id` int NOT NULL,
  `token` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','completado','expirado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `datos_json` json DEFAULT NULL,
  `id_documento_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `completado_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `creado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_checkin_token` (`token`),
  UNIQUE KEY `uk_checkin_reservacion` (`hotel_id`,`reservacion_id`),
  KEY `idx_checkin_hotel_estado` (`hotel_id`,`estado`),
  KEY `fk_checkin_reservacion` (`reservacion_id`),
  KEY `fk_checkin_creador` (`creado_por`),
  CONSTRAINT `fk_checkin_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_checkin_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_checkin_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compra_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compra_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `compra_id` int NOT NULL,
  `hotel_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `costo_unitario` decimal(12,2) NOT NULL DEFAULT '0.00',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `movimiento_inventario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_compra_detalles_movimiento` (`movimiento_inventario_id`),
  KEY `idx_compra_detalles_compra` (`compra_id`),
  KEY `idx_compra_detalles_hotel_producto` (`hotel_id`,`producto_id`),
  KEY `fk_compra_detalles_producto` (`producto_id`),
  CONSTRAINT `fk_compra_detalles_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compra_detalles_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compra_detalles_movimiento` FOREIGN KEY (`movimiento_inventario_id`) REFERENCES `movimientos_inventario` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_compra_detalles_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_compra_detalles_cantidad_positiva` CHECK ((`cantidad` > 0)),
  CONSTRAINT `chk_compra_detalles_importes_no_negativos` CHECK (((`costo_unitario` >= 0) and (`subtotal` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `compras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `compras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `proveedor_id` int NOT NULL,
  `folio` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_compra` date NOT NULL,
  `fecha_recepcion` datetime DEFAULT NULL,
  `estado` enum('borrador','recibida','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'borrador',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `impuestos` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `recibida_por` int DEFAULT NULL,
  `cancelada_por` int DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_compras_hotel_folio` (`hotel_id`,`folio`),
  KEY `idx_compras_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_compras_hotel_proveedor` (`hotel_id`,`proveedor_id`),
  KEY `idx_compras_fecha` (`fecha_compra`),
  KEY `idx_compras_recibida_por` (`recibida_por`),
  KEY `idx_compras_cancelada_por` (`cancelada_por`),
  KEY `idx_compras_created_by` (`created_by`),
  KEY `idx_compras_updated_by` (`updated_by`),
  KEY `fk_compras_proveedor` (`proveedor_id`),
  CONSTRAINT `fk_compras_cancelada_por` FOREIGN KEY (`cancelada_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_recibida_por` FOREIGN KEY (`recibida_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_compras_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_compras_recepcion_estado` CHECK ((((`estado` = _latin1'recibida') and (`fecha_recepcion` is not null)) or (`estado` <> _latin1'recibida'))),
  CONSTRAINT `chk_compras_totales_no_negativos` CHECK (((`subtotal` >= 0) and (`impuestos` >= 0) and (`total` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `configuracion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('string','integer','float','boolean','json') COLLATE utf8mb4_unicode_ci DEFAULT 'string',
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `clave` (`clave`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `control_llaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `control_llaves` (
  `id` int NOT NULL AUTO_INCREMENT,
  `habitacion_id` int NOT NULL,
  `reservacion_id` int DEFAULT NULL,
  `estado` enum('disponible','prestada','perdida') COLLATE utf8mb4_unicode_ci DEFAULT 'disponible',
  `fecha_prestamo` datetime DEFAULT NULL,
  `fecha_devolucion` datetime DEFAULT NULL,
  `usuario_presta_id` int DEFAULT NULL,
  `usuario_recibe_id` int DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `tiene_llave` tinyint(1) DEFAULT '1' COMMENT 'Si el hotel tiene la llave',
  `ultima_entrega_at` datetime DEFAULT NULL,
  `ultima_recogida_at` datetime DEFAULT NULL,
  `entregada_por_id` int DEFAULT NULL,
  `recibida_por_id` int DEFAULT NULL,
  `recibida_por_manual` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nombre manual de quien recibió',
  `entregada_por_manual` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Nombre manual de quien entregó',
  PRIMARY KEY (`id`),
  KEY `habitacion_id` (`habitacion_id`),
  KEY `reservacion_id` (`reservacion_id`),
  KEY `usuario_presta_id` (`usuario_presta_id`),
  KEY `usuario_recibe_id` (`usuario_recibe_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_tiene_llave` (`tiene_llave`),
  KEY `fk_entregada_por` (`entregada_por_id`),
  KEY `fk_recibida_por` (`recibida_por_id`),
  CONSTRAINT `fk_entregada_por` FOREIGN KEY (`entregada_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_recibida_por` FOREIGN KEY (`recibida_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `control_remotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `control_remotos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `habitacion_id` int NOT NULL,
  `reservacion_id` int DEFAULT NULL,
  `tiene_remoto` tinyint(1) DEFAULT '1',
  `ultima_entrega_at` datetime DEFAULT NULL,
  `ultima_recogida_at` datetime DEFAULT NULL,
  `entregada_por_id` int DEFAULT NULL,
  `recibida_por_id` int DEFAULT NULL,
  `entregada_por_manual` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recibida_por_manual` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_identificacion` enum('ine','licencia','otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_propietario_ine` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_habitacion` (`habitacion_id`),
  KEY `idx_reservacion` (`reservacion_id`),
  KEY `fk_remoto_entregada_usuario` (`entregada_por_id`),
  KEY `fk_remoto_recibida_usuario` (`recibida_por_id`),
  CONSTRAINT `fk_remoto_entregada_usuario` FOREIGN KEY (`entregada_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_remoto_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`),
  CONSTRAINT `fk_remoto_recibida_usuario` FOREIGN KEY (`recibida_por_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_remoto_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `control_remotos_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `control_remotos_backup` (
  `id` int NOT NULL DEFAULT '0',
  `habitacion_id` int NOT NULL,
  `reservacion_id` int DEFAULT NULL,
  `tiene_remoto` tinyint(1) DEFAULT '1',
  `ultima_entrega_at` datetime DEFAULT NULL,
  `ultima_recogida_at` datetime DEFAULT NULL,
  `entregada_por_id` int DEFAULT NULL,
  `recibida_por_id` int DEFAULT NULL,
  `entregada_por_manual` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `recibida_por_manual` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_identificacion` enum('ine','licencia','otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `copiloto_ia_generaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `copiloto_ia_generaciones` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `tipo` enum('resena','analisis','tarifa') COLLATE utf8mb4_unicode_ci NOT NULL,
  `ref_clave` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contenido` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `veces` int NOT NULL DEFAULT '1',
  `en_prueba` tinyint(1) NOT NULL DEFAULT '0',
  `tokens_entrada` int NOT NULL DEFAULT '0',
  `tokens_salida` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ia_gen` (`hotel_id`,`tipo`,`ref_clave`),
  KEY `idx_ia_gen_prueba` (`hotel_id`,`tipo`,`en_prueba`),
  KEY `idx_ia_gen_fecha` (`hotel_id`,`tipo`,`created_at`),
  KEY `fk_ia_gen_usuario` (`usuario_id`),
  CONSTRAINT `fk_ia_gen_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ia_gen_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `copiloto_mensajes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `copiloto_mensajes` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `pregunta` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fuente` enum('reglas','ia','fallback') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reglas',
  `intent` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tokens_entrada` int NOT NULL DEFAULT '0',
  `tokens_salida` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_copiloto_hotel_fecha` (`hotel_id`,`created_at`),
  KEY `idx_copiloto_hotel_fuente` (`hotel_id`,`fuente`,`created_at`),
  KEY `fk_copiloto_usuario` (`usuario_id`),
  CONSTRAINT `fk_copiloto_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_copiloto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cortes_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cortes_caja` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `caja_id` int NOT NULL,
  `fecha_apertura` datetime NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `monto_inicial` decimal(10,2) NOT NULL DEFAULT '0.00',
  `total_ingresos_efectivo` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_tarjeta` decimal(10,2) DEFAULT '0.00',
  `total_ingresos_transferencia` decimal(10,2) DEFAULT '0.00',
  `total_gastos_efectivo` decimal(10,2) DEFAULT '0.00',
  `total_gastos_tarjeta` decimal(10,2) DEFAULT '0.00',
  `total_gastos_transferencia` decimal(10,2) DEFAULT '0.00',
  `efectivo_esperado` decimal(10,2) DEFAULT '0.00',
  `efectivo_contado` decimal(10,2) DEFAULT NULL,
  `diferencia` decimal(10,2) DEFAULT NULL,
  `estado` enum('abierto','cerrado','cancelado') COLLATE utf8mb4_unicode_ci DEFAULT 'abierto',
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `usuario_apertura_id` int NOT NULL,
  `usuario_cierre_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `caja_id` (`caja_id`),
  KEY `usuario_apertura_id` (`usuario_apertura_id`),
  KEY `usuario_cierre_id` (`usuario_cierre_id`),
  KEY `idx_estado` (`estado`),
  KEY `idx_fecha` (`fecha_apertura`,`fecha_cierre`),
  KEY `idx_cortes_caja_hotel_id` (`hotel_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_por_cobrar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_por_cobrar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `origen_tipo` enum('manual','reservacion','solicitud_factura','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `origen_id` int DEFAULT NULL,
  `huesped_id` int DEFAULT NULL,
  `reservacion_id` int DEFAULT NULL,
  `solicitud_factura_id` int DEFAULT NULL,
  `folio` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','parcial','liquidada','vencida','cancelada','incobrable') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `creado_por_usuario_id` int DEFAULT NULL,
  `actualizado_por_usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cxc_hotel_origen` (`hotel_id`,`origen_tipo`,`origen_id`),
  KEY `idx_cxc_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_cxc_hotel_huesped` (`hotel_id`,`huesped_id`),
  KEY `idx_cxc_hotel_reservacion` (`hotel_id`,`reservacion_id`),
  KEY `idx_cxc_hotel_factura` (`hotel_id`,`solicitud_factura_id`),
  KEY `idx_cxc_fecha_vencimiento` (`fecha_vencimiento`),
  KEY `idx_cxc_creado_por` (`creado_por_usuario_id`),
  KEY `idx_cxc_actualizado_por` (`actualizado_por_usuario_id`),
  KEY `fk_cxc_huesped` (`huesped_id`),
  KEY `fk_cxc_reservacion` (`reservacion_id`),
  KEY `fk_cxc_solicitud_factura` (`solicitud_factura_id`),
  CONSTRAINT `fk_cxc_actualizado_por` FOREIGN KEY (`actualizado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cxc_creado_por` FOREIGN KEY (`creado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cxc_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cxc_huesped` FOREIGN KEY (`huesped_id`) REFERENCES `huespedes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cxc_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cxc_solicitud_factura` FOREIGN KEY (`solicitud_factura_id`) REFERENCES `solicitudes_factura` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_cxc_importes_no_negativos` CHECK (((`total` >= 0) and (`saldo` >= 0))),
  CONSTRAINT `chk_cxc_saldo_no_mayor_total` CHECK ((`saldo` <= `total`))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_por_cobrar_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_por_cobrar_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `cuenta_por_cobrar_id` int NOT NULL,
  `tipo_movimiento` enum('CREACION','AJUSTE','CANCELACION','NOTA','RECLASIFICACION','COBRO') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_anterior` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_posterior` decimal(12,2) NOT NULL DEFAULT '0.00',
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cxc_mov_cuenta` (`cuenta_por_cobrar_id`),
  KEY `idx_cxc_mov_hotel_tipo` (`hotel_id`,`tipo_movimiento`),
  KEY `idx_cxc_mov_usuario` (`usuario_id`),
  CONSTRAINT `fk_cxc_mov_cuenta` FOREIGN KEY (`cuenta_por_cobrar_id`) REFERENCES `cuentas_por_cobrar` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cxc_mov_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cxc_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_cxc_mov_importes_no_negativos` CHECK (((`monto` >= 0) and (`saldo_anterior` >= 0) and (`saldo_posterior` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_por_pagar`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_por_pagar` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `proveedor_id` int NOT NULL,
  `compra_id` int DEFAULT NULL,
  `folio` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_emision` date NOT NULL,
  `fecha_vencimiento` date DEFAULT NULL,
  `estado` enum('pendiente','parcial','pagada','vencida','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `subtotal` decimal(12,2) NOT NULL DEFAULT '0.00',
  `impuestos` decimal(12,2) NOT NULL DEFAULT '0.00',
  `total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_cxp_hotel_compra` (`hotel_id`,`compra_id`),
  KEY `idx_cxp_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_cxp_hotel_proveedor` (`hotel_id`,`proveedor_id`),
  KEY `idx_cxp_proveedor` (`proveedor_id`),
  KEY `idx_cxp_compra` (`compra_id`),
  KEY `idx_cxp_fecha_vencimiento` (`fecha_vencimiento`),
  KEY `idx_cxp_created_by` (`created_by`),
  KEY `idx_cxp_updated_by` (`updated_by`),
  CONSTRAINT `fk_cxp_compra` FOREIGN KEY (`compra_id`) REFERENCES `compras` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cxp_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cxp_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cxp_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cxp_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_cxp_importes_no_negativos` CHECK (((`subtotal` >= 0) and (`impuestos` >= 0) and (`total` >= 0) and (`saldo` >= 0))),
  CONSTRAINT `chk_cxp_saldo_no_mayor_total` CHECK ((`saldo` <= `total`))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cuentas_por_pagar_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `cuentas_por_pagar_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `cuenta_por_pagar_id` int NOT NULL,
  `hotel_id` int NOT NULL,
  `tipo_movimiento` enum('CREACION','AJUSTE','CANCELACION','PAGO_REFERENCIAL') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_anterior` decimal(12,2) NOT NULL DEFAULT '0.00',
  `saldo_posterior` decimal(12,2) NOT NULL DEFAULT '0.00',
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_cxp_mov_cuenta` (`cuenta_por_pagar_id`),
  KEY `idx_cxp_mov_hotel_tipo` (`hotel_id`,`tipo_movimiento`),
  KEY `idx_cxp_mov_usuario` (`usuario_id`),
  CONSTRAINT `fk_cxp_mov_cuenta` FOREIGN KEY (`cuenta_por_pagar_id`) REFERENCES `cuentas_por_pagar` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cxp_mov_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cxp_mov_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_cxp_mov_importes_no_negativos` CHECK (((`monto` >= 0) and (`saldo_anterior` >= 0) and (`saldo_posterior` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `denominaciones_efectivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `denominaciones_efectivo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `corte_id` int NOT NULL,
  `denominacion` decimal(10,2) NOT NULL,
  `cantidad` int NOT NULL DEFAULT '0',
  `subtotal` decimal(10,2) GENERATED ALWAYS AS ((`denominacion` * `cantidad`)) STORED,
  PRIMARY KEY (`id`),
  KEY `corte_id` (`corte_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documento_entidades`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_entidades` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `documento_id` int NOT NULL,
  `entidad_tipo` enum('proveedor','compra','cuenta_por_pagar','huesped','reservacion','trabajador','tarea') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_id` int NOT NULL,
  `relacion` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_documento_entidades_documento_entidad` (`hotel_id`,`documento_id`,`entidad_tipo`,`entidad_id`),
  KEY `idx_documento_entidades_hotel_id` (`hotel_id`),
  KEY `idx_documento_entidades_documento_id` (`documento_id`),
  KEY `idx_documento_entidades_entidad` (`hotel_id`,`entidad_tipo`,`entidad_id`),
  CONSTRAINT `fk_documento_entidades_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_documento_entidades_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_documento_entidades_entidad_id` CHECK ((`entidad_id` > 0))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documento_tipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documento_tipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `clave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime_permitidos` text COLLATE utf8mb4_unicode_ci,
  `max_size_mb` decimal(6,2) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_documento_tipos_hotel_clave` (`hotel_id`,`clave`),
  KEY `idx_documento_tipos_hotel_id` (`hotel_id`),
  KEY `idx_documento_tipos_activo` (`activo`),
  CONSTRAINT `fk_documento_tipos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_documento_tipos_max_size` CHECK (((`max_size_mb` is null) or (`max_size_mb` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `documentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `documento_tipo_id` int DEFAULT NULL,
  `nombre_original` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_archivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `storage_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `size_bytes` bigint unsigned NOT NULL,
  `sha256` char(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `titulo` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `etiquetas` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('activo','archivado','eliminado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `subido_por_usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_documentos_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_documentos_hotel_tipo` (`hotel_id`,`documento_tipo_id`),
  KEY `idx_documentos_documento_tipo_id` (`documento_tipo_id`),
  KEY `idx_documentos_usuario_id` (`subido_por_usuario_id`),
  KEY `idx_documentos_sha256` (`sha256`),
  CONSTRAINT `fk_documentos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_documentos_tipo` FOREIGN KEY (`documento_tipo_id`) REFERENCES `documento_tipos` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_documentos_usuario` FOREIGN KEY (`subido_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_documentos_size_bytes` CHECK ((`size_bytes` > 0))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `habitacion_imagenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `habitacion_imagenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `habitacion_id` int NOT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_principal` tinyint(1) DEFAULT '0',
  `orden` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_habitacion` (`habitacion_id`),
  KEY `idx_habitacion_imagenes_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_habitacion_imagenes_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `habitaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `habitaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `numero` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('sencilla','doble','triple','cuadruple','sencilla_manolo','doble_manolo') COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacidad_personas` int DEFAULT '2',
  `camas_individuales` int DEFAULT '0',
  `camas_matrimoniales` int DEFAULT '1',
  `piso` int NOT NULL,
  `precio_base` decimal(10,2) NOT NULL,
  `estado` enum('disponible','ocupada','mantenimiento','limpieza') COLLATE utf8mb4_unicode_ci DEFAULT 'disponible',
  `caracteristicas` text COLLATE utf8mb4_unicode_ci,
  `observaciones` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tiene_aire_acondicionado` tinyint(1) DEFAULT '0',
  `tiene_tv` tinyint(1) DEFAULT '1',
  `tiene_bano_privado` tinyint(1) DEFAULT '1',
  `foto_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activa` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_habitaciones_hotel_numero` (`hotel_id`,`numero`),
  KEY `idx_estado` (`estado`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_habitaciones_estado_activa` (`estado`,`activa`),
  KEY `idx_habitaciones_hotel_id` (`hotel_id`),
  KEY `idx_habitaciones_hotel_estado` (`hotel_id`,`estado`),
  CONSTRAINT `fk_habitaciones_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_llaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_llaves` (
  `id` int NOT NULL AUTO_INCREMENT,
  `habitacion_id` int NOT NULL,
  `reservacion_id` int DEFAULT NULL,
  `tipo_movimiento` enum('entrega','recogida') COLLATE utf8mb4_general_ci NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `usuario_manual` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_general_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_habitacion` (`habitacion_id`),
  KEY `idx_reservacion` (`reservacion_id`),
  KEY `idx_fecha` (`fecha_hora`),
  KEY `fk_hist_usuario` (`usuario_id`),
  CONSTRAINT `fk_hist_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hist_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_remotos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_remotos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `habitacion_id` int NOT NULL,
  `reservacion_id` int DEFAULT NULL,
  `tipo_movimiento` enum('entrega','recogida') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `usuario_manual` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_identificacion` enum('ine','licencia','otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_propietario_ine` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_habitacion` (`habitacion_id`),
  KEY `idx_reservacion` (`reservacion_id`),
  KEY `idx_fecha` (`fecha_hora`),
  KEY `fk_histremoto_usuario` (`usuario_id`),
  CONSTRAINT `fk_histremoto_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`),
  CONSTRAINT `fk_histremoto_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_histremoto_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `historial_remotos_backup`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `historial_remotos_backup` (
  `id` int NOT NULL DEFAULT '0',
  `habitacion_id` int NOT NULL,
  `reservacion_id` int DEFAULT NULL,
  `tipo_movimiento` enum('entrega','recogida') COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_hora` datetime NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `usuario_manual` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tipo_identificacion` enum('ine','licencia','otro') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotel_branding`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_branding` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `nombre_visual` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `logo_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `favicon_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_background_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pwa_icon_192_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pwa_icon_512_url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_primary` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_secondary` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `color_accent` varchar(7) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sidebar_style` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `login_style` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tema` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cupertino',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_hotel_branding_hotel` (`hotel_id`),
  KEY `idx_hotel_branding_activo` (`activo`),
  CONSTRAINT `fk_hotel_branding_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotel_configuracion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_configuracion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `clave` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `valor` text COLLATE utf8mb4_unicode_ci,
  `tipo` enum('string','integer','float','boolean','json') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'string',
  `grupo` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_feature_flag` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hotel_configuracion_clave` (`hotel_id`,`clave`),
  KEY `idx_hotel_configuracion_hotel` (`hotel_id`),
  KEY `idx_hotel_configuracion_grupo` (`hotel_id`,`grupo`),
  KEY `idx_hotel_configuracion_feature` (`hotel_id`,`es_feature_flag`,`activo`),
  CONSTRAINT `fk_hotel_configuracion_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotel_modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_modulos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `modulo_id` int NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `fuente` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `trial_until` date DEFAULT NULL,
  `config_json` json DEFAULT NULL,
  `precio_override` decimal(10,2) DEFAULT NULL,
  `enabled_by` int DEFAULT NULL,
  `enabled_at` timestamp NULL DEFAULT NULL,
  `disabled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hotel_modulos_hotel_modulo` (`hotel_id`,`modulo_id`),
  KEY `idx_hotel_modulos_hotel` (`hotel_id`),
  KEY `idx_hotel_modulos_modulo` (`modulo_id`),
  KEY `idx_hotel_modulos_activo` (`activo`),
  KEY `fk_hotel_modulos_enabled_by` (`enabled_by`),
  CONSTRAINT `fk_hotel_modulos_enabled_by` FOREIGN KEY (`enabled_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_hotel_modulos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hotel_modulos_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotel_pasarela_credenciales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_pasarela_credenciales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `proveedor` enum('stripe','mercadopago') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'stripe',
  `public_key` varchar(191) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secret_key_encrypted` text COLLATE utf8mb4_unicode_ci,
  `webhook_secret_encrypted` text COLLATE utf8mb4_unicode_ci,
  `modo` enum('test','live') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'test',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_pasarela_hotel` (`hotel_id`),
  CONSTRAINT `fk_pasarela_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotel_usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `rol` enum('superadmin','propietario','gerente','administrador','recepcionista') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'recepcionista',
  `role_id` int DEFAULT NULL,
  `es_principal` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `permisos_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hotel_usuarios_usuario` (`hotel_id`,`usuario_id`),
  KEY `idx_hotel_usuarios_hotel` (`hotel_id`),
  KEY `idx_hotel_usuarios_usuario` (`usuario_id`),
  KEY `idx_hotel_usuarios_rol` (`hotel_id`,`rol`,`activo`),
  KEY `idx_hotel_usuarios_role` (`role_id`),
  CONSTRAINT `fk_hotel_usuarios_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hotel_usuarios_role` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_hotel_usuarios_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hotel_whatsapp_credenciales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hotel_whatsapp_credenciales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `id_instance` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `api_token_encrypted` text COLLATE utf8mb4_unicode_ci,
  `api_host` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'https://api.green-api.com',
  `numero_avisos` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_whatsapp_hotel` (`hotel_id`),
  CONSTRAINT `fk_whatsapp_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `hoteles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `hoteles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razon_social` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ciudad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pais` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Mexico',
  `zona_horaria` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'America/Mexico_City',
  `moneda_codigo` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `moneda_simbolo` varchar(8) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '$',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `plan_id` int DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hoteles_slug` (`slug`),
  UNIQUE KEY `uk_hoteles_codigo` (`codigo`),
  KEY `idx_hoteles_activo` (`activo`),
  KEY `idx_hoteles_plan_id` (`plan_id`),
  CONSTRAINT `fk_hoteles_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `huesped_vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `huesped_vehiculos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `huesped_id` int NOT NULL,
  `marca` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estacionamiento` enum('coches','camionetas','discos','nikkos') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'coches',
  `datos_extra_json` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_huesped_vehiculos_hotel_placas` ((coalesce(`hotel_id`,0)),`placas`),
  KEY `idx_huesped` (`huesped_id`),
  KEY `idx_estacionamiento` (`estacionamiento`),
  KEY `idx_huesped_vehiculos_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_huesped_vehiculos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `huesped_vehiculos_orfanos_archivo`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `huesped_vehiculos_orfanos_archivo` (
  `id` int NOT NULL AUTO_INCREMENT,
  `vehiculo_id` int NOT NULL,
  `hotel_id` int DEFAULT NULL,
  `huesped_id` int NOT NULL,
  `marca` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `placas` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estacionamiento` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `datos_extra_json` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `archived_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `migration_name` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_hvoa_vehiculo_migration` (`vehiculo_id`,`migration_name`),
  KEY `idx_hvoa_huesped_id` (`huesped_id`),
  KEY `idx_hvoa_placas` (`placas`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `huespedes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `huespedes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `nombre_completo` varchar(200) COLLATE utf8mb4_unicode_ci NOT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descuento_tipo` varchar(12) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descuento_valor` decimal(10,2) DEFAULT NULL,
  `procedencia_estado` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `procedencia_ciudad` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehiculo_marca` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `vehiculo_placas` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `datos_extra_json` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nombre` (`nombre_completo`),
  KEY `idx_telefono` (`telefono`),
  KEY `idx_huespedes_hotel_id` (`hotel_id`),
  KEY `idx_huespedes_hotel_nombre` (`hotel_id`,`nombre_completo`),
  CONSTRAINT `fk_huespedes_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `huespedes_vehiculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `huespedes_vehiculos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `huesped_id` int NOT NULL,
  `placas` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `marca` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `modelo` varchar(50) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `color` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `huesped_id` (`huesped_id`),
  KEY `placas` (`placas`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ia_resumenes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ia_resumenes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `tipo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'gerencial_diario',
  `fecha` date NOT NULL,
  `contenido` mediumtext COLLATE utf8mb4_unicode_ci NOT NULL,
  `modelo` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tokens_entrada` int DEFAULT NULL,
  `tokens_salida` int DEFAULT NULL,
  `generado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ia_resumenes_hotel_tipo_fecha` (`hotel_id`,`tipo`,`fecha`),
  KEY `idx_ia_resumenes_hotel_fecha` (`hotel_id`,`fecha`),
  KEY `fk_ia_resumenes_usuario` (`generado_por`),
  CONSTRAINT `fk_ia_resumenes_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ia_resumenes_usuario` FOREIGN KEY (`generado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ical_bloqueos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ical_bloqueos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `habitacion_id` int NOT NULL,
  `feed_id` int NOT NULL,
  `uid` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `resumen` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('activo','liberado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ical_bloqueo_uid` (`feed_id`,`uid`),
  KEY `idx_ical_bloqueos_disponibilidad` (`hotel_id`,`estado`,`fecha_inicio`,`fecha_fin`),
  KEY `idx_ical_bloqueos_habitacion` (`habitacion_id`,`estado`),
  CONSTRAINT `fk_ical_bloqueos_feed` FOREIGN KEY (`feed_id`) REFERENCES `ical_feeds` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ical_bloqueos_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ical_bloqueos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ical_feeds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `ical_feeds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `habitacion_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `url` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `last_sync_at` datetime DEFAULT NULL,
  `last_sync_estado` enum('ok','error') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `last_sync_error` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `eventos_activos` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_ical_feeds_hotel` (`hotel_id`,`activo`),
  KEY `idx_ical_feeds_habitacion` (`habitacion_id`),
  CONSTRAINT `fk_ical_feeds_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ical_feeds_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `incrementos_tarifas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `incrementos_tarifas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Nombre descriptivo del incremento',
  `descripcion` text COLLATE utf8mb4_unicode_ci COMMENT 'Descripción o motivo del incremento',
  `tipo_incremento` enum('porcentaje','monto_fijo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje',
  `clase` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'incremento',
  `valor_incremento` decimal(10,2) NOT NULL COMMENT 'Valor del incremento (% o monto)',
  `alcance` enum('global','tipo_habitacion','habitacion') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'global',
  `tipos_habitacion` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Array de tipos si alcance=tipo_habitacion',
  `habitaciones` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin COMMENT 'Array de IDs si alcance=habitacion',
  `es_permanente` tinyint(1) DEFAULT '0',
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL COMMENT 'NULL si es permanente',
  `activo` tinyint(1) DEFAULT '1',
  `prioridad` int DEFAULT '0' COMMENT 'Mayor prioridad se aplica primero',
  `usuario_id` int NOT NULL,
  `origen` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'NULL=manual; copiloto=sugerencia IA confirmada por un humano',
  `consejo_ref` bigint DEFAULT NULL COMMENT 'id de copiloto_ia_generaciones del consejo que origino el ajuste',
  `aprobado_por` int DEFAULT NULL COMMENT 'usuario que confirmo la sugerencia del copiloto',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_fechas_activo` (`fecha_inicio`,`fecha_fin`,`activo`),
  KEY `idx_prioridad` (`prioridad` DESC,`created_at` DESC),
  KEY `idx_incrementos_tarifas_hotel_fecha` (`hotel_id`,`activo`,`fecha_inicio`,`fecha_fin`),
  CONSTRAINT `fk_incrementos_tarifas_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `incrementos_tarifas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `incrementos_tarifas_chk_1` CHECK (json_valid(`tipos_habitacion`)),
  CONSTRAINT `incrementos_tarifas_chk_2` CHECK (json_valid(`habitaciones`))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Control de incrementos de tarifas temporales y permanentes';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventario_categorias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `orden` int DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_inventario_categorias_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_inventario_categorias_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventario_config_habitacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_config_habitacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `tipo_habitacion` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad_descontar` decimal(10,2) DEFAULT '1.00',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventario_config_hotel_tipo_producto` (`hotel_id`,`tipo_habitacion`,`producto_id`),
  KEY `inventario_config_habitacion_ibfk_1` (`producto_id`),
  KEY `idx_inventario_config_habitacion_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_inventario_config_habitacion_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `inventario_config_habitacion_ibfk_1` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventario_habitacion_config`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_habitacion_config` (
  `id` int NOT NULL AUTO_INCREMENT,
  `habitacion_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad_por_checkin` decimal(10,2) NOT NULL DEFAULT '1.00',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `habitacion_id` (`habitacion_id`),
  KEY `producto_id` (`producto_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventario_movimientos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `tipo_movimiento` enum('entrada','salida','ajuste') COLLATE utf8mb4_general_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_anterior` decimal(10,2) DEFAULT NULL,
  `stock_posterior` decimal(10,2) DEFAULT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `habitacion_id` int DEFAULT NULL,
  `reservacion_id` int DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `usuario_id` int NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `producto_id` (`producto_id`),
  KEY `reservacion_id` (`reservacion_id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `inventario_productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventario_productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `codigo` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_general_ci,
  `categoria_id` int NOT NULL,
  `unidad_medida` varchar(20) COLLATE utf8mb4_general_ci NOT NULL DEFAULT 'unidad',
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) NOT NULL DEFAULT '0.00',
  `costo_unitario` decimal(10,2) DEFAULT '0.00',
  `descuento_automatico` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inventario_productos_hotel_codigo` (`hotel_id`,`codigo`),
  KEY `fk_categoria` (`categoria_id`),
  KEY `idx_inventario_productos_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `inventario_categorias` (`id`),
  CONSTRAINT `fk_inventario_productos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `lealtad_cupones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `lealtad_cupones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `huesped_id` int NOT NULL,
  `cupon_id` int NOT NULL,
  `estancias_al_generar` int NOT NULL DEFAULT '0',
  `correo_enviado_at` datetime DEFAULT NULL,
  `creado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_lealtad_cupon` (`cupon_id`),
  KEY `idx_lealtad_huesped` (`hotel_id`,`huesped_id`,`created_at`),
  KEY `fk_lealtad_huesped` (`huesped_id`),
  KEY `fk_lealtad_creador` (`creado_por`),
  CONSTRAINT `fk_lealtad_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_lealtad_cupon` FOREIGN KEY (`cupon_id`) REFERENCES `motor_cupones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lealtad_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_lealtad_huesped` FOREIGN KEY (`huesped_id`) REFERENCES `huespedes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `login_intentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `login_intentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` char(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'SHA2 del alcance: ip|usuario|hotel_slug o ip global',
  `ip` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_usuario` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `hotel_slug` varchar(120) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `intentos` int NOT NULL DEFAULT '0',
  `bloqueado_hasta` datetime DEFAULT NULL,
  `ultimo_intento` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_login_intentos_clave` (`clave`),
  KEY `idx_login_intentos_ultimo_intento` (`ultimo_intento`),
  KEY `idx_login_intentos_ip` (`ip`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs_acceso`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_acceso` (
  `id` int NOT NULL AUTO_INCREMENT,
  `tipo` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `exitoso` tinyint(1) DEFAULT '1',
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text COLLATE utf8mb4_unicode_ci,
  `detalles` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_fecha` (`created_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `logs_auditoria`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `logs_auditoria` (
  `id` bigint NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `accion` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_tipo` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `datos_antes` json DEFAULT NULL,
  `datos_despues` json DEFAULT NULL,
  `ip` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_logs_auditoria_hotel_fecha` (`hotel_id`,`created_at`),
  KEY `idx_logs_auditoria_usuario_fecha` (`usuario_id`,`created_at`),
  KEY `idx_logs_auditoria_entidad` (`entidad_tipo`,`entidad_id`),
  KEY `idx_logs_auditoria_accion` (`accion`),
  CONSTRAINT `fk_logs_auditoria_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_logs_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mantenimientos_habitaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `mantenimientos_habitaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `habitacion_id` int NOT NULL,
  `tipo_mantenimiento` enum('preventivo','correctivo','emergencia','limpieza_profunda') COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `prioridad` enum('baja','media','alta','urgente') COLLATE utf8mb4_unicode_ci DEFAULT 'media',
  `fecha_inicio` datetime NOT NULL,
  `fecha_programada` date DEFAULT NULL,
  `fecha_programada_fin` date DEFAULT NULL,
  `programado` tinyint(1) NOT NULL DEFAULT '0',
  `fecha_fin` datetime DEFAULT NULL,
  `estado` enum('en_proceso','completado','cancelado','programado') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'en_proceso',
  `realizado_por` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `costo` decimal(10,2) DEFAULT NULL,
  `observaciones` text COLLATE utf8mb4_unicode_ci,
  `usuario_registro_id` int NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_habitacion` (`habitacion_id`),
  KEY `idx_fecha_inicio` (`fecha_inicio`),
  KEY `idx_estado` (`estado`),
  KEY `usuario_registro_id` (`usuario_registro_id`),
  KEY `idx_programado` (`programado`,`fecha_programada`,`fecha_programada_fin`,`estado`),
  KEY `idx_habitacion_programado` (`habitacion_id`,`programado`,`estado`),
  KEY `idx_mantenimientos_habitaciones_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_mantenimientos_habitaciones_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Registro histórico de mantenimientos realizados a las habitaciones';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `migrations` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(191) COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL DEFAULT '1',
  `checksum` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('ejecutada','fallida','revertida') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'ejecutada',
  `ejecutada_en` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_migrations_nombre` (`nombre`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `modulos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `categoria` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_core` tinyint(1) NOT NULL DEFAULT '0',
  `precio_mensual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `activo_global` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `icono` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruta_base` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_modulos_clave` (`clave`),
  KEY `idx_modulos_activo_global` (`activo_global`),
  KEY `idx_modulos_categoria` (`categoria`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motor_cupones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motor_cupones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `codigo` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('porcentaje','monto') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'porcentaje',
  `valor` decimal(10,2) NOT NULL,
  `vigente_desde` date DEFAULT NULL,
  `vigente_hasta` date DEFAULT NULL,
  `limite_usos` int DEFAULT NULL,
  `usos` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_motor_cupones_codigo` (`hotel_id`,`codigo`),
  KEY `idx_motor_cupones_hotel_activo` (`hotel_id`,`activo`),
  KEY `fk_motor_cupones_creador` (`creado_por`),
  CONSTRAINT `fk_motor_cupones_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_motor_cupones_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motor_extras`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motor_extras` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `tipo_cobro` enum('por_reserva','por_noche','por_persona','por_persona_noche') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'por_reserva',
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `creado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_motor_extras_hotel_activo` (`hotel_id`,`activo`,`orden`),
  KEY `fk_motor_extras_creador` (`creado_por`),
  CONSTRAINT `fk_motor_extras_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_motor_extras_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motor_holds`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motor_holds` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `token` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `habitacion_ids_json` json NOT NULL,
  `fecha_entrada` date NOT NULL,
  `fecha_salida` date NOT NULL,
  `pago_online_id` int DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_motor_holds_token` (`token`),
  KEY `idx_motor_holds_hotel_exp` (`hotel_id`,`expires_at`),
  KEY `fk_motor_holds_pago` (`pago_online_id`),
  CONSTRAINT `fk_motor_holds_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_motor_holds_pago` FOREIGN KEY (`pago_online_id`) REFERENCES `motor_pagos_online` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `motor_pagos_online`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `motor_pagos_online` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `reservacion_id` int DEFAULT NULL,
  `proveedor` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `proveedor_pago_id` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `moneda` char(3) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MXN',
  `estado` enum('pendiente','procesando','pagado','conciliado','reembolsado','fallido','expirado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `huesped_nombre` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `huesped_email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `huesped_telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payload_json` json DEFAULT NULL,
  `abono_id` int DEFAULT NULL,
  `conciliado_por` int DEFAULT NULL,
  `conciliado_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_motor_pagos_proveedor` (`proveedor`,`proveedor_pago_id`),
  KEY `idx_motor_pagos_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_motor_pagos_reservacion` (`reservacion_id`),
  KEY `fk_motor_pagos_conciliador` (`conciliado_por`),
  CONSTRAINT `fk_motor_pagos_conciliador` FOREIGN KEY (`conciliado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_motor_pagos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_motor_pagos_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_caja` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `tipo` enum('ingreso','gasto') COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria_id` int DEFAULT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia') COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comprobante` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proveedor` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `reservacion_id` int DEFAULT NULL,
  `usuario_id` int NOT NULL,
  `corte_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `editado` tinyint(1) DEFAULT '0',
  `motivo_edicion` text COLLATE utf8mb4_unicode_ci,
  `usuario_edicion_id` int DEFAULT NULL,
  `fecha_edicion` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reservacion_id` (`reservacion_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_fecha` (`created_at`),
  KEY `idx_corte` (`corte_id`),
  KEY `fk_categoria_movimiento` (`categoria_id`),
  KEY `fk_usuario_edicion` (`usuario_edicion_id`),
  KEY `idx_corte_id` (`corte_id`),
  KEY `idx_reservacion_id` (`reservacion_id`),
  KEY `idx_movimientos_caja_hotel_id` (`hotel_id`),
  KEY `idx_movimientos_hotel_tipo_created` (`hotel_id`,`tipo`,`created_at`),
  KEY `idx_movimientos_hotel_created` (`hotel_id`,`created_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `movimientos_inventario`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `movimientos_inventario` (
  `id` int NOT NULL AUTO_INCREMENT,
  `producto_id` int NOT NULL,
  `hotel_id` int DEFAULT NULL,
  `tipo_movimiento` enum('ENTRADA','SALIDA','AJUSTE') COLLATE utf8mb4_unicode_ci NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `stock_anterior` decimal(10,2) NOT NULL,
  `stock_posterior` decimal(10,2) NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `habitacion_id` int DEFAULT NULL,
  `reservacion_id` int DEFAULT NULL,
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `habitacion_id` (`habitacion_id`),
  KEY `reservacion_id` (`reservacion_id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_fecha` (`created_at`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_movimientos_inventario_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_movimientos_inventario_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_movimientos_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`),
  CONSTRAINT `movimientos_inventario_ibfk_2` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`),
  CONSTRAINT `movimientos_inventario_ibfk_3` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`),
  CONSTRAINT `movimientos_inventario_ibfk_4` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `night_audit_cierres`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `night_audit_cierres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `fecha` date NOT NULL,
  `hallazgos_json` json DEFAULT NULL,
  `no_shows` int NOT NULL DEFAULT '0',
  `checkouts_vencidos` int NOT NULL DEFAULT '0',
  `cortes_abiertos` int NOT NULL DEFAULT '0',
  `llegadas` int NOT NULL DEFAULT '0',
  `salidas` int NOT NULL DEFAULT '0',
  `ocupadas_noche` int NOT NULL DEFAULT '0',
  `pagos_online` int NOT NULL DEFAULT '0',
  `monto_online` decimal(10,2) NOT NULL DEFAULT '0.00',
  `correo_enviado_at` datetime DEFAULT NULL,
  `ejecutado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_night_audit_fecha` (`hotel_id`,`fecha`),
  KEY `idx_night_audit_hotel` (`hotel_id`,`fecha`),
  KEY `fk_night_audit_ejecutor` (`ejecutado_por`),
  CONSTRAINT `fk_night_audit_ejecutor` FOREIGN KEY (`ejecutado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_night_audit_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_conceptos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_conceptos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('percepcion','deduccion') COLLATE utf8mb4_unicode_ci NOT NULL,
  `clasificacion` enum('sueldo','bono','comision','horas_extra','propina','destajo','descuento','anticipo','prestamo','ajuste','otro') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'otro',
  `modo_calculo` enum('manual','monto_fijo','por_cantidad') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `monto_default` decimal(12,2) DEFAULT NULL,
  `gravable_isr` tinyint(1) NOT NULL DEFAULT '0',
  `gravable_imss` tinyint(1) NOT NULL DEFAULT '0',
  `es_sistema` tinyint(1) NOT NULL DEFAULT '0',
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_conceptos_hotel_nombre` (`hotel_id`,`nombre`),
  KEY `idx_nomina_conceptos_hotel_activo` (`hotel_id`,`activo`),
  KEY `idx_nomina_conceptos_hotel_tipo` (`hotel_id`,`tipo`,`activo`),
  KEY `fk_nomina_conceptos_created_by` (`created_by`),
  KEY `fk_nomina_conceptos_updated_by` (`updated_by`),
  CONSTRAINT `fk_nomina_conceptos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_conceptos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_conceptos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_nomina_conceptos_monto` CHECK (((`monto_default` is null) or (`monto_default` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_departamentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_departamentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_departamentos_hotel_nombre` (`hotel_id`,`nombre`),
  KEY `idx_nomina_departamentos_hotel_activo` (`hotel_id`,`activo`),
  KEY `fk_nomina_departamentos_created_by` (`created_by`),
  KEY `fk_nomina_departamentos_updated_by` (`updated_by`),
  CONSTRAINT `fk_nomina_departamentos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_departamentos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_departamentos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_grupos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_grupos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodicidad` enum('semanal','quincenal','mensual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'quincenal',
  `dia_corte` tinyint DEFAULT NULL COMMENT 'semanal: dia ISO 1-7; quincenal/mensual: dia del mes 1-31',
  `dia_pago` tinyint DEFAULT NULL COMMENT 'mismo criterio que dia_corte',
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_grupos_hotel_nombre` (`hotel_id`,`nombre`),
  KEY `idx_nomina_grupos_hotel_activo` (`hotel_id`,`activo`),
  KEY `fk_nomina_grupos_created_by` (`created_by`),
  KEY `fk_nomina_grupos_updated_by` (`updated_by`),
  CONSTRAINT `fk_nomina_grupos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_grupos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_grupos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_nomina_grupos_dia_corte` CHECK (((`dia_corte` is null) or (`dia_corte` between 1 and 31))),
  CONSTRAINT `chk_nomina_grupos_dia_pago` CHECK (((`dia_pago` is null) or (`dia_pago` between 1 and 31)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_incidencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_incidencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `concepto_id` int NOT NULL,
  `fecha` date NOT NULL,
  `cantidad` decimal(8,2) DEFAULT NULL,
  `monto` decimal(12,2) DEFAULT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `origen` enum('manual','adaptador','api') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `referencia_origen` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('pendiente','aprobada','rechazada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aprobada',
  `aprobado_por` int DEFAULT NULL,
  `aprobado_at` datetime DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nomina_incidencias_hotel_trab_fecha` (`hotel_id`,`trabajador_id`,`fecha`),
  KEY `idx_nomina_incidencias_hotel_estado` (`hotel_id`,`estado`,`fecha`),
  KEY `idx_nomina_incidencias_concepto` (`concepto_id`),
  KEY `fk_nomina_incidencias_trabajador` (`trabajador_id`),
  KEY `fk_nomina_incidencias_aprobado_por` (`aprobado_por`),
  KEY `fk_nomina_incidencias_created_by` (`created_by`),
  KEY `fk_nomina_incidencias_updated_by` (`updated_by`),
  CONSTRAINT `fk_nomina_incidencias_aprobado_por` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_incidencias_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `nomina_conceptos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_nomina_incidencias_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_incidencias_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_incidencias_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_nomina_incidencias_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_nomina_incidencias_cantidad` CHECK (((`cantidad` is null) or (`cantidad` > 0))),
  CONSTRAINT `chk_nomina_incidencias_monto` CHECK (((`monto` is null) or (`monto` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_periodo_conceptos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_periodo_conceptos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `periodo_id` int NOT NULL,
  `detalle_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `concepto_id` int DEFAULT NULL,
  `concepto_nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` enum('percepcion','deduccion') COLLATE utf8mb4_unicode_ci NOT NULL,
  `clasificacion` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'otro',
  `origen` enum('salario','incidencia','ledger','manual','fiscal') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `cantidad` decimal(8,2) DEFAULT NULL,
  `base` decimal(12,2) DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `referencia` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nomina_periodo_conceptos_periodo` (`periodo_id`),
  KEY `idx_nomina_periodo_conceptos_detalle` (`detalle_id`),
  KEY `idx_nomina_periodo_conceptos_hotel_trab` (`hotel_id`,`trabajador_id`),
  KEY `fk_nomina_periodo_conceptos_trabajador` (`trabajador_id`),
  KEY `fk_nomina_periodo_conceptos_concepto` (`concepto_id`),
  CONSTRAINT `fk_nomina_periodo_conceptos_concepto` FOREIGN KEY (`concepto_id`) REFERENCES `nomina_conceptos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_periodo_conceptos_detalle` FOREIGN KEY (`detalle_id`) REFERENCES `trabajador_nomina_periodo_detalles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_nomina_periodo_conceptos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_periodo_conceptos_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `trabajador_nomina_periodos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_nomina_periodo_conceptos_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_nomina_periodo_conceptos_monto` CHECK ((`monto` >= 0))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_puestos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_puestos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `departamento_id` int DEFAULT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `salario_sugerido` decimal(12,2) DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_puestos_hotel_nombre` (`hotel_id`,`nombre`),
  KEY `idx_nomina_puestos_hotel_activo` (`hotel_id`,`activo`),
  KEY `idx_nomina_puestos_departamento` (`departamento_id`),
  KEY `fk_nomina_puestos_created_by` (`created_by`),
  KEY `fk_nomina_puestos_updated_by` (`updated_by`),
  CONSTRAINT `fk_nomina_puestos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_puestos_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `nomina_departamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_puestos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_puestos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_nomina_puestos_salario` CHECK (((`salario_sugerido` is null) or (`salario_sugerido` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_recibos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_recibos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `periodo_id` int NOT NULL,
  `detalle_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `folio_numero` int NOT NULL,
  `folio` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trabajador_nombre` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodo_etiqueta` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `percepciones` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deducciones` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deducciones_informativas` decimal(12,2) NOT NULL DEFAULT '0.00',
  `neto` decimal(12,2) NOT NULL DEFAULT '0.00',
  `lineas_json` json DEFAULT NULL,
  `estado` enum('emitido','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'emitido',
  `cancelacion_uk` int NOT NULL DEFAULT '0',
  `emitido_por` int DEFAULT NULL,
  `emitido_at` datetime NOT NULL,
  `cancelado_por` int DEFAULT NULL,
  `cancelado_at` datetime DEFAULT NULL,
  `motivo_cancelacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_recibos_folio` (`hotel_id`,`folio_numero`),
  UNIQUE KEY `uk_nomina_recibos_detalle_vigente` (`detalle_id`,`cancelacion_uk`),
  KEY `idx_nomina_recibos_hotel_periodo` (`hotel_id`,`periodo_id`),
  KEY `idx_nomina_recibos_trabajador` (`hotel_id`,`trabajador_id`),
  KEY `fk_nomina_recibos_periodo` (`periodo_id`),
  KEY `fk_nomina_recibos_trabajador` (`trabajador_id`),
  KEY `fk_nomina_recibos_emitido_por` (`emitido_por`),
  KEY `fk_nomina_recibos_cancelado_por` (`cancelado_por`),
  CONSTRAINT `fk_nomina_recibos_cancelado_por` FOREIGN KEY (`cancelado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_recibos_detalle` FOREIGN KEY (`detalle_id`) REFERENCES `trabajador_nomina_periodo_detalles` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_nomina_recibos_emitido_por` FOREIGN KEY (`emitido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_recibos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_recibos_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `trabajador_nomina_periodos` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `fk_nomina_recibos_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_nomina_recibos_montos` CHECK (((`percepciones` >= 0) and (`deducciones` >= 0) and (`neto` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_reglas_legales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_reglas_legales` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pais` char(2) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'MX',
  `tipo_regla` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ejercicio` smallint NOT NULL,
  `vigente_desde` date NOT NULL,
  `vigente_hasta` date DEFAULT NULL,
  `valor` decimal(12,4) DEFAULT NULL,
  `valores_json` json DEFAULT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fuente` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_reglas_pais_tipo_desde` (`pais`,`tipo_regla`,`vigente_desde`),
  KEY `idx_nomina_reglas_tipo_vigencia` (`pais`,`tipo_regla`,`estado`,`vigente_desde`),
  KEY `idx_nomina_reglas_ejercicio` (`pais`,`ejercicio`),
  KEY `fk_nomina_reglas_created_by` (`created_by`),
  KEY `fk_nomina_reglas_updated_by` (`updated_by`),
  CONSTRAINT `fk_nomina_reglas_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_reglas_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `chk_nomina_reglas_rango` CHECK (((`vigente_hasta` is null) or (`vigente_hasta` >= `vigente_desde`))),
  CONSTRAINT `chk_nomina_reglas_valor` CHECK (((`valor` is null) or (`valor` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_reglas_legales_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_reglas_legales_eventos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `regla_id` int NOT NULL,
  `accion` enum('creada','actualizada','desactivada','reactivada') COLLATE utf8mb4_unicode_ci NOT NULL,
  `datos_antes` json DEFAULT NULL,
  `datos_despues` json DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_nomina_reglas_eventos_regla` (`regla_id`,`created_at`),
  KEY `fk_nomina_reglas_eventos_created_by` (`created_by`),
  CONSTRAINT `fk_nomina_reglas_eventos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_reglas_eventos_regla` FOREIGN KEY (`regla_id`) REFERENCES `nomina_reglas_legales` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `nomina_tipos_contrato`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `nomina_tipos_contrato` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `orden` int NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_nomina_tipos_contrato_hotel_nombre` (`hotel_id`,`nombre`),
  KEY `idx_nomina_tipos_contrato_hotel_activo` (`hotel_id`,`activo`),
  KEY `fk_nomina_tipos_contrato_created_by` (`created_by`),
  KEY `fk_nomina_tipos_contrato_updated_by` (`updated_by`),
  CONSTRAINT `fk_nomina_tipos_contrato_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_nomina_tipos_contrato_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_nomina_tipos_contrato_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `notificaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `notificaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `rol_destino` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `modulo` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `severidad` enum('info','media','alta','critica') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'info',
  `titulo` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mensaje` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entidad_tipo` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `entidad_id` int DEFAULT NULL,
  `url` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('nueva','leida','resuelta','descartada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'nueva',
  `dedupe_key` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `leida_en` datetime DEFAULT NULL,
  `resuelta_en` datetime DEFAULT NULL,
  `descartada_en` datetime DEFAULT NULL,
  `creada_por` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notificaciones_hotel_dedupe` (`hotel_id`,`dedupe_key`),
  KEY `idx_notificaciones_hotel_estado_created` (`hotel_id`,`estado`,`created_at`),
  KEY `idx_notificaciones_hotel_modulo_created` (`hotel_id`,`modulo`,`created_at`),
  KEY `idx_notificaciones_hotel_severidad_created` (`hotel_id`,`severidad`,`created_at`),
  KEY `idx_notificaciones_usuario_estado` (`usuario_id`,`estado`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `operaciones_sync`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `operaciones_sync` (
  `uuid` char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipo` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `procesado_at` datetime NOT NULL,
  `resultado` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `detalle` text COLLATE utf8mb4_unicode_ci,
  PRIMARY KEY (`uuid`),
  KEY `idx_operaciones_sync_resultado` (`resultado`),
  KEY `idx_operaciones_sync_usuario` (`usuario_id`),
  KEY `idx_operaciones_sync_procesado` (`procesado_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plan_modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `plan_modulos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `plan_id` int NOT NULL,
  `modulo_id` int NOT NULL,
  `incluido` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_plan_modulos_plan_modulo` (`plan_id`,`modulo_id`),
  KEY `idx_plan_modulos_plan` (`plan_id`),
  KEY `idx_plan_modulos_modulo` (`modulo_id`),
  KEY `idx_plan_modulos_incluido` (`incluido`),
  CONSTRAINT `fk_plan_modulos_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_modulos_plan` FOREIGN KEY (`plan_id`) REFERENCES `planes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `planes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `clave` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `orden` int NOT NULL DEFAULT '0',
  `precio_mensual` decimal(10,2) DEFAULT NULL,
  `moneda_codigo` char(3) COLLATE utf8mb4_unicode_ci DEFAULT 'MXN',
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_planes_clave` (`clave`),
  KEY `idx_planes_activo` (`activo`),
  KEY `idx_planes_orden` (`orden`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `productos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `categoria_id` int NOT NULL,
  `stock_actual` decimal(10,2) DEFAULT '0.00',
  `stock_minimo` decimal(10,2) DEFAULT '0.00',
  `precio_unitario` decimal(10,2) DEFAULT '0.00',
  `descuento_automatico` tinyint(1) DEFAULT '0',
  `activo` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `codigo` (`codigo`),
  KEY `categoria_id` (`categoria_id`),
  KEY `idx_descuento_auto` (`descuento_automatico`),
  CONSTRAINT `productos_ibfk_1` FOREIGN KEY (`categoria_id`) REFERENCES `categorias_producto` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `proveedores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `proveedores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `nombre` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_activo_key` varchar(160) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci GENERATED ALWAYS AS ((case when (`activo` = 1) then `nombre` else NULL end)) STORED,
  `razon_social` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rfc` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(40) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `direccion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_proveedores_hotel_rfc` (`hotel_id`,`rfc`),
  UNIQUE KEY `uk_proveedores_hotel_nombre_activo` (`hotel_id`,`nombre_activo_key`),
  KEY `idx_proveedores_hotel_activo` (`hotel_id`,`activo`),
  KEY `idx_proveedores_hotel_nombre` (`hotel_id`,`nombre`),
  KEY `idx_proveedores_hotel_rfc` (`hotel_id`,`rfc`),
  KEY `idx_proveedores_created_by` (`created_by`),
  KEY `idx_proveedores_updated_by` (`updated_by`),
  CONSTRAINT `fk_proveedores_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_proveedores_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_proveedores_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `push_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `endpoint` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `auth` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_endpoint` (`user_id`,`endpoint`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `pwa_push_subscriptions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `pwa_push_subscriptions` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `endpoint_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `endpoint` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `p256dh` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `auth` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `navegador` varchar(180) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `last_seen_at` datetime DEFAULT NULL,
  `revoked_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pwa_push_hotel_endpoint` (`hotel_id`,`endpoint_hash`),
  KEY `idx_pwa_push_hotel_activo` (`hotel_id`,`activo`),
  KEY `idx_pwa_push_usuario_activo` (`usuario_id`,`activo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `remember_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `remember_tokens` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `hotel_id` int DEFAULT NULL,
  `token` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` datetime NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `idx_token` (`token`),
  KEY `idx_expires` (`expires_at`),
  KEY `idx_remember_tokens_hotel` (`hotel_id`),
  CONSTRAINT `fk_remember_tokens_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reporte_link_envios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reporte_link_envios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `reporte_link_id` int NOT NULL,
  `hotel_id` int NOT NULL,
  `canal` enum('email') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'email',
  `destinatarios` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `asunto` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('enviado','fallido') COLLATE utf8mb4_unicode_ci NOT NULL,
  `error_mensaje` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enviado_por` int DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reporte_link_envios_link_created` (`reporte_link_id`,`created_at`),
  KEY `idx_reporte_link_envios_hotel_created` (`hotel_id`,`created_at`),
  KEY `idx_reporte_link_envios_estado` (`estado`,`created_at`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reporte_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reporte_links` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `tipo_reporte` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL,
  `titulo` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `archivo_path` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `archivo_nombre` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'application/pdf',
  `tamano_bytes` bigint unsigned DEFAULT NULL,
  `parametros_json` text COLLATE utf8mb4_unicode_ci,
  `token_hash` char(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `token_hint` char(8) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('activo','expirado','revocado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `expira_en` datetime NOT NULL,
  `creado_por` int DEFAULT NULL,
  `primer_acceso_en` datetime DEFAULT NULL,
  `ultimo_acceso_en` datetime DEFAULT NULL,
  `accesos` int unsigned NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reporte_links_token_hash` (`token_hash`),
  KEY `idx_reporte_links_hotel_created` (`hotel_id`,`created_at`),
  KEY `idx_reporte_links_hotel_estado` (`hotel_id`,`estado`,`expira_en`),
  KEY `idx_reporte_links_expira_estado` (`estado`,`expira_en`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reputacion_encuestas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reputacion_encuestas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `reservacion_id` int NOT NULL,
  `token` char(32) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado` enum('pendiente','enviada','respondida','expirada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `calificacion` tinyint DEFAULT NULL,
  `nps` tinyint DEFAULT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `canal_envio` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `enviada_at` timestamp NULL DEFAULT NULL,
  `respondida_at` timestamp NULL DEFAULT NULL,
  `expires_at` datetime NOT NULL,
  `creado_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_reputacion_token` (`token`),
  UNIQUE KEY `uk_reputacion_reservacion` (`hotel_id`,`reservacion_id`),
  KEY `idx_reputacion_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_reputacion_hotel_respondida` (`hotel_id`,`respondida_at`),
  KEY `fk_reputacion_reservacion` (`reservacion_id`),
  KEY `fk_reputacion_creador` (`creado_por`),
  CONSTRAINT `fk_reputacion_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_reputacion_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_reputacion_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reservacion_abonos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservacion_abonos` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `reservacion_id` int unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'efectivo',
  `noches_cubiertas` tinyint unsigned NOT NULL DEFAULT '1' COMMENT 'Cuántas noches cubre este abono',
  `concepto` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Abono de hospedaje',
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Folio, número de transferencia, etc.',
  `usuario_id` int unsigned NOT NULL,
  `corte_id` int unsigned DEFAULT NULL COMMENT 'Corte de caja al que pertenece',
  `movimiento_caja_id` int unsigned DEFAULT NULL COMMENT 'Referencia al movimiento en caja',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `requiere_factura` enum('si','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `tipo_tarjeta` enum('credito','debito','') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_reservacion` (`reservacion_id`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_corte` (`corte_id`),
  KEY `idx_reservacion_abonos_hotel_id` (`hotel_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Abonos parciales por noche de una reservación';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reservacion_habitaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservacion_habitaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `reservacion_id` int NOT NULL,
  `habitacion_id` int NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `es_cortesia` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `reservacion_id` (`reservacion_id`),
  KEY `habitacion_id` (`habitacion_id`),
  KEY `idx_reservacion_habitaciones_hotel_id` (`hotel_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reservacion_notas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservacion_notas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `reservacion_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `nota` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`),
  KEY `idx_reservacion` (`reservacion_id`),
  KEY `idx_fecha` (`created_at` DESC),
  KEY `idx_reservacion_notas_hotel_id` (`hotel_id`),
  CONSTRAINT `reservacion_notas_ibfk_1` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reservacion_notas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reservacion_pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservacion_pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `reservacion_id` int NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `referencia` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL COMMENT 'Número de autorización tarjeta o referencia transferencia',
  `tipo_tarjeta` enum('credito','debito','') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reservacion` (`reservacion_id`),
  KEY `idx_metodo_pago` (`metodo_pago`),
  KEY `idx_fecha` (`created_at`),
  KEY `idx_reservacion_pagos_hotel_id` (`hotel_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Detalle de pagos mixtos por reservación';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `reservaciones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `reservaciones` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `huesped_id` int NOT NULL,
  `total_habitaciones` int DEFAULT '1',
  `habitaciones_cortesia` int DEFAULT '0',
  `fecha_entrada` date NOT NULL,
  `hora_llegada_estimada` time DEFAULT NULL,
  `hora_entrada` time DEFAULT NULL,
  `fecha_salida` date NOT NULL,
  `hora_salida` time DEFAULT NULL,
  `precio_total` decimal(10,2) NOT NULL,
  `descuento_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `descuento_detalle_json` text COLLATE utf8mb4_unicode_ci,
  `monto_recibido` decimal(10,2) DEFAULT NULL COMMENT 'Monto total recibido del cliente',
  `cambio` decimal(10,2) DEFAULT NULL COMMENT 'Cambio devuelto al cliente',
  `metodo_pago` enum('efectivo','tarjeta','transferencia') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('confirmada','checked_in','checked_out','cancelada') COLLATE utf8mb4_unicode_ci DEFAULT 'confirmada',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `usuario_registro_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `huesped_id` (`huesped_id`),
  KEY `usuario_registro_id` (`usuario_registro_id`),
  KEY `idx_fechas` (`fecha_entrada`,`fecha_salida`),
  KEY `idx_estado` (`estado`),
  KEY `idx_reservaciones_estado_fechas` (`estado`,`fecha_entrada`,`fecha_salida`),
  KEY `idx_reservaciones_hotel_id` (`hotel_id`),
  KEY `idx_reservaciones_hotel_entrada` (`hotel_id`,`fecha_entrada`),
  KEY `idx_reservaciones_hotel_salida` (`hotel_id`,`fecha_salida`),
  KEY `idx_reservaciones_hotel_estado` (`hotel_id`,`estado`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `roles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `clave` varchar(60) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `es_sistema` tinyint(1) NOT NULL DEFAULT '0',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `permisos_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_hotel_clave` (`hotel_id`,`clave`),
  KEY `idx_roles_hotel_activo` (`hotel_id`,`activo`),
  CONSTRAINT `fk_roles_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saas_admins`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_admins` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `rol` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'admin',
  `activo` tinyint(1) NOT NULL DEFAULT '1',
  `permisos_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_saas_admins_usuario` (`usuario_id`),
  KEY `idx_saas_admins_activo` (`activo`),
  KEY `idx_saas_admins_rol` (`rol`),
  CONSTRAINT `fk_saas_admins_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `saas_cobros`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saas_cobros` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `periodo` char(7) COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `desglose_json` json DEFAULT NULL,
  `estado` enum('pendiente','vencido','pagado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `metodo` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `proveedor_pago_id` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `checkout_url` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pagado_at` datetime DEFAULT NULL,
  `vence_at` date DEFAULT NULL,
  `correo_enviado_at` datetime DEFAULT NULL,
  `recordatorio_enviado_at` datetime DEFAULT NULL,
  `notas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_saas_cobro_periodo` (`hotel_id`,`periodo`),
  KEY `idx_saas_cobros_estado` (`estado`,`periodo`),
  KEY `idx_saas_cobros_vence` (`estado`,`vence_at`),
  CONSTRAINT `fk_saas_cobros_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `solicitudes_factura`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `solicitudes_factura` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `reservacion_id` int NOT NULL,
  `requiere_factura` enum('si','no') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'no',
  `tipo` enum('cliente','uso_interno') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cliente',
  `estatus` enum('pendiente','en_proceso','completada','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `rfc` varchar(13) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `razon_social` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `regimen_fiscal` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `uso_cfdi` varchar(10) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `codigo_postal_fiscal` varchar(5) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email_factura` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `metodo_pago_principal` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `monto_total` decimal(10,2) NOT NULL DEFAULT '0.00',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `usuario_registro_id` int DEFAULT NULL,
  `fecha_facturada` datetime DEFAULT NULL,
  `numero_factura` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reservacion` (`reservacion_id`),
  KEY `idx_estatus` (`estatus`),
  KEY `idx_tipo` (`tipo`),
  KEY `idx_requiere_factura` (`requiere_factura`),
  KEY `idx_created` (`created_at`),
  KEY `fk_solicitud_usuario` (`usuario_registro_id`),
  KEY `idx_estatus_tipo` (`estatus`,`tipo`),
  KEY `idx_solicitudes_factura_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_solicitud_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_solicitud_usuario` FOREIGN KEY (`usuario_registro_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sync_queue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `sync_queue` (
  `id` int NOT NULL AUTO_INCREMENT,
  `user_id` int NOT NULL,
  `action` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `entity_id` int DEFAULT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin,
  `status` enum('pending','syncing','completed','failed') COLLATE utf8mb4_unicode_ci DEFAULT 'pending',
  `attempts` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `synced_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_sync_status` (`status`),
  KEY `idx_sync_user` (`user_id`,`status`),
  CONSTRAINT `sync_queue_chk_1` CHECK (json_valid(`data`))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarea_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarea_eventos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `tarea_id` int NOT NULL,
  `tipo_evento` enum('creada','actualizada','asignada','iniciada','completada','cancelada','comentario','sistema') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_anterior` enum('pendiente','asignada','en_proceso','completada','cancelada') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado_nuevo` enum('pendiente','asignada','en_proceso','completada','cancelada') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `comentario` text COLLATE utf8mb4_unicode_ci,
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tarea_eventos_hotel_tarea` (`hotel_id`,`tarea_id`),
  KEY `idx_tarea_eventos_tipo` (`hotel_id`,`tipo_evento`),
  KEY `idx_tarea_eventos_usuario` (`usuario_id`),
  KEY `fk_tarea_eventos_tarea` (`tarea_id`),
  CONSTRAINT `fk_tarea_eventos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tarea_eventos_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas_operativas` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tarea_eventos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarea_trabajadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarea_trabajadores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `tarea_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `asignado_por_usuario_id` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tarea_trabajador` (`tarea_id`,`trabajador_id`),
  KEY `idx_tt_hotel_tarea` (`hotel_id`,`tarea_id`),
  KEY `idx_tt_hotel_trabajador` (`hotel_id`,`trabajador_id`),
  KEY `idx_tt_asignado_por` (`asignado_por_usuario_id`),
  KEY `fk_tt_trabajador` (`trabajador_id`),
  CONSTRAINT `fk_tt_asignado_por` FOREIGN KEY (`asignado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_tarea` FOREIGN KEY (`tarea_id`) REFERENCES `tareas_operativas` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_tt_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tareas_operativas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tareas_operativas` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `categoria` enum('limpieza','mantenimiento','general') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'general',
  `titulo` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `prioridad` enum('baja','media','alta','urgente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'media',
  `estado` enum('pendiente','asignada','en_proceso','completada','cancelada') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `habitacion_id` int DEFAULT NULL,
  `reservacion_id` int DEFAULT NULL,
  `huesped_id` int DEFAULT NULL,
  `trabajador_id` int DEFAULT NULL,
  `mantenimiento_id` int DEFAULT NULL,
  `fecha_programada` datetime DEFAULT NULL,
  `fecha_limite` datetime DEFAULT NULL,
  `fecha_inicio` datetime DEFAULT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `creada_por_usuario_id` int DEFAULT NULL,
  `asignada_por_usuario_id` int DEFAULT NULL,
  `cerrada_por_usuario_id` int DEFAULT NULL,
  `cancelada_por_usuario_id` int DEFAULT NULL,
  `origen` enum('manual','habitacion','mantenimiento','sistema') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `notas_cierre` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_tareas_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_tareas_hotel_categoria` (`hotel_id`,`categoria`),
  KEY `idx_tareas_hotel_prioridad` (`hotel_id`,`prioridad`),
  KEY `idx_tareas_hotel_fecha_programada` (`hotel_id`,`fecha_programada`),
  KEY `idx_tareas_habitacion` (`habitacion_id`),
  KEY `idx_tareas_reservacion` (`reservacion_id`),
  KEY `idx_tareas_huesped` (`huesped_id`),
  KEY `idx_tareas_trabajador` (`trabajador_id`),
  KEY `idx_tareas_mantenimiento` (`mantenimiento_id`),
  KEY `idx_tareas_creada_por` (`creada_por_usuario_id`),
  KEY `idx_tareas_asignada_por` (`asignada_por_usuario_id`),
  KEY `idx_tareas_cerrada_por` (`cerrada_por_usuario_id`),
  KEY `idx_tareas_cancelada_por` (`cancelada_por_usuario_id`),
  CONSTRAINT `fk_tareas_operativas_asignada_por` FOREIGN KEY (`asignada_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_cancelada_por` FOREIGN KEY (`cancelada_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_cerrada_por` FOREIGN KEY (`cerrada_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_creada_por` FOREIGN KEY (`creada_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_huesped` FOREIGN KEY (`huesped_id`) REFERENCES `huespedes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_mantenimiento` FOREIGN KEY (`mantenimiento_id`) REFERENCES `mantenimientos_habitaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_reservacion` FOREIGN KEY (`reservacion_id`) REFERENCES `reservaciones` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_tareas_operativas_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tarifas_temporada`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tarifas_temporada` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `porcentaje_incremento` decimal(5,2) DEFAULT '0.00',
  `activa` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_fechas` (`fecha_inicio`,`fecha_fin`),
  KEY `idx_activa` (`activa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tipos_habitacion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `tipos_habitacion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int DEFAULT NULL,
  `codigo` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` text COLLATE utf8mb4_unicode_ci,
  `capacidad_default` int DEFAULT '2',
  `precio_base_default` decimal(10,2) DEFAULT '0.00',
  `activo` tinyint(1) DEFAULT '1',
  `orden` int DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tipos_habitacion_hotel_codigo` ((coalesce(`hotel_id`,0)),`codigo`),
  KEY `idx_activo` (`activo`),
  KEY `idx_orden` (`orden`),
  KEY `idx_tipos_habitacion_hotel_id` (`hotel_id`),
  CONSTRAINT `fk_tipos_habitacion_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Catálogo de tipos de habitación del hotel';
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_anticipos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_anticipos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `saldo_pendiente` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `motivo` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('pendiente','descontado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pendiente',
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trabajador_anticipos_hotel_trabajador` (`hotel_id`,`trabajador_id`),
  KEY `idx_trabajador_anticipos_trabajador_estado` (`trabajador_id`,`estado`),
  KEY `idx_trabajador_anticipos_created_by` (`created_by`),
  KEY `idx_trabajador_anticipos_updated_by` (`updated_by`),
  CONSTRAINT `fk_trabajador_anticipos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_anticipos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_anticipos_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_anticipos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajador_anticipos_monto` CHECK ((`monto` >= 0)),
  CONSTRAINT `chk_trabajador_anticipos_saldo` CHECK (((`saldo_pendiente` >= 0) and (`saldo_pendiente` <= `monto`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_asistencias`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_asistencias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `fecha` date NOT NULL,
  `tipo` enum('asistencia','falta','retardo','permiso','incapacidad','descanso','horas_extra') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'asistencia',
  `hora_entrada` time DEFAULT NULL,
  `hora_salida` time DEFAULT NULL,
  `horas` decimal(5,2) DEFAULT NULL,
  `horas_extra` decimal(5,2) DEFAULT NULL,
  `observaciones` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trabajador_asistencias_dia` (`hotel_id`,`trabajador_id`,`fecha`),
  KEY `idx_trabajador_asistencias_hotel_fecha` (`hotel_id`,`fecha`),
  KEY `idx_trabajador_asistencias_trabajador_fecha` (`trabajador_id`,`fecha`),
  KEY `idx_trabajador_asistencias_created_by` (`created_by`),
  KEY `idx_trabajador_asistencias_updated_by` (`updated_by`),
  CONSTRAINT `fk_trabajador_asistencias_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_asistencias_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_asistencias_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_asistencias_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajador_asistencias_horas` CHECK (((`horas` is null) or (`horas` >= 0))),
  CONSTRAINT `chk_trabajador_asistencias_horas_extra` CHECK (((`horas_extra` is null) or (`horas_extra` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_documentos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_documentos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `tipo` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nombre_original` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `ruta_archivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `mime` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tamano` int DEFAULT NULL,
  `notas` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','eliminado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `subido_por` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trabajador_documentos_hotel_trabajador` (`hotel_id`,`trabajador_id`),
  KEY `idx_trabajador_documentos_trabajador_estado` (`trabajador_id`,`estado`),
  KEY `idx_trabajador_documentos_subido_por` (`subido_por`),
  CONSTRAINT `fk_trabajador_documentos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_documentos_subido_por` FOREIGN KEY (`subido_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_documentos_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajador_documentos_tamano` CHECK (((`tamano` is null) or (`tamano` >= 0)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_nomina_periodo_detalles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_nomina_periodo_detalles` (
  `id` int NOT NULL AUTO_INCREMENT,
  `periodo_id` int NOT NULL,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `trabajador_nombre` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `trabajador_identificacion` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trabajador_rol` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `trabajador_estado` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_preview_nomina` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo_bloqueo_nomina` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `conceptos_count` int NOT NULL DEFAULT '0',
  `conceptos_a_favor` decimal(12,2) NOT NULL DEFAULT '0.00',
  `conceptos_en_contra` decimal(12,2) NOT NULL DEFAULT '0.00',
  `bruto_periodo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `anticipos_count` int NOT NULL DEFAULT '0',
  `anticipos_saldo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `prestamos_count` int NOT NULL DEFAULT '0',
  `prestamos_saldo` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deducciones_informativas` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pagos_caja_count` int NOT NULL DEFAULT '0',
  `pagos_caja_pagados` int NOT NULL DEFAULT '0',
  `pagos_caja_revertidos` int NOT NULL DEFAULT '0',
  `pagos_caja_aplicados` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pagos_caja_revertidos_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reversiones_detectadas` decimal(12,2) NOT NULL DEFAULT '0.00',
  `ultimo_pago_caja` datetime DEFAULT NULL,
  `neto_sugerido` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pendiente_pago_sugerido` decimal(12,2) NOT NULL DEFAULT '0.00',
  `snapshot_json` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trabajador_nomina_detalle_periodo_trabajador` (`periodo_id`,`trabajador_id`),
  KEY `idx_trabajador_nomina_detalles_hotel_periodo` (`hotel_id`,`periodo_id`),
  KEY `idx_trabajador_nomina_detalles_trabajador` (`hotel_id`,`trabajador_id`),
  KEY `fk_trabajador_nomina_detalles_trabajador` (`trabajador_id`),
  CONSTRAINT `fk_trabajador_nomina_detalles_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_nomina_detalles_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `trabajador_nomina_periodos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_nomina_detalles_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajador_nomina_detalles_contadores` CHECK (((`conceptos_count` >= 0) and (`anticipos_count` >= 0) and (`prestamos_count` >= 0) and (`pagos_caja_count` >= 0) and (`pagos_caja_pagados` >= 0) and (`pagos_caja_revertidos` >= 0) and (`pendiente_pago_sugerido` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_nomina_periodo_eventos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_nomina_periodo_eventos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `periodo_id` int NOT NULL,
  `hotel_id` int NOT NULL,
  `tipo` enum('cierre','aprobacion','anulacion','reapertura') COLLATE utf8mb4_unicode_ci NOT NULL,
  `estado_resultante` enum('cerrado','aprobado','anulado') COLLATE utf8mb4_unicode_ci NOT NULL,
  `descripcion` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `motivo` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trabajador_nomina_eventos_periodo` (`periodo_id`,`created_at`),
  KEY `idx_trabajador_nomina_eventos_hotel` (`hotel_id`,`created_at`),
  KEY `idx_trabajador_nomina_eventos_created_by` (`created_by`),
  CONSTRAINT `fk_trabajador_nomina_eventos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_nomina_eventos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_nomina_eventos_periodo` FOREIGN KEY (`periodo_id`) REFERENCES `trabajador_nomina_periodos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_nomina_periodos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_nomina_periodos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `tipo_periodo` enum('semanal','quincenal','mensual','manual') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'manual',
  `grupo_nomina_id` int DEFAULT NULL,
  `motor` varchar(10) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'v1',
  `etiqueta` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date NOT NULL,
  `estado` enum('cerrado','aprobado','anulado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'cerrado',
  `filtros_json` json DEFAULT NULL,
  `resumen_json` json DEFAULT NULL,
  `reglas_snapshot_json` json DEFAULT NULL,
  `trabajadores_total` int NOT NULL DEFAULT '0',
  `bruto_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `deducciones_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pagos_caja_aplicados_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `reversiones_detectadas_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `neto_sugerido_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `pendiente_pago_total` decimal(12,2) NOT NULL DEFAULT '0.00',
  `cerrado_por` int DEFAULT NULL,
  `cerrado_at` datetime NOT NULL,
  `aprobado_por` int DEFAULT NULL,
  `aprobado_at` datetime DEFAULT NULL,
  `anulado_por` int DEFAULT NULL,
  `anulado_at` datetime DEFAULT NULL,
  `motivo_anulacion` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `anulacion_uk` int NOT NULL DEFAULT '0',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trabajador_nomina_periodo_grupo_rango_vigente` (`hotel_id`,`grupo_nomina_id`,`fecha_inicio`,`fecha_fin`,`anulacion_uk`),
  KEY `idx_trabajador_nomina_periodos_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_trabajador_nomina_periodos_hotel_fecha` (`hotel_id`,`fecha_inicio`,`fecha_fin`),
  KEY `idx_trabajador_nomina_periodos_cerrado_por` (`cerrado_por`),
  KEY `idx_trabajador_nomina_periodos_aprobado_por` (`aprobado_por`),
  KEY `idx_trabajador_nomina_periodos_anulado_por` (`anulado_por`),
  KEY `fk_trabajador_nomina_periodos_grupo` (`grupo_nomina_id`),
  CONSTRAINT `fk_trabajador_nomina_periodos_anulado_por` FOREIGN KEY (`anulado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_nomina_periodos_aprobado_por` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_nomina_periodos_cerrado_por` FOREIGN KEY (`cerrado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_nomina_periodos_grupo` FOREIGN KEY (`grupo_nomina_id`) REFERENCES `nomina_grupos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trabajador_nomina_periodos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajador_nomina_periodos_rango` CHECK ((`fecha_fin` >= `fecha_inicio`)),
  CONSTRAINT `chk_trabajador_nomina_periodos_totales` CHECK (((`trabajadores_total` >= 0) and (`bruto_total` >= 0) and (`deducciones_total` >= 0) and (`pagos_caja_aplicados_total` >= 0) and (`reversiones_detectadas_total` >= 0) and (`pendiente_pago_total` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_pagos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_pagos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `tipo` enum('pago','comision','bono','descuento','ajuste') COLLATE utf8mb4_unicode_ci NOT NULL,
  `efecto` enum('a_favor','en_contra') COLLATE utf8mb4_unicode_ci NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `concepto` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fin` date DEFAULT NULL,
  `fecha` date NOT NULL,
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `estado` enum('activo','anulado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trabajador_pagos_hotel_trabajador` (`hotel_id`,`trabajador_id`),
  KEY `idx_trabajador_pagos_hotel_fecha` (`hotel_id`,`fecha`),
  KEY `idx_trabajador_pagos_trabajador_estado` (`trabajador_id`,`estado`),
  KEY `idx_trabajador_pagos_created_by` (`created_by`),
  KEY `idx_trabajador_pagos_updated_by` (`updated_by`),
  CONSTRAINT `fk_trabajador_pagos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajador_pagos_monto` CHECK ((`monto` >= 0))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_pagos_caja`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_pagos_caja` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `movimiento_caja_id` int NOT NULL,
  `corte_id` int NOT NULL,
  `nomina_periodo_id` int DEFAULT NULL,
  `nomina_periodo_detalle_id` int DEFAULT NULL,
  `monto` decimal(12,2) NOT NULL,
  `metodo_pago` enum('efectivo','tarjeta','transferencia') COLLATE utf8mb4_unicode_ci NOT NULL,
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci NOT NULL,
  `periodo_inicio` date DEFAULT NULL,
  `periodo_fin` date DEFAULT NULL,
  `concepto` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `fecha_pago` datetime NOT NULL,
  `estado` enum('pagado','revertido') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pagado',
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trabajador_pagos_caja_hotel_referencia` (`hotel_id`,`referencia`),
  UNIQUE KEY `uk_trabajador_pagos_caja_movimiento` (`movimiento_caja_id`),
  KEY `idx_trabajador_pagos_caja_hotel_trabajador` (`hotel_id`,`trabajador_id`),
  KEY `idx_trabajador_pagos_caja_hotel_fecha` (`hotel_id`,`fecha_pago`),
  KEY `idx_trabajador_pagos_caja_hotel_corte` (`hotel_id`,`corte_id`),
  KEY `idx_trabajador_pagos_caja_estado` (`hotel_id`,`estado`),
  KEY `idx_trabajador_pagos_caja_created_by` (`created_by`),
  KEY `idx_trabajador_pagos_caja_updated_by` (`updated_by`),
  KEY `fk_trabajador_pagos_caja_trabajador` (`trabajador_id`),
  KEY `fk_trabajador_pagos_caja_corte` (`corte_id`),
  KEY `idx_trabajador_pagos_caja_nomina_periodo` (`nomina_periodo_id`),
  KEY `idx_trabajador_pagos_caja_nomina_detalle` (`nomina_periodo_detalle_id`),
  CONSTRAINT `fk_trabajador_pagos_caja_corte` FOREIGN KEY (`corte_id`) REFERENCES `cortes_caja` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_caja_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_caja_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_caja_movimiento` FOREIGN KEY (`movimiento_caja_id`) REFERENCES `movimientos_caja` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_caja_nomina_detalle` FOREIGN KEY (`nomina_periodo_detalle_id`) REFERENCES `trabajador_nomina_periodo_detalles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_caja_nomina_periodo` FOREIGN KEY (`nomina_periodo_id`) REFERENCES `trabajador_nomina_periodos` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_caja_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_pagos_caja_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajador_pagos_caja_monto` CHECK ((`monto` > 0)),
  CONSTRAINT `chk_trabajador_pagos_caja_periodo` CHECK (((`periodo_inicio` is null) or (`periodo_fin` is null) or (`periodo_fin` >= `periodo_inicio`)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_prestamos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_prestamos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `saldo_pendiente` decimal(12,2) NOT NULL,
  `fecha` date NOT NULL,
  `plazo_meses` int DEFAULT NULL,
  `abono_periodico` decimal(12,2) DEFAULT NULL,
  `motivo` varchar(160) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('vigente','liquidado','cancelado') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'vigente',
  `referencia` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trabajador_prestamos_hotel_trabajador` (`hotel_id`,`trabajador_id`),
  KEY `idx_trabajador_prestamos_trabajador_estado` (`trabajador_id`,`estado`),
  KEY `idx_trabajador_prestamos_created_by` (`created_by`),
  KEY `idx_trabajador_prestamos_updated_by` (`updated_by`),
  CONSTRAINT `fk_trabajador_prestamos_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_prestamos_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_prestamos_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_prestamos_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajador_prestamos_abono` CHECK (((`abono_periodico` is null) or (`abono_periodico` >= 0))),
  CONSTRAINT `chk_trabajador_prestamos_monto` CHECK ((`monto` >= 0)),
  CONSTRAINT `chk_trabajador_prestamos_plazo` CHECK (((`plazo_meses` is null) or (`plazo_meses` >= 0))),
  CONSTRAINT `chk_trabajador_prestamos_saldo` CHECK (((`saldo_pendiente` >= 0) and (`saldo_pendiente` <= `monto`)))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajador_salarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajador_salarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `trabajador_id` int NOT NULL,
  `salario` decimal(12,2) NOT NULL,
  `esquema` enum('semanal','quincenal','mensual','diario','por_hora','por_evento') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'quincenal',
  `vigente_desde` date NOT NULL,
  `vigente_hasta` date DEFAULT NULL,
  `motivo` varchar(200) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_trabajador_salarios_vigencia` (`hotel_id`,`trabajador_id`,`vigente_desde`),
  KEY `idx_trabajador_salarios_trabajador` (`hotel_id`,`trabajador_id`,`vigente_hasta`),
  KEY `fk_trabajador_salarios_trabajador` (`trabajador_id`),
  KEY `fk_trabajador_salarios_created_by` (`created_by`),
  CONSTRAINT `fk_trabajador_salarios_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trabajador_salarios_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajador_salarios_trabajador` FOREIGN KEY (`trabajador_id`) REFERENCES `trabajadores` (`id`) ON DELETE RESTRICT,
  CONSTRAINT `chk_trabajador_salarios_rango` CHECK (((`vigente_hasta` is null) or (`vigente_hasta` >= `vigente_desde`))),
  CONSTRAINT `chk_trabajador_salarios_salario` CHECK ((`salario` >= 0))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `trabajadores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `trabajadores` (
  `id` int NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `usuario_id` int DEFAULT NULL,
  `nombre_completo` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `identificacion` varchar(60) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol_laboral` varchar(80) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `puesto_id` int DEFAULT NULL,
  `departamento_id` int DEFAULT NULL,
  `tipo_contrato_id` int DEFAULT NULL,
  `grupo_nomina_id` int DEFAULT NULL,
  `telefono` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `email` varchar(120) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `estado` enum('activo','inactivo','baja') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'activo',
  `fecha_alta` date DEFAULT NULL,
  `fecha_baja` date DEFAULT NULL,
  `salario_base` decimal(12,2) DEFAULT NULL,
  `periodicidad_pago` enum('semanal','quincenal','mensual','por_evento') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `notas` text COLLATE utf8mb4_unicode_ci,
  `created_by` int DEFAULT NULL,
  `updated_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_trabajadores_hotel_estado` (`hotel_id`,`estado`),
  KEY `idx_trabajadores_hotel_rol` (`hotel_id`,`rol_laboral`),
  KEY `idx_trabajadores_usuario_id` (`usuario_id`),
  KEY `idx_trabajadores_created_by` (`created_by`),
  KEY `idx_trabajadores_updated_by` (`updated_by`),
  KEY `fk_trabajadores_puesto` (`puesto_id`),
  KEY `fk_trabajadores_departamento` (`departamento_id`),
  KEY `fk_trabajadores_tipo_contrato` (`tipo_contrato_id`),
  KEY `fk_trabajadores_grupo_nomina` (`grupo_nomina_id`),
  KEY `idx_trabajadores_hotel_grupo` (`hotel_id`,`grupo_nomina_id`),
  CONSTRAINT `fk_trabajadores_created_by` FOREIGN KEY (`created_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajadores_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `nomina_departamentos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trabajadores_grupo_nomina` FOREIGN KEY (`grupo_nomina_id`) REFERENCES `nomina_grupos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trabajadores_hotel` FOREIGN KEY (`hotel_id`) REFERENCES `hoteles` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajadores_puesto` FOREIGN KEY (`puesto_id`) REFERENCES `nomina_puestos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trabajadores_tipo_contrato` FOREIGN KEY (`tipo_contrato_id`) REFERENCES `nomina_tipos_contrato` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_trabajadores_updated_by` FOREIGN KEY (`updated_by`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_trabajadores_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `chk_trabajadores_salario_base` CHECK (((`salario_base` is null) or (`salario_base` >= 0)))
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuario_preferencias_nav`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuario_preferencias_nav` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `hotel_id` int NOT NULL,
  `usuario_id` int NOT NULL,
  `tipo` enum('favorito','reciente') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'reciente',
  `ruta` varchar(180) COLLATE utf8mb4_unicode_ci NOT NULL,
  `contador` int unsigned NOT NULL DEFAULT '1',
  `ultima_visita` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_pref_nav` (`hotel_id`,`usuario_id`,`tipo`,`ruta`),
  KEY `idx_pref_nav_lectura` (`hotel_id`,`usuario_id`,`tipo`,`ultima_visita`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre_usuario` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombre_completo` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telefono` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `rol` enum('gerente','administrador','recepcionista') COLLATE utf8mb4_unicode_ci NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  `ultimo_login` datetime DEFAULT NULL,
  `ip_ultimo_login` varchar(45) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `nombre_usuario` (`nombre_usuario`),
  KEY `idx_usuario` (`nombre_usuario`),
  KEY `idx_rol` (`rol`),
  KEY `idx_activo` (`activo`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `vista_caja_actual`;
/*!50001 DROP VIEW IF EXISTS `vista_caja_actual`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `vista_caja_actual` AS SELECT 
 1 AS `caja_id`,
 1 AS `caja_nombre`,
 1 AS `corte_id`,
 1 AS `fecha_apertura`,
 1 AS `monto_inicial`,
 1 AS `usuario_apertura_id`,
 1 AS `usuario_apertura`,
 1 AS `total_ingresos_efectivo`,
 1 AS `total_ingresos_tarjeta`,
 1 AS `total_ingresos_transferencia`,
 1 AS `total_gastos_efectivo`,
 1 AS `total_gastos_tarjeta`,
 1 AS `total_gastos_transferencia`,
 1 AS `efectivo_en_caja`*/;
SET character_set_client = @saved_cs_client;
/*!50001 DROP VIEW IF EXISTS `vista_caja_actual`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = latin1 */;
/*!50001 SET character_set_results     = latin1 */;
/*!50001 SET collation_connection      = latin1_swedish_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */

/*!50001 VIEW `vista_caja_actual` AS select `c`.`id` AS `caja_id`,`c`.`nombre` AS `caja_nombre`,`cc`.`id` AS `corte_id`,`cc`.`fecha_apertura` AS `fecha_apertura`,`cc`.`monto_inicial` AS `monto_inicial`,`cc`.`usuario_apertura_id` AS `usuario_apertura_id`,`u`.`nombre_completo` AS `usuario_apertura`,coalesce(sum((case when ((`mc`.`tipo` = 'ingreso') and (`mc`.`metodo_pago` = 'efectivo')) then `mc`.`monto` else 0 end)),0) AS `total_ingresos_efectivo`,coalesce(sum((case when ((`mc`.`tipo` = 'ingreso') and (`mc`.`metodo_pago` = 'tarjeta')) then `mc`.`monto` else 0 end)),0) AS `total_ingresos_tarjeta`,coalesce(sum((case when ((`mc`.`tipo` = 'ingreso') and (`mc`.`metodo_pago` = 'transferencia')) then `mc`.`monto` else 0 end)),0) AS `total_ingresos_transferencia`,coalesce(sum((case when ((`mc`.`tipo` = 'gasto') and (`mc`.`metodo_pago` = 'efectivo')) then `mc`.`monto` else 0 end)),0) AS `total_gastos_efectivo`,coalesce(sum((case when ((`mc`.`tipo` = 'gasto') and (`mc`.`metodo_pago` = 'tarjeta')) then `mc`.`monto` else 0 end)),0) AS `total_gastos_tarjeta`,coalesce(sum((case when ((`mc`.`tipo` = 'gasto') and (`mc`.`metodo_pago` = 'transferencia')) then `mc`.`monto` else 0 end)),0) AS `total_gastos_transferencia`,((`cc`.`monto_inicial` + coalesce(sum((case when ((`mc`.`tipo` = 'ingreso') and (`mc`.`metodo_pago` = 'efectivo')) then `mc`.`monto` else 0 end)),0)) - coalesce(sum((case when ((`mc`.`tipo` = 'gasto') and (`mc`.`metodo_pago` = 'efectivo')) then `mc`.`monto` else 0 end)),0)) AS `efectivo_en_caja` from (((`cajas` `c` left join `cortes_caja` `cc` on(((`c`.`id` = `cc`.`caja_id`) and (`cc`.`estado` = 'abierto')))) left join `movimientos_caja` `mc` on((`mc`.`corte_id` = `cc`.`id`))) left join `usuarios` `u` on((`cc`.`usuario_apertura_id` = `u`.`id`))) where (`c`.`activa` = 1) group by `c`.`id`,`c`.`nombre`,`cc`.`id`,`cc`.`fecha_apertura`,`cc`.`monto_inicial`,`cc`.`usuario_apertura_id`,`u`.`nombre_completo` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

