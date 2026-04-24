<?php $navRole = 'farmer'; ?>
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
    <div><div class="page-title">💰 Transactions</div><div class="page-subtitle">Your sales payment records</div></div>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));margin-bottom:22px;">
    <div class="stat-card"><div class="stat-icon green">💰</div><div><div class="stat-value" id="totalEarned">—</div><div class="stat-label">Total Earned</div></div></div>
    <div class="stat-card"><div class="stat-icon amber">⏳</div><div><div class="stat-value" id="totalPending">—</div><div class="stat-label">Pending</div></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-value" id="countPaid">—</div><div class="stat-label">Paid Transactions</div></div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Payment History</span>
      <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search…" oninput="filterTable('searchInput','txBody')"></div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Order</th><th>Livestock</th><th>Buyer</th><th>Method</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody id="txBody"><tr><td colspan="8"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Farmer']);

async function load() {
  const res = await Api.get('/transactions');
  const txs = res.data || [];

  const paid    = txs.filter(t => t.payment_status === 'Paid');
  const pending = txs.filter(t => t.payment_status === 'Pending');
  const earned  = paid.reduce((s, t) => s + parseFloat(t.total_amount || 0), 0);
  const pend    = pending.reduce((s, t) => s + parseFloat(t.total_amount || 0), 0);

  document.getElementById('totalEarned').textContent  = fmtMoney(earned);
  document.getElementById('totalPending').textContent = fmtMoney(pend);
  document.getElementById('countPaid').textContent    = paid.length;

  const body = document.getElementById('txBody');
  if (!txs.length) { body.innerHTML = emptyRow(8, 'No transactions yet.'); return; }

  body.innerHTML = txs.map(t => `<tr>
    <td>#${t.transaction_id}</td>
    <td><a style="color:var(--green-700);font-weight:600">#${t.order_id}</a></td>
    <td><strong>${fmt(t.tag_number)}</strong> <span class="tag">${fmt(t.category_name)}</span></td>
    <td>${fmt(t.buyer_first_name)} ${fmt(t.buyer_last_name, '')}</td>
    <td><span class="tag">${t.payment_method}</span></td>
    <td style="font-weight:700;color:var(--accent-dark)">${fmtMoney(t.total_amount)}</td>
    <td>${statusBadge(t.payment_status)}</td>
    <td>${fmtDate(t.transaction_date)}</td>
  </tr>`).join('');
}

load();
</script>
</body>
</html>
