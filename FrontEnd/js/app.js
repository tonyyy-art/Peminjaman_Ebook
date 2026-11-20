'use strict';
/* Utilitas bersama: API, tema, toast, dialog, sampul buku, riwayat. Dimuat di semua halaman. */
const API_BASE = '../BackEnd/public/api.php';

async function request(action, { method = 'GET', params = {} } = {}) {
  let url = `${API_BASE}?action=${encodeURIComponent(action)}`;
  const init = { method, credentials: 'same-origin' };
  if (method === 'POST') {
    init.headers = { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' };
    init.body = new URLSearchParams(params).toString();
  } else {
    const qs = new URLSearchParams(params).toString();
    if (qs) url += '&' + qs;
  }
  let data = {}, res;
  try { res = await fetch(url, init); data = await res.json(); } catch (_) { /* jaringan / bukan JSON */ }
  return { ok: !!res && res.ok && data.success === true, status: res ? res.status : 0, data };
}

const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

const store = {
  get(k, d) { try { return JSON.parse(localStorage.getItem(k)) ?? d; } catch (_) { return d; } },
  set(k, v) { try { localStorage.setItem(k, JSON.stringify(v)); } catch (_) { /* storage dinonaktifkan */ } },
};

/* ---------- Favorit (disimpan di browser) ---------- */
const Favs = {
  list: () => store.get('ebook_favs', []),
  has(id) { return this.list().includes(Number(id)); },
  toggle(id) {
    id = Number(id);
    const l = this.list();
    const next = l.includes(id) ? l.filter((x) => x !== id) : [...l, id];
    store.set('ebook_favs', next);
    return next.includes(id);
  },
};

/* ---------- Tema terang/gelap ---------- */
function bindTheme() {
  document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const next = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
      document.documentElement.dataset.theme = next;
      store.set('theme', next);
    });
  });
}

/* ---------- Toast ---------- */
function toast(msg, type = 'info') {
  let box = document.getElementById('toasts');
  if (!box) {
    box = document.createElement('div');
    box.id = 'toasts';
    box.setAttribute('aria-live', 'polite');
    document.body.append(box);
  }
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.textContent = msg;
  box.append(t);
  setTimeout(() => {
    t.classList.add('out');
    t.addEventListener('animationend', () => t.remove());
  }, 3200);
}

/* ---------- Dialog konfirmasi (body = HTML, escape dulu di pemanggil) ---------- */
function confirmDialog({ title, body, ok = 'Ya', danger = false }) {
  return new Promise((resolve) => {
    const d = document.createElement('dialog');
    d.className = 'dlg';
    d.innerHTML = `<form method="dialog"><h3>${esc(title)}</h3><p>${body}</p>
      <div class="dlg-actions"><button value="no" class="btn btn-ghost">Batal</button>
      <button value="yes" class="btn ${danger ? 'btn-danger' : 'btn-primary'}" autofocus>${esc(ok)}</button></div></form>`;
    d.addEventListener('close', () => { resolve(d.returnValue === 'yes'); d.remove(); });
    d.addEventListener('click', (e) => { if (e.target === d) d.close('no'); });
    document.body.append(d);
    d.showModal();
  });
}

/* ---------- Tanggal ---------- */
const parseDate = (s) => new Date(s + 'T00:00:00');
const fmtDate = (s) => parseDate(s).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
function daysLeft(due) {
  const t = new Date(); t.setHours(0, 0, 0, 0);
  return Math.round((parseDate(due) - t) / 864e5);
}

