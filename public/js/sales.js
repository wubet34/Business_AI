// ── Sales page ─────────────────────────────────────────────
let salesOffset = 0, salesLimit = 10, allProducts = [], saleItems = [];

async function initSales() {
  // Load products for the sale builder
  try {
    const res = await Products.list({ limit: 1000 });
    allProducts = res.data || [];
  } catch { allProducts = []; }

  // Populate customer dropdown
  try {
    const res = await Customers.list({ limit: 1000 });
    const sel = document.getElementById('sale-customer');
    sel.innerHTML = '<option value="">— Select Customer —</option>' +
      (res.data || []).map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  } catch {}

  document.getElementById('btn-add-sale-item').addEventListener('click', addSaleItemRow);
  document.getElementById('sale-form').addEventListener('submit', submitSale);
  document.getElementById('sale-date-from').addEventListener('change', () => { salesOffset = 0; loadSales(); });
  document.getElementById('sale-date-to').addEventListener('change',   () => { salesOffset = 0; loadSales(); });
  document.getElementById('btn-export-csv').addEventListener('click', exportCSV);

  addSaleItemRow(); // start with one row
  loadSales();
}

function addSaleItemRow() {
  const container = document.getElementById('sale-items-container');
  const idx = saleItems.length;
  saleItems.push({ product_id: '', quantity: 1 });

  const row = document.createElement('div');
  row.className = 'sale-item-row';
  row.dataset.idx = idx;
  row.innerHTML = `
    <select class="form-control item-product" onchange="updateSaleItem(${idx}, 'product_id', this.value); calcTotal()">
      <option value="">— Product —</option>
      ${allProducts.map(p => `<option value="${p.id}" data-price="${p.price}" data-stock="${p.stock_quantity}">${p.name} (${p.stock_quantity} left)</option>`).join('')}
    </select>
    <input type="number" class="form-control item-qty" min="1" value="1"
      oninput="updateSaleItem(${idx}, 'quantity', +this.value); calcTotal()" placeholder="Qty">
    <span class="item-price">$0.00</span>
    <button type="button" class="btn btn-danger btn-sm item-remove" onclick="removeSaleItemRow(this, ${idx})">
      <i class="fa-solid fa-xmark"></i>
    </button>
  `;
  container.appendChild(row);
}

function removeSaleItemRow(btn, idx) {
  btn.closest('.sale-item-row').remove();
  saleItems[idx] = null;
  calcTotal();
}

function updateSaleItem(idx, key, val) {
  if (saleItems[idx]) saleItems[idx][key] = val;
}

function calcTotal() {
  let total = 0;
  document.querySelectorAll('.sale-item-row').forEach(row => {
    const sel   = row.querySelector('.item-product');
    const qty   = parseFloat(row.querySelector('.item-qty').value) || 0;
    const opt   = sel.options[sel.selectedIndex];
    const price = parseFloat(opt?.dataset?.price || 0);
    const sub   = price * qty;
    row.querySelector('.item-price').textContent = formatMoney(sub);
    total += sub;
  });
  document.getElementById('sale-total-display').textContent = formatMoney(total);
}

async function submitSale(e) {
  e.preventDefault();
  const btn        = e.target.querySelector('button[type=submit]');
  const customerId = document.getElementById('sale-customer').value;
  if (!customerId) return toast('Please select a customer.', 'warning');

  const items = [];
  document.querySelectorAll('.sale-item-row').forEach(row => {
    const productId = row.querySelector('.item-product').value;
    const quantity  = parseInt(row.querySelector('.item-qty').value);
    if (productId && quantity > 0) items.push({ product_id: parseInt(productId), quantity });
  });
  if (!items.length) return toast('Add at least one product.', 'warning');

  setLoading(btn, true);
  try {
    await Sales.create({ customer_id: parseInt(customerId), items });
    toast('Sale created successfully!', 'success');
    document.getElementById('sale-form').reset();
    document.getElementById('sale-items-container').innerHTML = '';
    saleItems = [];
    addSaleItemRow();
    document.getElementById('sale-total-display').textContent = '$0.00';
    loadSales();
    // Refresh products stock
    const res = await Products.list({ limit: 1000 });
    allProducts = res.data || [];
  } catch (err) {
    toast(err.message, 'error');
  } finally {
    setLoading(btn, false);
  }
}

async function loadSales() {
  const tbody = document.getElementById('sales-tbody');
  tbody.innerHTML = `<tr><td colspan="5" class="table-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</td></tr>`;
  const from = document.getElementById('sale-date-from').value;
  const to   = document.getElementById('sale-date-to').value;
  try {
    const res = await Sales.list({ limit: salesLimit, offset: salesOffset, from, to });
    renderSalesTable(res.data || []);
    renderPagination(
      document.getElementById('sales-pagination'),
      res.data?.length || 0,
      salesLimit, salesOffset,
      (off) => { salesOffset = off; loadSales(); }
    );
  } catch (err) {
    toast(err.message, 'error');
  }
}

function renderSalesTable(sales) {
  const tbody = document.getElementById('sales-tbody');
  if (!sales.length) {
    tbody.innerHTML = `<tr><td colspan="5">${emptyState('receipt', 'No sales found')}</td></tr>`;
    return;
  }
  tbody.innerHTML = sales.map(s => `
    <tr>
      <td>#${s.id}</td>
      <td>${s.customer_name || '—'}</td>
      <td>${formatMoney(s.total_amount)}</td>
      <td>${formatDate(s.created_at)}</td>
      <td><button class="btn btn-ghost btn-sm" onclick="viewSale(${s.id})"><i class="fa-solid fa-eye"></i> View</button></td>
    </tr>
  `).join('');
}

async function viewSale(id) {
  try {
    const res = await Sales.get(id);
    const s   = res.data;
    const items = (s.items || []).map(i =>
      `<tr><td>${i.product_name}</td><td>${i.quantity}</td><td>${formatMoney(i.price)}</td><td>${formatMoney(i.price * i.quantity)}</td></tr>`
    ).join('');
    document.getElementById('sale-detail-body').innerHTML = `
      <p><strong>Sale #${s.id}</strong> — ${formatDate(s.created_at)}</p>
      <p style="margin:8px 0;color:var(--muted)">Customer: ${s.customer_name || '—'}</p>
      <div class="table-wrap" style="margin-top:12px">
        <table><thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
        <tbody>${items}</tbody></table>
      </div>
      <div class="sale-total-bar" style="margin-top:12px">
        <span class="total-label">Total</span>
        <span class="total-value">${formatMoney(s.total_amount)}</span>
      </div>
    `;
    openModal('sale-detail-modal');
  } catch (err) {
    toast(err.message, 'error');
  }
}

async function exportCSV() {
  const from = document.getElementById('sale-date-from').value;
  const to   = document.getElementById('sale-date-to').value;
  try {
    const res  = await Sales.export({ from, to });
    const blob = await res.blob();
    const url  = URL.createObjectURL(blob);
    const a    = document.createElement('a');
    a.href = url; a.download = 'sales_export.csv'; a.click();
    URL.revokeObjectURL(url);
  } catch (err) {
    toast('Export failed.', 'error');
  }
}
