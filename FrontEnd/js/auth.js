'use strict';

const isValidEmail = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v);

document.addEventListener('DOMContentLoaded', () => {
  bindTheme();
  const form = document.getElementById('loginForm');
  const email = document.getElementById('email');
  const pass = document.getElementById('password');
  const emailErr = document.getElementById('emailError');
  const passErr = document.getElementById('passwordError');
  const loginErr = document.getElementById('loginError');
  const submit = document.getElementById('submitBtn');
  const caps = document.getElementById('capsHint');

  // Rak sampul di sisi kiri (dekoratif)
  const sample = [
    { judul: 'Pemrograman Web Modern dengan PHP 8', penulis: 'Rian Hidayat', kategori: 'Pemrograman Web' },
    { judul: 'Basis Data Relasional dengan MySQL', penulis: 'Andi Wijaya', kategori: 'Database' },
    { judul: 'Pengantar Kecerdasan Buatan', penulis: 'Dewi Lestari', kategori: 'Kecerdasan Buatan' },
  ];
  document.getElementById('shelf').innerHTML = sample.map((b) => `<div class="shelf-book">${bookCover(b)}</div>`).join('');

  const home = (u) => (u && u.role === 'admin' ? 'admin.html' : 'dashboard.html');
  request('me').then(({ data }) => { if (data && data.user) window.location.replace(home(data.user)); });

  document.getElementById('togglePassword').addEventListener('click', (e) => {
    const show = pass.type === 'password';
    pass.type = show ? 'text' : 'password';
    e.currentTarget.textContent = show ? 'Sembunyikan' : 'Tampilkan';
    e.currentTarget.setAttribute('aria-pressed', show);
  });
  pass.addEventListener('keyup', (e) => caps.classList.toggle('hidden', !e.getModifierState('CapsLock')));
  pass.addEventListener('blur', () => caps.classList.add('hidden'));

  // Tombol akun demo mengisi form
  document.querySelectorAll('[data-demo]').forEach((b) => b.addEventListener('click', () => {
    email.value = b.dataset.demo;
    pass.value = b.dataset.pass;
    emailErr.textContent = passErr.textContent = '';
    pass.focus();
  }));

  const fail = (input, errEl, msg) => { errEl.textContent = msg; input.classList.add('input-error'); };
  const shake = () => { form.classList.remove('shake'); void form.offsetWidth; form.classList.add('shake'); };

  form.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    emailErr.textContent = passErr.textContent = '';
    loginErr.classList.add('hidden');
    email.classList.remove('input-error');
    pass.classList.remove('input-error');

    const em = email.value.trim();
    let valid = true;
    if (!em) { fail(email, emailErr, 'Email wajib diisi.'); valid = false; }
    else if (!isValidEmail(em)) { fail(email, emailErr, 'Format email tidak valid.'); valid = false; }
    if (!pass.value) { fail(pass, passErr, 'Password wajib diisi.'); valid = false; }
    if (!valid) { shake(); return; }

    submit.disabled = true;
    submit.innerHTML = '<span class="btn-spin"></span>Memproses…';
    const { ok, data } = await request('login', { method: 'POST', params: { email: em, password: pass.value } });
    if (ok) { window.location.replace(home(data.user)); return; }

    submit.disabled = false;
    submit.textContent = 'Masuk';
    loginErr.textContent = data.message || 'Tidak bisa terhubung ke server. Pastikan Apache dan MySQL berjalan.';
    loginErr.classList.remove('hidden');
    shake();
  });
});
