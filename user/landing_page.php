<?php
session_start();

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: landing_page.php');
    exit;
}

include 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $no_hp = trim(mysqli_real_escape_string($conn, $_POST['no_handphone'] ?? ''));
    $plat  = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['plat_mobil'] ?? '')));

    if ($no_hp === '' || $plat === '') {
        $error = 'No Handphone dan Plat Mobil wajib diisi.';
    } else {

        // ── CEK ADMIN DULU ─────────────────────────────────────────
        $q_admin = mysqli_query($conn,
            "SELECT * FROM login_admin
             WHERE no_telepon = '$no_hp' AND password = '$plat'
             LIMIT 1"
        );

        if ($q_admin && mysqli_num_rows($q_admin) > 0) {
            $admin = mysqli_fetch_assoc($q_admin);
            $_SESSION['status']      = 'login';
            $_SESSION['admin_login'] = true;
            $_SESSION['admin_user']  = $admin['no_telepon'];

            header('Location: ../admin/dashboard_admin.php');
            exit;
        }

        // ── CEK USER ───────────────────────────────────────────────
        $q_user = mysqli_query($conn,
            "SELECT * FROM pemesanan
             WHERE no_telepon = '$no_hp'
               AND UPPER(REPLACE(plat_mobil,' ','')) = REPLACE('$plat',' ','')
             ORDER BY id_pemesanan DESC
             LIMIT 1"
        );

        if ($q_user && mysqli_num_rows($q_user) > 0) {
            $data = mysqli_fetch_assoc($q_user);

            $_SESSION['user_login']     = true;
            $_SESSION['no_handphone']   = $data['no_telepon'];
            $_SESSION['plat_mobil']     = $data['plat_mobil'];
            $_SESSION['nama_pelanggan'] = $data['nama_pelanggan'];

            header('Location: profil.php');
            exit;
        }

        $error = 'No Handphone atau Plat Mobil tidak ditemukan.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Habibi Garage – Clean Washing Solution</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/landing_page.css">
</head>
<body>

<!-- NAVBAR -->
<nav>
  <a class="logo" href="#">
    <img src="../img/logo.png" alt="Habibi Garage Logo" class="logo-img">
  </a>
  <ul class="nav-links">
    <li><a href="#">Home</a></li>
    <li><a href="menu.php">Service</a></li>
    <li><a href="#contact">Contact Us</a></li>
    <li><a href="#">About Us</a></li>
  </ul>
</nav>

<!-- HERO -->
<section class="hero">
  <div class="hero-bg"></div>
  <div class="hero-overlay"></div>
  <div class="hero-content">

    <!-- Left: branding + tagline -->
    <div class="hero-left">
      <div class="brand-logo-hero">
        <img src="../img/logo.png" alt="Habibi Garage Logo" class="hero-logo">
        <div class="brand-divider"></div>
        <div class="hero-tagline">Clean<br>Washing<br>Solution</div>
      </div>
    </div>

    <!-- Right: auth card (satu form untuk semua) -->
    <div class="auth-card">
      <div class="auth-avatar">
        <svg viewBox="0 0 24 24" width="28" height="28" fill="rgba(255,255,255,0.6)">
          <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
        </svg>
      </div>
      <h2 class="auth-title">LOGIN</h2>

      <?php if ($error): ?>
        <p style="color:#ff6b7a;font-size:12px;text-align:center;margin-bottom:10px;line-height:1.4;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <p class="auth-sub">
        Belum memiliki akun? <a href="menu.php">Booking Sekarang</a>
      </p>

      <form method="POST" action="landing_page.php">
        <div class="input-group">
          <label>No Handphone</label>
          <input type="text" name="no_handphone"
                 placeholder="08512503097"
                 value="<?= htmlspecialchars($_POST['no_handphone'] ?? '') ?>">
        </div>
        <div class="input-group">
          <label>Plat Mobil (Password)</label>
          <input type="text" name="plat_mobil"
                 placeholder="E 2105 CNTH"
                 style="text-transform:uppercase"
                 value="<?= htmlspecialchars($_POST['plat_mobil'] ?? '') ?>">
        </div>

        <button class="btn-signup" type="submit">LOGIN</button>
      </form>

    </div>

  </div>
</section>

<!-- SERVICES -->
<section class="services">
  <h2 class="services-title">Perawatan Mobil Profesional Tanpa Ribet</h2>
  <p class="services-sub">Nikmati layanan cuci mobil profesional dengan hasil bersih maksimal, proses cepat, dan perawatan aman untuk kendaraan Anda.</p>

  <div class="cards-grid">

    <!-- Row 1 -->
    <div class="card-item">
      <div class="card-icon">🗓️</div>
      <h3 class="card-heading">Booking Online</h3>
      <p class="card-desc">Pesan layanan cuci mobil dengan mudah kapan saja melalui sistem booking online kami.</p>
      <button class="card-btn" onclick="window.location.href='menu.php'">Mulai Booking</button>
    </div>
    <div class="card-item">
      <div class="card-icon">🕘</div>
      <h3 class="card-heading">Bebas Atur Jadwal</h3>
      <p class="card-desc">Tentukan sendiri waktu pencucian sesuai aktivitas Anda tanpa perlu antri.</p>
      <button class="card-btn" onclick="window.location.href='jadwal.php'">Cek Jadwal</button>
    </div>
    <div class="card-item">
      <div class="card-icon">🕒</div>
      <h3 class="card-heading">Proses Cepat &amp; Aman</h3>
      <p class="card-desc">Dikerjakan oleh tenaga profesional dengan peralatan dan produk yang aman untuk kendaraan.</p>
      <button class="card-btn">Cek Layanan</button>
    </div>

    <hr class="divider-grid">

    <!-- Row 2 -->
    <!-- BASIC WASH -->
    <div class="card-item package-card" onclick="bukaModal('basic')">
      <div class="card-icon">⭐</div>
      <h3 class="card-heading">Basic Wash</h3>
      <p class="card-desc">Cuci cepat &amp; bersih untuk perawatan rutin kendaraan Anda.</p>
      <div class="price-range-tag">Rp 35.000 – 50.000</div>
      <button class="card-btn">Lihat Paket</button>
    </div>

    <!-- STANDARD CLEAN -->
    <div class="card-item package-card" onclick="bukaModal('standard')">
      <div class="card-icon">🔥</div>
      <h3 class="card-heading">Standard Clean</h3>
      <p class="card-desc">Eksterior &amp; interior bersih lebih menyeluruh dan detail.</p>
      <div class="price-range-tag">Rp 100.000 – 150.000</div>
      <button class="card-btn">Lihat Paket</button>
    </div>

    <!-- PREMIUM CARE -->
    <div class="card-item package-card" onclick="bukaModal('premium')">
      <div class="card-icon">💎</div>
      <h3 class="card-heading">Premium Care</h3>
      <p class="card-desc">Perawatan total eksterior &amp; interior dengan hasil maksimal.</p>
      <div class="price-range-tag">Rp 250.000 – 400.000</div>
      <button class="card-btn">Lihat Paket</button>
    </div>
  </div>
</section>

<!-- ===== MODAL PAKET ===== -->
<div id="modalOverlay" onclick="tutupModal()" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.6);z-index:999;backdrop-filter:blur(4px);"></div>

