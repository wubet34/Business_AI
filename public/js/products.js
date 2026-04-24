// ── Products page ──────────────────────────────────────────
let prodOffset = 0, prodLimit = 10, prodSearch = '', prodEditId = null;

function initProducts() {
  document.getElementById('prod-search').addEventListener('input', (e) => {
    prodSearch = e.target.value; prodOffset = 0; loadProducts();
  });
  document.getElementById('btn-add-product').addEventListener('click', () => openProductModal());
  document.getElementById('prod-form').addEventListener('submit', saveProduct);
  document.getElementById('prod-modal-close').addEventListener('click', () => closeModal('prod-modal'));
  loadProducts();
}

async function loadProducts() {
  const tbody = document.getElementById('prod-tbody');
  tbody.innerHTML = `<tr><td colspan="5" class="table-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</td></tr>`;
  try {
    const res = await Products.list({ limit: prodLimit, offset: prodOffset, search: prodSearch });
    renderProducts(res.data || []);
    renderPagination(
      document.getElementById('prod-pagination'),
      res.data?.length || 0,
      prodLimit, prodOffset,
      (off) => { prodOffset = off; loadProducts(); }
    );
  } catch (err) {
    toast(err.message, 'error');
  }
}

function renderProducts(products) {
  const tbody = document.getElementById('prod-tbody');
  if (!products.length) {
    tbody.innerHTML = `<tr><td colspan="5">${emptyState('box', 'No products found')}</td></tr>`;
    return;
  }
  tbody.innerHTML = products.map(p => {
    const stock = parseInt(p.stock_quantity);
    const badge = stock === 0
      ? `<span class="badge badge-danger">Out of stock</span>`
      : stock <= 5
        ? `<span class="badge badge-warning">Low: ${stock}</span>`
        : `<span class="badge badge-success">${stock}</span>`;
    return `
      <tr>
        <td data-label="Name">${p.name}</td>
        <td data-label="Price">${formatMoney(p.price)}</td>
        <td data-label="Stock">${badge}</td>
        <td data-label="Created">${formatDate(p.created_at)}</td>
        <td data-label="Actions" class="actions-cell">
          <button class="btn btn-ghost btn-sm" onclick="openProductModal(${p.id})">
            <i class="fa-solid fa-pen-to-square"></i> Edit
          </button>
          <button class="btn btn-danger btn-sm" onclick="deleteProduct(${p.id})">
            <i class="fa-solid fa-trash"></i>
          </button>
        </td>
      </tr>
    `;
  }).join('');
}

function openProductModal(id = null) {
  prodEditId = id;
  const form  = document.getElementById('prod-form');
  const title = document.getElementById('prod-modal-title');
  form.reset();
  if (id) {
    title.textContent = 'Edit Product';
    Products.list({ limit: 1000 }).then(res => {
      const p = res.data.find(x => x.id == id);
      if (p) {
        form.prod_name.value  = p.name;
        form.prod_price.value = p.price;
        form.prod_stock.value = p.stock_quantity;
      }
    });
  } else {
    title.textContent = 'Add Product';
  }
  openModal('prod-modal');
}

async function saveProduct(e) {
  e.preventDefault();
  const btn  = e.target.querySelector('button[type=submit]');
  const body = {
    name:           document.getElementById('prod_name').value.trim(),
    price:          parseFloat(document.getElementById('prod_price').value),
    stock_quantity: parseInt(document.getElementById('prod_stock').value),
  };
  if (!body.name)               return toast('Name is required.', 'warning');
  if (isNaN(body.price))        return toast('Valid price required.', 'warning');
  if (isNaN(body.stock_quantity)) return toast('Valid stock required.', 'warning');
  setLoading(btn, true);
  try {
    if (prodEditId) {
      await Products.update(prodEditId, body);
      toast('Product updated.', 'success');
    } else {
      await Products.create(body);
      toast('Product added.', 'success');
    }
    closeModal('prod-modal');
    loadProducts();
  } catch (err) {
    toast(err.message, 'error');
  } finally {
    setLoading(btn, false);
  }
}

async function deleteProduct(id) {
  if (!confirmAction('Delete this product?')) return;
  try {
    await Products.remove(id);
    toast('Product deleted.', 'success');
    loadProducts();
  } catch (err) {
    toast(err.message, 'error');
  }
}
