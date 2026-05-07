<?php
include 'koneksi.php';

// ============================
// VALIDASI ID
// ============================
$id_transaksi = $_GET['id_pemesanan'] ?? $_GET['id'] ?? null;

if (!$id_transaksi) {
    die("ID transaksi tidak ditemukan.");
}

$id_transaksi = mysqli_real_escape_string($conn, $id_transaksi);

// ============================
// AMBIL DATA TRANSAKSI
// ============================
$query = mysqli_query($conn, "
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.id_pemesanan = '$id_transaksi'
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Transaksi tidak ditemukan.");
}

$status = $data['status'];
$status_cuci = $data['status_cuci'];

// auto refresh kalau masih berjalan
if (
    $status === 'pending' ||
    ($status === 'lunas' && $status_cuci !== 'selesai')
) {
    $auto_refresh = true;
} else {
    $auto_refresh = false;
}

// mapping status tampilan
function getStatusView($status, $status_cuci) {
    // dibatalkan
    if ($status === 'dibatalkan') {
        return [
            'icon'  => 'bi-x-circle-fill',
            'color' => 'danger',
            'title' => 'Pesanan Dibatalkan',
            'desc'  => 'Pesanan dibatalkan dan tidak dapat diproses.',
            'badge' => 'Dibatalkan'
        ];
    }

    // pending
    if ($status === 'pending') {
        return [
            'icon'  => 'bi-hourglass-split',
            'color' => 'warning',
            'title' => 'Menunggu Konfirmasi Admin',
            'desc'  => 'Bukti pembayaran sudah diterima. Admin sedang mengecek pembayaran Anda.',
            'badge' => 'Pending Verifikasi'
        ];
    }

    // lunas + belum dicuci
    if ($status === 'lunas' && $status_cuci === 'belum_dicuci') {
        return [
            'icon'  => 'bi-credit-card-2-front-fill',
            'color' => 'primary',
            'title' => 'Pembayaran Dikonfirmasi',
            'desc'  => 'Pembayaran berhasil diverifikasi. Mobil Anda menunggu antrean pencucian.',
            'badge' => 'Menunggu Dicuci'
        ];
    }

    // lunas + diproses
    if ($status === 'lunas' && $status_cuci === 'diproses') {
        return [
            'icon'  => 'bi-tools',
            'color' => 'info',
            'title' => 'Mobil Sedang Dicuci',
            'desc'  => 'Mobil Anda sedang dalam proses pencucian.',
            'badge' => 'Sedang Diproses'
        ];
    }

    // lunas + selesai
    if ($status === 'lunas' && $status_cuci === 'selesai') {
        return [
            'icon'  => 'bi-check-circle-fill',
            'color' => 'success',
            'title' => 'Pesanan Selesai',
            'desc'  => 'Pencucian selesai. Terima kasih sudah menggunakan layanan kami.',
            'badge' => 'Selesai'
        ];
    }

    // fallback
    return [
        'icon'  => 'bi-question-circle-fill',
        'color' => 'secondary',
        'title' => 'Status Tidak Diketahui',
        'desc'  => 'Status transaksi tidak dikenali.',
        'badge' => '-'
    ];
}

$view = getStatusView($status, $status_cuci);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Status Pembayaran - Habibi Garage</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php if ($auto_refresh): ?>
        <meta http-equiv="refresh" content="30">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <style>
        body {
            background: #f4f7fb;
        }

        .status-card {
            border: none;
            border-radius: 18px;
            overflow: hidden;
        }

        .status-icon {
            font-size: 4rem;
        }

        .summary-box {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
        }

        .summary-box ul li {
            padding: 4px 0;
        }

        .badge-status {
            font-size: 14px;
            padding: 10px 14px;
            border-radius: 10px;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: #fff;
            }
        }
    </style>
</head>

<body>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm status-card">
                <div class="card-body text-center p-5">

                    <!-- STATUS -->
                    <div class="mb-4">
                        <i class="bi <?= $view['icon']; ?> text-<?= $view['color']; ?> status-icon"></i>
                    </div>

                    <h3 class="fw-bold text-<?= $view['color']; ?>">
                        <?= $view['title']; ?>
                    </h3>

                    <p class="text-muted">
                        <?= $view['desc']; ?>
                    </p>

                    <div class="alert alert-<?= $view['color']; ?> border-0 badge-status">
                        <strong>Status:</strong> <?= $view['badge']; ?>
                    </div>

                    <?php if ($status === 'lunas' && $status_cuci === 'selesai'): ?>
                        <button class="btn btn-success w-100 no-print" onclick="window.print()">
                            Cetak Bukti
                        </button>
                    <?php endif; ?>

                    <hr class="my-4">

                    <!-- RINGKASAN -->
                    <div class="summary-box text-start">
                        <h6 class="fw-bold mb-3">Ringkasan Transaksi</h6>
                        <ul class="list-unstyled small mb-0">
                            <li><strong>ID Transaksi:</strong> #<?= $id_transaksi; ?></li>
                            <li><strong>Pelanggan:</strong> <?= htmlspecialchars($data['nama_pelanggan']); ?></li>
                            <li><strong>Paket:</strong> <?= htmlspecialchars($data['nama_paket']); ?></li>
                            <li><strong>Plat Mobil:</strong> <?= htmlspecialchars(strtoupper($data['plat_mobil'])); ?></li>
                            <li><strong>Tanggal:</strong> <?= date('d M Y', strtotime($data['tanggal'])); ?></li>
                            <li><strong>Jam:</strong> <?= htmlspecialchars($data['jam']); ?></li>
                            <li><strong>Harga:</strong> Rp <?= number_format($data['harga'], 0, ',', '.'); ?></li>
                            <li><strong>Status Booking:</strong> <?= ucfirst($status); ?></li>
                            <li><strong>Status Cuci:</strong> <?= ucfirst(str_replace('_', ' ', $status_cuci)); ?></li>
                        </ul>
                    </div>

                    <a href="landing_page.php" class="btn btn-link text-secondary mt-3 no-print">
                        Kembali ke Home
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>