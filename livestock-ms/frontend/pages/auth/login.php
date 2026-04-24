<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — LivestoChub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<div class="auth-page">
  <div class="auth-box">
    <div class="auth-logo">
      <div class="logo-icon">🐄</div>
      <h1>LivestoChub</h1>
      <p>Livestock Management System</p>
    </div>

    <h2 class="auth-title">Welcome back</h2>
    <p class="auth-subtitle">Sign in to your account to continue</p>

    <div id="loginError" class="toast error" style="display:none;position:relative;top:0;right:0;margin-bottom:14px;"></div>

    <form id="loginForm">
      <div class="form-group">
        <label class="form-label">Email Address</label>
        <input type="email" class="form-control" id="loginEmail" placeholder="you@example.com" required>
      </div>
      <div class="form-group">
        <label class="form-label">Password</label>
        <input type="password" class="form-control" id="loginPassword" placeholder="••••••••" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px;" id="loginBtn">
        Sign In
      </button>
    </form>

    <div class="auth-footer">
      Don't have an account? <a href="register.php" class="auth-link">Register here</a>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script>
  // Redirect if already logged in
  if (Api.getToken() && Api.getUser()) {
    const u = Api.getUser();
    const map = { Admin: 'admin', Farmer: 'farmer', Buyer: 'buyer' };
    window.location.href = `../${map[u.user_role]}/dashboard.php`;
  }

  document.getElementById('loginForm').addEventListener('submit', async e => {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    const errEl = document.getElementById('loginError');
    btn.textContent = 'Signing in…';
    btn.disabled = true;
    errEl.style.display = 'none';

    const res = await Api.post('/auth/login', {
      email:    document.getElementById('loginEmail').value,
      password: document.getElementById('loginPassword').value,
    });

    if (res.ok && res.data?.token) {
      Api.setSession(res.data.token, res.data.user);
      const roleMap = { Admin: 'admin', Farmer: 'farmer', Buyer: 'buyer' };
      window.location.href = `../${roleMap[res.data.user.user_role]}/dashboard.php`;
    } else {
      errEl.innerHTML = `<span class="toast-icon">❌</span><span class="toast-msg">${res.message || 'Login failed.'}</span>`;
      errEl.style.display = 'flex';
      btn.textContent = 'Sign In';
      btn.disabled = false;
    }
  });
</script>
</body>
</html>
