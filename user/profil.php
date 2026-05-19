<?php
session_start();
include 'koneksi.php';

// Wajib login
if (empty($_SESSION['user_login'])) {
    header('Location: landing_page.php');
    exit;
}

$no_hp = $_SESSION['no_handphone'];

/* =========================================================
   HANDLE TAMBAH PLAT BARU
   ========================================================= */
if (isset($_POST['tambah_plat'])) {
    $plat_baru = strtoupper(trim(mysqli_real_escape_string($conn, $_POST['plat_baru'])));

    if ($plat_baru !== '') {
        // Simpan plat ke session (array plat)
        if (!isset($_SESSION['daftar_plat'])) {
            $_SESSION['daftar_plat'] = [$_SESSION['plat_mobil']];
        }
        if (!in_array($plat_baru, $_SESSION['daftar_plat'])) {
            $_SESSION['daftar_plat'][] = $plat_baru;
        }
    }
    header('Location: profil.php');
    exit;
}

/* =========================================================
   HANDLE HAPUS PLAT
   ========================================================= */
if (isset($_POST['hapus_plat'])) {
    $plat_hapus = strtoupper(trim($_POST['plat_hapus']));
    // Jangan hapus plat utama (pertama)
    if (isset($_SESSION['daftar_plat']) && $plat_hapus !== strtoupper($_SESSION['plat_mobil'])) {
        $_SESSION['daftar_plat'] = array_values(
            array_filter($_SESSION['daftar_plat'], fn($p) => strtoupper($p) !== $plat_hapus)
        );
    }
    header('Location: profil.php');
    exit;
}

/* =========================================================
   HANDLE BATAL BOOKING
   ========================================================= */

// --- Batalkan langsung (status pending, belum bayar) ---
if (isset($_POST['batalkan_booking'])) {
    $id_batal = (int) $_POST['id_pemesanan'];

    $plat_list = $_SESSION['daftar_plat'] ?? [$_SESSION['plat_mobil']];
    $plat_sql  = implode("','", array_map(fn($p) => strtoupper(str_replace(' ', '', $p)), $plat_list));

    $cek = mysqli_query($conn, "
        SELECT * FROM pemesanan
        WHERE id_pemesanan = '$id_batal'
        AND no_telepon = '$no_hp'
        AND UPPER(REPLACE(plat_mobil,' ','')) IN ('$plat_sql')
        AND status = 'pending'
    ");

    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "UPDATE pemesanan SET status = 'dibatalkan' WHERE id_pemesanan = '$id_batal'");
        echo "<script>alert('Booking berhasil dibatalkan.'); window.location='profil.php';</script>";
        exit;
    } else {
        echo "<script>alert('Booking tidak dapat dibatalkan.'); window.location='profil.php';</script>";
        exit;
    }
}

