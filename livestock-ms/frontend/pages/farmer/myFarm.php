<?php $navRole = 'farmer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Farm — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">My Farm</div><div class="page-subtitle">Manage farm profile and locations</div></div>
    <button class="btn btn-primary" onclick="openAddLocation()">+ Add Location</button>
  </div>

  <!-- Farm Info Card -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-header">
      <span class="card-title">🏡 Farm Profile</span>
      <button class="btn btn-sm btn-secondary" onclick="openEditFarm()">✏️ Edit</button>
    </div>
    <div class="card-body">
      <div class="form-grid cols-2" id="farmInfo">
        <div class="spinner"></div>
      </div>
    </div>
  </div>

  <!-- Locations -->
  <div class="card">
    <div class="card-header"><span class="card-title">📍 Farm Locations / Pens</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Name</th><th>Type</th><th>Barangay</th><th>City/Municipality</th><th>Province</th><th>Capacity</th><th>Actions</th></tr></thead>
        <tbody id="locBody"><tr><td colspan="7"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Edit Farm Modal -->
<div class="modal-overlay" id="farmModal">
  <div class="modal modal-sm">
    <div class="modal-header"><span class="modal-title">Edit Farm</span><button class="modal-close" onclick="closeModal('farmModal')">✕</button></div>
    <div class="modal-body">
      <input type="hidden" id="farmerId">
      <div class="form-group"><label class="form-label">Farm Name *</label><input class="form-control" id="farmName" placeholder="My Farm"></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('farmModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveFarm()">Save</button>
    </div>
  </div>
</div>

<!-- Add/Edit Location Modal -->
<div class="modal-overlay" id="locModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title" id="locModalTitle">Add Location</span>
      <button class="modal-close" onclick="closeModal('locModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="locId">
      <div class="form-grid cols-2">
        <div class="form-group"><label class="form-label">Location Name *</label><input class="form-control" id="locName" placeholder="e.g. Pen A"></div>
        <div class="form-group"><label class="form-label">Type</label>
          <select class="form-control" id="locType">
            <option value="Pen">Pen</option>
            <option value="Barn">Barn</option>
            <option value="Pasture">Pasture</option>
            <option value="Cage">Cage</option>
            <option value="Other">Other</option>
          </select>
        </div>
        <div class="form-group"><label class="form-label">Barangay</label><input class="form-control" id="locBrgy" placeholder="Barangay"></div>
        <div class="form-group"><label class="form-label">City / Municipality</label><input class="form-control" id="locCity" placeholder="City/Municipality"></div>
        <div class="form-group"><label class="form-label">Province</label><input class="form-control" id="locProv" placeholder="Province"></div>
        <div class="form-group"><label class="form-label">Capacity (animals)</label><input type="number" class="form-control" id="locCap" placeholder="50"></div>
      </div>
      <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" id="locDesc" rows="2" placeholder="Notes about this location…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('locModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveLocation()">Save</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
const authUser = requireAuth(['Farmer']);
let farmerData = null, locations = [];

async function load() {
  const [farmerRes, locRes] = await Promise.all([Api.get('/farmers'), Api.get('/locations')]);
  const farmers = farmerRes.data || [];
  farmerData = farmers.find(f => f.user_id == authUser.sub) || farmers[0];
  locations = locRes.data || [];

  renderFarmInfo();
  renderLocations();
}

function renderFarmInfo() {
  document.getElementById('farmInfo').innerHTML = farmerData ? `
    <div><label class="form-label">Farm Name</label><p><strong>${fmt(farmerData.farm_name)}</strong></p></div>
    <div><label class="form-label">Farmer</label><p>${fmt(farmerData.user_first_name)} ${fmt(farmerData.user_last_name,'')}</p></div>
    <div><label class="form-label">Email</label><p>${fmt(farmerData.user_email)}</p></div>
    <div><label class="form-label">Phone</label><p>${fmt(farmerData.user_phone_number)}</p></div>
  ` : '<p style="color:var(--text-muted)">No farm profile found.</p>';
}

function renderLocations() {
  const body = document.getElementById('locBody');
  if (!locations.length) { body.innerHTML = emptyRow(7, 'No locations added yet.'); return; }
  body.innerHTML = locations.map(l => `<tr>
    <td><strong>${l.location_name}</strong></td>
    <td><span class="tag">${l.location_type||'—'}</span></td>
    <td>${fmt(l.location_brgy)}</td>
    <td>${fmt(l.location_city_muni)}</td>
    <td>${fmt(l.location_province)}</td>
    <td><span class="badge badge-green">${l.capacity||0}</span></td>
    <td><div class="table-actions">
      <button class="btn btn-sm btn-secondary" onclick="editLocation(${l.location_id})">✏️</button>
      <button class="btn btn-sm btn-danger" onclick="deleteLocation(${l.location_id},'${l.location_name}')">🗑️</button>
    </div></td>
  </tr>`).join('');
}

function openEditFarm() {
  document.getElementById('farmerId').value = farmerData?.farmer_id || '';
  document.getElementById('farmName').value = farmerData?.farm_name || '';
  openModal('farmModal');
}

async function saveFarm() {
  const id = document.getElementById('farmerId').value;
  const res = await Api.put(`/farmers/${id}`, { farm_name: document.getElementById('farmName').value });
  if (res.ok) { toast('Farm updated!'); closeModal('farmModal'); load(); }
  else toast(res.message || 'Failed.', 'error');
}

function openAddLocation() {
  document.getElementById('locId').value = '';
  ['locName','locBrgy','locCity','locProv','locDesc'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('locCap').value = '';
  document.getElementById('locType').value = 'Pen';
  document.getElementById('locModalTitle').textContent = 'Add Location';
  openModal('locModal');
}

function editLocation(id) {
  const l = locations.find(x => x.location_id == id);
  document.getElementById('locId').value   = l.location_id;
  document.getElementById('locName').value = l.location_name;
  document.getElementById('locType').value = l.location_type || 'Pen';
  document.getElementById('locBrgy').value = l.location_brgy || '';
  document.getElementById('locCity').value = l.location_city_muni || '';
  document.getElementById('locProv').value = l.location_province || '';
  document.getElementById('locCap').value  = l.capacity || '';
  document.getElementById('locDesc').value = l.description || '';
  document.getElementById('locModalTitle').textContent = 'Edit Location';
  openModal('locModal');
}

async function saveLocation() {
  const id = document.getElementById('locId').value;
  const body = {
    location_name:     document.getElementById('locName').value,
    location_type:     document.getElementById('locType').value,
    location_brgy:     document.getElementById('locBrgy').value,
    location_city_muni:document.getElementById('locCity').value,
    location_province: document.getElementById('locProv').value,
    capacity:          document.getElementById('locCap').value || 0,
    description:       document.getElementById('locDesc').value,
  };
  if (!body.location_name) { toast('Location name required.', 'warning'); return; }
  const res = id ? await Api.put(`/locations/${id}`, body) : await Api.post('/locations', body);
  if (res.ok) { toast(id ? 'Location updated!' : 'Location added!'); closeModal('locModal'); load(); }
  else toast(res.message || 'Failed.', 'error');
}

function deleteLocation(id, name) {
  confirmDelete(`Delete location "${name}"?`, async () => {
    const res = await Api.del(`/locations/${id}`);
    if (res.ok) { toast('Location deleted.'); load(); }
    else toast(res.message || 'Failed.', 'error');
  });
}

load();
</script>
</body>
</html>
