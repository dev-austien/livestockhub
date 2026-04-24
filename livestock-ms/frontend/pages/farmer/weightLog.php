<?php $navRole = 'farmer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Weight Log — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">⚖️ Weight Log</div><div class="page-subtitle">Track weight history per livestock</div></div>
    <button class="btn btn-primary" onclick="openAddWeight()">+ Log Weight</button>
  </div>

  <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;">
    <!-- Livestock List -->
    <div class="card" style="height:fit-content;">
      <div class="card-header"><span class="card-title">Select Livestock</span></div>
      <div style="max-height:520px;overflow-y:auto;" id="lvList">
        <div style="padding:20px;text-align:center"><div class="spinner"></div></div>
      </div>
    </div>

    <!-- Weight History -->
    <div class="card">
      <div class="card-header">
        <span class="card-title" id="weightTitle">Select a livestock to view weights</span>
        <button class="btn btn-sm btn-primary" id="addWeightBtn" style="display:none" onclick="openAddWeight()">+ Log Weight</button>
      </div>
      <div class="card-body" id="weightHistory">
        <div class="empty-state"><div class="empty-icon">⚖️</div><h3>No livestock selected</h3><p>Click a livestock on the left to view its weight history.</p></div>
      </div>
    </div>
  </div>
</div>
</div>

<!-- Log Weight Modal -->
<div class="modal-overlay" id="weightModal">
  <div class="modal modal-sm">
    <div class="modal-header"><span class="modal-title">Log Weight</span><button class="modal-close" onclick="closeModal('weightModal')">✕</button></div>
    <div class="modal-body">
      <div class="form-group"><label class="form-label">Livestock</label>
        <select class="form-control" id="wLvSelect"><option value="">Select livestock…</option></select>
      </div>
      <div class="form-group"><label class="form-label">Weight (kg) *</label>
        <input type="number" step="0.1" class="form-control" id="wValue" placeholder="0.0">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('weightModal')">Cancel</button>
      <button class="btn btn-primary" onclick="saveWeight()">Save</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Farmer']);
let livestock = [], selectedId = null;

async function init() {
  const res = await Api.get('/livestock');
  livestock = res.data || [];

  const lvOpts = livestock.map(l => `<option value="${l.livestock_id}">${l.tag_number} (${l.category_name})</option>`).join('');
  document.getElementById('wLvSelect').innerHTML = '<option value="">Select livestock…</option>' + lvOpts;

  const listEl = document.getElementById('lvList');
  if (!livestock.length) { listEl.innerHTML = '<div style="padding:20px;text-align:center;color:var(--text-muted)">No livestock found.</div>'; return; }
  listEl.innerHTML = livestock.map(l => `
    <div onclick="selectLivestock(${l.livestock_id})" id="lvItem${l.livestock_id}"
      style="padding:12px 16px;cursor:pointer;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:12px;transition:background .15s;">
      <span style="font-size:1.4rem">${categoryEmoji(l.category_name)}</span>
      <div>
        <div style="font-weight:600;font-size:.9rem">${l.tag_number}</div>
        <div style="font-size:.78rem;color:var(--text-muted)">${fmt(l.category_name)} · ${fmtWeight(l.current_weight)}</div>
      </div>
    </div>`).join('');
}

async function selectLivestock(id) {
  selectedId = id;
  document.querySelectorAll('[id^=lvItem]').forEach(el => el.style.background = '');
  const el = document.getElementById(`lvItem${id}`);
  if (el) el.style.background = 'var(--green-50)';

  const lv = livestock.find(x => x.livestock_id == id);
  document.getElementById('weightTitle').textContent = `Weight History — ${lv?.tag_number}`;
  document.getElementById('addWeightBtn').style.display = '';
  document.getElementById('wLvSelect').value = id;

  const histEl = document.getElementById('weightHistory');
  histEl.innerHTML = '<div class="spinner"></div>';

  const res = await Api.get(`/livestock/${id}/weights`);
  const weights = res.data || [];

  if (!weights.length) {
    histEl.innerHTML = '<div class="empty-state"><div class="empty-icon">⚖️</div><h3>No weight records yet</h3><p>Click "+ Log Weight" to record the first entry.</p></div>';
    return;
  }

  const maxW = Math.max(...weights.map(w => parseFloat(w.weight||0)));
  histEl.innerHTML = `<div class="weight-history">
    ${weights.map(w => {
      const pct = maxW > 0 ? (parseFloat(w.weight)/maxW*100).toFixed(0) : 0;
      return `<div class="weight-row">
        <span class="weight-date">${fmtDate(w.date_recorded)}</span>
        <div class="weight-bar-wrap"><div class="weight-bar" style="width:${pct}%"></div></div>
        <span class="weight-value">${fmtWeight(w.weight)}</span>
        <button class="btn btn-sm btn-danger btn-icon" onclick="deleteWeight(${id},${w.weight_id})" title="Delete">🗑️</button>
      </div>`;
    }).join('')}
  </div>`;
}

function openAddWeight() {
  document.getElementById('wValue').value = '';
  if (selectedId) document.getElementById('wLvSelect').value = selectedId;
  openModal('weightModal');
}

async function saveWeight() {
  const lvId = document.getElementById('wLvSelect').value;
  const weight = document.getElementById('wValue').value;
  if (!lvId) { toast('Please select a livestock.', 'warning'); return; }
  if (!weight) { toast('Weight is required.', 'warning'); return; }

  const res = await Api.post(`/livestock/${lvId}/weights`, { weight });
  if (res.ok) {
    toast('Weight logged!');
    closeModal('weightModal');
    // Update local weight
    const lv = livestock.find(x => x.livestock_id == lvId);
    if (lv) lv.current_weight = weight;
    if (selectedId == lvId) selectLivestock(lvId);
  } else toast(res.message || 'Failed.', 'error');
}

function deleteWeight(lvId, weightId) {
  confirmDelete('Delete this weight record?', async () => {
    const res = await Api.del(`/livestock/${lvId}/weights/${weightId}`);
    if (res.ok) { toast('Record deleted.'); selectLivestock(lvId); }
    else toast(res.message || 'Failed.', 'error');
  });
}

init();
</script>
</body>
</html>
