<?php $navRole = 'buyer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payment History — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">💳 Payment History</div><div class="page-subtitle">All your past transactions</div></div>
  </div>

  <div class="stats-grid" style="grid-template-columns:repeat(auto-fit,minmax(170px,1fr));margin-bottom:22px;">
    <div class="stat-card"><div class="stat-icon teal">💰</div><div><div class="stat-value" id="totalSpent">—</div><div class="stat-label">Total Spent</div></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-value" id="countPaid">—</div><div class="stat-label">Paid</div></div></div>
    <div class="stat-card"><div class="stat-icon amber">⏳</div><div><div class="stat-value" id="countPending">—</div><div class="stat-label">Pending</div></div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Transactions</span>
      <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search…" oninput="filterTable('searchInput','txBody')"></div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Livestock</th><th>Farm</th><th>Payment Method</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
        <tbody id="txBody"><tr><td colspan="7"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Buyer']);

async function load() {
  const res = await Api.get('/transactions');
  const txs = res.data || [];

  const paid    = txs.filter(t => t.payment_status === 'Paid');
  const pending = txs.filter(t => t.payment_status === 'Pending');
  const spent   = paid.reduce((s, t) => s + parseFloat(t.total_amount || 0), 0);

  document.getElementById('totalSpent').textContent  = fmtMoney(spent);
  document.getElementById('countPaid').textContent    = paid.length;
  document.getElementById('countPending').textContent = pending.length;

  const body = document.getElementById('txBody');
  if (!txs.length) { body.innerHTML = emptyRow(7, 'No payment records yet.'); return; }

  body.innerHTML = txs.map(t => `<tr>
    <td>#${t.transaction_id}</td>
    <td>
      <div style="display:flex;align-items:center;gap:10px">
        <span style="font-size:1.4rem">${categoryEmoji(t.category_name)}</span>
        <div>
          <div style="font-weight:600">${fmt(t.tag_number)}</div>
          <div style="font-size:.78rem;color:var(--text-muted)">${fmt(t.category_name)} · ${fmt(t.breed_name)}</div>
        </div>
      </div>
    </td>
    <td style="font-size:.85rem">${fmt(t.farm_name)}</td>
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
