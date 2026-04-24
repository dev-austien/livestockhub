<?php $navRole = 'buyer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marketplace — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">🛒 Marketplace</div><div class="page-subtitle">Browse available livestock for purchase</div></div>
  </div>

  <!-- Filters -->
  <div class="card" style="margin-bottom:20px;">
    <div class="card-body" style="padding:14px 20px;">
      <div class="toolbar" style="flex-wrap:wrap;gap:12px;">
        <div class="search-box" style="max-width:300px;">
          <span class="search-icon">🔍</span>
          <input type="text" id="searchInput" placeholder="Search tag, description…" oninput="applyFilters()">
        </div>
        <select class="form-control" id="catFilter" style="width:auto" onchange="applyFilters()">
          <option value="">All Categories</option>
        </select>
        <select class="form-control" id="genderFilter" style="width:auto" onchange="applyFilters()">
          <option value="">Any Gender</option>
          <option value="Male">Male</option>
          <option value="Female">Female</option>
        </select>
        <select class="form-control" id="sortFilter" style="width:auto" onchange="applyFilters()">
          <option value="newest">Newest First</option>
          <option value="price_asc">Price: Low to High</option>
          <option value="price_desc">Price: High to Low</option>
        </select>
        <button class="btn btn-secondary" onclick="clearFilters()">✕ Clear</button>
      </div>
    </div>
  </div>

  <!-- Results count -->
  <div style="margin-bottom:14px;color:var(--text-muted);font-size:.88rem" id="resultsCount"></div>

  <!-- Grid -->
  <div class="livestock-grid" id="marketGrid">
    <div style="grid-column:1/-1;text-align:center;padding:40px"><div class="spinner"></div></div>
  </div>
</div>
</div>

<!-- Order Modal -->
<div class="modal-overlay" id="orderModal">
  <div class="modal">
    <div class="modal-header">
      <span class="modal-title" id="orderModalTitle">Place Order</span>
      <button class="modal-close" onclick="closeModal('orderModal')">✕</button>
    </div>
    <div class="modal-body">
      <input type="hidden" id="orderLvId">
      <div id="orderLvInfo" style="background:var(--green-50);border-radius:10px;padding:14px;margin-bottom:16px;"></div>
      <div class="form-group">
        <label class="form-label">Order Type</label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
          <label style="cursor:pointer;border:2px solid var(--border);border-radius:10px;padding:14px;text-align:center;transition:all .18s;" id="typeBuyLabel">
            <input type="radio" name="orderType" value="Buy" style="display:none" onchange="selectType('Buy')">
            <div style="font-size:1.5rem">🛒</div>
            <div style="font-weight:700;margin-top:4px">Buy Now</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Immediate purchase</div>
          </label>
          <label style="cursor:pointer;border:2px solid var(--border);border-radius:10px;padding:14px;text-align:center;transition:all .18s;" id="typeReserveLabel">
            <input type="radio" name="orderType" value="Reserve" style="display:none" onchange="selectType('Reserve')">
            <div style="font-size:1.5rem">📌</div>
            <div style="font-weight:700;margin-top:4px">Reserve</div>
            <div style="font-size:.78rem;color:var(--text-muted)">Hold for 3 days</div>
          </label>
        </div>
      </div>
      <div id="orderSummary" style="background:var(--accent-light);border-radius:10px;padding:14px;display:none;">
        <div style="display:flex;justify-content:space-between;font-size:.9rem;color:var(--text);">
          <span>Unit Price</span><strong id="summaryPrice">—</strong>
        </div>
        <hr class="divider" style="margin:10px 0;">
        <div style="display:flex;justify-content:space-between;font-size:1.05rem;font-weight:700;">
          <span>Total</span><span id="summaryTotal" style="color:var(--accent-dark)">—</span>
        </div>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('orderModal')">Cancel</button>
      <button class="btn btn-primary" id="placeOrderBtn" onclick="placeOrder()" disabled>Place Order</button>
    </div>
  </div>
</div>

<!-- Detail Modal -->
<div class="modal-overlay" id="detailModal">
  <div class="modal modal-lg">
    <div class="modal-header">
      <span class="modal-title" id="detailTitle">Livestock Details</span>
      <button class="modal-close" onclick="closeModal('detailModal')">✕</button>
    </div>
    <div class="modal-body" id="detailBody"></div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
requireAuth(['Buyer']);
let allLivestock = [], selectedOrderType = null, selectedLivestock = null;

