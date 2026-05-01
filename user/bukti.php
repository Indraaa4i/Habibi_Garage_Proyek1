<?php
include 'koneksi.php';

// ambil id transaksi dari URL
$id = $_GET['id'] ?? $_GET['id_pemesanan'] ?? null;

if (!$id) {
    die("ID transaksi tidak ditemukan.");
}

$id = mysqli_real_escape_string($conn, $id);

// ambil data transaksi + paket
$query = mysqli_query($conn, "
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.id_pemesanan = '$id'
");

$data = mysqli_fetch_assoc($query);

if (!$data) {
    die("Data transaksi tidak ditemukan.");
}


if ($data['status'] !== 'lunas') {
    echo "<script>
        alert('Bukti pembayaran hanya tersedia setelah dikonfirmasi admin.');
        window.location.href='pending.php?id=$id';
    </script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Bukti Pembayaran - Habibi Garage</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            background: #f5f6f8;
            font-family: Arial, sans-serif;
        }

        .card-bukti {
            max-width: 650px;
            margin: 50px auto;
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }

        .status {
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 12px;
            font-weight: bold;
        }

        .lunas { background: #d1fae5; color: #065f46; }
        .proses { background: #fef3c7; color: #92400e; }
        .pending { background: #e0f2fe; color: #075985; }
        .batal { background: #fee2e2; color: #991b1b; }

        .line {
            border-top: 2px dashed #ddd;
            margin: 20px 0;
        }
    </style>
</head>

<body>

<div class="card card-bukti">
    <div class="card-body p-5">

        <!-- HEADER -->
        <div class="text-center mb-4">
            <?php
            $status = strtolower($data['status']);
            ?>
            <span class="status <?= $status ?>">
                <?= strtoupper($data['status']) ?>
            </span>

            <h3 class="mt-3 fw-bold">HABIBI GARAGE</h3>
            <small>ID Transaksi: #<?= $data['id_pemesanan'] ?></small>
        </div>

        <!-- TOTAL -->
        <div class="text-center my-4">
            <p class="text-muted mb-1">Total Pembayaran</p>
            <h2 class="fw-bold text-dark">
                Rp <?= number_format($data['harga'], 0, ',', '.') ?>
            </h2>
        </div>

        <div class="line"></div>

        <!-- INFO -->
        <div class="row mb-2">
            <div class="col-6 text-muted">Nama Pelanggan</div>
            <div class="col-6 text-end fw-semibold">
                <?= htmlspecialchars($data['nama_pelanggan']) ?>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-6 text-muted">No Telepon</div>
            <div class="col-6 text-end fw-semibold">
                <?= htmlspecialchars($data['no_telepon']) ?>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-6 text-muted">Paket</div>
            <div class="col-6 text-end fw-semibold">
                <?= htmlspecialchars($data['nama_paket']) ?>
            </div>
        </div>

        <div class="row mb-2">
            <div class="col-6 text-muted">Jadwal</div>
            <div class="col-6 text-end fw-semibold">
                <?= date('d M Y', strtotime($data['tanggal'])) ?> - <?= $data['jam'] ?>
            </div>
        </div>

        <div class="line"></div>

        <!-- DETAIL KENDARAAN -->
        <h6 class="fw-bold mb-3">Detail Kendaraan</h6>

        <div class="row mb-2">
            <div class="col-6 text-muted">Plat Mobil</div>
            <div class="col-6 text-end"><?= $data['plat_mobil'] ?></div>
        </div>

        <div class="line"></div>

        <!-- BUKTI -->
        <?php if (!empty($data['bukti_bayar'])): ?>
            <div class="text-center">
                <p class="text-muted mb-2">Bukti Pembayaran</p>
                <img src="<?= $data['bukti_bayar'] ?>" class="img-fluid rounded" style="max-height:300px;">
            </div>
        <?php endif; ?>

        <!-- BUTTON -->
        <div class="d-grid gap-2 mt-4">
            <button class="btn btn-primary fw-bold" onclick="window.print()">
                Cetak Bukti
            </button>

            <a href="dashboard.php" class="btn btn-outline-secondary">
                Kembali
            </a>
        </div>

    </div>
</div>

</body>
</html>