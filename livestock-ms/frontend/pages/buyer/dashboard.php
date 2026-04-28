<?php $navRole = 'buyer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buyer Dashboard — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div>
      <div class="page-title" id="greeting">Welcome back 👋</div>
      <div class="page-subtitle">Find and purchase quality livestock</div>
    </div>
    <a href="marketplace.php" class="btn btn-primary">🛒 Browse Marketplace</a>
  </div>

  <!-- Stats -->
  <div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card"><div class="stat-icon amber">📋</div><div><div class="stat-value" id="statOrders">—</div><div class="stat-label">My Orders</div></div></div>
    <div class="stat-card"><div class="stat-icon green">✅</div><div><div class="stat-value" id="statCompleted">—</div><div class="stat-label">Completed</div></div></div>
    <div class="stat-card"><div class="stat-icon teal">💰</div><div><div class="stat-value" id="statSpent">—</div><div class="stat-label">Total Spent</div></div></div>
    <div class="stat-card"><div class="stat-icon green">🐄</div><div><div class="stat-value" id="statAvail">—</div><div class="stat-label">Available Livestock</div></div></div>
  </div>

  <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
    <!-- My Recent Orders -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">📋 My Recent Orders</span>
        <a href="myorders.php" class="btn btn-sm btn-secondary">View all</a>
      </div>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Livestock</th><th>Type</th><th>Amount</th><th>Status</th></tr></thead>
          <tbody id="myOrdersBody"><tr><td colspan="4"><div class="spinner"></div></td></tr></tbody>
        </table>
      </div>
    </div>

    <!-- Featured Livestock -->
    <div class="card">
      <div class="card-header">
        <span class="card-title">🐄 Featured Listings</span>
        <a href="marketplace.php" class="btn btn-sm btn-secondary">See all</a>
      </div>
      <div class="card-body" id="featuredBody">
        <div class="spinner"></div>
      </div>
    </div>
  </div>
</div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
const authUser = requireAuth(['Buyer']);

async function load() {
  const name = `${authUser.user_first_name || ''} ${authUser.user_last_name || ''}`.trim();
  document.getElementById('greeting').textContent = `Welcome, ${name || 'Buyer'} 👋`;

  const [ordRes, lvRes, txRes] = await Promise.all([
    Api.get('/orders'), Api.get('/livestock?sale_status=Available'), Api.get('/transactions')
  ]);

  const orders = ordRes.data || [];
  const lv     = lvRes.data  || [];
  const txs    = txRes.data  || [];

  document.getElementById('statOrders').textContent    = orders.length;
  document.getElementById('statCompleted').textContent = orders.filter(o => o.status === 'Completed').length;
  document.getElementById('statAvail').textContent     = lv.length;

  const spent = txs.filter(t => t.payment_status === 'Paid').reduce((s, t) => s + parseFloat(t.total_amount || 0), 0);
  document.getElementById('statSpent').textContent = fmtMoney(spent);

  // My orders
  const ob = document.getElementById('myOrdersBody');
  if (orders.length) {
    ob.innerHTML = orders.slice(0, 5).map(o => `<tr>
      <td><strong>${fmt(o.tag_number)}</strong><br><small style="color:var(--text-muted)">${fmt(o.category_name)}</small></td>
      <td><span class="tag">${o.order_type}</span></td>
      <td style="font-weight:600">${fmtMoney(o.total_price)}</td>
      <td>${statusBadge(o.status)}</td>
    </tr>`).join('');
  } else ob.innerHTML = emptyRow(4, 'No orders yet.');

  // Featured listings
  const fb = document.getElementById('featuredBody');
  if (lv.length) {
    fb.innerHTML = lv.slice(0, 4).map(l => `
      <div style="display:flex;align-items:center;gap:12px;padding:10px 0;border-bottom:1px solid var(--border);">
        <div style="font-size:2rem;width:44px;text-align:center">${categoryEmoji(l.category_name)}</div>
        <div style="flex:1">
          <div style="font-weight:600;font-size:.92rem">${l.tag_number}</div>
          <div style="font-size:.78rem;color:var(--text-muted)">${fmt(l.category_name)} · ${fmt(l.breed_name)} · ${fmt(l.farm_name)}</div>
        </div>
        <div style="text-align:right">
          <div style="font-weight:700;color:var(--accent-dark)">${fmtMoney(l.price)}</div>
          <a href="marketplace.php" class="btn btn-sm btn-primary" style="margin-top:4px">Buy</a>
        </div>
      </div>`).join('');
  } else fb.innerHTML = '<div class="empty-state"><div class="empty-icon">🌿</div><h3>No listings yet</h3></div>';
}

load();
</script>
</body>
</html>
