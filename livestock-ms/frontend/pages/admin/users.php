<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Users — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">Users</div><div class="page-subtitle">Manage system users</div></div>
  </div>

  <div class="card">
    <div class="card-header">
      <span class="card-title">All Users</span>
      <div class="toolbar">
        <div class="search-box"><span class="search-icon">🔍</span><input type="text" id="searchInput" placeholder="Search users…" oninput="filterTable('searchInput','usersBody')"></div>
        <select class="form-control" id="roleFilter" style="width:auto" onchange="loadUsers()">
          <option value="">All Roles</option>
          <option value="Admin">Admin</option>
          <option value="Farmer">Farmer</option>
          <option value="Buyer">Buyer</option>
        </select>
      </div>
    </div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
        <tbody id="usersBody"><tr><td colspan="8"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title">Edit User</span>
      <button class="modal-close" onclick="closeModal('editModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="editUserId">
      <div class="form-grid cols-2">
        <div class="form-group"><label class="form-label">First Name</label><input class="form-control" id="editFirstName"></div>
        <div class="form-group"><label class="form-label">Last Name</label><input class="form-control" id="editLastName"></div>
      </div>
      <div class="form-group"><label class="form-label">Phone</label><input class="form-control" id="editPhone"></div>
      <div class="form-group"><label class="form-label">Role</label>
        <select class="form-control" id="editRole">
          <option value="Admin">Admin</option>
          <option value="Farmer">Farmer</option>
          <option value="Buyer">Buyer</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Status</label>
        <select class="form-control" id="editStatus">
          <option value="Active">Active</option>
          <option value="Suspended">Suspended</option>
          <option value="Inactive">Inactive</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">New Password <small style="color:var(--text-muted)">(leave blank to keep)</small></label>
        <input type="password" class="form-control" id="editPassword" placeholder="New password…">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('editModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveUser()">Save Changes</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
const authUser = requireAuth(['Admin']);
let allUsers = [];

async function loadUsers() {
  const body = document.getElementById('usersBody');
  body.innerHTML = loadingRow(8);
  const res = await Api.get('/users');
  if (!res.ok) { body.innerHTML = emptyRow(8, 'Failed to load users.'); return; }
  const roleF = document.getElementById('roleFilter').value;
  allUsers = (res.data || []).filter(u => !roleF || u.user_role === roleF);
  renderUsers();
}

function renderUsers() {
  const body = document.getElementById('usersBody');
  if (!allUsers.length) { body.innerHTML = emptyRow(8); return; }
  body.innerHTML = allUsers.map((u, i) => `<tr>
    <td>${i+1}</td>
    <td><strong>${fmt(u.user_first_name)} ${fmt(u.user_last_name,'')}</strong><br><small style="color:var(--text-muted)">@${u.username||'—'}</small></td>
    <td>${fmt(u.user_email)}</td>
    <td>${fmt(u.user_phone_number)}</td>
    <td>${statusBadge(u.user_role)}</td>
    <td>${statusBadge(u.user_status)}</td>
    <td>${fmtDate(u.created_at)}</td>
    <td><div class="table-actions">
      <button class="btn btn-sm btn-secondary" onclick="editUser(${u.user_id})">✏️ Edit</button>
      ${u.user_id != authUser.sub ? `<button class="btn btn-sm btn-danger" onclick="deleteUser(${u.user_id},'${u.user_first_name}')">🗑️</button>` : ''}
    </div></td>
  </tr>`).join('');
}

function editUser(id) {
  const u = allUsers.find(x => x.user_id == id);
  if (!u) return;
  document.getElementById('editUserId').value    = u.user_id;
  document.getElementById('editFirstName').value = u.user_first_name || '';
  document.getElementById('editLastName').value  = u.user_last_name  || '';
  document.getElementById('editPhone').value     = u.user_phone_number || '';
  document.getElementById('editRole').value      = u.user_role;
  document.getElementById('editStatus').value    = u.user_status;
  document.getElementById('editPassword').value  = '';
  openModal('editModal');
}

async function saveUser() {
  const id = document.getElementById('editUserId').value;
  const body = {
    user_first_name:    document.getElementById('editFirstName').value,
    user_last_name:     document.getElementById('editLastName').value,
    user_phone_number:  document.getElementById('editPhone').value,
    user_role:          document.getElementById('editRole').value,
    user_status:        document.getElementById('editStatus').value,
  };
  const pw = document.getElementById('editPassword').value;
  if (pw) body.password = pw;

  const res = await Api.put(`/users/${id}`, body);
  if (res.ok) { toast('User updated!'); closeModal('editModal'); loadUsers(); }
  else toast(res.message || 'Update failed.', 'error');
}

function deleteUser(id, name) {
  confirmDelete(`Delete user "${name}"? This cannot be undone.`, async () => {
    const res = await Api.del(`/users/${id}`);
    if (res.ok) { toast('User deleted.'); loadUsers(); }
    else toast(res.message || 'Delete failed.', 'error');
  });
}

loadUsers();
</script>
</body>
</html>
