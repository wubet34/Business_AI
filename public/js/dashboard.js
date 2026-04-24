// ── Dashboard ──────────────────────────────────────────────
let salesChart = null;

async function initDashboard() {
  try {
    const [insightsRes, salesRes] = await Promise.all([
      AI.insights(),
      Sales.list({ limit: 7 }),
    ]);

    document.getElementById('stat-revenue').textContent  = formatMoney(insightsRes.total_revenue);
    document.getElementById('stat-lowstock').textContent = insightsRes.low_stock_products?.length ?? 0;
    document.getElementById('stat-products').textContent = insightsRes.top_selling_products?.length ?? 0;
    document.getElementById('stat-sales').textContent    = salesRes.data?.length ?? 0;

    renderRecentSales(salesRes.data || []);
    renderSalesChart(salesRes.data || []);
    renderLowStockAlerts(insightsRes.low_stock_products || []);
  } catch (err) {
    toast('Failed to load dashboard data.', 'error');
  }
}

function renderRecentSales(sales) {
  const tbody = document.getElementById('recent-sales-body');
  if (!tbody) return;
  if (!sales.length) {
    tbody.innerHTML = '<tr><td colspan="4">' + emptyState('receipt', 'No sales yet') + '</td></tr>';
    return;
  }
  tbody.innerHTML = sales.map(function(s) {
    return '<tr><td>#' + s.id + '</td><td>' + (s.customer_name || '—') + '</td><td>' +
      formatMoney(s.total_amount) + '</td><td>' + formatDate(s.created_at) + '</td></tr>';
  }).join('');
}

function renderSalesChart(sales) {
  const ctx = document.getElementById('sales-chart');
  if (!ctx) return;
  const reversed = [].concat(sales).reverse();
  const labels   = reversed.map(function(s) { return formatDate(s.created_at); });
  const values   = reversed.map(function(s) { return parseFloat(s.total_amount); });
  if (salesChart) salesChart.destroy();

  const isDark  = (localStorage.getItem('sbms_theme') || 'dark') === 'dark';
  const gridCol = isDark ? '#2e2e2e' : '#e0e0e0';
  const tickCol = isDark ? '#666666' : '#888888';
  const lineCol = isDark ? '#ffffff' : '#000000';
  const fillCol = isDark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.05)';

  salesChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: labels,
      datasets: [{
        label: 'Revenue',
        data: values,
        borderColor: lineCol,
        backgroundColor: fillCol,
        tension: 0.4,
        fill: true,
        pointBackgroundColor: lineCol,
        pointRadius: 4,
        borderWidth: 1.5,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: true,
      plugins: { legend: { display: false } },
      scales: {
        x: { ticks: { color: tickCol, font: { size: 11 } }, grid: { color: gridCol } },
        y: {
          ticks: {
            color: tickCol,
            font: { size: 11 },
            callback: function(v) { return '$' + v; }
          },
          grid: { color: gridCol },
        },
      }
    }
  });
}

function renderLowStockAlerts(products) {
  const el = document.getElementById('low-stock-alerts');
  if (!el) return;
  if (!products.length) {
    el.innerHTML = '<p class="muted-text" style="padding:8px 0"><i class="fa-solid fa-circle-check"></i> All products are well stocked.</p>';
    return;
  }
  el.innerHTML = products.map(function(p) {
    return '<div class="list-row"><span>' + p.name + '</span>' +
      '<span class="badge">' +
        '<i class="fa-solid fa-box"></i> ' +
        (p.stock_quantity === 0 ? 'Out of stock' : 'Stock: ' + p.stock_quantity) +
      '</span></div>';
  }).join('');
}
