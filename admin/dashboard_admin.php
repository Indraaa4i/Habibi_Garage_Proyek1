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

    // Ambil data pemesanan lengkap
    $q  = mysqli_query($conn,"
        SELECT p.*, pl.nama_paket
        FROM pemesanan p
        JOIN paket_layanan pl ON p.id_paket = pl.id_paket
        WHERE p.id_pemesanan='$id'
    ");
    $data_pesan = mysqli_fetch_assoc($q);
    $cur = $data_pesan['status_cuci'] ?? 'belum_dicuci';

    $next = $cur === 'belum_dicuci' ? 'diproses'
          : ($cur === 'diproses'    ? 'selesai'
          :                          'selesai');

    mysqli_query($conn,"UPDATE pemesanan SET status_cuci='$next' WHERE id_pemesanan='$id'");

    // Jika baru saja jadi selesai, kirim WA
    if($next === 'selesai'){
        $nama   = urlencode($data_pesan['nama_pelanggan']);
        $paket  = urlencode($data_pesan['nama_paket']);
        $plat   = urlencode($data_pesan['plat_mobil']);
        $no_hp  = preg_replace('/[^0-9]/', '', $data_pesan['no_telepon']);
        // Ubah awalan 0 jadi 62
        if(substr($no_hp, 0, 1) === '0') $no_hp = '62' . substr($no_hp, 1);

        $pesan = urlencode(
            "Halo, {$data_pesan['nama_pelanggan']}! 👋

" .
            "Mobil Anda dengan plat *{$data_pesan['plat_mobil']}* sudah selesai dicuci. ✅
" .
            "Paket: *{$data_pesan['nama_paket']}*

" .
            "Silakan datang untuk mengambil kendaraan Anda.

" .
            "Terima kasih telah mempercayai *Habibi Garage*! 🚗✨"
        );

        $wa_url = "https://wa.me/{$no_hp}?text={$pesan}";

        // Redirect ke halaman perantara yang buka WA lalu balik ke dashboard
        header("Location: dashboard_admin.php?wa_url=" . urlencode($wa_url));
        exit;
    }

    header("Location: dashboard_admin.php");
    exit;
}



/* =========================================================
   KONFIRMASI / TOLAK PEMBAYARAN
========================================================= */
if(isset($_POST['aksi_konfirmasi'])){
    $id  = (int)$_POST['id_pemesanan'];
    $aksi = $_POST['aksi_konfirmasi'];

    if($aksi === 'konfirmasi'){
        mysqli_query($conn,"UPDATE pemesanan SET status='lunas' WHERE id_pemesanan='$id'");
    } elseif($aksi === 'tolak'){
        mysqli_query($conn,"UPDATE pemesanan SET status='dibatalkan', bukti_bayar=NULL WHERE id_pemesanan='$id'");
    }

    header("Location: dashboard_admin.php?page=konfirmasi");
    exit;
}

