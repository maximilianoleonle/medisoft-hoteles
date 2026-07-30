# ¿Listo para vender? — checklist honesto

Creado 2026-07-13 · **Última reconciliación contra la realidad: 2026-07-27.**

Estado del sistema tras Operación 10 + pruebas de carga/concurrencia. Marca qué
falta ANTES de cobrarle al primer cliente que no seas tú. Ordenado por: si te
puede costar el cliente (o una demanda), va arriba.

> ⚠️ **Regla de este documento**: al cerrar una casilla, escribir **con qué se
> verificó**, no solo palomearla. Un checklist sin evidencia envejece mal y
> termina dando una foto falsa — pasó entre el 13 y el 27 de julio, cuando dos
> bloqueadores llevaban días cumplidos sin marcarse y el documento aparentaba
> el doble de trabajo pendiente del real.

## 🔴 BLOQUEADORES (no cobrar sin esto)

- [x] **B1. Estar en producción.** ✅ **2026-07-20.** VPS Vultr CDMX vivo con
      dominio propio y HTTPS automático (Caddy). Verificado 27-jul: hotel real
      operando con ~1,370 peticiones/hora, 7 días de uptime continuo.
      Runbook: `RUNBOOK-PRODUCCION.md`.
- [ ] **B2. Backup offsite REAL + restore drill en el servidor.** 🟡 **A medias
      (27-jul).** HECHO: rclone instalado; `backup_db.sh` §4 sube BD + uploads +
      `.env` cifrado a Backblaze B2, **re-lee el destino para verificar que el
      archivo llegó** y sale con error si falla; probado de punta a punta con un
      remoto local, incluido el camino de fallo. FALTA: (1) que el owner cree el
      bucket y pegue `BACKUP_B2_*` en el `.env` de prod — hasta entonces el log
      avisa a diario `NO hay copia fuera del VPS`; (2) **la restauración
      cronometrada en máquina limpia**, que es lo que convierte "tengo
      respaldos" en un número que puedes decirle a un cliente. Receta lista en
      `RUNBOOK-PRODUCCION.md` §Copia offsite; el entorno para ensayarla es el
      staging (ver I4).
- [ ] **B3. Monitoreo externo con alerta a tu teléfono.** UptimeRobot a
      `/health?token=…` cada 5 min. **Tiene que ser EXTERNO**: un vigilante que
      corre en el mismo VPS no puede avisar que el VPS se cayó. Complemento ya
      hecho (27-jul): `tools/watchdog_infra.sh` vigila lo que un monitor HTTP no
      ve —disco, frescura del backup, caducidad del certificado, salud de los
      contenedores— y corre por cron. Ojo: **el canal WhatsApp NO sirve hoy**
      como vía de alerta (`WHATSAPP_SEND_ENABLED=false` y su token sigue
      pendiente de rotar), por eso la alerta al teléfono depende del servicio
      externo.
- [ ] **B4. Aviso de privacidad + Términos y Condiciones (LFPDPPP).** Cobrar y
      tratar datos de terceros SIN esto es riesgo legal real en México. Mínimo:
      aviso de privacidad visible + contrato de servicio con límite de
      responsabilidad, SLA honesto (99.5%) y propiedad/exportación de datos del
      cliente. No es opcional para facturar. **Es el bloqueador más lento porque
      depende de terceros: arráncalo en paralelo, no al final.**
- [ ] **B5. Poder facturar TUS mensualidades (CFDI).** Para cobrarle a un hotel
      necesitas emitirle factura. Stripe MX + un PAC (Facturama) o al menos un
      proceso manual documentado desde el cliente #1.
- [x] **B6. Secretos de producción NUEVOS (no los de dev).** ✅ **Verificado
      2026-07-27** comparando por hash el `.env` de dev contra el de prod, sin
      exponer valores: `DB_PASS`, `DB_ROOT_PASS`, `HEALTH_TOKEN`,
      `MOTOR_PASARELA_KEY`, `PWA_VAPID_PRIVATE_KEY` y `ANTHROPIC_API_KEY` son
      todos distintos en prod. Pendiente aparte (no bloquea vender, sí es
      higiene): rotar el token de WhatsApp que quedó en el historial de git.

## 🟡 IMPORTANTES (primeras 2-4 semanas con clientes)

- [ ] **I1. CSP en modo enforce.** Hoy va `Report-Only` en `.htaccess`. Tras
      1-2 semanas sin violaciones en el log, promover a `Content-Security-Policy`.
- [ ] **I2. Bajar la deuda del linter de tenancy.** 🟡 En progreso: de 114 a
      **97** referencias (jul-26). El ratchet impide que crezca, pero el
      aislamiento es tu promesa de venta: revisar las restantes y confirmar que
      cada una está a salvo (o corregirla). Sesiones cortas, van bajando.
