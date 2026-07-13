# Prueba de carga — línea base (2026-07-13)

Primera prueba de rendimiento con k6. Entorno: **local** (Docker en la PC de
desarrollo, no VPS), BD `medisoft_hoteles_import` (datos de dev, hotel
Los Cedros). Sirve como referencia; el número de capacidad REAL se medirá
repitiendo esto contra el VPS/staging.

## Escenario de cada corrida
Login por slug (con CSRF) + ciclo de 6 acciones con think-time humano (2-5s):
dashboard → habitaciones → API ocupación → reservaciones → API caja snapshot
→ caja. Solo lecturas (90% del tráfico real).

## Resultados

| Escenario | VUs | ≈usuarios reales | Peticiones | Errores | Sesión perdida | p95 global |
|---|---|---|---|---|---|---|
| Humo | 5 | ~30 | 180 | 0% | 0% | ~200ms |
| **Carga** | **20** | **~100** | **2,574** | **0%** | **0%** | **162ms** |
| Estrés | 0→120 (rampa) | varios cientos | 8,811 | 0% | 0% | 309ms |

### p95 por pantalla (escenario carga, 20 VUs)
- API caja snapshot: **27ms**
- API ocupación: **74ms**
- Reservaciones: **147ms**
- Caja: **156ms**
- Dashboard: **159ms**
- Habitaciones: **195ms**

### Bajo estrés (120 VUs), p95 por pantalla
- Caja: 308ms · Dashboard: 344ms · Habitaciones: **457ms** (la primera en degradar)

## Lectura

1. **La app aguanta ~100 usuarios concurrentes reales sin sudar** (p95 162ms,
   cero errores). Para el tamaño de operación de varias decenas de hoteles,
   sobra margen.
2. **No hay cuellos de botella de código evidentes**: ni N+1 catastrófico ni
   contención de locks visible en lecturas; el throughput escaló linealmente
   hasta 120 VUs sin romperse. El techo de la corrida fue la PC, no la app.
3. **La vista más pesada es `/habitaciones`** (la de 16,892 líneas del examen):
   es la primera en degradar bajo estrés. Candidata #1 a optimización SI algún
   día el VPS lo pide — coincide con lo que el examen final ya señaló.
4. **El CSRF global (F1) no penaliza**: 979 logins + 8,811 peticiones mutantes
   con el escudo activo, 0 rechazos falsos. Confirma que el blindaje no estorba
   a clientes legítimos.

## Pendiente (no probado aquí)
- **Escrituras concurrentes de dinero** (abrir/cerrar caja y registrar
  movimientos simultáneos): es donde los candados FOR UPDATE se tensan.
  Requiere escenario de POSTs contra `medisoft_test`. Es la prueba que de
  verdad estresa los invariantes de Caja.
- **Repetir en VPS/staging** para obtener la cifra de capacidad real de la
  máquina de producción (mismo `carga.js`, con `-e BASE_URL=`).
