<?php $navRole = 'farmer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Livestock — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">My Livestock</div><div class="page-subtitle">Manage your animal records</div></div>
    <button class="btn btn-primary" onclick="openAdd()">+ Add Livestock</button>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">Livestock Records</span>
      <div class="toolbar">
        <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search…" oninput="filterTable('searchInput','lvBody')"></div>
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
        <thead><tr><th>Tag #</th><th>Category / Breed</th><th>Gender</th><th>Weight</th><th>Price</th><th>Location</th><th>Status</th><th>DOB</th><th>Actions</th></tr></thead>
        <tbody id="lvBody"><tr><td colspan="9"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="lvModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title" id="lvModalTitle">Add Livestock</span>
      <button class="modal-close" onclick="closeModal('lvModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="lvId">
      <div class="form-grid cols-2">
        <div class="form-group"><label class="form-label">Tag Number *</label><input class="form-control" id="lvTag" placeholder="e.g. TAG-001"></div>
        <div class="form-group"><label class="form-label">Gender *</label>
          <select class="form-control" id="lvGender"><option value="Male">Male</option><option value="Female">Female</option></select>
        </div>
        <div class="form-group"><label class="form-label">Category *</label>
          <select class="form-control" id="lvCat" onchange="loadBreeds()"><option value="">Select category…</option></select>
        </div>
        <div class="form-group"><label class="form-label">Breed</label>
          <select class="form-control" id="lvBreed"><option value="">Select breed…</option></select>
        </div>
        <div class="form-group"><label class="form-label">Location</label>
          <select class="form-control" id="lvLocation"><option value="">Select location…</option></select>
        </div>
        <div class="form-group"><label class="form-label">Health Status</label>
          <input class="form-control" id="lvHealth" placeholder="e.g. Healthy">
        </div>
        <div class="form-group"><label class="form-label">Price (₱)</label>
          <input type="number" step="0.01" class="form-control" id="lvPrice" placeholder="0.00">
        </div>
        <div class="form-group"><label class="form-label">Current Weight (kg)</label>
          <input type="number" step="0.1" class="form-control" id="lvWeight" placeholder="0.0">
        </div>
        <div class="form-group"><label class="form-label">Date of Birth</label>
          <input type="date" class="form-control" id="lvDob">
        </div>
        <div class="form-group"><label class="form-label">Sale Status</label>
          <select class="form-control" id="lvSaleStatus">
            <option value="Available">Available</option>
            <option value="Reserved">Reserved</option>
            <option value="Sold">Sold</option>
          </select>
        </div>
      </div>
      <div class="form-group"><label class="form-label">Description</label>
        <textarea class="form-control" id="lvDesc" rows="3" placeholder="Additional notes…"></textarea>
      </div>
      <div class="form-group"><label class="form-label">Photo</label>
        <input type="file" class="form-control" id="lvImage" accept="image/*">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('lvModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveLivestock()">Save</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
const authUser = requireAuth(['Farmer']);
let livestock = [], categories = [], locations = [];

async function init() {
  const [catRes, locRes] = await Promise.all([Api.get('/categories'), Api.get('/locations')]);
  categories = catRes.data || [];
  locations  = locRes.data || [];

  const catOpts = categories.map(c => `<option value="${c.category_id}">${categoryEmoji(c.category_name)} ${c.category_name}</option>`).join('');
  document.getElementById('lvCat').innerHTML = '<option value="">Select category…</option>' + catOpts;

  const locOpts = locations.map(l => `<option value="${l.location_id}">${l.location_name}</option>`).join('');
  document.getElementById('lvLocation').innerHTML = '<option value="">Select location…</option>' + locOpts;

  loadLivestock();
}

async function loadBreeds() {
  const catId = document.getElementById('lvCat').value;
  document.getElementById('lvBreed').innerHTML = '<option value="">Loading…</option>';
  if (!catId) { document.getElementById('lvBreed').innerHTML = '<option value="">Select breed…</option>'; return; }
  const res = await Api.get(`/breeds?category_id=${catId}`);
  const opts = (res.data||[]).map(b => `<option value="${b.breed_id}">${b.breed_name}</option>`).join('');
  document.getElementById('lvBreed').innerHTML = '<option value="">Select breed…</option>' + opts;
}

async function loadLivestock() {
  const body = document.getElementById('lvBody');
  body.innerHTML = loadingRow(9);
  const sf = document.getElementById('statusFilter').value;
  let ep = '/livestock';
  if (sf) ep += `?sale_status=${sf}`;
  const res = await Api.get(ep);
  livestock = res.data || [];
  if (!livestock.length) { body.innerHTML = emptyRow(9); return; }
  body.innerHTML = livestock.map(l => `<tr>
    <td><strong>${l.tag_number}</strong></td>
    <td><span class="tag">${categoryEmoji(l.category_name)} ${fmt(l.category_name)}</span><br><small style="color:var(--text-muted)">${fmt(l.breed_name)}</small></td>
    <td>${statusBadge(l.gender)}</td>
    <td>${fmtWeight(l.current_weight)}</td>
    <td style="font-weight:700;color:var(--accent-dark)">${fmtMoney(l.price)}</td>
    <td style="font-size:.82rem">${fmt(l.location_name)}</td>
    <td>${statusBadge(l.sale_status)}</td>
    <td>${fmtDate(l.date_of_birth)}</td>
    <td><div class="table-actions">
      <button class="btn btn-sm btn-secondary" onclick="editLivestock(${l.livestock_id})">✏️</button>
      <button class="btn btn-sm btn-danger" onclick="deleteLivestock(${l.livestock_id},'${l.tag_number}')">🗑️</button>
    </div></td>
  </tr>`).join('');
}

function openAdd() {
  document.getElementById('lvId').value = '';
  document.getElementById('lvTag').value = '';
  document.getElementById('lvGender').value = 'Male';
  document.getElementById('lvCat').value = '';
  document.getElementById('lvBreed').innerHTML = '<option value="">Select breed…</option>';
  document.getElementById('lvLocation').value = '';
  document.getElementById('lvHealth').value = '';
  document.getElementById('lvPrice').value = '';
  document.getElementById('lvWeight').value = '';
  document.getElementById('lvDob').value = '';
  document.getElementById('lvSaleStatus').value = 'Available';
  document.getElementById('lvDesc').value = '';
  document.getElementById('lvModalTitle').textContent = 'Add Livestock';
  openModal('lvModal');
}

async function editLivestock(id) {
  const l = livestock.find(x => x.livestock_id == id);
  document.getElementById('lvId').value         = l.livestock_id;
  document.getElementById('lvTag').value         = l.tag_number;
  document.getElementById('lvGender').value      = l.gender;
  document.getElementById('lvCat').value         = l.category_id || '';
  document.getElementById('lvLocation').value    = l.location_id || '';
  document.getElementById('lvHealth').value      = l.health_status || '';
  document.getElementById('lvPrice').value       = l.price || '';
  document.getElementById('lvWeight').value      = l.current_weight || '';
  document.getElementById('lvSaleStatus').value  = l.sale_status;
  document.getElementById('lvDesc').value        = l.description || '';
  if (l.date_of_birth) document.getElementById('lvDob').value = l.date_of_birth.split(' ')[0];
  await loadBreeds();
  document.getElementById('lvBreed').value = l.breed_id || '';
  document.getElementById('lvModalTitle').textContent = 'Edit Livestock';
  openModal('lvModal');
}

async function saveLivestock() {
  const id = document.getElementById('lvId').value;
  const fileInput = document.getElementById('lvImage');
  const hasFile = fileInput.files.length > 0;

  let res;
  if (hasFile) {
    const fd = new FormData();
    fd.append('tag_number',    document.getElementById('lvTag').value);
    fd.append('gender',        document.getElementById('lvGender').value);
    fd.append('category_id',   document.getElementById('lvCat').value);
    fd.append('breed_id',      document.getElementById('lvBreed').value);
    fd.append('location_id',   document.getElementById('lvLocation').value);
    fd.append('health_status', document.getElementById('lvHealth').value);
    fd.append('price',         document.getElementById('lvPrice').value);
    fd.append('current_weight',document.getElementById('lvWeight').value);
    fd.append('date_of_birth', document.getElementById('lvDob').value);
    fd.append('sale_status',   document.getElementById('lvSaleStatus').value);
    fd.append('description',   document.getElementById('lvDesc').value);
    fd.append('livestock_image', fileInput.files[0]);
    res = id ? await Api.uploadPut(`/livestock/${id}`, fd) : await Api.upload('/livestock', fd);
  } else {
    const body = {
      tag_number:     document.getElementById('lvTag').value,
      gender:         document.getElementById('lvGender').value,
      category_id:    document.getElementById('lvCat').value,
      breed_id:       document.getElementById('lvBreed').value || null,
      location_id:    document.getElementById('lvLocation').value || null,
      health_status:  document.getElementById('lvHealth').value,
      price:          document.getElementById('lvPrice').value || 0,
      current_weight: document.getElementById('lvWeight').value || null,
      date_of_birth:  document.getElementById('lvDob').value || null,
      sale_status:    document.getElementById('lvSaleStatus').value,
      description:    document.getElementById('lvDesc').value,
    };
    res = id ? await Api.put(`/livestock/${id}`, body) : await Api.post('/livestock', body);
  }

  if (res.ok) { toast(id ? 'Livestock updated!' : 'Livestock added!'); closeModal('lvModal'); loadLivestock(); }
  else toast(res.message || 'Save failed.', 'error');
}

function deleteLivestock(id, tag) {
  confirmDelete(`Delete livestock "${tag}"?`, async () => {
    const res = await Api.del(`/livestock/${id}`);
    if (res.ok) { toast('Livestock deleted.'); loadLivestock(); }
    else toast(res.message || 'Failed.', 'error');
  });
}

init();
</script>
</body>
</html>
