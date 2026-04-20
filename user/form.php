<?php
include 'koneksi.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $nama    = $_POST['nama'];
    $telp    = $_POST['telepon'];
    $plat    = $_POST['plat'];
    $jenis   = $_POST['jenis'];
    $warna   = $_POST['warna'];
    $layanan = $_POST['layanan'];
    $tanggal = $_POST['tanggal'];
    $jam     = $_POST['jam'];

    
    $sql = "INSERT INTO pemesanan (nama_pelanggan, no_telepon, plat_mobil, jenis_mobil, warna_mobil, tanggal, jam) 
            VALUES ('$nama', '$telp', '$plat', '$jenis', '$warna', '$tanggal', '$jam')";

    if (mysqli_query($conn, $sql)) {
        
        echo "<script>
                alert('Pemesanan Berhasil Disimpan!');
                window.location.href='form_booking.php'; 
              </script>";
    } else {
        
        echo "Gagal menyimpan data: " . mysqli_error($conn);
    }
}
?>