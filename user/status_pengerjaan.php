<?php
session_start();
include 'koneksi.php';



$plat = $_SESSION['plat_mobil'] ?? '';
$no_hp = $_SESSION['no_telepon'] ?? '';

// Ambil data pesanan terbaru user
$query = mysqli_query($conn, "
    SELECT p.*, l.nama_paket
    FROM pemesanan p
    JOIN paket_layanan l ON p.id_paket = l.id_paket
    WHERE p.plat_mobil = '$plat'
    AND p.no_telepon = '$no_hp'
    ORDER BY p.id_pemesanan DESC
    LIMIT 1
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Status Pengerjaan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b);
            font-family: 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        .container {
            padding-top: 50px;
            padding-bottom: 50px;
        }

        .page-title {
            color: white;
            font-weight: bold;
            text-align: center;
            margin-bottom: 40px;
        }

        .status-card {
            border: none;
            border-radius: 25px;
            overflow: hidden;
            background: #fff;
            transition: 0.3s ease;
        }

        .status-card:hover {
            transform: translateY(-5px);
        }

        .status-header {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: white;
            padding: 20px;
        }

        .status-header h5 {
            margin: 0;
            font-weight: bold;
        }

        .info-label {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 3px;
        }

        .info-value {
            font-weight: 600;
            color: #0f172a;
        }

        .progress {
            height: 12px;
            border-radius: 20px;
            background-color: #e2e8f0;
        }

        .progress-bar {
            border-radius: 20px;
            transition: width 0.7s ease;
        }

        .status-note {
            font-size: 14px;
            color: #64748b;
        }

        .empty-box {
            background: white;
            border-radius: 20px;
            padding: 40px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .empty-box h4 {
            color: #0f172a;
            font-weight: bold;
        }

        .empty-box p {
            color: #64748b;
        }

        .badge-status {
            font-size: 13px;
            padding: 8px 14px;
            border-radius: 30px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="page-title">Status Pengerjaan Cuci Mobil</h2>

    <div class="row g-4">
        <?php if (mysqli_num_rows($query) > 0): ?>
            <?php while ($data = mysqli_fetch_assoc($query)): ?>
                <?php
                    $status = $data['status_pengerjaan'];

                    if ($status == 'belum') {
                        $progress = 25;
                        $warna = 'danger';
                        $text = 'Belum Dikerjakan';
                        $icon = '⏳';
                        $note = 'Mobil Anda masih dalam antrean pencucian.';
                    } elseif ($status == 'proses') {
                        $progress = 65;
                        $warna = 'warning';
                        $text = 'Sedang Diproses';
                        $icon = '🧼';
                        $note = 'Mobil Anda sedang dalam proses pencucian.';
                    } else {
                        $progress = 100;
                        $warna = 'success';
                        $text = 'Selesai';
                        $icon = '✅';
                        $note = 'Mobil Anda telah selesai dicuci dan siap diambil.';
                    }
                ?>

                <div class="col-lg-6">
                    <div class="card status-card shadow-lg">
                        <div class="status-header">
                            <h5><?= $icon; ?> Status Pengerjaan</h5>
                        </div>

                        <div class="card-body p-4">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <div class="info-label">Nama Layanan</div>
                                    <div class="info-value"><?= htmlspecialchars($data['nama_paket']); ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Tanggal Pesan</div>
                                    <div class="info-value"><?= date('d M Y', strtotime($data['tanggal'])); ?></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold"><?= $text; ?></span>
                                    <span class="badge bg-<?= $warna; ?> badge-status"><?= ucfirst($status); ?></span>
                                </div>

                                <div class="progress">
                                    <div class="progress-bar bg-<?= $warna; ?>"
                                        role="progressbar"
                                        style="width: <?= $progress; ?>%;"
                                        aria-valuenow="<?= $progress; ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                    </div>
                                </div>

                                <div class="status-note mt-2">
                                    <?= $note; ?>
                                </div>
                            </div>

                            <div class="text-end">
                                <small class="text-muted">
                                    No. Pesanan #<?= $data['id_pemesanan']; ?>
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="empty-box">
                    <h4>Pesanan Tidak Ditemukan</h4>
                    <p>Anda tidak memiliki cucian mobil saat ini</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

</body>
</html>