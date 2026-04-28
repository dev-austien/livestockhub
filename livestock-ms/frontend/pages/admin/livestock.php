<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Livestock — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">All Livestock</div><div class="page-subtitle">Manage livestock across all farmers</div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Livestock Registry</span>
      <div class="toolbar">
        <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search tag, desc…" oninput="filterTable('searchInput','lvBody')"></div>
        <select class="form-control" id="catFilter" style="width:auto" onchange="loadLivestock()">
          <option value="">All Categories</option>
        </select>
        <select class="form-control" id="statusFilter" style="width:auto" onchange="loadLivestock()">
          <option value="">All Status</option>
          <option value="Available">Available</option>
          <option value="Reserved">Reserved</option>
          <option value="Sold">Sold</option>
        </select>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tag #</th><th>Category</th><th>Breed</th><th>Gender</th><th>Weight</th><th>Price</th><th>Farm</th><th>Status</th><th>Health</th><th>Actions</th></tr></thead>
        <tbody id="lvBody"><tr><td colspan="10"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- View Modal -->
<div class="modal-overlay" id="viewModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title" id="viewTitle">Livestock Details</span>
      <button class="modal-close" onclick="closeModal('viewModal')">✕</button>
    </div>
    <div class="modal-body" id="viewBody"></div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title">Edit Livestock</span>
      <button class="modal-close" onclick="closeModal('editModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editId">
      <div class="form-grid cols-2">
        <div class="form-group"><label class="form-label">Tag Number</label><input class="form-control" id="editTag"></div>
        <div class="form-group"><label class="form-label">Gender</label>
          <select class="form-control" id="editGender"><option value="Male">Male</option><option value="Female">Female</option></select>
        </div>
        <div class="form-group"><label class="form-label">Health Status</label><input class="form-control" id="editHealth"></div>
        <div class="form-group"><label class="form-label">Sale Status</label>
          <select class="form-control" id="editSaleStatus">
            <option value="Available">Available</option>
            <option value="Reserved">Reserved</option>
            <option value="Sold">Sold</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Price (₱)</label><input type="number" class="form-control" id="editPrice"></div>
        <div class="form-group"><label class="form-label">Current Weight (kg)</label><input type="number" step="0.1" class="form-control" id="editWeight"></div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" id="editDesc" rows="3"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveLivestock()">Save Changes</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Admin']);
let livestock = [];

async function init() {
  const catRes = await Api.get('/categories');
  const cats = catRes.data || [];
  document.getElementById('catFilter').innerHTML += cats.map(c => `<option value="${c.category_id}">${c.category_name}</option>`).join('');
  loadLivestock();
}

async function loadLivestock() {
  const body = document.getElementById('lvBody');
  body.innerHTML = loadingRow(10);
  const cat    = document.getElementById('catFilter').value;
  const status = document.getElementById('statusFilter').value;
  let ep = '/livestock';
  const params = [];
  if (cat)    params.push(`category_id=${cat}`);
  if (status) params.push(`sale_status=${status}`);
  if (params.length) ep += '?' + params.join('&');

  const res = await Api.get(ep);
  livestock = res.data || [];
  if (!livestock.length) { body.innerHTML = emptyRow(10); return; }
  body.innerHTML = livestock.map(l => `<tr>
    <td><strong>${l.tag_number}</strong></td>
    <td><span class="tag">${categoryEmoji(l.category_name)} ${fmt(l.category_name)}</span></td>
    <td>${fmt(l.breed_name)}</td>
    <td>${statusBadge(l.gender)}</td>
    <td>${fmtWeight(l.current_weight)}</td>
    <td>${fmtMoney(l.price)}</td>
    <td style="font-size:.83rem">${fmt(l.farm_name)}</td>
    <td>${statusBadge(l.sale_status)}</td>
    <td><span class="tag">${fmt(l.health_status)}</span></td>
    <td><div class="table-actions">
      <button class="btn btn-sm btn-secondary" onclick="viewLivestock(${l.livestock_id})">👁</button>
      <button class="btn btn-sm btn-secondary" onclick="editLivestock(${l.livestock_id})">✏️</button>
      <button class="btn btn-sm btn-danger" onclick="deleteLivestock(${l.livestock_id},'${l.tag_number}')">🗑️</button>
    </div></td>
  </tr>`).join('');
}

