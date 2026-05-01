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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Status Pembayaran - Habibi Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">

    <?php if ($status == 'pending' || $status == 'proses'): ?>
        <meta http-equiv="refresh" content="30">
    <?php endif; ?>
</head>

<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">

            <div class="card shadow-sm">
                <div class="card-body text-center p-5">

                    <!-- ===================== -->
                    <!-- MENUNGGU PEMBAYARAN -->
                    <!-- ===================== -->
                    <?php if ($status == 'menunggu_pembayaran'): ?>
                        <div class="mb-4">
                            <i class="bi bi-wallet2 text-primary" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold">Menunggu Pembayaran</h3>
                        <p class="text-muted">
                            Silakan lakukan pembayaran dan upload bukti transfer.
                        </p>
                        <div class="alert alert-primary border-0">
                            <strong>Status:</strong> Belum Dibayar
                        </div>

                    <!-- ===================== -->
                    <!-- PENDING (SUDAH UPLOAD) -->
                    <!-- ===================== -->
                    <?php elseif ($status == 'pending'): ?>
                        <div class="mb-4">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold">Menunggu Konfirmasi Admin</h3>
                        <p class="text-muted">
                            Bukti pembayaran sudah diterima. Admin sedang mengecek.
                        </p>
                        <div class="alert alert-warning border-0">
                            <strong>Status:</strong> Pending Verifikasi
                        </div>

                    <!-- ===================== -->
                    <!-- DIPROSES -->
                    <!-- ===================== -->
                    <?php elseif ($status == 'proses'): ?>
                        <div class="mb-4">
                            <i class="bi bi-tools text-info" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold text-info">Sedang Diproses</h3>
                        <p class="text-muted">
                            Pembayaran sudah dikonfirmasi, mobil sedang dicuci.
                        </p>
                        <div class="alert alert-info border-0">
                            <strong>Status:</strong> Proses Cuci
                        </div>

                    <!-- ===================== -->
                    <!-- LUNAS / SELESAI -->
                    <!-- ===================== -->
                    <?php elseif ($status == 'lunas'): ?>
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold text-success">Pesanan Selesai</h3>
                        <p class="text-muted">
                            Terima kasih sudah menggunakan layanan kami.
                        </p>
                        <div class="alert alert-success border-0">
                            <strong>Status:</strong> Selesai
                        </div>
                        <button class="btn btn-success w-100" onclick="window.print()">Cetak Bukti</button>

                    <!-- ===================== -->
                    <!-- DIBATALKAN -->
                    <!-- ===================== -->
                    <?php else: ?>
                        <div class="mb-4">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold text-danger">Pesanan Dibatalkan</h3>
                        <p class="text-muted">
                            Pesanan tidak dapat diproses.
                        </p>
                        <div class="alert alert-danger border-0">
                            <strong>Status:</strong> Dibatalkan
                        </div>
                    <?php endif; ?>

                    <hr class="my-4">

                    <!-- ===================== -->
                    <!-- RINGKASAN -->
                    <!-- ===================== -->
                    <div class="text-start">
                        <h6>Ringkasan Transaksi:</h6>
                        <ul class="list-unstyled small">
                            <li><strong>ID:</strong> #<?= $id_transaksi; ?></li>
                            <li><strong>Pelanggan:</strong> <?= htmlspecialchars($data['nama_pelanggan']); ?></li>
                            <li><strong>Paket:</strong> <?= htmlspecialchars($data['nama_paket']); ?></li>
                            <li><strong>Harga:</strong> Rp <?= number_format($data['harga'], 0, ',', '.'); ?></li>
                            <li><strong>Status:</strong> <?= ucfirst($status); ?></li>
                        </ul>
                    </div>

                    <a href="landing_page.php" class="btn btn-link text-secondary mt-3">
                        Kembali ke Home
                    </a>

                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>