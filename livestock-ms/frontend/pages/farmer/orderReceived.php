<?php $navRole = 'farmer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders Received — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">📦 Orders Received</div><div class="page-subtitle">Buyers interested in your livestock</div></div>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(160px,1fr));margin-bottom:22px;">
    <div class="stat-card"><div class="stat-icon amber">⏳</div><div><div class="stat-value" id="cntPending">—</div><div class="stat-label">Pending</div></div></div>
    <div class="stat-card"><div class="stat-icon blue">✅</div><div><div class="stat-value" id="cntConfirmed">—</div><div class="stat-label">Confirmed</div></div></div>
    <div class="stat-card"><div class="stat-icon green">🎉</div><div><div class="stat-value" id="cntCompleted">—</div><div class="stat-label">Completed</div></div></div>
    <div class="stat-card"><div class="stat-icon red">❌</div><div><div class="stat-value" id="cntCancelled">—</div><div class="stat-label">Cancelled</div></div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">All Orders</span>
      <select class="form-control" id="statusFilter" style="width:auto" onchange="renderOrders()">
        <option value="">All</option>
        <option value="Pending">Pending</option>
        <option value="Confirmed">Confirmed</option>
        <option value="Completed">Completed</option>
        <option value="Cancelled">Cancelled</option>
      </select>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Livestock</th><th>Buyer</th><th>Type</th><th>Qty</th><th>Total</th><th>Expiry</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody id="ordersBody"><tr><td colspan="10"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Record Payment Modal -->
<div class="modal-overlay" id="payModal">
  <div class="modal">
    <div class="modal-header"><span class="modal-title">Record Payment</span><button class="modal-close" onclick="closeModal('payModal')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="payOrderId">
      <p style="margin-bottom:14px;color:var(--text-muted);font-size:.88rem">Recording a payment will complete this order and mark the livestock as Sold.</p>
      <div class="form-group"><label class="form-label">Payment Method</label>
        <select class="form-control" id="payMethod">
          <option value="Cash">Cash</option>
          <option value="Bank Transfer">Bank Transfer</option>
          <option value="GCash">GCash</option>
          <option value="Maya">Maya</option>
          <option value="Credit Card">Credit Card</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Amount (₱)</label>
        <input type="number" step="0.01" class="form-control" id="payAmount" placeholder="Leave blank to use order total">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('payModal')">Cancel</button>
      <button class="btn btn-accent" onclick="recordPayment()">💰 Record Payment</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Farmer']);
let orders = [];

async function loadOrders() {
  const res = await Api.get('/orders');
  orders = res.data || [];
  document.getElementById('cntPending').textContent   = orders.filter(o=>o.status==='Pending').length;
  document.getElementById('cntConfirmed').textContent = orders.filter(o=>o.status==='Confirmed').length;
  document.getElementById('cntCompleted').textContent = orders.filter(o=>o.status==='Completed').length;
  document.getElementById('cntCancelled').textContent = orders.filter(o=>o.status==='Cancelled').length;
  renderOrders();
}

function renderOrders() {
  const body = document.getElementById('ordersBody');
  const sf = document.getElementById('statusFilter').value;
  const filtered = sf ? orders.filter(o=>o.status===sf) : orders;
  if (!filtered.length) { body.innerHTML = emptyRow(10); return; }
  body.innerHTML = filtered.map(o => `<tr>
    <td>#${o.order_id}</td>
    <td><strong>${fmt(o.tag_number)}</strong><br><small>${fmt(o.category_name)} ${fmt(o.breed_name,'')}</small></td>
    <td>${fmt(o.buyer_first_name)} ${fmt(o.buyer_last_name,'')}<br><small style="color:var(--text-muted)">${fmt(o.buyer_email)}</small></td>
    <td><span class="tag">${o.order_type}</span></td>
    <td>${o.quantity}</td>
    <td style="font-weight:700;color:var(--accent-dark)">${fmtMoney(o.total_price)}</td>
    <td style="font-size:.8rem;color:var(--text-muted)">${o.reservation_expiry ? fmtDate(o.reservation_expiry) : '—'}</td>
    <td>${statusBadge(o.status)}</td>
    <td>${fmtDate(o.created_at)}</td>
    <td><div class="table-actions">
      ${o.status==='Pending' ? `<button class="btn btn-sm btn-primary" onclick="updateStatus(${o.order_id},'Confirmed')">✅ Confirm</button>` : ''}
      ${o.status==='Confirmed' ? `<button class="btn btn-sm btn-accent" onclick="openPayment(${o.order_id},${o.total_price})">💰 Pay</button>` : ''}
      ${['Pending','Confirmed'].includes(o.status) ? `<button class="btn btn-sm btn-danger" onclick="updateStatus(${o.order_id},'Cancelled')">✕ Cancel</button>` : ''}
    </div></td>
  </tr>`).join('');
}

async function updateStatus(id, status) {
  const res = await Api.patch(`/orders/${id}/status`, { status });
  if (res.ok) { toast(`Order ${status.toLowerCase()}!`); loadOrders(); }
  else toast(res.message || 'Failed.', 'error');
}

function openPayment(id, amount) {
  document.getElementById('payOrderId').value = id;
  document.getElementById('payAmount').value = '';
  openModal('payModal');
}

async function recordPayment() {
  const id = document.getElementById('payOrderId').value;
  const body = {
    order_id:       id,
    payment_method: document.getElementById('payMethod').value,
    payment_status: 'Paid',
  };
  const amt = document.getElementById('payAmount').value;
  if (amt) body.total_amount = amt;

  const res = await Api.post('/transactions', body);
  if (res.ok) { toast('Payment recorded! Order completed. 🎉'); closeModal('payModal'); loadOrders(); }
  else toast(res.message || 'Failed.', 'error');
}

loadOrders();
</script>
</body>
</html>
