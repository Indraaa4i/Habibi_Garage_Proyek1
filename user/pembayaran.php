<?php
include 'koneksi.php';

// Ambil ID dari URL
if (isset($_GET['id']) && !empty($_GET['id'])) {
    $id_pemesanan = mysqli_real_escape_string($conn, $_GET['id']);

    // JOIN: Ambil data pemesanan sekaligus data paket (nama & harga)
    $sql = "SELECT p.*, l.nama_paket, l.harga 
            FROM pemesanan p 
            JOIN paket_layanan l ON p.id_paket = l.id_paket 
            WHERE p.id_pemesanan = '$id_pemesanan'";

    $query = mysqli_query($conn, $sql);
    $data = mysqli_fetch_array($query);

    if (!$data) {
        die("Data pesanan tidak ditemukan di sistem.");
    }
} else {
    echo "<script>alert('Akses ilegal! Isi form dulu.'); window.location.href='form_booking.php';</script>";
    exit;
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran & Konfirmasi - Habibi Garage</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/form.css">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #d4d5d7;
        }
        .card-pembayaran {
            background-color: #0d1117;
            border: 1px solid #30363d;
            border-radius: 20px;
        }
        .info-box {
            background-color: #161b22;
            border-left: 5px solid #0066ff;
            padding: 20px;
            border-radius: 12px;
        }
        .upload-area {
            border: 2px dashed #30363d;
            padding: 25px;
            border-radius: 15px;
            transition: all 0.3s ease;
            text-align: center;
        }
        .upload-area:hover {
            border-color: #0066ff;
            background-color: rgba(0, 102, 255, 0.05);
        }
        .btn-payment {
            background-color: #0066ff !important;
            color: white !important;
            border: none;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .btn-payment:hover {
            background-color: #0052cc !important;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 102, 255, 0.3);
        }
    </style>
</head>
<body class="right-section text-white">

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <div class="card card-pembayaran p-4 shadow-lg">
                
                <div class="text-center mb-4">
                    <img src="../img/logo.png" alt="Logo" style="height: 50px;" class="mb-3">
                    <h3 class="fw-bold text-primary">KONFIRMASI BAYAR</h3>
                    <p class="text-secondary small">Silakan transfer sesuai nominal di bawah ini</p>
                </div>

                <div class="info-box mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <small class="text-secondary">Nama Pelanggan</small>
                        <span class="fw-bold"><?php echo htmlspecialchars($data['nama_pelanggan']); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <small class="text-secondary">Paket Layanan</small>
                        <span class="text-info fw-bold"><?php echo htmlspecialchars($data['nama_paket']); ?></span>
                    </div>
                    <hr class="border-secondary">
                    <div class="text-center mt-3">
                        <small class="text-secondary d-block">Total yang Harus Dibayar:</small>
                        <h2 class="text-warning fw-bold mb-0">
                            Rp <?php echo number_format($data['harga'], 0, ',', '.'); ?>
                        </h2>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="form-label text-white-50 small fw-bold">Transfer ke Rekening:</label>
                    <div class="p-3 rounded bg-dark border border-secondary d-flex justify-content-between align-items-center">
                        <div>
                            <span class="d-block fw-bold">BCA - 1234567890</span>
                            <small class="text-secondary">A/N Habibi Garage</small>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" onclick="alert('Nomor rekening disalin!')">Salin</button>
                    </div>
                </div>

                <form action="proses_bayar.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="id_pemesanan" value="<?php echo $data['id_pemesanan']; ?>">
                    
                    <div class="upload-area mb-4">
                        <h6 class="text-white mb-3">Unggah Bukti Transfer</h6>
                        <input type="file" name="bukti_bayar" class="form-control bg-transparent text-white border-secondary" accept="image/*" required>
                        <small class="text-muted d-block mt-2">Pastikan gambar jelas (Max 2MB)</small>
                    </div>

                    <button type="submit" class="btn btn-payment w-100 py-3 fw-bold text-uppercase">
                        Kirim Bukti Pembayaran
                    </button>
                </form>

            </div>
            
            <div class="text-center mt-4">
                <a href="form_booking.php" class="text-secondary text-decoration-none small">
                    <i class="bi bi-arrow-left"></i> Kembali ke Pemesanan
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>