/* ---------- Sampul buku: warna dari kategori, judul diset di sampul ---------- */
const HUES = [226, 172, 342, 262, 198, 12, 150, 284]; // palet terkurasi, tanpa warna kusam
function hueOf(text) {
  let h = 7;
  for (const ch of text || 'Umum') h = (h * 31 + ch.codePointAt(0)) >>> 0;
  return HUES[h % HUES.length];
}
function bookCover(e) {
  return `<div class="book" style="--h:${hueOf(e.kategori)}" aria-hidden="true">
    <span class="book-cat">${esc(e.kategori)}</span>
    <strong class="book-title">${esc(e.judul)}</strong>
    <span class="book-author">${esc(e.penulis)}</span></div>`;
}
const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)').matches;
document.addEventListener('pointermove', (e) => {
  const b = e.target.closest && e.target.closest('.book');
  if (!b || e.pointerType === 'touch' || reduceMotion) return;
  const r = b.getBoundingClientRect();
  b.style.setProperty('--ry', ((e.clientX - r.left) / r.width - 0.5) * 20 + 'deg');
  b.style.setProperty('--rx', -((e.clientY - r.top) / r.height - 0.5) * 16 + 'deg');
});
document.addEventListener('pointerout', (e) => {
  const b = e.target.closest && e.target.closest('.book');
  if (b && !b.contains(e.relatedTarget)) { b.style.setProperty('--rx', '0deg'); b.style.setProperty('--ry', '0deg'); }
});

/* ---------- Riwayat peminjaman (dipakai dashboard & halaman detail) ---------- */
function dueInfo(p) {
  if (p.status !== 'dipinjam') return { cls: 'done', text: `Dikembalikan ${p.tanggal_kembali ? fmtDate(p.tanggal_kembali) : ''}`.trim(), pct: 100 };
  const d = daysLeft(p.tanggal_jatuh_tempo);
  const pct = Math.min(100, Math.max(0, ((7 - d) / 7) * 100));
  if (d < 0) return { cls: 'late', text: `Terlambat ${-d} hari`, pct: 100 };
  if (d === 0) return { cls: 'soon', text: 'Jatuh tempo hari ini', pct };
  return { cls: d <= 2 ? 'soon' : 'ok', text: `Sisa ${d} hari`, pct };
}
function riwayatItem(p, onReturn) {
  const info = dueInfo(p);
  const judul = p.ebook && p.ebook.judul ? p.ebook.judul : `E-book #${p.ebook_id}`;
  const el = document.createElement('div');
  el.className = 'riwayat-item';
  el.innerHTML = `<div class="riwayat-info">
      <a class="riwayat-judul" href="peminjaman.html?id=${p.ebook_id}">${esc(judul)}</a>
      <span class="riwayat-tanggal">Dipinjam ${esc(fmtDate(p.tanggal_pinjam))}, jatuh tempo ${esc(fmtDate(p.tanggal_jatuh_tempo))}</span>
      <div class="due-bar ${info.cls}" role="img" aria-label="${esc(info.text)}"><i style="width:${info.pct}%"></i></div>
    </div>
    <div class="riwayat-actions"><span class="status-badge due-${info.cls}">${esc(info.text)}</span></div>`;
  if (onReturn && p.status === 'dipinjam') {
    const btn = document.createElement('button');
    btn.className = 'btn btn-ghost';
    btn.textContent = 'Kembalikan';
    btn.addEventListener('click', () => onReturn(p, btn));
    el.querySelector('.riwayat-actions').append(btn);
  }
  return el;
}
function renderRiwayat(list, items, onReturn, emptyText = 'Belum ada riwayat peminjaman.') {
  list.innerHTML = '';
  if (!items.length) { list.innerHTML = `<div class="empty">${esc(emptyText)}</div>`; return; }
  items.forEach((p) => list.append(riwayatItem(p, onReturn)));
}

/* ---------- Kerangka halaman terproteksi: cek sesi, chip user, logout, tema ---------- */
async function initShell() {
  bindTheme();
  const me = await request('me');
  if (!me.ok || !me.data.user) { window.location.replace('login.html'); return null; }
  const u = me.data.user;
  document.getElementById('userChip').innerHTML = `<span class="avatar">${esc(u.nama.charAt(0).toUpperCase())}</span>${esc(u.nama)}${u.role === 'admin' ? '<span class="role-tag">Admin</span>' : ''}`;
  if (u.role === 'admin') {
    const here = location.pathname.split('/').pop();
    const link = (href, label) => `<a class="nav-link" href="${href}"${here === href ? ' aria-current="page"' : ''}>${label}</a>`;
    document.querySelector('.nav-right').insertAdjacentHTML('afterbegin', link('dashboard.html', 'Katalog') + link('admin.html', 'Panel admin'));
  }
  document.getElementById('logoutBtn').addEventListener('click', async () => {
    await request('logout', { method: 'POST' });
    window.location.replace('login.html');
  });
  return u;
}
