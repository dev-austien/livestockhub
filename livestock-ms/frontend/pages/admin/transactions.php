<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Transactions — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">Transactions</div><div class="page-subtitle">All payment records</div></div>
    <button class="btn btn-primary" onclick="openAdd()">+ Record Payment</button>
  </div>

  <!-- Summary Cards -->
  <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:22px;">
    <div class="stat-card"><div class="stat-icon green">💰</div><div><div class="stat-value" id="totalRev">—</div><div class="stat-label">Total Revenue</div></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-value" id="totalPaid">—</div><div class="stat-label">Paid</div></div></div>
    <div class="stat-card"><div class="stat-icon amber">⏳</div><div><div class="stat-value" id="totalPending">—</div><div class="stat-label">Pending</div></div></div>
    <div class="stat-card"><div class="stat-icon red">↩️</div><div><div class="stat-value" id="totalRefunded">—</div><div class="stat-label">Refunded</div></div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">All Transactions</span>
      <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search…" oninput="filterTable('searchInput','txBody')"></div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Order</th><th>Livestock</th><th>Buyer</th><th>Method</th><th>Amount</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody id="txBody"><tr><td colspan="9"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="txModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="txModalTitle">Record Payment</span>
      <button class="modal-close" onclick="closeModal('txModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="txId">
      <div class="form-group"><label class="form-label">Order ID *</label><input type="number" class="form-control" id="txOrderId" placeholder="Enter order ID"></div>
      <div class="form-group"><label class="form-label">Payment Method *</label>
        <select class="form-control" id="txMethod">
          <option value="Cash">Cash</option>
          <option value="Bank Transfer">Bank Transfer</option>
          <option value="GCash">GCash</option>
          <option value="Maya">Maya</option>
          <option value="Credit Card">Credit Card</option>
          <option value="Other">Other</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Payment Status *</label>
        <select class="form-control" id="txStatus">
          <option value="Paid">Paid</option>
          <option value="Pending">Pending</option>
          <option value="Failed">Failed</option>
          <option value="Refunded">Refunded</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Total Amount (₱)</label><input type="number" step="0.01" class="form-control" id="txAmount" placeholder="Leave blank to use order total"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('txModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveTx()">Save</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Admin']);
let txs = [];

async function loadTx() {
  const body = document.getElementById('txBody');
  body.innerHTML = loadingRow(9);
  const res = await Api.get('/transactions');
  txs = res.data || [];

  // Summaries
  const paid = txs.filter(t=>t.payment_status==='Paid').reduce((s,t)=>s+parseFloat(t.total_amount||0),0);
  const pending = txs.filter(t=>t.payment_status==='Pending').reduce((s,t)=>s+parseFloat(t.total_amount||0),0);
  const refunded = txs.filter(t=>t.payment_status==='Refunded').reduce((s,t)=>s+parseFloat(t.total_amount||0),0);
  document.getElementById('totalRev').textContent     = fmtMoney(paid);
  document.getElementById('totalPaid').textContent    = fmtMoney(paid);
  document.getElementById('totalPending').textContent = fmtMoney(pending);
  document.getElementById('totalRefunded').textContent= fmtMoney(refunded);

  if (!txs.length) { body.innerHTML = emptyRow(9); return; }
  body.innerHTML = txs.map(t => `<tr>
    <td>#${t.transaction_id}</td>
    <td><a href="orders.php" style="color:var(--green-700);font-weight:600">#${t.order_id}</a></td>
    <td>${fmt(t.tag_number)} <span class="tag">${fmt(t.category_name)}</span></td>
    <td>${fmt(t.buyer_first_name)} ${fmt(t.buyer_last_name,'')}</td>
    <td><span class="tag">${t.payment_method}</span></td>
    <td style="font-weight:700;color:var(--accent-dark)">${fmtMoney(t.total_amount)}</td>
    <td>${statusBadge(t.payment_status)}</td>
    <td>${fmtDate(t.transaction_date)}</td>
    <td><div class="table-actions">
      <button class="btn btn-sm btn-secondary" onclick="editTx(${t.transaction_id})">✏️</button>
      <button class="btn btn-sm btn-danger" onclick="deleteTx(${t.transaction_id})">🗑️</button>
    </div></td>
  </tr>`).join('');
}

function openAdd() {
  document.getElementById('txId').value = '';
  document.getElementById('txOrderId').value = '';
  document.getElementById('txOrderId').disabled = false;
  document.getElementById('txAmount').value = '';
  document.getElementById('txModalTitle').textContent = 'Record Payment';
  openModal('txModal');
}

function editTx(id) {
  const t = txs.find(x => x.transaction_id == id);
  document.getElementById('txId').value       = t.transaction_id;
  document.getElementById('txOrderId').value  = t.order_id;
  document.getElementById('txOrderId').disabled = true;
  document.getElementById('txMethod').value   = t.payment_method;
  document.getElementById('txStatus').value   = t.payment_status;
  document.getElementById('txAmount').value   = t.total_amount;
  document.getElementById('txModalTitle').textContent = 'Edit Transaction';
  openModal('txModal');
}

async function saveTx() {
  const id = document.getElementById('txId').value;
  const body = {
    order_id:       document.getElementById('txOrderId').value,
    payment_method: document.getElementById('txMethod').value,
    payment_status: document.getElementById('txStatus').value,
    total_amount:   document.getElementById('txAmount').value || undefined,
  };
  const res = id ? await Api.put(`/transactions/${id}`, body) : await Api.post('/transactions', body);
  if (res.ok) { toast(id ? 'Updated!' : 'Payment recorded!'); closeModal('txModal'); loadTx(); }
  else toast(res.message || 'Failed.', 'error');
}

function deleteTx(id) {
  confirmDelete(`Delete transaction #${id}?`, async () => {
    const res = await Api.del(`/transactions/${id}`);
    if (res.ok) { toast('Transaction deleted.'); loadTx(); }
    else toast(res.message || 'Failed.', 'error');
  });
}

loadTx();
</script>
</body>
</html>
