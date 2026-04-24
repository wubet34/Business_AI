// ── AI Assistant page ──────────────────────────────────────
let chatCustomerId = null;

async function initAI() {
  // Populate customer selector for chat
  try {
    const res = await Customers.list({ limit: 1000 });
    const sel = document.getElementById('chat-customer');
    sel.innerHTML = '<option value="">— Select Customer —</option>' +
      (res.data || []).map(c => `<option value="${c.id}">${c.name}</option>`).join('');
  } catch {}

  document.getElementById('chat-customer').addEventListener('change', (e) => {
    chatCustomerId = e.target.value || null;
  });

  document.getElementById('chat-send-btn').addEventListener('click', sendChatMessage);
  document.getElementById('chat-input').addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendChatMessage(); }
  });

  document.getElementById('btn-insights').addEventListener('click', loadInsights);
  document.getElementById('btn-report').addEventListener('click', loadReport);
}

async function sendChatMessage() {
  const input = document.getElementById('chat-input');
  const msg   = input.value.trim();
  if (!msg) return;
  if (!chatCustomerId) return toast('Please select a customer first.', 'warning');

  appendBubble(msg, 'user');
  input.value = '';

  const btn = document.getElementById('chat-send-btn');
  setLoading(btn, true);
  appendBubble('Thinking…', 'ai', 'typing-bubble');
  try {
    const res = await AI.reply({ customer_id: parseInt(chatCustomerId), message: msg });
    document.getElementById('typing-bubble')?.remove();
    appendBubble(res.reply, 'ai');
  } catch (err) {
    document.getElementById('typing-bubble')?.remove();
    appendBubble('Sorry, I could not generate a reply.', 'ai');
    toast(err.message, 'error');
  } finally {
    setLoading(btn, false);
  }
}

function appendBubble(text, who, id = '') {
  const wrap = document.getElementById('chat-messages');
  const div  = document.createElement('div');
  div.className = `chat-bubble ${who}`;
  if (id) div.id = id;
  div.innerHTML = `<div class="bubble-label">${who === 'user' ? '<i class="fa-solid fa-user"></i> You' : '<i class="fa-solid fa-robot"></i> AI'}</div>${text}`;
  wrap.appendChild(div);
  wrap.scrollTop = wrap.scrollHeight;
}

async function loadInsights() {
  const btn = document.getElementById('btn-insights');
  setLoading(btn, true);
  try {
    const res = await AI.insights();
    const top = res.top_selling_products?.map(p =>
      `<div class="list-row">
        <span>${p.name}</span>
        <span class="badge badge-info"><i class="fa-solid fa-arrow-trend-up"></i> ${p.total_sold} sold</span>
      </div>`
    ).join('') || '<p class="muted-text">No data yet</p>';

    const low = res.low_stock_products?.map(p =>
      `<div class="list-row">
        <span>${p.name}</span>
        <span class="badge badge-warning"><i class="fa-solid fa-box"></i> Stock: ${p.stock_quantity}</span>
      </div>`
    ).join('') || '<p class="muted-text">All stocked well</p>';

    document.getElementById('insights-result').innerHTML = `
      <div class="insight-grid">
        <div class="insight-card">
          <h4><i class="fa-solid fa-dollar-sign"></i> Total Revenue</h4>
          <div style="font-size:1.6rem;font-weight:700;color:var(--text)">${formatMoney(res.total_revenue)}</div>
        </div>
        <div class="insight-card">
          <h4><i class="fa-solid fa-trophy"></i> Top Selling Products</h4>${top}
        </div>
        <div class="insight-card">
          <h4><i class="fa-solid fa-triangle-exclamation"></i> Low Stock Alerts</h4>${low}
        </div>
      </div>
    `;
  } catch (err) {
    toast(err.message, 'error');
  } finally {
    setLoading(btn, false);
  }
}

async function loadReport() {
  const btn = document.getElementById('btn-report');
  setLoading(btn, true);
  try {
    const res = await AI.report();
    document.getElementById('report-result').innerHTML = `
      <div class="card" style="white-space:pre-line;line-height:1.8;font-size:.92rem">${res.report}</div>
    `;
  } catch (err) {
    toast(err.message, 'error');
  } finally {
    setLoading(btn, false);
  }
}
