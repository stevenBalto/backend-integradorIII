import http from 'k6/http';
import { check } from 'k6';

// Base del API (backend Laravel en php artisan serve).
const BASE = __ENV.BASE || 'http://127.0.0.1:8000/api';
const ITERS = Number(__ENV.ITERS || 30);

// 3 escenarios = 3 perspectivas. 1 VU secuencial por rol para medir la latencia
// limpia por endpoint (media/p50/p95 reales, sin cola artificial). Corren a la vez
// (carga suave y realista: cliente + admin + superadmin simultáneos).
const ALL = {
  cliente:    { executor: 'per-vu-iterations', vus: 1, iterations: ITERS, exec: 'clienteFlow' },
  admin:      { executor: 'per-vu-iterations', vus: 1, iterations: ITERS, exec: 'adminFlow' },
  superadmin: { executor: 'per-vu-iterations', vus: 1, iterations: ITERS, exec: 'superadminFlow' },
};
// ONLY=<rol> corre un solo escenario (medición secuencial limpia, sin cola del dev-server).
const ONLY = __ENV.ONLY;
export const options = { scenarios: ONLY ? { [ONLY]: ALL[ONLY] } : ALL };

const JSONH = { headers: { 'Content-Type': 'application/json', Accept: 'application/json' } };

export function setup() {
  const cliente = http.post(`${BASE}/login`,
    JSON.stringify({ email: 'cliente@rooster.com', password: 'Rooster2026' }), JSONH).json('token');
  const admin = http.post(`${BASE}/login`,
    JSON.stringify({ email: 'admin@rooster.com', password: 'admin123456' }), JSONH).json('token');
  const superadmin = http.post(`${BASE}/superadmin/login`,
    JSON.stringify({ login: 'super@rooster.com', password: 'Super#Rooster2026' }), JSONH).json('token');
  return { cliente, admin, superadmin };
}

function hit(method, path, name, token) {
  const params = { headers: { Accept: 'application/json' }, tags: { name } };
  if (token) params.headers.Authorization = `Bearer ${token}`;
  const res = http.request(method, `${BASE}${path}`, null, params);
  check(res, { [`${name} 2xx`]: (r) => r.status >= 200 && r.status < 300 });
  return res;
}

// ── CLIENTE / público (lo que espera el usuario final) ──
export function clienteFlow(data) {
  hit('GET', '/productos', 'cli: catalogo productos', null);
  hit('GET', '/categorias', 'cli: categorias', null);
  hit('GET', '/ofertas', 'cli: ofertas', null);
  hit('GET', '/cupones', 'cli: cupones', null);
  hit('GET', '/home-config', 'cli: home-config', null);
  hit('GET', '/sucursales', 'cli: sucursales', null);
  hit('GET', '/restaurantes', 'cli: restaurantes', null);
  hit('GET', '/me', 'cli: perfil (me)', data.cliente);
  hit('GET', '/puntos/mios', 'cli: roosters', data.cliente);
  hit('GET', '/pedidos/mios', 'cli: mis pedidos', data.cliente);
  hit('GET', '/resenas/pendientes', 'cli: resenas pendientes', data.cliente);
}

// ── ADMIN (panel de administración) ──
export function adminFlow(data) {
  const t = data.admin;
  hit('GET', '/admin/dashboard', 'adm: dashboard', t);
  hit('GET', '/admin/productos', 'adm: menu productos', t);
  hit('GET', '/admin/ofertas', 'adm: ofertas', t);
  hit('GET', '/admin/cupones', 'adm: cupones', t);
  hit('GET', '/admin/insumos', 'adm: inventario', t);
  hit('GET', '/admin/usuarios', 'adm: usuarios', t);
  hit('GET', '/admin/clientes', 'adm: clientes', t);
  hit('GET', '/admin/pedidos', 'adm: pedidos', t);
  hit('GET', '/admin/resenas', 'adm: resenas', t);
  hit('GET', '/admin/resenas/stats', 'adm: resenas stats', t);
  hit('GET', '/admin/analiticas', 'adm: analiticas', t);
  hit('GET', '/admin/notificaciones', 'adm: notificaciones', t);
  hit('GET', '/admin/configuracion', 'adm: configuracion', t);
  hit('GET', '/admin/categorias', 'adm: categorias', t);
  hit('GET', '/admin/extras', 'adm: extras', t);
  hit('GET', '/admin/modulos', 'adm: modulos', t);
  hit('GET', '/admin/sucursales', 'adm: sucursales', t);
}

// ── SUPERADMIN (panel aislado) ──
export function superadminFlow(data) {
  const t = data.superadmin;
  hit('GET', '/superadmin/me', 'sup: perfil (me)', t);
  hit('GET', '/superadmin/superadmins', 'sup: superadmins', t);
  hit('GET', '/superadmin/instancias', 'sup: instancias', t);
}
