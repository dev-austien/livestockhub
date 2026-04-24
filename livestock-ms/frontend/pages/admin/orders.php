<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Orders — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">Orders</div><div class="page-subtitle">Manage all orders in the system</div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">All Orders</span>
      <div class="toolbar">
        <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search orders…" oninput="filterTable('searchInput','ordersBody')"></div>
        <select class="form-control" id="statusFilter" style="width:auto" onchange="loadOrders()">
          <option value="">All Status</option>
          <option value="Pending">Pending</option>
          <option value="Confirmed">Confirmed</option>
          <option value="Completed">Completed</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Livestock</th><th>Buyer</th><th>Farmer/Farm</th><th>Type</th><th>Qty</th><th>Total</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody id="ordersBody"><tr><td colspan="10"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Update Status Modal -->
<div class="modal-overlay" id="statusModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <span class="modal-title">Update Order Status</span>
      <button class="modal-close" onclick="closeModal('statusModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="statusOrderId">
      <div class="form-group"><label class="form-label">Current Status</label><p id="currentStatusDisplay"></p></div>
      <div class="form-group"><label class="form-label">New Status</label>
        <select class="form-control" id="newStatus">
          <option value="Pending">Pending</option>
          <option value="Confirmed">Confirmed</option>
          <option value="Completed">Completed</option>
          <option value="Cancelled">Cancelled</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('statusModal')">Cancel</button>
      <button class="btn btn-primary" onclick="updateStatus()">Update</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Admin']);
let orders = [];

async function loadOrders() {
  const body = document.getElementById('ordersBody');
  body.innerHTML = loadingRow(10);
  const res = await Api.get('/orders');
  const sf = document.getElementById('statusFilter').value;
  orders = (res.data || []).filter(o => !sf || o.status === sf);

  if (!orders.length) { body.innerHTML = emptyRow(10); return; }
  body.innerHTML = orders.map(o => `<tr>
    <td>#${o.order_id}</td>
    <td><strong>${fmt(o.tag_number)}</strong><br><small style="color:var(--text-muted)">${fmt(o.category_name)}</small></td>
    <td>${fmt(o.buyer_first_name)} ${fmt(o.buyer_last_name,'')}<br><small style="color:var(--text-muted)">${fmt(o.buyer_email)}</small></td>
    <td>${fmt(o.farm_name)}<br><small style="color:var(--text-muted)">${fmt(o.farmer_first_name)} ${fmt(o.farmer_last_name,'')}</small></td>
    <td><span class="tag">${o.order_type}</span></td>
    <td>${o.quantity}</td>
    <td style="font-weight:700;color:var(--accent-dark)">${fmtMoney(o.total_price)}</td>
    <td>${statusBadge(o.status)}</td>
    <td>${fmtDate(o.created_at)}</td>
    <td><div class="table-actions">
      <button class="btn btn-sm btn-secondary" onclick="openStatusModal(${o.order_id},'${o.status}')">🔄 Status</button>
      <button class="btn btn-sm btn-danger" onclick="deleteOrder(${o.order_id})">🗑️</button>
    </div></td>
  </tr>`).join('');
}

function openStatusModal(id, current) {
  document.getElementById('statusOrderId').value = id;
  document.getElementById('currentStatusDisplay').innerHTML = statusBadge(current);
  document.getElementById('newStatus').value = current;
  openModal('statusModal');
}

async function updateStatus() {
  const id = document.getElementById('statusOrderId').value;
  const status = document.getElementById('newStatus').value;
  const res = await Api.patch(`/orders/${id}/status`, { status });
  if (res.ok) { toast('Order status updated!'); closeModal('statusModal'); loadOrders(); }
  else toast(res.message || 'Failed.', 'error');
}

function deleteOrder(id) {
  confirmDelete(`Delete order #${id}?`, async () => {
    const res = await Api.del(`/orders/${id}`);
    if (res.ok) { toast('Order deleted.'); loadOrders(); }
    else toast(res.message || 'Delete failed.', 'error');
  });
}

loadOrders();
</script>
</body>
</html>
