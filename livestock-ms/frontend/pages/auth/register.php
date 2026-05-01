<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — LivestoChub</title>
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

    <form id="registerForm">
      <div class="form-grid cols-2">
        <div class="form-group">
          <label class="form-label">First Name *</label>
          <input class="form-control" id="firstName" placeholder="Juan" required>
        </div>
        <div class="form-group">
          <label class="form-label">Last Name *</label>
          <input class="form-control" id="lastName" placeholder="dela Cruz" required>
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Middle Name</label>
        <input class="form-control" id="middleName" placeholder="Optional">
      </div>
      <div class="form-group">
        <label class="form-label">Username</label>
        <input class="form-control" id="username" placeholder="juandelacruz">
      </div>
      <div class="form-group">
        <label class="form-label">Email Address *</label>
        <input type="email" class="form-control" id="email" placeholder="juan@example.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Phone Number</label>
        <input class="form-control" id="phone" placeholder="09XXXXXXXXX">
      </div>
      <div class="form-group">
        <label class="form-label">Password *</label>
        <input type="password" class="form-control" id="password" placeholder="At least 6 characters" required>
      </div>
      <div class="form-group">
        <label class="form-label">I am a… *</label>
        <select class="form-control" id="role">
          <option value="Buyer">Buyer — I want to purchase livestock</option>
          <option value="Farmer">Farmer — I want to sell my livestock</option>
        </select>
      </div>
      <div class="form-group" id="farmNameGroup" style="display:none;">
        <label class="form-label">Farm Name</label>
        <input class="form-control" id="farmName" placeholder="e.g. Dela Cruz Farm">
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
  document.getElementById('role').addEventListener('change', function() {
    document.getElementById('farmNameGroup').style.display = this.value === 'Farmer' ? 'block' : 'none';
  });

  document.getElementById('registerForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('regBtn');
    const errEl = document.getElementById('regError');
    btn.textContent = 'Creating account…';
    btn.disabled = true;
    errEl.style.display = 'none';

    const res = await Api.post('/auth/register', {
      first_name:  document.getElementById('firstName').value,
      last_name:   document.getElementById('lastName').value,
      middle_name: document.getElementById('middleName').value,
      username:    document.getElementById('username').value,
      email:       document.getElementById('email').value,
      phone:       document.getElementById('phone').value,
      password:    document.getElementById('password').value,
      role:        document.getElementById('role').value,
      farm_name:   document.getElementById('farmName').value,
    });

    if (res.ok) {
      window.location.href = 'login.php?registered=1';
    } else {
      errEl.innerHTML = `<span class="toast-icon">❌</span><span class="toast-msg">${res.message || 'Registration failed.'}</span>`;
      errEl.style.display = 'flex';
      btn.textContent = 'Create Account';
      btn.disabled = false;
    }
  });
</script>
</body>
</html>
