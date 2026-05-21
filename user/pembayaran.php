<?php
include 'koneksi.php';
require_once '../config/cloudinary.php';

use Cloudinary\Api\Upload\UploadApi;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id_pemesanan']) || empty($_POST['id_pemesanan'])) die("ID pemesanan tidak valid.");
    $id_pemesanan = mysqli_real_escape_string($conn, $_POST['id_pemesanan']);
    $cek = mysqli_query($conn, "SELECT status FROM pemesanan WHERE id_pemesanan = '$id_pemesanan'");
    $dataCek = mysqli_fetch_assoc($cek);
    if (!$dataCek) die("Data pemesanan tidak ditemukan.");
    if (!isset($_FILES['bukti_bayar']) || $_FILES['bukti_bayar']['error'] !== 0) die("Upload bukti pembayaran gagal.");
    $file = $_FILES['bukti_bayar'];
    $tmpFile = $file['tmp_name'];
    $sizeFile = $file['size'];
    $extValid = ['jpg', 'jpeg', 'png', 'webp'];
    $extFile = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extFile, $extValid)) die("Format file tidak valid.");
    if ($sizeFile > 2 * 1024 * 1024) die("Ukuran file terlalu besar (max 2MB).");
    try {
        $upload = (new UploadApi())->upload($tmpFile, ['folder' => 'habibi_garage/bukti_transfer']);
        $urlBukti = $upload['secure_url'];
    } catch (Exception $e) { die("Upload gagal: " . $e->getMessage()); }
    // Ambil data rekening refund (opsional, hanya tersimpan jika diisi)
    $refund_nama_rek  = mysqli_real_escape_string($conn, trim($_POST['refund_nama_rek']  ?? ''));
    $refund_nomor_rek = mysqli_real_escape_string($conn, trim($_POST['refund_nomor_rek'] ?? ''));
    $refund_bank      = mysqli_real_escape_string($conn, trim($_POST['refund_bank']      ?? ''));

    mysqli_query($conn, "UPDATE pemesanan
        SET bukti_bayar = '$urlBukti',
            status = 'pending',
            refund_nama_rek  = IF('$refund_nama_rek'  != '', '$refund_nama_rek',  refund_nama_rek),
            refund_nomor_rek = IF('$refund_nomor_rek' != '', '$refund_nomor_rek', refund_nomor_rek),
            refund_bank      = IF('$refund_bank'      != '', '$refund_bank',      refund_bank)
        WHERE id_pemesanan = '$id_pemesanan'");
    echo "<script>alert('Bukti berhasil dikirim!'); window.location.href='pending.php?id_pemesanan=$id_pemesanan';</script>";
    exit;
}

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_pemesanan = mysqli_real_escape_string($conn, $_GET['id']);
    $sql = "SELECT p.*, l.nama_paket, l.harga FROM pemesanan p JOIN paket_layanan l ON p.id_paket = l.id_paket WHERE p.id_pemesanan = '$id_pemesanan'";
    $query = mysqli_query($conn, $sql);
    $data = mysqli_fetch_assoc($query);
    if (!$data) die("Data pesanan tidak ditemukan.");
} else { die("Akses tidak valid."); }
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran — Habibi Garage</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --blue: #4f6ef7;
            --blue-light: #6b85f8;
            --blue-dim: #eef1fe;
            --sidebar: #1a2236;
            --bg: #f1f4f9;
            --white: #ffffff;
            --text: #1a2236;
            --text-muted: #6b7280;
            --text-light: #9ca3af;
            --border: #e5e9f0;
            --green: #22c55e;
            --green-dim: #f0fdf4;
            --amber: #f59e0b;
            --amber-dim: #fffbeb;
            --red: #ef4444;
            --radius: 10px;
            --radius-sm: 6px;
            --shadow: 0 1px 4px rgba(0,0,0,0.06);
            --shadow-md: 0 4px 16px rgba(0,0,0,0.08);
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            font-size: 14px;
            line-height: 1.5;
        }

        /* ── HEADER ── */
        .site-header {
            background: var(--white);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .header-inner {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 24px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .header-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .brand-icon {
            width: 34px; height: 34px;
            background: var(--sidebar);
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 15px;
        }
        .brand-name {
            font-size: 15px;
            font-weight: 700;
            color: var(--sidebar);
        }
        .brand-name span { color: var(--blue); }
        .secure-badge {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 12px;
            font-weight: 500;
            color: var(--green);
            background: var(--green-dim);
            border: 1px solid #bbf7d0;
            padding: 4px 10px;
            border-radius: 100px;
        }

        /* ── STEPS ── */
        .steps-bar {
            background: var(--white);
            border-bottom: 1px solid var(--border);
        }
        .steps-inner {
            max-width: 960px;
            margin: 0 auto;
            padding: 0 24px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .step-item {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 13px 20px;
        }
        .step-num {
            width: 24px; height: 24px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px;
            font-weight: 600;
            flex-shrink: 0;
        }
        .step-label { font-size: 13px; font-weight: 500; }
        .step-item.done .step-num { background: var(--green); color: #fff; }
        .step-item.done .step-label { color: var(--green); }
        .step-item.active .step-num { background: var(--blue); color: #fff; }
        .step-item.active .step-label { color: var(--blue); font-weight: 600; }
        .step-item.pending .step-num { background: var(--border); color: var(--text-light); }
        .step-item.pending .step-label { color: var(--text-light); }
        .step-line { width: 40px; height: 1px; background: var(--border); }

        @media (max-width: 480px) {
            .step-label { display: none; }
            .step-item { padding: 13px 10px; }
        }

        /* ── MAIN ── */
        .page-main {
            max-width: 960px;
            margin: 0 auto;
            padding: 28px 24px 60px;
        }
        .page-heading { font-size: 20px; font-weight: 700; color: var(--text); margin-bottom: 4px; }
        .page-sub { font-size: 13px; color: var(--text-muted); margin-bottom: 24px; }

        .page-grid {
            display: grid;
            grid-template-columns: 1fr 320px;
            gap: 20px;
            align-items: start;
        }
        @media (max-width: 768px) { .page-grid { grid-template-columns: 1fr; } }

        /* ── CARD ── */
        .card {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }
        .card + .card { margin-top: 16px; }
        .card-head {
            padding: 14px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-head-icon {
            width: 30px; height: 30px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            font-size: 14px;
            flex-shrink: 0;
        }
        .card-head-icon.blue { background: var(--blue-dim); }
        .card-head-icon.green { background: var(--green-dim); }
        .card-head-icon.amber { background: var(--amber-dim); }
        .card-title { font-size: 13px; font-weight: 600; color: var(--text); }
        .card-sub { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
        .card-body { padding: 20px; }

        /* ── STEPS LIST ── */
        .step-list { list-style: none; display: flex; flex-direction: column; gap: 12px; }
        .step-li { display: flex; gap: 10px; align-items: flex-start; }
        .step-dot {
            width: 20px; height: 20px;
            border-radius: 50%;
            background: var(--blue-dim);
            color: var(--blue);
            font-size: 10px;
            font-weight: 700;
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0; margin-top: 1px;
        }
        .step-li p { font-size: 13px; color: var(--text-muted); line-height: 1.5; }
        .step-li p strong { color: var(--text); font-weight: 500; }

        /* ── BANK CARD ── */
        .bank-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            padding: 14px 16px;
            margin-bottom: 14px;
        }
        .bank-left { display: flex; align-items: center; gap: 12px; }
        .bank-logo {
            width: 38px; height: 38px;
            border-radius: var(--radius-sm);
            background: #003d8a;
            display: flex; align-items: center; justify-content: center;
            font-size: 10px; font-weight: 800; color: #fff;
            flex-shrink: 0;
        }
        .bank-name { font-size: 12px; color: var(--text-muted); }
        .bank-number { font-size: 17px; font-weight: 700; color: var(--text); letter-spacing: 1px; font-family: monospace; }
        .bank-holder { font-size: 11px; color: var(--text-light); margin-top: 1px; }
        .btn-copy {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-size: 12px;
            font-weight: 500;
            color: var(--text-muted);
            padding: 6px 12px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }
        .btn-copy:hover { border-color: var(--blue); color: var(--blue); background: var(--blue-dim); }
        .btn-copy.copied { border-color: var(--green); color: var(--green); background: var(--green-dim); }

        /* ── REFUND INFO BOX ── */
        .refund-info-box {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: var(--radius);
            padding: 14px 16px;
            margin-top: 16px;
        }
        .refund-info-title {
            font-size: 12px;
            font-weight: 700;
            color: #166534;
            text-transform: uppercase;
            letter-spacing: .5px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .refund-toggle-btn {
            background: none;
            border: none;
            color: #16a34a;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            padding: 0;
            text-decoration: underline;
            font-family: 'Inter', sans-serif;
        }
        .refund-fields {
            display: none;
        }
        .refund-fields.show {
            display: block;
        }
        .refund-field-row {
            margin-bottom: 10px;
        }
        .refund-field-row label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: #374151;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: .4px;
        }
        .refund-field-row input,
        .refund-field-row select {
            width: 100%;
            padding: 9px 12px;
            border: 1.5px solid #d1fae5;
            border-radius: 8px;
            font-size: 13px;
            font-family: 'Inter', sans-serif;
            outline: none;
            transition: border .15s;
            background: #fff;
        }
        .refund-field-row input:focus,
        .refund-field-row select:focus {
            border-color: #22c55e;
        }
        .refund-note {
            font-size: 11px;
            color: #15803d;
            margin-top: 8px;
            line-height: 1.5;
        }

        /* ── ALERT BOX ── */
        .alert-box {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: var(--amber-dim);
            border: 1px solid #fde68a;
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            font-size: 12px;
            color: #92400e;
            line-height: 1.5;
        }
        .alert-box strong { font-weight: 600; }

        /* ── UPLOAD ZONE ── */
        .upload-zone {
            border: 2px dashed var(--border);
            border-radius: var(--radius);
            padding: 28px 20px;
            text-align: center;
            cursor: pointer;
            position: relative;
            transition: all 0.2s;
            background: var(--bg);
        }
        .upload-zone:hover, .upload-zone.dragover {
            border-color: var(--blue);
            background: var(--blue-dim);
        }
        .upload-zone input[type="file"] {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            opacity: 0; cursor: pointer; z-index: 2;
        }
        .upload-icon { font-size: 28px; display: block; margin-bottom: 8px; }
        .upload-main { font-size: 13px; font-weight: 600; color: var(--text); margin-bottom: 3px; }
        .upload-sub { font-size: 12px; color: var(--text-muted); }

        .upload-preview {
            display: none;
            align-items: center;
            gap: 12px;
            background: var(--green-dim);
            border: 1px solid #bbf7d0;
            border-radius: var(--radius-sm);
            padding: 12px 14px;
            margin-top: 12px;
        }
        .upload-preview.show { display: flex; }
        .preview-thumb { width: 44px; height: 44px; border-radius: var(--radius-sm); object-fit: cover; flex-shrink: 0; }
        .preview-name { font-size: 13px; font-weight: 500; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .preview-size { font-size: 11px; color: var(--text-muted); margin-top: 1px; }
        .preview-ok { font-size: 18px; margin-left: auto; flex-shrink: 0; }

        /* ── BUTTONS ── */
        .btn-primary {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%;
            padding: 13px 20px;
            background: var(--blue);
            color: #fff;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 600;
            border: none;
            border-radius: var(--radius);
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-primary:hover { background: var(--blue-light); box-shadow: 0 4px 12px rgba(79,110,247,0.3); }
        .btn-primary:disabled { opacity: 0.45; cursor: not-allowed; box-shadow: none; }

        .btn-ghost {
            display: flex; align-items: center; justify-content: center; gap: 6px;
            width: 100%;
            padding: 11px 20px;
            background: transparent;
            color: var(--text-muted);
            font-family: 'Inter', sans-serif;
            font-size: 13px;
            font-weight: 500;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
            margin-top: 10px;
        }
        .btn-ghost:hover { border-color: var(--red); color: var(--red); background: #fef2f2; }

        /* ── ORDER SUMMARY ── */
        .summary-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border);
            font-size: 13px;
        }
        .summary-row:first-child { padding-top: 0; }
        .summary-row:last-child { border-bottom: none; padding-bottom: 0; }
        .summary-label { color: var(--text-muted); }
        .summary-val { font-weight: 500; color: var(--text); text-align: right; }
        .id-badge {
            font-family: monospace;
            font-size: 12px;
            background: var(--blue-dim);
            color: var(--blue);
            padding: 2px 8px;
            border-radius: var(--radius-sm);
        }
        .status-pill {
            display: inline-flex; align-items: center; gap: 4px;
            font-size: 11px; font-weight: 600;
            padding: 3px 9px;
            border-radius: 100px;
            background: var(--amber-dim);
            color: #92400e;
            border: 1px solid #fde68a;
        }
        .dot { width: 5px; height: 5px; border-radius: 50%; background: currentColor; }

        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--blue-dim);
            border: 1px solid #c7d2fe;
            border-radius: var(--radius);
            padding: 14px 16px;
            margin: 16px 0;
        }
        .total-label { font-size: 12px; font-weight: 500; color: var(--blue); }
        .total-amount { font-size: 20px; font-weight: 700; color: var(--blue); }

        .info-small {
            font-size: 12px;
            color: var(--text-muted);
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 10px 12px;
            line-height: 1.5;
        }
        .info-small a { color: var(--blue); text-decoration: none; font-weight: 500; }

        /* ── FOOTER ── */
        .site-footer {
            border-top: 1px solid var(--border);
            background: var(--white);
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: var(--text-light);
        }
        .site-footer a { color: var(--text-muted); text-decoration: none; }
        .site-footer a:hover { color: var(--blue); }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<!-- HEADER -->
<header class="site-header">
    <div class="header-inner">
        <a href="index.php" class="header-brand">
            <div class="brand-icon">🔧</div>
            <span class="brand-name">Habibi <span>Garage</span></span>
        </a>
        <span class="secure-badge">✓ Pembayaran Aman</span>
    </div>
</header>

<!-- STEPS -->
<div class="steps-bar">
    <div class="steps-inner">
        <div class="step-item done">
            <div class="step-num">✓</div>
            <span class="step-label">Pemesanan</span>
        </div>
        <div class="step-line"></div>
        <div class="step-item active">
            <div class="step-num">2</div>
            <span class="step-label">Pembayaran</span>
        </div>
        <div class="step-line"></div>
        <div class="step-item pending">
            <div class="step-num">3</div>
            <span class="step-label">Konfirmasi</span>
        </div>
    </div>
</div>

<!-- MAIN -->
<main class="page-main">
    <h1 class="page-heading">Selesaikan Pembayaran</h1>
    <p class="page-sub">Transfer ke rekening di bawah, lalu upload bukti pembayaran Anda.</p>

    <div class="page-grid">

        <!-- KIRI -->
        <div>

            <!-- Cara Bayar -->
            <div class="card">
                <div class="card-head">
                    <div class="card-head-icon amber">📋</div>
                    <div>
                        <div class="card-title">Cara Pembayaran</div>
                        <div class="card-sub">Ikuti 4 langkah berikut</div>
                    </div>
                </div>
                <div class="card-body">
                    <ul class="step-list">
                        <li class="step-li"><span class="step-dot">1</span><p>Salin <strong>nomor rekening</strong> tujuan transfer.</p></li>
                        <li class="step-li"><span class="step-dot">2</span><p>Transfer <strong>tepat sesuai nominal</strong> yang tertera.</p></li>
                        <li class="step-li"><span class="step-dot">3</span><p>Simpan <strong>screenshot bukti transfer</strong> dari aplikasi/ATM Anda.</p></li>
                        <li class="step-li"><span class="step-dot">4</span><p>Upload bukti di bawah, lalu klik <strong>Kirim Bukti Pembayaran</strong>.</p></li>
                    </ul>
                </div>
            </div>

            <!-- Rekening -->
            <div class="card">
                <div class="card-head">
                    <div class="card-head-icon green">🏦</div>
                    <div>
                        <div class="card-title">Rekening Tujuan</div>
                        <div class="card-sub">Klik "Salin" untuk menyalin nomor rekening</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="bank-row">
                        <div class="bank-left">
                            <div class="bank-logo">BCA</div>
                            <div>
                                <div class="bank-name">Bank Central Asia</div>
                                <div class="bank-number" id="rek-no">1234567890</div>
                                <div class="bank-holder">a.n. Habibi Garage</div>
                            </div>
                        </div>
                        <button type="button" class="btn-copy" id="btn-copy" onclick="copyRek()">
                            <span class="copy-icon">📋</span>
                            <span class="copy-label">Salin</span>
                        </button>
                    </div>
                    <div class="alert-box">
                        <span>⚠️</span>
                        <div><strong>Perhatian:</strong> Pastikan nomor rekening sudah benar sebelum transfer. Kesalahan transfer bukan tanggung jawab Habibi Garage.</div>
                    </div>
                </div>
            </div>

            <!-- Upload -->
            <div class="card">
                <div class="card-head">
                    <div class="card-head-icon blue">📤</div>
                    <div>
                        <div class="card-title">Upload Bukti Pembayaran</div>
                        <div class="card-sub">JPG, JPEG, PNG, WEBP — Maks. 2MB</div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="payment-form">
                        <input type="hidden" name="id_pemesanan" value="<?= $data['id_pemesanan']; ?>">

                        <div class="upload-zone" id="upload-zone">
                            <input type="file" name="bukti_bayar" id="file-input" accept=".jpg,.jpeg,.png,.webp" required onchange="handleFile(this)">
                            <span class="upload-icon">📎</span>
                            <p class="upload-main">Klik atau seret file ke sini</p>
                            <p class="upload-sub">Format JPG, PNG, WEBP hingga 2MB</p>
                        </div>

                        <div class="upload-preview" id="upload-preview">
                            <img src="" alt="Preview" class="preview-thumb" id="preview-thumb">
                            <div style="flex:1;min-width:0">
                                <p class="preview-name" id="preview-name">—</p>
                                <p class="preview-size" id="preview-size">—</p>
                            </div>
                            <span class="preview-ok">✅</span>
                        </div>

                        <button type="submit" class="btn-primary" id="submit-btn" disabled style="margin-top:16px">
                            Kirim Bukti Pembayaran →
                        </button>
                        <a href="form_booking.php" class="btn-ghost" onclick="return confirmCancel()">
                            ← Batalkan & Kembali ke Pemesanan
                        </a>
                    </form>
                </div>
            </div>

        </div>

        <!-- KANAN -->
        <div>
            <div class="card" style="position:sticky;top:72px">
                <div class="card-head">
                    <div class="card-head-icon blue">🧾</div>
                    <div>
                        <div class="card-title">Ringkasan Pesanan</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="summary-row">
                        <span class="summary-label">ID Pesanan</span>
                        <span class="id-badge">#<?= htmlspecialchars($data['id_pemesanan']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Pelanggan</span>
                        <span class="summary-val"><?= htmlspecialchars($data['nama_pelanggan']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Paket</span>
                        <span class="summary-val" style="color:var(--blue)"><?= htmlspecialchars($data['nama_paket']); ?></span>
                    </div>
                    <div class="summary-row">
                        <span class="summary-label">Status</span>
                        <span class="status-pill"><span class="dot"></span>Menunggu Bayar</span>
                    </div>

                    <div class="total-box">
                        <span class="total-label">Total Pembayaran</span>
                        <span class="total-amount">Rp <?= number_format($data['harga'], 0, ',', '.'); ?></span>
                    </div>

                    <div class="info-small">
                        🕐 Pesanan diproses dalam <strong>1×24 jam</strong> setelah bukti dikonfirmasi.<br><br>
                        Butuh bantuan? <a href="https://wa.me/628xxxxxxxxxx">Hubungi WhatsApp</a>
                    </div>
                </div>
            </div>
        </div>

    </div>
</main>

<!-- FOOTER -->
<footer class="site-footer">
    &copy; <?= date('Y'); ?> Habibi Garage &nbsp;·&nbsp;
    <a href="#">Syarat & Ketentuan</a> &nbsp;·&nbsp;
    <a href="#">Kebijakan Privasi</a>
</footer>

<script>
function copyRek() {
    const no = document.getElementById('rek-no').innerText;
    navigator.clipboard.writeText(no).then(() => {
        const btn = document.getElementById('btn-copy');
        btn.classList.add('copied');
        btn.querySelector('.copy-icon').textContent = '✓';
        btn.querySelector('.copy-label').textContent = 'Disalin!';
        setTimeout(() => {
            btn.classList.remove('copied');
            btn.querySelector('.copy-icon').textContent = '📋';
            btn.querySelector('.copy-label').textContent = 'Salin';
        }, 2500);
    }).catch(() => alert('Nomor rekening: ' + no));
}

function handleFile(input) {
    const file = input.files[0];
    const preview = document.getElementById('upload-preview');
    const btn = document.getElementById('submit-btn');
    if (!file) { preview.classList.remove('show'); btn.disabled = true; return; }
    const ext = file.name.split('.').pop().toLowerCase();
    if (!['jpg','jpeg','png','webp'].includes(ext)) {
        alert('Format tidak valid. Gunakan JPG, PNG, atau WEBP.');
        input.value = ''; preview.classList.remove('show'); btn.disabled = true; return;
    }
    if (file.size > 2 * 1024 * 1024) {
        alert('Ukuran file melebihi 2MB.');
        input.value = ''; preview.classList.remove('show'); btn.disabled = true; return;
    }
    const reader = new FileReader();
    reader.onload = e => {
        document.getElementById('preview-thumb').src = e.target.result;
        document.getElementById('preview-name').textContent = file.name;
        document.getElementById('preview-size').textContent = (file.size/1024).toFixed(1) + ' KB';
        preview.classList.add('show');
        btn.disabled = false;
    };
    reader.readAsDataURL(file);
}

const zone = document.getElementById('upload-zone');
zone.addEventListener('dragover', e => { e.preventDefault(); zone.classList.add('dragover'); });
zone.addEventListener('dragleave', () => zone.classList.remove('dragover'));
zone.addEventListener('drop', e => {
    e.preventDefault(); zone.classList.remove('dragover');
    const input = document.getElementById('file-input');
    input.files = e.dataTransfer.files;
    handleFile(input);
});

function confirmCancel() {
    return confirm('Yakin ingin membatalkan? Pesanan tetap tersimpan, namun perlu upload bukti untuk melanjutkan.');
}

function toggleRefundFields() {
    const fields = document.getElementById('refundFields');
    const btn = document.getElementById('refundToggleBtn');
    const showing = fields.classList.toggle('show');
    btn.textContent = showing ? '- Sembunyikan' : '+ Isi sekarang (opsional)';
}

document.getElementById('payment-form').addEventListener('submit', () => {
    const btn = document.getElementById('submit-btn');
    btn.disabled = true;
    btn.innerHTML = '<span style="display:inline-block;animation:spin 1s linear infinite">↻</span> Mengunggah...';
});
</script>
</body>
</html>