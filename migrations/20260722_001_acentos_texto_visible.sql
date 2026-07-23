-- Correccion de acentos y enie en TEXTO VISIBLE sembrado por seeds anteriores
-- (la UI mostraba "Dueno", "gestion", "Lavanderia", etc.).
-- Alcance:
-- - roles: nombre/descripcion de los 6 roles base (es_sistema = 1).
-- - modulos: nombre/descripcion del catalogo comercial (global, por clave).
-- - planes: nombre/descripcion.
-- - nomina_conceptos: conceptos base sembrados (es_sistema = 1).
-- - documento_tipos: catalogo global (por clave).
-- - categorias_movimientos: categorias sembradas por Lavanderia.
-- NO toca claves internas (clave, permisos_json, rutas, slugs).
-- Idempotente y conservadora: en tablas editables por hotel solo pisa el texto
-- exacto sembrado; si el hotel lo edito, no matchea y se respeta.
-- (Nota: la colacion utf8mb4_unicode_ci compara ignorando acentos, por lo que
-- re-ejecutar esta migracion deja los datos identicos.)
-- Los seeds fuente ya quedaron corregidos en el codigo: config/permisos.php,
-- NominaCatalogoService::sembrarConceptosBase() y LavanderiaController.

SET NAMES utf8mb4;
SET @migration_name := '20260722_001_acentos_texto_visible.sql';

START TRANSACTION;

-- 1) Roles base por hotel (solo si el hotel conserva el texto sembrado).
UPDATE roles SET descripcion = 'Acceso total. Rol técnico reservado.'
WHERE clave = 'superadmin' AND es_sistema = 1
  AND descripcion = 'Acceso total. Rol tecnico reservado.';

UPDATE roles SET descripcion = 'Dueño del hotel. Acceso total, incluida la gestión de roles.'
WHERE clave = 'propietario' AND es_sistema = 1
  AND descripcion = 'Dueno del hotel. Acceso total, incluida la gestion de roles.';

UPDATE roles SET descripcion = 'Dirección operativa y financiera del hotel.'
WHERE clave = 'gerente' AND es_sistema = 1
  AND descripcion = 'Direccion operativa y financiera del hotel.';

UPDATE roles SET descripcion = 'Administración operativa del hotel.'
WHERE clave = 'administrador' AND es_sistema = 1
  AND descripcion = 'Administracion operativa del hotel.';

UPDATE roles SET descripcion = 'Operación de recepción: check-in/out, huéspedes y cobros.'
WHERE clave = 'recepcionista' AND es_sistema = 1
  AND descripcion = 'Operacion de recepcion: check-in/out, huespedes y cobros.';

UPDATE roles SET nombre = 'Dueño (remoto)'
WHERE clave = 'dueno_remoto' AND es_sistema = 1
  AND nombre = 'Dueno (remoto)';

UPDATE roles SET descripcion = 'Dueño que no opera el hotel: solo lectura del resumen del día (Modo Dueño).'
WHERE clave = 'dueno_remoto' AND es_sistema = 1
  AND descripcion = 'Dueno que no opera el hotel: solo lectura del resumen del dia (Modo Dueno).';

