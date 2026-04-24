// ── Theme ──────────────────────────────────────────────────
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  localStorage.setItem('sbms_theme', theme);
  const btn = document.getElementById('theme-toggle');
  if (btn) {
    btn.title = theme === 'dark' ? 'Switch to Light Mode' : 'Switch to Dark Mode';
    btn.innerHTML = theme === 'dark'
      ? '<i class="fa-solid fa-sun"></i>'
      : '<i class="fa-solid fa-moon"></i>';
  }
}

function initTheme() {
  const saved = localStorage.getItem('sbms_theme') || 'dark';
  applyTheme(saved);
}

function toggleTheme() {
  const current = localStorage.getItem('sbms_theme') || 'dark';
  applyTheme(current === 'dark' ? 'light' : 'dark');
}

// ── Toast ──────────────────────────────────────────────────
function toast(message, type = 'info') {
  const icons = {
    success: '<i class="fa-solid fa-circle-check"></i>',
    error:   '<i class="fa-solid fa-circle-xmark"></i>',
    warning: '<i class="fa-solid fa-triangle-exclamation"></i>',
    info:    '<i class="fa-solid fa-circle-info"></i>',
  };
  const el = document.createElement('div');
  el.className = 'toast ' + type;
  el.innerHTML = '<span class="toast-icon">' + (icons[type] || icons.info) + '</span><span>' + message + '</span>';
  const container = document.getElementById('toast-container');
  if (container) container.appendChild(el);
  setTimeout(() => el.remove(), 3500);
}

// ── Loading state ──────────────────────────────────────────
function setLoading(btn, loading) {
  if (!btn) return;
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Loading…';
    btn.disabled = true;
  } else {
    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
    btn.disabled = false;
  }
}

// ── Modal helpers ──────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.add('open'); document.body.style.overflow = 'hidden'; }
}
function closeModal(id) {
  const el = document.getElementById(id);
  if (el) { el.classList.remove('open'); document.body.style.overflow = ''; }
}

document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
    document.body.style.overflow = '';
  }
});

// ── Format currency ────────────────────────────────────────
function formatMoney(val) {
  const n = parseFloat(val || 0);
  return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// ── Format date ────────────────────────────────────────────
function formatDate(str) {
  if (!str) return '—';
  return new Date(str).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ── Confirm dialog ─────────────────────────────────────────
function confirmAction(message) {
  return window.confirm(message);
}

// ── Empty state ────────────────────────────────────────────
function emptyState(icon, message) {
  return '<div class="empty-state"><div class="empty-icon"><i class="fa-solid fa-' + icon + '"></i></div><p>' + message + '</p></div>';
}

// ── Pagination ─────────────────────────────────────────────
function renderPagination(container, total, limit, offset, onPage) {
  if (!container) return;
  // total here is the count of items returned; use it to determine if there's a next page
  const current   = Math.floor(offset / limit) + 1;
  const hasPrev   = offset > 0;
  const hasNext   = total >= limit; // if we got a full page, assume there's more
  container.innerHTML =
    '<button class="btn btn-ghost btn-sm" ' + (hasPrev ? '' : 'disabled') + ' data-page="' + (current - 1) + '">' +
      '<i class="fa-solid fa-chevron-left"></i> Prev' +
    '</button>' +
    '<span class="page-info">Page ' + current + '</span>' +
    '<button class="btn btn-ghost btn-sm" ' + (hasNext ? '' : 'disabled') + ' data-page="' + (current + 1) + '">' +
      'Next <i class="fa-solid fa-chevron-right"></i>' +
    '</button>';
  container.querySelectorAll('button[data-page]').forEach(btn => {
    btn.addEventListener('click', () => onPage((+btn.dataset.page - 1) * limit));
  });
}

// ── Session helpers ────────────────────────────────────────
function saveUser(user) { localStorage.setItem('sbms_user', JSON.stringify(user)); }
function getUser()      { try { return JSON.parse(localStorage.getItem('sbms_user')); } catch { return null; } }
function clearUser()    { localStorage.removeItem('sbms_user'); }
