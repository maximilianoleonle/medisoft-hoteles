-- Textos comerciales del catalogo: quitar promesas que el producto no cumple
-- o que exponen al hotel a sancion (2026-07-26).
--
-- Sale de los pendientes 2, 4 y 7 de docs/auditoria_comercial_modulos_20260725.md
-- (seccion 9, "Pendientes antes de convertir la auditoria en oferta"). Los tres
-- textos afectados se leen tal cual en /admin/saas/modulos y en el detalle de
-- cada hotel, o sea que hoy son la oferta escrita.
--
-- 1) reputacion — descripcion vendia REVIEW GATING
--    Antes: "El huesped califica su estancia al salir; las buenas experiencias
--            van a Google y las malas te llegan a ti antes de hacerse publicas."
--    Esa frase describe exactamente lo que la politica de contenido de Google
--    Maps prohibe: solicitar resenas de forma selectiva / desalentar las
--    negativas. El codigo ya se corrigio en la misma sesion (ReputacionService
--    ::puedeInvitarResena + views/encuesta/formulario.php ya no filtran por
--    calificacion); esta linea alinea el texto con el comportamiento nuevo.
--    Lo que SI se conserva y se puede seguir vendiendo: la encuesta privada y
--    la alerta interna cuando alguien califica bajo.
--
-- 2) facturacion — nombre inducia a pensar en timbrado CFDI
--    Antes: nombre "Facturacion" / "Solicitudes y seguimiento de facturacion."
--    El modulo NO timbra, NO se conecta al SAT y NO sustituye al PAC: solo
--    administra las solicitudes y deja registrar el folio de la factura que se
--    emitio por fuera. Nombre nuevo elegido por el owner: "Control de
--    facturacion", con descripcion que dice explicitamente que no timbra.
--
-- 3) pwa — descripcion prometia "experiencia offline"
--    Antes: "Instalacion web, cache y experiencia offline."
--    /api/sync responde HTTP 423 (sync_temporarily_disabled) y hay un test que
--    lo fija (ClasificacionComercialModulosTest). Sin ese endpoint no existe
--    sincronizacion: lo que si funciona es instalar la app y consultar en cache.
--    La captura de escrituras sin conexion se apago en esta misma sesion.
--
-- Idempotente: UPDATE declarativo re-ejecutable, sin DDL. No toca hotel_modulos,
-- planes, plan_modulos, precios ni activo_global. Solo cambia texto visible.
--
-- REVERSION (manual; respaldo previo en
-- backups/respaldo_catalogo_modulos_20260726.sql):
--   UPDATE modulos SET descripcion = 'El huesped califica su estancia al salir; las buenas experiencias van a Google y las malas te llegan a ti antes de hacerse publicas.' WHERE clave = 'reputacion';
--   UPDATE modulos SET nombre = 'Facturacion', descripcion = 'Solicitudes y seguimiento de facturacion.' WHERE clave = 'facturacion';
--   UPDATE modulos SET descripcion = 'Instalacion web, cache y experiencia offline.' WHERE clave = 'pwa';
-- (revertir el texto NO revierte el codigo: eso se hace por git)

UPDATE modulos
SET descripcion = 'Encuesta al huesped despues del checkout: concentra sus opiniones, te avisa cuando alguien queda insatisfecho e invita a dejar resena en Google.',
    updated_at = NOW()
WHERE clave = 'reputacion';

UPDATE modulos
SET nombre = 'Control de facturacion',
    descripcion = 'Solicitudes de factura y su seguimiento hasta el folio. No timbra CFDI ni se conecta al SAT: la factura se emite por fuera y aqui se registra.',
    updated_at = NOW()
WHERE clave = 'facturacion';

UPDATE modulos
SET descripcion = 'Se instala como app en el celular y consulta en cache lo ya cargado. Sin sincronizacion de capturas sin conexion.',
    updated_at = NOW()
WHERE clave = 'pwa';
