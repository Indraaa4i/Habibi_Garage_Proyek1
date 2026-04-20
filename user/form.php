<?php
include 'koneksi.php';

if (isset($_POST['submit'])) {
    
    $id_paket        = $_POST['id_paket'];
    $nama_pelanggan  = $_POST['nama_pelanggan'];
    $plat_mobil      = $_POST['plat_mobil'];
    $jenis_mobil     = $_POST['jenis_mobil'];
    $warna_mobil     = $_POST['warna_mobil'];
    $no_telepon      = $_POST['no_telepon'];
    $tanggal         = $_POST['tanggal'];
    $jam             = $_POST['jam'];

    
    $sql = "INSERT INTO pemesanan (id_paket, nama_pelanggan, plat_mobil, jenis_mobil, warna_mobil, no_telepon, tanggal, jam) 
            VALUES ('$id_paket', '$nama_pelanggan', '$plat_mobil', '$jenis_mobil', '$warna_mobil', '$no_telepon', '$tanggal', '$jam')";

    if (mysqli_query($conn, $sql)) {
        echo "<script>alert('Pemesanan berhasil disimpan!'); window.location='index.php';</script>";
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
}
?>