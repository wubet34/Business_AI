// ── API base URL ───────────────────────────────────────────
const API_BASE = window.location.hostname === 'localhost'
  ? '/Business-Ai/api'
  : '/api';

// Core fetch wrapper
async function apiFetch(endpoint, options = {}) {
  const defaults = {
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
  };
  const config = { ...defaults, ...options };
  if (config.body && typeof config.body === 'object') {
    config.body = JSON.stringify(config.body);
  }

  const res = await fetch(API_BASE + endpoint, config);

  // CSV export — return raw response
  if (res.headers.get('Content-Type')?.includes('text/csv')) return res;

  // Safely parse JSON — if server returns HTML (PHP error), show a clean message
  let data;
  try {
    data = await res.json();
  } catch {
    throw { status: res.status, message: `Server error (${res.status}). Check PHP logs.` };
  }

  if (!res.ok) throw { status: res.status, message: data.error || 'Request failed' };
  return data;
}

// ── Auth ───────────────────────────────────────────────────
const Auth = {
  register: (body) => apiFetch('/register', { method: 'POST', body }),
  login:    (body) => apiFetch('/login',    { method: 'POST', body }),
  logout:   ()     => apiFetch('/logout',   { method: 'POST' }),
};

// ── Customers ──────────────────────────────────────────────
const Customers = {
  list:    (params = {}) => apiFetch('/customers?' + new URLSearchParams(params)),
  create:  (body)        => apiFetch('/customers',    { method: 'POST', body }),
  update:  (id, body)    => apiFetch(`/customers/${id}`, { method: 'PUT',  body }),
  remove:  (id)          => apiFetch(`/customers/${id}`, { method: 'DELETE' }),
};

// ── Products ───────────────────────────────────────────────
const Products = {
  list:   (params = {}) => apiFetch('/products?' + new URLSearchParams(params)),
  create: (body)        => apiFetch('/products',    { method: 'POST', body }),
  update: (id, body)    => apiFetch(`/products/${id}`, { method: 'PUT',  body }),
  remove: (id)          => apiFetch(`/products/${id}`, { method: 'DELETE' }),
};

// ── Sales ──────────────────────────────────────────────────
const Sales = {
  list:   (params = {}) => apiFetch('/sales?' + new URLSearchParams(params)),
  get:    (id)          => apiFetch(`/sales/${id}`),
  create: (body)        => apiFetch('/sales', { method: 'POST', body }),
  export: (params = {}) => apiFetch('/sales/export?' + new URLSearchParams(params)),
};

// ── AI ─────────────────────────────────────────────────────
const AI = {
  reply:    (body) => apiFetch('/ai/reply',    { method: 'POST', body }),
  insights: ()     => apiFetch('/ai/insights'),
  report:   ()     => apiFetch('/ai/report'),
};
