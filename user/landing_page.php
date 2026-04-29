<?php
session_start();
include 'koneksi.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipe = $_POST['tipe'] ?? '';

    // ── LOGIN USER ────────────────────────────────────────────────
    // Cocokkan no_telepon + plat_mobil dari tabel pemesanan
    if ($tipe === 'user') {
        $no_hp = trim(mysqli_real_escape_string($conn, $_POST['no_handphone'] ?? ''));
        $plat  = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['plat_mobil'] ?? '')));

        if ($no_hp === '' || $plat === '') {
            $error = 'No Handphone dan Plat Mobil wajib diisi.';
        } else {
            // Ambil data booking terakhir user
            $q = mysqli_query($conn,
                "SELECT * FROM pemesanan
                 WHERE no_telepon = '$no_hp'
                   AND UPPER(REPLACE(plat_mobil,' ','')) = REPLACE('$plat',' ','')
                 ORDER BY id_pemesanan DESC
                 LIMIT 1"
            );

            if ($q && mysqli_num_rows($q) > 0) {
                $data = mysqli_fetch_assoc($q);

                // Simpan ke session untuk auto-fill form_booking.php
                $_SESSION['user_login']     = true;
                $_SESSION['no_handphone']   = $data['no_telepon'];
                $_SESSION['plat_mobil']     = $data['plat_mobil'];
                $_SESSION['nama_pelanggan'] = $data['nama_pelanggan'];
                $_SESSION['jenis_mobil']    = $data['jenis_mobil'];
                $_SESSION['warna_mobil']    = $data['warna_mobil'];

                header('Location: menu.php');
                exit;
            } else {
                $error = 'No Handphone atau Plat Mobil tidak ditemukan.';
            }
        }
    }

    // ── LOGIN ADMIN ───────────────────────────────────────────────
    // Username = kolom email di tabel login_admin (dipakai sebagai teks, bukan harus format email)
    if ($tipe === 'admin') {
        $username = trim(mysqli_real_escape_string($conn, $_POST['username'] ?? ''));
        $password = trim(mysqli_real_escape_string($conn, $_POST['password'] ?? ''));

        if ($username === '' || $password === '') {
            $error = 'Username dan Password wajib diisi.';
        } else {
            $q = mysqli_query($conn,
                "SELECT * FROM login_admin
                 WHERE email = '$username' AND password = '$password'
                 LIMIT 1"
            );

            if ($q && mysqli_num_rows($q) > 0) {
                $admin = mysqli_fetch_assoc($q);
                $_SESSION['status']      = 'login';
                $_SESSION['admin_login'] = true;
                $_SESSION['admin_user']  = $admin['email'];

                header('Location: ../admin/dashboard_admin.php');
                exit;
            } else {
                $error = 'Username atau Password salah.';
            }
        }
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
    <li><a href="#">Service</a></li>
    <li><a href="#">Contact Us</a></li>
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

    <!-- Right: auth card -->
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

      <!-- ── USER FORM (default tampil) ── -->
      <div id="tab-user">

        <p class="auth-sub">
          Belum memiliki akun? <a href="menu.php">booking Sekarang</a>
        </p>

        <form method="POST" action="landing_page.php">
          <input type="hidden" name="tipe" value="user">

          <div class="input-group">
            <label>No Handphone</label>
            <input type="text" name="no_handphone"
                   placeholder="08512503097"
                   value="<?= htmlspecialchars($_POST['no_handphone'] ?? '') ?>">
          </div>
          <div class="input-group">
            <label>Plat mobil</label>
            <input type="text" name="plat_mobil"
                   placeholder="E 2105 CNTH"
                   style="text-transform:uppercase"
                   value="<?= htmlspecialchars($_POST['plat_mobil'] ?? '') ?>">
          </div>

          <button class="btn-signup" type="submit">LOGIN</button>
        </form>

        <p style="text-align:right;margin-top:10px;">
          <a href="#" onclick="showAdmin();return false;"
             style="font-size:11px;color:rgba(255,255,255,0.3);text-decoration:none;">
            Admin ›
          </a>
        </p>
      </div>

      <!-- ── ADMIN FORM (tersembunyi) ── -->
      <div id="tab-admin" style="display:none;">

        <p class="auth-sub">Login sebagai Admin</p>

        <form method="POST" action="landing_page.php">
          <input type="hidden" name="tipe" value="admin">

          <div class="input-group">
            <label>Username</label>
            <input type="text" name="username"
                   placeholder="username admin"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <div class="input-group">
            <label>Password</label>
            <input type="password" name="password"
                   placeholder="••••••••">
          </div>

          <button class="btn-signup" type="submit">LOGIN</button>
        </form>

        <p style="text-align:center;margin-top:10px;">
          <a href="#" onclick="showUser();return false;"
             style="font-size:11px;color:rgba(255,255,255,0.3);text-decoration:none;">
            ‹ Kembali ke User
          </a>
        </p>
      </div>

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
      <button class="card-btn"onclick="window.location.href='menu.php'">Mulai Booking</button>
    </div>
    <div class="card-item">
      <div class="card-icon">🕘</div>
      <h3 class="card-heading">Bebas Atur Jadwal</h3>
      <p class="card-desc">Tentukan sendiri waktu pencucian sesuai aktivitas Anda tanpa perlu antri.</p>
      <button class="card-btn">Atur Jadwal</button>
    </div>
    <div class="card-item">
      <div class="card-icon">🕒</div>
      <h3 class="card-heading">Proses Cepat &amp; Aman</h3>
      <p class="card-desc">Dikerjakan oleh tenaga profesional dengan peralatan dan produk yang aman untuk kendaraan.</p>
      <button class="card-btn">Cek Layanan</button>
    </div>

    <hr class="divider-grid">

    <!-- Row 2 -->
    <div class="card-item">
      <div class="card-icon">⭐</div>
      <h3 class="card-heading">Basic Wash</h3>
      <p class="card-desc">Cuci cepat &amp; bersih untuk perawatan rutin.</p>
      <button class="card-btn">Pesan Sekarang</button>
    </div>
    <div class="card-item">
      <div class="card-icon">🔥</div>
      <h3 class="card-heading">Standard Clean</h3>
      <p class="card-desc">Eksterior &amp; interior bersih lebih menyeluruh.</p>
      <button class="card-btn">Pesan Sekarang</button>
    </div>
    <div class="card-item">
      <div class="card-icon">💎</div>
      <h3 class="card-heading">Premium Care</h3>
      <p class="card-desc">Perawatan lengkap dengan hasil maksimal.</p>
      <button class="card-btn">Pesan Sekarang</button>
    </div>

  </div>
</section>

<!-- FOOTER -->
<footer class="footer">
  <div class="footer-container">

    <!-- Kiri -->
    <div class="footer-col">
      <h3>Habibi Garage</h3>
      <p>
        Habibi Garage adalah solusi terbaik untuk perawatan kendaraan Anda.
      </p>
      <p>
        Kami menghadirkan layanan cuci mobil yang bersih, cepat, dan berkualitas dengan hasil maksimal.
        Kepuasan pelanggan adalah prioritas utama kami.
      </p>
    </div>

    <!-- Tengah -->
    <div class="footer-col">
      <h4> Alamat</h4>
      <p>
         Jl. Sojar No.Depan, pasar,<br>
         Kec. Jatibarang,<br>
        Kabupaten Indramayu
      </p>
    </div>

    <!-- Kanan -->
    <div class="footer-col">
      <h4>Contact</h4>
      <p>Email: info@email.com</p>
      <p>Phone: +62 xxx-xxxx-xxxx</p>
      <p>WhatsApp: +62 xxx-xxxx-xxxx</p>
    </div>

  </div>

  <!-- Bottom -->
  <div class="footer-bottom">
    <p>© 2026 Habibi Garage. All Rights Reserved.</p>
    <div class="footer-links">
      <a href="#">Privacy Policy</a>
      <a href="#">Terms of Service</a>
    </div>
  </div>
</footer>

<script>
  function showAdmin() {
    document.getElementById('tab-user').style.display  = 'none';
    document.getElementById('tab-admin').style.display = 'block';
  }
  function showUser() {
    document.getElementById('tab-admin').style.display = 'none';
    document.getElementById('tab-user').style.display  = 'block';
  }

  // Kalau POST admin gagal, otomatis buka form admin lagi
  <?php if (($_ = $_POST['tipe'] ?? '') === 'admin' && $error): ?>
  document.addEventListener('DOMContentLoaded', showAdmin);
  <?php endif; ?>
</script>

</body>
</html>