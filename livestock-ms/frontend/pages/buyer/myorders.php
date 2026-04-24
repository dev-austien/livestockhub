<?php $navRole = 'buyer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Orders — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">📋 My Orders</div><div class="page-subtitle">Track all your livestock orders</div></div>
    <a href="marketplace.php" class="btn btn-primary">🛒 Browse More</a>
  </div>

  <!-- Filter Tabs -->
  <div style="display:flex;gap:8px;margin-bottom:18px;flex-wrap:wrap;">
    <button class="btn btn-primary btn-sm tab-btn active" data-status="" onclick="setTab(this,'')">All</button>
    <button class="btn btn-secondary btn-sm tab-btn" data-status="Pending" onclick="setTab(this,'Pending')">⏳ Pending</button>
    <button class="btn btn-secondary btn-sm tab-btn" data-status="Confirmed" onclick="setTab(this,'Confirmed')">✅ Confirmed</button>
    <button class="btn btn-secondary btn-sm tab-btn" data-status="Completed" onclick="setTab(this,'Completed')">🎉 Completed</button>
    <button class="btn btn-secondary btn-sm tab-btn" data-status="Cancelled" onclick="setTab(this,'Cancelled')">❌ Cancelled</button>
  </div>

  <div id="ordersList">
    <div class="card"><div class="card-body" style="text-align:center"><div class="spinner"></div></div></div>
  </div>
</div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Buyer']);
let allOrders = [], activeStatus = '';

function setTab(el, status) {
  document.querySelectorAll('.tab-btn').forEach(b => {
    b.classList.remove('btn-primary');
    b.classList.add('btn-secondary');
  });
  el.classList.add('btn-primary');
  el.classList.remove('btn-secondary');
  activeStatus = status;
  renderOrders();
}

async function load() {
  const res = await Api.get('/orders');
  allOrders = res.data || [];
  renderOrders();
}

function renderOrders() {
  const filtered = activeStatus ? allOrders.filter(o => o.status === activeStatus) : allOrders;
  const el = document.getElementById('ordersList');

  if (!filtered.length) {
    el.innerHTML = `<div class="card"><div class="card-body"><div class="empty-state">
      <div class="empty-icon">📋</div>
      <h3>No orders found</h3>
      <p>You don't have any ${activeStatus.toLowerCase() || ''} orders yet.</p>
      <a href="marketplace.php" class="btn btn-primary" style="margin-top:14px">Browse Marketplace</a>
    </div></div></div>`;
    return;
  }

  el.innerHTML = filtered.map(o => `
    <div class="card" style="margin-bottom:14px;">
      <div style="padding:16px 20px;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <div style="font-size:2.2rem">${categoryEmoji(o.category_name)}</div>
        <div style="flex:1;min-width:180px">
          <div style="font-weight:700;font-size:1rem">${fmt(o.tag_number)} <span style="font-weight:400;color:var(--text-muted)">— ${fmt(o.category_name)}</span></div>
          <div style="font-size:.82rem;color:var(--text-muted);margin-top:2px">
            ${fmt(o.breed_name)} &nbsp;·&nbsp; 🏡 ${fmt(o.farm_name)} &nbsp;·&nbsp; Farmer: ${fmt(o.farmer_first_name)} ${fmt(o.farmer_last_name, '')}
          </div>
          <div style="margin-top:6px;display:flex;gap:8px;flex-wrap:wrap;align-items:center">
            <span class="tag">${o.order_type}</span>
            ${statusBadge(o.status)}
            <span style="font-size:.78rem;color:var(--text-muted)">Ordered ${fmtDate(o.created_at)}</span>
            ${o.reservation_expiry ? `<span style="font-size:.78rem;color:var(--warning)">Expires ${fmtDate(o.reservation_expiry)}</span>` : ''}
          </div>
        </div>
        <div style="text-align:right;min-width:120px">
          <div style="font-size:1.2rem;font-weight:800;color:var(--accent-dark)">${fmtMoney(o.total_price)}</div>
          <div style="font-size:.78rem;color:var(--text-muted)">Qty: ${o.quantity}</div>
          ${o.status === 'Pending' ? `
          <button class="btn btn-sm btn-danger" style="margin-top:8px" onclick="cancelOrder(${o.order_id})">✕ Cancel</button>` : ''}
        </div>
      </div>
      ${o.status === 'Completed' ? `<div style="background:var(--green-50);padding:10px 20px;font-size:.83rem;color:var(--green-700);display:flex;align-items:center;gap:8px;">
        <span>🎉</span> Order completed! Payment has been processed.
      </div>` : ''}
      ${o.status === 'Confirmed' ? `<div style="background:#EFF6FF;padding:10px 20px;font-size:.83rem;color:#1d4ed8;display:flex;align-items:center;gap:8px;">
        <span>✅</span> Farmer confirmed your order. Please coordinate payment with the farmer.
      </div>` : ''}
    </div>`).join('');
}

async function cancelOrder(id) {
  confirmDelete('Cancel this order? This cannot be undone.', async () => {
    const res = await Api.patch(`/orders/${id}/status`, { status: 'Cancelled' });
    if (res.ok) { toast('Order cancelled.'); load(); }
    else toast(res.message || 'Failed to cancel.', 'error');
  });
}

load();
</script>
</body>
</html>
