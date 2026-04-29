<?php 
session_start(); // Baris paling penting agar data session terbaca
include 'koneksi.php'; 

// Mengambil data dari session yang dibuat di landing_page.php
$nama_awal   = $_SESSION['nama_pelanggan'] ?? '';
$telp_awal   = $_SESSION['no_handphone'] ?? '';
$plat_awal   = $_SESSION['plat_mobil'] ?? '';
$jenis_awal  = $_SESSION['jenis_mobil'] ?? '';
$warna_awal  = $_SESSION['warna_mobil'] ?? '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proses_booking'])) {
    
    // Ambil data dari input form
    $nama    = mysqli_real_escape_string($conn, $_POST['nama']);
    $telp    = mysqli_real_escape_string($conn, $_POST['telepon']);
    $plat    = mysqli_real_escape_string($conn, $_POST['plat']);
    $jenis   = mysqli_real_escape_string($conn, $_POST['jenis']);
    $warna   = mysqli_real_escape_string($conn, $_POST['warna']);
    $id_paket = mysqli_real_escape_string($conn, $_POST['id_paket']); 
    $tanggal = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam     = mysqli_real_escape_string($conn, $_POST['jam']);

    $sql = "INSERT INTO pemesanan (id_paket, nama_pelanggan, plat_mobil, jenis_mobil, warna_mobil, no_telepon, tanggal, jam) 
            VALUES ('$id_paket', '$nama', '$plat', '$jenis', '$warna', '$telp', '$tanggal', '$jam')";

    if (mysqli_query($conn, $sql)) {
        $id_terakhir = mysqli_insert_id($conn);
        echo "<script>
                alert('Pemesanan Berhasil!');
                window.location.href='pembayaran.php?id=" . $id_terakhir . "';
              </script>";
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habibi Garage - Full Screen Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/form.css">
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 full-height">
        
        <div class="col-lg-5 left-section d-flex flex-column justify-content-center p-4 p-md-5">
            <div class="content-wrapper">
                <div class="header-mobile-flex d-flex align-items-center gap-3 mb-4">
                    <img src="../img/logo.png" alt="Habibi Garage" class="garage-logo">
                    <div class="cta-text-wrapper">
                        <h1 class="call-to-action mb-0">Atur Jadwalmu</h1>
                        <p class="description mb-0 d-none d-md-block">Pesan jadwal Anda dalam sekejap</p>
                    </div>
                </div>
                <p class="description d-md-none">Pesan jadwal Anda dalam sekejap</p>
            </div>
        </div>
        
        <div class="col-lg-7 right-section d-flex align-items-center p-4 p-md-5">
    <div class="form-wrapper w-100">
        <form id="bookingForm" action="form_booking.php" method="POST" class="row g-4">
            <div class="col-md-7">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control shadow-sm" value="<?= htmlspecialchars($nama_awal) ?>" required>
            </div>
            <div class="col-md-5">
                <label class="form-label">No. Telepon</label>
                <input type="tel" name="telepon" class="form-control shadow-sm" value="<?= htmlspecialchars($telp_awal) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Plat Nomor</label>
                <input type="text" name="plat" class="form-control shadow-sm" value="<?= htmlspecialchars($plat_awal) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Jenis Mobil</label>
                <input type="text" name="jenis" class="form-control shadow-sm" value="<?= htmlspecialchars($jenis_awal) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label">Warna Mobil</label>
                <input type="text" name="warna" class="form-control shadow-sm" value="<?= htmlspecialchars($warna_awal) ?>" required>
            </div>

            <div class="col-12">
                <label class="form-label">Pilih Layanan Utama</label>
                <select name="id_paket" class="form-select shadow-sm" required>
                    <option value="" disabled selected>Pilih paket cuci...</option>
                    <?php
                    $id_pilihan = isset($_GET['id_paket']) ? $_GET['id_paket'] : '';
                    $query_paket = mysqli_query($conn, "SELECT * FROM paket_layanan");
                    while($p = mysqli_fetch_array($query_paket)) {
                        $selected = ($p['id_paket'] == $id_pilihan) ? 'selected' : '';
                        echo "<option value='$p[id_paket]' $selected>$p[nama_paket] - Rp " . number_format($p['harga'], 0, ',', '.') . "</option>";
                    }
                    ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Tanggal Kedatangan</label>
                        <input type="date" id="bookingDate" name="tanggal" class="form-control shadow-sm" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Pilih Jam</label>
                        <select name="jam" class="form-select shadow-sm" required>
                            <option value="" selected disabled>Pilih waktu...</option>
                            <option>08:00 - 09:00</option>
                            <option>09:00 - 10:00</option>
                            <option>10:00 - 11:00</option>
                            <option>11:00 - 12:00</option>
                            <option>13:00 - 14:00</option>
                            <option>14:00 - 15:00</option>
                            <option>15:00 - 16:00</option>
                        </select>
                    </div>

                    <div class="col-12 mt-5">
                        <button type="submit" name="proses_booking" class="btn btn-payment w-100 py-3 text-uppercase fw-bold">
                            Lanjutkan Pembayaran
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/form.js"></script>
</body>
</html>