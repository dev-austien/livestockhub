function initReportPage(allowedRoles) {
  requireAuth(allowedRoles);
  let selectedUser = null;

  document.getElementById('searchInput').addEventListener('keydown', async e => {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    const q = e.target.value.trim();
    if (!q) return toast('Enter a search term', 'warning');

    const res = await Api.get('/reports/search?q=' + encodeURIComponent(q));
    if (!res.ok) {
      clearResult();
      toast(res.message || 'No user found', 'error');
      return;
    }
    selectedUser = res.data;
    renderResult(res.data);
  });

  window.clearResult = function() {
    selectedUser = null;
    document.getElementById('resultCard').style.display = 'none';
    document.getElementById('searchInput').value = '';
  };

  window.openReportModal = function() {
    if (!selectedUser) return;
    document.getElementById('reportDesc').value = '';
    document.getElementById('confirmReportBtn').disabled = true;
    openModal('reportModal');
  };

  document.getElementById('reportDesc').addEventListener('input', function() {
    document.getElementById('confirmReportBtn').disabled = !this.value.trim();
  });

  window.submitReport = async function() {
    const desc = document.getElementById('reportDesc').value.trim();
    if (!desc || !selectedUser) return;
    const res = await Api.post('/reports', {
      reported_user_id: selectedUser.user_id,
      description: desc,
    });
    if (res.ok) {
      closeModal('reportModal');
      clearResult();
      toast('Report submitted successfully');
    } else {
      toast(res.message || 'Failed to submit report', 'error');
    }
  };

  function renderResult(u) {
    const isFarmer = u.user_role === 'Farmer';
    let html = '<div class="form-grid cols-2">';
    html += '<div><strong>Name</strong><br>' + fmt(u.user_first_name) + ' ' + fmt(u.user_last_name) + '</div>';
    html += '<div><strong>Role</strong><br>' + u.user_role + '</div>';
    if (isFarmer) {
      html += '<div><strong>Farm Name</strong><br>' + fmt(u.farm_name) + '</div>';
      html += '<div><strong>Total Earning</strong><br>' + fmtMoney(u.total_earning) + '</div>';
    } else {
      html += '<div><strong>Total Spent</strong><br>' + fmtMoney(u.total_spent) + '</div>';
    }
    html += '<div><strong>Completed Orders</strong><br>' + (u.completed_orders ?? 0) + '</div></div>';
    document.getElementById('resultBody').innerHTML = html;
    document.getElementById('resultCard').style.display = 'block';
  }
}
