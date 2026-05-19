<?php
include 'koneksi.php';

// Ambil semua paket dari database
$q_paket = mysqli_query($conn, "SELECT * FROM paket_layanan ORDER BY harga ASC");
$paket_list = [];
while ($row = mysqli_fetch_assoc($q_paket)) {
    $paket_list[] = $row;
}

// -------------------------------------------------------
// Data detail: alat & produk per nama paket
// Jika nama paket tidak cocok persis, fallback ke default
// -------------------------------------------------------
function getDetailLayanan($nama_paket, $harga) {
    $nama = strtolower($nama_paket);

    // Tentukan kategori berdasarkan harga & nama
    if (str_contains($nama, 'mesin') || str_contains($nama, 'engine')) {
        return [
            'icon'    => '⚙️',
            'kategori'=> 'Engine',
            'deskripsi'=> 'Pembersihan ruang mesin secara menyeluruh untuk menjaga performa optimal dan mencegah korosi.',
            'alat'    => ['Steam cleaner bertekanan tinggi', 'Kuas detailing mesin', 'Microfiber khusus mesin', 'Kompresor angin'],
            'produk'  => ['Engine degreaser (penghilang oli & kotoran)', 'Engine shine (pelindung karet & plastik)', 'Anti-karat mesin'],
            'durasi'  => '30–45 menit',
            'badge'   => 'Engine Care',
            'warna'   => '#f59e0b',
            'warna_bg'=> '#fef3c7',
        ];
    } elseif (str_contains($nama, 'wax butter') || str_contains($nama, 'poles')) {
        return [
            'icon'    => '✨',
            'kategori'=> 'Polish',
            'deskripsi'=> 'Pemolesan body luar kendaraan menggunakan wax butter premium untuk mengangkat baret halus dan mengembalikan kilap cat.',
            'alat'    => ['Mesin polisher rotary', 'Pad poles busa', 'Microfiber lap finishing', 'Lampu inspeksi'],
            'produk'  => ['Wax butter premium', 'Pre-wax cleaner', 'Carnauba wax sealant'],
            'durasi'  => '45–60 menit',
            'badge'   => 'Polish',
            'warna'   => '#8b5cf6',
            'warna_bg'=> '#ede9fe',
        ];
    } elseif (str_contains($nama, 'wax spray')) {
        return [
            'icon'    => '💨',
            'kategori'=> 'Spray Wax',
            'deskripsi'=> 'Aplikasi wax spray cepat untuk proteksi body dan kilap instan tanpa proses poles manual.',
            'alat'    => ['Spray gun bertekanan', 'Microfiber applicator', 'Lap buffing premium'],
            'produk'  => ['Quick detailer spray wax', 'Hydrophobic coat rinse', 'UV protectant spray'],
            'durasi'  => '20–30 menit',
            'badge'   => 'Quick Wax',
            'warna'   => '#06b6d4',
            'warna_bg'=> '#e0f2fe',
        ];
    } elseif (str_contains($nama, 'foging') || str_contains($nama, 'fogging')) {
        return [
            'icon'    => '🌫️',
            'kategori'=> 'Interior Sanitasi',
            'deskripsi'=> 'Proses fogging interior menggunakan cairan disinfektan khusus untuk membunuh kuman, bakteri, dan menghilangkan bau tidak sedap.',
            'alat'    => ['Mesin fogging ULV', 'Nozzle atomizer fine mist', 'Masker & APD operator'],
            'produk'  => ['Disinfektan fogging grade otomotif', 'Air freshener enzyme base', 'Anti-bacterial fog solution'],
            'durasi'  => '20–30 menit (+ 15 menit diam setelah fogging)',
            'badge'   => 'Sanitasi',
            'warna'   => '#10b981',
            'warna_bg'=> '#d1fae5',
        ];
    } elseif (str_contains($nama, 'premium') || str_contains($nama, 'gantenk') || $harga >= 250000) {
        return [
            'icon'    => '💎',
            'kategori'=> 'Premium Full Detail',
            'deskripsi'=> 'Perawatan lengkap eksterior & interior secara menyeluruh: cuci body, fogging, poles wax, cuci mesin, anti-karat, jamur kaca, dan interior cleaning.',
            'alat'    => ['Pressure washer 150 bar', 'Mesin polisher dual-action', 'Mesin fogging ULV', 'Steam cleaner', 'Wet & dry vacuum cleaner', 'Lampu inspeksi LED', 'Kuas detailing set lengkap'],
            'produk'  => ['Car shampoo pH neutral', 'Wax butter premium', 'Engine degreaser', 'Anti-karat undercoat', 'Glass cleaner anti-jamur', 'Interior cleaner kulit/fabric', 'Disinfektan fogging', 'Tire shine gel'],
            'durasi'  => '2–3 jam',
            'badge'   => 'All-In-One',
            'warna'   => '#7c3aed',
            'warna_bg'=> '#ede9fe',
        ];
    } elseif (str_contains($nama, 'firsdate') || str_contains($nama, 'first date')) {
        return [
            'icon'    => '🔥',
            'kategori'=> 'Standard Plus',
            'deskripsi'=> 'Kombinasi cuci luar-dalam, fogging interior, wax spray body, dan cuci mesin. Hasil bersih menyeluruh siap tampil.',
            'alat'    => ['Pressure washer 120 bar', 'Vacuum cleaner interior', 'Spray gun wax', 'Mesin fogging', 'Kuas detail'],
            'produk'  => ['Car shampoo premium', 'Interior all-purpose cleaner', 'Wax spray', 'Engine degreaser', 'Air freshener enzyme'],
            'durasi'  => '75–90 menit',
            'badge'   => 'Best Seller',
            'warna'   => '#ef4444',
            'warna_bg'=> '#fee2e2',
        ];
    } elseif (str_contains($nama, 'dalam') || str_contains($nama, 'interior')) {
        return [
            'icon'    => '🛋️',
            'kategori'=> 'Interior',
            'deskripsi'=> 'Pembersihan menyeluruh bagian dalam kabin: dashboard, jok, karpet, plafon, dan seluruh sudut interior.',
            'alat'    => ['Wet & dry vacuum cleaner', 'Steamer interior', 'Kuas detailing halus', 'Microfiber interior grade'],
            'produk'  => ['Interior all-purpose cleaner', 'Dashboard protectant', 'Fabric cleaner jok', 'Carpet foam cleaner', 'Glass cleaner'],
            'durasi'  => '60–75 menit',
            'badge'   => 'Interior',
            'warna'   => '#0066cc',
            'warna_bg'=> '#dbeafe',
        ];
    } elseif (str_contains($nama, 'luar') || str_contains($nama, 'eksterior') || str_contains($nama, 'exterior')) {
        return [
            'icon'    => '🚿',
            'kategori'=> 'Eksterior',
            'deskripsi'=> 'Cuci eksterior body kendaraan menggunakan shampoo pH neutral dan tekanan air optimal agar cat terlindungi.',
            'alat'    => ['Pressure washer 120 bar', 'Foam cannon', 'Microfiber wash mitt', 'Grit guard bucket', 'Lap pengering chamois'],
            'produk'  => ['Car shampoo pH neutral', 'Snow foam pre-wash', 'Rinse aid drying spray', 'Tire shine gel', 'Glass cleaner'],
            'durasi'  => '30–45 menit',
            'badge'   => 'Eksterior',
            'warna'   => '#0284c7',
            'warna_bg'=> '#e0f2fe',
        ];
    } else {
        // Cuci Umum / General Wash
        return [
            'icon'    => '🚗',
            'kategori'=> 'Cuci Umum',
            'deskripsi'=> 'Layanan cuci standar luar dan dalam, cocok untuk perawatan rutin kendaraan sehari-hari.',
            'alat'    => ['Pressure washer 100 bar', 'Foam cannon', 'Microfiber wash mitt', 'Vacuum cleaner', 'Lap pengering chamois'],
            'produk'  => ['Car shampoo pH neutral', 'Snow foam', 'Interior deodorizer', 'Tire dressing'],
            'durasi'  => '45–60 menit',
            'badge'   => 'Rutin',
            'warna'   => '#0f1b2d',
            'warna_bg'=> '#f3f4f6',
        ];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Layanan Kami – Habibi Garage</title>
  <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Barlow:wght@400;500;600;700&family=Barlow+Condensed:wght@700;800&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    :root {
      --navy: #0f1b2d;
      --accent: #e8a020;
      --bg: #f2f4f8;
      --white: #ffffff;
      --text: #1c2b3a;
      --muted: #6b7280;
      --border: #e2e8f0;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body {
      font-family: 'Nunito', sans-serif;
      background: var(--bg);
      color: var(--text);
      min-height: 100vh;
    }

    /* ── HEADER ── */
    header {
      background: var(--navy);
      padding: 0 48px;
      height: 64px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 100;
      box-shadow: 0 2px 16px rgba(0,0,0,0.22);
    }
    header .logo { height: 40px; }
    header nav a {
      color: rgba(255,255,255,.7);
      text-decoration: none;
      font-size: 14px;
      font-weight: 700;
      padding: 8px 14px;
      border-radius: 8px;
      transition: .18s;
    }
    header nav a:hover { color: #fff; background: rgba(255,255,255,.1); }
    header nav a.back-btn {
      background: rgba(255,255,255,.1);
      color: #fff;
      border: 1px solid rgba(255,255,255,.2);
    }
    header nav a.back-btn:hover { background: var(--accent); color: var(--navy); }

    /* ── HERO BANNER ── */
    .hero-banner {
      background: linear-gradient(135deg, #0f1b2d 0%, #1a3050 60%, #0d2540 100%);
      padding: 64px 48px 56px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
    .hero-banner::before {
      content: '';
      position: absolute;
      inset: 0;
      background: radial-gradient(ellipse at 70% 50%, rgba(232,160,32,.12) 0%, transparent 70%);
    }
    .hero-banner h1 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: clamp(32px, 5vw, 52px);
      font-weight: 800;
      color: #fff;
      letter-spacing: 1px;
      position: relative;
    }
    .hero-banner h1 span { color: var(--accent); }
    .hero-banner p {
      color: rgba(255,255,255,.65);
      font-size: 15px;
      margin-top: 14px;
      max-width: 520px;
      margin-left: auto;
      margin-right: auto;
      line-height: 1.65;
      position: relative;
    }
    .hero-badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(232,160,32,.15);
      border: 1px solid rgba(232,160,32,.35);
      color: var(--accent);
      font-size: 12px;
      font-weight: 800;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 6px 16px;
      border-radius: 40px;
      margin-bottom: 20px;
      position: relative;
    }

    /* ── MAIN CONTENT ── */
    .main {
      max-width: 1200px;
      margin: 0 auto;
      padding: 52px 32px 80px;
    }

    .section-label {
      font-family: 'Bebas Neue', sans-serif;
      font-size: 13px;
      letter-spacing: 3px;
      color: var(--muted);
      text-transform: uppercase;
      margin-bottom: 8px;
    }
    .section-title {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 30px;
      font-weight: 800;
      color: var(--navy);
      margin-bottom: 36px;
      padding-left: 16px;
      border-left: 4px solid var(--accent);
      line-height: 1.2;
    }

    /* ── GRID LAYANAN ── */
    .layanan-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
      gap: 28px;
    }

    .layanan-card {
      background: var(--white);
      border-radius: 20px;
      border: 1.5px solid var(--border);
      overflow: hidden;
      transition: transform .2s, box-shadow .2s, border-color .2s;
      display: flex;
      flex-direction: column;
    }
    .layanan-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 40px rgba(15,27,45,.1);
      border-color: #c8d4e8;
    }

    /* Card header dengan warna dinamis */
    .card-head {
      padding: 22px 24px 18px;
      display: flex;
      align-items: flex-start;
      gap: 16px;
      position: relative;
    }
    .card-icon-wrap {
      width: 52px;
      height: 52px;
      border-radius: 14px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 24px;
      flex-shrink: 0;
    }
    .card-head-info { flex: 1; }
    .card-head-info h3 {
      font-size: 17px;
      font-weight: 800;
      color: var(--navy);
      line-height: 1.3;
      margin-bottom: 4px;
    }
    .card-kategori {
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 1px;
      text-transform: uppercase;
      padding: 3px 10px;
      border-radius: 20px;
      display: inline-block;
    }
    .card-badge-price {
      position: absolute;
      top: 18px;
      right: 20px;
      font-size: 15px;
      font-weight: 800;
      color: var(--navy);
    }

    /* Divider */
    .card-divider {
      height: 1px;
      background: var(--border);
      margin: 0 24px;
    }

    /* Card body */
    .card-body {
      padding: 18px 24px 22px;
      flex: 1;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .card-desc {
      font-size: 13px;
      color: var(--muted);
      line-height: 1.65;
    }

    /* Detail section */
    .detail-section { }
    .detail-section h4 {
      font-size: 11px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1.2px;
      color: var(--muted);
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .detail-section h4::after {
      content: '';
      flex: 1;
      height: 1px;
      background: var(--border);
    }
    .tag-list {
      display: flex;
      flex-wrap: wrap;
      gap: 6px;
    }
    .tag {
      font-size: 12px;
      font-weight: 700;
      padding: 5px 12px;
      border-radius: 8px;
      background: #f8fafc;
      border: 1px solid var(--border);
      color: var(--text);
      line-height: 1.3;
    }

    /* Durasi footer */
    .card-footer {
      padding: 12px 24px;
      background: #f8fafc;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 12px;
      color: var(--muted);
      font-weight: 700;
    }
    .card-footer span { color: var(--navy); }

    /* ── INFO BANNER ── */
    .info-banner {
      background: linear-gradient(135deg, #0f1b2d, #1a3050);
      border-radius: 20px;
      padding: 32px 36px;
      display: flex;
      align-items: center;
      gap: 24px;
      margin-bottom: 48px;
    }
    .info-banner-icon {
      font-size: 40px;
      flex-shrink: 0;
    }
    .info-banner h3 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 20px;
      font-weight: 800;
      color: #fff;
      margin-bottom: 6px;
    }
    .info-banner p {
      font-size: 13px;
      color: rgba(255,255,255,.65);
      line-height: 1.55;
    }
    .info-banner a {
      margin-left: auto;
      flex-shrink: 0;
      background: var(--accent);
      color: var(--navy);
      font-size: 13px;
      font-weight: 800;
      padding: 12px 24px;
      border-radius: 10px;
      text-decoration: none;
      white-space: nowrap;
      transition: .18s;
    }
    .info-banner a:hover { background: #f5c842; }

    /* ── EMPTY STATE ── */
    .empty {
      text-align: center;
      padding: 80px 24px;
      color: var(--muted);
    }
    .empty .empty-icon { font-size: 56px; margin-bottom: 16px; }
    .empty h3 { font-size: 20px; font-weight: 800; color: var(--navy); margin-bottom: 8px; }

    @media (max-width: 768px) {
      header { padding: 0 20px; }
      .hero-banner { padding: 48px 24px 40px; }
      .main { padding: 36px 16px 60px; }
      .layanan-grid { grid-template-columns: 1fr; }
      .info-banner { flex-direction: column; text-align: center; }
      .info-banner a { margin-left: 0; }
    }
  </style>
</head>
<body>

<!-- HEADER -->
<header>
  <img src="../img/logo.png" alt="Habibi Garage" class="logo">
  <nav style="display:flex;gap:8px;align-items:center;">
    <a href="landing_page.php">Home</a>
    <a href="jadwal.php">Jadwal</a>
    <a href="landing_page.php" class="back-btn">← Kembali</a>
  </nav>
</header>

<!-- HERO BANNER -->
<div class="hero-banner">
  <div class="hero-badge">🔧 Detail Layanan</div>
  <h1>Proses <span>Cepat</span> &amp; <span>Aman</span></h1>
  <p>Dikerjakan oleh tenaga profesional menggunakan peralatan dan produk terbaik yang aman untuk kendaraan Anda.</p>
</div>

<!-- MAIN -->
<div class="main">

  <!-- Info booking banner -->
  <div class="info-banner">
    <div class="info-banner-icon">🗓️</div>
    <div>
      <h3>Tertarik dengan layanan kami?</h3>
      <p>Buat booking sekarang melalui menu Service dan pilih paket yang sesuai kebutuhan Anda.</p>
    </div>
    <a href="menu.php">Booking Sekarang →</a>
  </div>

  <div class="section-label">Semua Paket</div>
  <div class="section-title">Detail Layanan & Produk yang Digunakan</div>

  <?php if (empty($paket_list)): ?>
    <div class="empty">
      <div class="empty-icon">🔧</div>
      <h3>Belum ada layanan</h3>
      <p>Layanan sedang dipersiapkan. Silakan cek kembali nanti.</p>
    </div>
  <?php else: ?>

  <div class="layanan-grid">
    <?php foreach ($paket_list as $paket):
      $detail = getDetailLayanan($paket['nama_paket'], (float)$paket['harga']);
    ?>
    <div class="layanan-card">

      <!-- Card Head -->
      <div class="card-head" style="background: <?= $detail['warna_bg'] ?>;">
        <div class="card-icon-wrap" style="background:<?= $detail['warna'] ?>22;">
          <?= $detail['icon'] ?>
        </div>
        <div class="card-head-info">
          <h3><?= htmlspecialchars($paket['nama_paket']) ?></h3>
          <span class="card-kategori" style="background:<?= $detail['warna'] ?>22;color:<?= $detail['warna'] ?>;">
            <?= $detail['badge'] ?>
          </span>
        </div>
        <div class="card-badge-price">
          Rp <?= number_format($paket['harga'], 0, ',', '.') ?>
        </div>
      </div>

      <div class="card-divider"></div>

      <!-- Card Body -->
      <div class="card-body">

        <!-- Deskripsi dari DB jika ada, fallback ke generated -->
        <p class="card-desc">
          <?= !empty($paket['deskripsi'])
              ? htmlspecialchars($paket['deskripsi'])
              : $detail['deskripsi'] ?>
        </p>

        <!-- Alat yang digunakan -->
        <div class="detail-section">
          <h4>🔧 Alat yang Digunakan</h4>
          <div class="tag-list">
            <?php foreach ($detail['alat'] as $alat): ?>
              <span class="tag"><?= htmlspecialchars($alat) ?></span>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Produk yang digunakan -->
        <div class="detail-section">
          <h4>🧴 Produk yang Digunakan</h4>
          <div class="tag-list">
            <?php foreach ($detail['produk'] as $produk): ?>
              <span class="tag" style="border-color:<?= $detail['warna'] ?>44;color:<?= $detail['warna'] ?>;">
                <?= htmlspecialchars($produk) ?>
              </span>
            <?php endforeach; ?>
          </div>
        </div>

      </div>

      <!-- Card Footer: durasi -->
      <div class="card-footer">
        ⏱️ Estimasi waktu: <span><?= $detail['durasi'] ?></span>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <?php endif; ?>

</div>

</body>
</html>