async function init() {
  const [lvRes, catRes] = await Promise.all([
    Api.get('/livestock?sale_status=Available'), Api.get('/categories')
  ]);
  allLivestock = lvRes.data || [];

  const catOpts = (catRes.data || []).map(c => `<option value="${c.category_id}">${categoryEmoji(c.category_name)} ${c.category_name}</option>`).join('');
  document.getElementById('catFilter').innerHTML = '<option value="">All Categories</option>' + catOpts;

  applyFilters();
}

function applyFilters() {
  const q      = document.getElementById('searchInput').value.toLowerCase();
  const cat    = document.getElementById('catFilter').value;
  const gender = document.getElementById('genderFilter').value;
  const sort   = document.getElementById('sortFilter').value;

  let filtered = allLivestock.filter(l => {
    const matchQ = !q || l.tag_number?.toLowerCase().includes(q) || l.description?.toLowerCase().includes(q);
    const matchC = !cat || l.category_id == cat;
    const matchG = !gender || l.gender === gender;
    return matchQ && matchC && matchG;
  });

  if (sort === 'price_asc')  filtered.sort((a, b) => a.price - b.price);
  if (sort === 'price_desc') filtered.sort((a, b) => b.price - a.price);

  document.getElementById('resultsCount').textContent = `Showing ${filtered.length} of ${allLivestock.length} livestock`;
  renderGrid(filtered);
}

function clearFilters() {
  document.getElementById('searchInput').value = '';
  document.getElementById('catFilter').value = '';
  document.getElementById('genderFilter').value = '';
  document.getElementById('sortFilter').value = 'newest';
  applyFilters();
}

function renderGrid(list) {
  const grid = document.getElementById('marketGrid');
  if (!list.length) {
    grid.innerHTML = `<div style="grid-column:1/-1" class="empty-state">
      <div class="empty-icon">🌿</div>
      <h3>No livestock found</h3>
      <p>Try adjusting your filters.</p>
    </div>`;
    return;
  }
  grid.innerHTML = list.map(l => `
    <div class="livestock-card">
      <div class="livestock-card-img">
        ${l.livestock_image ? `<img src="${API_BASE}/../${l.livestock_image}" alt="${l.tag_number}">` : categoryEmoji(l.category_name)}
      </div>
      <div class="livestock-card-body">
        <div class="livestock-card-tag"># ${l.tag_number}</div>
        <div class="livestock-card-name">${fmt(l.category_name)} — ${fmt(l.breed_name)}</div>
        <div class="livestock-card-meta">
          ${statusBadge(l.gender)} &nbsp;
          ${l.current_weight ? `<span class="tag">⚖️ ${fmtWeight(l.current_weight)}</span>` : ''}
        </div>
        <div class="livestock-card-meta" style="margin-top:4px">
          🏡 ${fmt(l.farm_name)} &nbsp; <span style="color:var(--text-muted)">· ${fmt(l.location_name)}</span>
        </div>
        <div class="livestock-card-price">${fmtMoney(l.price)}</div>
      </div>
      <div class="livestock-card-footer">
        <button class="btn btn-sm btn-secondary" onclick="viewDetail(${l.livestock_id})">👁 Details</button>
        <button class="btn btn-sm btn-primary" onclick="openOrder(${l.livestock_id})">🛒 Order</button>
      </div>
    </div>`).join('');
}

function viewDetail(id) {
  const l = allLivestock.find(x => x.livestock_id == id);
  document.getElementById('detailTitle').textContent = `${l.tag_number} — ${l.category_name}`;
  document.getElementById('detailBody').innerHTML = `
    <div style="display:flex;gap:20px;flex-wrap:wrap;">
      <div style="width:180px;height:160px;background:var(--green-100);border-radius:12px;display:grid;place-items:center;font-size:4rem;flex-shrink:0">
        ${l.livestock_image ? `<img src="${API_BASE}/../${l.livestock_image}" style="width:100%;height:100%;object-fit:cover;border-radius:12px">` : categoryEmoji(l.category_name)}
      </div>
      <div style="flex:1;min-width:220px">
        <div class="form-grid cols-2" style="gap:12px">
          <div><label class="form-label">Tag Number</label><p><strong>${l.tag_number}</strong></p></div>
          <div><label class="form-label">Category</label><p>${fmt(l.category_name)}</p></div>
          <div><label class="form-label">Breed</label><p>${fmt(l.breed_name)}</p></div>
          <div><label class="form-label">Gender</label><p>${statusBadge(l.gender)}</p></div>
          <div><label class="form-label">Weight</label><p>${fmtWeight(l.current_weight)}</p></div>
          <div><label class="form-label">Health</label><p><span class="tag">${fmt(l.health_status)}</span></p></div>
          <div><label class="form-label">Farm</label><p>${fmt(l.farm_name)}</p></div>
          <div><label class="form-label">Location</label><p>${fmt(l.location_name)}</p></div>
          <div><label class="form-label">Date of Birth</label><p>${fmtDate(l.date_of_birth)}</p></div>
          <div><label class="form-label">Date Listed</label><p>${fmtDate(l.date_created)}</p></div>
        </div>
        ${l.description ? `<div style="margin-top:12px"><label class="form-label">Description</label><p style="color:var(--text-muted)">${l.description}</p></div>` : ''}
      </div>
    </div>
    <hr class="divider">
    <div style="display:flex;align-items:center;justify-content:space-between;">
      <span style="font-size:1.4rem;font-weight:800;color:var(--accent-dark)">${fmtMoney(l.price)}</span>
      <button class="btn btn-primary" onclick="closeModal('detailModal');openOrder(${l.livestock_id})">🛒 Order Now</button>
    </div>`;
  openModal('detailModal');
}

