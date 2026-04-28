<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Breeds — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">Breeds</div><div class="page-subtitle">Manage livestock breeds by category</div></div>
    <button class="btn btn-primary" onclick="openAdd()">+ Add Breed</button>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">All Breeds</span>
      <div class="toolbar">
        <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search breeds…" oninput="filterTable('searchInput','breedBody')"></div>
        <select class="form-control" id="catFilter" style="width:auto" onchange="renderBreeds()">
          <option value="">All Categories</option>
        </select>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Breed Name</th><th>Category</th><th>Actions</th></tr></thead>
        <tbody id="breedBody"><tr><td colspan="4"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="breedModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <span class="modal-title" id="breedModalTitle">Add Breed</span>
      <button class="modal-close" onclick="closeModal('breedModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="breedId">
      <div class="form-group"><label class="form-label">Category *</label>
        <select class="form-control" id="breedCatId"><option value="">Select category…</option></select>
      </div>
      <div class="form-group"><label class="form-label">Breed Name *</label>
        <input class="form-control" id="breedName" placeholder="e.g. Brahman">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('breedModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveBreed()">Save</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Admin']);
let breeds = [], categories = [];

async function init() {
  const [br, cr] = await Promise.all([Api.get('/breeds'), Api.get('/categories')]);
  breeds = br.data || [];
  categories = cr.data || [];

  // Populate category dropdowns
  const opts = categories.map(c => `<option value="${c.category_id}">${c.category_name}</option>`).join('');
  document.getElementById('catFilter').innerHTML = '<option value="">All Categories</option>' + opts;
  document.getElementById('breedCatId').innerHTML = '<option value="">Select category…</option>' + opts;
  renderBreeds();
}

function renderBreeds() {
  const body = document.getElementById('breedBody');
  const catF = document.getElementById('catFilter').value;
  const filtered = catF ? breeds.filter(b => b.category_id == catF) : breeds;

  if (!filtered.length) { body.innerHTML = emptyRow(4); return; }
  body.innerHTML = filtered.map((b, i) => `<tr>
    <td>${i+1}</td>
    <td><strong>${b.breed_name}</strong></td>
    <td><span class="tag">${categoryEmoji(b.category_name)} ${b.category_name}</span></td>
    <td><div class="table-actions">
      <button class="btn btn-sm btn-secondary" onclick="editBreed(${b.breed_id})">✏️ Edit</button>
      <button class="btn btn-sm btn-danger" onclick="deleteBreed(${b.breed_id},'${b.breed_name}')">🗑️</button>
    </div></td>
  </tr>`).join('');
}

function openAdd() {
  document.getElementById('breedId').value = '';
  document.getElementById('breedName').value = '';
  document.getElementById('breedCatId').value = '';
  document.getElementById('breedModalTitle').textContent = 'Add Breed';
  openModal('breedModal');
}

function editBreed(id) {
  const b = breeds.find(x => x.breed_id == id);
  document.getElementById('breedId').value     = b.breed_id;
  document.getElementById('breedName').value   = b.breed_name;
  document.getElementById('breedCatId').value  = b.category_id;
  document.getElementById('breedModalTitle').textContent = 'Edit Breed';
  openModal('breedModal');
}

async function saveBreed() {
  const id = document.getElementById('breedId').value;
  const body = { breed_name: document.getElementById('breedName').value, category_id: document.getElementById('breedCatId').value };
  if (!body.breed_name || !body.category_id) { toast('All fields are required.', 'warning'); return; }
  const res = id ? await Api.put(`/breeds/${id}`, body) : await Api.post('/breeds', body);
  if (res.ok) { toast(id ? 'Breed updated!' : 'Breed added!'); closeModal('breedModal'); init(); }
  else toast(res.message || 'Failed.', 'error');
}

function deleteBreed(id, name) {
  confirmDelete(`Delete breed "${name}"?`, async () => {
    const res = await Api.del(`/breeds/${id}`);
    if (res.ok) { toast('Breed deleted.'); init(); }
    else toast(res.message || 'Delete failed.', 'error');
  });
}

init();
</script>
</body>
</html>
