'use strict';

const st = { ebooks: [], loans: [], q: '', filter: 'semua' };

const stokBadge = (n) => n <= 0 ? '<span class="badge badge-habis">Habis</span>'
  : n <= 2 ? `<span class="badge badge-tipis">Sisa ${n}</span>` : `<span class="badge badge-stok">${n}</span>`;
const isLate = (p) => p.status === 'dipinjam' && daysLeft(p.tanggal_jatuh_tempo) < 0;

async function loadOverview() {
  const { data } = await request('admin_overview');
  const o = (data && data.data) || {};
  document.getElementById('stEbook').textContent = o.total_ebook ?? 0;
  document.getElementById('stStok').textContent = o.total_stok ?? 0;
  document.getElementById('stPinjam').textContent = o.dipinjam ?? 0;
  document.getElementById('stLate').textContent = o.terlambat ?? 0;
}

/* ---------- Tab e-book ---------- */
async function loadEbooks() {
  const { data } = await request('ebooks');
  st.ebooks = (data && data.data) || [];
  renderEbooks();
}

function renderEbooks() {
  const q = st.q.toLowerCase();
  const rows = st.ebooks.filter((e) => `${e.judul} ${e.penulis} ${e.kategori}`.toLowerCase().includes(q));
  const body = document.getElementById('ebookRows');
  if (!rows.length) {
    body.innerHTML = `<tr><td colspan="5" class="td-empty">${st.ebooks.length ? 'Tidak ada e-book yang cocok.' : 'Katalog masih kosong. Klik “Tambah e-book”.'}</td></tr>`;
    return;
  }
  body.innerHTML = rows.map((e) => `<tr>
    <td><a class="td-title" href="peminjaman.html?id=${e.id}">${esc(e.judul)}</a><span class="td-sub">${esc(e.penulis)}</span></td>
    <td>${esc(e.kategori)}</td><td>${esc(String(e.tahun))}</td><td>${stokBadge(e.stok)}</td>
    <td class="col-act"><button class="btn btn-ghost btn-sm" data-edit="${e.id}">Ubah</button>
      <button class="btn btn-ghost btn-sm btn-del" data-del="${e.id}">Hapus</button></td></tr>`).join('');
}

function ebookDialog(e) {
  const d = document.createElement('dialog');
  d.className = 'dlg dlg-wide';
  const v = e || { judul: '', penulis: '', kategori: '', tahun: new Date().getFullYear(), stok: 1, deskripsi: '' };
  const cats = [...new Set(st.ebooks.map((x) => x.kategori))].map((c) => `<option value="${esc(c)}">`).join('');
  d.innerHTML = `<form novalidate>
    <h3>${e ? 'Ubah e-book' : 'Tambah e-book'}</h3>
    <div class="field"><label for="f-judul">Judul</label><input id="f-judul" name="judul" maxlength="200" value="${esc(v.judul)}" required></div>
    <div class="field-row">
      <div class="field"><label for="f-penulis">Penulis</label><input id="f-penulis" name="penulis" maxlength="120" value="${esc(v.penulis)}" required></div>
      <div class="field"><label for="f-kategori">Kategori</label><input id="f-kategori" name="kategori" maxlength="80" list="kategoriList" value="${esc(v.kategori)}" required><datalist id="kategoriList">${cats}</datalist></div>
    </div>
    <div class="field-row">
      <div class="field"><label for="f-tahun">Tahun terbit</label><input id="f-tahun" name="tahun" type="number" min="1900" max="${new Date().getFullYear() + 1}" value="${esc(String(v.tahun))}" required></div>
      <div class="field"><label for="f-stok">Stok</label><input id="f-stok" name="stok" type="number" min="0" value="${esc(String(v.stok))}" required></div>
    </div>
    <div class="field"><label for="f-desk">Deskripsi</label><textarea id="f-desk" name="deskripsi" rows="4">${esc(v.deskripsi)}</textarea></div>
    <div class="alert error hidden" role="alert"></div>
    <div class="dlg-actions"><button type="button" class="btn btn-ghost" data-cancel>Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
  </form>`;
  const form = d.querySelector('form');
  const err = d.querySelector('.alert');
  d.querySelector('[data-cancel]').addEventListener('click', () => d.close());
  d.addEventListener('close', () => d.remove());
  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    const btn = form.querySelector('[type=submit]');
    btn.disabled = true; btn.textContent = 'Menyimpan…'; err.classList.add('hidden');
    const params = Object.fromEntries(new FormData(form));
    if (e) params.id = e.id;
    const res = await request('admin_ebook_save', { method: 'POST', params });
    if (res.ok) {
      d.close();
      toast(res.data.message, 'success');
      await Promise.all([loadEbooks(), loadOverview()]);
    } else {
      btn.disabled = false; btn.textContent = 'Simpan';
      err.textContent = res.data.message || 'Gagal menyimpan. Periksa koneksi ke server.';
      err.classList.remove('hidden');
    }
  });
  document.body.append(d);
  d.showModal();
}

