<?php
if (!file_exists(__DIR__ . '/../.env') && file_exists(__DIR__ . '/../install.php')) {
    header('Location: ../install.php');
    exit;
}
if (file_exists(__DIR__ . '/../install.php')) {
    http_response_code(403);
    die('<div style="padding:40px;text-align:center;font-family:sans-serif;color:#1A2B4A;margin-top:100px;"><h2>Security Warning</h2><p>The installation was successful, but the <strong>install.php</strong> file still exists.</p><p>You must delete <strong>install.php</strong> from your server before you can access the admin panel.</p></div>');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>HSI Admin — Content Management</title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --navy:#1A2B4A;--teal:#0D7B6E;--teal-l:#0FA08F;--red:#C8102E;
  --gray-50:#F8F9FA;--gray-100:#F1F3F5;--gray-200:#E9ECEF;
  --gray-400:#ADB5BD;--gray-600:#6C757D;--gray-800:#343A40;
  --white:#fff;--font:'Inter',system-ui,sans-serif;
  --shadow:0 2px 8px rgba(0,0,0,.1);--shadow-lg:0 8px 32px rgba(0,0,0,.15);
  --radius:6px;--tr:.2s ease;
}
body{font-family:var(--font);background:var(--gray-100);color:var(--gray-800);font-size:14px;line-height:1.5}
a{color:inherit;text-decoration:none}
button{font-family:inherit;cursor:pointer}
input,textarea,select{font-family:inherit;font-size:14px}

/* ── Layout ── */
#app{display:flex;min-height:100vh}

