# ¿Listo para vender? — checklist honesto (2026-07-13)

Estado del sistema tras Operación 10 + pruebas de carga/concurrencia. Marca qué
falta ANTES de cobrarle al primer cliente que no seas tú. Ordenado por: si te
puede costar el cliente (o una demanda), va arriba.

## 🔴 BLOQUEADORES (no cobrar sin esto)

- [ ] **B1. Estar en producción.** Sin VPS + deploy no hay producto. Todo lo
      demás depende de esto. Checklist técnico: `checklist_deploy_produccion.md`.
      *(En proceso — Vultr CDMX, Paso A.)*
- [ ] **B2. Backup offsite REAL + restore drill en el servidor.** El script ya
      sube a rclone si `BACKUP_RCLONE_REMOTE` está en el `.env` del VPS; falta
      instalar rclone + configurarlo + **probar una restauración cronometrada**.
      Un SaaS que cobra y pierde los datos de un cliente está muerto. Es el #1.
- [ ] **B3. Monitoreo externo con alerta a tu teléfono.** UptimeRobot a
      `/health?token=…` cada 5 min. Si se cae de noche y te enteras por la
      llamada enojada de un hotel, ya perdiste ese cliente.
- [ ] **B4. Aviso de privacidad + Términos y Condiciones (LFPDPPP).** Cobrar y
      tratar datos de terceros SIN esto es riesgo legal real en México. Mínimo:
      aviso de privacidad visible + contrato de servicio con límite de
      responsabilidad, SLA honesto (99.5%) y propiedad/exportación de datos del
      cliente. No es opcional para facturar.
- [ ] **B5. Poder facturar TUS mensualidades (CFDI).** Para cobrarle a un hotel
      necesitas emitirle factura. Stripe MX + un PAC (Facturama) o al menos un
      proceso manual documentado desde el cliente #1.
- [ ] **B6. Secretos de producción NUEVOS (no los de dev).** Generar en el VPS:
      `MOTOR_PASARELA_KEY`, `PWA_VAPID_*`, `HEALTH_TOKEN`, contraseñas de MySQL,
      y una `ANTHROPIC_API_KEY` de producción (la de dev dio 401). Las llaves de
      dev jamás van al servidor.

## 🟡 IMPORTANTES (primeras 2-4 semanas con clientes)

- [ ] **I1. CSP en modo enforce.** Hoy va `Report-Only` en `.htaccess`. Tras
      1-2 semanas sin violaciones en el log, promover a `Content-Security-Policy`.
- [ ] **I2. Bajar la deuda del linter de tenancy (114 queries sin hotel_id).**
      El ratchet impide que crezca, pero el aislamiento es tu promesa de venta:
      revisar las 114 y confirmar que cada una está a salvo (o corregirla).
      Sesiones cortas, van bajando.
- [ ] **I3. NominaCreditoTest.** Es el único invariante de dinero (crédito
      NOMV2) sin test automatizado. Cerrarlo completa la red de seguridad.
- [ ] **I4. Prueba de carga en el VPS real.** Repetir `tools/k6` con
      `-e BASE_URL=` contra staging para tener la cifra de capacidad REAL de la
      máquina (local ya salió sano: ~100 usuarios, p95 162ms).
- [ ] **I5. Onboarding self-service (importador CSV).** Si cada alta de hotel la
      haces a mano, no escala más allá de ~10 clientes. Migrar del Excel del
      hotel es además la mitad de la venta.
- [ ] **I6. Runbook de incidentes.** Una página: "si pasa X, corro Y". Restaurar
      backup, reiniciar contenedor, ver logs. Para no improvisar a las 3 AM.

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
difícil y lo más caro de equivocar. Lo que falta para VENDER no es código de
producto: es **poner en producción (B1) con red de seguridad (B2-B3) y estar en
regla para cobrar (B4-B5-B6)**. Eso es ~1 semana de trabajo, casi todo tuyo y de
infraestructura, no de desarrollo. Los "Importantes" se atienden ya con clientes
reales dando feedback; los "Deseables" esperan a que el MRR los pida.
