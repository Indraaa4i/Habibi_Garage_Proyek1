<?php
session_start();
include 'koneksi.php';

// Wajib login
if (empty($_SESSION['user_login'])) {
    header('Location: landing_page.php');
    exit;
}

$no_hp = $_SESSION['no_handphone'];
$plat  = $_SESSION['plat_mobil'];

/* =========================================================
   HANDLE BATAL BOOKING
   Boleh batal jika:
   - status = pending
   - atau status = lunas DAN status_cuci = belum_dicuci
   ========================================================= */
if (isset($_POST['batalkan_booking'])) {
    $id_batal = (int) $_POST['id_pemesanan'];

    $cek = mysqli_query($conn, "
        SELECT * FROM pemesanan
        WHERE id_pemesanan = '$id_batal'
        AND no_telepon = '$no_hp'
        AND UPPER(REPLACE(plat_mobil,' ','')) = REPLACE('$plat',' ','')
        AND (
            status = 'pending'
            OR (status = 'lunas' AND status_cuci = 'belum_dicuci')
        )
    ");

    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "
            UPDATE pemesanan 
            SET status = 'dibatalkan'
            WHERE id_pemesanan = '$id_batal'
        ");

        echo "<script>alert('Booking berhasil dibatalkan.'); window.location='profile.php';</script>";
        exit;
    } else {
        echo "<script>alert('Booking tidak dapat dibatalkan.'); window.location='profile.php';</script>";
        exit;
    }
}

/* =========================================================
   AMBIL DATA BOOKING USER
   ========================================================= */
$q = mysqli_query($conn,
    "SELECT p.*, pl.nama_paket, pl.harga
     FROM pemesanan p
     JOIN paket_layanan pl ON p.id_paket = pl.id_paket
     WHERE p.no_telepon = '$no_hp'
       AND UPPER(REPLACE(p.plat_mobil,' ','')) = REPLACE('$plat',' ','')
     ORDER BY p.tanggal DESC, p.jam DESC"
);

$booking_aktif = null;
$semua = [];

while ($row = mysqli_fetch_assoc($q)) {
    $semua[] = $row;

    if (
        !$booking_aktif &&
        $row['status'] !== 'dibatalkan' &&
        $row['status_cuci'] !== 'selesai'
    ) {
        $booking_aktif = $row;
    }
}

/* =========================================================
   STATUS BOOKING
   ========================================================= */
function statusInfo($status) {
    switch ($status) {
        case 'pending':
            return ['label' => 'Menunggu Konfirmasi', 'color' => '#f59e0b', 'icon' => '⏳'];
        case 'lunas':
            return ['label' => 'Pembayaran Dikonfirmasi', 'color' => '#06b6d4', 'icon' => '💳'];
        case 'dibatalkan':
            return ['label' => 'Dibatalkan', 'color' => '#ef4444', 'icon' => '❌'];
        default:
            return ['label' => ucfirst($status), 'color' => '#6b7280', 'icon' => '•'];
    }
}

/* =========================================================
   STATUS PENGERJAAN CUCI
   ========================================================= */