<div id="modalPaket" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:1000;
  background:#fff;border-radius:20px;width:90%;max-width:480px;max-height:85vh;overflow-y:auto;
  box-shadow:0 30px 80px rgba(0,0,0,0.35);">

  <!-- Header modal -->
  <div id="modalHeader" style="padding:28px 28px 0;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;">
      <div>
        <div id="modalIcon" style="font-size:28px;margin-bottom:8px;"></div>
        <h2 id="modalTitle" style="font-family:'Barlow Condensed',sans-serif;font-size:22px;font-weight:800;color:#0d0d0d;letter-spacing:.5px;"></h2>
        <p id="modalSub" style="font-size:13px;color:#6b7280;margin-top:4px;"></p>
      </div>
      <button onclick="tutupModal()" style="background:none;border:none;font-size:22px;cursor:pointer;color:#9ca3af;line-height:1;padding:4px;">✕</button>
    </div>
    <div id="modalRangeTag" style="display:inline-block;margin:14px 0 0;padding:5px 14px;border-radius:20px;font-size:12px;font-weight:700;letter-spacing:.5px;"></div>
  </div>

  <!-- Daftar layanan -->
  <div id="modalBody" style="padding:20px 28px 28px;"></div>

  <!-- CTA -->
  <div style="padding:0 28px 28px;">
    <a href="menu.php" style="display:block;text-align:center;background:#0d0d0d;color:white;
      padding:14px;border-radius:10px;font-weight:700;font-size:14px;letter-spacing:1.5px;
      text-decoration:none;text-transform:uppercase;transition:.2s;"
      onmouseover="this.style.background='#00c8e0';this.style.color='#0d0d0d'"
      onmouseout="this.style.background='#0d0d0d';this.style.color='white'">
      Booking Sekarang
    </a>
  </div>
</div>

