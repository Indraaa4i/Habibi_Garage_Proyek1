<?php
session_start();
include 'koneksi.php';

$semua_slot = [
    '08:00 - 09:00',
    '09:00 - 10:00',
    '10:00 - 11:00',
    '11:00 - 12:00',
    '13:00 - 14:00',
    '14:00 - 15:00',
    '15:00 - 16:00',
];

$hari_nama = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
$jadwal    = [];
$ts        = strtotime('today');
$count     = 0;

while ($count < 3) {
    $ts  += 86400;
    $dow  = (int) date('w', $ts);
    if ($dow === 5) continue; // Jumat libur

    $tgl   = date('Y-m-d', $ts);
    $label = $hari_nama[$dow] . ', ' . date('d M Y', $ts);

    $q        = mysqli_query($conn, "SELECT jam FROM pemesanan WHERE tanggal = '$tgl'");
    $terpesan = [];
    while ($row = mysqli_fetch_assoc($q)) $terpesan[] = $row['jam'];

    $slots = [];
    foreach ($semua_slot as $slot) {
        $slots[] = ['jam' => $slot, 'status' => in_array($slot, $terpesan) ? 'penuh' : 'tersedia'];
    }

    $jadwal[] = ['tanggal' => $tgl, 'label' => $label, 'slots' => $slots];
    $count++;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cek Jadwal – Habibi Garage</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../css/landing_page.css">
  <style>
    body { background: #000000; min-height: 100vh; }

    /* ── Navbar (sama dengan landing) ── */
    nav {
      position: relative;
      background: #0d0d0d;
      border-bottom: 1px solid rgba(255,255,255,.08);
    }

    /* ── Page wrapper ── */
    .jadwal-page {
      max-width: 800px;
      margin: 0 auto;
      padding: 48px 24px 80px;
    }

    /* ── Page header ── */
    .page-header {
      margin-bottom: 36px;
    }
    .back-link {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      color: #9ca3af;
      font-size: 13px;
      text-decoration: none;
      margin-bottom: 20px;
      transition: color .2s;
    }
    .back-link:hover { color: #00c8e0; }
    .page-title {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 42px;
      color: #fff;
      line-height: 1;
      margin-bottom: 8px;
    }
    .page-title span { color: #00c8e0; }
    .page-sub {
      color: #9ca3af;
      font-size: 14px;
    }
    .page-sub em { color: #facc15; font-style: normal; }

    /* ── Legenda ── */
    .legenda {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-bottom: 32px;
      flex-wrap: wrap;
    }
    .leg-item {
      display: flex;
      align-items: center;
      gap: 7px;
      font-size: 13px;
      color: #d1d5db;
    }
    .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    .dot-hijau { background: #22c55e; }
    .dot-merah { background: #ef4444; }

    /* ── Hari block ── */
    .hari-blok {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 14px;
      padding: 24px;
      margin-bottom: 20px;
    }
    .hari-label {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 16px;
      flex-wrap: wrap;
      gap: 6px;
    }
    .hari-nama {
      font-size: 15px;
      font-weight: 700;
      color: #00c8e0;
      text-transform: uppercase;
      letter-spacing: .05em;
    }
    .hari-badge {
      font-size: 11px;
      font-weight: 600;
      color: #0d0d0d;
      background: #22c55e;
      border-radius: 20px;
      padding: 3px 10px;
    }
    .hari-badge.penuh-badge {
      background: #ef4444;
    }

    /* ── Slot grid ── */
    .slot-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
      gap: 10px;
    }
    .slot-item {
      border-radius: 10px;
      padding: 10px 12px;
      font-size: 13px;
      font-weight: 600;
      text-align: center;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 7px;
    }
    .slot-tersedia {
      background: rgba(34,197,94,.1);
      border: 1px solid rgba(34,197,94,.35);
      color: #86efac;
    }
    .slot-penuh {
      background: rgba(239,68,68,.08);
      border: 1px solid rgba(239,68,68,.3);
      color: #fca5a5;
      text-decoration: line-through;
      opacity: .65;
    }
    .slot-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
    .slot-tersedia .slot-dot { background: #22c55e; }
    .slot-penuh    .slot-dot { background: #ef4444; }

    /* ── CTA button ── */
    .cta-wrap {
      text-align: center;
      margin-top: 40px;
    }
    .btn-booking {
      display: inline-block;
      background: #00c8e0;
      color: #0d0d0d;
      font-family: 'Barlow', sans-serif;
      font-weight: 700;
      font-size: 15px;
      padding: 14px 40px;
      border-radius: 10px;
      text-decoration: none;
      transition: background .2s, transform .15s;
    }
    .btn-booking:hover {
      background: #00afc5;
      transform: translateY(-2px);
    }

    @media (max-width: 480px) {
      .page-title { font-size: 32px; }
      .slot-grid { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<nav>
  <a class="logo" href="landing_page.php">
    <img src="../img/logo.png" alt="Habibi Garage Logo" class="logo-img">
  </a>
  <ul class="nav-links">
    <li><a href="landing_page.php">Home</a></li>
    <li><a href="#">Service</a></li>
    <li><a href="#">Contact Us</a></li>
    <li><a href="#">About Us</a></li>
  </ul>
</nav>

<!-- KONTEN JADWAL -->
<div class="jadwal-page">

  <div class="page-header">
    <a href="landing_page.php" class="back-link">← Kembali ke Beranda</a>
    <h1 class="page-title">Cek <span>Jadwal</span></h1>
    <p class="page-sub">
      Jadwal tersedia 3 hari ke depan &nbsp;·&nbsp;
      <em>Jumat libur</em>
    </p>
  </div>

  <!-- Legenda -->
  <div class="legenda">
    <div class="leg-item"><span class="dot dot-hijau"></span> Slot tersedia</div>
    <div class="leg-item"><span class="dot dot-merah"></span> Sudah penuh</div>
  </div>

  <!-- Daftar hari -->
  <?php foreach ($jadwal as $hari):
    $tersedia = count(array_filter($hari['slots'], fn($s) => $s['status'] === 'tersedia'));
  ?>
  <div class="hari-blok">
    <div class="hari-label">
      <span class="hari-nama"><?= $hari['label'] ?></span>
      <span class="hari-badge <?= $tersedia === 0 ? 'penuh-badge' : '' ?>">
        <?= $tersedia === 0 ? 'Penuh' : "$tersedia slot tersedia" ?>
      </span>
    </div>
    <div class="slot-grid">
      <?php foreach ($hari['slots'] as $slot): ?>
        <div class="slot-item <?= $slot['status'] === 'tersedia' ? 'slot-tersedia' : 'slot-penuh' ?>">
          <span class="slot-dot"></span>
          <?= $slot['jam'] ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>

  <!-- CTA -->
  <div class="cta-wrap">
    <a href="menu.php" class="btn-booking">Booking Sekarang →</a>
  </div>

</div>

</body>
</html>