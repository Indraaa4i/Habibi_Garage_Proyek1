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

// Ambil semua booking user ini, terbaru di atas
$q = mysqli_query($conn,
    "SELECT p.*, pl.nama_paket, pl.harga
     FROM pemesanan p
     JOIN paket_layanan pl ON p.id_paket = pl.id_paket
     WHERE p.no_telepon = '$no_hp'
       AND UPPER(REPLACE(p.plat_mobil,' ','')) = REPLACE('$plat',' ','')
     ORDER BY p.tanggal DESC, p.jam DESC"
);

// Booking aktif = yang belum selesai/dibatalkan (untuk kartu status utama)
$booking_aktif = null;
$semua = [];
while ($row = mysqli_fetch_assoc($q)) {
    $semua[] = $row;
    if (!$booking_aktif && in_array($row['status'], ['pending', 'proses', 'lunas'])) {
        $booking_aktif = $row;
    }
}

// Mapping status ke label & warna
function statusInfo($status) {
    switch ($status) {
        case 'pending':  return ['label' => 'Menunggu Konfirmasi', 'color' => '#f59e0b', 'icon' => '⏳'];
        case 'proses':   return ['label' => 'Sedang Dicuci',       'color' => '#3b82f6', 'icon' => '🚗'];
        case 'lunas':    return ['label' => 'Selesai',             'color' => '#10b981', 'icon' => '✅'];
        case 'dibatalkan': return ['label' => 'Dibatalkan',        'color' => '#ef4444', 'icon' => '❌'];
        default:         return ['label' => ucfirst($status),      'color' => '#6b7280', 'icon' => '•'];
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
      --navy:    #0f1b2d;
      --cyan:    #00c8e0;
      --accent:  #e8a020;
      --bg:      #f2f4f8;
      --white:   #ffffff;
      --text:    #1c2b3a;
      --muted:   #8a9ab0;
      --border:  #e2e8f0;
      --radius:  14px;
      --shadow:  0 2px 16px rgba(15,27,45,0.10);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Nunito', sans-serif; background: var(--bg); color: var(--text); min-height: 100vh; }

    /* HEADER */
    header {
      background: var(--navy);
      padding: 0 40px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    header img { height: 42px; }
    .header-right { display: flex; align-items: center; gap: 16px; }
    .btn-logout {
      background: rgba(255,255,255,0.1);
      color: #fff;
      border: 1px solid rgba(255,255,255,0.2);
      padding: 7px 18px;
      border-radius: 8px;
      font-family: 'Nunito', sans-serif;
      font-size: 13px;
      font-weight: 700;
      cursor: pointer;
      text-decoration: none;
      transition: background .2s;
    }
    .btn-logout:hover { background: rgba(255,255,255,0.2); }
    .btn-booking {
      background: var(--cyan);
      color: var(--navy);
      border: none;
      padding: 8px 20px;
      border-radius: 8px;
      font-family: 'Nunito', sans-serif;
      font-size: 13px;
      font-weight: 800;
      cursor: pointer;
      text-decoration: none;
      transition: opacity .2s;
    }
    .btn-booking:hover { opacity: .85; }

    /* LAYOUT */
    .container { max-width: 860px; margin: 36px auto; padding: 0 20px; }

    /* GREETING */
    .greeting { margin-bottom: 28px; }
    .greeting h1 { font-size: 26px; font-weight: 800; color: var(--navy); }
    .greeting p  { color: var(--muted); font-size: 14px; margin-top: 4px; }

    /* STATUS CARD UTAMA */
    .status-card {
      background: var(--navy);
      border-radius: var(--radius);
      padding: 28px 32px;
      margin-bottom: 28px;
      color: #fff;
      position: relative;
      overflow: hidden;
      box-shadow: var(--shadow);
    }
    .status-card::after {
      content: '';
      position: absolute;
      right: -40px; top: -40px;
      width: 200px; height: 200px;
      border-radius: 50%;
      background: rgba(0,200,224,0.08);
    }
    .status-card .sc-label { font-size: 12px; color: rgba(255,255,255,0.5); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .status-card .sc-paket { font-size: 22px; font-weight: 800; margin-bottom: 16px; }
    .status-card .sc-meta  { display: flex; gap: 32px; flex-wrap: wrap; }
    .status-card .sc-meta-item .key { font-size: 11px; color: rgba(255,255,255,0.4); }
    .status-card .sc-meta-item .val { font-size: 15px; font-weight: 700; margin-top: 2px; }
    .status-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 5px 14px;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 800;
      margin-bottom: 18px;
    }
    .no-booking {
      background: var(--white);
      border-radius: var(--radius);
      padding: 40px;
      text-align: center;
      margin-bottom: 28px;
      box-shadow: var(--shadow);
    }
    .no-booking .nb-icon { font-size: 48px; margin-bottom: 12px; }
    .no-booking h3 { font-size: 18px; font-weight: 800; color: var(--navy); margin-bottom: 6px; }
    .no-booking p  { color: var(--muted); font-size: 14px; margin-bottom: 20px; }

    /* RIWAYAT */
    .section-title {
      font-size: 16px;
      font-weight: 800;
      color: var(--navy);
      margin-bottom: 14px;
    }
    .history-list { display: flex; flex-direction: column; gap: 12px; }
    .history-item {
      background: var(--white);
      border-radius: var(--radius);
      padding: 18px 22px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      box-shadow: var(--shadow);
      border-left: 4px solid transparent;
    }
    .history-item.status-pending  { border-left-color: #f59e0b; }
    .history-item.status-proses   { border-left-color: #3b82f6; }
    .history-item.status-lunas    { border-left-color: #10b981; }
    .history-item.status-dibatalkan { border-left-color: #ef4444; }

    .hi-left .hi-paket { font-size: 15px; font-weight: 800; color: var(--navy); }
    .hi-left .hi-meta  { font-size: 12px; color: var(--muted); margin-top: 3px; }
    .hi-right { text-align: right; flex-shrink: 0; }
    .hi-right .hi-harga { font-size: 14px; font-weight: 800; color: var(--navy); }
    .hi-badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 50px;
      font-size: 11px;
      font-weight: 700;
      margin-top: 4px;
    }
    .badge-pending    { background: #fef3c7; color: #92400e; }
    .badge-proses     { background: #dbeafe; color: #1e40af; }
    .badge-lunas      { background: #d1fae5; color: #065f46; }
    .badge-dibatalkan { background: #fee2e2; color: #991b1b; }

    .empty-history { color: var(--muted); font-size: 14px; text-align: center; padding: 24px; background: var(--white); border-radius: var(--radius); }

    @media (max-width: 600px) {
      header { padding: 0 20px; }
      .container { margin: 20px auto; }
      .status-card { padding: 20px; }
      .history-item { flex-direction: column; align-items: flex-start; }
      .hi-right { text-align: left; }
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

  <!-- GREETING -->
  <div class="greeting">
    <h1>Halo, <?= htmlspecialchars($_SESSION['nama_pelanggan']) ?> 👋</h1>
    <p>No. Telepon: <?= htmlspecialchars($no_hp) ?> &nbsp;·&nbsp; Plat: <?= htmlspecialchars(strtoupper($plat)) ?></p>
  </div>

  <!-- STATUS BOOKING AKTIF -->
  <?php if ($booking_aktif): 
    $si = statusInfo($booking_aktif['status']);
  ?>
  <div class="status-card">
    <div class="sc-label">Booking Aktif</div>
    <div class="sc-paket"><?= htmlspecialchars($booking_aktif['nama_paket']) ?></div>
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
    <?php if ($booking_aktif['status'] === 'pending'): ?>
      <p style="margin-top:16px;font-size:12px;color:rgba(255,255,255,0.4);">
        Bukti pembayaran sedang diverifikasi admin. Mohon tunggu.
      </p>
    <?php elseif ($booking_aktif['status'] === 'lunas'): ?>
      <p style="margin-top:16px;font-size:12px;color:rgba(255,255,255,0.4);">
        Pembayaran dikonfirmasi. Silakan datang sesuai jadwal.
      </p>
      <a href="bukti.php?id=<?= $booking_aktif['id_pemesanan'] ?>" 
         style="display:inline-block;margin-top:12px;padding:7px 16px;background:var(--cyan);color:var(--navy);border-radius:8px;font-size:12px;font-weight:800;text-decoration:none;">
        Lihat Bukti Pembayaran
      </a>
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

  <!-- RIWAYAT -->
  <div class="section-title">Riwayat Booking</div>
  <div class="history-list">
    <?php if (empty($semua)): ?>
      <div class="empty-history">Belum ada riwayat booking.</div>
    <?php else: foreach ($semua as $item):
      $si = statusInfo($item['status']);
    ?>
    <div class="history-item status-<?= $item['status'] ?>">
      <div class="hi-left">
        <div class="hi-paket"><?= htmlspecialchars($item['nama_paket']) ?></div>
        <div class="hi-meta">
          <?= date('d M Y', strtotime($item['tanggal'])) ?> &nbsp;·&nbsp; <?= htmlspecialchars($item['jam']) ?>
          &nbsp;·&nbsp; <?= htmlspecialchars(strtoupper($item['plat_mobil'])) ?>
        </div>
      </div>
      <div class="hi-right">
        <div class="hi-harga">Rp <?= number_format($item['harga'], 0, ',', '.') ?></div>
        <span class="hi-badge badge-<?= $item['status'] ?>"><?= $si['icon'] ?> <?= $si['label'] ?></span>
      </div>
    </div>
    <?php endforeach; endif; ?>
  </div>

</div>

</body>
</html>