/* =========================================================
   TANDAI REFUND SELESAI
========================================================= */
if(isset($_POST['selesai_refund'])){
    $id = (int)$_POST['id_pemesanan'];
    @mysqli_query($conn,"UPDATE pemesanan SET refund_status='selesai' WHERE id_pemesanan='$id'");
    header("Location: dashboard_admin.php?page=konfirmasi");
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
   TAMBAH PELANGGAN WALK-IN
========================================================= */
if(isset($_POST['tambah_walkin'])){

    $nama    = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $telepon = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $plat    = strtoupper(mysqli_real_escape_string($conn, $_POST['plat_mobil']));
    $paket   = (int)$_POST['id_paket'];
    $tanggal = date('Y-m-d'); // hari ini
    $jam     = mysqli_real_escape_string($conn, $_POST['jam']);

    mysqli_query($conn,"
        INSERT INTO pemesanan(
            nama_pelanggan, no_telepon, plat_mobil,
            id_paket, tanggal, jam,
            status, status_cuci, created_at
        )
        VALUES(
            '$nama', '$telepon', '$plat',
            '$paket', '$tanggal', '$jam',
            'lunas', 'belum_dicuci', NOW()
        )
    ");

    $id_baru = mysqli_insert_id($conn);
    header("Location: dashboard_admin.php?page=walkin&sukses=$id_baru");
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

<a href="?page=walkin"
class="<?= $page=='walkin' ? 'active' : '' ?>">
Walk-In
</a>

<a href="?page=konfirmasi"
class="<?= $page=='konfirmasi' ? 'active' : '' ?>"
style="position:relative;">
Konfirmasi
<?php if($total_pending > 0): ?>
<span style="position:absolute;top:4px;right:4px;background:#ef5350;color:white;border-radius:50%;font-size:10px;width:17px;height:17px;display:flex;align-items:center;justify-content:center;font-weight:700;"><?= $total_pending ?></span>
<?php endif; ?>
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

<?php if(!empty($_GET['wa_url'])): ?>
<script>
  // Buka WhatsApp di tab baru
  window.open(<?= json_encode(urldecode($_GET['wa_url'])) ?>, '_blank');
</script>
<?php endif; ?>


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

<?php
// Ambil slot yang sudah terpesan hari ini untuk ditampilkan awal
$slot_booked_today = [];
$q_slot_today = mysqli_query($conn,"SELECT jam FROM pemesanan WHERE tanggal='".date('Y-m-d')."'");
while($rs = mysqli_fetch_assoc($q_slot_today)) $slot_booked_today[] = $rs['jam'];
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;align-items:start;">

<!-- KIRI: Form -->
<div class="form-box" style="max-width:100%;">
<div class="box-title">
<h3>Tambah Booking Pelanggan</h3>
</div>

<form method="POST" id="formBookingAdmin">
<input type="hidden" name="tambah_pelanggan" value="1">

<input type="text" name="nama_pelanggan" placeholder="Nama Pelanggan" required>
<input type="text" name="no_telepon" placeholder="No Telepon" required>

<select name="id_paket" required>
<option value="">Pilih Paket</option>
<?php
mysqli_data_seek($q_paket,0);
while($p=mysqli_fetch_assoc($q_paket)):
?>
<option value="<?= $p['id_paket'] ?>"><?= htmlspecialchars($p['nama_paket']) ?></option>
<?php endwhile; ?>
</select>

<!-- Tanggal & jam disembunyikan, diisi otomatis dari kalender -->
<input type="hidden" name="tanggal" id="inputTanggal" required>
<input type="hidden" name="jam" id="inputJam" required>

<!-- Tampilan tanggal & jam yang dipilih -->
<div id="pilihanInfo" style="background:#f0f9ff;border:1px solid #bae6fd;border-radius:12px;padding:14px;font-size:14px;color:#0f1b2d;display:none;">
  <div style="margin-bottom:4px;">📅 <strong id="labelTanggal">-</strong></div>
  <div>🕐 <strong id="labelJam">-</strong></div>
</div>
<div id="pilihanWarning" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:12px;font-size:13px;color:#92400e;display:none;">
  ⚠️ Pilih tanggal dan jam dari kalender di sebelah kanan.
</div>

<button type="submit" onclick="return validasiForm()">Tambah Booking</button>
</form>
</div>

<!-- KANAN: Kalender + Slot Jam -->
<div class="form-box" style="max-width:100%;">
<div class="box-title">
<h3>Pilih Tanggal & Jam</h3>
</div>

<!-- Header navigasi bulan -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px;">
  <button onclick="gantibulan(-1)" style="background:#f1f5f9;color:#0f1b2d;border-radius:8px;padding:6px 14px;font-size:18px;border:none;cursor:pointer;">‹</button>
  <span id="labelBulanTahun" style="font-weight:700;font-size:15px;color:#0f1b2d;"></span>
  <button onclick="gantibulan(1)" style="background:#f1f5f9;color:#0f1b2d;border-radius:8px;padding:6px 14px;font-size:18px;border:none;cursor:pointer;">›</button>
</div>

<!-- Kalender -->
<table style="width:100%;border-spacing:4px;border-collapse:separate;">
<tr>
<?php foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $h): ?>
<th style="text-align:center;font-size:11px;color:<?= $h==='Jum' ? '#ef5350' : '#9ca3af' ?>;padding:4px;font-weight:600;"><?= $h ?></th>
<?php endforeach; ?>
</tr>
</table>
<div id="gridKalender" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:16px;"></div>

<!-- Slot jam -->
<div style="border-top:1px solid #eee;padding-top:14px;">
  <div style="font-size:13px;font-weight:700;color:#0f1b2d;margin-bottom:10px;">
    Slot Jam — <span id="labelTanggalSlot" style="color:#119cc2;">pilih tanggal dulu</span>
  </div>
  <div id="gridSlot" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
    <div style="grid-column:span 2;text-align:center;color:#9ca3af;font-size:13px;padding:16px 0;">
      Klik tanggal untuk melihat slot jam
    </div>
  </div>
</div>

<!-- Legenda -->
<div style="display:flex;gap:16px;margin-top:14px;flex-wrap:wrap;">
  <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#555;">
    <span style="width:12px;height:12px;border-radius:3px;background:#e2f4ff;border:1px solid #119cc2;display:inline-block;"></span> Tersedia
  </div>
  <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#555;">
    <span style="width:12px;height:12px;border-radius:3px;background:#0f1b2d;display:inline-block;"></span> Dipilih
  </div>
  <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:#555;">
    <span style="width:12px;height:12px;border-radius:3px;background:#fee2e2;border:1px solid #ef4444;display:inline-block;"></span> Penuh
  </div>
</div>

</div><!-- /kanan -->
</div><!-- /grid -->

<script>
const SEMUA_SLOT = [
  '08:00 - 09:00','09:00 - 10:00','10:00 - 11:00','11:00 - 12:00',
  '13:00 - 14:00','14:00 - 15:00','15:00 - 16:00'
];
const BULAN_ADM = ['Januari','Februari','Maret','April','Mei','Juni',
                   'Juli','Agustus','September','Oktober','November','Desember'];
const HARI_ADM  = ['Min','Sen','Sel','Rab','Kam','Jum','Sab'];

let tglAktif   = null;
let jamAktif   = null;
let bulanView  = new Date().getMonth();
let tahunView  = new Date().getFullYear();

function gantibulan(arah){
  bulanView += arah;
  if(bulanView < 0){ bulanView = 11; tahunView--; }
  if(bulanView > 11){ bulanView = 0; tahunView++; }
  renderKalender();
}

function renderKalender(){
  document.getElementById('labelBulanTahun').textContent =
    BULAN_ADM[bulanView] + ' ' + tahunView;

  const grid   = document.getElementById('gridKalender');
  const today  = new Date(); today.setHours(0,0,0,0);
  const awal   = new Date(tahunView, bulanView, 1).getDay();
  const total  = new Date(tahunView, bulanView+1, 0).getDate();

  let html = '';
  // Offset awal
  for(let i=0;i<awal;i++) html += '<div></div>';

  for(let d=1;d<=total;d++){
    const tgl  = new Date(tahunView, bulanView, d);
    const dow  = tgl.getDay();
    const tglStr = tahunView+'-'+String(bulanView+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
    const isPast   = tgl < today;
    const isJumat  = dow === 5;
    const isToday  = tgl.getTime() === today.getTime();
    const isAktif  = tglStr === tglAktif;

    let bg='#f7f8fc', color='#0f1b2d', cursor='pointer', border='1px solid transparent', opacity='1';
    if(isAktif){ bg='#0f1b2d'; color='white'; }
    else if(isToday){ bg='#e0f2fe'; border='1px solid #119cc2'; }
    else if(isPast||isJumat){ bg='#e5e7eb'; color='#9ca3af'; cursor='not-allowed'; opacity='.6'; }

    const disabled = (isPast||isJumat) ? 'disabled' : '';
    html += `<div onclick="pilihTanggal('${tglStr}','${d} ${BULAN_ADM[bulanView]}')" ${disabled}
      style="text-align:center;padding:8px 4px;border-radius:8px;font-size:13px;font-weight:600;
             background:${bg};color:${color};cursor:${cursor};border:${border};opacity:${opacity};
             transition:.15s;">
      ${d}
    </div>`;
  }
  grid.innerHTML = html;
}

function pilihTanggal(tglStr, labelStr){
  tglAktif = tglStr;
  jamAktif = null;
  document.getElementById('inputTanggal').value = tglStr;
  document.getElementById('inputJam').value = '';
  document.getElementById('labelTanggalSlot').textContent = labelStr;
  document.getElementById('pilihanInfo').style.display = 'none';
  document.getElementById('labelTanggal').textContent = labelStr;
  renderKalender();
  muatSlot(tglStr);
}

function muatSlot(tglStr){
  const grid = document.getElementById('gridSlot');
  grid.innerHTML = '<div style="grid-column:span 2;text-align:center;color:#9ca3af;font-size:13px;padding:16px 0;">Memuat slot...</div>';

  fetch('dashboard_admin.php?ajax_booking=1&tgl=' + tglStr)
    .then(r => r.json())
    .then(data => {
      const terpesan = data.map(b => b.jam);
      grid.innerHTML = SEMUA_SLOT.map(slot => {
        const penuh   = terpesan.includes(slot);
        const dipilih = slot === jamAktif;
        let bg = penuh ? '#fee2e2' : (dipilih ? '#0f1b2d' : '#e2f4ff');
        let color = penuh ? '#ef4444' : (dipilih ? 'white' : '#0f1b2d');
        let border = penuh ? '1px solid #ef4444' : (dipilih ? 'none' : '1px solid #119cc2');
        let cursor = penuh ? 'not-allowed' : 'pointer';
        let strike = penuh ? 'text-decoration:line-through;' : '';
        const disabled = penuh ? 'disabled' : '';
        return `<div onclick="pilihJam('${slot}')" ${disabled}
          style="padding:10px;border-radius:10px;text-align:center;font-size:13px;font-weight:600;
                 background:${bg};color:${color};border:${border};cursor:${cursor};${strike}transition:.15s;">
          ${slot}${penuh ? '<br><span style="font-size:10px;font-weight:400;">Penuh</span>' : ''}
        </div>`;
      }).join('');
    })
    .catch(() => {
      grid.innerHTML = '<div style="grid-column:span 2;color:#ef4444;font-size:13px;text-align:center;">Gagal memuat slot.</div>';
    });
}

function pilihJam(slot){
  jamAktif = slot;
  document.getElementById('inputJam').value = slot;
  // Update tampilan info
  const info = document.getElementById('pilihanInfo');
  document.getElementById('labelTanggal').textContent =
    document.getElementById('labelTanggalSlot').textContent;
  document.getElementById('labelJam').textContent = slot;
  info.style.display = 'block';
  document.getElementById('pilihanWarning').style.display = 'none';
  // Re-render slot biar highlight update
  muatSlot(tglAktif);
}

function validasiForm(){
  if(!tglAktif || !jamAktif){
    document.getElementById('pilihanWarning').style.display = 'block';
    return false;
  }
  return true;
}

// Init
renderKalender();
</script>

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
<button onclick="exportExcel()" style="
  background:#1d6f42;color:white;border:none;
  border-radius:10px;padding:10px 20px;
  font-size:13px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;gap:8px;
  transition:.2s;font-family:'Poppins',sans-serif;"
  onmouseover="this.style.background='#155734'"
  onmouseout="this.style.background='#1d6f42'">
  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/>
    <line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/>
  </svg>
  Export Excel
</button>
</div>

<?php
// Kumpulkan data recap ke array PHP untuk di-embed ke JS
$recap_rows = [];
mysqli_data_seek($q_recap, 0);
while($r_tmp = mysqli_fetch_assoc($q_recap)) $recap_rows[] = $r_tmp;
?>

<table id="tabelRecap">

<tr>
<th>No</th>
<th>ID Pemesanan</th>
<th>Nama Pelanggan</th>
<th>No. Telepon</th>
<th>Plat Mobil</th>
<th>Paket Layanan</th>
<th>Tanggal</th>
<th>Jam</th>
<th>Total (Rp)</th>
</tr>

<?php foreach($recap_rows as $i => $row): ?>
<tr>
<td><?= $i+1 ?></td>
<td><?= $row['id_pemesanan'] ?></td>
<td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
<td><?= htmlspecialchars($row['no_telepon']) ?></td>
<td><?= htmlspecialchars(strtoupper($row['plat_mobil'])) ?></td>
<td><?= htmlspecialchars($row['nama_paket']) ?></td>
<td><?= date('d-m-Y', strtotime($row['tanggal'])) ?></td>
<td><?= htmlspecialchars($row['jam']) ?></td>
<td>Rp <?= number_format($row['harga'],0,',','.') ?></td>
</tr>
<?php endforeach; ?>

<?php if(empty($recap_rows)): ?>
<tr><td colspan="9" style="text-align:center;color:#9ca3af;padding:24px;">Tidak ada data untuk filter ini.</td></tr>
<?php else: ?>
<!-- Baris total -->
<tr style="font-weight:700;background:#f0fdf4;">
<td colspan="8" style="text-align:right;">TOTAL PENDAPATAN</td>
<td>Rp <?= number_format($total_recap,0,',','.') ?></td>
</tr>
<?php endif; ?>

</table>

</div>

<!-- SheetJS untuk export Excel -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script>
function exportExcel() {
  <?php
  // Label filter untuk nama file
  $label_filter = '';
  if(!empty($_GET['bulan']) && !empty($_GET['tahun'])){
      $label_filter = date('F', mktime(0,0,0,(int)$_GET['bulan'],1)) . '_' . (int)$_GET['tahun'];
  } else {
      $label_filter = 'Semua';
  }
  ?>

  const namaFile = 'Recap_Habibi_Garage_<?= $label_filter ?>.xlsx';

  // Header informasi di atas tabel
  const info = [
    ['REKAP PENDAPATAN — HABIBI GARAGE'],
    ['Periode', '<?= !empty($_GET['bulan']) && !empty($_GET['tahun']) ? date('F Y', mktime(0,0,0,(int)$_GET['bulan'],1,(int)$_GET['tahun'])) : "Semua Data" ?>'],
    ['Tanggal Export', '<?= date('d/m/Y H:i') ?>'],
    ['Total Pendapatan', 'Rp <?= number_format($total_recap, 0, ',', '.') ?>'],
    [], // baris kosong
    // Header kolom
    ['No','ID Pemesanan','Nama Pelanggan','No. Telepon','Plat Mobil','Paket Layanan','Tanggal','Jam','Total (Rp)']
  ];

  // Data baris
  const dataRows = <?php
    $js_rows = [];
    foreach($recap_rows as $i => $r){
        $js_rows[] = [
            $i+1,
            $r['id_pemesanan'],
            $r['nama_pelanggan'],
            $r['no_telepon'],
            strtoupper($r['plat_mobil']),
            $r['nama_paket'],
            date('d-m-Y', strtotime($r['tanggal'])),
            $r['jam'],
            (int)$r['harga']
        ];
    }
    echo json_encode($js_rows);
  ?>;

  // Baris total
  const totalRow = ['','','','','','','','TOTAL', <?= (int)$total_recap ?>];

  const allRows = [...info, ...dataRows, [], totalRow];

  const ws = XLSX.utils.aoa_to_sheet(allRows);

  // Lebar kolom
  ws['!cols'] = [
    {wch:5},  // No
    {wch:14}, // ID
    {wch:22}, // Nama
    {wch:16}, // Telepon
    {wch:12}, // Plat
    {wch:22}, // Paket
    {wch:12}, // Tanggal
    {wch:18}, // Jam
    {wch:16}, // Harga
  ];

  // Format angka kolom harga (kolom I = index 8, mulai baris data ke-7)
  const dataStart = info.length + 1; // baris pertama data (0-indexed)
  for(let r = dataStart; r < dataStart + dataRows.length; r++){
    const cell = XLSX.utils.encode_cell({r, c: 8});
    if(ws[cell]) {
      ws[cell].t = 'n';
      ws[cell].z = '#,##0';
    }
  }
  // Format total
  const totalCell = XLSX.utils.encode_cell({r: dataStart + dataRows.length + 1, c: 8});
  if(ws[totalCell]){ ws[totalCell].t = 'n'; ws[totalCell].z = '#,##0'; }

  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Rekap Pendapatan');
  XLSX.writeFile(wb, namaFile);
}
</script>

<?php endif; ?>


<!-- =========================================================
WALK-IN (PELANGGAN DATANG LANGSUNG)
========================================================= -->

<?php if($page == 'walkin'): ?>

<?php
// Ambil slot jam yang sudah terpakai hari ini
$slot_walkin_booked = [];
$q_slot_walkin = mysqli_query($conn,"SELECT jam FROM pemesanan WHERE tanggal='".date('Y-m-d')."'");
while($rs = mysqli_fetch_assoc($q_slot_walkin)) $slot_walkin_booked[] = $rs['jam'];

$semua_slot_walkin = [
    '08:00 - 09:00','09:00 - 10:00','10:00 - 11:00','11:00 - 12:00',
    '13:00 - 14:00','14:00 - 15:00','15:00 - 16:00'
];

// Walk-in hari ini
$q_walkin_today = mysqli_query($conn,"
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.tanggal = CURDATE() AND p.status = 'lunas'
    ORDER BY p.created_at DESC
");
?>

<div style="margin-bottom:24px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
  <div>
    <h2 style="font-size:20px;font-weight:700;color:#0f1b2d;">Pelanggan Walk-In</h2>
    <p style="color:#777;font-size:14px;">Tambahkan pelanggan yang datang langsung ke bengkel. Mereka bisa melacak progres cuci di halaman profil.</p>
  </div>
</div>

<?php if(isset($_GET['sukses']) && is_numeric($_GET['sukses'])): ?>
<div style="background:#dcfce7;border:1px solid #86efac;border-radius:14px;padding:16px 20px;margin-bottom:24px;display:flex;align-items:center;gap:12px;">
  <span style="font-size:22px;">✅</span>
  <div>
    <strong style="color:#15803d;">Pelanggan walk-in berhasil ditambahkan!</strong>
    <div style="font-size:13px;color:#16a34a;margin-top:2px;">
      ID Pemesanan: <strong>#<?= (int)$_GET['sukses'] ?></strong> — Status langsung <em>Lunas</em> & masuk antrian cuci.
      <br>Pelanggan bisa lihat progres di <strong>profil.php</strong> dengan no. telepon & plat yang sama.
    </div>
  </div>
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:25px;align-items:start;">

<!-- FORM TAMBAH WALK-IN -->
<div style="background:white;padding:28px;border-radius:22px;box-shadow:0 8px 20px rgba(0,0,0,0.07);">
  <h3 style="font-size:16px;font-weight:700;color:#0f1b2d;margin-bottom:20px;">
    🚗 Tambah Pelanggan Walk-In
  </h3>

  <form method="POST" id="formWalkin" style="display:flex;flex-direction:column;gap:14px;">
    <input type="hidden" name="tambah_walkin" value="1">
    <input type="hidden" name="jam" id="walkinJam" required>

    <div>
      <label style="font-size:12px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Nama Pelanggan</label>
      <input type="text" name="nama_pelanggan" placeholder="Nama lengkap" required
        style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid #e5e7eb;font-size:14px;outline:none;font-family:inherit;">
    </div>

    <div>
      <label style="font-size:12px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">No. Telepon</label>
      <input type="text" name="no_telepon" placeholder="08xxxxxxxxxx" required
        style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid #e5e7eb;font-size:14px;outline:none;font-family:inherit;">
    </div>

    <div>
      <label style="font-size:12px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Plat Nomor Kendaraan</label>
      <input type="text" name="plat_mobil" placeholder="Contoh: B 1234 XY" required
        style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid #e5e7eb;font-size:14px;outline:none;font-family:inherit;text-transform:uppercase;"
        oninput="this.value=this.value.toUpperCase()">
    </div>

    <div>
      <label style="font-size:12px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:6px;">Paket Layanan</label>
      <select name="id_paket" required
        style="width:100%;padding:12px 14px;border-radius:10px;border:1.5px solid #e5e7eb;font-size:14px;outline:none;font-family:inherit;background:white;">
        <option value="">Pilih Paket</option>
        <?php mysqli_data_seek($q_paket,0); while($p=mysqli_fetch_assoc($q_paket)): ?>
        <option value="<?= $p['id_paket'] ?>"><?= htmlspecialchars($p['nama_paket']) ?> — Rp <?= number_format($p['harga'],0,',','.') ?></option>
        <?php endwhile; ?>
      </select>
    </div>

    <div>
      <label style="font-size:12px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;display:block;margin-bottom:8px;">Slot Jam Hari Ini</label>
      <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;" id="slotGrid">
        <?php foreach($semua_slot_walkin as $slot):
          $terpakai = in_array($slot, $slot_walkin_booked);
        ?>
        <button type="button"
          class="slot-btn"
          data-slot="<?= $slot ?>"
          onclick="pilihSlotWalkin(this, '<?= $slot ?>')"
          <?= $terpakai ? 'disabled' : '' ?>
          style="padding:10px 8px;border-radius:10px;font-size:12px;font-weight:700;border:2px solid <?= $terpakai ? '#fecaca' : '#bae6fd' ?>;
                 background:<?= $terpakai ? '#fee2e2' : '#e0f2fe' ?>;color:<?= $terpakai ? '#ef4444' : '#0f1b2d' ?>;
                 cursor:<?= $terpakai ? 'not-allowed' : 'pointer' ?>;text-decoration:<?= $terpakai ? 'line-through' : 'none' ?>;
                 transition:.15s;font-family:inherit;">
          <?= $slot ?><?= $terpakai ? '<br><span style="font-size:10px;font-weight:400;">Terpakai</span>' : '' ?>
        </button>
        <?php endforeach; ?>
      </div>
      <div id="slotPilihInfo" style="display:none;margin-top:10px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:10px 14px;font-size:13px;color:#15803d;">
        ✅ Slot dipilih: <strong id="slotLabel">-</strong>
      </div>
      <div id="slotWarning" style="display:none;margin-top:8px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:10px 14px;font-size:12px;color:#92400e;">
        ⚠️ Pilih slot jam terlebih dahulu
      </div>
    </div>

    <!-- Info status walk-in -->
    <div style="background:#f0fdf4;border:1px solid #86efac;border-radius:12px;padding:14px 16px;font-size:13px;color:#15803d;">
      ✅ <strong>Walk-in = langsung Lunas.</strong> Booking tidak melalui proses pembayaran online. Admin bertanggung jawab atas konfirmasi pembayaran tunai.
    </div>

    <button type="submit" onclick="return validasiWalkin()"
      style="background:#0f1b2d;color:white;border:none;border-radius:12px;padding:14px;font-size:14px;font-weight:700;cursor:pointer;transition:.2s;font-family:inherit;"
      onmouseover="this.style.background='#1a2f4a'" onmouseout="this.style.background='#0f1b2d'">
      + Tambahkan Pelanggan Walk-In
    </button>
  </form>
</div>

<!-- DAFTAR WALK-IN HARI INI -->
<div style="background:white;padding:28px;border-radius:22px;box-shadow:0 8px 20px rgba(0,0,0,0.07);">
  <h3 style="font-size:16px;font-weight:700;color:#0f1b2d;margin-bottom:6px;">
    📋 Walk-In Hari Ini
  </h3>
  <p style="font-size:13px;color:#9ca3af;margin-bottom:20px;"><?= date('d F Y') ?></p>

  <?php if(mysqli_num_rows($q_walkin_today) == 0): ?>
  <div style="text-align:center;padding:40px 20px;color:#9ca3af;">
    <div style="font-size:40px;margin-bottom:10px;">🚗</div>
    <p style="font-size:14px;">Belum ada walk-in hari ini</p>
  </div>
  <?php endif; ?>

  <div style="display:flex;flex-direction:column;gap:14px;">
  <?php while($wi = mysqli_fetch_assoc($q_walkin_today)):
    $sc = $wi['status_cuci'];
    $step = $sc === 'belum_dicuci' ? 1 : ($sc === 'diproses' ? 2 : 3);
    $badge_color = $sc === 'belum_dicuci' ? '#f59e0b' : ($sc === 'diproses' ? '#3b82f6' : '#22c55e');
    $badge_label = $sc === 'belum_dicuci' ? 'Antrian' : ($sc === 'diproses' ? 'Dicuci' : 'Selesai');
  ?>
  <div style="background:#f8fafc;border-radius:14px;padding:16px 18px;border-left:4px solid <?= $badge_color ?>;">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:10px;">
      <div>
        <div style="font-weight:700;font-size:14px;color:#0f1b2d;"><?= htmlspecialchars($wi['nama_pelanggan']) ?></div>
        <div style="font-size:12px;color:#777;margin-top:2px;">
          📞 <?= htmlspecialchars($wi['no_telepon']) ?> &nbsp;·&nbsp;
          🚘 <strong><?= htmlspecialchars(strtoupper($wi['plat_mobil'])) ?></strong>
        </div>
        <div style="font-size:12px;color:#777;margin-top:2px;">
          🕐 <?= htmlspecialchars($wi['jam']) ?> &nbsp;·&nbsp;
          <?= htmlspecialchars($wi['nama_paket']) ?>
        </div>
      </div>
      <span style="font-size:11px;font-weight:700;color:white;background:<?= $badge_color ?>;padding:4px 10px;border-radius:20px;white-space:nowrap;">
        <?= $badge_label ?>
      </span>
    </div>

    <!-- Progress bar mini -->
    <div style="display:flex;gap:6px;align-items:center;">
      <?php
      $steps_wi = ['Antrian','Dicuci','Selesai'];
      foreach($steps_wi as $i => $s_label):
        $done = $step >= ($i+1);
      ?>
      <div style="flex:1;height:6px;border-radius:3px;background:<?= $done ? $badge_color : '#e5e7eb' ?>;transition:.3s;"></div>
      <?php endforeach; ?>
    </div>
    <div style="font-size:11px;color:#9ca3af;margin-top:5px;">
      Progress: Step <?= $step ?>/3 — <?= $badge_label ?>
    </div>
  </div>
  <?php endwhile; ?>
  </div>
</div>

</div><!-- /grid -->

<script>
function pilihSlotWalkin(btn, slot) {
  // Reset semua
  document.querySelectorAll('.slot-btn:not([disabled])').forEach(b => {
    b.style.background = '#e0f2fe';
    b.style.borderColor = '#bae6fd';
    b.style.color = '#0f1b2d';
  });
  // Aktifkan yang dipilih
  btn.style.background = '#0f1b2d';
  btn.style.borderColor = '#0f1b2d';
  btn.style.color = 'white';

  document.getElementById('walkinJam').value = slot;
  document.getElementById('slotLabel').textContent = slot;
  document.getElementById('slotPilihInfo').style.display = 'block';
  document.getElementById('slotWarning').style.display = 'none';
}

function validasiWalkin() {
  if (!document.getElementById('walkinJam').value) {
    document.getElementById('slotWarning').style.display = 'block';
    return false;
  }
  return true;
}
</script>

<?php endif; ?>


<!-- =========================================================
KONFIRMASI PEMBAYARAN
========================================================= -->

<?php if($page == 'konfirmasi'): ?>

<?php
$q_konfirmasi = mysqli_query($conn,"
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.status='pending' AND p.bukti_bayar IS NOT NULL AND p.bukti_bayar != ''
    ORDER BY p.created_at ASC
");

// Query refund yang menunggu diproses
$q_refund = mysqli_query($conn,"
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.status='dibatalkan'
    AND p.refund_status='menunggu'
    AND p.refund_nomor_rek IS NOT NULL
    ORDER BY p.created_at ASC
");
$total_refund = mysqli_num_rows($q_refund);
?>

<div style="margin-bottom:24px;">
<h2 style="font-size:20px;font-weight:700;color:#0f1b2d;">Konfirmasi Pembayaran</h2>
<p style="color:#777;font-size:14px;">Verifikasi bukti transfer dari pelanggan sebelum booking dikonfirmasi.</p>
</div>

<?php if(mysqli_num_rows($q_konfirmasi) == 0): ?>
<div style="background:white;border-radius:22px;padding:50px;text-align:center;box-shadow:0 8px 20px rgba(0,0,0,0.06);">
  <div style="font-size:48px;margin-bottom:12px;">✅</div>
  <h3 style="color:#0f1b2d;margin-bottom:8px;">Tidak ada pembayaran menunggu</h3>
  <p style="color:#9ca3af;font-size:14px;">Semua pembayaran sudah dikonfirmasi.</p>
</div>
<?php endif; ?>

<div style="display:grid;gap:20px;">
<?php while($row = mysqli_fetch_assoc($q_konfirmasi)): ?>
<div style="background:white;border-radius:22px;padding:28px;box-shadow:0 8px 20px rgba(0,0,0,0.07);display:grid;grid-template-columns:1fr auto;gap:24px;align-items:start;">

  <!-- INFO + BUKTI -->
  <div style="display:grid;grid-template-columns:220px 1fr;gap:20px;align-items:start;">

    <!-- Bukti bayar (gambar dari Cloudinary) -->
    <div>
      <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Bukti Transfer</p>
      <a href="<?= htmlspecialchars($row['bukti_bayar']) ?>" target="_blank" title="Klik untuk perbesar">
        <img
          src="<?= htmlspecialchars($row['bukti_bayar']) ?>"
          alt="Bukti Bayar"
          style="width:100%;max-width:220px;border-radius:14px;border:2px solid #e5e7eb;object-fit:cover;cursor:zoom-in;transition:.2s;"
          onmouseover="this.style.borderColor='#119cc2'"
          onmouseout="this.style.borderColor='#e5e7eb'"
        >
      </a>
      <p style="font-size:11px;color:#9ca3af;margin-top:6px;text-align:center;">Klik gambar untuk perbesar</p>
    </div>

    <!-- Detail transaksi -->
    <div>
      <p style="font-size:11px;font-weight:700;color:#9ca3af;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">Detail Transaksi</p>

      <table style="width:100%;font-size:13px;border-collapse:collapse;">
        <tr><td style="color:#777;padding:5px 0;width:140px;">ID Pemesanan</td><td style="font-weight:600;color:#0f1b2d;">#<?= $row['id_pemesanan'] ?></td></tr>
        <tr><td style="color:#777;padding:5px 0;">Nama Pelanggan</td><td style="font-weight:600;color:#0f1b2d;"><?= htmlspecialchars($row['nama_pelanggan']) ?></td></tr>
        <tr><td style="color:#777;padding:5px 0;">No. Telepon</td><td style="color:#0f1b2d;"><?= htmlspecialchars($row['no_telepon']) ?></td></tr>
        <tr><td style="color:#777;padding:5px 0;">Plat Mobil</td><td style="font-weight:600;color:#0f1b2d;text-transform:uppercase;"><?= htmlspecialchars($row['plat_mobil']) ?></td></tr>
        <tr><td style="color:#777;padding:5px 0;">Paket</td><td style="color:#119cc2;font-weight:600;"><?= htmlspecialchars($row['nama_paket']) ?></td></tr>
        <tr><td style="color:#777;padding:5px 0;">Tanggal Booking</td><td style="color:#0f1b2d;"><?= date('d M Y', strtotime($row['tanggal'])) ?></td></tr>
        <tr><td style="color:#777;padding:5px 0;">Jam</td><td style="color:#0f1b2d;"><?= htmlspecialchars($row['jam']) ?></td></tr>
        <tr>
          <td style="color:#777;padding:5px 0;">Total Bayar</td>
          <td style="font-size:18px;font-weight:700;color:#16a34a;">Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
        </tr>
      </table>

      <div style="margin-top:14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:10px 14px;font-size:12px;color:#92400e;">
        ⏳ Menunggu konfirmasi sejak: <strong><?= date('d M Y H:i', strtotime($row['created_at'])) ?></strong>
      </div>
    </div>
  </div>

  <!-- TOMBOL AKSI -->
  <div style="display:flex;flex-direction:column;gap:12px;min-width:160px;">
    <!-- Konfirmasi -->
    <form method="POST" onsubmit="return confirm('Konfirmasi pembayaran ini? Booking akan langsung aktif.')">
      <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
      <input type="hidden" name="aksi_konfirmasi" value="konfirmasi">
      <button type="submit" style="background:#16a34a;color:white;border:none;border-radius:14px;padding:14px 20px;font-size:14px;font-weight:700;width:100%;cursor:pointer;transition:.2s;"
        onmouseover="this.style.background='#15803d'" onmouseout="this.style.background='#16a34a'">
        ✅ Konfirmasi Lunas
      </button>
    </form>
    <!-- Tolak -->
    <form method="POST" onsubmit="return confirm('Tolak pembayaran ini? Status akan menjadi dibatalkan.')">
      <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
      <input type="hidden" name="aksi_konfirmasi" value="tolak">
      <button type="submit" style="background:#ef5350;color:white;border:none;border-radius:14px;padding:14px 20px;font-size:14px;font-weight:700;width:100%;cursor:pointer;transition:.2s;"
        onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef5350'">
        ❌ Tolak Pembayaran
      </button>
    </form>
    <!-- Preview full -->
    <a href="<?= htmlspecialchars($row['bukti_bayar']) ?>" target="_blank"
      style="display:block;text-align:center;background:#f1f5f9;color:#0f1b2d;border:none;border-radius:14px;padding:12px 20px;font-size:13px;font-weight:600;text-decoration:none;cursor:pointer;transition:.2s;"
      onmouseover="this.style.background='#e2e8f0'" onmouseout="this.style.background='#f1f5f9'">
      🔍 Lihat Bukti Fullscreen
    </a>
  </div>

</div>
<?php endwhile; ?>
</div>

<!-- ============================================================
     SECTION: PENGEMBALIAN DANA MENUNGGU
     ============================================================ -->
<?php if($total_refund > 0): ?>
<div style="margin-top:40px;">
  <div style="display:flex;align-items:center;gap:12px;margin-bottom:20px;">
    <h2 style="font-size:20px;font-weight:700;color:#0f1b2d;">💸 Pengembalian Dana</h2>
    <span style="background:#ef4444;color:white;font-size:12px;font-weight:800;padding:3px 12px;border-radius:20px;">
      <?= $total_refund ?> menunggu
    </span>
  </div>
  <p style="color:#777;font-size:14px;margin-bottom:20px;">Transfer dana ke rekening pelanggan di bawah, lalu tandai sebagai selesai.</p>

  <div style="display:grid;gap:16px;">
  <?php
  // Reset pointer
  mysqli_data_seek($q_refund, 0);
  while($rr = mysqli_fetch_assoc($q_refund)):
  ?>
  <div style="background:white;border-radius:18px;padding:24px 28px;box-shadow:0 6px 20px rgba(0,0,0,0.07);
              display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;
              border-left:5px solid #ef4444;">

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px;">

      <div>
        <p style="font-size:11px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Pelanggan</p>
        <p style="font-weight:800;font-size:15px;color:#0f1b2d;"><?= htmlspecialchars($rr['nama_pelanggan']) ?></p>
        <p style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($rr['no_telepon']) ?></p>
      </div>

      <div>
        <p style="font-size:11px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Paket Dibatalkan</p>
        <p style="font-weight:700;color:#119cc2;font-size:14px;"><?= htmlspecialchars($rr['nama_paket']) ?></p>
        <p style="font-size:12px;color:#6b7280;"><?= date('d M Y', strtotime($rr['tanggal'])) ?> · <?= $rr['jam'] ?></p>
      </div>

      <div>
        <p style="font-size:11px;color:#9ca3af;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px;">Nominal Refund</p>
        <p style="font-weight:800;font-size:20px;color:#16a34a;">Rp <?= number_format($rr['harga'],0,',','.') ?></p>
      </div>

      <div style="background:#fff7ed;border-radius:12px;padding:14px 16px;border:1px solid #fed7aa;">
        <p style="font-size:11px;color:#92400e;font-weight:700;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Transfer ke</p>
        <p style="font-weight:800;font-size:15px;color:#0f1b2d;"><?= htmlspecialchars($rr['refund_bank'] ?? '-') ?></p>
        <p style="font-size:16px;font-weight:800;color:#0f1b2d;letter-spacing:1px;margin:4px 0;"><?= htmlspecialchars($rr['refund_nomor_rek'] ?? '-') ?></p>
        <p style="font-size:12px;color:#6b7280;">a/n <?= htmlspecialchars($rr['refund_nama_rek'] ?? '-') ?></p>
      </div>

    </div>

    <form method="POST" onsubmit="return confirm('Tandai refund ini sudah ditransfer?')">
      <input type="hidden" name="id_pemesanan" value="<?= $rr['id_pemesanan'] ?>">
      <button type="submit" name="selesai_refund"
        style="background:#16a34a;color:white;border:none;border-radius:14px;
               padding:14px 20px;font-size:13px;font-weight:800;cursor:pointer;
               white-space:nowrap;transition:.2s;"
        onmouseover="this.style.background='#15803d'"
        onmouseout="this.style.background='#16a34a'">
        ✅ Tandai Selesai
      </button>
    </form>

  </div>
  <?php endwhile; ?>
  </div>
</div>
<?php endif; ?>

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