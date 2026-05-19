// ── API Configuration ──────────────────────
// Adjust this path to match your server setup
const API_BASE = 'http://localhost/livestockhub/livestock-ms/api';
const FRONTEND_BASE = '/livestockhub/livestock-ms/frontend';
const UPLOAD_BASE = '/livestockhub/livestock-ms/uploads';

// Expose on window so inline scripts and all pages can use them
window.API_BASE = API_BASE;
window.FRONTEND_BASE = FRONTEND_BASE;
window.UPLOAD_BASE = UPLOAD_BASE;
