<?php
// Detect role from URL path for nav rendering
$navRole = 'buyer'; // default; overridden by JS
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div><div class="page-title">👤 My Profile</div><div class="page-subtitle">Manage your account information</div></div>
  </div>

  <div style="display:grid;grid-template-columns:280px 1fr;gap:20px;align-items:start;flex-wrap:wrap;">

    <!-- Profile Card -->
    <div class="card">
      <div class="card-body" style="text-align:center;padding:28px 20px;">
        <div style="width:80px;height:80px;border-radius:50%;background:var(--green-700);display:grid;place-items:center;margin:0 auto 14px;font-size:2rem;font-weight:700;color:#fff;" id="profileAvatar">U</div>
        <div style="font-weight:700;font-size:1.1rem;margin-bottom:4px" id="profileName">—</div>
        <div id="profileRoleBadge"></div>
        <div style="font-size:.83rem;color:var(--text-muted);margin-top:8px" id="profileEmail">—</div>
        <div style="font-size:.83rem;color:var(--text-muted)" id="profilePhone">—</div>
        <hr class="divider">
        <div style="font-size:.8rem;color:var(--text-muted)">Member since</div>
        <div style="font-size:.85rem;font-weight:600" id="profileSince">—</div>
      </div>
    </div>

    <!-- Edit Forms -->
    <div style="display:flex;flex-direction:column;gap:18px;">

      <!-- Personal Info -->
      <div class="card">
        <div class="card-header"><span class="card-title">Personal Information</span></div>
        <div class="card-body">
          <div class="form-grid cols-2">
            <div class="form-group"><label class="form-label">First Name</label><input class="form-control" id="editFirst"></div>
            <div class="form-group"><label class="form-label">Last Name</label><input class="form-control" id="editLast"></div>
            <div class="form-group"><label class="form-label">Middle Name</label><input class="form-control" id="editMiddle"></div>
            <div class="form-group"><label class="form-label">Username</label><input class="form-control" id="editUsername"></div>
            <div class="form-group"><label class="form-label">Phone Number</label><input class="form-control" id="editPhone" placeholder="09XXXXXXXXX"></div>
            <div class="form-group"><label class="form-label">Email</label><input class="form-control" id="editEmail" disabled style="background:var(--surface-2)"></div>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:4px">
            <button class="btn btn-primary" onclick="saveProfile()">Save Changes</button>
          </div>
        </div>
      </div>

      <!-- Change Password -->
      <div class="card">
        <div class="card-header"><span class="card-title">Change Password</span></div>
        <div class="card-body">
          <div class="form-grid cols-2">
            <div class="form-group"><label class="form-label">New Password</label><input type="password" class="form-control" id="newPw" placeholder="At least 6 characters"></div>
            <div class="form-group"><label class="form-label">Confirm Password</label><input type="password" class="form-control" id="confirmPw" placeholder="Repeat new password"></div>
          </div>
          <div style="display:flex;justify-content:flex-end;margin-top:4px">
            <button class="btn btn-secondary" onclick="changePassword()">Update Password</button>
          </div>
        </div>
      </div>

      <!-- Farm Info (Farmer only) -->
      <div class="card" id="farmCard" style="display:none;">
        <div class="card-header"><span class="card-title">🏡 Farm Information</span></div>
        <div class="card-body">
          <div class="form-group"><label class="form-label">Farm Name</label><input class="form-control" id="editFarmName"></div>
          <div style="display:flex;justify-content:flex-end;margin-top:4px">
            <button class="btn btn-primary" onclick="saveFarm()">Save Farm</button>
          </div>
        </div>
      </div>

      <!-- Danger Zone -->
      <div class="card" style="border-color:#FECACA;">
        <div class="card-header" style="background:#FFF5F5;"><span class="card-title" style="color:var(--danger)">⚠️ Account</span></div>
        <div class="card-body">
          <p style="font-size:.88rem;color:var(--text-muted);margin-bottom:14px">Log out from this device or deactivate your account.</p>
          <div style="display:flex;gap:10px;">
            <button class="btn btn-danger" onclick="logout()">🚪 Logout</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script>
const authUser = requireAuth();
let farmerId = null;

// Fix nav role dynamically
document.getElementById('navRoleBadge').textContent = authUser.user_role;

async function load() {
  const res = await Api.get(`/users/${authUser.sub}`);
  if (!res.ok) { toast('Failed to load profile.', 'error'); return; }
  const u = res.data;

  const name = `${u.user_first_name || ''} ${u.user_last_name || ''}`.trim();
  document.getElementById('profileAvatar').textContent     = (name[0] || 'U').toUpperCase();
  document.getElementById('profileName').textContent       = name || u.username || '—';
  document.getElementById('profileRoleBadge').innerHTML    = statusBadge(u.user_role);
  document.getElementById('profileEmail').textContent      = u.user_email || '—';
  document.getElementById('profilePhone').textContent      = u.user_phone_number || 'No phone added';
  document.getElementById('profileSince').textContent      = fmtDate(u.created_at);

  document.getElementById('editFirst').value    = u.user_first_name    || '';
  document.getElementById('editLast').value     = u.user_last_name     || '';
  document.getElementById('editMiddle').value   = u.user_middle_name   || '';
  document.getElementById('editUsername').value = u.username           || '';
  document.getElementById('editPhone').value    = u.user_phone_number  || '';
  document.getElementById('editEmail').value    = u.user_email         || '';

  if (u.user_role === 'Farmer') {
    document.getElementById('farmCard').style.display = '';
    farmerId = u.farmer_id;
    document.getElementById('editFarmName').value = u.farm_name || '';
  }
}

async function saveProfile() {
  const body = {
    user_first_name:   document.getElementById('editFirst').value,
    user_last_name:    document.getElementById('editLast').value,
    user_middle_name:  document.getElementById('editMiddle').value,
    username:          document.getElementById('editUsername').value,
    user_phone_number: document.getElementById('editPhone').value,
  };
  const res = await Api.put(`/users/${authUser.sub}`, body);
  if (res.ok) { toast('Profile updated!'); load(); }
  else toast(res.message || 'Update failed.', 'error');
}

async function changePassword() {
  const pw  = document.getElementById('newPw').value;
  const cpw = document.getElementById('confirmPw').value;
  if (!pw) { toast('Please enter a new password.', 'warning'); return; }
  if (pw.length < 6) { toast('Password must be at least 6 characters.', 'warning'); return; }
  if (pw !== cpw)  { toast('Passwords do not match.', 'warning'); return; }

  const res = await Api.put(`/users/${authUser.sub}`, { password: pw });
  if (res.ok) {
    toast('Password changed! Please log in again.');
    setTimeout(() => { Api.clearSession(); window.location.href = '../../pages/auth/login.php'; }, 1500);
  } else toast(res.message || 'Failed.', 'error');
}

async function saveFarm() {
  if (!farmerId) { toast('No farm profile found.', 'error'); return; }
  const res = await Api.put(`/farmers/${farmerId}`, { farm_name: document.getElementById('editFarmName').value });
  if (res.ok) toast('Farm updated!');
  else toast(res.message || 'Failed.', 'error');
}

load();
</script>
</body>
</html>
