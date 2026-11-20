'use strict';

const state = { q: '', kategori: '', sort: 'judul', favOnly: false, riwayat: [], tab: 'semua' };
let reqId = 0;

function ebookCard(e) {
  const a = document.createElement('article');
  a.className = 'card';
  const stok = e.stok <= 0 ? '<span class="badge badge-habis">Habis</span>'
    : e.stok <= 2 ? `<span class="badge badge-tipis">Sisa ${e.stok}</span>`
    : `<span class="badge badge-stok">Stok ${e.stok}</span>`;
  const fav = Favs.has(e.id);
  a.innerHTML = `
    <a class="card-cover" href="peminjaman.html?id=${e.id}" tabindex="-1">${bookCover(e)}</a>
    <button type="button" class="fav" aria-pressed="${fav}" aria-label="Favorit: ${esc(e.judul)}">${fav ? '♥' : '♡'}</button>
    <div class="card-body">
      <h3 class="card-title"><a href="peminjaman.html?id=${e.id}">${esc(e.judul)}</a></h3>
      <p class="card-meta">${esc(e.penulis)}, ${esc(String(e.tahun))}</p>
      <div class="card-foot">${stok}<a class="btn btn-primary btn-sm" href="peminjaman.html?id=${e.id}">Detail</a></div>
    </div>`;
  a.querySelector('.fav').addEventListener('click', (ev) => {
    const on = Favs.toggle(e.id);
    ev.currentTarget.setAttribute('aria-pressed', on);
    ev.currentTarget.textContent = on ? '♥' : '♡';
    toast(on ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit');
    if (state.favOnly && !on) loadCatalog();
  });
  return a;
}

function skeletons(n = 8) {
  return Array.from({ length: n }, () => '<div class="card sk-card"><div class="sk sk-cover"></div><div class="sk sk-line"></div><div class="sk sk-line short"></div></div>').join('');
}

async function loadCatalog() {
  const grid = document.getElementById('ebookGrid');
  const empty = document.getElementById('emptyState');
  const count = document.getElementById('resultCount');
  const my = ++reqId;
  empty.classList.add('hidden');
  grid.innerHTML = skeletons();

  const { data } = await request('ebooks', { params: { q: state.q, kategori: state.kategori } });
  if (my !== reqId) return; // ada permintaan yang lebih baru
  let items = (data && data.data) || [];
  if (state.favOnly) items = items.filter((e) => Favs.has(e.id));
  items.sort((a, b) => state.sort === 'tahun' ? b.tahun - a.tahun
    : state.sort === 'stok' ? b.stok - a.stok
    : a.judul.localeCompare(b.judul, 'id'));

  grid.innerHTML = '';
  count.textContent = `${items.length} e-book`;
  if (!items.length) {
    empty.textContent = state.favOnly ? 'Belum ada favorit. Tekan ikon hati pada e-book untuk menyimpannya.' : 'Tidak ada e-book yang cocok. Coba kata kunci atau kategori lain.';
    empty.classList.remove('hidden');
    return;
  }
  items.forEach((e) => grid.append(ebookCard(e)));
}

function renderChips(categories) {
  const box = document.getElementById('chips');
  const chips = [['', 'Semua'], ...categories.map((c) => [c, c]), ['__fav', '♥ Favorit']];
  box.innerHTML = '';
  chips.forEach(([val, label]) => {
    const b = document.createElement('button');
    b.type = 'button';
    b.className = 'chip';
    b.textContent = label;
    const active = val === '__fav' ? state.favOnly : (!state.favOnly && state.kategori === val);
    b.setAttribute('aria-pressed', active);
    b.addEventListener('click', () => {
      state.favOnly = val === '__fav';
      state.kategori = state.favOnly ? '' : val;
      renderChips(categories);
      loadCatalog();
    });
    box.append(b);
  });
}

function renderStats() {
  const aktif = state.riwayat.filter((p) => p.status === 'dipinjam');
  document.getElementById('statAktif').textContent = aktif.length;
  document.getElementById('statTempo').textContent = aktif.filter((p) => daysLeft(p.tanggal_jatuh_tempo) <= 2).length;
  document.getElementById('statSelesai').textContent = state.riwayat.length - aktif.length;
}

function renderTab() {
  const f = state.tab;
  const items = state.riwayat.filter((p) => f === 'semua' || (f === 'aktif') === (p.status === 'dipinjam'));
  renderRiwayat(document.getElementById('riwayatList'), items, kembalikan,
    f === 'aktif' ? 'Tidak ada e-book yang sedang dipinjam.' : 'Belum ada riwayat peminjaman.');
  document.querySelectorAll('#riwayatTabs button').forEach((b) => b.setAttribute('aria-pressed', b.dataset.tab === f));
}

async function loadRiwayat() {
  const { data } = await request('peminjaman');
  state.riwayat = (data && data.data) || [];
  renderStats();
  renderTab();
}

async function kembalikan(p, btn) {
  const judul = p.ebook && p.ebook.judul ? p.ebook.judul : `E-book #${p.ebook_id}`;
  const ok = await confirmDialog({ title: 'Kembalikan e-book?', body: `<strong>${esc(judul)}</strong> akan ditandai sudah dikembalikan dan stoknya bertambah.`, ok: 'Kembalikan' });
  if (!ok) return;
  btn.disabled = true;
  btn.textContent = 'Memproses…';
  const res = await request('kembalikan', { method: 'POST', params: { id: p.id } });
  if (res.ok) {
    toast('E-book berhasil dikembalikan', 'success');
    await Promise.all([loadRiwayat(), loadCatalog()]);
  } else {
    btn.disabled = false;
    btn.textContent = 'Kembalikan';
    toast(res.data.message || 'Gagal mengembalikan e-book.', 'error');
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const user = await initShell();
  if (!user) return;
  const admin = user.role === 'admin';
  document.getElementById('greet').textContent = admin ? 'Katalog e-book' : `Halo, ${user.nama.split(' ')[0]}`;
  if (admin) {
    document.querySelector('.stats').classList.add('hidden');
    document.querySelector('.riwayat-section').classList.add('hidden');
  }

  const input = document.getElementById('searchInput');
  let timer;
  input.addEventListener('input', () => {
    clearTimeout(timer);
    timer = setTimeout(() => { state.q = input.value.trim(); loadCatalog(); }, 300);
  });
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && input.value) { input.value = ''; state.q = ''; loadCatalog(); }
  });
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName) && !document.querySelector('dialog[open]')) {
      e.preventDefault();
      input.focus();
    }
  });
  document.getElementById('sortSelect').addEventListener('change', (e) => { state.sort = e.target.value; loadCatalog(); });
  document.querySelectorAll('#riwayatTabs button').forEach((b) => b.addEventListener('click', () => { state.tab = b.dataset.tab; renderTab(); }));

  const cats = await request('categories');
  renderChips((cats.data && cats.data.data) || []);
  await Promise.all([loadCatalog(), admin ? null : loadRiwayat()]);
});