async function hapusEbook(e) {
  const ok = await confirmDialog({ title: 'Hapus e-book?', body: `<strong>${esc(e.judul)}</strong> beserta riwayat peminjamannya akan dihapus permanen.`, ok: 'Hapus', danger: true });
  if (!ok) return;
  const res = await request('admin_ebook_delete', { method: 'POST', params: { id: e.id } });
  if (res.ok) {
    toast('E-book dihapus', 'success');
    await Promise.all([loadEbooks(), loadOverview(), loadLoans()]);
  } else {
    toast(res.data.message || 'Gagal menghapus e-book.', 'error');
  }
}

/* ---------- Tab peminjaman ---------- */
async function loadLoans() {
  const { data } = await request('admin_loans');
  st.loans = (data && data.data) || [];
  renderLoans();
}

function renderLoans() {
  const f = st.filter;
  const rows = st.loans.filter((p) => f === 'semua' || (f === 'aktif' && p.status === 'dipinjam') || (f === 'terlambat' && isLate(p)) || (f === 'selesai' && p.status !== 'dipinjam'));
  document.querySelectorAll('#loanTabs button').forEach((b) => b.setAttribute('aria-pressed', b.dataset.f === f));
  const body = document.getElementById('loanRows');
  if (!rows.length) { body.innerHTML = '<tr><td colspan="6" class="td-empty">Tidak ada peminjaman pada filter ini.</td></tr>'; return; }
  body.innerHTML = rows.map((p) => {
    const info = dueInfo(p);
    const u = p.user || {}, b = p.ebook || {};
    return `<tr>
      <td><span class="td-title">${esc(u.nama || '-')}</span><span class="td-sub">${esc(u.email || '')}</span></td>
      <td>${esc(b.judul || `E-book #${p.ebook_id}`)}</td>
      <td>${esc(fmtDate(p.tanggal_pinjam))}</td><td>${esc(fmtDate(p.tanggal_jatuh_tempo))}</td>
      <td><span class="status-badge due-${info.cls}">${esc(info.text)}</span></td>
      <td class="col-act">${p.status === 'dipinjam' ? `<button class="btn btn-ghost btn-sm" data-ret="${p.id}">Tandai kembali</button>` : ''}</td></tr>`;
  }).join('');
}

async function kembalikan(p) {
  const ok = await confirmDialog({ title: 'Tandai sudah dikembalikan?', body: `<strong>${esc((p.ebook || {}).judul || '')}</strong> milik ${esc((p.user || {}).nama || 'peminjam')} akan dicatat kembali hari ini dan stoknya bertambah.`, ok: 'Tandai kembali' });
  if (!ok) return;
  const res = await request('kembalikan', { method: 'POST', params: { id: p.id } });
  if (res.ok) {
    toast('Pengembalian dicatat', 'success');
    await Promise.all([loadLoans(), loadOverview(), loadEbooks()]);
  } else {
    toast(res.data.message || 'Gagal mencatat pengembalian.', 'error');
  }
}

function showTab(tab) {
  document.getElementById('panelEbook').classList.toggle('hidden', tab !== 'ebook');
  document.getElementById('panelLoans').classList.toggle('hidden', tab !== 'peminjaman');
  document.querySelectorAll('#mainTabs button').forEach((b) => b.setAttribute('aria-pressed', b.dataset.tab === tab));
  history.replaceState(null, '', tab === 'ebook' ? location.pathname : '#' + tab);
}

document.addEventListener('DOMContentLoaded', async () => {
  const user = await initShell();
  if (!user) return;
  if (user.role !== 'admin') { window.location.replace('dashboard.html'); return; }

  document.querySelectorAll('#mainTabs button').forEach((b) => b.addEventListener('click', () => showTab(b.dataset.tab)));
  document.querySelectorAll('#loanTabs button').forEach((b) => b.addEventListener('click', () => { st.filter = b.dataset.f; renderLoans(); }));
  document.getElementById('addBtn').addEventListener('click', () => ebookDialog(null));
  let timer;
  document.getElementById('adminSearch').addEventListener('input', (ev) => {
    clearTimeout(timer);
    timer = setTimeout(() => { st.q = ev.target.value.trim(); renderEbooks(); }, 200);
  });
  document.getElementById('ebookRows').addEventListener('click', (ev) => {
    const edit = ev.target.closest('[data-edit]'), del = ev.target.closest('[data-del]');
    if (edit) ebookDialog(st.ebooks.find((x) => x.id === Number(edit.dataset.edit)));
    if (del) hapusEbook(st.ebooks.find((x) => x.id === Number(del.dataset.del)));
  });
  document.getElementById('loanRows').addEventListener('click', (ev) => {
    const r = ev.target.closest('[data-ret]');
    if (r) kembalikan(st.loans.find((x) => x.id === Number(r.dataset.ret)));
  });

  if (location.hash === '#peminjaman') showTab('peminjaman');
  await Promise.all([loadOverview(), loadEbooks(), loadLoans()]);
});
