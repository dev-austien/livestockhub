<?php
/**
 * nav.php — shared navigation include
 * $navRole: 'admin' | 'farmer' | 'buyer'  (set in each page before including)
 * $navActive: current page slug (optional)
 */
$navRole = $navRole ?? 'buyer';
?>
<!-- Topbar -->
<header class="topbar">
  <button class="hamburger" id="hamburger" title="Toggle menu">☰</button>
  <div class="topbar-logo">
    <span>🐄</span>
    <span>LivestoChub</span>
  </div>
  <div class="topbar-spacer"></div>
  <div class="topbar-user">
    <div class="topbar-avatar" id="navAvatar">U</div>
    <div>
      <div style="font-size:.88rem;color:#fff;font-weight:600;" id="navUserName">User</div>
      <span class="topbar-role-badge" id="navRoleBadge"><?= ucfirst($navRole) ?></span>
    </div>
  </div>
</header>

<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">

  <?php if ($navRole === 'admin'): ?>
  <div class="sidebar-section">
    <div class="sidebar-label">Main</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="users.php"><span class="nav-icon">👥</span> Users</a>
      <a href="livestock.php"><span class="nav-icon">🐄</span> All Livestock</a>
    </nav>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Management</div>
    <nav class="sidebar-nav">
      <a href="categories.php"><span class="nav-icon">🗂️</span> Categories</a>
      <a href="breeds.php"><span class="nav-icon">🧬</span> Breeds</a>
      <a href="orders.php"><span class="nav-icon">📋</span> Orders</a>
      <a href="transactions.php"><span class="nav-icon">💰</span> Transactions</a>
    </nav>
  </div>

  <?php elseif ($navRole === 'farmer'): ?>
  <div class="sidebar-section">
    <div class="sidebar-label">My Farm</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="nav-icon">📊</span> Dashboard</a>
      <a href="mylivestock.php"><span class="nav-icon">🐄</span> My Livestock</a>
      <a href="myFarm.php"><span class="nav-icon">🏡</span> My Farm</a>
      <a href="weightLog.php"><span class="nav-icon">⚖️</span> Weight Log</a>
    </nav>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">Orders & Sales</div>
    <nav class="sidebar-nav">
      <a href="orderReceived.php"><span class="nav-icon">📦</span> Orders Received</a>
      <a href="transaction.php"><span class="nav-icon">💰</span> Transactions</a>
    </nav>
  </div>

  <?php elseif ($navRole === 'buyer'): ?>
  <div class="sidebar-section">
    <div class="sidebar-label">Browse</div>
    <nav class="sidebar-nav">
      <a href="dashboard.php"><span class="nav-icon">🏠</span> Dashboard</a>
      <a href="marketplace.php"><span class="nav-icon">🛒</span> Marketplace</a>
    </nav>
  </div>
  <div class="sidebar-section">
    <div class="sidebar-label">My Account</div>
    <nav class="sidebar-nav">
      <a href="myorders.php"><span class="nav-icon">📋</span> My Orders</a>
      <a href="paymenthistory.php"><span class="nav-icon">💳</span> Payment History</a>
      <a href="myprofile.php"><span class="nav-icon">👤</span> My Profile</a>
    </nav>
  </div>
  <?php endif; ?>

  <div class="sidebar-bottom">
    <button class="logout-btn" onclick="logout()">
      <span>🚪</span> Logout
    </button>
  </div>
</aside>

<!-- Global Confirm Modal -->
<div class="modal-overlay" id="confirmModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <span class="modal-title">Confirm Action</span>
      <button class="modal-close" onclick="closeModal('confirmModal')">✕</button>
    </div>
    <div class="modal-body">
      <div class="confirm-dialog">
        <div class="confirm-icon">⚠️</div>
        <h3>Are you sure?</h3>
        <p id="confirmMessage">This action cannot be undone.</p>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('confirmModal')">Cancel</button>
      <button class="btn btn-danger" id="confirmOkBtn">Confirm</button>
    </div>
  </div>
</div>