function viewLivestock(id) {
  const l = livestock.find(x => x.livestock_id == id);
  document.getElementById('viewTitle').textContent = `Livestock #${l.tag_number}`;
  document.getElementById('viewBody').innerHTML = `
    <div class="form-grid cols-2">
      <div><label class="form-label">Tag Number</label><p><strong>${l.tag_number}</strong></p></div>
      <div><label class="form-label">Category / Breed</label><p>${fmt(l.category_name)} / ${fmt(l.breed_name)}</p></div>
      <div><label class="form-label">Gender</label><p>${statusBadge(l.gender)}</p></div>
      <div><label class="form-label">Health Status</label><p><span class="tag">${fmt(l.health_status)}</span></p></div>
      <div><label class="form-label">Sale Status</label><p>${statusBadge(l.sale_status)}</p></div>
      <div><label class="form-label">Price</label><p style="font-size:1.1rem;font-weight:700;color:var(--accent-dark)">${fmtMoney(l.price)}</p></div>
      <div><label class="form-label">Current Weight</label><p>${fmtWeight(l.current_weight)}</p></div>
      <div><label class="form-label">Date of Birth</label><p>${fmtDate(l.date_of_birth)}</p></div>
      <div><label class="form-label">Farm</label><p>${fmt(l.farm_name)}</p></div>
      <div><label class="form-label">Location</label><p>${fmt(l.location_name)}</p></div>
      <div><label class="form-label">Farmer</label><p>${fmt(l.user_first_name)} ${fmt(l.user_last_name,'')}</p></div>
      <div><label class="form-label">Added</label><p>${fmtDate(l.date_created)}</p></div>
    </div>
    ${l.description ? `<div class="form-group"><label class="form-label">Description</label><p style="color:var(--text-muted)">${l.description}</p></div>` : ''}`;
  openModal('viewModal');
}

function editLivestock(id) {
  const l = livestock.find(x => x.livestock_id == id);
  document.getElementById('editId').value         = l.livestock_id;
  document.getElementById('editTag').value         = l.tag_number;
  document.getElementById('editGender').value      = l.gender;
  document.getElementById('editHealth').value      = l.health_status || '';
  document.getElementById('editSaleStatus').value  = l.sale_status;
  document.getElementById('editPrice').value       = l.price;
  document.getElementById('editWeight').value      = l.current_weight || '';
  document.getElementById('editDesc').value        = l.description || '';
  openModal('editModal');
}

async function saveLivestock() {
  const id = document.getElementById('editId').value;
  const res = await Api.put(`/livestock/${id}`, {
    tag_number:     document.getElementById('editTag').value,
    gender:         document.getElementById('editGender').value,
    health_status:  document.getElementById('editHealth').value,
    sale_status:    document.getElementById('editSaleStatus').value,
    price:          document.getElementById('editPrice').value,
    current_weight: document.getElementById('editWeight').value,
    description:    document.getElementById('editDesc').value,
  });
  if (res.ok) { toast('Livestock updated!'); closeModal('editModal'); loadLivestock(); }
  else toast(res.message || 'Update failed.', 'error');
}

function deleteLivestock(id, tag) {
  confirmDelete(`Delete livestock "${tag}"?`, async () => {
    const res = await Api.del(`/livestock/${id}`);
    if (res.ok) { toast('Livestock deleted.'); loadLivestock(); }
    else toast(res.message || 'Delete failed.', 'error');
  });
}

init();
</script>
</body>
</html>
