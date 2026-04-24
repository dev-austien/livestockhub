<?php $navRole = 'farmer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Farmer Dashboard — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div>
      <div class="page-title" id="farmGreeting">My Farm Dashboard</div>
      <div class="page-subtitle">Track your livestock and sales</div>
    </div>
    <a href="mylivestock.php" class="btn btn-primary">+ Add Livestock</a>
  </div>

  <div class="stats-grid" id="statsGrid">
    <div class="stat-card"><div class="stat-icon green">🐄</div><div><div class="stat-value" id="statTotal">—</div><div class="stat-label">Total Livestock</div></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-value" id="statAvail">—</div><div class="stat-label">Available</div></div></div>
    <div class="stat-card"><div class="stat-icon amber">📦</div><div><div class="stat-value" id="statOrders">—</div><div class="stat-label">Pending Orders</div></div></div>
    <div class="stat-card"><div class="stat-icon teal">💰</div><div><div class="stat-value" id="statRev">—</div><div class="stat-label">Total Earned</div></div></div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <div class="card">
      <div class="card-header"><span class="card-title">🐄 My Livestock</span><a href="mylivestock.php" class="btn btn-sm btn-secondary">Manage</a></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Tag</th><th>Type</th><th>Weight</th><th>Price</th><th>Status</th></tr></thead>
          <tbody id="myLvBody"><tr><td colspan="5"><div class="spinner"></div></td></tr></tbody>
        </table>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><span class="card-title">📦 Recent Orders</span><a href="orderReceived.php" class="btn btn-sm btn-secondary">View all</a></div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Order</th><th>Buyer</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody id="myOrdersBody"><tr><td colspan="4"><div class="spinner"></div></td></tr></tbody>
        </table>
      </div>
    </div>
  </div>
</div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
const authUser = requireAuth(['Farmer']);

async function load() {
  const name = `${authUser.user_first_name || ''} ${authUser.user_last_name || ''}`.trim();
  document.getElementById('farmGreeting').textContent = `Welcome, ${name || 'Farmer'} 👋`;

  const [lvRes, ordRes, txRes] = await Promise.all([
    Api.get('/livestock'), Api.get('/orders'), Api.get('/transactions')
  ]);

  const lv = lvRes.data || [];
  document.getElementById('statTotal').textContent = lv.length;
  document.getElementById('statAvail').textContent = lv.filter(l=>l.sale_status==='Available').length;

  const orders = ordRes.data || [];
  document.getElementById('statOrders').textContent = orders.filter(o=>o.status==='Pending').length;

  const rev = (txRes.data||[]).filter(t=>t.payment_status==='Paid').reduce((s,t)=>s+parseFloat(t.total_amount||0),0);
  document.getElementById('statRev').textContent = fmtMoney(rev);

  // My livestock table
  const lvBody = document.getElementById('myLvBody');
  if (lv.length) {
    lvBody.innerHTML = lv.slice(0,6).map(l=>`<tr>
      <td><strong>${l.tag_number}</strong></td>
      <td><span class="tag">${categoryEmoji(l.category_name)} ${fmt(l.category_name)}</span></td>
      <td>${fmtWeight(l.current_weight)}</td>
      <td>${fmtMoney(l.price)}</td>
      <td>${statusBadge(l.sale_status)}</td>
    </tr>`).join('');
  } else lvBody.innerHTML = emptyRow(5, 'No livestock yet.');

  // My orders table
  const obody = document.getElementById('myOrdersBody');
  if (orders.length) {
    obody.innerHTML = orders.slice(0,5).map(o=>`<tr>
      <td>#${o.order_id}</td>
      <td>${fmt(o.buyer_first_name)} ${fmt(o.buyer_last_name,'')}</td>
      <td>${fmtMoney(o.total_price)}</td>
      <td>${statusBadge(o.status)}</td>
    </tr>`).join('');
  } else obody.innerHTML = emptyRow(4, 'No orders yet.');
}

load();
</script>
</body>
</html>