-- 2) Catalogo de modulos (global; la fuente de verdad son las migraciones,
--    por eso se actualiza por clave sin condicion de texto).
UPDATE modulos SET descripcion = 'Gestión de habitaciones, estados, imágenes y mantenimiento operativo.' WHERE clave = 'habitaciones';
UPDATE modulos SET nombre = 'Nómina avanzada', descripcion = 'Motor de nómina configurable por negocio: modo operativo, híbrido o legal por fases, catálogos propios, permisos finos y auditoría. Requiere el bloque Personal para gestionar empleados.' WHERE clave = 'nomina_avanzada';
UPDATE modulos SET descripcion = 'El huésped llena sus datos y sube su identificación antes de llegar; recepción hace check-in en un minuto.' WHERE clave = 'checkin_digital';
UPDATE modulos SET descripcion = 'Sincroniza tu calendario con Airbnb y Booking: exporta tus reservas e importa las de ellos para no vender dos veces la misma habitación.' WHERE clave = 'canales_ical';
UPDATE modulos SET descripcion = 'Tablero móvil para el personal de limpieza: ve las salidas del día y marca cada habitación como limpia con un toque.' WHERE clave = 'camarista';
UPDATE modulos SET nombre = 'Reputación y encuestas', descripcion = 'El huésped califica su estancia al salir; las buenas experiencias van a Google y las malas te llegan a ti antes de hacerse públicas.' WHERE clave = 'reputacion';
UPDATE modulos SET descripcion = 'Entrega y recepción de llaves y controles remotos por reservación.' WHERE clave = 'llaves_remotos';
UPDATE modulos SET nombre = 'Night audit automático', descripcion = 'Cierre nocturno del día: detecta no-shows, checkouts vencidos y cortes de caja abiertos, y manda el resumen al gerente cada madrugada.' WHERE clave = 'night_audit';
UPDATE modulos SET nombre = 'Huéspedes', descripcion = 'Directorio de huéspedes y datos relacionados.' WHERE clave = 'huespedes';
UPDATE modulos SET nombre = 'Vehículos y estacionamiento', descripcion = 'Registro de vehículos y placas por huésped, control de estacionamiento.' WHERE clave = 'vehiculos';
UPDATE modulos SET descripcion = 'Descuentos por huésped frecuente aplicados al crear reservaciones.' WHERE clave = 'descuentos';
UPDATE modulos SET nombre = 'Facturación', descripcion = 'Solicitudes y seguimiento de facturación.' WHERE clave = 'facturacion';
UPDATE modulos SET nombre = 'Crédito a clientes (CxC)', descripcion = 'Cuentas por cobrar de empresas y clientes, cobros parciales vía Caja.' WHERE clave = 'cuentas_cobrar';
UPDATE modulos SET descripcion = 'Compras, recepción, catálogo de proveedores y cuentas por pagar.' WHERE clave = 'compras';
UPDATE modulos SET nombre = 'Tablero de dirección', descripcion = 'Operación diaria, conciliación financiera, tablero ejecutivo y reporte gerencial diario.' WHERE clave = 'tablero_ejecutivo';
UPDATE modulos SET nombre = 'Forecast de ocupación', descripcion = 'Proyección de ocupación a 30/60/90 días, ritmo de reservas semana contra semana y comparativa con el año pasado.' WHERE clave = 'forecast';
UPDATE modulos SET nombre = 'Modo Dueño', descripcion = 'Vista remota para el dueño que no opera el hotel: cómo va el día, cuánto entró, ocupación y qué dicen los huéspedes. Solo lectura, en su teléfono.' WHERE clave = 'modo_dueno';
UPDATE modulos SET nombre = 'Personal y Nómina', descripcion = 'Trabajadores, ledger laboral, nómina por periodos y pagos vía Caja.' WHERE clave = 'personal';
UPDATE modulos SET nombre = 'Configuración', descripcion = 'Configuración general del hotel, backups y preferencias.' WHERE clave = 'configuracion';
UPDATE modulos SET nombre = 'Distribución de reportes', descripcion = 'Links seguros públicos de reportes PDF y envío automático por correo.' WHERE clave = 'reportes_distribucion';
UPDATE modulos SET descripcion = 'Incrementos y reglas de tarifas por temporada, fecha o habitación.' WHERE clave = 'tarifas_dinamicas';
UPDATE modulos SET descripcion = 'Evidencia fotográfica de incidencias, costos reales a gastos, activos con mantenimiento preventivo y recordatorios.' WHERE clave = 'mantenimiento_plus';
UPDATE modulos SET nombre = 'Bitácora de auditoría', descripcion = 'Registro de quién hizo qué y cuándo: cancelaciones, precios, pagos revertidos y cambios de configuración, con filtros por usuario y fecha.' WHERE clave = 'auditoria';
UPDATE modulos SET descripcion = 'Seguimiento de limpieza y tareas por habitación.' WHERE clave = 'limpieza';
UPDATE modulos SET descripcion = 'Alta, asignación y seguimiento de tareas de limpieza y mantenimiento.' WHERE clave = 'tareas';
UPDATE modulos SET nombre = 'Lavandería', descripcion = 'Control de lavandería y blancos.' WHERE clave = 'lavanderia';
UPDATE modulos SET descripcion = 'Centro de avisos, reglas automáticas y push al celular.' WHERE clave = 'notificaciones';
UPDATE modulos SET descripcion = 'Confirmaciones, recordatorios, avisos de anticipo y encuestas listos para mandarse por WhatsApp: el sistema arma cada mensaje con los datos de la reserva y recepción solo toca Enviar. Sin instalar nada.' WHERE clave = 'canal_whatsapp';
UPDATE modulos SET descripcion = 'Página pública de reservas con pago de anticipo online y conciliación a Caja.' WHERE clave = 'motor_reservas';
UPDATE modulos SET descripcion = 'Códigos de descuento para el motor de reservas online: por porcentaje o monto, con vigencia y límite de usos.' WHERE clave = 'promociones';
UPDATE modulos SET descripcion = 'Vende desayuno, late checkout o decoración directo en la reserva online; el extra se suma al total de la estancia.' WHERE clave = 'upsells';
UPDATE modulos SET descripcion = 'Instalación web, caché y experiencia offline.' WHERE clave = 'pwa';
UPDATE modulos SET nombre = 'Huésped frecuente', descripcion = 'Detecta a tus huéspedes que regresan y les genera un cupón personal de agradecimiento para su siguiente reserva en línea.' WHERE clave = 'lealtad';
UPDATE modulos SET nombre = 'Motor en inglés', descripcion = 'Tu página pública de reservas en español e inglés, con selector de idioma para huéspedes extranjeros.' WHERE clave = 'motor_idiomas';
UPDATE modulos SET descripcion = 'Comunicación y automatizaciones vía WhatsApp.' WHERE clave = 'whatsapp';
UPDATE modulos SET descripcion = 'Asistencia e inteligencia operativa para dirección.' WHERE clave = 'ia_ejecutiva';
UPDATE modulos SET descripcion = 'Asistente en todas las pantallas: pregunta por tu ocupación, caja o llegadas y te responde al instante; también te explica cómo hacer las cosas.' WHERE clave = 'copiloto';
UPDATE modulos SET descripcion = 'La IA trabaja con tus datos: borradores listos para responder reseñas, análisis mensual de encuestas con las quejas más repetidas y consejo de tarifa según tu ocupación proyectada.' WHERE clave = 'copiloto_ia';
UPDATE modulos SET descripcion = 'Tu copiloto se adelanta: briefing matutino por notificación push con llegadas, salidas, limpieza pendiente, caja y avisos con criterio (ocupación baja) antes de que preguntes.' WHERE clave = 'copiloto_briefing';