// --- Batalkan + Refund (status lunas, belum dicuci) ---
if (isset($_POST['ajukan_refund'])) {
    $id_batal    = (int) $_POST['id_pemesanan'];
    $nama_rek    = mysqli_real_escape_string($conn, trim($_POST['refund_nama_rek']    ?? ''));
    $nomor_rek   = mysqli_real_escape_string($conn, trim($_POST['refund_nomor_rek']   ?? ''));
    $bank        = mysqli_real_escape_string($conn, trim($_POST['refund_bank']        ?? ''));

    if (!$nama_rek || !$nomor_rek || !$bank) {
        echo "<script>alert('Lengkapi data rekening untuk refund!'); window.history.back();</script>";
        exit;
    }

    $plat_list = $_SESSION['daftar_plat'] ?? [$_SESSION['plat_mobil']];
    $plat_sql  = implode("','", array_map(fn($p) => strtoupper(str_replace(' ', '', $p)), $plat_list));

    $cek = mysqli_query($conn, "
        SELECT * FROM pemesanan
        WHERE id_pemesanan = '$id_batal'
        AND no_telepon = '$no_hp'
        AND UPPER(REPLACE(plat_mobil,' ','')) IN ('$plat_sql')
        AND status = 'lunas'
        AND status_cuci = 'belum_dicuci'
    ");

    if (mysqli_num_rows($cek) > 0) {
        mysqli_query($conn, "
            UPDATE pemesanan
            SET status           = 'dibatalkan',
                refund_nama_rek  = '$nama_rek',
                refund_nomor_rek = '$nomor_rek',
                refund_bank      = '$bank',
                refund_status    = 'menunggu'
            WHERE id_pemesanan = '$id_batal'
        ");
        echo "<script>alert('Booking dibatalkan. Permintaan refund telah dikirim ke admin.'); window.location='profil.php';</script>";
        exit;
    } else {
        echo "<script>alert('Booking tidak dapat dibatalkan.'); window.location='profil.php';</script>";
        exit;
    }
}

/* =========================================================
   INISIALISASI DAFTAR PLAT
   ========================================================= */
// Saat pertama login, bangun daftar_plat dari semua plat
// yang pernah dipakai dengan no_hp yang sama
if (!isset($_SESSION['daftar_plat'])) {
    $q_plat = mysqli_query($conn,
        "SELECT UPPER(REPLACE(plat_mobil,' ','')) as plat
         FROM pemesanan
         WHERE no_telepon = '$no_hp'
         GROUP BY UPPER(REPLACE(plat_mobil,' ',''))
         ORDER BY MAX(id_pemesanan) DESC"
    );
    $daftar = [];
    while ($rp = mysqli_fetch_assoc($q_plat)) {
        $daftar[] = $rp['plat'];
    }
    // Pastikan plat login ada di awal
    $plat_login = strtoupper(str_replace(' ', '', $_SESSION['plat_mobil']));
    if (!in_array($plat_login, $daftar)) {
        array_unshift($daftar, $plat_login);
    } else {
        // Geser plat login ke posisi pertama
        $daftar = array_merge([$plat_login], array_diff($daftar, [$plat_login]));
    }
    $_SESSION['daftar_plat'] = $daftar;
}

$plat_list = $_SESSION['daftar_plat'];

/* =========================================================
   AMBIL SEMUA BOOKING DARI SEMUA PLAT USER
   ========================================================= */
$plat_sql_in = implode("','", array_map(fn($p) => strtoupper(str_replace(' ', '', $p)), $plat_list));

$q = mysqli_query($conn,
    "SELECT p.*, pl.nama_paket, pl.harga
     FROM pemesanan p
     JOIN paket_layanan pl ON p.id_paket = pl.id_paket
     WHERE p.no_telepon = '$no_hp'
       AND UPPER(REPLACE(p.plat_mobil,' ','')) IN ('$plat_sql_in')
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
   HELPERS
   ========================================================= */
function statusInfo($status) {
    switch ($status) {
        case 'pending':    return ['label' => 'Menunggu Konfirmasi',    'color' => '#f59e0b', 'icon' => '⏳'];
        case 'lunas':      return ['label' => 'Pembayaran Dikonfirmasi','color' => '#06b6d4', 'icon' => '💳'];
        case 'dibatalkan': return ['label' => 'Dibatalkan',             'color' => '#ef4444', 'icon' => '❌'];
        case 'ditolak':    return ['label' => 'Ditolak Admin',          'color' => '#dc2626', 'icon' => '🚫'];
        default:           return ['label' => ucfirst($status),         'color' => '#6b7280', 'icon' => '•'];
    }
}

function cuciInfo($status_cuci) {
    switch ($status_cuci) {
        case 'belum_dicuci': return ['label' => 'Belum Dicuci',  'step' => 1];
        case 'diproses':     return ['label' => 'Sedang Dicuci', 'step' => 2];
        case 'selesai':      return ['label' => 'Selesai',       'step' => 3];
        default:             return ['label' => '-',             'step' => 0];
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

    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── HEADER ── */
    header {
      background: var(--navy);
      padding: 0 40px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
    }

    header img { height: 42px; }

    .header-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .btn-logout, .btn-booking {
      padding: 8px 18px;
      border-radius: 8px;
      text-decoration: none;
      font-size: 13px;
      font-weight: 800;
      transition: .2s;
    }

    .btn-logout { background: rgba(255,255,255,.1); color: #fff; }
    .btn-logout:hover { background: rgba(255,255,255,.2); }
    .btn-booking { background: var(--cyan); color: var(--navy); }
    .btn-booking:hover { opacity: .85; }

    /* ── CONTAINER ── */
    .container {
      max-width: 860px;
      margin: 36px auto;
      padding: 0 20px;
    }

    .greeting { margin-bottom: 28px; }
    .greeting h1 { font-size: 26px; font-weight: 800; color: var(--navy); }
    .greeting p { color: var(--muted); font-size: 14px; margin-top: 4px; }

    /* ── PANEL PLAT NOMOR ── */
    .plat-panel {
      background: var(--white);
      border-radius: var(--radius);
      padding: 20px 24px;
      margin-bottom: 28px;
      box-shadow: var(--shadow);
    }

    .plat-panel-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      margin-bottom: 14px;
      flex-wrap: wrap;
      gap: 8px;
    }

    .plat-panel-title {
      font-size: 14px;
      font-weight: 800;
      color: var(--navy);
    }

    .plat-panel-sub {
      font-size: 12px;
      color: var(--muted);
      margin-bottom: 14px;
    }

    .plat-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 16px;
    }

    .plat-chip {
      display: inline-flex;
      align-items: center;
      gap: 7px;
      padding: 6px 14px;
      border-radius: 50px;
      font-size: 13px;
      font-weight: 800;
      letter-spacing: .05em;
      border: 6px solid var(--cyan);
      color: var(--navy);
      background: rgba(0,200,224,.08);
    }

    .plat-chip.utama {
      background: var(--cyan);
      color: var(--navy);
    }

    .plat-chip-label {
      font-size: 10px;
      font-weight: 700;
      background: var(--navy);
      color: var(--cyan);
      border-radius: 20px;
      padding: 1px 7px;
    }

    .btn-hapus-plat {
      background: none;
      border: none;
      color: #ef4444;
      cursor: pointer;
      font-size: 14px;
      padding: 0;
      line-height: 1;
    }

    .btn-hapus-plat:hover { opacity: .7; }

    /* Form tambah plat */
    .tambah-plat-wrap {
      display: flex;
      gap: 8px;
      align-items: center;
      flex-wrap: wrap;
    }

    .tambah-plat-wrap input[type="text"] {
      padding: 8px 14px;
      border-radius: 8px;
      border: 1.5px solid #d1d8e0;
      font-size: 13px;
      font-family: 'Nunito', sans-serif;
      font-weight: 700;
      letter-spacing: .05em;
      text-transform: uppercase;
      width: 170px;
      outline: none;
      transition: border .2s;
    }

    .tambah-plat-wrap input[type="text"]:focus { border-color: var(--cyan); }

    .btn-tambah-plat {
      padding: 8px 16px;
      border-radius: 8px;
      background: var(--navy);
      color: #fff;
      font-size: 13px;
      font-weight: 800;
      border: none;
      cursor: pointer;
      transition: .2s;
    }

    .btn-tambah-plat:hover { background: #1a2f4a; }

    /* ── STATUS CARD ── */
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

    .sc-meta { display: flex; gap: 24px; flex-wrap: wrap; }
    .sc-meta-item .key { font-size: 11px; color: rgba(255,255,255,.45); }
    .sc-meta-item .val { font-size: 15px; font-weight: 700; margin-top: 2px; }

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

    .progress-steps { display: flex; justify-content: space-between; gap: 12px; }

    .step { flex: 1; text-align: center; position: relative; }

    .step::after {
      content: '';
      position: absolute;
      top: 14px; right: -50%;
      width: 100%; height: 3px;
      background: rgba(255,255,255,.12);
      z-index: 0;
    }

    .step:last-child::after { display: none; }

    .step-circle {
      width: 28px; height: 28px;
      border-radius: 50%;
      margin: 0 auto 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 12px; font-weight: 800;
      background: rgba(255,255,255,.12);
      color: #fff;
      position: relative; z-index: 1;
    }

    .step.active .step-circle { background: var(--cyan); color: var(--navy); }
    .step-label { font-size: 11px; color: rgba(255,255,255,.75); }

    .action-wrap { margin-top: 18px; }

    .btn-cancel {
      border: none; background: #ef4444; color: #fff;
      padding: 7px 14px; border-radius: 8px;
      font-size: 12px; font-weight: 700;
      cursor: pointer; margin-top: 6px;
    }
    .btn-cancel:hover { opacity: .9; }

    /* ── NO BOOKING ── */
    .no-booking {
      background: var(--white); border-radius: var(--radius);
      padding: 40px; text-align: center;
      margin-bottom: 28px; box-shadow: var(--shadow);
    }
    .no-booking .nb-icon { font-size: 48px; margin-bottom: 12px; }
    .no-booking h3 { font-size: 18px; font-weight: 800; color: var(--navy); margin-bottom: 6px; }
    .no-booking p { color: var(--muted); font-size: 14px; margin-bottom: 20px; }

    /* ── HISTORY ── */
    .section-title { font-size: 16px; font-weight: 800; color: var(--navy); margin-bottom: 14px; }

    .history-list { display: flex; flex-direction: column; gap: 12px; }

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

    .hi-left .hi-paket { font-size: 15px; font-weight: 800; color: var(--navy); }
    .hi-left .hi-meta { font-size: 12px; color: var(--muted); margin-top: 4px; }

    .hi-plat-badge {
      display: inline-block;
      background: rgba(0,200,224,.12);
      border: 1px solid var(--cyan);
      color: var(--navy);
      font-size: 11px;
      font-weight: 800;
      padding: 2px 9px;
      border-radius: 20px;
      margin-top: 5px;
      letter-spacing: .04em;
    }

    .hi-right { text-align: right; }
    .hi-harga { font-size: 14px; font-weight: 800; color: var(--navy); }
    .hi-badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 50px;
      font-size: 11px; font-weight: 700;
      margin-top: 5px;
    }

    .empty-history {
      color: var(--muted); font-size: 14px; text-align: center;
      padding: 24px; background: var(--white); border-radius: var(--radius);
    }

    /* ── FILTER PLAT ── */
    .filter-plat-wrap {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-bottom: 16px;
      align-items: center;
    }

    .filter-plat-wrap span {
      font-size: 13px;
      color: var(--muted);
      font-weight: 700;
    }

    .filter-btn {
      padding: 5px 14px;
      border-radius: 50px;
      font-size: 12px;
      font-weight: 800;
      border: 1.5px solid #d1d8e0;
      background: transparent;
      cursor: pointer;
      transition: .15s;
      letter-spacing: .04em;
    }

    .filter-btn:hover, .filter-btn.aktif {
      border-color: var(--cyan);
      background: rgba(0,200,224,.12);
      color: var(--navy);
    }

    @media (max-width: 600px) {
      header { padding: 0 20px; }
      .container { margin: 20px auto; }
      .status-card { padding: 20px; }
      .history-item { flex-direction: column; align-items: flex-start; }
      .hi-right { text-align: left; }
      .progress-steps { flex-direction: column; }
      .step::after { display: none; }
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
    <h3>Jangan Lupa datang tepat waktu ya🤗</h3>
    <p>No. Telepon: <?= htmlspecialchars($no_hp) ?> &nbsp;·&nbsp;
       <?= count($plat_list) ?> plat terdaftar
    </p>
  </div>

  <!-- PANEL PLAT NOMOR -->
  <div class="plat-panel">
    <div class="plat-panel-header">
      <div class="plat-panel-title">🚗 Kendaraan Terdaftar</div>
    </div>
    <div class="plat-panel-sub">
      Semua plat nomor di bawah ini berada dalam satu profil. Booking baru dari profil ini bisa menggunakan plat mana saja.
    </div>

    <!-- Chip plat -->
    <div class="plat-chips">
      <?php foreach ($plat_list as $idx => $plat_item): ?>
        <div class="plat-chip <?= $idx === 0 ? 'utama' : '' ?>">
          <?= htmlspecialchars(strtoupper($plat_item)) ?>
          <?php if ($idx === 0): ?>
            <span class="plat-chip-label">Utama</span>
          <?php else: ?>
            <form method="POST" style="display:inline;"
                  onsubmit="return confirm('Hapus plat <?= htmlspecialchars($plat_item) ?> dari profil ini?')">
              <input type="hidden" name="plat_hapus" value="<?= htmlspecialchars($plat_item) ?>">
              <button type="submit" name="hapus_plat" class="btn-hapus-plat" title="Hapus plat ini">✕</button>
            </form>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Form tambah plat -->
    <form method="POST" class="tambah-plat-wrap">
      <input type="text" name="plat_baru" placeholder="Contoh: B 1234 XY"
             style="text-transform:uppercase" maxlength="12" required>
      <button type="submit" name="tambah_plat" class="btn-tambah-plat">+ Tambah Plat</button>
    </form>
  </div>

  <!-- BOOKING AKTIF -->
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
          <?php if ($booking_aktif['status'] === 'pending'): ?>
            <form method="POST" onsubmit="return confirm('Yakin ingin membatalkan booking ini? Tindakan ini tidak bisa dibatalkan.')">
              <input type="hidden" name="id_pemesanan" value="<?= $booking_aktif['id_pemesanan'] ?>">
              <button type="submit" name="batalkan_booking" class="btn-cancel">Batalkan Booking</button>
            </form>
          <?php else: ?>
            <button class="btn-cancel" onclick="bukaModalRefund(<?= $booking_aktif['id_pemesanan'] ?>, '<?= number_format($booking_aktif['harga'],0,',','.') ?>')">
              Batalkan &amp; Ajukan Refund
            </button>
          <?php endif; ?>
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

  <!-- RIWAYAT BOOKING (semua plat) -->
  <div class="section-title">Riwayat Booking</div>

  <!-- Filter per plat -->
  <?php if (count($plat_list) > 1): ?>
    <div class="filter-plat-wrap">
      <span>Filter:</span>
      <button class="filter-btn aktif" onclick="filterPlat('semua', this)">Semua</button>
      <?php foreach ($plat_list as $plat_item): ?>
        <button class="filter-btn" onclick="filterPlat('<?= htmlspecialchars(strtoupper($plat_item)) ?>', this)">
          <?= htmlspecialchars(strtoupper($plat_item)) ?>
        </button>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <div class="history-list" id="historyList">
    <?php if (empty($semua)): ?>
      <div class="empty-history">Belum ada riwayat booking.</div>
    <?php else: ?>
      <?php foreach ($semua as $item):
        $si = statusInfo($item['status']);
        $plat_item_upper = strtoupper(str_replace(' ', '', $item['plat_mobil']));
      ?>
        <div class="history-item" data-plat="<?= htmlspecialchars($plat_item_upper) ?>">
          <div class="hi-left">
            <div class="hi-paket"><?= htmlspecialchars($item['nama_paket']) ?></div>
            <div class="hi-meta">
              <?= date('d M Y', strtotime($item['tanggal'])) ?> &nbsp;·&nbsp;
              <?= htmlspecialchars($item['jam']) ?>
            </div>
            <span class="hi-plat-badge"><?= htmlspecialchars(strtoupper($item['plat_mobil'])) ?></span>

            <?php if (
              $item['status'] === 'pending' ||
              ($item['status'] === 'lunas' && $item['status_cuci'] === 'belum_dicuci')
            ): ?>
              <?php if ($item['status'] === 'pending'): ?>
                <form method="POST" onsubmit="return confirm('Yakin ingin membatalkan booking ini?')" style="margin-top:6px;">
                  <input type="hidden" name="id_pemesanan" value="<?= $item['id_pemesanan'] ?>">
                  <button type="submit" name="batalkan_booking" class="btn-cancel">Batalkan</button>
                </form>
              <?php else: ?>
                <button class="btn-cancel" style="margin-top:6px;"
                  onclick="bukaModalRefund(<?= $item['id_pemesanan'] ?>, '<?= number_format($item['harga'],0,',','.') ?>')">
                  Batalkan &amp; Refund
                </button>
              <?php endif; ?>
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

<script>
function filterPlat(plat, btn) {
  // Update tombol aktif
  document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('aktif'));
  btn.classList.add('aktif');

  // Filter kartu
  document.querySelectorAll('#historyList .history-item').forEach(item => {
    if (plat === 'semua' || item.dataset.plat === plat.replace(/\s/g,'')) {
      item.style.display = 'flex';
    } else {
      item.style.display = 'none';
    }
  });
}
</script>


<!-- ============================================================
     MODAL FORM REFUND
     ============================================================ -->
<div id="overlayRefund" onclick="tutupModalRefund()"
  style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:998;backdrop-filter:blur(3px);"></div>

<div id="modalRefund"
  style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
         z-index:999;width:90%;max-width:440px;background:#fff;border-radius:20px;
         box-shadow:0 24px 64px rgba(0,0,0,0.22);overflow:hidden;">

  <!-- Header -->
  <div style="background:#0f1b2d;padding:22px 24px 18px;position:relative;">
    <div style="font-size:22px;margin-bottom:6px;">💸</div>
    <h3 style="color:#fff;font-size:16px;font-weight:800;margin:0;">Ajukan Pengembalian Dana</h3>
    <p style="color:rgba(255,255,255,.55);font-size:12px;margin-top:4px;">
      Total refund: <strong id="refundNominal" style="color:#e8a020;"></strong>
    </p>
    <button onclick="tutupModalRefund()"
      style="position:absolute;top:16px;right:16px;background:rgba(255,255,255,.1);
             border:none;color:#fff;width:28px;height:28px;border-radius:50%;
             font-size:16px;cursor:pointer;line-height:1;">✕</button>
  </div>

  <!-- Info -->
  <div style="background:#fef3c7;border-left:4px solid #f59e0b;padding:12px 16px;font-size:12px;color:#92400e;line-height:1.5;">
    ⚠️ Setelah pembatalan, admin akan memproses transfer refund ke rekening yang Anda isi di bawah ini.
  </div>

  <!-- Form -->
  <form method="POST" style="padding:20px 24px 24px;" onsubmit="return validasiRefund()">
    <input type="hidden" name="id_pemesanan" id="refundIdPemesanan">

    <div style="margin-bottom:14px;">
      <label style="display:block;font-size:12px;font-weight:800;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">
        Nama Pemilik Rekening
      </label>
      <input type="text" name="refund_nama_rek" id="refundNamaRek" placeholder="Contoh: Budi Santoso"
        style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;
               font-size:14px;outline:none;transition:.15s;font-family:inherit;"
        onfocus="this.style.borderColor='#0f1b2d'"
        onblur="this.style.borderColor='#e5e7eb'">
    </div>

    <div style="margin-bottom:14px;">
      <label style="display:block;font-size:12px;font-weight:800;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">
        Nama Bank / E-Wallet
      </label>
      <select name="refund_bank" id="refundBank"
        style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;
               font-size:14px;outline:none;background:#fff;font-family:inherit;cursor:pointer;
               transition:.15s;"
        onfocus="this.style.borderColor='#0f1b2d'"
        onblur="this.style.borderColor='#e5e7eb'">
        <option value="">-- Pilih Bank / E-Wallet --</option>
        <optgroup label="Bank">
          <option>BCA</option>
          <option>BNI</option>
          <option>BRI</option>
          <option>Mandiri</option>
          <option>CIMB Niaga</option>
          <option>BSI</option>
          <option>Bank Lainnya</option>
        </optgroup>
        <optgroup label="E-Wallet">
          <option>GoPay</option>
          <option>OVO</option>
          <option>Dana</option>
          <option>ShopeePay</option>
        </optgroup>
      </select>
    </div>

    <div style="margin-bottom:20px;">
      <label style="display:block;font-size:12px;font-weight:800;color:#374151;margin-bottom:6px;text-transform:uppercase;letter-spacing:.5px;">
        Nomor Rekening / E-Wallet
      </label>
      <input type="text" name="refund_nomor_rek" id="refundNomorRek" placeholder="Contoh: 1234567890"
        inputmode="numeric"
        style="width:100%;padding:10px 14px;border:1.5px solid #e5e7eb;border-radius:10px;
               font-size:14px;outline:none;transition:.15s;font-family:inherit;"
        onfocus="this.style.borderColor='#0f1b2d'"
        onblur="this.style.borderColor='#e5e7eb'">
    </div>

    <button type="submit" name="ajukan_refund"
      style="width:100%;padding:13px;border:none;border-radius:12px;
             background:#ef4444;color:#fff;font-size:14px;font-weight:800;
             cursor:pointer;letter-spacing:.5px;transition:.18s;"
      onmouseover="this.style.background='#dc2626'"
      onmouseout="this.style.background='#ef4444'">
      Batalkan Booking &amp; Ajukan Refund
    </button>
    <button type="button" onclick="tutupModalRefund()"
      style="width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:12px;
             background:#fff;color:#6b7280;font-size:13px;font-weight:700;
             cursor:pointer;margin-top:8px;transition:.18s;"
      onmouseover="this.style.borderColor='#374151';this.style.color='#374151'"
      onmouseout="this.style.borderColor='#e5e7eb';this.style.color='#6b7280'">
      Batal
    </button>
  </form>
</div>

<script>
function bukaModalRefund(idPemesanan, nominal) {
  document.getElementById('refundIdPemesanan').value = idPemesanan;
  document.getElementById('refundNominal').textContent = 'Rp ' + nominal;
  document.getElementById('refundNamaRek').value = '';
  document.getElementById('refundNomorRek').value = '';
  document.getElementById('refundBank').value = '';
  document.getElementById('overlayRefund').style.display = 'block';
  document.getElementById('modalRefund').style.display = 'block';
  document.body.style.overflow = 'hidden';
}
function tutupModalRefund() {
  document.getElementById('overlayRefund').style.display = 'none';
  document.getElementById('modalRefund').style.display = 'none';
  document.body.style.overflow = '';
}
function validasiRefund() {
  const nama  = document.getElementById('refundNamaRek').value.trim();
  const bank  = document.getElementById('refundBank').value;
  const nomor = document.getElementById('refundNomorRek').value.trim();
  if (!nama || !bank || !nomor) {
    alert('Lengkapi semua data rekening terlebih dahulu.');
    return false;
  }
  return confirm('Yakin ingin membatalkan booking dan mengajukan refund ke ' + bank + ' ' + nomor + ' a/n ' + nama + '?');
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') tutupModalRefund(); });
</script>

</body>
</html>