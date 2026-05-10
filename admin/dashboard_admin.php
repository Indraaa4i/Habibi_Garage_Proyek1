<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../user/landing_page.php");
    exit;
}

if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../user/landing_page.php");
    exit;
}

include '../user/koneksi.php';

/* =========================================================
   AJAX: BOOKING PER TANGGAL (untuk kalender)
========================================================= */
if(isset($_GET['ajax_booking'])){
    $tgl = mysqli_real_escape_string($conn, $_GET['tgl']);
    $q = mysqli_query($conn,"
        SELECT p.*, pl.nama_paket, pl.harga
        FROM pemesanan p
        JOIN paket_layanan pl ON p.id_paket = pl.id_paket
        WHERE p.tanggal = '$tgl'
        ORDER BY p.jam ASC
    ");
    $rows = [];
    while($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
}

/* =========================================================
   UPDATE STATUS CUCI — ONE-CLICK (belum_dicuci→diproses→selesai)
========================================================= */
if(isset($_POST['next_status'])){
    $id = (int)$_POST['id_pemesanan'];
    $q  = mysqli_query($conn,"SELECT status_cuci FROM pemesanan WHERE id_pemesanan='$id'");
    $cur = mysqli_fetch_assoc($q)['status_cuci'] ?? 'belum_dicuci';
    $next = $cur === 'belum_dicuci' ? 'diproses'
          : ($cur === 'diproses'    ? 'selesai'
          :                          'selesai');
    mysqli_query($conn,"UPDATE pemesanan SET status_cuci='$next' WHERE id_pemesanan='$id'");
    header("Location: dashboard_admin.php");
    exit;
}



/* =========================================================
   STATISTIK
========================================================= */

$hari_ini = date('Y-m-d');

$q_booking = mysqli_query($conn,"
SELECT COUNT(*) as total
FROM pemesanan
WHERE tanggal='$hari_ini'
");

$total_booking = mysqli_fetch_assoc($q_booking)['total'];

$q_pending = mysqli_query($conn,"
SELECT COUNT(*) as total
FROM pemesanan
WHERE status='pending'
");

$total_pending = mysqli_fetch_assoc($q_pending)['total'];

$q_income_today = mysqli_query($conn,"
SELECT SUM(pl.harga) as total
FROM pemesanan p
JOIN paket_layanan pl ON p.id_paket = pl.id_paket
WHERE p.status='lunas'
AND DATE(p.tanggal)=CURDATE()
");

$income_today = mysqli_fetch_assoc($q_income_today)['total'] ?? 0;

$q_income_month = mysqli_query($conn,"
SELECT SUM(pl.harga) as total
FROM pemesanan p
JOIN paket_layanan pl ON p.id_paket = pl.id_paket
WHERE p.status='lunas'
AND MONTH(p.tanggal)=MONTH(CURDATE())
AND YEAR(p.tanggal)=YEAR(CURDATE())
");

$income_month = mysqli_fetch_assoc($q_income_month)['total'] ?? 0;

/* =========================================================
   UPDATE STATUS CUCI
========================================================= */

if(isset($_POST['update_status'])){

    $id = $_POST['id_pemesanan'];
    $status_cuci = $_POST['status_cuci'];

    mysqli_query($conn,"
    UPDATE pemesanan
    SET status_cuci='$status_cuci'
    WHERE id_pemesanan='$id'
    ");

    header("Location: dashboard_admin.php");
    exit;
}

/* =========================================================
   TAMBAH PELANGGAN
========================================================= */

if(isset($_POST['tambah_pelanggan'])){

    $nama = $_POST['nama_pelanggan'];
    $telepon = $_POST['no_telepon'];
    $paket = $_POST['id_paket'];
    $tanggal = $_POST['tanggal'];
    $jam = $_POST['jam'];

    mysqli_query($conn,"
    INSERT INTO pemesanan(
        nama_pelanggan,
        no_telepon,
        id_paket,
        tanggal,
        jam,
        status,
        status_cuci,
        created_at
    )
    VALUES(
        '$nama',
        '$telepon',
        '$paket',
        '$tanggal',
        '$jam',
        'pending',
        'belum_dicuci',
        NOW()
    )
    ");

    header("Location: dashboard_admin.php?page=booking");
    exit;
}

/* =========================================================
   CRUD MENU
========================================================= */

if(isset($_POST['aksi_menu'])){

    $aksi = $_POST['aksi_menu'];

    if($aksi == "tambah"){

        $nama = $_POST['nama_paket'];
        $harga = $_POST['harga'];
        $deskripsi = $_POST['deskripsi'];

        mysqli_query($conn,"
        INSERT INTO paket_layanan(
            nama_paket,
            harga,
            deskripsi
        )
        VALUES(
            '$nama',
            '$harga',
            '$deskripsi'
        )
        ");
    }

    if($aksi == "edit"){

        $id = $_POST['id_paket'];
        $nama = $_POST['nama_paket'];
        $harga = $_POST['harga'];
        $deskripsi = $_POST['deskripsi'];

        mysqli_query($conn,"
        UPDATE paket_layanan
        SET
            nama_paket='$nama',
            harga='$harga',
            deskripsi='$deskripsi'
        WHERE id_paket='$id'
        ");
    }

    if($aksi == "hapus"){

        $id = $_POST['id_paket'];

        mysqli_query($conn,"
        DELETE FROM paket_layanan
        WHERE id_paket='$id'
        ");
    }

    header("Location: dashboard_admin.php?page=menu");
    exit;
}

/* =========================================================
   QUERY
========================================================= */

$q_booking_today = mysqli_query($conn,"
SELECT p.*, pl.nama_paket, pl.harga
FROM pemesanan p
JOIN paket_layanan pl ON p.id_paket = pl.id_paket
WHERE p.tanggal = CURDATE()
ORDER BY p.jam ASC
");

$q_paket = mysqli_query($conn,"
SELECT *
FROM paket_layanan
ORDER BY id_paket DESC
");

$page = $_GET['page'] ?? 'dashboard';

?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Dashboard Admin</title>

<link rel="preconnect" href="https://fonts.googleapis.com">

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Poppins',sans-serif;
}

body{
background:#d9d9d9;
padding:25px;
}

.container{
width:100%;
max-width:1450px;
margin:auto;
background:white;
border-radius:28px;
overflow:hidden;
box-shadow:0 10px 30px rgba(0,0,0,0.1);
}

.navbar{
background:#0f1b2d;
padding:35px;
display:flex;
justify-content:space-between;
align-items:center;
flex-wrap:wrap;
gap:20px;
}

.logo h2{
color:white;
font-size:28px;
}

.nav-links{
display:flex;
gap:12px;
flex-wrap:wrap;
}

.nav-links a{
text-decoration:none;
color:#d8dbe2;
padding:12px 18px;
border-radius:12px;
transition:0.3s;
font-size:14px;
}

.nav-links a:hover,
.nav-links .active{
background:white;
color:#0f1b2d;
}

.main{
padding:30px;
}

.cards{
display:grid;
grid-template-columns:repeat(3,1fr);
gap:20px;
margin-bottom:30px;
}

.card{
background:white;
padding:25px;
border-radius:22px;
box-shadow:0 8px 20px rgba(0,0,0,0.08);
}

.card h5{
color:#777;
margin-bottom:10px;
}

.card h2{
font-size:30px;
color:#0f1b2d;
margin-bottom:10px;
}

.grid{
display:grid;
grid-template-columns:1.5fr 1fr;
gap:25px;
}

.box{
background:white;
padding:25px;
border-radius:22px;
box-shadow:0 8px 20px rgba(0,0,0,0.08);
}

.box-title{
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:20px;
}

.booking-item{
background:#f7f8fc;
padding:18px;
border-radius:18px;
margin-bottom:15px;
}

.booking-item h4{
margin-bottom:5px;
color:#0f1b2d;
}

.booking-item p{
color:#777;
font-size:14px;
margin-bottom:12px;
}

.time{
background:#119cc2;
color:white;
padding:10px 15px;
border-radius:20px;
display:inline-block;
font-size:13px;
margin-top:10px;
}

form{
display:flex;
flex-direction:column;
gap:15px;
}

input,
select{
padding:14px;
border-radius:12px;
border:1px solid #ddd;
outline:none;
}

button{
padding:14px;
border:none;
border-radius:12px;
background:#0f1b2d;
color:white;
cursor:pointer;
font-weight:600;
}

.form-box{
background:white;
padding:25px;
border-radius:22px;
box-shadow:0 8px 20px rgba(0,0,0,0.08);
max-width:700px;
}

.menu-item{
background:#f7f8fc;
padding:18px;
border-radius:16px;
display:flex;
justify-content:space-between;
align-items:center;
margin-bottom:15px;
}

.action-btn{
display:flex;
gap:10px;
}

.edit{
background:#119cc2;
}

.delete{
background:#ef5350;
}

.table-box{
background:white;
padding:25px;
border-radius:22px;
box-shadow:0 8px 20px rgba(0,0,0,0.08);
overflow:auto;
}

table{
width:100%;
border-collapse:collapse;
}

th{
background:#0f1b2d;
color:white;
padding:14px;
text-align:left;
font-size:14px;
}

td{
padding:14px;
border-bottom:1px solid #eee;
font-size:14px;
}

.calendar{
width:100%;
border-spacing:10px;
}

.calendar td{
background:#f7f8fc;
padding:15px;
border-radius:12px;
text-align:center;
cursor:pointer;
transition:0.3s;
}

.calendar td:hover{
background:#0f1b2d;
color:white;
}

@media(max-width:1000px){

.cards,
.grid{
grid-template-columns:1fr;
}

}


/* Override hover untuk kalender */
.calendar td[data-disabled]:hover {
  background: #e5e7eb !important;
  color: #9ca3af !important;
  cursor: not-allowed !important;
}
.calendar td[data-today]:hover {
  background: #0f1b2d !important;
  color: white !important;
}

</style>
</head>
<body>

<div class="container">

<div class="navbar">

<div class="logo">
<h2>Habibi Garage</h2>
</div>

<div class="nav-links">

<a href="?page=dashboard"
class="<?= $page=='dashboard' ? 'active' : '' ?>">
Dashboard
</a>

<a href="?page=booking"
class="<?= $page=='booking' ? 'active' : '' ?>">
Booking
</a>

<a href="?page=menu"
class="<?= $page=='menu' ? 'active' : '' ?>">
Menu
</a>

<a href="?page=recap"
class="<?= $page=='recap' ? 'active' : '' ?>">
Recap
</a>

<a href="?page=admin"
class="<?= $page=='admin' ? 'active' : '' ?>">
Admin
</a>

<a href="?logout=true">
Logout
</a>

</div>

</div>

<div class="main">

<!-- =========================================================
DASHBOARD
========================================================= -->

<?php if($page == 'dashboard'): ?>

<div class="cards">

<div class="card">
<h5>Booking Hari Ini</h5>
<h2><?= $total_booking ?></h2>
<p>Total booking hari ini</p>
</div>

<div class="card">
<h5>Konfirmasi Admin</h5>
<h2><?= $total_pending ?></h2>
<p>Menunggu konfirmasi</p>
</div>

<div class="card">
<h5>Pendapatan Hari Ini</h5>
<h2>Rp <?= number_format($income_today,0,',','.') ?></h2>
<p>Total income hari ini</p>
</div>

</div>

<div class="grid">

<div class="box">

<div class="box-title">
<h3>Booking Hari Ini</h3>
<p><?= date('d F Y') ?></p>
</div>

<?php while($row=mysqli_fetch_assoc($q_booking_today)): ?>

<div class="booking-item">

<h4><?= htmlspecialchars($row['nama_pelanggan']) ?></h4>

<p>
<?= htmlspecialchars($row['nama_paket']) ?>
</p>

<?php
$sc = $row['status_cuci'];
$btn_label = $sc === 'belum_dicuci' ? '&#9654; Mulai Proses'
           : ($sc === 'diproses'    ? '&#10004; Selesai Dicuci'
           :                         '&#10004; Sudah Selesai');
$btn_color = $sc === 'belum_dicuci' ? '#119cc2'
           : ($sc === 'diproses'    ? '#22c55e'
           :                         '#9ca3af');
$btn_disabled = $sc === 'selesai' ? 'disabled' : '';
$badge_label  = $sc === 'belum_dicuci' ? 'Belum Dicuci'
              : ($sc === 'diproses'    ? 'Diproses'
              :                         'Selesai');
$badge_color  = $sc === 'belum_dicuci' ? '#f59e0b'
              : ($sc === 'diproses'    ? '#3b82f6'
              :                         '#22c55e');
?>
<div style="margin-bottom:8px;">
  <span style="font-size:12px;font-weight:600;color:white;background:<?= $badge_color ?>;padding:3px 10px;border-radius:20px;">
    <?= $badge_label ?>
  </span>
</div>
<form method="POST" style="display:block;">
  <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
  <button type="submit" name="next_status" <?= $btn_disabled ?>
    style="background:<?= $btn_color ?>;padding:10px 16px;border-radius:10px;font-size:13px;width:100%;<?= $sc==='selesai' ? 'opacity:.5;cursor:not-allowed;' : '' ?>">
    <?= $btn_label ?>
  </button>
</form>

<div class="time">
<?= htmlspecialchars($row['jam']) ?>
</div>

</div>

<?php endwhile; ?>

</div>

<div class="box">

<div class="box-title">
<h3>Kalender Booking</h3>
</div>

<table class="calendar">
<tr>
<th style="color:#777;font-size:12px;padding:8px;text-align:center;">Min</th>
<th style="color:#777;font-size:12px;padding:8px;text-align:center;">Sen</th>
<th style="color:#777;font-size:12px;padding:8px;text-align:center;">Sel</th>
<th style="color:#777;font-size:12px;padding:8px;text-align:center;">Rab</th>
<th style="color:#777;font-size:12px;padding:8px;text-align:center;">Kam</th>
<th style="color:#ef5350;font-size:12px;padding:8px;text-align:center;">Jum</th>
<th style="color:#777;font-size:12px;padding:8px;text-align:center;">Sab</th>
</tr>
<?php
$tahun_kal  = (int)date('Y');
$bulan_kal  = (int)date('m');
$hari_ini_n = (int)date('j');
$total_hari = (int)date('t');
$awal_dow   = (int)date('w', mktime(0,0,0,$bulan_kal,1,$tahun_kal)); // 0=Min

// Ambil semua tanggal yang ada booking bulan ini
$tgl_booking = [];
$q_kal = mysqli_query($conn,"
    SELECT DISTINCT DAY(tanggal) as hari
    FROM pemesanan
    WHERE MONTH(tanggal)='$bulan_kal' AND YEAR(tanggal)='$tahun_kal'
");
while($rk = mysqli_fetch_assoc($q_kal)) $tgl_booking[] = (int)$rk['hari'];

$col = 0;
echo '<tr>';
// Offset kolom awal
for($i=0; $i<$awal_dow; $i++){
    echo '<td style="background:transparent;border:none;"></td>';
    $col++;
}
for($d=1; $d<=$total_hari; $d++){
    if($col==7){ echo '</tr><tr>'; $col=0; }
    $dow_hari = ($awal_dow + $d - 1) % 7;
    $is_past  = $d < $hari_ini_n;
    $is_today = $d === $hari_ini_n;
    $is_jumat = $dow_hari === 5;
    $ada_booking = in_array($d, $tgl_booking);

    $tgl_str = date('Y-m-d', mktime(0,0,0,$bulan_kal,$d,$tahun_kal));

    $style = '';
    $extra = '';
    if($is_today){
        $style = 'background:#0f1b2d;color:white;font-weight:700;';
        $extra = 'data-today="1"';
    } elseif($is_past || $is_jumat){
        $style = 'background:#e5e7eb;color:#9ca3af;cursor:not-allowed;opacity:.6;';
        $extra = 'data-disabled="1"';
    } elseif($ada_booking){
        $style = 'background:#dbeafe;color:#1e40af;font-weight:600;';
    }

    $dot = $ada_booking && !$is_past && !$is_jumat
         ? '<span style="display:block;width:5px;height:5px;border-radius:50%;background:#119cc2;margin:3px auto 0;"></span>'
         : '';

    echo "<td style='{$style}' {$extra} data-tgl='{$tgl_str}' onclick='klikKalender(this)'>{$d}{$dot}</td>";
    $col++;
}
// Sisa kolom
while($col<7){ echo '<td style="background:transparent;border:none;"></td>'; $col++; }
echo '</tr>';
?>
</table>
<div id="panelKalender" style="display:none;margin-top:20px;background:#f0f9ff;border:1px solid #bae6fd;border-radius:14px;padding:18px;">
  <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
    <h4 id="panelTanggalLabel" style="color:#0f1b2d;font-size:14px;"></h4>
    <button onclick="document.getElementById('panelKalender').style.display='none'"
      style="background:none;border:none;color:#9ca3af;font-size:18px;cursor:pointer;padding:0;line-height:1;">&#215;</button>
  </div>
  <div id="panelKalenderIsi">
    <p style="color:#9ca3af;font-size:13px;">Klik tanggal untuk melihat booking.</p>
  </div>
</div>

<div style="margin-top:20px;">

<h4 style="margin-bottom:15px;">
Booking Hari Ini
</h4>

<?php
mysqli_data_seek($q_booking_today,0);

while($b=mysqli_fetch_assoc($q_booking_today)):
?>

<div style="background:#f7f8fc;padding:12px;border-radius:12px;margin-bottom:10px;">

<?= htmlspecialchars($b['nama_pelanggan']) ?>
-
<?= htmlspecialchars($b['jam']) ?>

</div>

<?php endwhile; ?>

</div>

</div>

</div>

<?php endif; ?>


<!-- =========================================================
BOOKING
========================================================= -->

<?php if($page == 'booking'): ?>

<div class="form-box">

<div class="box-title">
<h3>Tambah Booking Pelanggan</h3>
</div>

<form method="POST">

<input type="hidden"
name="tambah_pelanggan"
value="1">

<input type="text"
name="nama_pelanggan"
placeholder="Nama Pelanggan"
required>

<input type="text"
name="no_telepon"
placeholder="No Telepon"
required>

<select name="id_paket" required>

<option value="">
Pilih Paket
</option>

<?php
mysqli_data_seek($q_paket,0);

while($p=mysqli_fetch_assoc($q_paket)):
?>

<option value="<?= $p['id_paket'] ?>">
<?= htmlspecialchars($p['nama_paket']) ?>
</option>

<?php endwhile; ?>

</select>

<input type="date"
name="tanggal"
required>

<input type="time"
name="jam"
required>

<button type="submit">
Tambah Booking
</button>

</form>

</div>

<?php endif; ?>


<!-- =========================================================
MENU
========================================================= -->

<?php if($page == 'menu'): ?>

<div class="form-box" style="margin-bottom:30px;">

<div class="box-title">
<h3>Tambah Menu</h3>
</div>

<form method="POST">

<input type="hidden"
name="aksi_menu"
value="tambah">

<input type="text"
name="nama_paket"
placeholder="Nama Paket"
required>

<input type="number"
name="harga"
placeholder="Harga"
required>

<input type="text"
name="deskripsi"
placeholder="Deskripsi"
required>

<button type="submit">
Tambah Paket
</button>

</form>

</div>

<?php
mysqli_data_seek($q_paket,0);

while($paket=mysqli_fetch_assoc($q_paket)):
?>

<div class="menu-item">

<div>

<h4><?= htmlspecialchars($paket['nama_paket']) ?></h4>

<p>
Rp <?= number_format($paket['harga'],0,',','.') ?>
</p>

</div>

<div class="action-btn">

<button
class="edit"
onclick="toggleEdit(<?= $paket['id_paket'] ?>)">
Edit
</button>

<form method="POST">

<input type="hidden"
name="aksi_menu"
value="hapus">

<input type="hidden"
name="id_paket"
value="<?= $paket['id_paket'] ?>">

<button type="submit" class="delete">
Delete
</button>

</form>

</div>

</div>

<div id="edit<?= $paket['id_paket'] ?>"
style="display:none; margin-bottom:20px;">

<form method="POST">

<input type="hidden"
name="aksi_menu"
value="edit">

<input type="hidden"
name="id_paket"
value="<?= $paket['id_paket'] ?>">

<input type="text"
name="nama_paket"
value="<?= htmlspecialchars($paket['nama_paket']) ?>">

<input type="number"
name="harga"
value="<?= $paket['harga'] ?>">

<input type="text"
name="deskripsi"
value="<?= htmlspecialchars($paket['deskripsi']) ?>">

<button type="submit">
Simpan Perubahan
</button>

</form>

</div>

<?php endwhile; ?>

<?php endif; ?>


<!-- =========================================================
RECAP
========================================================= -->

<?php if($page == 'recap'): ?>

<?php

$where = "WHERE p.status='lunas'";

if(!empty($_GET['bulan']) && !empty($_GET['tahun'])){

    $bulan = (int) $_GET['bulan'];
    $tahun = (int) $_GET['tahun'];

    $where .= "
    AND MONTH(p.tanggal)='$bulan'
    AND YEAR(p.tanggal)='$tahun'
    ";
}

$q_recap = mysqli_query($conn,"
SELECT p.*, pl.nama_paket, pl.harga
FROM pemesanan p
JOIN paket_layanan pl ON p.id_paket = pl.id_paket
$where
ORDER BY p.tanggal DESC
");

$q_total = mysqli_query($conn,"
SELECT SUM(pl.harga) as total
FROM pemesanan p
JOIN paket_layanan pl ON p.id_paket = pl.id_paket
$where
");

$total_recap = mysqli_fetch_assoc($q_total)['total'] ?? 0;

?>

<div class="cards">

<div class="card">
<h5>Income Hari Ini</h5>
<h2>Rp <?= number_format($income_today,0,',','.') ?></h2>
<p>Pendapatan hari ini</p>
</div>

<div class="card">
<h5>Income Bulan Ini</h5>
<h2>Rp <?= number_format($income_month,0,',','.') ?></h2>
<p>Pendapatan bulan sekarang</p>
</div>

<div class="card">
<h5>Total Recap</h5>
<h2>Rp <?= number_format($total_recap,0,',','.') ?></h2>
<p>Total hasil filter</p>
</div>

</div>

<div class="form-box"
style="max-width:100%; margin-bottom:30px;">

<div class="box-title">
<h3>Filter Recap Pendapatan</h3>
</div>

<form method="GET">

<input type="hidden"
name="page"
value="recap">

<select name="bulan">

<option value="">
Pilih Bulan
</option>

<?php for($i=1; $i<=12; $i++): ?>

<option value="<?= $i ?>"

<?= (isset($_GET['bulan']) && $_GET['bulan']==$i)
? 'selected'
: '' ?>>

<?= date('F', mktime(0,0,0,$i,1)) ?>

</option>

<?php endfor; ?>

</select>

<select name="tahun">

<option value="">
Pilih Tahun
</option>

<?php for($y=date('Y'); $y>=2023; $y--): ?>

<option value="<?= $y ?>"

<?= (isset($_GET['tahun']) && $_GET['tahun']==$y)
? 'selected'
: '' ?>>

<?= $y ?>

</option>

<?php endfor; ?>

</select>

<button type="submit">
Cari Recap
</button>

</form>

</div>

<div class="table-box">

<div class="box-title">
<h3>Data Pendapatan</h3>
</div>

<table>

<tr>
<th>No</th>
<th>Pelanggan</th>
<th>Paket</th>
<th>Tanggal</th>
<th>Total</th>
</tr>

<?php
$no = 1;

while($row=mysqli_fetch_assoc($q_recap)):
?>

<tr>

<td><?= $no++ ?></td>

<td>
<?= htmlspecialchars($row['nama_pelanggan']) ?>
</td>

<td>
<?= htmlspecialchars($row['nama_paket']) ?>
</td>

<td>
<?= date('d-m-Y', strtotime($row['tanggal'])) ?>
</td>

<td>
Rp <?= number_format($row['harga'],0,',','.') ?>
</td>

</tr>

<?php endwhile; ?>

</table>

</div>

<?php endif; ?>


<!-- =========================================================
ADMIN
========================================================= -->

<?php if($page == 'admin'): ?>

<div class="form-box">

<div class="box-title">
<h3>Tambah Admin Baru</h3>
</div>

<form method="POST" action="tambah_admin.php">

<input type="text"
name="no_telepon"
placeholder="No Telepon Admin"
required>

<input type="email"
name="email"
placeholder="Email Admin"
required>

<input type="password"
name="password"
placeholder="Password"
required>

<button type="submit">
Tambah Admin
</button>

</form>

</div>

<?php endif; ?>

</div>

</div>

<script>

function toggleEdit(id){

let x = document.getElementById("edit"+id);

if(x.style.display=="none"){
x.style.display="block";
}else{
x.style.display="none";
}

}

</script>



<script>
const BULAN_KAL = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function klikKalender(el) {
  if (el.dataset.disabled) return;
  const tgl = el.dataset.tgl;
  if (!tgl) return;

  // Reset highlight semua cell
  document.querySelectorAll('.calendar td[data-tgl]').forEach(t => {
    if (t.dataset.today) { t.style.background='#0f1b2d'; t.style.color='white'; return; }
    if (t.dataset.disabled) { t.style.background='#e5e7eb'; t.style.color='#9ca3af'; return; }
    t.style.background = '#f7f8fc';
    t.style.color = '';
  });
  if (!el.dataset.today) { el.style.background = '#119cc2'; el.style.color = 'white'; }

  const panel = document.getElementById('panelKalender');
  const label = document.getElementById('panelTanggalLabel');
  const isi   = document.getElementById('panelKalenderIsi');

  const parts = tgl.split('-');
  label.textContent = 'Booking ' + parseInt(parts[2]) + ' ' + BULAN_KAL[parseInt(parts[1])-1] + ' ' + parts[0];
  isi.innerHTML = '<p style="color:#9ca3af;font-size:13px;">Memuat...</p>';
  panel.style.display = 'block';

  fetch('dashboard_admin.php?ajax_booking=1&tgl=' + tgl)
    .then(r => r.json())
    .then(data => {
      if (!data.length) {
        isi.innerHTML = '<p style="color:#9ca3af;font-size:13px;">Tidak ada booking pada tanggal ini.</p>';
        return;
      }
      const statusColor = { belum_dicuci:'#f59e0b', diproses:'#3b82f6', selesai:'#22c55e' };
      const statusLabel = { belum_dicuci:'Belum Dicuci', diproses:'Diproses', selesai:'Selesai' };
      isi.innerHTML = data.map(b => `
        <div style="background:white;border-radius:10px;padding:12px 14px;margin-bottom:10px;border:1px solid #e0f2fe;">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
            <span style="font-weight:600;color:#0f1b2d;">${b.nama_pelanggan}</span>
            <span style="font-size:11px;font-weight:600;color:white;background:${statusColor[b.status_cuci]||'#9ca3af'};padding:2px 8px;border-radius:12px;">
              ${statusLabel[b.status_cuci]||b.status_cuci}
            </span>
          </div>
          <div style="font-size:12px;color:#555;">${b.nama_paket} &middot; <strong>${b.jam}</strong></div>
          <div style="font-size:11px;color:#9ca3af;margin-top:4px;">Plat: ${b.plat_mobil}</div>
        </div>
      `).join('');
    })
    .catch(() => {
      isi.innerHTML = '<p style="color:#ef5350;font-size:13px;">Gagal memuat data.</p>';
    });
}
</script>

</body>
</html>