-- 3) Planes (catalogo global).
UPDATE planes SET nombre = 'Básico', descripcion = 'Operación hotelera esencial.' WHERE clave = 'basico';
UPDATE planes SET descripcion = 'Operación avanzada con inventario y facturación.' WHERE clave = 'pro';
UPDATE planes SET descripcion = 'Suite completa con canales, auditoría e inteligencia.' WHERE clave = 'premium';
UPDATE planes SET descripcion = 'Configuración manual de módulos por hotel.' WHERE clave = 'personalizado';

-- 4) Conceptos base de nomina sembrados (por hotel; solo los del sistema).
UPDATE nomina_conceptos SET nombre = 'Comisión'
WHERE es_sistema = 1 AND nombre = 'Comision';

UPDATE nomina_conceptos SET nombre = 'Préstamo (abono)'
WHERE es_sistema = 1 AND nombre = 'Prestamo (abono)';

-- 5) Tipos de documento (catalogo global).
UPDATE documento_tipos SET nombre = 'Identificación'
WHERE clave = 'identificacion' AND nombre = 'Identificacion';

UPDATE documento_tipos SET descripcion = 'Evidencias fotográficas o documentales.'
WHERE clave = 'evidencia' AND descripcion = 'Evidencias fotograficas o documentales.';

-- 6) Categorias de caja sembradas por Lavanderia (editables por hotel:
--    solo se pisa el texto exacto sembrado).
UPDATE categorias_movimientos SET nombre = 'Lavandería'
WHERE nombre = 'Lavanderia';

UPDATE categorias_movimientos SET descripcion = 'Costos de lavandería (servicio externo, insumos del lavado)'
WHERE descripcion = 'Costos de lavanderia (servicio externo, insumos del lavado)';

UPDATE categorias_movimientos SET descripcion = 'Cobros de lavandería de huéspedes'
WHERE descripcion = 'Cobros de lavanderia de huespedes';

-- Registro de la migracion.
INSERT INTO migrations (nombre, batch, checksum, estado)
VALUES (@migration_name, 1, NULL, 'ejecutada')
ON DUPLICATE KEY UPDATE
    estado = VALUES(estado),
    ejecutada_en = CURRENT_TIMESTAMP;

COMMIT;

-- Rollback manual: no aplica restaurar ortografia incorrecta; si hiciera falta,
-- los textos anteriores estan en las migraciones de siembra originales.
-- DELETE FROM migrations WHERE nombre = '20260722_001_acentos_texto_visible.sql';
