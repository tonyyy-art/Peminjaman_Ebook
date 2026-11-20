'use strict';

const DURASI = 7;
let activeLoans = [];
let isAdmin = false;

function renderDetail(e) {
  const dueDate = new Date(Date.now() + DURASI * 864e5).toISOString().slice(0, 10);
  const loan = activeLoans.find((p) => Number(p.ebook_id) === Number(e.id) && p.status === 'dipinjam');
  const fav = Favs.has(e.id);
  const stok = e.stok <= 0 ? '<span class="badge badge-habis">Habis</span>'
    : e.stok <= 2 ? `<span class="badge badge-tipis">Sisa ${e.stok}</span>`
    : `<span class="badge badge-stok">Stok ${e.stok}</span>`;
  const btnLabel = loan ? 'Sedang Anda pinjam' : e.stok > 0 ? 'Pinjam e-book' : 'Stok habis';
  const hint = isAdmin ? 'Akun admin mengelola katalog dan tidak meminjam e-book.' : loan ? `Jatuh tempo ${fmtDate(loan.tanggal_jatuh_tempo)}`
    : e.stok > 0 ? `Dipinjam ${DURASI} hari, jatuh tempo ${fmtDate(dueDate)}` : 'Coba lagi saat ada yang mengembalikan.';

  const section = document.getElementById('detailSection');
  section.innerHTML = `<article class="detail-card">
    <div class="detail-cover">${bookCover(e)}</div>
    <div class="detail-info">
      <h1 class="detail-title">${esc(e.judul)}</h1>
      <div class="detail-meta"><span>${esc(e.penulis)}</span><span>${esc(e.kategori)}, terbit ${esc(String(e.tahun))}</span></div>
      <p class="detail-desc">${esc(e.deskripsi)}</p>
      <div class="detail-actions">
        ${stok}
        ${isAdmin
          ? '<a class="btn btn-primary" href="admin.html">Kelola di panel admin</a>'
          : `<button type="button" class="btn btn-primary" id="pinjamBtn" ${loan || e.stok <= 0 ? 'disabled' : ''}>${btnLabel}</button>`}
        <button type="button" class="btn btn-ghost" id="favBtn" aria-pressed="${fav}">${fav ? '♥ Favorit' : '♡ Favorit'}</button>
        <span class="stok-info">${esc(hint)}</span>
      </div>
    </div></article>`;

  document.getElementById('favBtn').addEventListener('click', (ev) => {
    const on = Favs.toggle(e.id);
    ev.currentTarget.setAttribute('aria-pressed', on);
    ev.currentTarget.textContent = on ? '♥ Favorit' : '♡ Favorit';
    toast(on ? 'Ditambahkan ke favorit' : 'Dihapus dari favorit');
  });
  if (!isAdmin) document.getElementById('pinjamBtn').addEventListener('click', () => pinjam(e, dueDate));
}

async function loadDetail(id) {
  const { ok, data } = await request('ebook', { params: { id } });
  document.getElementById('loading').classList.add('hidden');
  if (!ok || !data.data) {
    document.getElementById('detailSection').innerHTML = '<div class="empty">E-book tidak ditemukan.</div>';
    return;
  }
  document.title = `${data.data.judul} · E-Library`;
  renderDetail(data.data);
}

async function loadRiwayat() {
  const { data } = await request('peminjaman');
  activeLoans = (data && data.data) || [];
  renderRiwayat(document.getElementById('riwayatList'), activeLoans);
}

async function pinjam(e, dueDate) {
  const ok = await confirmDialog({
    title: 'Pinjam e-book ini?',
    body: `<strong>${esc(e.judul)}</strong> dipinjam selama ${DURASI} hari dan harus dikembalikan sebelum <strong>${esc(fmtDate(dueDate))}</strong>.`,
    ok: 'Pinjam sekarang',
  });
  if (!ok) return;

  const btn = document.getElementById('pinjamBtn');
  btn.disabled = true;
  btn.textContent = 'Menyimpan…';
  const res = await request('pinjam', { method: 'POST', params: { ebook_id: e.id } });
  if (res.ok) {
    toast('Peminjaman berhasil. Selamat membaca!', 'success');
    await loadRiwayat();
    await loadDetail(e.id);
  } else {
    toast(res.data.message || 'Peminjaman gagal.', 'error');
    btn.disabled = false;
    btn.textContent = 'Pinjam e-book';
  }
}

document.addEventListener('DOMContentLoaded', async () => {
  const me = await initShell();
  if (!me) return;
  isAdmin = me.role === 'admin';
  const id = new URLSearchParams(window.location.search).get('id');
  if (isAdmin) document.querySelector('.riwayat-section').classList.add('hidden');
  else await loadRiwayat();
  if (!id) {
    document.getElementById('loading').classList.add('hidden');
    document.getElementById('detailSection').innerHTML = '<div class="empty">Parameter id e-book tidak ditemukan.</div>';
    return;
  }
  await loadDetail(id);
});
