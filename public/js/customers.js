// ── Customers page ─────────────────────────────────────────
let custOffset = 0, custLimit = 10, custSearch = '', custEditId = null;

function initCustomers() {
  document.getElementById('cust-search').addEventListener('input', (e) => {
    custSearch = e.target.value; custOffset = 0; loadCustomers();
  });
  document.getElementById('btn-add-customer').addEventListener('click', () => openCustomerModal());
  document.getElementById('cust-form').addEventListener('submit', saveCustomer);
  document.getElementById('cust-modal-close').addEventListener('click', () => closeModal('cust-modal'));
  loadCustomers();
}

async function loadCustomers() {
  const tbody = document.getElementById('cust-tbody');
  tbody.innerHTML = `<tr><td colspan="5" class="table-loading"><i class="fa-solid fa-spinner fa-spin"></i> Loading…</td></tr>`;
  try {
    const res = await Customers.list({ limit: custLimit, offset: custOffset, search: custSearch });
    renderCustomers(res.data || []);
    renderPagination(
      document.getElementById('cust-pagination'),
      res.data?.length || 0,
      custLimit, custOffset,
      (off) => { custOffset = off; loadCustomers(); }
    );
  } catch (err) {
    toast(err.message, 'error');
    tbody.innerHTML = `<tr><td colspan="5">${emptyState('triangle-exclamation', 'Failed to load customers')}</td></tr>`;
  }
}

function renderCustomers(customers) {
  const tbody = document.getElementById('cust-tbody');
  if (!customers.length) {
    tbody.innerHTML = `<tr><td colspan="5">${emptyState('users', 'No customers found')}</td></tr>`;
    return;
  }
  tbody.innerHTML = customers.map(c => `
    <tr>
      <td data-label="Name">${c.name}</td>
      <td data-label="Phone">${c.phone || '—'}</td>
      <td data-label="Email">${c.email || '—'}</td>
      <td data-label="Notes">${c.notes ? c.notes.substring(0, 40) + (c.notes.length > 40 ? '…' : '') : '—'}</td>
      <td data-label="Actions" class="actions-cell">
        <button class="btn btn-ghost btn-sm" onclick="openCustomerModal(${c.id})">
          <i class="fa-solid fa-pen-to-square"></i> Edit
        </button>
        <button class="btn btn-danger btn-sm" onclick="deleteCustomer(${c.id})">
          <i class="fa-solid fa-trash"></i>
        </button>
      </td>
    </tr>
  `).join('');
}

function openCustomerModal(id = null) {
  custEditId = id;
  const form  = document.getElementById('cust-form');
  const title = document.getElementById('cust-modal-title');
  form.reset();
  if (id) {
    title.textContent = 'Edit Customer';
    Customers.list({ limit: 1000 }).then(res => {
      const c = res.data.find(x => x.id == id);
      if (c) {
        form.cust_name.value  = c.name;
        form.cust_phone.value = c.phone || '';
        form.cust_email.value = c.email || '';
        form.cust_notes.value = c.notes || '';
      }
    });
  } else {
    title.textContent = 'Add Customer';
  }
  openModal('cust-modal');
}

async function saveCustomer(e) {
  e.preventDefault();
  const btn  = e.target.querySelector('button[type=submit]');
  const body = {
    name:  document.getElementById('cust_name').value.trim(),
    phone: document.getElementById('cust_phone').value.trim(),
    email: document.getElementById('cust_email').value.trim(),
    notes: document.getElementById('cust_notes').value.trim(),
  };
  if (!body.name) return toast('Name is required.', 'warning');
  setLoading(btn, true);
  try {
    if (custEditId) {
      await Customers.update(custEditId, body);
      toast('Customer updated.', 'success');
    } else {
      await Customers.create(body);
      toast('Customer added.', 'success');
    }
    closeModal('cust-modal');
    loadCustomers();
  } catch (err) {
    toast(err.message, 'error');
  } finally {
    setLoading(btn, false);
  }
}

async function deleteCustomer(id) {
  if (!confirmAction('Delete this customer?')) return;
  try {
    await Customers.remove(id);
    toast('Customer deleted.', 'success');
    loadCustomers();
  } catch (err) {
    toast(err.message, 'error');
  }
}
