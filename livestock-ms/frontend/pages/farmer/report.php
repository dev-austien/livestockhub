<?php $navRole = 'farmer'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report — LivestockHub</title>
  <link rel="stylesheet" href="../../css/main.css">
</head>
<body>
<?php include '../../includes/nav.php'; ?>
<div class="app-layout">
<div class="main-content" id="mainContent">
  <div class="page-header">
    <div>
      <div class="page-title">Report</div>
      <div class="page-subtitle">Search and report a farmer or buyer</div>
    </div>
  </div>

  <div class="card" style="margin-bottom:20px;">
    <div class="card-body">
      <div class="search-box" style="max-width:100%;">
        <span class="search-icon">&#128269;</span>
        <input type="text" id="searchInput" placeholder="Farm name, farmer name, email, or buyer name" autocomplete="off">
      </div>
      <p style="margin-top:8px;font-size:.85rem;color:var(--text-muted)">Press Enter to search</p>
    </div>
  </div>

  <div id="resultCard" class="card" style="display:none;max-width:520px;">
    <div class="card-header"><span class="card-title">User Details</span></div>
    <div class="card-body" id="resultBody"></div>
    <div class="card-footer" style="display:flex;justify-content:flex-end;gap:10px;padding:14px 20px;">
      <button class="btn btn-secondary" onclick="clearResult()">Cancel</button>
      <button class="btn btn-danger" onclick="openReportModal()">Report</button>
    </div>
  </div>
</div>
</div>

<div class="modal-overlay" id="reportModal">
  <div class="modal modal-sm">
    <div class="modal-header">
      <span class="modal-title">Report Reason</span>
      <button class="modal-close" onclick="closeModal('reportModal')">X</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label">Description *</label>
        <textarea class="form-control" id="reportDesc" rows="4" placeholder="Describe the reason for this report"></textarea>
      </div>
    </div>
    <div class="modal-footer" style="justify-content:flex-end;">
      <button class="btn btn-secondary" onclick="closeModal('reportModal')">Cancel</button>
      <button class="btn btn-danger" id="confirmReportBtn" disabled onclick="submitReport()">Confirm</button>
    </div>
  </div>
</div>

<script src="../../js/config.js"></script>
<script src="../../js/api/client.js"></script>
<script src="../../js/utils/helpers.js"></script>
<script src="../shared/report-page.js"></script>
<script>initReportPage(['Farmer']);</script>
</body>
</html>
