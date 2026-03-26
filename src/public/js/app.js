'use strict';

/* ──────────────────────────────────────────────
   Utility helpers
────────────────────────────────────────────── */
const BASE = '';

async function apiFetch(url, options = {}) {
  const res = await fetch(BASE + url, {
    headers: { 'Content-Type': 'application/json', ...(options.headers || {}) },
    ...options,
  });
  const text = await res.text();
  let data;
  try { data = JSON.parse(text); } catch { data = text; }
  if (!res.ok) throw new Error((data && data.error) || `HTTP ${res.status}`);
  return data;
}

function toast(message, type = 'success') {
  const container = document.getElementById('toast-container');
  const el = document.createElement('div');
  el.className = `toast${type === 'error' ? ' error' : ''}`;
  el.textContent = message;
  container.appendChild(el);
  setTimeout(() => el.remove(), 4000);
}

function setStatus(id, message, role = 'status') {
  const el = document.getElementById(id);
  if (el) { el.textContent = message; el.setAttribute('role', role); }
}

function statusBadge(status) {
  const labels = { pending: 'Pendiente', confirmed: 'Confirmada', cancelled: 'Cancelada', completed: 'Completada' };
  return `<span class="badge badge-${status}" aria-label="Estado: ${labels[status] || status}">${labels[status] || status}</span>`;
}

/* ──────────────────────────────────────────────
   Tenant selects (shared across sections)
────────────────────────────────────────────── */
async function populateTenantSelects(tenants) {
  ['filtro-empresa', 'filtro-empresa-clientes'].forEach((id) => {
    const sel = document.getElementById(id);
    if (!sel) return;
    const current = sel.value;
    sel.innerHTML = '<option value="">— Selecciona una empresa —</option>';
    tenants.forEach((t) => {
      const opt = document.createElement('option');
      opt.value = t.id;
      opt.textContent = t.name;
      if (String(t.id) === current) opt.selected = true;
      sel.appendChild(opt);
    });
  });
}

/* ──────────────────────────────────────────────
   Empresas
────────────────────────────────────────────── */
let tenantsList = [];

async function loadEmpresas() {
  try {
    tenantsList = await apiFetch('/api/empresas');
    renderTenants(tenantsList);
    populateTenantSelects(tenantsList);
  } catch (err) {
    setStatus('status-empresas', 'Error al cargar empresas: ' + err.message, 'alert');
  }
}

function renderTenants(tenants) {
  const tbody = document.getElementById('tbody-empresas');
  if (!tenants.length) {
    tbody.innerHTML = '<tr><td colspan="5">No hay empresas registradas.</td></tr>';
    return;
  }
  tbody.innerHTML = tenants.map((t) => `
    <tr>
      <td>${t.id}</td>
      <td>${escHtml(t.name)}</td>
      <td>${escHtml(t.phone)}</td>
      <td>${escHtml(t.timezone)}</td>
      <td>
        <button class="btn btn-danger btn-sm" onclick="deleteTenant(${t.id}, '${escAttr(t.name)}')"
                aria-label="Eliminar empresa ${escAttr(t.name)}">
          Eliminar
        </button>
      </td>
    </tr>
  `).join('');
}

async function deleteTenant(id, name) {
  if (!confirm(`¿Eliminar la empresa "${name}"? Esta acción no se puede deshacer.`)) return;
  try {
    await apiFetch(`/api/empresas/${id}`, { method: 'DELETE' });
    toast(`Empresa "${name}" eliminada.`);
    loadEmpresas();
  } catch (err) {
    toast('Error: ' + err.message, 'error');
  }
}

document.getElementById('form-empresa').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);
  const body = Object.fromEntries(formData.entries());
  const statusEl = document.getElementById('form-empresa-status');
  statusEl.textContent = '';

  if (!body.name.trim() || !body.phone.trim()) {
    statusEl.textContent = 'Nombre y teléfono son requeridos.';
    statusEl.classList.remove('sr-only');
    return;
  }

  try {
    await apiFetch('/api/empresas', { method: 'POST', body: JSON.stringify(body) });
    toast('Empresa registrada correctamente.');
    e.target.reset();
    loadEmpresas();
  } catch (err) {
    statusEl.textContent = 'Error: ' + err.message;
    statusEl.classList.remove('sr-only');
    toast('Error: ' + err.message, 'error');
  }
});

