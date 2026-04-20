<?php
$conn = mysqli_connect("localhost", "root", "", "habibi_garage");

if (!$conn) {
    die("Koneksi gagal: " . mysqli_connect_error());
}
?>