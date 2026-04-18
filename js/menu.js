/* HABIBI GARAGE — main.js */

let cartCount = 0;

/* Wishlist */
document.querySelectorAll('.wishlist-btn').forEach(btn => {
  btn.addEventListener('click', () => btn.classList.toggle('active'));
});

/* Filter chips */
function setChip(el) {
  document.querySelectorAll('.chip').forEach(c => c.classList.remove('active'));
  el.classList.add('active');
}

/* Add to cart */
function addToCart(btn, name, price) {
  cartCount++;
  document.querySelector('.cart-badge').textContent = cartCount;
  const orig = btn.textContent;
  btn.textContent = '✓ Ditambahkan';
  btn.style.background = '#16a34a';
  btn.disabled = true;
  setTimeout(() => {
    btn.textContent = orig;
    btn.style.background = '';
    btn.disabled = false;
  }, 1400);
  showToast(name + ' ditambahkan ke keranjang!');
}

/* Toast */
let timer;
function showToast(msg) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(timer);
  timer = setTimeout(() => t.classList.remove('show'), 2400);
}

/* Logo fallback */
const logo = document.querySelector('.logo');
if (logo) {
  logo.onerror = () => {
    logo.style.display = 'none';
    const fb = document.createElement('div');
    fb.className = 'logo-fallback';
    fb.innerHTML = 'HABIBI <span>GARAGE</span>';
    logo.closest('.logo-wrap').appendChild(fb);
  };
}

/* Car image fallback */
document.querySelectorAll('.card-img').forEach(img => {
  img.onerror = () => {
    img.style.display = 'none';
    const w = img.closest('.card-img-wrap');
    if (w) w.innerHTML = `<svg xmlns="http://www.w3.org/2000/svg" width="150" height="75" viewBox="0 0 150 75" fill="none">
      <rect x="10" y="38" width="130" height="24" rx="5" fill="#c8d4e0"/>
      <rect x="30" y="22" width="90" height="26" rx="7" fill="#b0bfcf"/>
      <circle cx="38" cy="64" r="10" fill="#8a9ab0"/>
      <circle cx="112" cy="64" r="10" fill="#8a9ab0"/>
      <circle cx="38" cy="64" r="5" fill="#edf0f5"/>
      <circle cx="112" cy="64" r="5" fill="#edf0f5"/>
    </svg>`;
  };
});