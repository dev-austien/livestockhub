<?php
// nav.php — include this at the top of every farmer page
// Requires: $page_title (string), $current_page (string matching ag-nav href keys)

$username     = $_SESSION['username']     ?? 'User';
$display_name = $_SESSION['display_name'] ?? $username;

// Build initials (max 2 chars)
$words    = array_filter(explode(' ', trim($display_name)));
$initials = '';
foreach ($words as $w) { $initials .= strtoupper($w[0]); }
$initials = substr($initials, 0, 2);

$nav_items = [
  ['href' => 'dashboard.php',     'label' => 'Dashboard',       'sub' => 'Overview',        'page' => 'dashboard'],
  ['href' => 'mylivestock.php',   'label' => 'My Livestock',    'sub' => 'All animals',     'page' => 'mylivestock'],
  ['href' => 'addAnimals.php',    'label' => 'Add Animals',     'sub' => 'Register new',    'page' => 'addAnimals'],
  ['href' => 'weightLog.php',     'label' => 'Weight Log',      'sub' => 'Track & compare', 'page' => 'weightLog'],
  ['href' => 'myFarm.php',        'label' => 'My Farm',         'sub' => 'Farm details',    'page' => 'myFarm'],
  ['href' => 'orderRecieved.php', 'label' => 'Orders Received', 'sub' => 'Pending orders',  'page' => 'orderRecieved'],
  ['href' => 'transaction.php',   'label' => 'Transactions',    'sub' => 'Revenue history', 'page' => 'transaction'],
];

// SVG icons keyed by page slug
$icons = [
  'dashboard'     => '<rect x="2" y="2" width="5" height="5" rx="1.5" fill="currentColor"/><rect x="9" y="2" width="5" height="5" rx="1.5" fill="currentColor" opacity=".5"/><rect x="2" y="9" width="5" height="5" rx="1.5" fill="currentColor" opacity=".5"/><rect x="9" y="9" width="5" height="5" rx="1.5" fill="currentColor" opacity=".3"/>',
  'mylivestock'   => '<circle cx="8" cy="5.5" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M2 13.5c0-2.5 2.686-4 6-4s6 1.5 6 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
  'addAnimals'    => '<rect x="2" y="2" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M8 5.5v5M5.5 8h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
  'weightLog'     => '<circle cx="8" cy="8" r="5.5" stroke="currentColor" stroke-width="1.5"/><path d="M8 5.5v3l1.5 1.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
  'myFarm'        => '<rect x="2" y="2" width="12" height="12" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M5 5.5h6M5 8h6M5 10.5h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>',
  'orderRecieved' => '<path d="M3 3h10l-1 7H4L3 3z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/><circle cx="6.5" cy="13" r="1" fill="currentColor"/><circle cx="10" cy="13" r="1" fill="currentColor"/>',
  'transaction'   => '<path d="M2 8h12" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M10 5l3 3-3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 5L3 8l3 3" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>',
];
?>
<header class="ag-topbar">
    <div class="ag-logo">Agri<em>Hub</em></div>

    <nav class="ag-breadcrumb" aria-label="breadcrumb">
        <span class="ag-breadcrumb-item">Portal</span>
        <span class="ag-breadcrumb-sep">/</span>
        <span class="ag-breadcrumb-item active"><?= htmlspecialchars($page_title) ?></span>
    </nav>

    <div class="ag-topbar-actions">
        <button class="ag-notif-btn" title="Notifications" aria-label="Notifications">
            <svg width="16" height="16" fill="none" viewBox="0 0 16 16">
                <path d="M8 2a4.5 4.5 0 0 0-4.5 4.5c0 2-.5 3-1 3.5h11c-.5-.5-1-1.5-1-3.5A4.5 4.5 0 0 0 8 2z"
                    stroke="currentColor" stroke-width="1.4" />
                <path d="M6.5 12a1.5 1.5 0 0 0 3 0" stroke="currentColor" stroke-width="1.4" />
            </svg>
            <span class="ag-notif-badge"></span>
        </button>

        <div class="ag-avatar" title="<?= htmlspecialchars($display_name) ?>"><?= htmlspecialchars($initials) ?></div>

        <button class="ag-burger" id="ag-burger" aria-label="Toggle navigation" aria-expanded="false">
            <span></span><span></span><span></span>
        </button>
    </div>
</header>

<div class="ag-drawer-overlay" id="ag-overlay"></div>

<div class="ag-drawer" id="ag-drawer" role="navigation" aria-label="Main navigation">
    <div class="ag-drawer-grid">
        <?php foreach ($nav_items as $item): ?>
        <a href="<?= $item['href'] ?>" class="ag-nav-item<?= ($current_page === $item['page']) ? ' active' : '' ?>">
            <div class="ag-nav-icon"
                style="color:<?= ($current_page === $item['page']) ? 'var(--accent-dim)' : 'rgba(255,255,255,0.3)' ?>">
                <svg width="15" height="15" fill="none" viewBox="0 0 16 16">
                    <?= $icons[$item['page']] ?>
                </svg>
            </div>
            <div class="ag-nav-label"><?= $item['label'] ?></div>
            <div class="ag-nav-sub"><?= $item['sub'] ?></div>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<script>
(function() {
    var burger = document.getElementById('ag-burger');
    var drawer = document.getElementById('ag-drawer');
    var overlay = document.getElementById('ag-overlay');

    function toggle(force) {
        var open = force !== undefined ? force : !drawer.classList.contains('open');
        burger.classList.toggle('open', open);
        drawer.classList.toggle('open', open);
        overlay.classList.toggle('open', open);
        burger.setAttribute('aria-expanded', open);
    }

    burger.addEventListener('click', function() {
        toggle();
    });
    overlay.addEventListener('click', function() {
        toggle(false);
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') toggle(false);
    });
})();
</script>