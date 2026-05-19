<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — LivestockHub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-box" style="max-width:520px;">
    <div class="auth-logo">
      <div class="logo-icon">🐄</div>
      <h1>LivestockHub</h1>
      <p>Create your account</p>
    </div>

    <div id="regError" class="toast error" style="display:none;position:relative;top:0;right:0;margin-bottom:14px;"></div>
    <div id="regSuccess" class="toast success" style="display:none;position:relative;top:0;right:0;margin-bottom:14px;"></div>

    <form id="registerForm">
      <div class="form-grid cols-2">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input class="form-control" id="firstName" name="first_name" placeholder="Juan" required>
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input class="form-control" id="lastName" name="last_name" placeholder="dela Cruz" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Middle Name</label>
        <input class="form-control" id="middleName" name="middle_name" placeholder="Optional">
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input class="form-control" id="username" name="username" placeholder="juandelacruz">
      </div>
      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" class="form-control" id="email" name="email" placeholder="juan@example.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input class="form-control" id="phone" name="phone" placeholder="09XXXXXXXXX">
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="password" class="form-control" id="password" name="password" placeholder="At least 6 characters" required>
      </div>
      <div class="form-group">
        <label class="form-label">I am a… *</label>
        <select class="form-control" id="role" name="role">
          <option value="Buyer">Buyer — I want to purchase livestock</option>
          <option value="Farmer">Farmer — I want to sell my livestock</option>
        </select>
      </div>
      <div class="form-group" id="farmNameGroup" style="display:none;">
        <label class="form-label">Farm Name</label>
        <input class="form-control" id="farmName" name="farm_name" placeholder="e.g. Dela Cruz Farm">
      </div>
      <div id="farmerDocsGroup" style="display:none;">
        <div class="form-group">
          <label class="form-label">Valid I.D (PNG) *</label>
          <input type="file" class="form-control" id="validId" name="valid_id" accept="image/png,.png">
        </div>
        <div class="form-group">
          <label class="form-label">Birth Certificate (PNG) *</label>
          <input type="file" class="form-control" id="birthCert" name="birth_cert" accept="image/png,.png">
        </div>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;" id="regBtn">
        Create Account
      </button>
    </form>

    <div class="auth-footer">
      Already have an account? <a href="login.php" class="auth-link">Sign in</a>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script>
  function toggleFarmerFields() {
    const isFarmer = document.getElementById('role').value === 'Farmer';
    document.getElementById('farmNameGroup').style.display = isFarmer ? 'block' : 'none';
    document.getElementById('farmerDocsGroup').style.display = isFarmer ? 'block' : 'none';
    document.getElementById('validId').required = isFarmer;
    document.getElementById('birthCert').required = isFarmer;
  }
  document.getElementById('role').addEventListener('change', toggleFarmerFields);
  toggleFarmerFields();

  document.getElementById('registerForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('regBtn');
    const errEl = document.getElementById('regError');
    const okEl  = document.getElementById('regSuccess');
    btn.textContent = 'Creating account…';
    btn.disabled = true;
    errEl.style.display = 'none';
    okEl.style.display = 'none';

    const role = document.getElementById('role').value;
    let res;

    if (role === 'Farmer') {
      const fd = new FormData(e.target);
      fd.set('role', 'Farmer');
      res = await Api.upload('/auth/register', fd);
    } else {
      res = await Api.post('/auth/register', {
        first_name:  document.getElementById('firstName').value,
        last_name:   document.getElementById('lastName').value,
        middle_name: document.getElementById('middleName').value,
        username:    document.getElementById('username').value,
        email:       document.getElementById('email').value,
        phone:       document.getElementById('phone').value,
        password:    document.getElementById('password').value,
        role:        'Buyer',
      });
    }

    if (res.ok) {
      if (role === 'Farmer' || res.data?.pending) {
        okEl.innerHTML = '<span class="toast-icon">✅</span><span class="toast-msg">Account creation pending. Wait for admin to approve.</span>';
        okEl.style.display = 'flex';
        e.target.reset();
        toggleFarmerFields();
      } else {
        window.location.href = 'login.php?registered=1';
      }
    } else {
      errEl.innerHTML = `<span class="toast-icon">❌</span><span class="toast-msg">${res.message || 'Registration failed.'}</span>`;
      errEl.style.display = 'flex';
    }
    btn.textContent = 'Create Account';
    btn.disabled = false;
  });
</script>
</body>
</html>
