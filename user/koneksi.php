<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "habibi_garage"; 

$conn = mysqli_connect($host, $user, $pass, $db);

if ($conn) {
    echo "Koneksi Berhasil! Database sudah terhubung.";
} else {
    die("Koneksi Gagal: " . mysqli_connect_error());
}
?>