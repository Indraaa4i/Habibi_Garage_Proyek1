<?php
include 'koneksi.php'; 

$id_transaksi = $_GET['id_pemesanan']; 
$query = mysqli_query($conn, "SELECT status, bukti_bayar FROM pemesanan WHERE id_pemesanan = '$id_transaksi'");
$data = mysqli_fetch_assoc($query);

// Jika data tidak ditemukan
if (!$data) {
    echo "Transaksi tidak ditemukan.";
    exit;
}

$status = $data['status'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Status Pembayaran - Cuci Mobil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <?php if ($status == 'pending'): ?>
    <meta http-equiv="refresh" content="30">
    <?php endif; ?>
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-6">
            
            <div class="card shadow-sm">
                <div class="card-body text-center p-5">
                    
                    <?php if ($status == 'pending'): ?>
                        <div class="mb-4">
                            <i class="bi bi-hourglass-split text-warning" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold">Menunggu Konfirmasi</h3>
                        <p class="text-muted">
                            Bukti pembayaran Anda sudah kami terima. Admin sedang melakukan pengecekan manual. 
                            Halaman ini akan terupdate otomatis jika sudah dikonfirmasi.
                        </p>
                        <div class="alert alert-warning border-0">
                            <strong>Status:</strong> Sedang Diverifikasi
                        </div>

                    <?php elseif ($status == 'success'): ?>
                        <div class="mb-4">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold text-success">Pembayaran Berhasil!</h3>
                        <p class="text-muted">Pembayaran Anda telah divalidasi. Silakan tunjukkan halaman ini ke petugas cuci mobil kami.</p>
                        <div class="alert alert-success border-0">
                            <strong>Status:</strong> Pembayaran Diterima
                        </div>
                        <button class="btn btn-success w-100" onclick="window.print()">Cetak Bukti</button>

                    <?php else: ?>
                        <div class="mb-4">
                            <i class="bi bi-x-circle-fill text-danger" style="font-size: 4rem;"></i>
                        </div>
                        <h3 class="fw-bold text-danger">Pembayaran Ditolak</h3>
                        <p class="text-muted">Mohon maaf, bukti pembayaran yang Anda unggah tidak valid atau tidak terbaca.</p>
                        <a href="upload_ulang.php?id=<?= $id_transaksi; ?>" class="btn btn-danger w-100">Upload Ulang Bukti</a>
                    <?php endif; ?>

                    <hr class="my-4">
                    <div class="text-start">
                        <h6>Ringkasan Transaksi:</h6>
                        <ul class="list-unstyled small">
                            <li><strong>ID Transaksi:</strong> #<?= $id_transaksi; ?></li>
                            <li><strong>Status Saat Ini:</strong> <?= ucfirst($status); ?></li>
                        </ul>
                    </div>
                    
                    <a href="dashboard.php" class="btn btn-link text-secondary mt-3">Kembali ke Dashboard</a>
                </div>
            </div>

        </div>
    </div>
</div>

</body>
</html>