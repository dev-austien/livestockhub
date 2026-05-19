<?php $navRole = 'admin'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports — LivestockHub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div>
      <div class="page-title">Reports</div>
      <div class="page-subtitle">Review user reports and apply bans</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-body">
      <div class="search-box" style="max-width:100%;">
        <span class="search-icon">&#128269;</span>
        <input type="text" id="searchInput" placeholder="Farmer name, farm name, email, buyer name…" autocomplete="off">
      </div>
      <p style="margin-top:8px;font-size:.85rem;color:var(--text-muted)">Press Enter to filter reports</p>
    </div>
  </div>

  <div id="reportDetail" class="card" style="display:none;margin-bottom:20px;">
    <div class="card-header"><span class="card-title">Report Details</span></div>
    <div class="card-body" id="reportDetailBody"></div>
    <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;">
      <button class="btn btn-secondary" onclick="openBanModal('temporary')">Temporary Suspension</button>
      <button class="btn btn-danger" onclick="openBanModal('permanent')">Forever Ban</button>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><span class="card-title">Open Reports</span></div>
    <div class="table-wrap">
      <table>
        <thead><tr><th>ID</th><th>Reported</th><th>Role</th><th>Reporter</th><th>Date</th><th></th></tr></thead>
        <tbody id="reportsBody"><tr><td colspan="6"><div class="spinner"></div></td></tr></tbody>
      </table>
    </div>
  </div>
</div>
</div>

<div class="modal-overlay" id="banModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <span class="modal-title" id="banModalTitle">Ban User</span>
      <button class="modal-close" onclick="closeModal('banModal')">X</button>
    </div>
    <div class="modal-body">
      <div class="form-group" id="hoursGroup" style="display:none;">
        <label class="form-label">Hours *</label>
        <input type="number" class="form-control" id="banHours" min="1" value="24">
      </div>
      <div class="form-group">
        <label class="form-label">Reason *</label>
        <textarea class="form-control" id="banReason" rows="4" placeholder="Reason for ban or suspension"></textarea>
      </div>
    </div>
    <div class="modal-footer" style="justify-content:flex-end;">
      <button class="btn btn-secondary" onclick="closeModal('banModal')">Cancel</button>
      <button class="btn btn-danger" id="confirmBanBtn" disabled onclick="submitBan()">Confirm</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Admin']);
let allReports = [];
let selectedReport = null;
let banType = 'temporary';

async function loadReports() {
  const body = document.getElementById('reportsBody');
  body.innerHTML = loadingRow(6);
  const res = await Api.get('/reports');
  if (!res.ok) { body.innerHTML = emptyRow(6, 'Failed to load reports.'); return; }
  allReports = res.data || [];
  renderTable(allReports);
}

function renderTable(list) {
  const body = document.getElementById('reportsBody');
  if (!list.length) { body.innerHTML = emptyRow(6, 'No open reports.'); return; }
  body.innerHTML = list.map(r => `<tr>
    <td>#${r.report_id}</td>
    <td>${fmt(r.reported_first)} ${fmt(r.reported_last)}</td>
    <td>${r.reported_role}</td>
    <td>${fmt(r.reporter_first)} ${fmt(r.reporter_last)}</td>
    <td>${fmtDate(r.created_at)}</td>
    <td><button class="btn btn-sm btn-secondary" onclick="viewReport(${r.report_id})">View</button></td>
  </tr>`).join('');
}

function viewReport(id) {
  selectedReport = allReports.find(r => r.report_id == id);
  if (!selectedReport) return;
  const r = selectedReport;
  const isFarmer = r.reported_role === 'Farmer';
  let stats = '';
  if (isFarmer) {
    stats = `<div><strong>Farm</strong><br>${fmt(r.farm_name)}</div>
      <div><strong>Total Earning</strong><br>${fmtMoney(r.total_earning)}</div>`;
  } else {
    stats = `<div><strong>Total Spent</strong><br>${fmtMoney(r.total_spent)}</div>`;
  }
  document.getElementById('reportDetailBody').innerHTML = `
    <div class="form-grid cols-2">
      <div><strong>Reported User</strong><br>${fmt(r.reported_first)} ${fmt(r.reported_last)} (${r.reported_role})</div>
      <div><strong>Email</strong><br>${fmt(r.reported_email)}</div>
      ${stats}
      <div><strong>Completed Orders</strong><br>${r.completed_orders ?? 0}</div>
      <div style="grid-column:1/-1"><strong>Report Description</strong><br>${fmt(r.description)}</div>
      <div><strong>Reported By</strong><br>${fmt(r.reporter_first)} ${fmt(r.reporter_last)} (${r.reporter_role})</div>
      <div><strong>Date</strong><br>${fmtDate(r.created_at)}</div>
    </div>`;
  document.getElementById('reportDetail').style.display = 'block';
}

document.getElementById('searchInput').addEventListener('keydown', e => {
  if (e.key !== 'Enter') return;
  e.preventDefault();
  const q = e.target.value.trim().toLowerCase();
  if (!q) { renderTable(allReports); return; }
  const filtered = allReports.filter(r => {
    const hay = [
      r.reported_first, r.reported_last, r.reported_email, r.farm_name,
      r.reporter_first, r.reporter_last, r.description
    ].join(' ').toLowerCase();
    return hay.includes(q);
  });
  renderTable(filtered);
  if (filtered.length === 1) viewReport(filtered[0].report_id);
});

function openBanModal(type) {
  if (!selectedReport) return toast('Select a report first', 'warning');
  banType = type;
  document.getElementById('banModalTitle').textContent = type === 'temporary' ? 'Temporary Suspension' : 'Forever Ban';
  document.getElementById('hoursGroup').style.display = type === 'temporary' ? 'block' : 'none';
  document.getElementById('banReason').value = '';
  document.getElementById('confirmBanBtn').disabled = true;
  openModal('banModal');
}

document.getElementById('banReason').addEventListener('input', function() {
  document.getElementById('confirmBanBtn').disabled = !this.value.trim();
});

async function submitBan() {
  if (!selectedReport) return;
  const reason = document.getElementById('banReason').value.trim();
  if (!reason) return;
  const payload = {
    user_id: selectedReport.reported_user_id,
    ban_type: banType,
    reason,
    report_id: selectedReport.report_id,
  };
  if (banType === 'temporary') {
    payload.hours = parseInt(document.getElementById('banHours').value, 10) || 1;
  }
  const res = await Api.post('/bans', payload);
  if (res.ok) {
    closeModal('banModal');
    toast('Ban applied');
    selectedReport = null;
    document.getElementById('reportDetail').style.display = 'none';
    loadReports();
  } else toast(res.message || 'Failed', 'error');
}

loadReports();
</script>
</body>
</html>
