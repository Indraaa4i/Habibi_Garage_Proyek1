<?php 
include 'koneksi.php'; // 1. Pastikan koneksi dipanggil di paling atas
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
                <form id="bookingForm" action="form.php" method="POST" class="row g-4">
                    <div class="col-md-7">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control shadow-sm" placeholder="Contoh: Ali Indrawijaya" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">No. Telepon</label>
                        <input type="tel" name="telepon" class="form-control shadow-sm" placeholder="0812xxxx" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Plat Nomor</label>
                        <input type="text" name="plat" class="form-control shadow-sm" placeholder="contoh: B 1234 ABC" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Jenis Mobil</label>
                        <input type="text" name="jenis" class="form-control shadow-sm" placeholder="contoh:Mazda 3" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Warna Mobil</label>
                        <input type="text" name="warna" class="form-control shadow-sm" placeholder="contoh: hitam" required>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Pilih Layanan Utama</label>
                        <select name="layanan" class="form-select shadow-sm" required>
                            <option value="" disabled>Pilih paket cuci...</option>
                            
                            <?php
                            // Cek apakah ada ID paket kiriman dari menu.php
                            $id_pilihan = isset($_GET['id_paket']) ? $_GET['id_paket'] : '';
                            
                            // Ambil data dari tabel paket_layanan
                            $query_paket = mysqli_query($conn, "SELECT * FROM paket_layanan");
                            
                            while($p = mysqli_fetch_array($query_paket)) {
                                // Jika ID paket di database sama dengan ID dari menu, kasih tanda 'selected'
                                $selected = ($p['id_paket'] == $id_pilihan) ? 'selected' : '';
                                
                                echo "<option value='$p[id_paket]' $selected>
                                        $p[nama_paket] - Rp " . number_format($p['harga'], 0, ',', '.') . "
                                      </option>";
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
                        <button type="submit" id="btnSubmit" class="btn btn-payment w-100 py-3 text-uppercase fw-bold">Lanjutkan Pembayaran</button>
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