<script>
const paketData = {
  basic: {
    icon: '⭐',
    title: 'Basic Wash',
    sub: 'Layanan cuci cepat & bersih untuk perawatan rutin',
    range: 'Rp 35.000 – 50.000',
    rangeColor: '#0d0d0d',
    rangeBg: '#f3f4f6',
    layanan: [
      { nama: 'Wax Butter',  harga: 35000, desc: 'Poles body luar' },
      { nama: 'Cuci Mesin',  harga: 40000, desc: 'Cuci bagian mesinnya saja' },
      { nama: 'Cuci Umum',   harga: 50000, desc: 'Cuci luar dalam biasa' },
      { nama: 'Cuci Luar',   harga: 50000, desc: 'Cuci luar saja' },
      { nama: 'Wax Spray',   harga: 50000, desc: 'Wax spray body luar' },
    ]
  },
  standard: {
    icon: '🔥',
    title: 'Standard Clean',
    sub: 'Pembersihan eksterior & interior lebih menyeluruh',
    range: 'Rp 100.000 – 150.000',
    rangeColor: '#0066cc',
    rangeBg: '#dbeafe',
    layanan: [
      { nama: 'Cuci Dalam',      harga: 100000, desc: 'Cuci dalam saja' },
      { nama: 'Cuci Premium',    harga: 100000, desc: 'Cuci luar dalam + mesin + anti karat' },
      { nama: 'Paket Firsdate',  harga: 150000, desc: 'Cuci luar dalam + foging + wax spray + cuci mesin' },
    ]
  },
  premium: {
    icon: '💎',
    title: 'Premium Care',
    sub: 'Perawatan total eksterior & interior dengan hasil terbaik',
    range: 'Rp 250.000 – 400.000',
    rangeColor: '#7c3aed',
    rangeBg: '#ede9fe',
    layanan: [
      { nama: 'Foging',         harga: 250000, desc: 'Foging interior' },
      { nama: 'Paket Gantenk',  harga: 400000, desc: 'Cuci luar dalam + foging + wax butter + cuci mesin + anti karat + jamur kaca + interior cleaning' },
    ]
  }
};

function bukaModal(tipe) {
  const d = paketData[tipe];

  document.getElementById('modalIcon').textContent = d.icon;
  document.getElementById('modalTitle').textContent = d.title;
  document.getElementById('modalSub').textContent = d.sub;

  const rangeTag = document.getElementById('modalRangeTag');
  rangeTag.textContent = d.range;
  rangeTag.style.background = d.rangeBg;
  rangeTag.style.color = d.rangeColor;

  let html = '<div style="display:flex;flex-direction:column;gap:12px;margin-top:8px;">';
  d.layanan.forEach((l, i) => {
    html += `
      <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:12px;
        padding:14px 16px;border-radius:12px;background:#f9fafb;border:1.5px solid #f3f4f6;transition:.15s;"
        onmouseover="this.style.borderColor='#e5e7eb';this.style.background='#f3f4f6'"
        onmouseout="this.style.borderColor='#f3f4f6';this.style.background='#f9fafb'">
        <div style="flex:1;">
          <div style="font-size:14px;font-weight:700;color:#0d0d0d;margin-bottom:3px;">${l.nama}</div>
          <div style="font-size:12px;color:#6b7280;line-height:1.45;">${l.desc}</div>
        </div>
        <div style="font-size:14px;font-weight:800;color:${d.rangeColor};white-space:nowrap;margin-top:1px;">
          Rp ${l.harga.toLocaleString('id-ID')}
        </div>
      </div>`;
  });
  html += '</div>';

  document.getElementById('modalBody').innerHTML = html;
  document.getElementById('modalOverlay').style.display = 'block';
  document.getElementById('modalPaket').style.display = 'block';
  document.body.style.overflow = 'hidden';
}

function tutupModal() {
  document.getElementById('modalOverlay').style.display = 'none';
  document.getElementById('modalPaket').style.display = 'none';
  document.body.style.overflow = '';
}

document.addEventListener('keydown', e => { if(e.key === 'Escape') tutupModal(); });
</script>



<!-- FOOTER -->
<footer class="footer" id="contact">
  <div class="footer-container">
    <div class="footer-col">
      <h3>Habibi Garage</h3>
      <p>Habibi Garage adalah solusi terbaik untuk perawatan kendaraan Anda.</p>
      <p>Kami menghadirkan layanan cuci mobil yang bersih, cepat, dan berkualitas. Kepuasan pelanggan adalah prioritas utama kami.</p>
    </div>
    <div class="footer-col">
      <h4>Alamat</h4>
      <p>Jl. Sojar No.Depan, pasar,<br>Kec. Jatibarang,<br>Kabupaten Indramayu</p>
    </div>
    <div class="footer-col">
      <h4>Contact</h4>
      <p>Email: info@email.com</p>
      <p>Phone: +62 xxx-xxxx-xxxx</p>
      <p>WhatsApp: +62 xxx-xxxx-xxxx</p>
    </div>
  </div>
  <div class="footer-bottom">
    <p>© 2026 Habibi Garage. All Rights Reserved.</p>
    <div class="footer-links">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
    </div>
  </div>
</footer>

</body>
</html>