/* ── Sidebar ── */
.sidebar{
  width:240px;flex-shrink:0;background:var(--navy);color:rgba(255,255,255,.85);
  display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;
  overflow-y:auto;
}
.sidebar-logo{padding:20px 20px 16px;border-bottom:1px solid rgba(255,255,255,.1)}
.sidebar-logo h2{font-size:15px;font-weight:800;color:#fff;line-height:1.2}
.sidebar-logo p{font-size:11px;opacity:.5;margin-top:3px;font-style:italic}
.sidebar-user{padding:12px 20px;font-size:12px;opacity:.5;border-bottom:1px solid rgba(255,255,255,.08)}

.nav-group{padding:16px 0 8px}
.nav-label{padding:4px 20px;font-size:10px;font-weight:700;letter-spacing:1.5px;text-transform:uppercase;opacity:.4;margin-bottom:4px}
.nav-item{
  display:flex;align-items:center;gap:10px;padding:9px 20px;
  font-size:13px;font-weight:500;cursor:pointer;transition:background var(--tr);
  border-left:3px solid transparent;
}
.nav-item:hover{background:rgba(255,255,255,.07)}
.nav-item.active{background:rgba(13,123,110,.35);border-left-color:var(--teal-l);color:#fff;font-weight:600}
.nav-item .ni-icon{font-size:16px;width:20px;text-align:center;flex-shrink:0}

.sidebar-footer{margin-top:auto;padding:16px 20px;border-top:1px solid rgba(255,255,255,.1)}
#btn-logout{width:100%;padding:9px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.15);color:rgba(255,255,255,.7);border-radius:var(--radius);font-size:13px;transition:background var(--tr)}
#btn-logout:hover{background:rgba(255,255,255,.15);color:#fff}

/* ── Main ── */
.main{margin-left:240px;flex:1;display:flex;flex-direction:column;min-height:100vh}

.topbar{
  background:var(--white);border-bottom:1px solid var(--gray-200);
  padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between;
  position:sticky;top:0;z-index:50;
}
.topbar h1{font-size:17px;font-weight:700;color:var(--navy)}
.topbar-actions{display:flex;align-items:center;gap:10px}

.content{padding:32px;flex:1}

/* ── Login ── */
#login-screen{
  position:fixed;inset:0;background:var(--navy);display:flex;align-items:center;justify-content:center;z-index:9999;
}
.login-card{background:var(--white);border-radius:12px;padding:40px 36px;width:360px;box-shadow:var(--shadow-lg)}
.login-logo{text-align:center;margin-bottom:28px}
.login-logo h2{font-size:20px;font-weight:800;color:var(--navy)}
.login-logo p{font-size:13px;color:var(--gray-600);margin-top:4px}
.login-error{background:#FEE2E2;color:#991B1B;padding:10px 14px;border-radius:var(--radius);font-size:13px;margin-bottom:16px;display:none}

/* ── Forms ── */
.form-group{margin-bottom:18px}
.form-group label{display:block;font-weight:600;font-size:13px;color:var(--gray-800);margin-bottom:6px}
.form-group .hint{font-size:11px;color:var(--gray-400);margin-top:4px}
.form-group input[type=text],
.form-group input[type=email],
.form-group input[type=password],
.form-group input[type=url],
.form-group input[type=number],
.form-group textarea,
.form-group select{
  width:100%;padding:9px 12px;border:1.5px solid var(--gray-200);
  border-radius:var(--radius);outline:none;transition:border-color var(--tr);
  background:var(--white);color:var(--gray-800);resize:vertical;
}
.form-group input:focus,.form-group textarea:focus,.form-group select:focus{border-color:var(--teal)}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px}

/* ── Buttons ── */
.btn{display:inline-flex;align-items:center;gap:6px;padding:9px 18px;border-radius:var(--radius);font-size:13px;font-weight:600;border:1.5px solid transparent;transition:all var(--tr);cursor:pointer}
.btn-primary{background:var(--teal);color:#fff;border-color:var(--teal)}
.btn-primary:hover{background:var(--teal-l);border-color:var(--teal-l)}
.btn-secondary{background:var(--white);color:var(--navy);border-color:var(--gray-200)}
.btn-secondary:hover{border-color:var(--navy)}
.btn-danger{background:#FEE2E2;color:var(--red);border-color:#FECACA}
.btn-danger:hover{background:var(--red);color:#fff}
.btn-sm{padding:6px 12px;font-size:12px}

/* ── Cards / Sections ── */
.page-section{background:var(--white);border-radius:var(--radius);border:1px solid var(--gray-200);margin-bottom:24px;overflow:hidden}
.section-header{padding:16px 20px;border-bottom:1px solid var(--gray-100);display:flex;align-items:center;justify-content:space-between;background:var(--gray-50)}
.section-header h3{font-size:14px;font-weight:700;color:var(--navy)}
.section-body{padding:20px}

/* ── Save bar ── */
.save-bar{
  position:sticky;bottom:0;left:0;right:0;background:var(--white);
  border-top:1px solid var(--gray-200);padding:12px 32px;
  display:flex;align-items:center;justify-content:space-between;
  z-index:50;box-shadow:0 -4px 12px rgba(0,0,0,.06);
}
.save-status{font-size:13px;color:var(--gray-600)}
.save-status.saved{color:#15803D}
.save-status.saving{color:var(--teal)}
.save-status.error{color:var(--red)}

/* ── Table ── */
.data-table{width:100%;border-collapse:collapse}
.data-table th{text-align:left;padding:10px 12px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--gray-400);border-bottom:2px solid var(--gray-200);background:var(--gray-50)}
.data-table td{padding:12px;border-bottom:1px solid var(--gray-100);vertical-align:middle}
.data-table tr:last-child td{border-bottom:none}
.data-table tr:hover td{background:var(--gray-50)}

/* ── Badges ── */
.badge{display:inline-block;padding:3px 9px;border-radius:100px;font-size:11px;font-weight:600}
.badge-green{background:#D1FAE5;color:#065F46}
.badge-gray{background:var(--gray-200);color:var(--gray-600)}
.badge-red{background:#FEE2E2;color:#991B1B}

/* ── Modal ── */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:200;display:flex;align-items:center;justify-content:center;padding:20px}
.modal{background:#fff;border-radius:10px;width:100%;max-width:600px;max-height:90vh;overflow-y:auto;box-shadow:var(--shadow-lg)}
.modal-header{padding:20px 24px;border-bottom:1px solid var(--gray-200);display:flex;align-items:center;justify-content:space-between}
.modal-header h3{font-size:16px;font-weight:700;color:var(--navy)}
.modal-close{background:none;border:none;font-size:20px;color:var(--gray-400);line-height:1}
.modal-close:hover{color:var(--gray-800)}
.modal-body{padding:24px}
.modal-footer{padding:16px 24px;border-top:1px solid var(--gray-200);display:flex;justify-content:flex-end;gap:10px}

/* ── Toast ── */
#toast{
  position:fixed;bottom:24px;right:24px;z-index:9999;
  background:var(--navy);color:#fff;padding:12px 20px;
  border-radius:var(--radius);font-size:13px;font-weight:600;
  transform:translateY(80px);opacity:0;transition:all .3s ease;
  box-shadow:var(--shadow-lg);
}
#toast.show{transform:translateY(0);opacity:1}
#toast.success{background:#065F46}
#toast.error{background:var(--red)}

/* ── Stats ── */
.stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
.stat-card{background:var(--white);border:1px solid var(--gray-200);border-radius:var(--radius);padding:20px 22px}
.stat-card .sv{font-size:28px;font-weight:800;color:var(--navy);line-height:1}
.stat-card .sl{font-size:12px;color:var(--gray-600);margin-top:4px}

/* ── Image preview ── */
.img-preview{width:80px;height:54px;object-fit:cover;border-radius:4px;border:1px solid var(--gray-200);background:var(--gray-100)}

/* Image upload widget */
.img-upload-widget{
  position:relative;width:100%;height:200px;border:2px dashed var(--gray-200);border-radius:8px;
  overflow:hidden;cursor:pointer;background:var(--gray-50);transition:border-color .2s;
}
.img-upload-widget:hover,.img-upload-widget.drag-over{border-color:var(--teal)}
.img-upload-widget.drag-over{background:rgba(13,123,110,.05)}
.img-upload-preview{width:100%;height:100%;object-fit:cover;display:block}
.img-upload-placeholder{
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  height:100%;gap:8px;color:var(--gray-400);pointer-events:none;
}
.img-upload-icon{font-size:32px}
.img-upload-placeholder span:nth-child(2){font-size:14px;font-weight:500;color:var(--gray-600)}
.img-upload-hint{font-size:12px}
.img-upload-overlay{
  position:absolute;inset:0;background:rgba(0,0,0,.5);color:#fff;
  display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:600;
  opacity:0;transition:opacity .2s;pointer-events:none;
}
.img-upload-widget:hover .img-upload-overlay{opacity:1}
.img-uploading .img-upload-overlay{opacity:1}
.img-uploading .img-upload-overlay::after{content:' Uploading…'}

/* ── Toggle ── */
.toggle{display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:500}
.toggle input{display:none}
.toggle-track{width:36px;height:20px;background:var(--gray-200);border-radius:10px;position:relative;transition:background var(--tr)}
.toggle input:checked+.toggle-track{background:var(--teal)}
.toggle-track::after{content:'';position:absolute;width:16px;height:16px;background:#fff;border-radius:50%;top:2px;left:2px;transition:transform var(--tr);box-shadow:0 1px 3px rgba(0,0,0,.2)}
.toggle input:checked+.toggle-track::after{transform:translateX(16px)}

@media(max-width:768px){
  .sidebar{transform:translateX(-100%)}
  .main{margin-left:0}
  .stats-row{grid-template-columns:1fr 1fr}
}

/* ── Quill editor ── */
.ql-toolbar { border: 1px solid var(--gray-200) !important; border-radius: var(--radius) var(--radius) 0 0 !important; background: var(--gray-50); }
.ql-container { border: 1px solid var(--gray-200) !important; border-top: none !important; border-radius: 0 0 var(--radius) var(--radius) !important; font-family: var(--font) !important; font-size: 14px !important; min-height: 200px; }
.ql-editor { min-height: 200px; }
</style>
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
</head>
<body>

<!-- Login Screen -->
<div id="login-screen">
  <div class="login-card">
    <div class="login-logo">
      <h2>⊕ HSI Admin</h2>
      <p>Content Management System</p>
    </div>
    <div class="login-error" id="login-error"></div>
    <form id="login-form">
      <div class="form-group">
        <label>Username</label>
        <input type="text" id="login-user" required />
      </div>
      <div class="form-group">
        <label>Password</label>
        <input type="password" id="login-pass" required />
      </div>
      <!-- Honeypot for bots -->
      <div style="display:none" aria-hidden="true">
        <label>Website Name</label>
        <input type="text" id="login-website" tabindex="-1" autocomplete="off" />
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;padding:11px">Sign In</button>
    </form>
  </div>
</div>

<!-- Admin App -->
<div id="app" style="display:none">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-logo">
      <h2>⊕ HSI Admin</h2>
      <p>Content Management System</p>
    </div>
    <div class="sidebar-user" id="sidebar-user"></div>
    <nav>
      <div class="nav-group">
        <div class="nav-label">Overview</div>
        <div class="nav-item active" data-section="dashboard"><span class="ni-icon">📊</span> Dashboard</div>
      </div>
      <div class="nav-group">
        <div class="nav-label">Page Content</div>
        <div class="nav-item" data-section="home"><span class="ni-icon">🏠</span> Homepage</div>
        <div class="nav-item" data-section="about"><span class="ni-icon">ℹ️</span> About Us</div>
        <div class="nav-item" data-section="approach"><span class="ni-icon">🔬</span> Our Approach</div>
        <div class="nav-item" data-section="projects"><span class="ni-icon">📋</span> Programmes</div>
        <div class="nav-item" data-section="nicu"><span class="ni-icon">🏥</span> NICU Network</div>
        <div class="nav-item" data-section="getinvolved"><span class="ni-icon">🤝</span> Get Involved</div>
        <div class="nav-item" data-section="contact"><span class="ni-icon">📬</span> Contact</div>
      </div>
      <div class="nav-group">
        <div class="nav-label">Data</div>
        <div class="nav-item" data-section="team"><span class="ni-icon">👥</span> Team Members</div>
        <div class="nav-item" data-section="news"><span class="ni-icon">📰</span> News &amp; Articles</div>
        <div class="nav-item" data-section="messages"><span class="ni-icon">✉️</span> Messages <span id="msg-badge" style="display:none;background:var(--red);color:#fff;border-radius:100px;padding:1px 7px;font-size:10px;font-weight:700;margin-left:4px"></span></div>
      </div>
      <div class="nav-group">
        <div class="nav-label">Configuration</div>
        <div class="nav-item" data-section="map"><span class="ni-icon">🗺️</span> Where We Work</div>
        <div class="nav-item" data-section="settings"><span class="ni-icon">⚙️</span> Settings</div>
      </div>
    </nav>
    <div class="sidebar-footer">
      <button id="btn-logout">Sign Out</button>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">
    <div class="topbar">
      <h1 id="topbar-title">Dashboard</h1>
      <div class="topbar-actions">
        <a href="../" target="_blank" class="btn btn-secondary btn-sm">👁 View Site</a>
      </div>
    </div>

    <div class="content" id="content-area"></div>
  </div>
</div>

<!-- Modal -->
<div class="modal-bg" id="modal" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3 id="modal-title">Edit</h3>
      <button class="modal-close" id="modal-close">✕</button>
    </div>
    <div class="modal-body" id="modal-body"></div>
    <div class="modal-footer" id="modal-footer"></div>
  </div>
</div>

<!-- Toast -->
<div id="toast"></div>

<script>
const API = '../api/admin';
let currentSection = 'dashboard';
let allContent = {};
let pendingChanges = {};

// ── Helpers ──────────────────────────────────────────────────────────────────
const API_BASE = '../api';

async function apiFetch(path, opts = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    headers: { 'Content-Type': 'application/json' },
    credentials: 'include',
    ...opts,
  });
  return res.json();
}

function toast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.className = `show ${type}`;
  setTimeout(() => t.className = '', 2800);
}

function showModal(title, bodyHTML, footerHTML) {
  document.getElementById('modal-title').textContent = title;
  document.getElementById('modal-body').innerHTML = bodyHTML;
  document.getElementById('modal-footer').innerHTML = footerHTML;
  document.getElementById('modal').style.display = 'flex';
}

function hideModal() { document.getElementById('modal').style.display = 'none'; }

function el(tag, attrs = {}, ...children) {
  const e = document.createElement(tag);
  Object.entries(attrs).forEach(([k, v]) => { if (k === 'class') e.className = v; else e.setAttribute(k, v); });
  children.forEach(c => typeof c === 'string' ? e.insertAdjacentHTML('beforeend', c) : e.appendChild(c));
  return e;
}

// ── Auth ─────────────────────────────────────────────────────────────────────
document.getElementById('login-form').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = e.target.querySelector('button');
  btn.textContent = 'Signing in…'; btn.disabled = true;
  const res = await apiFetch('/admin/auth.php', {
    method: 'POST',
    body: JSON.stringify({ 
      username: document.getElementById('login-user').value, 
      password: document.getElementById('login-pass').value,
      website: document.getElementById('login-website').value
    })
  }).catch(() => ({ success: false }));

  if (res.success) {
    document.getElementById('login-screen').style.display = 'none';
    document.getElementById('app').style.display = 'flex';
    document.getElementById('sidebar-user').textContent = '👤 ' + res.username;
    loadSection('dashboard');
    loadAllContent();
  } else {
    const err = document.getElementById('login-error');
    err.textContent = res.error || 'Invalid credentials';
    err.style.display = 'block';
    btn.textContent = 'Sign In'; btn.disabled = false;
  }
});

document.getElementById('btn-logout').addEventListener('click', async () => {
  await apiFetch('/admin/auth.php', { method: 'DELETE' });
  location.reload();
});

// ── Session restore on page load ──────────────────────────────────────────────
(async () => {
  const res = await apiFetch('/admin/auth.php').catch(() => ({ success: false }));
  if (res.success) {
    document.getElementById('login-screen').style.display = 'none';
    document.getElementById('app').style.display = 'flex';
    document.getElementById('sidebar-user').textContent = '👤 ' + res.username;
    loadSection('dashboard');
    loadAllContent();
  }
})();

// ── Navigation ────────────────────────────────────────────────────────────────
document.querySelectorAll('.nav-item').forEach(item => {
  item.addEventListener('click', () => {
    document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
    item.classList.add('active');
    loadSection(item.dataset.section);
  });
});

document.getElementById('modal-close').addEventListener('click', hideModal);
document.getElementById('modal').addEventListener('click', e => { if (e.target.id === 'modal') hideModal(); });

// ── Load all content from DB ──────────────────────────────────────────────────
async function loadAllContent() {
  const res = await apiFetch('/admin/content.php').catch(() => ({ content: {} }));
  allContent = res.content || {};
}

function getVal(page, key, def = '') {
  return allContent[page]?.[key]?.value ?? def;
}

// ── Save a single content block ───────────────────────────────────────────────
async function saveBlock(page, key, value) {
  const res = await apiFetch('/admin/content.php', {
    method: 'POST',
    body: JSON.stringify({ page, key, value })
  });
  if (res.success) {
    if (!allContent[page]) allContent[page] = {};
    allContent[page][key] = { value };
  }
  return res.success;
}

// ── Content field builder ─────────────────────────────────────────────────────
function contentField(page, key, label, type = 'text', hint = '', rows = 3) {
  const val = getVal(page, key, '');
  const id = `cf_${page}_${key}`;

  let input;
  if (type === 'textarea') {
    input = `<textarea id="${id}" rows="${rows}" style="min-height:${rows * 28}px">${escHtml(val)}</textarea>`;
  } else if (type === 'image') {
    // Image upload widget
    const prevId = `prev_${id}`;
    const dropId = `drop_${id}`;
    const fileId = `file_${id}`;
    input = `
      <div class="img-upload-widget" id="${dropId}" onclick="document.getElementById('${fileId}').click()">
        <input type="hidden" id="${id}" value="${escHtml(val)}"/>
        <input type="file" id="${fileId}" accept="image/*" style="display:none" onchange="handleImgUpload('${fileId}','${id}','${prevId}','${dropId}')"/>
        ${val
          ? `<img src="${escHtml(resolveImg(val))}" class="img-upload-preview" id="${prevId}" onerror="this.style.display='none'"/>`
          : `<div class="img-upload-placeholder" id="${prevId}"><span class="img-upload-icon">🖼️</span><span>Click or drag to upload image</span><span class="img-upload-hint">JPG, PNG, WebP — max 5MB</span></div>`
        }
        <div class="img-upload-overlay"><span>Replace image</span></div>
      </div>
      <div style="margin-top:8px;display:flex;gap:8px;align-items:center">
        <input type="url" id="url_${id}" value="${escHtml(val)}" placeholder="Or paste image URL…" style="flex:1;font-size:12px" oninput="document.getElementById('${id}').value=this.value;updateImgPreview('${id}','${prevId}','${dropId}')"/>
        ${val ? `<button type="button" class="btn btn-sm" style="background:#f1f3f5;color:var(--gray-800);font-size:12px" onclick="clearImg('${id}','${prevId}','${dropId}','url_${id}')">✕ Clear</button>` : ''}
      </div>`;
    // Wire drag-and-drop after render
    setTimeout(() => {
      const drop = document.getElementById(dropId);
      if (!drop) return;
      drop.addEventListener('dragover', e => { e.preventDefault(); drop.classList.add('drag-over'); });
      drop.addEventListener('dragleave', () => drop.classList.remove('drag-over'));
      drop.addEventListener('drop', e => {
        e.preventDefault(); drop.classList.remove('drag-over');
        const f = e.dataTransfer.files[0];
        if (f && f.type.startsWith('image/')) {
          const dt = new DataTransfer(); dt.items.add(f);
          const fi = document.getElementById(fileId); fi.files = dt.files;
          handleImgUpload(fileId, id, prevId, dropId);
        }
      });
    }, 0);
  } else if (type === 'url') {
    input = `<div style="display:flex;gap:8px;align-items:center">
      <input type="url" id="${id}" value="${escHtml(val)}" style="flex:1" placeholder="https://…"/>
      ${val ? `<img src="${escHtml(resolveImg(val))}" class="img-preview" onerror="this.style.display='none'" id="prev_${id}">` : ''}
    </div>`;
  } else {
    input = `<input type="text" id="${id}" value="${escHtml(val)}" />`;
  }

  return `
    <div class="form-group">
      <label>${escHtml(label)}</label>
      ${input}
      ${hint ? `<div class="hint">${hint}</div>` : ''}
    </div>`;
}

function resolveImg(url) {
  if (!url) return '';
  return url.startsWith('uploads/') ? '../' + url : url;
}
function escHtml(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── Image upload helpers ──────────────────────────────────────────────────────
async function handleImgUpload(fileInputId, hiddenId, prevId, dropId) {
  const fileInput = document.getElementById(fileInputId);
  const file = fileInput?.files[0];
  if (!file) return;
  const drop = document.getElementById(dropId);
  if (drop) drop.classList.add('img-uploading');
  try {
    const fd = new FormData();
    fd.append('file', file);
    const res = await fetch(`${API_BASE}/admin/upload.php?type=pages`, { method:'POST', credentials:'include', body:fd });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Upload failed');
    const url = data.url;
    document.getElementById(hiddenId).value = url;
    const urlInput = document.getElementById('url_' + hiddenId);
    if (urlInput) urlInput.value = url;
    updateImgPreview(hiddenId, prevId, dropId);
    // Auto-save immediately — derive page/key from hiddenId (format: cf_PAGE_KEY)
    const parts = hiddenId.replace(/^cf_/, '').match(/^([^_]+)_(.+)$/);
    if (parts) {
      const [, page, key] = parts;
      const saved = await saveBlock(page, key, url);
      toast(saved ? 'Image saved' : 'Uploaded but save failed — click Save Changes', saved ? 'success' : 'error');
    } else {
      toast('Image uploaded');
    }
  } catch (e) {
    toast(e.message || 'Upload failed', 'error');
  } finally {
    if (drop) drop.classList.remove('img-uploading');
  }
}

function updateImgPreview(hiddenId, prevId, dropId) {
  const url = document.getElementById(hiddenId)?.value;
  const prev = document.getElementById(prevId);
  const drop = document.getElementById(dropId);
  if (!prev || !drop) return;
  if (url) {
    if (prev.tagName === 'IMG') {
      prev.src = url; prev.style.display = 'block';
    } else {
      const img = document.createElement('img');
      img.src = url; img.className = 'img-upload-preview'; img.id = prevId;
      img.onerror = () => img.style.display = 'none';
      prev.replaceWith(img);
    }
  }
}

function clearImg(hiddenId, prevId, dropId, urlInputId) {
  document.getElementById(hiddenId).value = '';
  if (urlInputId) document.getElementById(urlInputId).value = '';
  const drop = document.getElementById(dropId);
  const prev = document.getElementById(prevId);
  if (prev && prev.tagName === 'IMG') {
    const ph = document.createElement('div');
    ph.className = 'img-upload-placeholder'; ph.id = prevId;
    ph.innerHTML = '<span class="img-upload-icon">🖼️</span><span>Click or drag to upload image</span><span class="img-upload-hint">JPG, PNG, WebP — max 5MB</span>';
    prev.replaceWith(ph);
  }
}

// Brand asset upload (logo / favicon) — uses 'brand' upload type
async function handleBrandUpload(fileInputId, hiddenId, prevId, dropId, type) {
  const fileInput = document.getElementById(fileInputId);
  const file = fileInput?.files[0];
  if (!file) return;
  const drop = document.getElementById(dropId);
  if (drop) drop.classList.add('img-uploading');
  try {
    const fd = new FormData();
    fd.append('file', file);
    const res = await fetch(`${API_BASE}/admin/upload.php?type=${type || 'brand'}`, { method:'POST', credentials:'include', body:fd });
    const data = await res.json();
    if (!data.success) throw new Error(data.error || 'Upload failed');
    document.getElementById(hiddenId).value = data.url;
    const visInput = document.getElementById('vis_' + hiddenId);
    if (visInput) visInput.value = data.url;
    updateBrandPreview(hiddenId, prevId);
    toast('Uploaded — save settings to apply');
  } catch (e) {
    toast(e.message || 'Upload failed', 'error');
  } finally {
    if (drop) drop.classList.remove('img-uploading');
  }
}

function updateBrandPreview(hiddenId, prevId) {
  const url = document.getElementById(hiddenId)?.value;
  const prev = document.getElementById(prevId);
  if (!prev || !url) return;
  const existing = prev.querySelector('img') || (prev.tagName === 'IMG' ? prev : null);
  if (existing) { existing.src = url; return; }
  prev.innerHTML = `<img src="${resolveImg(url)}" style="max-height:80px;max-width:100%;object-fit:contain">`;
}

// Save all fields in a section card
async function saveSectionFields(page, keys, btn) {
  btn.textContent = 'Saving…'; btn.disabled = true;
  let ok = true;
  for (const key of keys) {
    const el = document.getElementById(`cf_${page}_${key}`);
    if (el) {
      const r = await saveBlock(page, key, el.value);
      if (!r) ok = false;
    }
  }
  btn.textContent = ok ? '✓ Saved' : '✗ Error';
  btn.style.background = ok ? '#065F46' : 'var(--red)';
  toast(ok ? 'Content saved successfully' : 'Save failed', ok ? 'success' : 'error');
  setTimeout(() => { btn.textContent = 'Save Changes'; btn.disabled = false; btn.style.background = ''; }, 2000);
}

function saveBtn(page, keys, label = 'Save Changes') {
  const id = `save_${page}_${keys[0]}`;
  setTimeout(() => {
    const b = document.getElementById(id);
    if (b) b.addEventListener('click', () => saveSectionFields(page, keys, b));
  }, 0);
  return `<button class="btn btn-primary" id="${id}">${label}</button>`;
}

function sectionCard(title, bodyHtml, page, keys) {
  return `
    <div class="page-section">
      <div class="section-header">
        <h3>${title}</h3>
        ${saveBtn(page, keys)}
      </div>
      <div class="section-body">${bodyHtml}</div>
    </div>`;
}

// ── Sections ─────────────────────────────────────────────────────────────────
function loadSection(name) {
  currentSection = name;
  const titles = { dashboard:'Dashboard', home:'Homepage', about:'About Us', approach:'Our Approach', projects:'Programmes', nicu:'NICU Network Nigeria', team:'Team Members', news:'News & Articles', messages:'Contact Messages', getinvolved:'Get Involved', contact:'Contact Page', map:'Where We Work', settings:'Settings' };
  document.getElementById('topbar-title').textContent = titles[name] || name;
  const area = document.getElementById('content-area');
  area.innerHTML = '<div style="color:var(--gray-400);padding:40px;text-align:center">Loading…</div>';

  const renderers = { dashboard:renderDashboard, home:renderHome, about:renderAbout, approach:renderApproach, projects:renderProjects, nicu:renderNicu, team:renderTeam, news:renderNews, messages:renderMessages, getinvolved:renderGetInvolved, contact:renderContact, map:renderMap, settings:renderSettings };
  (renderers[name] || (() => area.innerHTML = '<p>Section coming soon.</p>'))();
}

// ── Dashboard ─────────────────────────────────────────────────────────────────
async function renderDashboard() {
  const area = document.getElementById('content-area');

  let msgs = 0, team = 0, articles = 0;
  try {
    const [t, n, m] = await Promise.all([
      apiFetch('/admin/team.php'),
      apiFetch('/admin/news.php'),
      apiFetch('/admin/messages.php')
    ]);
    team = t.team?.length || 0;
    articles = n.articles?.length || 0;
    msgs = m.messages?.length || 0;
    const unread = m.messages?.filter(x => !x.read_at).length || 0;
    if (unread) { const b = document.getElementById('msg-badge'); b.textContent = unread; b.style.display = 'inline-block'; }
  } catch {}

  area.innerHTML = `
    <div class="stats-row">
      <div class="stat-card"><div class="sv">${team}</div><div class="sl">Team Members</div></div>
      <div class="stat-card"><div class="sv">${articles}</div><div class="sl">News Articles</div></div>
      <div class="stat-card"><div class="sv">${msgs}</div><div class="sl">Contact Messages</div></div>
      <div class="stat-card"><div class="sv">9</div><div class="sl">Editable Pages</div></div>
    </div>
    <div class="page-section">
      <div class="section-header"><h3>Quick Access</h3></div>
      <div class="section-body" style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
        ${[['home','🏠','Homepage'],['about','ℹ️','About Us'],['nicu','🏥','NICU Network'],['team','👥','Team Members'],['news','📰','News'],['messages','✉️','Messages']].map(([s,ic,lb]) =>
          `<button class="btn btn-secondary" style="justify-content:flex-start;gap:10px" onclick="document.querySelector('[data-section=${s}]').click()">${ic} ${lb}</button>`
        ).join('')}
      </div>
    </div>
    <div class="page-section">
      <div class="section-header"><h3>Getting Started</h3></div>
      <div class="section-body">
        <p style="color:var(--gray-600);line-height:1.8">Use the sidebar to navigate between pages. Click any section to edit its content, then press <strong>Save Changes</strong>. Changes go live on the website immediately — <a href="../" target="_blank" style="color:var(--teal)">click here to view the site</a>.</p>
        <ul style="margin-top:12px;padding-left:20px;color:var(--gray-600);line-height:2">
          <li>Edit page copy, headlines, and images from the page sections</li>
          <li>Add or remove team members under <strong>Team Members</strong></li>
          <li>Publish news articles under <strong>News &amp; Articles</strong></li>
          <li>View incoming contact form submissions under <strong>Messages</strong></li>
        </ul>
      </div>
    </div>`;
}

// ── Homepage ──────────────────────────────────────────────────────────────────
function renderHome() {
  document.getElementById('content-area').innerHTML = `
    ${sectionCard('Hero Section',
      contentField('home','hero_eyebrow','Eyebrow Text','text','Small text above the headline — e.g. organisation name') +
      contentField('home','hero_headline','Headline','text') +
      contentField('home','hero_sub','Sub-headline','text') +
      contentField('home','hero_btn1','Primary Button Label','text','Default: Explore Our Work') +
      contentField('home','hero_btn2','Secondary Button Label','text','Default: Get Involved') +
      contentField('home','hero_image','Hero Background Image','image','Recommended: 1600×900px'),
      'home', ['hero_eyebrow','hero_headline','hero_sub','hero_btn1','hero_btn2','hero_image'])}

    ${sectionCard('Why This Work Is Needed (Problem Section)',
      contentField('home','problem_tag','Section Tag','text','Small label above the heading — e.g. Why This Work Is Needed') +
      contentField('home','problem_title','Section Heading') +
      contentField('home','problem_p1','Paragraph 1','textarea','',4) +
      contentField('home','problem_p2','Paragraph 2','textarea','',4) +
      contentField('home','problem_emphasis','Emphasis Line','text','Bold closing line') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Challenge cards (right column)</strong></div>' +
      contentField('home','problem_card1','Card 1 Label') +
      contentField('home','problem_card2','Card 2 Label') +
      contentField('home','problem_card3','Card 3 Label') +
      contentField('home','problem_card4','Card 4 Label') +
      contentField('home','problem_card5','Card 5 Label (accent)'),
      'home', ['problem_tag','problem_title','problem_p1','problem_p2','problem_emphasis','problem_card1','problem_card2','problem_card3','problem_card4','problem_card5'])}

    ${sectionCard('Impact Stats Section',
      contentField('home','stats_heading','Section Heading') +
      contentField('home','stats_subtext','Section Subtext','textarea','',2) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Stat 1</strong></div>' +
      contentField('home','stat1_value','Value','text','e.g. 41') +
      contentField('home','stat1_unit','Unit','text','e.g. per 1,000') +
      contentField('home','stat1_label','Label') +
      contentField('home','stat1_desc','Description') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Stat 2</strong></div>' +
      contentField('home','stat2_value','Value') +
      contentField('home','stat2_unit','Unit') +
      contentField('home','stat2_label','Label') +
      contentField('home','stat2_desc','Description') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Stat 3</strong></div>' +
      contentField('home','stat3_value','Value') +
      contentField('home','stat3_unit','Unit') +
      contentField('home','stat3_label','Label') +
      contentField('home','stat3_desc','Description') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Stat 4</strong></div>' +
      contentField('home','stat4_value','Value') +
      contentField('home','stat4_unit','Unit') +
      contentField('home','stat4_label','Label') +
      contentField('home','stat4_desc','Description'),
      'home', ['stats_heading','stats_subtext','stat1_value','stat1_unit','stat1_label','stat1_desc','stat2_value','stat2_unit','stat2_label','stat2_desc','stat3_value','stat3_unit','stat3_label','stat3_desc','stat4_value','stat4_unit','stat4_label','stat4_desc'])}

    ${sectionCard('What We Do Section',
      contentField('home','wwd_tag','Section Tag') +
      contentField('home','wwd_heading','Section Heading') +
      contentField('home','wwd_subtitle','Section Subtitle') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Area 1</strong></div>' +
      contentField('home','wwd1_title','Title') + contentField('home','wwd1_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Area 2</strong></div>' +
      contentField('home','wwd2_title','Title') + contentField('home','wwd2_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Area 3</strong></div>' +
      contentField('home','wwd3_title','Title') + contentField('home','wwd3_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Area 4</strong></div>' +
      contentField('home','wwd4_title','Title') + contentField('home','wwd4_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Area 5</strong></div>' +
      contentField('home','wwd5_title','Title') + contentField('home','wwd5_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Area 6</strong></div>' +
      contentField('home','wwd6_title','Title') + contentField('home','wwd6_desc','Description','textarea','',2),
      'home', ['wwd_tag','wwd_heading','wwd_subtitle','wwd1_title','wwd1_desc','wwd2_title','wwd2_desc','wwd3_title','wwd3_desc','wwd4_title','wwd4_desc','wwd5_title','wwd5_desc','wwd6_title','wwd6_desc'])}

    ${sectionCard('Feature Quote / Why It Matters',
      contentField('home','quote_tag','Section Tag') +
      contentField('home','quote_title','Section Heading') +
      contentField('home','quote_text','Blockquote Text','textarea','',4) +
      contentField('home','quote_name','Attribution Name') +
      contentField('home','quote_role','Attribution Role'),
      'home', ['quote_tag','quote_title','quote_text','quote_name','quote_role'])}

    ${sectionCard('Get Involved Cards',
      contentField('home','gic_tag','Section Tag') +
      contentField('home','gic_heading','Section Heading') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Card 1 — Donate</strong></div>' +
      contentField('home','gic1_label','Label') + contentField('home','gic1_title','Title') + contentField('home','gic1_desc','Description','textarea','',2) + contentField('home','gic1_img','Background Image','image') +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Card 2 — Partner</strong></div>' +
      contentField('home','gic2_label','Label') + contentField('home','gic2_title','Title') + contentField('home','gic2_desc','Description','textarea','',2) + contentField('home','gic2_img','Background Image','image') +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Card 3 — Careers</strong></div>' +
      contentField('home','gic3_label','Label') + contentField('home','gic3_title','Title') + contentField('home','gic3_desc','Description','textarea','',2) + contentField('home','gic3_img','Background Image','image'),
      'home', ['gic_tag','gic_heading','gic1_label','gic1_title','gic1_desc','gic1_img','gic2_label','gic2_title','gic2_desc','gic2_img','gic3_label','gic3_title','gic3_desc','gic3_img'])}

    ${sectionCard('Programmes Strip (Homepage)',
      contentField('home','ps_tag','Section Tag','text','Default: Our Programmes') +
      contentField('home','ps_heading','Section Heading','text','Default: Our programme portfolio') +
      contentField('home','ps_subtitle','Section Subtitle','text') +
      contentField('home','ps_nicu_label','NICU Badge Label','text','Default: Flagship Programme') +
      contentField('home','ps_nicu_status','NICU Status Badge','text','Default: Pilot — launching 2026') +
      contentField('home','ps_nicu_title','NICU Card Title','text','Default: NICU Network Nigeria') +
      contentField('home','ps_nicu_summary','NICU Card Summary','textarea','',4) +
      contentField('home','ps_nicu_btn','NICU Card Button','text','Default: View Programme →') +
      contentField('home','ps_future_text','Future Programmes Placeholder','text'),
      'home', ['ps_tag','ps_heading','ps_subtitle','ps_nicu_label','ps_nicu_status','ps_nicu_title','ps_nicu_summary','ps_nicu_btn','ps_future_text'])}

    ${sectionCard('Partners Section (Homepage)',
      contentField('home','partners_tag','Section Tag','text','Default: Partnerships') +
      contentField('home','partners_heading','Section Heading','text','Default: Built with partners, for health systems') +
      contentField('home','partners_subtitle','Section Subtitle','textarea','',3) +
      contentField('home','partners_btn','Button Label','text','Default: Become a Partner') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Partner Type Cards</strong></div>' +
      contentField('home','pt1_icon','Card 1 Icon','text','Emoji') + contentField('home','pt1_label','Card 1 Label','text') +
      contentField('home','pt2_icon','Card 2 Icon','text','Emoji') + contentField('home','pt2_label','Card 2 Label','text') +
      contentField('home','pt3_icon','Card 3 Icon','text','Emoji') + contentField('home','pt3_label','Card 3 Label','text') +
      contentField('home','pt4_icon','Card 4 Icon','text','Emoji') + contentField('home','pt4_label','Card 4 Label','text') +
      contentField('home','pt5_icon','Card 5 Icon','text','Emoji') + contentField('home','pt5_label','Card 5 Label','text') +
      contentField('home','pt6_icon','Card 6 Icon','text','Emoji') + contentField('home','pt6_label','Card 6 Label','text'),
      'home', ['partners_tag','partners_heading','partners_subtitle','partners_btn','pt1_icon','pt1_label','pt2_icon','pt2_label','pt3_icon','pt3_label','pt4_icon','pt4_label','pt5_icon','pt5_label','pt6_icon','pt6_label'])}

    ${sectionCard('Bottom CTA Banner',
      contentField('home','cta_heading','Headline') +
      contentField('home','cta_body','Body Text','textarea','',3),
      'home', ['cta_heading','cta_body'])}`;
}

// ── About Us ──────────────────────────────────────────────────────────────────
function renderAbout() {
  document.getElementById('content-area').innerHTML = `
    ${sectionCard('Page Hero',
      contentField('about','hero_image','Hero Background Image','image','Full-width banner shown at the top of the About page') +
      contentField('about','hero_tag','Hero Tag','text','Small label above the h1 — default: About Us') +
      contentField('about','hero_h1','Page Title (H1)','text') +
      contentField('about','hero_sub','Sub-headline','text'),
      'about', ['hero_image','hero_tag','hero_h1','hero_sub'])}

    ${sectionCard('Who We Are',
      contentField('about','who_headline','Section Headline') +
      contentField('about','who_body','Body Copy (separate paragraphs with blank line)','textarea','',10) +
      contentField('about','who_image','Supporting Image','image','Shown alongside the Who We Are text'),
      'about', ['who_headline','who_body','who_image'])}

    ${sectionCard('Vision & Mission',
      contentField('about','vision','Vision Statement','textarea','',3) +
      contentField('about','mission','Mission Statement','textarea','',3),
      'about', ['vision','mission'])}

    ${sectionCard('Our Goals',
      contentField('about','goals_heading','Section Heading') +
      contentField('about','goal1','Goal 1','textarea','',2) +
      contentField('about','goal2','Goal 2','textarea','',2) +
      contentField('about','goal3','Goal 3','textarea','',2) +
      contentField('about','goal4','Goal 4','textarea','',2) +
      contentField('about','goal5','Goal 5','textarea','',2) +
      contentField('about','goal6','Goal 6','textarea','',2) +
      contentField('about','goal7','Goal 7','textarea','',2) +
      contentField('about','goal8','Goal 8','textarea','',2),
      'about', ['goals_heading','goal1','goal2','goal3','goal4','goal5','goal6','goal7','goal8'])}

    ${sectionCard('Governance',
      contentField('about','governance_body','Governance Paragraph 1','textarea','',5) +
      contentField('about','governance_body2','Governance Paragraph 2','textarea','',5) +
      contentField('about','cac_number','CAC Registration Number','text','Leave blank until confirmed') +
      contentField('about','reg_type','Registration Body','text') +
      contentField('about','reg_subtype','Registration Type','text'),
      'about', ['governance_body','governance_body2','cac_number','reg_type','reg_subtype'])}`;
}

// ── Our Approach ──────────────────────────────────────────────────────────────
function renderApproach() {
  document.getElementById('content-area').innerHTML = `
    ${sectionCard('Page Hero',
      contentField('approach','hero_image','Hero Background Image','image') +
      contentField('approach','hero_h1','Page Title (H1)','text') +
      contentField('approach','hero_sub','Sub-headline','text'),
      'approach', ['hero_image','hero_h1','hero_sub'])}

    ${sectionCard('Page Introduction',
      contentField('approach','intro_headline','Section Headline') +
      contentField('approach','intro_body','Introduction Copy','textarea','',4),
      'approach', ['intro_headline','intro_body'])}

    ${sectionCard('Closing Quote',
      contentField('approach','quote','Pull Quote','textarea','The quote shown in the blockquote on this page',3),
      'approach', ['quote'])}

    ${sectionCard('8 Guiding Principles',
      '<div style="margin-top:0px"><strong style="font-size:12px;color:var(--gray-600)">Principle 1</strong></div>' +
      contentField('approach','principle1_title','Title') + contentField('approach','principle1_desc','Description','textarea','',2) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Principle 2</strong></div>' +
      contentField('approach','principle2_title','Title') + contentField('approach','principle2_desc','Description','textarea','',2) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Principle 3</strong></div>' +
      contentField('approach','principle3_title','Title') + contentField('approach','principle3_desc','Description','textarea','',2) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Principle 4</strong></div>' +
      contentField('approach','principle4_title','Title') + contentField('approach','principle4_desc','Description','textarea','',2) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Principle 5</strong></div>' +
      contentField('approach','principle5_title','Title') + contentField('approach','principle5_desc','Description','textarea','',2) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Principle 6</strong></div>' +
      contentField('approach','principle6_title','Title') + contentField('approach','principle6_desc','Description','textarea','',2) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Principle 7</strong></div>' +
      contentField('approach','principle7_title','Title') + contentField('approach','principle7_desc','Description','textarea','',2) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Principle 8</strong></div>' +
      contentField('approach','principle8_title','Title') + contentField('approach','principle8_desc','Description','textarea','',2),
      'approach', ['principle1_title','principle1_desc','principle2_title','principle2_desc','principle3_title','principle3_desc','principle4_title','principle4_desc','principle5_title','principle5_desc','principle6_title','principle6_desc','principle7_title','principle7_desc','principle8_title','principle8_desc'])}

    ${sectionCard('9 Process Steps',
      contentField('approach','step1','Step 1') +
      contentField('approach','step2','Step 2') +
      contentField('approach','step3','Step 3') +
      contentField('approach','step4','Step 4') +
      contentField('approach','step5','Step 5') +
      contentField('approach','step6','Step 6') +
      contentField('approach','step7','Step 7') +
      contentField('approach','step8','Step 8') +
      contentField('approach','step9','Step 9'),
      'approach', ['step1','step2','step3','step4','step5','step6','step7','step8','step9'])}

    ${sectionCard('Our Intended Contribution',
      contentField('approach','contribution_headline','Section Headline') +
      contentField('approach','contribution_body','Body Copy','textarea','',4),
      'approach', ['contribution_headline','contribution_body'])}`;
}

// ── NICU Network ──────────────────────────────────────────────────────────────
function renderNicu() {
  document.getElementById('content-area').innerHTML = `
    ${sectionCard('Programme Hero',
      contentField('nicu','hero_image','Hero Background Image','image','Recommended: 1600×900px — clinical/NICU context') +
      contentField('nicu','hero_attribution','Attribution Line','text','Default: A programme of Health Systems Initiative') +
      contentField('nicu','hero_label','Badge Label','text','Default: Flagship Programme') +
      contentField('nicu','hero_status','Status Badge','text','Default: Pilot — launching 2026') +
      contentField('nicu','hero_title','Page Title (H1)','text','Default: NICU Network Nigeria') +
      contentField('nicu','strapline','Strapline','text','Shown below the title') +
      contentField('nicu','tagline','Tagline','text','All-caps line at the bottom of the hero'),
      'nicu', ['hero_image','hero_attribution','hero_label','hero_status','hero_title','strapline','tagline'])}

    ${sectionCard('The Problem — Section',
      contentField('nicu','problem_tag','Section Tag','text','Default: Why This Programme Exists') +
      contentField('nicu','problem_headline','Section Heading') +
      contentField('nicu','problem_p1','Paragraph 1','textarea','',4) +
      contentField('nicu','problem_p2','Paragraph 2','textarea','',4) +
      contentField('nicu','problem_p3','Paragraph 3','textarea','',4) +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Stat Card</strong></div>' +
      contentField('nicu','stat_big','Big Number','text','Default: 41') +
      contentField('nicu','stat_unit','Unit Text','text','Default: per 1,000 live births') +
      contentField('nicu','stat_label','Stat Label','text','Default: Neonatal deaths in Nigeria') +
      contentField('nicu','stat_global','Global Rate','text','Default: 17.2') +
      contentField('nicu','stat_source','Source','text','Default: NDHS 2024'),
      'nicu', ['problem_tag','problem_headline','problem_p1','problem_p2','problem_p3','stat_big','stat_unit','stat_label','stat_global','stat_source'])}

    ${sectionCard('What NICU Network Does',
      contentField('nicu','what_tag','Section Tag','text','Default: A Better Way to Coordinate Neonatal Referrals') +
      contentField('nicu','what_headline','Section Heading') +
      contentField('nicu','what_p1','Paragraph 1','textarea','',3) +
      contentField('nicu','what_p2','Paragraph 2','textarea','',3) +
      contentField('nicu','what_p3','Paragraph 3','textarea','',3) +
      contentField('nicu','what_image','Section Image','image','700×500px recommended'),
      'nicu', ['what_tag','what_headline','what_p1','what_p2','what_p3','what_image'])}

    ${sectionCard('The Pilot',
      contentField('nicu','pilot_tag','Section Tag','text','Default: Where We Are Starting') +
      contentField('nicu','pilot_headline','Section Heading') +
      contentField('nicu','pilot_p1','Paragraph 1','textarea','',3) +
      contentField('nicu','pilot_p2','Paragraph 2','textarea','',3) +
      contentField('nicu','pilot_timeline','Timeline Note','text','Shown in the timeline bar e.g. Pilot launching 2026 — FCT Abuja') +
      contentField('nicu','pilot_image','Section Image','image','700×500px recommended'),
      'nicu', ['pilot_tag','pilot_headline','pilot_p1','pilot_p2','pilot_timeline','pilot_image'])}

    ${sectionCard('Guiding Principles',
      contentField('nicu','principles_tag','Section Tag','text','Default: How We Work') +
      contentField('nicu','principles_heading','Section Heading','text','Default: Our guiding principles') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Principle 1</strong></div>' +
      contentField('nicu','p1_title','Title') + contentField('nicu','p1_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Principle 2</strong></div>' +
      contentField('nicu','p2_title','Title') + contentField('nicu','p2_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Principle 3</strong></div>' +
      contentField('nicu','p3_title','Title') + contentField('nicu','p3_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Principle 4</strong></div>' +
      contentField('nicu','p4_title','Title') + contentField('nicu','p4_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Principle 5</strong></div>' +
      contentField('nicu','p5_title','Title') + contentField('nicu','p5_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Principle 6</strong></div>' +
      contentField('nicu','p6_title','Title') + contentField('nicu','p6_desc','Description','textarea','',2),
      'nicu', ['principles_tag','principles_heading','p1_title','p1_desc','p2_title','p2_desc','p3_title','p3_desc','p4_title','p4_desc','p5_title','p5_desc','p6_title','p6_desc'])}

    ${sectionCard('Hospital Partnership Section',
      contentField('nicu','partnership_tag','Section Tag','text','Default: Partner With NICU Network') +
      contentField('nicu','partnership_heading','Section Heading','text','Default: Join the network') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Block 1 — What partners gain</strong></div>' +
      contentField('nicu','block1_title','Block Title','text') +
      contentField('nicu','block1_items','Bullet Points (one per line)','textarea','Each line becomes a bullet point',5) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Block 2 — What partnership involves</strong></div>' +
      contentField('nicu','block2_title','Block Title','text') +
      contentField('nicu','block2_items','Bullet Points (one per line)','textarea','Each line becomes a bullet point',5) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Block 3 — Who can join</strong></div>' +
      contentField('nicu','block3_title','Block Title','text') +
      contentField('nicu','block3_body','Body Text','textarea','',3) +
      contentField('nicu','partnership_closing','Closing Statement','textarea','Shown above the CTA button',3) +
      contentField('nicu','partnership_cta_btn','CTA Button Label','text','Default: Enquire About Partnership'),
      'nicu', ['partnership_tag','partnership_heading','block1_title','block1_items','block2_title','block2_items','block3_title','block3_body','partnership_closing','partnership_cta_btn'])}

    ${sectionCard('Partner Hospitals',
      contentField('nicu','hospitals_heading','Section Heading','text','Default: Our Partner Hospitals') +
      contentField('nicu','hospitals_body','Intro Text','text') +
      contentField('nicu','hospitals_placeholder','Placeholder Text','text','Shown until hospitals are confirmed'),
      'nicu', ['hospitals_heading','hospitals_body','hospitals_placeholder'])}

    ${sectionCard('Clinical Oversight',
      contentField('nicu','clinical_tag','Section Tag','text','Default: Clinical Oversight') +
      contentField('nicu','clinical_body','Clinical Advisory Group Statement','textarea','',3),
      'nicu', ['clinical_tag','clinical_body'])}

    ${sectionCard('Get Involved — NICU',
      contentField('nicu','involve_heading','Section Heading','text','Default: Get Involved With NICU Network') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Card 1 — For Hospitals</strong></div>' +
      contentField('nicu','inv1_icon','Icon (emoji)','text') +
      contentField('nicu','inv1_title','Card Title','text') +
      contentField('nicu','inv1_body','Card Body','textarea','',3) +
      contentField('nicu','inv1_btn','Button Label','text') +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Card 2 — For Funders</strong></div>' +
      contentField('nicu','inv2_icon','Icon (emoji)','text') +
      contentField('nicu','inv2_title','Card Title','text') +
      contentField('nicu','inv2_body','Card Body','textarea','',3) +
      contentField('nicu','inv2_btn','Button Label','text'),
      'nicu', ['involve_heading','inv1_icon','inv1_title','inv1_body','inv1_btn','inv2_icon','inv2_title','inv2_body','inv2_btn'])}`;
}

// ── Projects ──────────────────────────────────────────────────────────────────
function renderProjects() {
  document.getElementById('content-area').innerHTML = `
    ${sectionCard('Page Hero',
      contentField('projects','hero_image','Hero Background Image','image','Recommended: 1600×900px') +
      contentField('projects','hero_tag','Hero Tag','text','Default: Programmes') +
      contentField('projects','hero_h1','Page Title (H1)','text','Default: Our Programmes') +
      contentField('projects','hero_sub','Sub-headline','text'),
      'projects', ['hero_image','hero_tag','hero_h1','hero_sub'])}

    ${sectionCard('Page Intro',
      contentField('projects','intro','Intro Paragraph','textarea','Shown below the hero above the programme cards',4),
      'projects', ['intro'])}

    ${sectionCard('NICU Network Card',
      contentField('projects','nicu_image','Card Image','image','700×500px recommended') +
      contentField('projects','nicu_label','Badge Label','text','Default: Flagship Programme') +
      contentField('projects','nicu_status','Status Badge','text','Default: Pilot — launching 2026') +
      contentField('projects','nicu_title','Programme Title','text','Default: NICU Network Nigeria') +
      contentField('projects','nicu_summary','Summary Text','textarea','',3) +
      contentField('projects','nicu_detail','Detail Text','textarea','',3) +
      contentField('projects','nicu_btn','Button Label','text','Default: View Programme →'),
      'projects', ['nicu_image','nicu_label','nicu_status','nicu_title','nicu_summary','nicu_detail','nicu_btn'])}

    ${sectionCard('Future Programmes Placeholder',
      contentField('projects','future_title','Heading','text','Default: More programmes coming') +
      contentField('projects','future_body','Body Text','textarea','',3),
      'projects', ['future_title','future_body'])}`;
}

// ── Get Involved ──────────────────────────────────────────────────────────────
function renderGetInvolved() {
  document.getElementById('content-area').innerHTML = `
    ${sectionCard('Page Hero',
      contentField('getinvolved','hero_image','Hero Background Image','image','Full-width banner image for the Get Involved page') +
      contentField('getinvolved','hero_tag','Hero Tag','text','Default: Get Involved') +
      contentField('getinvolved','hero_h1','Page Title (H1)','text','Default: Support Our Work') +
      contentField('getinvolved','hero_sub','Sub-headline','text'),
      'getinvolved', ['hero_image','hero_tag','hero_h1','hero_sub'])}

    ${sectionCard('Donate Section',
      contentField('getinvolved','donate_headline','Section Heading','text','Default: Support Our Work') +
      contentField('getinvolved','donate_body','Body Copy','textarea','',4) +
      contentField('getinvolved','donate_image','Section Image','image','Image shown beside the donation text') +
      contentField('getinvolved','donorbox_url','Donorbox Donation URL','url') +
      contentField('getinvolved','paystack_url','Paystack URL (NGN)','url') +
      contentField('getinvolved','donate_btn1','Donorbox Button Label','text','Default: Donate via Donorbox (USD / Card)') +
      contentField('getinvolved','donate_btn2','Paystack Button Label','text','Default: Donate in NGN via Paystack') +
      contentField('getinvolved','donate_note','Placeholder Note','text','Shown when donation links are not yet set'),
      'getinvolved', ['donate_headline','donate_body','donate_image','donorbox_url','paystack_url','donate_btn1','donate_btn2','donate_note'])}

    ${sectionCard('Donation Tiers',
      '<div style="margin-top:4px"><strong style="font-size:12px;color:var(--gray-600)">Tier 1</strong></div>' +
      contentField('getinvolved','tier1_amount','Amount','text','e.g. $10') + contentField('getinvolved','tier1_ngn','NGN Equivalent','text','e.g. ₦15,000') +
      contentField('getinvolved','tier1_label','Label','text','e.g. Supporter') + contentField('getinvolved','tier1_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Tier 2</strong></div>' +
      contentField('getinvolved','tier2_amount','Amount','text') + contentField('getinvolved','tier2_ngn','NGN Equivalent','text') +
      contentField('getinvolved','tier2_label','Label','text') + contentField('getinvolved','tier2_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Tier 3</strong></div>' +
      contentField('getinvolved','tier3_amount','Amount','text') + contentField('getinvolved','tier3_ngn','NGN Equivalent','text') +
      contentField('getinvolved','tier3_label','Label','text') + contentField('getinvolved','tier3_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Tier 4 (Featured)</strong></div>' +
      contentField('getinvolved','tier4_amount','Amount','text') + contentField('getinvolved','tier4_ngn','NGN Equivalent','text') +
      contentField('getinvolved','tier4_label','Label','text') + contentField('getinvolved','tier4_desc','Description','textarea','',2) +
      contentField('getinvolved','tier_featured_badge','Featured Badge Text','text','Default: Most Impactful') +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Tier 5</strong></div>' +
      contentField('getinvolved','tier5_amount','Amount','text') + contentField('getinvolved','tier5_ngn','NGN Equivalent','text') +
      contentField('getinvolved','tier5_label','Label','text') + contentField('getinvolved','tier5_desc','Description','textarea','',2) +
      '<div style="margin-top:8px"><strong style="font-size:12px;color:var(--gray-600)">Tier 6 (Custom)</strong></div>' +
      contentField('getinvolved','tier6_amount','Amount','text','Default: Custom') +
      contentField('getinvolved','tier6_label','Label','text') + contentField('getinvolved','tier6_desc','Description','textarea','',2),
      'getinvolved', ['tier1_amount','tier1_ngn','tier1_label','tier1_desc','tier2_amount','tier2_ngn','tier2_label','tier2_desc','tier3_amount','tier3_ngn','tier3_label','tier3_desc','tier4_amount','tier4_ngn','tier4_label','tier4_desc','tier_featured_badge','tier5_amount','tier5_ngn','tier5_label','tier5_desc','tier6_amount','tier6_label','tier6_desc'])}

    ${sectionCard('Partner With Us',
      contentField('getinvolved','partner_tag','Section Tag','text','Default: Partner With Us') +
      contentField('getinvolved','partner_headline','Section Heading','text','Default: Partner With Us') +
      contentField('getinvolved','partner_body','Body Copy','textarea','',5) +
      contentField('getinvolved','partner_image','Section Image','image','Image shown beside the partner text') +
      contentField('getinvolved','partner_btn','CTA Button Label','text','Default: Start a Conversation') +
      '<div style="margin-top:12px"><strong style="font-size:12px;color:var(--gray-600)">Partner Types</strong></div>' +
      contentField('getinvolved','pt1_icon','Type 1 Icon','text','Emoji') + contentField('getinvolved','pt1_label','Type 1 Label','text') + contentField('getinvolved','pt1_desc','Type 1 Description','text') +
      contentField('getinvolved','pt2_icon','Type 2 Icon','text','Emoji') + contentField('getinvolved','pt2_label','Type 2 Label','text') + contentField('getinvolved','pt2_desc','Type 2 Description','text') +
      contentField('getinvolved','pt3_icon','Type 3 Icon','text','Emoji') + contentField('getinvolved','pt3_label','Type 3 Label','text') + contentField('getinvolved','pt3_desc','Type 3 Description','text') +
      contentField('getinvolved','pt4_icon','Type 4 Icon','text','Emoji') + contentField('getinvolved','pt4_label','Type 4 Label','text') + contentField('getinvolved','pt4_desc','Type 4 Description','text'),
      'getinvolved', ['partner_tag','partner_headline','partner_body','partner_image','partner_btn','pt1_icon','pt1_label','pt1_desc','pt2_icon','pt2_label','pt2_desc','pt3_icon','pt3_label','pt3_desc','pt4_icon','pt4_label','pt4_desc'])}

    ${sectionCard('Join the Team / Careers',
      contentField('getinvolved','careers_tag','Section Tag','text','Default: Join the Team') +
      contentField('getinvolved','careers_headline','Section Heading','text','Default: Join the Team') +
      contentField('getinvolved','careers_body','Body Copy','textarea','',4) +
      contentField('getinvolved','careers_email','Careers Email','text') +
      contentField('getinvolved','careers_btn','Button Label','text','Default: Express Your Interest') +
      contentField('getinvolved','expertise_heading','Expertise Heading','text','Default: We look for expertise in:') +
      contentField('getinvolved','expertise_areas','Expertise Areas (one per line)','textarea','Each line becomes a tag',6),
      'getinvolved', ['careers_tag','careers_headline','careers_body','careers_email','careers_btn','expertise_heading','expertise_areas'])}`;
}

// ── Contact ───────────────────────────────────────────────────────────────────
function renderContact() {
  document.getElementById('content-area').innerHTML = `
    ${sectionCard('Page Hero',
      contentField('contact','hero_image','Hero Background Image','image','Full-width banner image for the Contact page') +
      contentField('contact','hero_tag','Hero Tag','text','Default: Contact') +
      contentField('contact','hero_h1','Page Title (H1)','text','Default: Get in Touch') +
      contentField('contact','hero_sub','Sub-headline','text'),
      'contact', ['hero_image','hero_tag','hero_h1','hero_sub'])}

    ${sectionCard('Contact Details',
      contentField('contact','general_email','General Enquiries Email') +
      contentField('contact','press_email','Press & Media Email') +
      contentField('contact','partnerships_email','Partnerships Email') +
      contentField('contact','careers_email','Careers Email') +
      contentField('contact','address','Registered Address','textarea','',2),
      'contact', ['general_email','press_email','partnerships_email','careers_email','address'])}`;
}

// ── Where We Work Map ─────────────────────────────────────────────────────────
async function renderMap() {
  const area = document.getElementById('content-area');
  const res  = await apiFetch('/admin/content.php').catch(() => ({ content: {} }));
  const mc   = res.content?.map || {};
  const mode = mc.mode?.value || 'countries';
  let countries = [], states = [];
  try { countries = JSON.parse(mc.countries?.value || '[]'); } catch {}
  try { states    = JSON.parse(mc.states?.value    || '[]'); } catch {}
  window._mapCountries = countries;
  window._mapStates    = states;
  window._mapMode      = mode;

  const isCountries = mode === 'countries';

  area.innerHTML = `
    ${sectionCard('Section Text',
      contentField('map','section_headline','Section Headline') +
      contentField('map','section_body','Section Description','textarea','',3),
      'map', ['section_headline','section_body'])}

    <div class="page-section">
      <div class="section-header"><h3>Map Mode</h3>${saveBtn('map',['mode'],'Save Mode')}</div>
      <div class="section-body">
        <div style="display:flex;gap:16px;flex-wrap:wrap">
          <label style="display:flex;align-items:flex-start;gap:12px;padding:16px 20px;border:2px solid ${isCountries?'var(--teal)':'var(--gray-200)'};border-radius:8px;cursor:pointer;flex:1;min-width:220px">
            <input type="radio" name="map-mode" id="mode-countries" value="countries" ${isCountries?'checked':''} style="margin-top:3px;accent-color:var(--teal)">
            <div>
              <strong style="display:block;color:var(--navy);margin-bottom:4px">🌍 Multiple Countries</strong>
              <span style="font-size:13px;color:var(--gray-600)">Show a world map with several countries highlighted — best when you work across multiple countries.</span>
            </div>
          </label>
          <label style="display:flex;align-items:flex-start;gap:12px;padding:16px 20px;border:2px solid ${!isCountries?'var(--teal)':'var(--gray-200)'};border-radius:8px;cursor:pointer;flex:1;min-width:220px">
            <input type="radio" name="map-mode" id="mode-states" value="states" ${!isCountries?'checked':''} style="margin-top:3px;accent-color:var(--teal)">
            <div>
              <strong style="display:block;color:var(--navy);margin-bottom:4px">📍 States / Regions</strong>
              <span style="font-size:13px;color:var(--gray-600)">Zoom into one country and highlight specific states or regions — best when all your work is within one country.</span>
            </div>
          </label>
        </div>
      </div>
    </div>

    ${!isCountries ? `
    <div class="page-section">
      <div class="section-header"><h3>Country Settings</h3>${saveBtn('map',['focus_country','focus_country_code'],'Save')}</div>
      <div class="section-body">
        <div class="form-row">
          <div class="form-group">
            <label>Country Name</label>
            <input type="text" id="cf_map_focus_country" value="${escHtml(mc.focus_country?.value||'Nigeria')}" placeholder="Nigeria" />
          </div>
          <div class="form-group">
            <label>ISO Alpha-3 Code</label>
            <input type="text" id="cf_map_focus_country_code" value="${escHtml(mc.focus_country_code?.value||'NGA')}" maxlength="3" style="font-family:monospace;text-transform:uppercase" placeholder="NGA" />
            <div class="hint">Used to identify the country. NGA = Nigeria.</div>
          </div>
        </div>
        <div class="hint" style="margin-top:-8px">The map is pre-loaded with Nigeria states. For other countries, contact your developer to add the state boundaries file.</div>
      </div>
    </div>` : ''}

    <div class="page-section">
      <div class="section-header">
        <h3>${isCountries ? `Countries (${countries.length})` : `States / Regions (${states.length})`}</h3>
        <button class="btn btn-primary" id="btn-add-item">+ Add ${isCountries?'Country':'State / Region'}</button>
      </div>
      <div class="section-body">
        ${(isCountries ? countries : states).length === 0
          ? `<p style="color:var(--gray-400);text-align:center;padding:24px 0">Nothing added yet. Click "+ Add" to get started.</p>`
          : `<table class="data-table">
              <thead><tr>
                ${isCountries ? '<th>ISO Code</th>' : ''}
                <th>Name</th><th>Note / Description</th><th>Actions</th>
              </tr></thead>
              <tbody>
                ${(isCountries ? countries : states).map((item, i) => `
                  <tr>
                    ${isCountries ? `<td><code style="background:var(--gray-100);padding:2px 8px;border-radius:4px;font-size:12px">${escHtml(item.code)}</code></td>` : ''}
                    <td><strong>${escHtml(item.name)}</strong></td>
                    <td style="font-size:13px;color:var(--gray-600)">${escHtml(item.note||'—')}</td>
                    <td style="display:flex;gap:6px">
                      <button class="btn btn-secondary btn-sm" onclick="${isCountries?'editCountry':'editState'}(${i})">Edit</button>
                      <button class="btn btn-danger btn-sm" onclick="${isCountries?'removeCountry':'removeState'}(${i})">Remove</button>
                    </td>
                  </tr>`).join('')}
              </tbody>
            </table>`}
      </div>
    </div>

    ${isCountries ? `<p style="font-size:12px;color:var(--gray-400);margin-top:4px">
      <strong>Common ISO codes:</strong> NGA · KEN · GHA · ZAF · ETH · TZA · UGA · RWA · SEN · CMR · EGY · MAR
    </p>` : `<p style="font-size:12px;color:var(--gray-400);margin-top:4px">
      <strong>Nigerian states:</strong> Abia · Adamawa · Akwa Ibom · Anambra · Bauchi · Bayelsa · Benue · Borno · Cross River · Delta · Ebonyi · Edo · Ekiti · Enugu · Gombe · Imo · Jigawa · Kaduna · Kano · Katsina · Kebbi · Kogi · Kwara · Lagos · Nasarawa · Niger · Ogun · Ondo · Osun · Oyo · Plateau · Rivers · Sokoto · Taraba · Yobe · Zamfara · Federal Capital Territory
    </p>`}`;

  // Wire up mode radio save
  document.querySelectorAll('input[name="map-mode"]').forEach(r => {
    r.addEventListener('change', () => {
      // visually update borders
      document.querySelectorAll('input[name="map-mode"]').forEach(rb => {
        rb.closest('label').style.borderColor = rb.checked ? 'var(--teal)' : 'var(--gray-200)';
      });
    });
  });
  const modeEl = document.getElementById('cf_map_mode');
  // override saveBtn for mode — read from radio
  const modeSaveBtn = document.getElementById('save_map_mode');
  if (modeSaveBtn) {
    modeSaveBtn.onclick = async () => {
      const selected = document.querySelector('input[name="map-mode"]:checked')?.value || 'countries';
      modeSaveBtn.textContent = 'Saving…'; modeSaveBtn.disabled = true;
      const ok = await saveBlock('map', 'mode', selected);
      toast(ok ? 'Mode saved — reload the site to see the change' : 'Save failed', ok ? 'success' : 'error');
      setTimeout(() => { modeSaveBtn.textContent = 'Save Mode'; modeSaveBtn.disabled = false; renderMap(); }, 1200);
    };
  }

  document.getElementById('btn-add-item').addEventListener('click', () => isCountries ? editCountry(-1) : editState(-1));
}

function editCountry(idx) {
  const countries = window._mapCountries || [];
  const c = idx >= 0 ? countries[idx] : {};
  showModal(idx >= 0 ? 'Edit Country' : 'Add Country', `
    <div class="form-row">
      <div class="form-group">
        <label>ISO Alpha-3 Code *</label>
        <input type="text" id="c-code" value="${escHtml(c.code||'')}" maxlength="3" style="font-family:monospace;text-transform:uppercase" placeholder="NGA" />
        <div class="hint">3-letter ISO 3166-1 code. NGA = Nigeria, KEN = Kenya, GHA = Ghana.</div>
      </div>
      <div class="form-group">
        <label>Country Name *</label>
        <input type="text" id="c-name" value="${escHtml(c.name||'')}" placeholder="Nigeria" />
      </div>
    </div>
    <div class="form-group">
      <label>Note / Description</label>
      <input type="text" id="c-note" value="${escHtml(c.note||'')}" placeholder="e.g. Headquarters &amp; pilot programmes" />
      <div class="hint">Shown as a tooltip on the map and in list view.</div>
    </div>`, `
    <button class="btn btn-secondary" onclick="hideModal()">Cancel</button>
    <button class="btn btn-primary" id="btn-save-item">Save Country</button>`);

  document.getElementById('c-code').addEventListener('input', e => e.target.value = e.target.value.toUpperCase());
  document.getElementById('btn-save-item').addEventListener('click', async () => {
    const code = document.getElementById('c-code').value.trim().toUpperCase();
    const name = document.getElementById('c-name').value.trim();
    const note = document.getElementById('c-note').value.trim();
    if (!code || code.length !== 3) return alert('Enter a valid 3-letter ISO code');
    if (!name) return alert('Country name is required');
    const arr = window._mapCountries || [];
    if (idx >= 0) arr[idx] = { code, name, note }; else arr.push({ code, name, note });
    window._mapCountries = arr;
    const ok = await saveBlock('map', 'countries', JSON.stringify(arr));
    if (ok) { hideModal(); toast('Saved'); renderMap(); } else toast('Save failed', 'error');
  });
}

async function removeCountry(idx) {
  const arr = window._mapCountries || [];
  if (!confirm(`Remove "${arr[idx].name}"?`)) return;
  arr.splice(idx, 1);
  window._mapCountries = arr;
  const ok = await saveBlock('map', 'countries', JSON.stringify(arr));
  if (ok) { toast('Removed'); renderMap(); } else toast('Failed', 'error');
}

function editState(idx) {
  const states = window._mapStates || [];
  const s = idx >= 0 ? states[idx] : {};
  showModal(idx >= 0 ? 'Edit State / Region' : 'Add State / Region', `
    <div class="form-group">
      <label>State / Region Name *</label>
      <input type="text" id="s-name" value="${escHtml(s.name||'')}" placeholder="e.g. Federal Capital Territory" />
      <div class="hint">Must match exactly how it appears in the map boundaries file. See the list of valid Nigerian state names below the table.</div>
    </div>
    <div class="form-group">
      <label>Note / Description</label>
      <input type="text" id="s-note" value="${escHtml(s.note||'')}" placeholder="e.g. NICU Network pilot site" />
    </div>`, `
    <button class="btn btn-secondary" onclick="hideModal()">Cancel</button>
    <button class="btn btn-primary" id="btn-save-item">Save State</button>`);

  document.getElementById('btn-save-item').addEventListener('click', async () => {
    const name = document.getElementById('s-name').value.trim();
    const note = document.getElementById('s-note').value.trim();
    if (!name) return alert('State name is required');
    const arr = window._mapStates || [];
    if (idx >= 0) arr[idx] = { name, note }; else arr.push({ name, note });
    window._mapStates = arr;
    const ok = await saveBlock('map', 'states', JSON.stringify(arr));
    if (ok) { hideModal(); toast('Saved'); renderMap(); } else toast('Save failed', 'error');
  });
}

async function removeState(idx) {
  const arr = window._mapStates || [];
  if (!confirm(`Remove "${arr[idx].name}"?`)) return;
  arr.splice(idx, 1);
  window._mapStates = arr;
  const ok = await saveBlock('map', 'states', JSON.stringify(arr));
  if (ok) { toast('Removed'); renderMap(); } else toast('Failed', 'error');
}

// ── Settings ──────────────────────────────────────────────────────────────────
function renderSettings() {
  document.getElementById('content-area').innerHTML = `

    <div style="margin-bottom:24px">
      <p style="color:var(--gray-600);font-size:13px;line-height:1.7">
        These settings control site-wide SEO, analytics, and social presence. Changes are saved to the database and applied across all pages.
      </p>
    </div>

    ${sectionCard('Site Preferences',
      `<div class="form-group">
        <label>Scroll Animations</label>
        <select id="cf_settings_animations_enabled" style="max-width:200px">
          <option value="true" ${getVal('settings','animations_enabled','true')==='true'?'selected':''}>Enabled</option>
          <option value="false" ${getVal('settings','animations_enabled')==='false'?'selected':''}>Disabled</option>
        </select>
        <div class="hint">Enable or disable fade-in scroll animations globally.</div>
       </div>`,
      'settings', ['animations_enabled'])}


    ${sectionCard('Organisation Identity',
      `<div class="form-group">
        <label>Organisation Name</label>
        <input type="text" id="cf_settings_org_name" value="${escHtml(getVal('settings','org_name','Health Systems Initiative'))}" placeholder="Health Systems Initiative" />
        <div class="hint">Displayed in the site header, footer, and across the site.</div>
       </div>
       <div class="form-group">
        <label>Tagline</label>
        <input type="text" id="cf_settings_org_tagline" value="${escHtml(getVal('settings','org_tagline','Building health systems. Saving lives.'))}" placeholder="Building health systems. Saving lives." />
        <div class="hint">Short phrase shown under the logo in the header and footer.</div>
       </div>
       <div class="form-group">
        <label>Footer Description</label>
        <textarea id="cf_settings_footer_desc" rows="3">${escHtml(getVal('settings','footer_desc','HSI is a non-profit organisation committed to strengthening health systems through effective, equitable, and sustainable digital technologies. Based in Nigeria, designed to contribute across Africa and other low- and middle-income settings.'))}</textarea>
        <div class="hint">Short paragraph shown in the footer below the logo.</div>
       </div>
       <div class="form-group">
        <label>Footer Registration Line 1</label>
        <input type="text" id="cf_settings_footer_reg_line1" value="${escHtml(getVal('settings','footer_reg_line1','Registered with the Corporate Affairs Commission, Nigeria'))}" />
       </div>
       <div class="form-group">
        <label>Footer Registration Line 2</label>
        <input type="text" id="cf_settings_footer_reg_line2" value="${escHtml(getVal('settings','footer_reg_line2','Incorporated Trustees under Part F, CAMA 2020'))}" />
        <div class="hint">Both lines appear at the bottom-left of the footer.</div>
       </div>`,
      'settings', ['org_name','org_tagline','footer_desc','footer_reg_line1','footer_reg_line2'])}

    ${sectionCard('Brand Assets',
      `<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">
        <!-- Logo -->
        <div class="form-group" style="margin:0">
          <label>Site Logo <span style="font-weight:400;color:var(--gray-400)">(SVG or PNG, transparent background)</span></label>
          <div class="img-upload-widget" id="logo-drop" style="height:110px;margin-top:8px" onclick="document.getElementById('logo-file').click()">
            <div class="img-upload-preview" id="logo-prev">
              ${getVal('settings','logo_url') ? `<img src="${escHtml(resolveImg(getVal('settings','logo_url')))}" style="max-height:80px;max-width:100%;object-fit:contain">` : '<div class="img-upload-placeholder"><div style="font-size:28px">🖼</div><div>Click or drag to upload logo</div></div>'}
            </div>
            <div class="img-upload-overlay">⬆ Replace</div>
          </div>
          <input type="file" id="logo-file" accept="image/svg+xml,image/png,image/webp" style="display:none" onchange="handleBrandUpload('logo-file','cf_settings_logo_url','logo-prev','logo-drop','brand')">
          <input type="hidden" id="cf_settings_logo_url" value="${escHtml(getVal('settings','logo_url',''))}">
          <input type="url" id="vis_cf_settings_logo_url" style="margin-top:6px;font-size:12px" placeholder="…or paste image URL" value="${escHtml(getVal('settings','logo_url',''))}" oninput="document.getElementById('cf_settings_logo_url').value=this.value;updateBrandPreview('cf_settings_logo_url','logo-prev')">
          <div class="hint">Appears in the site header and emails. SVG recommended for crisp scaling.</div>
        </div>
        <!-- Favicon -->
        <div class="form-group" style="margin:0">
          <label>Favicon <span style="font-weight:400;color:var(--gray-400)">(SVG or 32×32 PNG/ICO)</span></label>
          <div class="img-upload-widget" id="fav-drop" style="height:110px;margin-top:8px" onclick="document.getElementById('fav-file').click()">
            <div class="img-upload-preview" id="fav-prev">
              ${getVal('settings','favicon_url') ? `<img src="${escHtml(resolveImg(getVal('settings','favicon_url')))}" style="max-height:60px;max-width:80px;object-fit:contain">` : '<div class="img-upload-placeholder"><div style="font-size:28px">🔖</div><div>Click or drag to upload favicon</div></div>'}
            </div>
            <div class="img-upload-overlay">⬆ Replace</div>
          </div>
          <input type="file" id="fav-file" accept="image/svg+xml,image/png,image/x-icon,image/vnd.microsoft.icon" style="display:none" onchange="handleBrandUpload('fav-file','cf_settings_favicon_url','fav-prev','fav-drop','brand')">
          <input type="hidden" id="cf_settings_favicon_url" value="${escHtml(getVal('settings','favicon_url',''))}">
          <input type="url" id="vis_cf_settings_favicon_url" style="margin-top:6px;font-size:12px" placeholder="…or paste image URL" value="${escHtml(getVal('settings','favicon_url',''))}" oninput="document.getElementById('cf_settings_favicon_url').value=this.value;updateBrandPreview('cf_settings_favicon_url','fav-prev')">
          <div class="hint">Shown in browser tabs and bookmarks. SVG or 32×32 PNG works in all modern browsers.</div>
        </div>
       </div>`,
      'settings', ['logo_url','favicon_url'])}

    ${sectionCard('SEO & Meta Tags',
      `<div class="form-group">
        <label>Page Title Tag <span style="font-weight:400;color:var(--gray-400)">(50–60 characters recommended)</span></label>
        <input type="text" id="cf_settings_seo_title" value="${escHtml(getVal('settings','seo_title','Health Systems Initiative | Building Health Systems. Saving Lives.'))}" maxlength="70" />
        <div class="hint" id="title-counter"></div>
       </div>
       <div class="form-group">
        <label>Meta Description <span style="font-weight:400;color:var(--gray-400)">(150–160 characters recommended)</span></label>
        <textarea id="cf_settings_seo_description" rows="3" maxlength="200">${escHtml(getVal('settings','seo_description','HSI is a non-profit strengthening health systems through effective, equitable, and sustainable digital technologies — improving coordination, communication, and access to quality healthcare.'))}</textarea>
        <div class="hint" id="desc-counter"></div>
       </div>
       <div class="form-group">
        <label>Keywords <span style="font-weight:400;color:var(--gray-400)">(comma-separated)</span></label>
        <input type="text" id="cf_settings_seo_keywords" value="${escHtml(getVal('settings','seo_keywords','health systems strengthening, digital health Africa, healthcare coordination Nigeria, health equity, digital health non-profit, interoperability, health information systems'))}" />
        <div class="hint">Not a major ranking factor, but used by some aggregators.</div>
       </div>
       <div class="form-group">
        <label>OG / Social Share Image URL <span style="font-weight:400;color:var(--gray-400)">(1200×630 px)</span></label>
        <div style="display:flex;gap:8px;align-items:flex-start">
          <input type="url" id="cf_settings_og_image" value="${escHtml(getVal('settings','og_image',''))}" placeholder="https://healthsystemsinitiative.org/og-image.jpg" style="flex:1"/>
          ${getVal('settings','og_image') ? `<img src="${escHtml(resolveImg(getVal('settings','og_image')))}" style="height:52px;border-radius:4px;border:1px solid var(--gray-200)" onerror="this.style.display='none'">` : ''}
        </div>
        <div class="hint">Shown when pages are shared on WhatsApp, Twitter, LinkedIn etc. Recommended: HSI logo on navy background with tagline.</div>
       </div>
       <div class="form-group">
        <label>Canonical Site URL</label>
        <input type="url" id="cf_settings_site_url" value="${escHtml(getVal('settings','site_url','https://healthsystemsinitiative.org'))}" placeholder="https://healthsystemsinitiative.org" />
        <div class="hint">Used to build canonical tags and sitemap links. Include https://, no trailing slash.</div>
       </div>`,
      'settings', ['seo_title','seo_description','seo_keywords','og_image','site_url'])}

    ${sectionCard('Analytics',
      `<div class="form-group">
        <label>Google Analytics Measurement ID</label>
        <div style="display:flex;align-items:center;gap:10px">
          <input type="text" id="cf_settings_ga_id" value="${escHtml(getVal('settings','ga_id',''))}" placeholder="G-XXXXXXXXXX" style="max-width:240px;font-family:monospace" />
          <span style="font-size:12px;color:var(--gray-400)">Leave blank to disable GA</span>
        </div>
        <div class="hint">Find this in Google Analytics → Admin → Data Streams → your stream. Starts with <code style="background:var(--gray-100);padding:1px 5px;border-radius:3px">G-</code></div>
       </div>
       <div class="form-group">
        <label>Cookie Consent Tool</label>
        <select id="cf_settings_cookie_tool" style="max-width:280px">
          <option value="" ${!getVal('settings','cookie_tool')?'selected':''}>Not yet configured</option>
          <option value="cookieyes" ${getVal('settings','cookie_tool')==='cookieyes'?'selected':''}>CookieYes (recommended — free tier available)</option>
          <option value="cookiebot" ${getVal('settings','cookie_tool')==='cookiebot'?'selected':''}>Cookiebot</option>
          <option value="osano" ${getVal('settings','cookie_tool')==='osano'?'selected':''}>Osano</option>
          <option value="custom" ${getVal('settings','cookie_tool')==='custom'?'selected':''}>Custom / other</option>
        </select>
        <div class="hint">Required under GDPR/NDPR before running analytics. CookieYes has a generous free plan.</div>
       </div>
       <div class="form-group">
        <label>Cookie Consent Script / Embed Code</label>
        <textarea id="cf_settings_cookie_script" rows="4" placeholder="Paste the &lt;script&gt; tag from your cookie consent provider here">${escHtml(getVal('settings','cookie_script',''))}</textarea>
        <div class="hint">This will be injected into the &lt;head&gt; of every page.</div>
       </div>`,
      'settings', ['ga_id','cookie_tool','cookie_script'])}

    ${sectionCard('Email & SMTP Settings',
      `<div class="form-group">
        <label>Primary / Info Email</label>
        <input type="email" id="cf_settings_info_email" value="${escHtml(getVal('settings','info_email','info@healthsystemsinitiative.org'))}" placeholder="info@healthsystemsinitiative.org" />
        <div class="hint">Recommended: <strong>info@healthsystemsinitiative.org</strong> — set up by IT Lead via Google Workspace or similar.</div>
       </div>
       <div class="form-group">
        <label>Contact Form Recipient</label>
        <input type="email" id="cf_settings_form_recipient" value="${escHtml(getVal('settings','form_recipient',''))}" placeholder="Where contact form submissions get emailed" />
       </div>
       <hr style="margin:24px 0; border:none; border-top:1px solid var(--gray-200)">
       <h4 style="margin:0 0 16px 0">SMTP Configuration</h4>
       <div style="display:flex;gap:12px">
         <div class="form-group" style="flex:2">
          <label>SMTP Host</label>
          <input type="text" id="cf_settings_smtp_host" value="${escHtml(getVal('settings','smtp_host',''))}" placeholder="smtp.gmail.com" />
         </div>
         <div class="form-group" style="flex:1">
          <label>SMTP Port</label>
          <input type="text" id="cf_settings_smtp_port" value="${escHtml(getVal('settings','smtp_port','465'))}" placeholder="465 or 587" />
         </div>
       </div>
       <div class="form-group">
        <label>SMTP Username</label>
        <input type="text" id="cf_settings_smtp_user" value="${escHtml(getVal('settings','smtp_user',''))}" placeholder="you@domain.com" />
       </div>
       <div class="form-group">
        <label>SMTP Password</label>
        <input type="password" id="cf_settings_smtp_pass" value="${escHtml(getVal('settings','smtp_pass',''))}" placeholder="••••••••" />
       </div>
       <div class="form-group">
        <label>From Address</label>
        <input type="email" id="cf_settings_smtp_from" value="${escHtml(getVal('settings','smtp_from',''))}" placeholder="noreply@domain.com" />
       </div>`,
      'settings', ['info_email','form_recipient','smtp_host','smtp_port','smtp_user','smtp_pass','smtp_from'])}

    ${sectionCard('Social Media',
      `<div class="form-group">
        <label>LinkedIn Page URL</label>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="color:var(--gray-400);font-size:18px">in</span>
          <input type="url" id="cf_settings_linkedin" value="${escHtml(getVal('settings','linkedin',''))}" placeholder="https://linkedin.com/company/health-systems-initiative" />
        </div>
       </div>
       <div class="form-group">
        <label>Twitter / X Handle</label>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="color:var(--gray-400);font-weight:700">@</span>
          <input type="text" id="cf_settings_twitter" value="${escHtml(getVal('settings','twitter',''))}" placeholder="HSI_Africa" style="max-width:260px"/>
        </div>
        <div class="hint">Handle only, without the @</div>
       </div>
       <div class="form-group">
        <label>Facebook Page URL</label>
        <input type="url" id="cf_settings_facebook" value="${escHtml(getVal('settings','facebook',''))}" placeholder="https://facebook.com/…" />
       </div>
       <div class="form-group">
        <label>YouTube Channel URL</label>
        <input type="url" id="cf_settings_youtube" value="${escHtml(getVal('settings','youtube',''))}" placeholder="https://youtube.com/@…" />
       </div>
       <div class="form-group">
        <label>Other / Additional Social Link</label>
        <input type="url" id="cf_settings_social_other" value="${escHtml(getVal('settings','social_other',''))}" placeholder="https://…" />
        <div class="hint">Instagram, Threads, ResearchGate, etc.</div>
       </div>`,
      'settings', ['linkedin','twitter','facebook','youtube','social_other'])}

    ${sectionCard('Donations',
      `<div class="form-group">
        <label>Donorbox Campaign URL</label>
        <input type="url" id="cf_settings_donorbox_url" value="${escHtml(getVal('settings','donorbox_url',''))}" placeholder="https://donorbox.org/your-campaign" />
        <div class="hint">The full URL to your Donorbox campaign page. Used on the Donate and Get Involved pages.</div>
       </div>
       <div class="form-group">
        <label>Paystack Payment URL <span style="font-weight:400;color:var(--gray-400)">(NGN)</span></label>
        <input type="url" id="cf_settings_paystack_url" value="${escHtml(getVal('settings','paystack_url',''))}" placeholder="https://paystack.com/pay/your-page" />
        <div class="hint">Your Paystack payment link for Nigerian Naira donations.</div>
       </div>`,
      'settings', ['donorbox_url','paystack_url'])}

    ${sectionCard('Site Widgets',
      `<p style="color:var(--gray-600);font-size:13px;margin-bottom:20px">Controls the floating chat button, sticky donate bar, and cookie consent banner shown across the site.</p>

       <h4 style="font-size:13px;font-weight:700;color:var(--navy);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid var(--gray-200)">WhatsApp &amp; Contact Button</h4>
       <div class="form-group">
        <label>WhatsApp Number <span style="font-weight:400;color:var(--gray-400)">(digits only, with country code)</span></label>
        <div style="display:flex;align-items:center;gap:8px">
          <span style="color:#25D366;font-size:18px">📱</span>
          <input type="text" id="cf_settings_whatsapp_number" value="${escHtml(getVal('settings','whatsapp_number',''))}" placeholder="2348012345678" style="max-width:240px;font-family:monospace" />
        </div>
        <div class="hint">Include country code without +. E.g. <code style="background:var(--gray-100);padding:1px 5px;border-radius:3px">2348012345678</code> for a Nigerian number.</div>
       </div>

       <h4 style="font-size:13px;font-weight:700;color:var(--navy);margin:20px 0 14px;padding-bottom:8px;border-bottom:1px solid var(--gray-200)">Sticky Donate Bar</h4>
       <div class="form-group">
        <label>Sticky Bar Message</label>
        <input type="text" id="cf_settings_sticky_donate_text" value="${escHtml(getVal('settings','sticky_donate_text','Support stronger health systems in Nigeria'))}" placeholder="Support stronger health systems in Nigeria" />
        <div class="hint">Short message shown on the left side of the sticky bar at the bottom of the page.</div>
       </div>
       <div class="form-group">
        <label>Sticky Bar Button Label</label>
        <input type="text" id="cf_settings_sticky_donate_btn" value="${escHtml(getVal('settings','sticky_donate_btn','Donate ♥'))}" placeholder="Donate ♥" style="max-width:220px" />
       </div>

       <h4 style="font-size:13px;font-weight:700;color:var(--navy);margin:20px 0 14px;padding-bottom:8px;border-bottom:1px solid var(--gray-200)">Cookie Consent Banner</h4>
       <div class="form-group">
        <label>Cookie Notice Text</label>
        <textarea id="cf_settings_cookie_notice_text" rows="2" placeholder="to improve your experience and analyse site usage. We do not sell your data.">${escHtml(getVal('settings','cookie_notice_text','to improve your experience and analyse site usage. We do not sell your data.'))}</textarea>
        <div class="hint">Appended after "We use cookies" in the banner. Keep it brief.</div>
       </div>
       <div class="form-group">
        <label>Privacy Policy URL</label>
        <input type="text" id="cf_settings_cookie_policy_url" value="${escHtml(getVal('settings','cookie_policy_url','#/privacy'))}" placeholder="#/privacy" style="max-width:340px" />
        <div class="hint">The "Privacy Policy" link in the cookie banner. Use <code style="background:var(--gray-100);padding:1px 5px;border-radius:3px">#/privacy</code> if you have an internal privacy page.</div>
       </div>`,
      'settings', ['whatsapp_number','sticky_donate_text','sticky_donate_btn','cookie_notice_text','cookie_policy_url'])}

    ${sectionCard('Custom CSS',
      `<div class="form-group">
        <label>Custom CSS <span style="font-weight:400;color:var(--gray-400)">(injected into every page)</span></label>
        <textarea id="cf_settings_custom_css" rows="16" style="font-family:monospace;font-size:13px;line-height:1.6;background:#1e2a3a;color:#e2e8f0;border-color:#2d3f55;border-radius:var(--radius);resize:vertical" placeholder="/* Add custom CSS overrides here */&#10;&#10;.my-class {&#10;  color: red;&#10;}">${escHtml(getVal('settings','custom_css',''))}</textarea>
        <div class="hint" style="margin-top:8px">⚠️ Applied site-wide. Overrides are injected after all other styles. Use specific selectors to avoid unintended side effects.</div>
       </div>`,
      'settings', ['custom_css'])}
  `;

  // Live character counters for SEO fields
  function counter(inputId, hintId, min, max) {
    const el = document.getElementById(inputId);
    const hint = document.getElementById(hintId);
    if (!el || !hint) return;
    function update() {
      const n = el.value.length;
      const ok = n >= min && n <= max;
      hint.innerHTML = `<span style="color:${n > max ? 'var(--red)' : ok ? '#15803D' : 'var(--gray-400)'};font-weight:${ok?'600':'400'}">${n} / ${max} characters ${ok ? '✓' : n > max ? '— too long' : `(aim for ${min}–${max})`}</span>`;
    }
    el.addEventListener('input', update);
    update();
  }
  counter('cf_settings_seo_title', 'title-counter', 50, 60);
  counter('cf_settings_seo_description', 'desc-counter', 150, 160);
}

// ── Team Members ──────────────────────────────────────────────────────────────
async function renderTeam() {
  const area = document.getElementById('content-area');
  const res = await apiFetch('/admin/team.php').catch(() => ({ team: [] }));
  const team = res.team || [];

  area.innerHTML = `
    ${sectionCard('Page Hero',
      contentField('team','hero_image','Hero Background Image','image','Full-width banner image for the Our Team page') +
      contentField('team','hero_tag','Hero Tag','text','Default: Our Team') +
      contentField('team','hero_h1','Page Title (H1)','text','Default: The People Behind HSI') +
      contentField('team','hero_sub','Sub-headline','text'),
      'team', ['hero_image','hero_tag','hero_h1','hero_sub'])}

    <div style="display:flex;justify-content:flex-end;margin-bottom:16px">
      <button class="btn btn-primary" id="btn-add-member">+ Add Team Member</button>
    </div>
    <div class="page-section">
      <div class="section-header"><h3>Team Members (${team.length})</h3></div>
      <table class="data-table">
        <thead><tr><th>Name</th><th>Role</th><th>Photo</th><th>Order</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          ${team.map(m => `
            <tr id="row-${m.id}">
              <td><strong>${escHtml(m.name)}</strong></td>
              <td>${escHtml(m.role)}</td>
              <td>${m.photo_url ? `<img src="${escHtml(resolveImg(m.photo_url))}" class="img-preview" onerror="this.style.display='none'">` : '<span style="color:var(--gray-400);font-size:12px">No photo</span>'}</td>
              <td>${m.sort_order}</td>
              <td><span class="badge ${m.published ? 'badge-green' : 'badge-gray'}">${m.published ? 'Published' : 'Hidden'}</span></td>
              <td style="display:flex;gap:6px">
                <button class="btn btn-secondary btn-sm" onclick="editMember(${m.id})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteMember(${m.id},'${escHtml(m.name)}')">Delete</button>
              </td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>`;

  document.getElementById('btn-add-member').addEventListener('click', () => editMember(0));
}

function memberModalBody(m = {}) {
  const hasPhoto = !!(m.photo_url);
  return `
    <div class="form-row">
      <div class="form-group"><label>Full Name *</label><input type="text" id="m-name" value="${escHtml(m.name||'')}" /></div>
      <div class="form-group"><label>Role / Title *</label><input type="text" id="m-role" value="${escHtml(m.role||'')}" /></div>
    </div>
    <div class="form-group"><label>Bio (50–80 words)</label><textarea id="m-bio" rows="5">${escHtml(m.bio||'')}</textarea></div>

    <div class="form-group">
      <label>Photo</label>
      <div id="photo-upload-area" style="border:2px dashed var(--gray-200);border-radius:8px;padding:16px;text-align:center;cursor:pointer;transition:border-color .2s;position:relative" onclick="document.getElementById('m-photo-file').click()">
        ${hasPhoto
          ? `<img id="photo-preview" src="${escHtml(resolveImg(m.photo_url))}" style="max-height:120px;max-width:200px;border-radius:6px;display:block;margin:0 auto 8px" />`
          : `<div id="photo-preview" style="display:none"><img id="photo-preview-img" style="max-height:120px;max-width:200px;border-radius:6px;margin:0 auto 8px;display:block"/></div>`
        }
        <div id="upload-prompt" style="${hasPhoto ? 'display:none' : ''}">
          <div style="font-size:28px;margin-bottom:6px">📷</div>
          <div style="font-weight:600;color:var(--navy)">Click to upload photo</div>
          <div style="font-size:12px;color:var(--gray-400);margin-top:4px">JPG, PNG, WebP · Max 5 MB</div>
        </div>
        <div id="upload-loading" style="display:none;color:var(--teal);font-weight:600;font-size:13px">Uploading…</div>
        <input type="file" id="m-photo-file" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none" />
      </div>
      ${hasPhoto ? `<div style="margin-top:8px;display:flex;align-items:center;gap:8px"><span style="font-size:12px;color:var(--gray-400);flex:1" id="photo-filename">Current photo</span><button type="button" class="btn btn-danger btn-sm" id="btn-clear-photo">Remove</button></div>` : `<div style="margin-top:8px;display:flex;align-items:center;gap:8px"><span style="font-size:12px;color:var(--gray-400);flex:1" id="photo-filename"></span><button type="button" class="btn btn-danger btn-sm" id="btn-clear-photo" style="display:none">Remove</button></div>`}
      <input type="hidden" id="m-photo" value="${escHtml(m.photo_url||'')}" />
    </div>

    <div class="form-row">
      <div class="form-group"><label>Display Order</label><input type="number" id="m-order" value="${m.sort_order||0}" /></div>
      <div class="form-group"><label style="margin-bottom:12px">Visibility</label>
        <label class="toggle"><input type="checkbox" id="m-pub" ${m.published!==0?'checked':''}><span class="toggle-track"></span> Published on site</label>
      </div>
    </div>`;
}

function initPhotoUpload() {
  const fileInput  = document.getElementById('m-photo-file');
  const hiddenUrl  = document.getElementById('m-photo');
  const area       = document.getElementById('photo-upload-area');
  const prompt     = document.getElementById('upload-prompt');
  const loading    = document.getElementById('upload-loading');
  const filename   = document.getElementById('photo-filename');
  const clearBtn   = document.getElementById('btn-clear-photo');

  // Show a preview from an <img> that may already be on the page
  function showPreview(src, name) {
    // If a static img already exists (edit mode), swap src; otherwise use the hidden preview block
    const staticImg = area.querySelector('img#photo-preview');
    if (staticImg) {
      staticImg.src = src;
      staticImg.style.display = 'block';
    } else {
      const dynBlock = document.getElementById('photo-preview');
      const dynImg   = document.getElementById('photo-preview-img');
      dynImg.src = src;
      dynBlock.style.display = 'block';
    }
    prompt.style.display    = 'none';
    filename.textContent    = name || 'Photo uploaded';
    clearBtn.style.display  = 'inline-flex';
    area.style.borderColor  = 'var(--teal)';
  }

  function clearPhoto() {
    hiddenUrl.value = '';
    const staticImg = area.querySelector('img#photo-preview');
    if (staticImg) staticImg.style.display = 'none';
    const dynBlock  = document.getElementById('photo-preview');
    if (dynBlock) dynBlock.style.display = 'none';
    prompt.style.display   = '';
    filename.textContent   = '';
    clearBtn.style.display = 'none';
    area.style.borderColor = '';
    fileInput.value        = '';
  }

  // Drag-and-drop
  area.addEventListener('dragover', e => { e.preventDefault(); area.style.borderColor = 'var(--teal)'; });
  area.addEventListener('dragleave', () => { area.style.borderColor = ''; });
  area.addEventListener('drop', e => {
    e.preventDefault();
    const file = e.dataTransfer.files[0];
    if (file) handleFile(file);
  });

  fileInput.addEventListener('change', () => {
    if (fileInput.files[0]) handleFile(fileInput.files[0]);
  });

  clearBtn.addEventListener('click', e => { e.stopPropagation(); clearPhoto(); });

  async function handleFile(file) {
    if (file.size > 5 * 1024 * 1024) { toast('File too large (max 5MB)', 'error'); return; }
    prompt.style.display   = 'none';
    loading.style.display  = 'block';
    clearBtn.style.display = 'none';

    const fd = new FormData();
    fd.append('file', file);
    try {
      const res = await fetch('../api/admin/upload.php?type=team', {
        method: 'POST', credentials: 'include', body: fd
      });
      const data = await res.json();
      if (data.success) {
        hiddenUrl.value = data.url;
        showPreview(data.url, file.name);
        toast('Photo uploaded');
      } else {
        toast(data.error || 'Upload failed', 'error');
        prompt.style.display = '';
      }
    } catch {
      toast('Upload failed', 'error');
      prompt.style.display = '';
    } finally {
      loading.style.display = 'none';
    }
  }
}

async function editMember(id) {
  let m = {};
  if (id) {
    const res = await apiFetch('/admin/team.php');
    m = res.team?.find(x => x.id == id) || {};
  }
  showModal(id ? 'Edit Team Member' : 'Add Team Member', memberModalBody(m), `
    <button class="btn btn-secondary" onclick="hideModal()">Cancel</button>
    <button class="btn btn-primary" id="btn-save-member">Save Member</button>`);

  initPhotoUpload();

  document.getElementById('btn-save-member').addEventListener('click', async () => {
    const payload = {
      id, name: document.getElementById('m-name').value,
      role: document.getElementById('m-role').value,
      bio: document.getElementById('m-bio').value,
      photo_url: document.getElementById('m-photo').value,
      sort_order: document.getElementById('m-order').value,
      published: document.getElementById('m-pub').checked ? 1 : 0,
    };
    if (!payload.name) return alert('Name is required');
    const res = await apiFetch('/admin/team.php', { method: 'POST', body: JSON.stringify(payload) });
    if (res.success) { hideModal(); toast('Team member saved'); renderTeam(); }
    else toast('Save failed', 'error');
  });
}

async function deleteMember(id, name) {
  if (!confirm(`Delete "${name}"? This cannot be undone.`)) return;
  const res = await apiFetch(`/admin/team.php?id=${id}`, { method: 'DELETE' });
  if (res.success) { toast('Member deleted'); renderTeam(); }
  else toast('Delete failed', 'error');
}

// ── News Articles ─────────────────────────────────────────────────────────────
async function renderNews() {
  const area = document.getElementById('content-area');
  const res = await apiFetch('/admin/news.php').catch(() => ({ articles: [] }));
  const articles = res.articles || [];

  area.innerHTML = `
    ${sectionCard('Page Hero',
      contentField('news','hero_image','Hero Background Image','image','Full-width banner image for the News & Resources page') +
      contentField('news','hero_h1','Page Title (H1)','text','Default: Latest from HSI') +
      contentField('news','hero_sub','Sub-headline','text'),
      'news', ['hero_image','hero_h1','hero_sub'])}

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
      <a href="../api/admin/subscribers.php?format=csv" class="btn btn-secondary" style="font-size:13px" title="Download newsletter subscriber list as CSV">
        ⬇ Export Subscribers CSV
      </a>
      <button class="btn btn-primary" id="btn-add-article">+ Add Article</button>
    </div>
    <div class="page-section">
      <div class="section-header"><h3>Articles (${articles.length})</h3></div>
      <table class="data-table">
        <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>
        <tbody>
          ${articles.map(a => `
            <tr>
              <td><strong>${escHtml(a.title)}</strong><div style="font-size:12px;color:var(--gray-400);margin-top:2px">${escHtml((a.excerpt||'').substring(0,80))}…</div></td>
              <td style="font-size:13px">${escHtml(a.author||'—')}</td>
              <td>${a.category ? `<span class="badge badge-gray">${escHtml(a.category)}</span>` : '—'}</td>
              <td><span class="badge ${a.published ? 'badge-green' : 'badge-gray'}">${a.published ? 'Published' : 'Draft'}</span></td>
              <td style="font-size:12px;white-space:nowrap">${new Date(a.created_at).toLocaleDateString()}</td>
              <td style="display:flex;gap:6px">
                <button class="btn btn-secondary btn-sm" onclick="editArticle(${a.id})">Edit</button>
                <button class="btn btn-danger btn-sm" onclick="deleteArticle(${a.id},'${escHtml(a.title).replace(/'/g,"\\'")}')">Delete</button>
              </td>
            </tr>`).join('')}
        </tbody>
      </table>
    </div>`;

  document.getElementById('btn-add-article').addEventListener('click', () => editArticle(0));
}

function articleModalBody(a = {}) {
  return `
    <div class="form-group"><label>Title *</label><input type="text" id="a-title" value="${escHtml(a.title||'')}" /></div>
    <div class="form-row">
      <div class="form-group"><label>Author</label><input type="text" id="a-author" value="${escHtml(a.author||'')}" /></div>
      <div class="form-group"><label>Category</label><input type="text" id="a-category" value="${escHtml(a.category||'')}" placeholder="e.g. Announcement" /></div>
    </div>
    <div class="form-group"><label>Excerpt (shown in listings)</label><textarea id="a-excerpt" rows="3">${escHtml(a.excerpt||'')}</textarea></div>
    <div class="form-group">
      <label>Full Article Body</label>
      <div id="a-body-editor"></div>
      <input type="hidden" id="a-body" value="${escHtml(a.body||'')}">
    </div>
    <div class="form-group">
      <label>Cover Image</label>
      <div class="photo-upload-zone" id="news-img-zone" style="height:140px" onclick="document.getElementById('news-img-file').click()">
        ${a.image_url ? `<img src="${escHtml(resolveImg(a.image_url))}" style="max-height:120px;max-width:100%;object-fit:cover;border-radius:4px">` : '<span class="puz-hint">Click or drag to upload image</span>'}
      </div>
      <input type="file" id="news-img-file" accept="image/*" style="display:none">
      <input type="hidden" id="a-image" value="${escHtml(a.image_url||'')}">
      <p style="font-size:12px;color:var(--gray-400);margin-top:6px">Or paste a URL: <input type="url" id="a-image-url" placeholder="https://…" value="${escHtml(a.image_url||'')}" style="font-size:12px;padding:4px 8px;width:300px;border:1px solid var(--border);border-radius:4px" oninput="document.getElementById('a-image').value=this.value"></p>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Display Order</label><input type="number" id="a-order" value="${a.sort_order||0}" /></div>
      <div class="form-group"><label style="margin-bottom:12px">Visibility</label>
        <label class="toggle"><input type="checkbox" id="a-pub" ${a.published!==0?'checked':''}><span class="toggle-track"></span> Published</label>
      </div>
    </div>`;
}

async function editArticle(id) {
  let a = {};
  if (id) {
    const res = await apiFetch('/admin/news.php');
    a = res.articles?.find(x => x.id == id) || {};
  }
  showModal(id ? 'Edit Article' : 'New Article', articleModalBody(a), `
    <button class="btn btn-secondary" onclick="hideModal()">Cancel</button>
    <button class="btn btn-primary" id="btn-save-article">Save Article</button>`);

  // Wire up Quill rich text editor for article body
  const quill = new Quill('#a-body-editor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ header: [2, 3, false] }],
        ['bold', 'italic', 'underline'],
        [{ list: 'ordered' }, { list: 'bullet' }],
        ['link', 'blockquote'],
        ['clean'],
      ]
    },
    placeholder: 'Write the full article here…',
  });
  // Load existing HTML body
  const existingBody = document.getElementById('a-body').value;
  if (existingBody) quill.root.innerHTML = existingBody;
  // Keep hidden input in sync
  quill.on('text-change', () => {
    document.getElementById('a-body').value = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
  });

  // Wire up news image upload
  const newsFileInput = document.getElementById('news-img-file');
  if (newsFileInput) {
    newsFileInput.addEventListener('change', async () => {
      const file = newsFileInput.files[0];
      if (!file) return;
      const zone = document.getElementById('news-img-zone');
      zone.innerHTML = '<span class="puz-hint">Uploading…</span>';
      const fd = new FormData();
      fd.append('file', file);
      try {
        const res = await fetch('../api/admin/upload.php?type=news', { method:'POST', body:fd, credentials:'include' });
        const data = await res.json();
        if (data.url) {
          document.getElementById('a-image').value = data.url;
          document.getElementById('a-image-url').value = data.url;
          zone.innerHTML = `<img src="${resolveImg(data.url)}" style="max-height:120px;max-width:100%;object-fit:cover;border-radius:4px">`;
        } else {
          zone.innerHTML = `<span class="puz-hint" style="color:red">${data.error||'Upload failed'}</span>`;
        }
      } catch { zone.innerHTML = '<span class="puz-hint" style="color:red">Upload failed</span>'; }
    });

    const zone = document.getElementById('news-img-zone');
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('drag-over'); });
    zone.addEventListener('dragleave', () => zone.classList.remove('drag-over'));
    zone.addEventListener('drop', e => {
      e.preventDefault(); zone.classList.remove('drag-over');
      const dt = e.dataTransfer; if (dt.files[0]) { newsFileInput.files = dt.files; newsFileInput.dispatchEvent(new Event('change')); }
    });
  }

  document.getElementById('btn-save-article').addEventListener('click', async () => {
    const payload = {
      id, title: document.getElementById('a-title').value,
      author: document.getElementById('a-author').value,
      excerpt: document.getElementById('a-excerpt').value,
      body: document.getElementById('a-body').value,
      image_url: document.getElementById('a-image').value,
      category: document.getElementById('a-category').value,
      sort_order: document.getElementById('a-order').value,
      published: document.getElementById('a-pub').checked ? 1 : 0,
    };
    if (!payload.title) return alert('Title is required');
    const res = await apiFetch('/admin/news.php', { method: 'POST', body: JSON.stringify(payload) });
    if (res.success) { hideModal(); toast('Article saved'); renderNews(); }
    else toast('Save failed', 'error');
  });
}

async function deleteArticle(id, title) {
  if (!confirm(`Delete "${title}"?`)) return;
  const res = await apiFetch(`/admin/news.php?id=${id}`, { method: 'DELETE' });
  if (res.success) { toast('Article deleted'); renderNews(); }
  else toast('Delete failed', 'error');
}

// ── Messages ──────────────────────────────────────────────────────────────────
async function renderMessages() {
  const area = document.getElementById('content-area');
  const res = await apiFetch('/admin/messages.php').catch(() => ({ messages: [] }));
  const messages = res.messages || [];
  const unread = messages.filter(m => !m.read_at).length;

  area.innerHTML = `
    <div class="page-section">
      <div class="section-header"><h3>Contact Messages (${messages.length}) — ${unread} unread</h3></div>
      ${messages.length === 0 ? '<div class="section-body" style="color:var(--gray-400);text-align:center;padding:32px">No messages yet.</div>' : `
      <table class="data-table">
        <thead><tr><th>Status</th><th>Name</th><th>Email</th><th>Type</th><th>Message</th><th>Date</th></tr></thead>
        <tbody>
          ${messages.map(m => `
            <tr style="${!m.read_at ? 'font-weight:600' : ''}">
              <td><span class="badge ${m.read_at ? 'badge-gray' : 'badge-green'}">${m.read_at ? 'Read' : 'New'}</span></td>
              <td>${escHtml(m.name)}<div style="font-size:11px;color:var(--gray-400)">${escHtml(m.org||'')}</div></td>
              <td style="font-size:13px"><a href="mailto:${escHtml(m.email)}" style="color:var(--teal)">${escHtml(m.email)}</a></td>
              <td style="font-size:12px">${escHtml(m.type||'—')}</td>
              <td style="font-size:13px;max-width:280px">${escHtml((m.message||'').substring(0,100))}…</td>
              <td style="font-size:12px;white-space:nowrap">${new Date(m.created_at).toLocaleDateString()}</td>
            </tr>`).join('')}
        </tbody>
      </table>`}
    </div>`;
}
</script>
</body>
</html>
