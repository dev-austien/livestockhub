// ── Toast Notifications ────────────────────
function toast(message, type = 'success') {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }
  const icons = { success: '✅', error: '❌', warning: '⚠️' };
  const t = document.createElement('div');
  t.className = `toast ${type}`;
  t.innerHTML = `<span class="toast-icon">${icons[type]||'ℹ️'}</span><span class="toast-msg">${message}</span>`;
  container.appendChild(t);
  setTimeout(() => t.remove(), 3500);
}

// ── Modal ──────────────────────────────────
function openModal(id) { document.getElementById(id).classList.add('show'); }
function closeModal(id) { document.getElementById(id).classList.remove('show'); }
function closeAllModals() { document.querySelectorAll('.modal-overlay').forEach(m => m.classList.remove('show')); }

// Close modal on overlay click
document.addEventListener('click', e => {
  if (e.target.classList.contains('modal-overlay')) closeAllModals();
});

// ── Sidebar Toggle ─────────────────────────
function initSidebar() {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const hamburger = document.getElementById('hamburger');
  if (!sidebar) return;

  hamburger?.addEventListener('click', () => {
    if (window.innerWidth <= 768) {
      sidebar.classList.toggle('open');
      overlay?.classList.toggle('show');
    } else {
      sidebar.classList.toggle('collapsed');
      document.querySelector('.main-content')?.classList.toggle('expanded');
    }
  });
  overlay?.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.classList.remove('show');
  });
}

// ── Set Active Nav Link ────────────────────
function setActiveNav() {
  const current = location.pathname;
  document.querySelectorAll('.sidebar-nav a').forEach(a => {
    if (a.href && current.endsWith(a.getAttribute('href')?.replace(/^.*\/pages/, ''))) {
      a.classList.add('active');
    }
  });
}

// ── Populate User Info in Nav ──────────────
function populateNavUser() {
  const user = Api.getUser();
  if (!user) return;
  const name = `${user.user_first_name || ''} ${user.user_last_name || ''}`.trim();
  const el = document.getElementById('navUserName');
  const av = document.getElementById('navAvatar');
  const rb = document.getElementById('navRoleBadge');
  if (el) el.textContent = name || user.username;
  if (av) av.textContent = (name[0] || user.username[0] || 'U').toUpperCase();
  if (rb) rb.textContent = user.user_role;
}

// ── Format Helpers ─────────────────────────
function fmt(val, fallback = '—') { return val ?? fallback; }
function fmtDate(d) {
  if (!d) return '—';
  return new Date(d).toLocaleDateString('en-PH', { year:'numeric', month:'short', day:'numeric' });
}
function fmtMoney(v) {
  return '₱' + parseFloat(v || 0).toLocaleString('en-PH', { minimumFractionDigits: 2 });
}
function fmtWeight(v) { return v ? `${parseFloat(v).toFixed(1)} kg` : '—'; }

// ── Status Badge ───────────────────────────
function statusBadge(status) {
  const map = {
    Available: 'badge-green', Reserved: 'badge-amber', Sold: 'badge-gray',
    Pending: 'badge-amber', Confirmed: 'badge-blue', Completed: 'badge-green', Cancelled: 'badge-red',
    Paid: 'badge-green', Failed: 'badge-red', Refunded: 'badge-gray',
    Active: 'badge-green', Suspended: 'badge-red', Inactive: 'badge-gray',
    Male: 'badge-blue', Female: 'badge-amber',
  };
  return `<span class="badge ${map[status]||'badge-gray'}">${status||'—'}</span>`;
}

// ── Category/Livestock Emoji ───────────────
function categoryEmoji(name) {
  const map = { Cattle:'🐄', Poultry:'🐓', Swine:'🐷', Goat:'🐐', Sheep:'🐑' };
  return map[name] || '🐾';
}

// ── Confirm Delete Dialog ──────────────────
function confirmDelete(message, onConfirm) {
  const overlay = document.getElementById('confirmModal');
  document.getElementById('confirmMessage').textContent = message;
  openModal('confirmModal');
  document.getElementById('confirmOkBtn').onclick = () => { closeModal('confirmModal'); onConfirm(); };
}

// ── Render Empty State ─────────────────────
function emptyRow(cols, message = 'No records found.') {
  return `<tr><td colspan="${cols}"><div class="empty-state">
    <div class="empty-icon">🌿</div>
    <h3>${message}</h3>
    <p>Try adjusting your search or add new records.</p>
  </div></td></tr>`;
}

// ── Loading Row ────────────────────────────
function loadingRow(cols) {
  return `<tr class="loading-row"><td colspan="${cols}"><div class="spinner"></div></td></tr>`;
}

// ── Pagination ─────────────────────────────
function renderPagination(containerId, current, total, onPage) {
  const el = document.getElementById(containerId);
  if (!el || total <= 1) { if(el) el.innerHTML=''; return; }
  let html = `<button class="page-btn" ${current===1?'disabled':''} onclick="(${onPage})(${current-1})">‹</button>`;
  for (let i = 1; i <= total; i++) {
    html += `<button class="page-btn ${i===current?'active':''}" onclick="(${onPage})(${i})">${i}</button>`;
  }
  html += `<button class="page-btn" ${current===total?'disabled':''} onclick="(${onPage})(${current+1})">›</button>`;
  el.innerHTML = html;
}

// ── Simple Client-side Search ──────────────
function filterTable(searchId, tableBodyId) {
  const q = document.getElementById(searchId)?.value.toLowerCase();
  document.querySelectorAll(`#${tableBodyId} tr`).forEach(row => {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// Init on load
document.addEventListener('DOMContentLoaded', () => { initSidebar(); setActiveNav(); populateNavUser(); });
