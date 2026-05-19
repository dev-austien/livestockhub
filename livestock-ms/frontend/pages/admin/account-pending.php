<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Pending — LivestockHub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div>
      <div class="page-title">Account Pending</div>
      <div class="page-subtitle">Click a row or View to open registration details</div>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Pending Farmer Accounts</span></div>
    <div class="table-wrap">
      <table>
        <thead>
          <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Farm</th>
            <th>Submitted</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody id="pendingBody">
          <tr><td colspan="5"><div class="spinner"></div></td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>
</div>

<div class="modal-overlay" id="accountDetailModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title" id="accountDetailTitle">Farmer Registration</span>
      <button type="button" class="modal-close" id="accountDetailClose">✕</button>
    </div>
    <div class="modal-body" id="accountDetailBody"></div>
    <div class="modal-footer" style="justify-content:flex-end;gap:10px;">
      <button type="button" class="btn btn-secondary" id="accountDetailCloseBtn">Close</button>
      <button type="button" class="btn btn-danger" id="accountDeclineBtn">Decline</button>
      <button type="button" class="btn btn-primary" id="accountApproveBtn">Approve</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
(function() {
  const user = requireAuth(['Admin']);
  if (!user) return;

  let selectedId = null;

  function esc(s) {
    if (s == null || s === '') return '—';
    const d = document.createElement('div');
    d.textContent = String(s);
    return d.innerHTML;
  }

  function docUrl(path) {
    const base = window.UPLOAD_BASE || '/livestockhub/livestock-ms/uploads';
    return path ? (base + '/' + String(path).replace(/^\//, '')) : '';
  }

  document.getElementById('accountDetailClose').addEventListener('click', () => closeModal('accountDetailModal'));
  document.getElementById('accountDetailCloseBtn').addEventListener('click', () => closeModal('accountDetailModal'));
  document.getElementById('accountApproveBtn').addEventListener('click', approveAccount);
  document.getElementById('accountDeclineBtn').addEventListener('click', declineAccount);

  document.getElementById('pendingBody').addEventListener('click', function(e) {
    const target = e.target.closest('[data-user-id]');
    if (!target) return;
    e.preventDefault();
    const id = Number(target.getAttribute('data-user-id'));
    if (id) viewAccount(id);
  });

  async function loadPending() {
    const body = document.getElementById('pendingBody');
    body.innerHTML = loadingRow(5);
    const res = await Api.get('/pending-accounts');
    if (!res.ok) {
      body.innerHTML = emptyRow(5, res.message || 'Failed to load pending accounts.');
      return;
    }
    const accounts = res.data || [];
    if (!accounts.length) {
      body.innerHTML = emptyRow(5, 'No pending accounts.');
      return;
    }
    body.innerHTML = accounts.map(a => {
      const id = Number(a.user_id);
      const name = esc(a.user_first_name) + ' ' + esc(a.user_last_name);
      return '<tr class="pending-row" data-user-id="' + id + '" style="cursor:pointer">' +
        '<td><strong>' + name + '</strong></td>' +
        '<td>' + esc(a.user_email) + '</td>' +
        '<td>' + esc(a.farm_name) + '</td>' +
        '<td>' + esc(fmtDate(a.created_at)) + '</td>' +
        '<td><button type="button" class="btn btn-sm btn-secondary" data-user-id="' + id + '">View</button></td>' +
        '</tr>';
    }).join('');
  }

  async function viewAccount(id) {
    selectedId = id;
    const res = await Api.get('/pending-accounts/' + id);
    if (!res.ok || !res.data) {
      toast(res.message || 'Could not load account details', 'error');
      return;
    }
    const a = res.data;

    document.getElementById('accountDetailTitle').textContent =
      (a.user_first_name || '') + ' ' + (a.user_last_name || '') + ' — Registration';

    const idImg = a.valid_id_path
      ? '<a href="' + esc(docUrl(a.valid_id_path)) + '" target="_blank" rel="noopener">' +
        '<img src="' + esc(docUrl(a.valid_id_path)) + '" alt="Valid ID" style="width:100%;max-height:280px;object-fit:contain;border:1px solid var(--border);border-radius:12px;">' +
        '</a>'
      : '<p style="color:var(--text-muted)">No file uploaded</p>';

    const birthImg = a.birth_cert_path
      ? '<a href="' + esc(docUrl(a.birth_cert_path)) + '" target="_blank" rel="noopener">' +
        '<img src="' + esc(docUrl(a.birth_cert_path)) + '" alt="Birth Certificate" style="width:100%;max-height:280px;object-fit:contain;border:1px solid var(--border);border-radius:12px;">' +
        '</a>'
      : '<p style="color:var(--text-muted)">No file uploaded</p>';

    document.getElementById('accountDetailBody').innerHTML =
      '<div class="form-grid cols-2" style="gap:16px">' +
        field('First Name', a.user_first_name, true) +
        field('Last Name', a.user_last_name, true) +
        field('Middle Name', a.user_middle_name) +
        field('Username', a.username) +
        field('Email Address', a.user_email) +
        field('Phone Number', a.user_phone_number) +
        '<div class="form-group" style="grid-column:1/-1">' +
          '<label class="form-label">Farm Name</label><p>' + esc(a.farm_name) + '</p></div>' +
      '</div>' +
      '<hr class="divider">' +
      '<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">' +
        '<div><label class="form-label">Valid I.D</label>' + idImg + '</div>' +
        '<div><label class="form-label">Birth Certificate</label>' + birthImg + '</div>' +
      '</div>';

    openModal('accountDetailModal');
  }

  function field(label, value, bold) {
    const v = esc(value);
    return '<div class="form-group"><label class="form-label">' + label + '</label><p>' +
      (bold ? '<strong>' + v + '</strong>' : v) + '</p></div>';
  }

  async function approveAccount() {
    if (!selectedId) return;
    const res = await Api.post('/pending-accounts/' + selectedId + '/approve', {});
    if (res.ok) {
      closeModal('accountDetailModal');
      toast('Account approved');
      selectedId = null;
      loadPending();
    } else {
      toast(res.message || 'Failed to approve', 'error');
    }
  }

  async function declineAccount() {
    if (!selectedId) return;
    if (!confirm('Decline this account? The user will be removed.')) return;
    const res = await Api.post('/pending-accounts/' + selectedId + '/decline', {});
    if (res.ok) {
      closeModal('accountDetailModal');
      toast('Account declined');
      selectedId = null;
      loadPending();
    } else {
      toast(res.message || 'Failed to decline', 'error');
    }
  }

  loadPending();
})();
</script>
</body>
</html>
