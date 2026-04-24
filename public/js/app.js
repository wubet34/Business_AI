// ── Boot: guard + theme ────────────────────────────────────
initTheme();

const currentUser = getUser();
if (!currentUser) { window.location.href = 'login.html'; }

// ── Topbar user info + role badge ──────────────────────────
document.getElementById('user-name').textContent   = currentUser?.name || 'User';
document.getElementById('user-avatar').textContent = (currentUser?.name || 'U')[0].toUpperCase();

const roleBadge = document.getElementById('role-badge');
if (roleBadge) {
  roleBadge.textContent = currentUser?.role === 'admin' ? 'Admin' : 'Staff';
  roleBadge.className   = 'role-badge';
}

// ── Role-based nav ─────────────────────────────────────────
if (currentUser?.role !== 'admin') {
  document.querySelectorAll('[data-admin-only]').forEach(el => el.remove());
}

// ── Navigation ─────────────────────────────────────────────
const pages = ['dashboard', 'customers', 'products', 'sales', 'ai'];
const pageTitles = {
  dashboard: 'Dashboard',
  customers: 'Customers',
  products:  'Products',
  sales:     'Sales',
  ai:        'AI Assistant',
};

const pageInited = {};

function navigateTo(page) {
  const navEl = document.querySelector('[data-page="' + page + '"]');
  if (navEl && navEl.dataset.adminOnly !== undefined && currentUser?.role !== 'admin') {
    toast('Access denied.', 'error');
    return;
  }

  pages.forEach(function(p) {
    var pageEl = document.getElementById('page-' + p);
    var navItem = document.querySelector('[data-page="' + p + '"]');
    if (pageEl)  pageEl.classList.toggle('active', p === page);
    if (navItem) navItem.classList.toggle('active', p === page);
  });

  var titleEl = document.getElementById('page-title');
  if (titleEl) titleEl.textContent = pageTitles[page] || page;

  if (!pageInited[page]) {
    pageInited[page] = true;
    if (page === 'dashboard') initDashboard();
    if (page === 'customers') initCustomers();
    if (page === 'products')  initProducts();
    if (page === 'sales')     initSales();
    if (page === 'ai')        initAI();
  } else {
    if (page === 'dashboard') initDashboard();
    if (page === 'customers') loadCustomers();
    if (page === 'products')  loadProducts();
    if (page === 'sales')     loadSales();
  }
}

document.querySelectorAll('.nav-item').forEach(function(item) {
  item.addEventListener('click', function() {
    navigateTo(item.dataset.page);
    closeSidebar();
  });
});

// ── Sidebar toggle ─────────────────────────────────────────
var sidebar  = document.getElementById('sidebar');
var backdrop = document.getElementById('sidebar-backdrop');
var hamburger = document.getElementById('hamburger');

function openSidebar() {
  sidebar.classList.add('open');
  backdrop.classList.add('show');
  document.body.style.overflow = 'hidden'; // prevent scroll behind overlay
}

function closeSidebar() {
  sidebar.classList.remove('open');
  backdrop.classList.remove('show');
  document.body.style.overflow = '';
}

function toggleSidebar() {
  if (sidebar.classList.contains('open')) {
    closeSidebar();
  } else {
    openSidebar();
  }
}

hamburger.addEventListener('click', function(e) {
  e.stopPropagation();
  toggleSidebar();
});

backdrop.addEventListener('click', closeSidebar);

// Close sidebar on Escape key
document.addEventListener('keydown', function(e) {
  if (e.key === 'Escape') closeSidebar();
});

// ── Theme toggle ───────────────────────────────────────────
var themeBtn = document.getElementById('theme-toggle');
if (themeBtn) {
  themeBtn.addEventListener('click', function() {
    toggleTheme();
    if (pageInited['dashboard']) initDashboard();
  });
}

// ── Logout ─────────────────────────────────────────────────
document.getElementById('logout-btn').addEventListener('click', async function() {
  try { await Auth.logout(); } catch(e) {}
  clearUser();
  window.location.href = 'login.html';
});

// ── Boot ───────────────────────────────────────────────────
navigateTo('dashboard');
