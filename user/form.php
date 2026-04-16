<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "habibi_garage";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Koneksi ke database gagal: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
   // Ambil data dari form (sesuaikan dengan atribut 'name' di HTML)
$nama     = $_POST['nama'];
$telepon  = $_POST['telepon'];
$plat     = $_POST['plat'];
$jenis    = $_POST['jenis'];
$warna    = $_POST['warna'];
$layanan  = $_POST['layanan']; // Pastikan ini sesuai dengan id_paket jika itu foreign key
$tanggal  = $_POST['tanggal'];
$jam      = $_POST['jam'];

// Query yang sudah disesuaikan dengan struktur tabel 'pemesanan' di database kamu
$sql = "INSERT INTO pemesanan (nama_pelanggan, no_telepon, plat_mobil, jenis_mobil, warna_mobil, tanggal, jam) 
        VALUES ('$nama', '$telepon', '$plat', '$jenis', '$warna', '$tanggal', '$jam')";

if (mysqli_query($conn, $sql)) {
    echo "<script>
            alert('Booking Berhasil Disimpan!');
            window.location.href='index.html';
          </script>";
} else {
    echo "Error: " . mysqli_error($conn);
}