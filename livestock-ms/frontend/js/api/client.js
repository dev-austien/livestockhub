// ── API Client ─────────────────────────────
const Api = (() => {
  function getToken() { return localStorage.getItem('lh_token'); }
  function getUser()  { return JSON.parse(localStorage.getItem('lh_user') || 'null'); }
  function setSession(token, user) {
    localStorage.setItem('lh_token', token);
    localStorage.setItem('lh_user', JSON.stringify(user));
  }
  function clearSession() {
    localStorage.removeItem('lh_token');
    localStorage.removeItem('lh_user');
  }

  async function request(method, endpoint, body = null, isForm = false) {
    const headers = {};
    const token = getToken();
    if (token) headers['Authorization'] = `Bearer ${token}`;
    if (!isForm) headers['Content-Type'] = 'application/json';

    const opts = { method, headers };
    if (body) opts.body = isForm ? body : JSON.stringify(body);

    try {
      const res = await fetch(`${API_BASE}${endpoint}`, opts);
      const data = await res.json();
      return { ok: res.ok, status: res.status, ...data };
    } catch (err) {
      return { ok: false, message: 'Network error. Please try again.' };
    }
  }

  return {
    getToken, getUser, setSession, clearSession,
    get:    (ep)           => request('GET',    ep),
    post:   (ep, body)     => request('POST',   ep, body),
    put:    (ep, body)     => request('PUT',    ep, body),
    patch:  (ep, body)     => request('PATCH',  ep, body),
    del:    (ep)           => request('DELETE', ep),
    upload: (ep, formData) => request('POST',   ep, formData, true),
    uploadPut: (ep, formData) => request('PUT', ep, formData, true),
  };
})();

// ── Auth Guard ─────────────────────────────
function requireAuth(allowedRoles = null) {
  const token = Api.getToken();
  const user  = Api.getUser();
  if (!token || !user) {
    const depth = location.pathname.split('/').length - 1;
    const loginPath = '../'.repeat(Math.max(0, depth - 4)) + 'pages/auth/login.php';
    window.location.href = FRONTEND_BASE + '/pages/auth/login.php';
    return null;
  }
  if (allowedRoles && !allowedRoles.includes(user.user_role)) {
    const roleMap = {
      Admin:  FRONTEND_BASE + '/pages/admin/dashboard.php',
      Farmer: FRONTEND_BASE + '/pages/farmer/dashboard.php',
      Buyer:  FRONTEND_BASE + '/pages/buyer/dashboard.php',
    };
    window.location.href = roleMap[user.user_role] || FRONTEND_BASE + '/pages/auth/login.php';
    return null;
  }
  return user;
}

function logout() {
  Api.clearSession();
  window.location.href = FRONTEND_BASE + '/pages/auth/login.php';
}