- [ ] **I3. NominaCreditoTest.** Es el único invariante de dinero (crédito
      NOMV2) sin test automatizado. Cerrarlo completa la red de seguridad.
- [ ] **I4. Staging + prueba de carga en máquina real.** 🟡 Staging creado
      27-jul (`docker-compose.staging.yml`, se levanta BAJO DEMANDA en el VPS,
      escucha solo en loopback, se accede por túnel SSH). Sirve ya para el drill
      de restauración (B2) y para QA de deploys. **Pero la prueba de carga NO se
      puede correr ahí y dar por buena**: comparte los 2 vCPU con producción, así
      que el número saldría contaminado y de paso le pegaría al hotel. Para la
      cifra de capacidad real hace falta una máquina aparte, temporal.
      Y jamás correr `tools/k6/carga.js` contra prod: sus ~979 logins
      **bloquearían la cuenta real** (rate-limit de 15/15 min).
- [ ] **I5. Onboarding self-service (importador CSV).** Si cada alta de hotel la
      haces a mano, no escala más allá de ~10 clientes. Migrar del Excel del
      hotel es además la mitad de la venta.
- [x] **I6. Runbook de incidentes.** ✅ `RUNBOOK-PRODUCCION.md` cubre salud,
      logs, backup, restauración desde el bucket, deploy y edición del `.env`.
      Ampliarlo cuando aparezca un incidente que no esté contemplado.
- [ ] **I7. Cuota de almacenamiento por hotel (2 GB) — NUEVO 27-jul.** Hoy el
      límite, el aviso al 80% y el bloqueo de cargas **no existen en el código**
      (lo único real es el tope de 10 MB por archivo). Es tu **techo real de
      capacidad**: con ~50 GB utilizables son ~25 hoteles a cuota llena, y sin
      bloqueo un solo hotel puede llenarte el disco y tumbar a todos los demás.
      Medición que lo sustenta: un hotel real con 49 cuartos y 2 años de
      historial ocupa **&lt;10 MB de BD**; lo que pesa son los documentos.

## 🟢 DESEABLES (cuando el MRR lo justifique, no antes)

- [ ] D1. Redis para sesiones y cache (hoy archivos locales) — quita el techo de
      "todo en un servidor". Ya está en el plan del giro nuevo; en hoteles es
      migración, no urgencia.
- [ ] D2. Cola de trabajos para WhatsApp/push/emails/PDFs (hoy síncronos). Un
      canal externo lento hoy le pega al usuario en caja.
- [ ] D3. Partir las 3 vistas monolito (habitaciones 16,892 líneas es la que la
      prueba de carga marcó como primera en degradar).
- [ ] D4. Sentry o APM para errores en producción con alerta.
- [ ] D5. 2FA para roles admin/superadmin.

## Lo que YA está listo (no re-hacer)

- ✅ Seguridad de app: PDO en todo, CSRF global + escudo JS, headers, rate limit
  persistente, cookies endurecidas, secretos fuera de git.
- ✅ Dinero probado: 38 tests de invariantes + 3 de concurrencia bajo carrera
  real (candados FOR UPDATE resisten). En CI.
- ✅ Aislamiento multi-tenant con test A/B + linter ratchet.
- ✅ Rendimiento: aguanta ~100 usuarios concurrentes con p95 162ms, 0% error.
- ✅ Ops: runner de migraciones, backup con verificación de integridad, health
  endpoint, Docker prod con Caddy/TLS y sin phpMyAdmin ni puertos de BD
  expuestos, `display_errors=Off` en prod.

## Veredicto

**Técnicamente el producto está listo para los primeros clientes** — el núcleo
que maneja dinero es sólido y está probado bajo concurrencia, que es lo más
difícil y lo más caro de equivocar.

**Al 27 de julio, de 6 bloqueadores: 2 cerrados (B1, B6), 1 a medias (B2) y 3
abiertos (B3, B4, B5).** Y lo que de verdad importa de esa cuenta: **de lo que
sigue abierto, casi nada es programar**. B2 y B3 se cierran en ~1 hora entre
pegar credenciales, dar de alta un monitor externo y ensayar una restauración.
B4 y B5 son trámite legal y fiscal, no desarrollo — y son los más lentos porque
dependen de terceros, así que van en paralelo desde ya.

El camino corto, en orden:
1. Credenciales de B2 en el `.env` de prod (5 min) → cierra la mitad de B2.
2. Monitor externo dado de alta (5 min) → cierra B3.
3. Drill de restauración cronometrado en staging (~1 h) → cierra B2 y te da el
   número que responder cuando un cliente pregunte "¿y si se cae?".
4. B4 y B5 en paralelo, con terceros.
5. Ya vendiendo: I7 (cuota) antes de pasar de ~10 hoteles, I5 (importador) antes
   de pasar de ~10 clientes.

Los "Importantes" se atienden ya con clientes reales dando feedback; los
"Deseables" esperan a que el MRR los pida.