function openOrder(id) {
  selectedLivestock = allLivestock.find(x => x.livestock_id == id);
  selectedOrderType = null;
  document.getElementById('orderLvId').value = id;
  document.getElementById('orderModalTitle').textContent = `Order — ${selectedLivestock.tag_number}`;
  document.getElementById('orderLvInfo').innerHTML = `
    <div style="display:flex;align-items:center;gap:14px">
      <span style="font-size:2.2rem">${categoryEmoji(selectedLivestock.category_name)}</span>
      <div>
        <div style="font-weight:700">${selectedLivestock.tag_number} — ${fmt(selectedLivestock.category_name)}</div>
        <div style="font-size:.83rem;color:var(--text-muted)">${fmt(selectedLivestock.breed_name)} · ${statusBadge(selectedLivestock.gender)} · ${fmtWeight(selectedLivestock.current_weight)}</div>
        <div style="font-size:.83rem;color:var(--text-muted)">🏡 ${fmt(selectedLivestock.farm_name)}</div>
      </div>
      <div style="margin-left:auto;font-size:1.2rem;font-weight:800;color:var(--accent-dark)">${fmtMoney(selectedLivestock.price)}</div>
    </div>`;
  document.getElementById('orderSummary').style.display = 'none';
  document.getElementById('placeOrderBtn').disabled = true;

  // Reset radio styles
  ['typeBuyLabel','typeReserveLabel'].forEach(id => {
    document.getElementById(id).style.borderColor = 'var(--border)';
    document.getElementById(id).style.background = '';
  });
  document.querySelectorAll('input[name="orderType"]').forEach(r => r.checked = false);
  openModal('orderModal');
}

function selectType(type) {
  selectedOrderType = type;
  document.getElementById('typeBuyLabel').style.borderColor    = type === 'Buy'     ? 'var(--green-500)' : 'var(--border)';
  document.getElementById('typeBuyLabel').style.background     = type === 'Buy'     ? 'var(--green-50)'  : '';
  document.getElementById('typeReserveLabel').style.borderColor = type === 'Reserve' ? 'var(--green-500)' : 'var(--border)';
  document.getElementById('typeReserveLabel').style.background  = type === 'Reserve' ? 'var(--green-50)'  : '';

  const price = parseFloat(selectedLivestock.price || 0);
  document.getElementById('summaryPrice').textContent = fmtMoney(price);
  document.getElementById('summaryTotal').textContent = fmtMoney(price);
  document.getElementById('orderSummary').style.display = '';
  document.getElementById('placeOrderBtn').disabled = false;
}

async function placeOrder() {
  if (!selectedOrderType) { toast('Please select an order type.', 'warning'); return; }
  const btn = document.getElementById('placeOrderBtn');
  btn.textContent = 'Placing…';
  btn.disabled = true;

  const res = await Api.post('/orders', {
    livestock_id: parseInt(document.getElementById('orderLvId').value),
    order_type:   selectedOrderType,
    quantity:     1,
  });

  if (res.ok) {
    toast(`Order placed! ${selectedOrderType === 'Reserve' ? 'Livestock reserved for 3 days.' : 'Awaiting farmer confirmation.'} 🎉`);
    closeModal('orderModal');
    // Remove from grid
    allLivestock = allLivestock.filter(l => l.livestock_id != selectedLivestock.livestock_id);
    applyFilters();
  } else {
    toast(res.message || 'Order failed.', 'error');
    btn.textContent = 'Place Order';
    btn.disabled = false;
  }
}

init();
</script>
</body>
</html>
