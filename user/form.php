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
    
    $nama     = $_POST['nama'];
    $telepon  = $_POST['telepon'];
    $plat     = $_POST['plat'];
    $jenis    = $_POST['jenis'];
    $warna    = $_POST['warna'];
    $layanan  = $_POST['layanan'];
    $tanggal  = $_POST['tanggal'];
    $jam      = $_POST['jam'];

    $sql = "INSERT INTO tabel_booking (nama, telepon, plat, jenis_mobil, warna_mobil, layanan, tanggal, jam) 
            VALUES ('$nama', '$telepon', '$plat', '$jenis', '$warna', '$layanan', '$tanggal', '$jam')";

    if (mysqli_query($conn, $sql)) {

        echo "<script>
                alert('Booking Berhasil Disimpan!');
                window.location.href='index.html'; 
              </script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>