<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categories — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">Categories</div><div class="page-subtitle">Manage livestock categories</div></div>
    <button class="btn btn-primary" onclick="openAddModal()">+ Add Category</button>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">All Categories</span>
      <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search…" oninput="filterTable('searchInput','catBody')"></div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Icon</th><th>Name</th><th>Description</th><th>Breeds</th><th>Actions</th></tr></thead>
        <tbody id="catBody"><tr><td colspan="6"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="catModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <span class="modal-title" id="catModalTitle">Add Category</span>
      <button class="modal-close" onclick="closeModal('catModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="catId">
      <div class="form-group"><label class="form-label">Category Name *</label><input class="form-control" id="catName" placeholder="e.g. Cattle"></div>
      <div class="form-group"><label class="form-label">Description</label><textarea class="form-control" id="catDesc" rows="3" placeholder="Optional description…"></textarea></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('catModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveCategory()">Save</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Admin']);
let cats = [];

async function loadCategories() {
  const body = document.getElementById('catBody');
  body.innerHTML = loadingRow(6);
  const [catRes, breedRes] = await Promise.all([Api.get('/categories'), Api.get('/breeds')]);
  cats = catRes.data || [];
  const breedCount = {};
  (breedRes.data || []).forEach(b => breedCount[b.category_id] = (breedCount[b.category_id]||0)+1);

  if (!cats.length) { body.innerHTML = emptyRow(6); return; }
  body.innerHTML = cats.map((c, i) => `<tr>
    <td>${i+1}</td>
    <td style="font-size:1.6rem">${categoryEmoji(c.category_name)}</td>
    <td><strong>${c.category_name}</strong></td>
    <td style="color:var(--text-muted);font-size:.85rem">${fmt(c.description)}</td>
    <td><span class="badge badge-green">${breedCount[c.category_id]||0} breeds</span></td>
    <td><div class="table-actions">
      <button class="btn btn-sm btn-secondary" onclick="editCat(${c.category_id})">✏️ Edit</button>
      <button class="btn btn-sm btn-danger" onclick="deleteCat(${c.category_id},'${c.category_name}')">🗑️</button>
    </div></td>
  </tr>`).join('');
}

function openAddModal() {
  document.getElementById('catId').value = '';
  document.getElementById('catName').value = '';
  document.getElementById('catDesc').value = '';
  document.getElementById('catModalTitle').textContent = 'Add Category';
  openModal('catModal');
}

function editCat(id) {
  const c = cats.find(x => x.category_id == id);
  document.getElementById('catId').value   = c.category_id;
  document.getElementById('catName').value = c.category_name;
  document.getElementById('catDesc').value = c.description || '';
  document.getElementById('catModalTitle').textContent = 'Edit Category';
  openModal('catModal');
}

async function saveCategory() {
  const id = document.getElementById('catId').value;
  const body = { category_name: document.getElementById('catName').value, description: document.getElementById('catDesc').value };
  if (!body.category_name) { toast('Category name is required.', 'warning'); return; }

  const res = id ? await Api.put(`/categories/${id}`, body) : await Api.post('/categories', body);
  if (res.ok) { toast(id ? 'Category updated!' : 'Category added!'); closeModal('catModal'); loadCategories(); }
  else toast(res.message || 'Failed.', 'error');
}

function deleteCat(id, name) {
  confirmDelete(`Delete category "${name}"? All linked breeds may be affected.`, async () => {
    const res = await Api.del(`/categories/${id}`);
    if (res.ok) { toast('Category deleted.'); loadCategories(); }
    else toast(res.message || 'Delete failed.', 'error');
  });
}

loadCategories();
</script>
</body>
</html>
