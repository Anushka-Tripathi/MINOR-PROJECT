// ═══════════════════════════════════════════════════════════
//  MediCare Pro — Global JavaScript Utilities v3.0
//  Full debug edition with AI path fix
// ═══════════════════════════════════════════════════════════

const MediCare = (() => {

  // ── Determine correct API base path ─────────────────────
  // Pages live at:
  //   /Medicare-Pro/index.html          → apiBase = "backend/api"
  //   /Medicare-Pro/admin/dashboard.html → apiBase = "../backend/api"
  //   /Medicare-Pro/doctor/dashboard.html → apiBase = "../backend/api"
  //   /Medicare-Pro/patient/dashboard.html → apiBase = "../backend/api"
  function getApiBase() {
    const path = window.location.pathname;
    // If the page is inside a subfolder (admin/, doctor/, patient/)
    if (/\/(admin|doctor|patient)\/[^/]*$/.test(path)) {
      return '../backend/api';
    }
    return 'backend/api';
  }

  function getRootPath() {
    const path = window.location.pathname;
    if (/\/(admin|doctor|patient)\/[^/]*$/.test(path)) {
      return '../';
    }
    return './';
  }

  // Build an absolute URL for any API endpoint
  function buildApiUrl(endpoint, params = {}) {
    // Get the page's directory (everything up to the last slash)
    const pageDir  = window.location.href.replace(/[^/]+$/, '');
    const base     = getApiBase();
    const url      = new URL(base + '/' + endpoint, pageDir);
    Object.entries(params).forEach(([k, v]) => {
      if (v !== '' && v !== null && v !== undefined) {
        url.searchParams.set(k, String(v));
      }
    });
    return url.toString();
  }

  // ── Core fetch wrapper ───────────────────────────────────
  const API = {
    async call(endpoint, method = 'GET', body = null, params = {}) {
      const url  = buildApiUrl(endpoint, params);
      const opts = {
        method,
        headers: { 'Content-Type': 'application/json' },
      };
      if (body !== null) opts.body = JSON.stringify(body);

      try {
        const res  = await fetch(url, opts);
        const text = await res.text();

        // Try to parse as JSON
        try {
          return JSON.parse(text);
        } catch {
          // Not JSON — probably a PHP error page
          console.error(`[MediCare] Non-JSON from ${endpoint}:`, text.substring(0, 400));
          return {
            success: false,
            error: `Server returned non-JSON (HTTP ${res.status}). Check Apache/PHP error logs. Response: ${text.substring(0, 150)}`,
          };
        }
      } catch (networkErr) {
        console.error(`[MediCare] Fetch failed for ${endpoint}:`, networkErr);
        return {
          success: false,
          error: `Cannot reach server. Is XAMPP running? Apache started? URL: ${buildApiUrl(endpoint)}`,
        };
      }
    },

    get:    (ep, params = {}) => API.call(ep, 'GET',    null, params),
    post:   (ep, body)        => API.call(ep, 'POST',   body, {}),
    put:    (ep, body, p)     => API.call(ep, 'PUT',    body, p || {}),
    delete: (ep, params)      => API.call(ep, 'DELETE', null, params || {}),
  };

  // ── AI Request ───────────────────────────────────────────
  // Calls ai.php via POST — the module, feature, payload pattern
  async function aiRequest(module, feature, payload = {}) {
    const result = await API.post('ai.php', { module, feature, payload });

    // Surface error to UI console for debugging
    if (!result.success) {
      console.warn(`[MediCare AI] ${module}:${feature} failed:`, result.error);
    }

    return result;
  }

  // ── AI Test (call from browser console to debug) ─────────
  // Usage: MediCare.testAI()
  async function testAI() {
    console.log('[MediCare AI Test] Sending test request to ai.php...');
    const url = buildApiUrl('ai.php');
    console.log('[MediCare AI Test] URL:', url);
    const result = await aiRequest('patient', 'health_insights', { patient: 'Test', age: 30 });
    console.log('[MediCare AI Test] Result:', result);
    return result;
  }

  // ── Auth — persisted in both storages ───────────────────
  function getUser() {
    try {
      const s = sessionStorage.getItem('medicare_user') || localStorage.getItem('medicare_user');
      return s ? JSON.parse(s) : null;
    } catch { return null; }
  }

  function setUser(user) {
    const s = JSON.stringify(user);
    try { sessionStorage.setItem('medicare_user', s); } catch {}
    try { localStorage.setItem('medicare_user', s);   } catch {}
  }

  function clearUser() {
    try { sessionStorage.removeItem('medicare_user'); } catch {}
    try { localStorage.removeItem('medicare_user');   } catch {}
  }

  function requireAuth(role) {
    const user = getUser();
    if (!user || (role && user.role !== role)) {
      window.location.href = getRootPath() + (role || '') + '/login.html';
      return null;
    }
    return user;
  }

  // Logout always goes to the main landing page
  function logout() {
    clearUser();
    window.location.href = getRootPath() + 'index.html';
  }

  // ── Toast notifications ──────────────────────────────────
  function getToastContainer() {
    let el = document.getElementById('medicare-toasts');
    if (!el) {
      el = document.createElement('div');
      el.id = 'medicare-toasts';
      el.className = 'toast-container';
      document.body.appendChild(el);
    }
    return el;
  }

  function toast(message, type = 'info', duration = 3800) {
    const icons = {
      success: 'fa-circle-check',
      error:   'fa-circle-xmark',
      warning: 'fa-triangle-exclamation',
      info:    'fa-circle-info',
    };
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `<i class="fa-solid ${icons[type] || icons.info} toast-icon"></i><span>${message}</span>`;
    getToastContainer().appendChild(t);
    setTimeout(() => {
      t.style.cssText = 'opacity:0;transform:translateX(120%);transition:all .3s;';
      setTimeout(() => t.remove(), 330);
    }, duration);
  }

  // ── Modal ────────────────────────────────────────────────
  function openModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.remove('hidden'); document.body.style.overflow = 'hidden'; }
  }
  function closeModal(id) {
    const el = document.getElementById(id);
    if (el) { el.classList.add('hidden'); document.body.style.overflow = ''; }
  }
  document.addEventListener('click', e => {
    if (e.target.classList.contains('modal-overlay')) {
      e.target.classList.add('hidden');
      document.body.style.overflow = '';
    }
  });

  // ── Date/Time Helpers ────────────────────────────────────
  function formatDate(d) {
    if (!d) return '—';
    try {
      // Handle both "2026-04-09" and "2026-04-09T..." formats
      const date = new Date(d.includes('T') ? d : d + 'T00:00:00');
      return date.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
    } catch { return d; }
  }

  function formatTime(t) {
    if (!t) return '—';
    try {
      const [h, m] = t.split(':');
      const hr = parseInt(h, 10);
      return `${hr > 12 ? hr - 12 : hr || 12}:${m} ${hr >= 12 ? 'PM' : 'AM'}`;
    } catch { return t; }
  }

  function timeAgo(d) {
    if (!d) return '—';
    const diff = Date.now() - new Date(d).getTime();
    const m = Math.floor(diff / 60000);
    if (m < 1)  return 'just now';
    if (m < 60) return `${m}m ago`;
    const h = Math.floor(m / 60);
    if (h < 24) return `${h}h ago`;
    return `${Math.floor(h / 24)}d ago`;
  }

  // ── Status Tag ───────────────────────────────────────────
  function statusTag(status) {
    const map = {
      Scheduled:       'blue',
      Confirmed:       'teal',
      'In Progress':   'amber',
      Completed:       'green',
      Cancelled:       'neutral',
      'No-Show':       'red',
      Pending:         'amber',
      Partial:         'blue',
      Paid:            'green',
      Refunded:        'purple',
      Disputed:        'red',
      Available:       'green',
      Occupied:        'red',
      Reserved:        'amber',
      Maintenance:     'neutral',
      Low:             'green',
      Moderate:        'amber',
      High:            'red',
      Critical:        'red',
      Active:          'green',
      Inactive:        'neutral',
      Submitted:       'blue',
      'Under Review':  'amber',
      Approved:        'green',
      Rejected:        'red',
      Settled:         'teal',
      Routine:         'green',
      Urgent:          'red',
    };
    const cls = map[status] || 'neutral';
    return `<span class="tag ${cls}">${status || '—'}</span>`;
  }

  // ── Utilities ────────────────────────────────────────────
  function initials(name) {
    if (!name) return '?';
    return name.split(' ').filter(Boolean).map(w => w[0]).slice(0, 2).join('').toUpperCase();
  }

  function currency(amount) {
    return '₹' + Number(amount || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 });
  }

  function populateUserUI(user) {
    if (!user) return;
    document.querySelectorAll('[data-user-name]').forEach(el     => el.textContent = user.full_name || 'User');
    document.querySelectorAll('[data-user-email]').forEach(el    => el.textContent = user.email || '');
    document.querySelectorAll('[data-user-initials]').forEach(el => el.textContent = initials(user.full_name));
    document.querySelectorAll('[data-user-role]').forEach(el     => {
      const r = user.role || '';
      el.textContent = r.charAt(0).toUpperCase() + r.slice(1);
    });
  }

  function startClock(el) {
    if (!el) return;
    const tick = () => {
      el.textContent = new Date().toLocaleTimeString('en-IN', {
        hour: '2-digit', minute: '2-digit', second: '2-digit'
      });
    };
    tick();
    setInterval(tick, 1000);
  }

  // ── Bar Chart (Canvas) ───────────────────────────────────
  function drawBarChart(canvasId, labels, values, color = '#1d6cf5') {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    // Resize canvas to match its displayed size
    canvas.width  = canvas.offsetWidth  || 500;
    canvas.height = canvas.offsetHeight || 180;

    const ctx = canvas.getContext('2d');
    const W = canvas.width, H = canvas.height;
    const max = Math.max(...values, 1);
    const pad = { t: 24, r: 16, b: 44, l: 44 };
    const cW  = W - pad.l - pad.r;
    const cH  = H - pad.t - pad.b;
    const n   = labels.length || 1;
    const gap = cW / n;
    const barW = gap * 0.55;

    ctx.clearRect(0, 0, W, H);

    // Grid lines
    ctx.strokeStyle = 'rgba(0,0,0,0.07)';
    ctx.lineWidth   = 1;
    for (let i = 0; i <= 4; i++) {
      const y = pad.t + (cH / 4) * i;
      ctx.beginPath();
      ctx.moveTo(pad.l, y);
      ctx.lineTo(W - pad.r, y);
      ctx.stroke();
      ctx.fillStyle = '#9ca3af';
      ctx.font = '10px Sora, sans-serif';
      ctx.textAlign = 'right';
      ctx.fillText(Math.round(max - (max / 4) * i), pad.l - 4, y + 4);
    }

    // Bars
    values.forEach((v, i) => {
      const bH = Math.max((v / max) * cH, v > 0 ? 3 : 0);
      const x  = pad.l + i * gap + (gap - barW) / 2;
      const y  = pad.t + cH - bH;

      const grad = ctx.createLinearGradient(0, y, 0, y + bH);
      grad.addColorStop(0, color);
      grad.addColorStop(1, color + '55');
      ctx.fillStyle = grad;
      ctx.beginPath();
      if (ctx.roundRect) ctx.roundRect(x, y, barW, bH, [4, 4, 0, 0]);
      else ctx.rect(x, y, barW, bH);
      ctx.fill();

      ctx.fillStyle = '#6b7280';
      ctx.font = '11px Sora, sans-serif';
      ctx.textAlign = 'center';
      ctx.fillText(labels[i], x + barW / 2, H - pad.b + 15);

      if (v > 0) {
        ctx.fillStyle = '#374151';
        ctx.font = 'bold 10px Sora, sans-serif';
        ctx.fillText(v, x + barW / 2, y - 5);
      }
    });
  }

  // ── Donut Chart ──────────────────────────────────────────
  function drawDonutChart(canvasId, segments, colors) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;
    const ctx   = canvas.getContext('2d');
    const cx    = canvas.width / 2;
    const cy    = canvas.height / 2;
    const r     = Math.min(cx, cy) - 20;
    const total = segments.reduce((s, v) => s + (v.value || 0), 0);
    if (total === 0) return;
    let start = -Math.PI / 2;
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    segments.forEach((seg, i) => {
      const angle = (seg.value / total) * Math.PI * 2;
      ctx.beginPath();
      ctx.moveTo(cx, cy);
      ctx.arc(cx, cy, r, start, start + angle);
      ctx.closePath();
      ctx.fillStyle = colors[i % colors.length];
      ctx.fill();
      start += angle;
    });
    // Centre hole
    ctx.beginPath();
    ctx.arc(cx, cy, r * 0.58, 0, Math.PI * 2);
    ctx.fillStyle = 'white';
    ctx.fill();
  }

  // ── AI Chat ──────────────────────────────────────────────
  function initChat(containerEl, module, aiFeature = 'chat') {
    if (!containerEl) return;
    const messages = containerEl.querySelector('.chat-messages');
    const input    = containerEl.querySelector('.chat-input');
    const sendBtn  = containerEl.querySelector('.chat-send-btn');
    if (!messages || !input || !sendBtn) return;

    const history = [];

    async function send() {
      const text = input.value.trim();
      if (!text || input.disabled) return;
      input.value = '';
      input.disabled = true;
      sendBtn.disabled = true;

      appendMsg('user', text);
      history.push({ role: 'user', content: text });

      const thinking = appendMsg('ai', '<div class="ai-loading"><div class="ai-spinner"></div>Thinking...</div>');

      const res = await aiRequest(module, aiFeature, {
        message: text,
        history: history.slice(-8),
      });

      thinking.remove();

      const reply = (res.success && res.data?.response)
        ? res.data.response
        : (res.error || 'AI unavailable. Check your Gemini API key in backend/config/db.php');

      appendMsg('ai', reply);
      history.push({ role: 'assistant', content: reply });

      input.disabled  = false;
      sendBtn.disabled = false;
      input.focus();
    }

    function appendMsg(role, html) {
      const icons = {
        ai:   '<i class="fa-solid fa-robot"></i>',
        user: '<i class="fa-solid fa-user"></i>',
      };
      const div = document.createElement('div');
      div.className = `chat-msg ${role}`;
      div.innerHTML = `<div class="chat-msg-avatar">${icons[role] || ''}</div><div class="chat-msg-bubble">${html}</div>`;
      messages.appendChild(div);
      messages.scrollTop = messages.scrollHeight;
      return div;
    }

    sendBtn.addEventListener('click', send);
    input.addEventListener('keydown', e => {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); send(); }
    });

    // Role-specific welcome messages
    const welcomes = {
      admin:   'Hello! I\'m your Admin AI. Ask me about bed forecasts, staff workload, billing anomalies, or hospital analytics.',
      doctor:  'Hello Doctor! I\'m your Clinical Copilot. Ask me about diagnoses, drug interactions, SOAP notes, or patient risk scores.',
      patient: 'Hello! I\'m your Health Assistant. I can help with symptoms, lab reports, medicines, or booking appointments.',
    };
    setTimeout(() => appendMsg('ai', welcomes[module] || 'Hello! How can I assist you today?'), 500);
  }

  // ── Notifications ────────────────────────────────────────
  async function loadNotifications(role) {
    const badge = document.getElementById('notif-badge');
    try {
      const res = await API.get('admin.php', { action: 'notifications', role: role || 'admin' });
      if (!res.success || !res.data?.length) {
        if (badge) badge.style.display = 'none';
        return [];
      }
      const unread = res.data.filter(n => !parseInt(n.is_read));
      if (badge) {
        badge.textContent = unread.length || '';
        badge.style.display = unread.length > 0 ? 'flex' : 'none';
      }
      return res.data;
    } catch {
      if (badge) badge.style.display = 'none';
      return [];
    }
  }

  function renderNotificationDrawer(notifications) {
    // Remove existing drawer
    document.getElementById('notif-drawer')?.remove();

    const drawer = document.createElement('div');
    drawer.id = 'notif-drawer';
    drawer.style.cssText = [
      'position:fixed', 'top:64px', 'right:16px', 'width:340px', 'max-height:480px',
      'background:white', 'border:1px solid var(--card-border)', 'border-radius:14px',
      'box-shadow:0 8px 40px rgba(0,0,0,0.15)', 'z-index:99999',
      'overflow:hidden', 'display:flex', 'flex-direction:column',
    ].join(';');

    const typeIcon  = { info: 'fa-circle-info', warning: 'fa-triangle-exclamation', alert: 'fa-circle-xmark', success: 'fa-circle-check' };
    const typeColor = { info: '#1d6cf5', warning: '#f59e0b', alert: '#ef4444', success: '#10b981' };

    drawer.innerHTML = `
      <div style="padding:14px 16px;border-bottom:1px solid var(--card-border);display:flex;justify-content:space-between;align-items:center;flex-shrink:0;">
        <div style="font-size:14px;font-weight:700;color:var(--ink-900);">Notifications</div>
        <button onclick="document.getElementById('notif-drawer').remove()" style="background:none;border:none;cursor:pointer;color:var(--ink-400);font-size:18px;line-height:1;">✕</button>
      </div>
      <div style="overflow-y:auto;flex:1;">
        ${notifications.length ? notifications.map(n => `
          <div style="padding:13px 16px;border-bottom:1px solid var(--card-border);display:flex;gap:12px;align-items:flex-start;background:${parseInt(n.is_read) ? 'white' : '#f8faff'};">
            <div style="width:32px;height:32px;border-radius:50%;background:${(typeColor[n.type] || '#1d6cf5')}22;display:grid;place-items:center;flex-shrink:0;margin-top:2px;">
              <i class="fa-solid ${typeIcon[n.type] || typeIcon.info}" style="font-size:12px;color:${typeColor[n.type] || '#1d6cf5'};"></i>
            </div>
            <div style="flex:1;min-width:0;">
              <div style="font-size:13px;font-weight:600;color:var(--ink-900);">${n.title || 'Notification'}</div>
              <div style="font-size:12px;color:var(--ink-500);margin-top:2px;line-height:1.5;">${n.message || ''}</div>
              <div style="font-size:11px;color:var(--ink-300);margin-top:4px;">${timeAgo(n.created_at)}</div>
            </div>
            ${!parseInt(n.is_read) ? '<div style="width:8px;height:8px;border-radius:50%;background:#1d6cf5;flex-shrink:0;margin-top:6px;"></div>' : ''}
          </div>`).join('') :
          '<div style="padding:32px;text-align:center;color:var(--ink-400);font-size:13px;"><i class="fa-regular fa-bell" style="font-size:24px;display:block;margin-bottom:8px;opacity:0.3;"></i>No notifications</div>'}
      </div>`;

    document.body.appendChild(drawer);

    // Auto-close on outside click
    setTimeout(() => {
      document.addEventListener('click', function closeDrawer(e) {
        const btn = document.getElementById('notif-btn');
        if (!drawer.contains(e.target) && !btn?.contains(e.target)) {
          drawer.remove();
          document.removeEventListener('click', closeDrawer);
        }
      });
    }, 100);
  }

  async function toggleNotifications(role) {
    const existing = document.getElementById('notif-drawer');
    if (existing) { existing.remove(); return; }
    const notifs = await loadNotifications(role);
    renderNotificationDrawer(notifs);
  }

  // ── Public API ───────────────────────────────────────────
  return {
    API,
    aiRequest,
    testAI,
    getUser,
    setUser,
    clearUser,
    requireAuth,
    logout,
    toast,
    openModal,
    closeModal,
    formatDate,
    formatTime,
    timeAgo,
    statusTag,
    initials,
    currency,
    populateUserUI,
    startClock,
    drawBarChart,
    drawDonutChart,
    initChat,
    loadNotifications,
    toggleNotifications,
  };

})();

// Global shortcuts
window.MediCare = MediCare;
window.toast    = MediCare.toast;

// Auto-init on DOM ready
document.addEventListener('DOMContentLoaded', () => {
  const user = MediCare.getUser();
  if (user) MediCare.populateUserUI(user);
  const clockEl = document.querySelector('[data-clock]');
  if (clockEl) MediCare.startClock(clockEl);
});
