/**
 * Prueba de carga k6 — Medisoft Hoteles
 *
 * Simula recepcionistas reales: login por slug (con CSRF), luego un ciclo de
 * las pantallas/APIs más usadas con think-time humano.
 *
 * Escenarios (elegir con -e ESCENARIO=...):
 *   humo    →   5 VUs, 1 min   (validar script y login)
 *   carga   →  20 VUs, 4 min   (≈100 usuarios reales con think-time)
 *   estres  →  rampa 0→120 VUs (buscar el punto de quiebre)
 *
 * Correr (desde la raíz del repo, en la red de compose):
 *   docker run --rm -i --network=medisoft-hoteles_default \
 *     -e ESCENARIO=humo grafana/k6 run - < tools/k6/carga.js
 *
 * Usuarios: qa_k6_001..qa_k6_100 (password compartido) — cada VU usa el suyo
 * para no compartir sesión PHP (el lock del archivo de sesión serializaría
 * todo y arruinaría la medición).
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const BASE = __ENV.BASE_URL || 'http://medisoft_hoteles_app';
const SLUG = 'los-cedros';
const PASSWORD = 'K6carga!2026';

const loginFallido = new Rate('login_fallido');
const sesionPerdida = new Rate('sesion_perdida');
const tDashboard = new Trend('vista_dashboard', true);
const tHabitaciones = new Trend('vista_habitaciones', true);
const tReservaciones = new Trend('vista_reservaciones', true);
const tCaja = new Trend('vista_caja', true);
const tApiOcupacion = new Trend('api_ocupacion', true);
const tApiSnapshot = new Trend('api_caja_snapshot', true);

const ESCENARIOS = {
  humo: {
    escenario: { executor: 'constant-vus', vus: 5, duration: '1m' },
    umbrales: { http_req_failed: ['rate<0.05'] },
  },
  carga: {
    escenario: { executor: 'constant-vus', vus: 20, duration: '4m' },
    umbrales: {
      http_req_failed: ['rate<0.01'],
      http_req_duration: ['p(95)<1500'],
      sesion_perdida: ['rate<0.01'],
    },
  },
  estres: {
    escenario: {
      executor: 'ramping-vus',
      startVUs: 0,
      stages: [
        { duration: '1m', target: 30 },
        { duration: '1m', target: 60 },
        { duration: '1m', target: 90 },
        { duration: '1m', target: 120 },
        { duration: '30s', target: 0 },
      ],
    },
    umbrales: {}, // el estrés no aprueba/reprueba: mide dónde truena
  },
};

const cfg = ESCENARIOS[__ENV.ESCENARIO || 'humo'];

export const options = {
  scenarios: { principal: cfg.escenario },
  thresholds: cfg.umbrales,
  // Recursos estáticos no: medimos la app, no a Apache sirviendo CSS.
};

function usuarioDelVU() {
  const n = ((__VU - 1) % 100) + 1;
  return 'qa_k6_' + String(n).padStart(3, '0');
}

function login() {
  const pagina = http.get(`${BASE}/h/${SLUG}/login`);
  const m = pagina.body && pagina.body.match(/name="csrf_token"\s+value="([^"]+)"/);
  const csrf = m ? m[1] : '';

  const res = http.post(`${BASE}/h/${SLUG}/login/authenticate`, {
    nombre_usuario: usuarioDelVU(),
    password: PASSWORD,
    csrf_token: csrf,
  });

  // Login bueno = redirect que NO regresa al login.
  const ok = res.status === 200 && !(res.url || '').includes('/login');
  loginFallido.add(!ok);
  return ok;
}

function visita(url, trend, extraHeaders) {
  const res = http.get(BASE + url, { headers: extraHeaders || {} });
  trend.add(res.timings.duration);
  const perdida = (res.url || '').includes('/login');
  sesionPerdida.add(perdida);
  check(res, { [`${url} -> 2xx`]: (r) => r.status === 200 });
  return res;
}

export default function () {
  // Cada VU se loguea una vez y conserva cookies el resto de la prueba.
  if (__ITER === 0) {
    if (!login()) {
      sleep(5);
      return;
    }
  }

  const ajax = { 'X-Requested-With': 'XMLHttpRequest' };

  visita('/dashboard', tDashboard);
  sleep(Math.random() * 3 + 2); // think-time humano 2-5s

  visita('/habitaciones', tHabitaciones);
  sleep(Math.random() * 3 + 2);

  visita(
    '/api/habitaciones/todas-con-ocupacion?fecha_entrada=2026-07-12&fecha_salida=2026-07-13',
    tApiOcupacion,
    ajax
  );
  sleep(1);

  visita('/reservaciones', tReservaciones);
  sleep(Math.random() * 3 + 2);

  visita('/api/caja/snapshot', tApiSnapshot, ajax);
  sleep(1);

  visita('/caja', tCaja);
  sleep(Math.random() * 4 + 2);
}
