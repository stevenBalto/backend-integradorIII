import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  scenarios: {
    api_smoke: {
      executor: 'constant-vus',
      vus: Number(__ENV.VUS || 3),
      duration: __ENV.DURATION || '30s',
      gracefulStop: '5s',
    },
  },
  thresholds: {
    http_req_failed: ['rate<0.01'],
    http_req_duration: ['p(95)<500'],
  },
};

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8000/api';
const ADMIN_EMAIL = __ENV.ADMIN_EMAIL || 'admin@rooster.com';
const ADMIN_PASSWORD = __ENV.ADMIN_PASSWORD || 'admin123456';

function jsonHeaders(token) {
  const headers = {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };

  if (token) {
    headers.Authorization = `Bearer ${token}`;
  }

  return headers;
}

function loginAsAdmin() {
  const response = http.post(
    `${BASE_URL}/login`,
    JSON.stringify({
      email: ADMIN_EMAIL,
      password: ADMIN_PASSWORD,
    }),
    { headers: jsonHeaders() }
  );

  check(response, {
    'login status is 200': (r) => r.status === 200,
    'login returns token': (r) => !!r.json('token'),
  });

  return response.json('token');
}

export function setup() {
  return {
    adminToken: loginAsAdmin(),
  };
}

export default function (data) {
  const publicProducts = http.get(`${BASE_URL}/productos`, {
    headers: { Accept: 'application/json' },
  });

  check(publicProducts, {
    'public products return 200': (r) => r.status === 200,
  });

  const publicCategories = http.get(`${BASE_URL}/categorias`, {
    headers: { Accept: 'application/json' },
  });

  check(publicCategories, {
    'public categories return 200': (r) => r.status === 200,
  });

  const adminDashboard = http.get(`${BASE_URL}/admin/dashboard`, {
    headers: jsonHeaders(data.adminToken),
  });

  check(adminDashboard, {
    'admin dashboard returns 200': (r) => r.status === 200,
  });

  const adminNotifications = http.get(`${BASE_URL}/admin/notificaciones`, {
    headers: jsonHeaders(data.adminToken),
  });

  check(adminNotifications, {
    'admin notifications do not fail': (r) => r.status < 500,
  });

  sleep(1);
}