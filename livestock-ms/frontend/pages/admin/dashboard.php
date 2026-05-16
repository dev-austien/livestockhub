<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — LivestockHub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
  <div class="main-content" id="mainContent">
    <div class="page-header">
      <div>
        <div class="page-title">Dashboard</div>
        <div class="page-subtitle">System overview and statistics</div>
      </div>
    </div>

    <!-- Stats -->
    <div class="stats-grid" id="statsGrid">
      <div class="stat-card"><div class="stat-icon green">👥</div><div><div class="stat-value" id="statUsers">—</div><div class="stat-label">Total Users</div></div></div>
      <div class="stat-card"><div class="stat-icon green">🐄</div><div><div class="stat-value" id="statLivestock">—</div><div class="stat-label">Total Livestock</div></div></div>
      <div class="stat-card"><div class="stat-icon amber">📋</div><div><div class="stat-value" id="statOrders">—</div><div class="stat-label">Active Orders</div></div></div>
      <div class="stat-card"><div class="stat-icon teal">💰</div><div><div class="stat-value" id="statRevenue">—</div><div class="stat-label">Total Revenue</div></div></div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;flex-wrap:wrap;">
      <!-- Recent Orders -->
      <div class="card" style="grid-column:1/-1;">
        <div class="card-header">
          <span class="card-title">Recent Orders</span>
          <a href="orders.php" class="btn btn-sm btn-secondary">View all</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Order ID</th><th>Livestock</th><th>Buyer</th><th>Type</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody id="recentOrdersBody"><tr><td colspan="7"><div class="spinner"></div></td></tr></tbody>
          </table>
        </div>
      </div>

      <!-- Available Livestock -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">🐄 Livestock by Status</span>
        </div>
        <div class="card-body" id="livestockStatus">
          <div class="spinner"></div>
        </div>
      </div>

      <!-- Recent Users -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">👥 Recent Users</span>
          <a href="users.php" class="btn btn-sm btn-secondary">View all</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Name</th><th>Role</th><th>Status</th></tr></thead>
            <tbody id="recentUsersBody"><tr><td colspan="3"><div class="spinner"></div></td></tr></tbody>
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
const user = requireAuth(['Admin']);

async function loadDashboard() {
  const [usersRes, livestockRes, ordersRes, txRes] = await Promise.all([
    Api.get('/users'), Api.get('/livestock'), Api.get('/orders'), Api.get('/transactions')
  ]);

  // Stats
  if (usersRes.ok)    document.getElementById('statUsers').textContent    = usersRes.data?.length || 0;
  if (livestockRes.ok) document.getElementById('statLivestock').textContent = livestockRes.data?.length || 0;
  if (ordersRes.ok) {
    const active = (ordersRes.data||[]).filter(o => ['Pending','Confirmed'].includes(o.status));
    document.getElementById('statOrders').textContent = active.length;
  }
  if (txRes.ok) {
    const rev = (txRes.data||[]).filter(t=>t.payment_status==='Paid').reduce((s,t)=>s+parseFloat(t.total_amount||0),0);
    document.getElementById('statRevenue').textContent = fmtMoney(rev);
  }

  // Recent orders
  const ob = document.getElementById('recentOrdersBody');
  if (ordersRes.ok && ordersRes.data?.length) {
    ob.innerHTML = ordersRes.data.slice(0,6).map(o => `<tr>
      <td>#${o.order_id}</td>
      <td>${fmt(o.tag_number)} <small style="color:var(--text-muted)">${fmt(o.category_name)}</small></td>
      <td>${fmt(o.buyer_first_name)} ${fmt(o.buyer_last_name,'')}</td>
      <td><span class="tag">${o.order_type}</span></td>
      <td>${fmtMoney(o.total_price)}</td>
      <td>${statusBadge(o.status)}</td>
      <td>${fmtDate(o.created_at)}</td>
    </tr>`).join('');
  } else { ob.innerHTML = emptyRow(7); }

  // Livestock by status
  if (livestockRes.ok) {
    const all = livestockRes.data || [];
    const byStatus = { Available:0, Reserved:0, Sold:0 };
    all.forEach(l => { byStatus[l.sale_status] = (byStatus[l.sale_status]||0)+1; });
    document.getElementById('livestockStatus').innerHTML = Object.entries(byStatus).map(([s,n]) => `
      <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border);">
        <span>${statusBadge(s)}</span>
        <strong style="font-size:1.3rem;color:var(--green-900)">${n}</strong>
      </div>`).join('') || '<p style="color:var(--text-muted)">No livestock found.</p>';
  }

  // Recent users
  const ub = document.getElementById('recentUsersBody');
  if (usersRes.ok && usersRes.data?.length) {
    ub.innerHTML = usersRes.data.slice(0,6).map(u => `<tr>
      <td>${fmt(u.user_first_name)} ${fmt(u.user_last_name,'')}</td>
      <td>${statusBadge(u.user_role)}</td>
      <td>${statusBadge(u.user_status)}</td>
    </tr>`).join('');
  } else { ub.innerHTML = emptyRow(3); }
}

loadDashboard();
</script>
</body>
</html>