function cuciInfo($status_cuci) {
    switch ($status_cuci) {
        case 'belum_dicuci':
            return ['label' => 'Belum Dicuci', 'step' => 1];
        case 'diproses':
            return ['label' => 'Sedang Dicuci', 'step' => 2];
        case 'selesai':
            return ['label' => 'Selesai', 'step' => 3];
        default:
            return ['label' => '-', 'step' => 0];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil – Habibi Garage</title>
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Bebas+Neue&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy: #0f1b2d;
      --cyan: #00c8e0;
      --bg: #f2f4f8;
      --white: #ffffff;
      --text: #1c2b3a;
      --muted: #8a9ab0;
      --radius: 14px;
      --shadow: 0 2px 16px rgba(15,27,45,0.10);
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    header {
      background: var(--navy);
      padding: 0 40px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    header img {
      height: 42px;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .btn-logout,
    .btn-booking {
      padding: 8px 18px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 800;
      transition: .2s;
    }

    .btn-logout {
      background: rgba(255,255,255,.1);
      color: #fff;
    }

    .btn-logout:hover {
      background: rgba(255,255,255,.2);
    }

    .btn-booking {
      background: var(--cyan);
      color: var(--navy);
    }

    .btn-booking:hover {
      opacity: .85;
    }

    .container {
      max-width: 860px;
      margin: 36px auto;
      padding: 0 20px;
    }

    .greeting {
      margin-bottom: 28px;
    }

    .greeting h1 {
      font-size: 26px;
      font-weight: 800;
      color: var(--navy);
    }

    .greeting p {
      color: var(--muted);
      font-size: 14px;
      margin-top: 4px;
    }

    .status-card {
      background: var(--navy);
      border-radius: var(--radius);
      padding: 28px;
      margin-bottom: 28px;
      color: #fff;
      box-shadow: var(--shadow);
    }

    .status-card .top-label {
      font-size: 12px;
      color: rgba(255,255,255,.5);
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .status-card .paket-title {
      font-size: 24px;
      font-weight: 800;
      margin-top: 4px;
    }

    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 800;
      margin: 14px 0 18px;
    }

    .sc-meta {
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
    }

    .sc-meta-item .key {
      font-size: 11px;
      color: rgba(255,255,255,.45);
    }

    .sc-meta-item .val {
      font-size: 15px;
      font-weight: 700;
      margin-top: 2px;
    }

    .progress-wrap {
      margin-top: 24px;
      background: rgba(255,255,255,.05);
      border-radius: 12px;
      padding: 16px;
    }

    .progress-title {
      font-size: 12px;
      color: rgba(255,255,255,.6);
      margin-bottom: 14px;
      text-transform: uppercase;
      letter-spacing: .8px;
    }

    .progress-steps {
      display: flex;
      justify-content: space-between;
      gap: 12px;
    }

    .step {
      flex: 1;
      text-align: center;
      position: relative;
    }

    .step::after {
      content: '';
      position: absolute;
      top: 14px;
      right: -50%;
      width: 100%;
      height: 3px;
      background: rgba(255,255,255,.12);
      z-index: 0;
    }

    .step:last-child::after {
      display: none;
    }

    .step-circle {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      margin: 0 auto 8px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 800;
      background: rgba(255,255,255,.12);
      color: #fff;
      position: relative;
      z-index: 1;
    }

    .step.active .step-circle {
      background: var(--cyan);
      color: var(--navy);
    }

    .step-label {
      font-size: 11px;
      color: rgba(255,255,255,.75);
    }

    .action-wrap {
      margin-top: 18px;
    }

    .btn-cancel {
      border: none;
      background: #ef4444;
      color: #fff;
      padding: 7px 14px;
      border-radius: 8px;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      margin-top: 6px;
    }

    .btn-cancel:hover {
      opacity: .9;
    }

    .no-booking {
      background: var(--white);
      border-radius: var(--radius);
      padding: 40px;
      text-align: center;
      margin-bottom: 28px;
      box-shadow: var(--shadow);
    }

    .no-booking .nb-icon {
      font-size: 48px;
      margin-bottom: 12px;
    }

    .no-booking h3 {
      font-size: 18px;
      font-weight: 800;
      color: var(--navy);
      margin-bottom: 6px;
    }

    .no-booking p {
      color: var(--muted);
      font-size: 14px;
      margin-bottom: 20px;
    }

    .section-title {
      font-size: 16px;
      font-weight: 800;
      color: var(--navy);
      margin-bottom: 14px;
    }

    .history-list {
      display: flex;
      flex-direction: column;
      gap: 12px;
    }

    .history-item {
      background: var(--white);
      border-radius: var(--radius);
      padding: 18px 20px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      box-shadow: var(--shadow);
    }

    .hi-left .hi-paket {
      font-size: 15px;
      font-weight: 800;
      color: var(--navy);
    }

    .hi-left .hi-meta {
      font-size: 12px;
      color: var(--muted);
      margin-top: 4px;
    }

    .hi-right {
      text-align: right;
    }

    .hi-harga {
      font-size: 14px;
      font-weight: 800;
      color: var(--navy);
    }

    .hi-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 50px;
      font-size: 11px;
      font-weight: 700;
      margin-top: 5px;
    }

    .empty-history {
      color: var(--muted);
      font-size: 14px;
      text-align: center;
      padding: 24px;
      background: var(--white);
      border-radius: var(--radius);
    }

    @media (max-width: 600px) {
      header {
        padding: 0 20px;
      }

      .container {
        margin: 20px auto;
      }

      .status-card {
        padding: 20px;
      }

      .history-item {
        flex-direction: column;
        align-items: flex-start;
      }

      .hi-right {
        text-align: left;
      }

      .progress-steps {
        flex-direction: column;
      }

      .step::after {
        display: none;
      }
    }
  </style>
</head>
<body>

<header>
  <img src="../img/logo.png" alt="Habibi Garage">
  <div class="header-right">
    <a href="menu.php" class="btn-booking">+ Booking Baru</a>
    <a href="landing_page.php?logout=1" class="btn-logout">Logout</a>
  </div>
</header>

<div class="container">

  <div class="greeting">
    <h1>Halo, <?= htmlspecialchars($_SESSION['nama_pelanggan']) ?> 👋</h1>
    <p>No. Telepon: <?= htmlspecialchars($no_hp) ?> &nbsp;·&nbsp; Plat: <?= htmlspecialchars(strtoupper($plat)) ?></p>
  </div>

  <?php if ($booking_aktif): 
    $si = statusInfo($booking_aktif['status']);
    $ci = cuciInfo($booking_aktif['status_cuci']);
  ?>
    <div class="status-card">
      <div class="top-label">Booking Aktif</div>
      <div class="paket-title"><?= htmlspecialchars($booking_aktif['nama_paket']) ?></div>

      <div class="status-badge" style="background:<?= $si['color'] ?>22;color:<?= $si['color'] ?>;">
        <?= $si['icon'] ?> <?= $si['label'] ?>
      </div>

      <div class="sc-meta">
        <div class="sc-meta-item">
          <div class="key">Tanggal</div>
          <div class="val"><?= date('d M Y', strtotime($booking_aktif['tanggal'])) ?></div>
        </div>
        <div class="sc-meta-item">
          <div class="key">Jam Datang</div>
          <div class="val"><?= htmlspecialchars($booking_aktif['jam']) ?></div>
        </div>
        <div class="sc-meta-item">
          <div class="key">Plat Mobil</div>
          <div class="val"><?= htmlspecialchars(strtoupper($booking_aktif['plat_mobil'])) ?></div>
        </div>
        <div class="sc-meta-item">
          <div class="key">Total</div>
          <div class="val">Rp <?= number_format($booking_aktif['harga'], 0, ',', '.') ?></div>
        </div>
      </div>

      <div class="progress-wrap">
        <div class="progress-title">Status Pengerjaan Mobil</div>
        <div class="progress-steps">
          <div class="step <?= $ci['step'] >= 1 ? 'active' : '' ?>">
            <div class="step-circle">1</div>
            <div class="step-label">Belum Dicuci</div>
          </div>

          <div class="step <?= $ci['step'] >= 2 ? 'active' : '' ?>">
            <div class="step-circle">2</div>
            <div class="step-label">Diproses</div>
          </div>

          <div class="step <?= $ci['step'] >= 3 ? 'active' : '' ?>">
            <div class="step-circle">3</div>
            <div class="step-label">Selesai</div>
          </div>
        </div>
      </div>

      <?php if (
        $booking_aktif['status'] === 'pending' ||
        ($booking_aktif['status'] === 'lunas' && $booking_aktif['status_cuci'] === 'belum_dicuci')
      ): ?>
        <div class="action-wrap">
          <form method="POST" onsubmit="return confirm('Yakin ingin membatalkan booking ini?')">
            <input type="hidden" name="id_pemesanan" value="<?= $booking_aktif['id_pemesanan'] ?>">
            <button type="submit" name="batalkan_booking" class="btn-cancel">Batalkan Booking</button>
          </form>
        </div>
      <?php endif; ?>
    </div>

  <?php else: ?>
    <div class="no-booking">
      <div class="nb-icon">🚗</div>
      <h3>Belum ada booking aktif</h3>
      <p>Buat booking baru untuk layanan cuci mobil Anda.</p>
      <a href="menu.php" class="btn-booking" style="display:inline-block;padding:10px 28px;border-radius:10px;">Booking Sekarang</a>
    </div>
  <?php endif; ?>

  <div class="section-title">Riwayat Booking</div>
  <div class="history-list">
    <?php if (empty($semua)): ?>
      <div class="empty-history">Belum ada riwayat booking.</div>
    <?php else: ?>
      <?php foreach ($semua as $item): 
        $si = statusInfo($item['status']);
      ?>
        <div class="history-item">
          <div class="hi-left">
            <div class="hi-paket"><?= htmlspecialchars($item['nama_paket']) ?></div>
            <div class="hi-meta">
              <?= date('d M Y', strtotime($item['tanggal'])) ?> &nbsp;·&nbsp;
              <?= htmlspecialchars($item['jam']) ?> &nbsp;·&nbsp;
              <?= htmlspecialchars(strtoupper($item['plat_mobil'])) ?>
            </div>

            <?php if (
              $item['status'] === 'pending' ||
              ($item['status'] === 'lunas' && $item['status_cuci'] === 'belum_dicuci')
            ): ?>
              <form method="POST" onsubmit="return confirm('Yakin ingin membatalkan booking ini?')">
                <input type="hidden" name="id_pemesanan" value="<?= $item['id_pemesanan'] ?>">
                <button type="submit" name="batalkan_booking" class="btn-cancel">Batalkan</button>
              </form>
            <?php endif; ?>
          </div>

          <div class="hi-right">
            <div class="hi-harga">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
            <span class="hi-badge" style="background:<?= $si['color'] ?>22;color:<?= $si['color'] ?>;">
              <?= $si['icon'] ?> <?= $si['label'] ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</div>

</body>
</html>