/* ──────────────────────────────────────────────
   Citas
────────────────────────────────────────────── */
document.getElementById('form-filtro-citas').addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData  = new FormData(e.target);
  const tenantId  = formData.get('tenantId');
  const date      = formData.get('date');
  const status    = formData.get('status');

  if (!tenantId) {
    setStatus('status-citas', 'Selecciona una empresa.', 'alert');
    return;
  }

  setStatus('status-citas', 'Cargando…');

  try {
    const params = new URLSearchParams();
    if (date)   params.set('date', date);
    if (status) params.set('status', status);
    const citas = await apiFetch(`/api/empresas/${tenantId}/citas?${params}`);
    renderCitas(citas, tenantId);
    setStatus('status-citas', `${citas.length} cita(s) encontrada(s).`);
  } catch (err) {
    setStatus('status-citas', 'Error: ' + err.message, 'alert');
  }
});

function renderCitas(citas, tenantId) {
  const tbody = document.getElementById('tbody-citas');
  if (!citas.length) {
    tbody.innerHTML = '<tr><td colspan="7">No se encontraron citas.</td></tr>';
    return;
  }
  tbody.innerHTML = citas.map((c) => `
    <tr>
      <td>${c.id}</td>
      <td>${escHtml(c.client_name)}</td>
      <td>${escHtml(c.service)}</td>
      <td>${escHtml(c.appointment_date)}</td>
      <td>${escHtml(c.appointment_time)}</td>
      <td>${statusBadge(c.status)}</td>
      <td>
        ${c.status !== 'cancelled' && c.status !== 'completed' ? `
          <button class="btn btn-danger btn-sm"
                  onclick="cancelCita(${c.id}, ${tenantId})"
                  aria-label="Cancelar cita #${c.id} de ${escAttr(c.client_name)}">
            Cancelar
          </button>` : ''}
      </td>
    </tr>
  `).join('');
}

async function cancelCita(id, tenantId) {
  if (!confirm(`¿Cancelar la cita #${id}?`)) return;
  try {
    await apiFetch(`/api/empresas/${tenantId}/citas/${id}/estado`, {
      method: 'PATCH',
      body: JSON.stringify({ status: 'cancelled' }),
    });
    toast(`Cita #${id} cancelada.`);
    document.getElementById('form-filtro-citas').dispatchEvent(new Event('submit'));
  } catch (err) {
    toast('Error: ' + err.message, 'error');
  }
}

/* ──────────────────────────────────────────────
   Clientes
────────────────────────────────────────────── */
document.getElementById('form-filtro-clientes').addEventListener('submit', async (e) => {
  e.preventDefault();
  const tenantId = new FormData(e.target).get('tenantId');
  if (!tenantId) {
    setStatus('status-clientes', 'Selecciona una empresa.', 'alert');
    return;
  }
  setStatus('status-clientes', 'Cargando…');
  try {
    const clients = await apiFetch(`/api/empresas/${tenantId}/clientes`);
    renderClientes(clients);
    setStatus('status-clientes', `${clients.length} cliente(s) encontrado(s).`);
  } catch (err) {
    setStatus('status-clientes', 'Error: ' + err.message, 'alert');
  }
});

function renderClientes(clients) {
  const tbody = document.getElementById('tbody-clientes');
  if (!clients.length) {
    tbody.innerHTML = '<tr><td colspan="5">No hay clientes registrados para esta empresa.</td></tr>';
    return;
  }
  tbody.innerHTML = clients.map((c) => `
    <tr>
      <td>${c.id}</td>
      <td>${escHtml(c.name)}</td>
      <td>${escHtml(c.phone)}</td>
      <td>${escHtml(c.email || '—')}</td>
      <td>${escHtml(c.created_at)}</td>
    </tr>
  `).join('');
}

/* ──────────────────────────────────────────────
   XSS helpers
────────────────────────────────────────────── */
function escHtml(str) {
  if (str == null) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#39;');
}

function escAttr(str) {
  if (str == null) return '';
  return String(str).replace(/'/g, "\\'").replace(/"/g, '&quot;');
}

/* ──────────────────────────────────────────────
   Init
────────────────────────────────────────────── */
document.addEventListener('DOMContentLoaded', () => {
  loadEmpresas();
});
