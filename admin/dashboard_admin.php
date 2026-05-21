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
   UPDATE STATUS CUCI — ONE-CLICK
========================================================= */
if(isset($_POST['next_status'])){
    $id = (int)$_POST['id_pemesanan'];
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
    if($next === 'selesai'){
        $no_hp  = preg_replace('/[^0-9]/', '', $data_pesan['no_telepon']);
        if(substr($no_hp, 0, 1) === '0') $no_hp = '62' . substr($no_hp, 1);
        $pesan = urlencode(
            "Halo, {$data_pesan['nama_pelanggan']}! 👋\r\n\r\n" .
            "Mobil Anda dengan plat *{$data_pesan['plat_mobil']}* sudah selesai dicuci. ✅\r\n" .
            "Paket: *{$data_pesan['nama_paket']}*\r\n\r\n" .
            "Silakan datang untuk mengambil kendaraan Anda.\r\n\r\n" .
            "Terima kasih telah mempercayai *Habibi Garage*! 🚗✨"
        );
        $wa_url = "https://wa.me/{$no_hp}?text={$pesan}";
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

$q_booking = mysqli_query($conn,"SELECT COUNT(*) as total FROM pemesanan WHERE tanggal='$hari_ini'");
$total_booking = mysqli_fetch_assoc($q_booking)['total'];

$q_pending = mysqli_query($conn,"SELECT COUNT(*) as total FROM pemesanan WHERE status='pending'");
$total_pending = mysqli_fetch_assoc($q_pending)['total'];

$q_income_today = mysqli_query($conn,"
SELECT SUM(pl.harga) as total
FROM pemesanan p
JOIN paket_layanan pl ON p.id_paket = pl.id_paket
WHERE p.status='lunas' AND DATE(p.tanggal)=CURDATE()
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
    mysqli_query($conn,"UPDATE pemesanan SET status_cuci='$status_cuci' WHERE id_pemesanan='$id'");
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
    INSERT INTO pemesanan(nama_pelanggan,no_telepon,id_paket,tanggal,jam,status,status_cuci,created_at)
    VALUES('$nama','$telepon','$paket','$tanggal','$jam','pending','belum_dicuci',NOW())
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
    $tanggal = date('Y-m-d');
    $jam     = mysqli_real_escape_string($conn, $_POST['jam']);
    mysqli_query($conn,"
        INSERT INTO pemesanan(nama_pelanggan,no_telepon,plat_mobil,id_paket,tanggal,jam,status,status_cuci,created_at)
        VALUES('$nama','$telepon','$plat','$paket','$tanggal','$jam','lunas','belum_dicuci',NOW())
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
        mysqli_query($conn,"INSERT INTO paket_layanan(nama_paket,harga,deskripsi) VALUES('$nama','$harga','$deskripsi')");
    }
    if($aksi == "edit"){
        $id = $_POST['id_paket'];
        $nama = $_POST['nama_paket'];
        $harga = $_POST['harga'];
        $deskripsi = $_POST['deskripsi'];
        mysqli_query($conn,"UPDATE paket_layanan SET nama_paket='$nama',harga='$harga',deskripsi='$deskripsi' WHERE id_paket='$id'");
    }
    if($aksi == "hapus"){
        $id = $_POST['id_paket'];
        mysqli_query($conn,"DELETE FROM paket_layanan WHERE id_paket='$id'");
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

$q_paket = mysqli_query($conn,"SELECT * FROM paket_layanan ORDER BY id_paket DESC");

$page = $_GET['page'] ?? 'dashboard';
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard Admin — Habibi Garage</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
/* ====================================================
   RESET & BASE
==================================================== */
*, *::before, *::after {
  margin: 0; padding: 0; box-sizing: border-box;
}

:root {
  --sidebar-w: 260px;
  --navy: #0f1b2d;
  --navy-hover: #1a2f4a;
  --navy-light: #162336;
  --accent: #119cc2;
  --accent-hover: #0e87a9;
  --accent-light: #e0f7fe;
  --success: #22c55e;
  --danger: #ef4444;
  --warning: #f59e0b;
  --bg: #f0f4f8;
  --surface: #ffffff;
  --border: #e5eaf0;
  --text: #1e293b;
  --muted: #64748b;
  --sidebar-text: #94a3b8;
  --sidebar-active-bg: rgba(17,156,194,0.18);
  --sidebar-active-text: #7dd3fc;
  --radius: 16px;
  --radius-sm: 10px;
  --shadow: 0 4px 20px rgba(0,0,0,0.06);
  --shadow-lg: 0 8px 32px rgba(0,0,0,0.10);
}

html, body {
  height: 100%;
  font-family: 'Plus Jakarta Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  font-size: 14px;
}

/* ====================================================
   LAYOUT
==================================================== */
.layout {
  display: flex;
  min-height: 100vh;
}

/* ====================================================
   SIDEBAR
==================================================== */
.sidebar {
  width: var(--sidebar-w);
  background: #0f1929;
  position: fixed;
  top: 0;
  left: 0;
  height: 100vh;
  display: flex;
  flex-direction: column;
  z-index: 100;
  overflow: hidden;
}

/* Subtle texture */
.sidebar-header {
  padding: 28px 22px 24px;
  border-bottom: 1px solid rgba(255,255,255,0.07);
  flex-shrink: 0;
}

.sidebar-brand {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-icon {
  width: 70px; height: 40px;
  border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 18px;
  flex-shrink: 0;
}

.brand-text h2 {
  color: white;
  font-size: 16px;
  font-weight: 800;
  letter-spacing: -0.3px;
  line-height: 1.2;
}

.brand-text span {
  color: var(--sidebar-text);
  font-size: 11px;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 1px;
}

/* Nav */
.sidebar-nav {
  flex: 1;
  padding: 20px 14px;
  overflow-y: auto;
  scrollbar-width: none;
}
.sidebar-nav::-webkit-scrollbar { display: none; }

.brand-icon img{
    width: 45px;
    height: 45px;
    object-fit: contain;
}

.nav-section-label {
  color: rgba(148,163,184,0.5);
  font-size: 10px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 1.5px;
  padding: 0 10px;
  margin-bottom: 8px;
  margin-top: 16px;
}
.nav-section-label:first-child { margin-top: 0; }

.nav-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 12px;
  border-radius: var(--radius-sm);
  color: var(--sidebar-text);
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 500;
  transition: all 0.2s ease;
  position: relative;
  margin-bottom: 2px;
}

.nav-item:hover {
  background: rgba(255,255,255,0.06);
  color: white;
}

.nav-item.active {
  background: var(--sidebar-active-bg);
  color: var(--sidebar-active-text);
  font-weight: 600;
}

.nav-item.active::before {
  content: '';
  position: absolute;
  left: 0; top: 20%; bottom: 20%;
  width: 3px;
  background: var(--accent);
  border-radius: 0 4px 4px 0;
}

.nav-icon {
  width: 20px; height: 20px;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  font-size: 16px;
}

.nav-badge {
  margin-left: auto;
  background: var(--danger);
  color: white;
  font-size: 10px;
  font-weight: 800;
  padding: 2px 7px;
  border-radius: 20px;
  min-width: 20px;
  text-align: center;
}

/* Sidebar footer */
.sidebar-footer {
  padding: 14px;
  border-top: 1px solid rgba(255,255,255,0.07);
  flex-shrink: 0;
}

.logout-btn {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 11px 12px;
  border-radius: var(--radius-sm);
  color: #fca5a5;
  text-decoration: none;
  font-size: 13.5px;
  font-weight: 600;
  transition: all 0.2s ease;
  width: 100%;
}

.logout-btn:hover {
  background: rgba(239,68,68,0.15);
  color: #f87171;
}

/* ====================================================
   MAIN CONTENT
==================================================== */
.main-wrap {
  margin-left: var(--sidebar-w);
  flex: 1;
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

/* ====================================================
   TOPBAR
==================================================== */
.topbar {
  background: var(--surface);
  padding: 0 32px;
  height: 64px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid var(--border);
  position: sticky; top: 0;
  z-index: 50;
  gap: 16px;
}

.topbar-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.page-title {
  font-size: 17px;
  font-weight: 700;
  color: var(--text);
}

.breadcrumb {
  font-size: 12px;
  color: var(--muted);
  display: flex;
  align-items: center;
  gap: 6px;
}

.topbar-right {
  display: flex;
  align-items: center;
  gap: 12px;
}

.topbar-date {
  font-size: 12px;
  color: var(--muted);
  background: var(--bg);
  padding: 6px 14px;
  border-radius: 8px;
  font-weight: 600;
}

.admin-chip {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg);
  padding: 6px 14px 6px 8px;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
  color: var(--text);
}

.admin-avatar {
  width: 28px; height: 28px;
  background: linear-gradient(135deg, var(--accent), #0e87a9);
  border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  color: white;
  font-size: 12px;
  font-weight: 800;
}

/* ====================================================
   CONTENT AREA
==================================================== */
.content {
  flex: 1;
  padding: 28px 32px;
}

/* ====================================================
   PAGE HEADER
==================================================== */
.page-header {
  margin-bottom: 24px;
}

.page-header h1 {
  font-size: 22px;
  font-weight: 800;
  color: var(--text);
  line-height: 1.2;
}

.page-header p {
  color: var(--muted);
  font-size: 13px;
  margin-top: 4px;
}

/* ====================================================
   STAT CARDS
==================================================== */
.stat-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 18px;
  margin-bottom: 24px;
}

.stat-card {
  background: var(--surface);
  border-radius: var(--radius);
  padding: 22px 24px;
  box-shadow: var(--shadow);
  border: 1px solid var(--border);
  position: relative;
  overflow: hidden;
  transition: transform 0.2s, box-shadow 0.2s;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.stat-card::after {
  content: '';
  position: absolute;
  right: -10px; top: -10px;
  width: 70px; height: 70px;
  border-radius: 50%;
  opacity: 0.08;
}

.stat-card.blue::after  { background: #3b82f6; }
.stat-card.green::after { background: var(--success); }
.stat-card.orange::after{ background: var(--warning); }
.stat-card.purple::after{ background: #a855f7; }

.stat-label {
  font-size: 12px;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.8px;
  margin-bottom: 10px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.stat-icon {
  width: 30px; height: 30px;
  border-radius: 8px;
  display: flex; align-items: center; justify-content: center;
  font-size: 14px;
}

.stat-icon.blue   { background: #dbeafe; color: #2563eb; }
.stat-icon.green  { background: #dcfce7; color: #16a34a; }
.stat-icon.orange { background: #fff7ed; color: #d97706; }
.stat-icon.purple { background: #f5f3ff; color: #7c3aed; }

.stat-value {
  font-size: 28px;
  font-weight: 800;
  color: var(--text);
  line-height: 1.1;
}

.stat-sub {
  font-size: 12px;
  color: var(--muted);
  margin-top: 6px;
}

/* ====================================================
   GRID LAYOUT
==================================================== */
.grid-2 {
  display: grid;
  grid-template-columns: 1.6fr 1fr;
  gap: 20px;
}

/* ====================================================
   CARD / BOX
==================================================== */
.card {
  background: var(--surface);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px 0;
  margin-bottom: 16px;
}

.card-head h3 {
  font-size: 15px;
  font-weight: 700;
  color: var(--text);
}

.card-head span {
  font-size: 12px;
  color: var(--muted);
  font-weight: 500;
}

.card-body {
  padding: 0 22px 22px;
}

/* ====================================================
   BOOKING ITEM
==================================================== */
.booking-item {
  background: var(--bg);
  border-radius: 12px;
  padding: 14px 16px;
  margin-bottom: 10px;
  border: 1px solid var(--border);
  transition: box-shadow 0.2s;
}

.booking-item:hover {
  box-shadow: 0 2px 12px rgba(0,0,0,0.06);
}

.booking-item-top {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 8px;
}

.booking-name {
  font-weight: 700;
  font-size: 14px;
  color: var(--text);
}

.booking-paket {
  font-size: 12px;
  color: var(--muted);
  margin-top: 2px;
}

.time-chip {
  background: var(--navy);
  color: white;
  padding: 4px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  white-space: nowrap;
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 3px 10px;
  border-radius: 20px;
  font-size: 11px;
  font-weight: 700;
  color: white;
  margin-bottom: 8px;
}

/* ====================================================
   PROGRESS BUTTON
==================================================== */
.progress-btn {
  width: 100%;
  padding: 9px 14px;
  border: none;
  border-radius: 8px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: filter 0.2s, transform 0.1s;
}

.progress-btn:hover:not(:disabled) {
  filter: brightness(1.1);
  transform: translateY(-1px);
}

.progress-btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ====================================================
   FORMS
==================================================== */
.form-card {
  background: var(--surface);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  padding: 26px;
}

.form-card h3 {
  font-size: 15px;
  font-weight: 700;
  margin-bottom: 18px;
  color: var(--text);
}

.form-group {
  margin-bottom: 14px;
}

.form-label {
  display: block;
  font-size: 11px;
  font-weight: 700;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.6px;
  margin-bottom: 6px;
}

.form-control {
  width: 100%;
  padding: 11px 14px;
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 13.5px;
  font-family: inherit;
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
  background: white;
  color: var(--text);
}

.form-control:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(17,156,194,0.12);
}

.btn {
  padding: 11px 22px;
  border: none;
  border-radius: var(--radius-sm);
  font-size: 13.5px;
  font-weight: 700;
  cursor: pointer;
  font-family: inherit;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.btn-primary {
  background: var(--navy);
  color: white;
}
.btn-primary:hover { background: var(--navy-hover); }

.btn-accent {
  background: var(--accent);
  color: white;
}
.btn-accent:hover { background: var(--accent-hover); }

.btn-success {
  background: var(--success);
  color: white;
}
.btn-success:hover { background: #16a34a; }

.btn-danger {
  background: var(--danger);
  color: white;
}
.btn-danger:hover { background: #dc2626; }

.btn-secondary {
  background: var(--bg);
  color: var(--text);
  border: 1.5px solid var(--border);
}
.btn-secondary:hover { background: var(--border); }

.btn-full { width: 100%; justify-content: center; }
.btn-sm { padding: 7px 14px; font-size: 12px; }

/* ====================================================
   TABLE
==================================================== */
.table-wrap {
  background: var(--surface);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  overflow: hidden;
}

.table-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 18px 22px;
  border-bottom: 1px solid var(--border);
}

.table-head h3 {
  font-size: 15px;
  font-weight: 700;
}

table {
  width: 100%;
  border-collapse: collapse;
}

th {
  background: var(--navy);
  color: rgba(255,255,255,0.7);
  padding: 12px 16px;
  text-align: left;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.8px;
}

td {
  padding: 13px 16px;
  border-bottom: 1px solid var(--border);
  font-size: 13px;
  color: var(--text);
  vertical-align: middle;
}

tr:last-child td { border-bottom: none; }

tr:hover td { background: #fafbfc; }

/* ====================================================
   KALENDER BOOKING
==================================================== */
.calendar-nav {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 12px;
}

.cal-nav-btn {
  background: var(--bg);
  border: 1px solid var(--border);
  border-radius: 8px;
  width: 32px; height: 32px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  font-size: 16px;
  color: var(--text);
  transition: background 0.2s;
}

.cal-nav-btn:hover { background: var(--border); }

.cal-month-label {
  font-size: 13px;
  font-weight: 700;
  color: var(--text);
}

.calendar-header-row {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  margin-bottom: 4px;
}

.cal-day-label {
  text-align: center;
  font-size: 10px;
  font-weight: 700;
  color: var(--muted);
  padding: 4px 0;
  text-transform: uppercase;
}

.cal-day-label.friday { color: var(--danger); }

/* ====================================================
   MENU ITEM
==================================================== */
.menu-item {
  background: var(--bg);
  border-radius: 12px;
  padding: 16px 18px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 10px;
  border: 1px solid var(--border);
  transition: box-shadow 0.2s;
}

.menu-item:hover { box-shadow: 0 2px 10px rgba(0,0,0,0.05); }

.menu-item-info h4 {
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}

.menu-item-info p {
  font-size: 12px;
  color: var(--muted);
  margin-top: 3px;
}

.action-row {
  display: flex;
  gap: 8px;
}

/* ====================================================
   ALERTS / PANELS
==================================================== */
.alert {
  padding: 14px 18px;
  border-radius: 12px;
  font-size: 13px;
  display: flex;
  align-items: flex-start;
  gap: 12px;
  margin-bottom: 16px;
}

.alert-success { background: #dcfce7; border: 1px solid #86efac; color: #15803d; }
.alert-warning { background: #fff7ed; border: 1px solid #fed7aa; color: #92400e; }
.alert-info    { background: #e0f7fe; border: 1px solid #bae6fd; color: #0369a1; }
.alert-danger  { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; }

/* ====================================================
   KONFIRMASI PEMBAYARAN
==================================================== */
.konfirmasi-card {
  background: var(--surface);
  border-radius: var(--radius);
  border: 1px solid var(--border);
  box-shadow: var(--shadow);
  padding: 26px;
  margin-bottom: 20px;
  display: grid;
  grid-template-columns: 1fr auto;
  gap: 26px;
  align-items: start;
}

.konfirmasi-inner {
  display: grid;
  grid-template-columns: 200px 1fr;
  gap: 20px;
}

.konfirmasi-actions {
  display: flex;
  flex-direction: column;
  gap: 10px;
  min-width: 160px;
}

/* ====================================================
   EMPTY STATE
==================================================== */
.empty-state {
  text-align: center;
  padding: 60px 20px;
  color: var(--muted);
}

.empty-state .empty-icon {
  font-size: 48px;
  margin-bottom: 12px;
}

.empty-state h3 {
  font-size: 16px;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 6px;
}

.empty-state p {
  font-size: 13px;
}

/* ====================================================
   MOBILE TOGGLE
==================================================== */
.sidebar-toggle {
  display: none;
  background: none;
  border: none;
  cursor: pointer;
  padding: 4px;
  color: var(--text);
  font-size: 20px;
}

/* ====================================================
   RESPONSIVE
==================================================== */
@media (max-width: 1100px) {
  .stat-grid { grid-template-columns: repeat(2, 1fr); }
  .grid-2 { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
  :root { --sidebar-w: 0px; }

  .sidebar {
    width: 260px;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }

  .sidebar.open {
    transform: translateX(0);
    width: 260px;
  }

  .sidebar-toggle { display: block; }

  .main-wrap { margin-left: 0; }

  .stat-grid { grid-template-columns: 1fr; }

  .konfirmasi-card {
    grid-template-columns: 1fr;
  }

  .konfirmasi-inner {
    grid-template-columns: 1fr;
  }

  .content { padding: 20px 16px; }
  .topbar { padding: 0 16px; }
}

@media (max-width: 480px) {
  .topbar-date { display: none; }
}

/* ====================================================
   CALENDAR GRID (booking page)
==================================================== */
#gridKalender {
  display: grid;
  grid-template-columns: repeat(7, 1fr);
  gap: 4px;
  margin-bottom: 16px;
}

</style>
</head>
<body>

<div class="layout">

<!-- =====================================================
     SIDEBAR
===================================================== -->
<aside class="sidebar" id="sidebar">

  <div class="sidebar-header">
    <div class="sidebar-brand">
      <div class="brand-icon">
    <img src="../img/logo.png" alt="Logo Habibi Garage">
      </div>
      <div class="brand-text">
        <h2>Habibi Garage</h2>
        <span>Admin Panel</span>
      </div>
    </div>
  </div>

  <nav class="sidebar-nav">

    <div class="nav-section-label">Menu Utama</div>

    <a href="?page=dashboard" class="nav-item <?= $page=='dashboard'?'active':'' ?>">
      <span class="nav-icon">📊</span>
      Dashboard
    </a>

    <a href="?page=booking" class="nav-item <?= $page=='booking'?'active':'' ?>">
      <span class="nav-icon">📅</span>
      Booking
    </a>

    <a href="?page=walkin" class="nav-item <?= $page=='walkin'?'active':'' ?>">
      <span class="nav-icon">🚗</span>
      Walk-In
    </a>

    <a href="?page=konfirmasi" class="nav-item <?= $page=='konfirmasi'?'active':'' ?>">
      <span class="nav-icon">✅</span>
      Konfirmasi
      <?php if($total_pending > 0): ?>
      <span class="nav-badge"><?= $total_pending ?></span>
      <?php endif; ?>
    </a>

    <div class="nav-section-label">Pengelolaan</div>

    <a href="?page=menu" class="nav-item <?= $page=='menu'?'active':'' ?>">
      <span class="nav-icon">🛠</span>
      Menu &amp; Paket
    </a>

    <a href="?page=recap" class="nav-item <?= $page=='recap'?'active':'' ?>">
      <span class="nav-icon">📈</span>
      Rekap Pendapatan
    </a>

    <a href="?page=admin" class="nav-item <?= $page=='admin'?'active':'' ?>">
      <span class="nav-icon">👤</span>
      Manajemen Admin
    </a>

  </nav>

  <div class="sidebar-footer">
    <a href="?logout=true" class="logout-btn">
      <span class="nav-icon">🚪</span>
      Logout
    </a>
  </div>

</aside>

<!-- =====================================================
     MAIN WRAP
===================================================== -->
<div class="main-wrap">

  <!-- TOPBAR -->
  <header class="topbar">
    <div class="topbar-left">
      <button class="sidebar-toggle" onclick="toggleSidebar()" aria-label="Toggle Sidebar">☰</button>
      <div>
        <div class="page-title">
          <?php
          $titles = [
            'dashboard'   => 'Dashboard',
            'booking'     => 'Manajemen Booking',
            'walkin'      => 'Pelanggan Walk-In',
            'konfirmasi'  => 'Konfirmasi Pembayaran',
            'menu'        => 'Menu & Paket Layanan',
            'recap'       => 'Rekap Pendapatan',
            'admin'       => 'Manajemen Admin',
          ];
          echo $titles[$page] ?? 'Dashboard';
          ?>
        </div>
        <div class="breadcrumb">Habibi Garage &rsaquo; <?= $titles[$page] ?? 'Dashboard' ?></div>
      </div>
    </div>
    <div class="topbar-right">
      <div class="topbar-date"><?= date('l, d F Y') ?></div>
      <div class="admin-chip">
        <div class="admin-avatar">A</div>
        Admin
      </div>
    </div>
  </header>

  <!-- CONTENT -->
  <main class="content">

    <?php if(!empty($_GET['wa_url'])): ?>
    <!-- POPUP NOTIFIKASI WHATSAPP -->
    <div id="waPopupOverlay" style="
      position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:9999;
      display:flex;align-items:center;justify-content:center;
      animation:fadeInOverlay .25s ease;
    ">
      <div style="
        background:#fff;border-radius:20px;padding:36px 32px;max-width:420px;width:90%;
        box-shadow:0 20px 60px rgba(0,0,0,0.25);text-align:center;
        animation:slideUpPopup .3s ease;
      ">
        <!-- Icon -->
        <div style="
          width:72px;height:72px;border-radius:50%;
          background:linear-gradient(135deg,#25d366,#128c7e);
          display:flex;align-items:center;justify-content:center;
          font-size:36px;margin:0 auto 20px;
          box-shadow:0 8px 24px rgba(37,211,102,0.35);
        ">💬</div>

        <!-- Title -->
        <h2 style="font-size:20px;font-weight:800;color:#1e293b;margin-bottom:8px;">
          Cucian Selesai! ✅
        </h2>
        <p style="color:#64748b;font-size:14px;line-height:1.6;margin-bottom:24px;">
          Status berhasil diperbarui ke <strong style="color:#22c55e;">Selesai</strong>.<br>
          Kirim notifikasi WhatsApp kepada pelanggan sekarang?
        </p>

        <!-- Buttons -->
        <div style="display:flex;gap:12px;flex-direction:column;">
          <a
            href="<?= htmlspecialchars(urldecode($_GET['wa_url'])) ?>"
            target="_blank"
            onclick="document.getElementById('waPopupOverlay').remove()"
            style="
              display:flex;align-items:center;justify-content:center;gap:10px;
              background:linear-gradient(135deg,#25d366,#128c7e);
              color:white;font-size:15px;font-weight:700;
              padding:14px 24px;border-radius:12px;text-decoration:none;
              box-shadow:0 4px 16px rgba(37,211,102,0.35);
              transition:opacity .2s;
            "
            onmouseover="this.style.opacity='.9'"
            onmouseout="this.style.opacity='1'"
          >
            <svg width="22" height="22" viewBox="0 0 24 24" fill="white"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413Z"/></svg>
            Kirim via WhatsApp
          </a>
          <button
            onclick="document.getElementById('waPopupOverlay').remove()"
            style="
              background:#f1f5f9;color:#475569;font-size:14px;font-weight:600;
              padding:12px 24px;border-radius:12px;border:none;cursor:pointer;
              transition:background .2s;
            "
            onmouseover="this.style.background='#e2e8f0'"
            onmouseout="this.style.background='#f1f5f9'"
          >
            Lewati
          </button>
        </div>
      </div>
    </div>

    <style>
    @keyframes fadeInOverlay { from{opacity:0} to{opacity:1} }
    @keyframes slideUpPopup  { from{opacity:0;transform:translateY(30px)} to{opacity:1;transform:translateY(0)} }
    </style>
    <?php endif; ?>

    <!-- ================================================
         DASHBOARD PAGE
    ================================================ -->
    <?php if($page == 'dashboard'): ?>

    <div class="page-header">
      <h1>Selamat datang, Admin! 👋</h1>
      <p>Berikut ringkasan aktivitas Habibi Garage hari ini.</p>
    </div>

    <!-- Stat Cards -->
    <div class="stat-grid">
      <div class="stat-card blue">
        <div class="stat-label">
          <div class="stat-icon blue">📅</div>
          Booking Hari Ini
        </div>
        <div class="stat-value"><?= $total_booking ?></div>
        <div class="stat-sub">Total booking <?= date('d F Y') ?></div>
      </div>

      <div class="stat-card green">
        <div class="stat-label">
          <div class="stat-icon green">✅</div>
          Konfirmasi Pending
        </div>
        <div class="stat-value"><?= $total_pending ?></div>
        <div class="stat-sub">Menunggu konfirmasi admin</div>
      </div>

      <div class="stat-card orange">
        <div class="stat-label">
          <div class="stat-icon orange">💰</div>
          Pendapatan Hari Ini
        </div>
        <div class="stat-value" style="font-size:20px;">Rp <?= number_format($income_today,0,',','.') ?></div>
        <div class="stat-sub">Bulan ini: Rp <?= number_format($income_month,0,',','.') ?></div>
      </div>
    </div>

    <!-- Grid: Booking List + Kalender -->
    <div class="grid-2">

      <!-- Booking Hari Ini -->
      <div class="card">
        <div class="card-head">
          <h3>Booking Hari Ini</h3>
          <span><?= date('d F Y') ?></span>
        </div>
        <div class="card-body">
          <?php
          $has_booking = false;
          while($row=mysqli_fetch_assoc($q_booking_today)):
            $has_booking = true;
            $sc = $row['status_cuci'];
            $btn_label = $sc === 'belum_dicuci' ? '▶ Mulai Proses'
                       : ($sc === 'diproses'    ? '✔ Selesai Dicuci'
                       :                         '✔ Sudah Selesai');
            $btn_color = $sc === 'belum_dicuci' ? 'var(--accent)'
                       : ($sc === 'diproses'    ? 'var(--success)'
                       :                         '#9ca3af');
            $btn_disabled = $sc === 'selesai' ? 'disabled' : '';
            $badge_label  = $sc === 'belum_dicuci' ? 'Belum Dicuci'
                          : ($sc === 'diproses'    ? 'Diproses'
                          :                         'Selesai');
            $badge_color  = $sc === 'belum_dicuci' ? 'var(--warning)'
                          : ($sc === 'diproses'    ? '#3b82f6'
                          :                         'var(--success)');
          ?>
          <div class="booking-item">
            <div class="booking-item-top">
              <div>
                <div class="booking-name"><?= htmlspecialchars($row['nama_pelanggan']) ?></div>
                <div class="booking-paket"><?= htmlspecialchars($row['nama_paket']) ?></div>
              </div>
              <div class="time-chip"><?= htmlspecialchars($row['jam']) ?></div>
            </div>
            <div class="status-badge" style="background:<?= $badge_color ?>;"><?= $badge_label ?></div>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
              <button type="submit" name="next_status" <?= $btn_disabled ?>
                class="progress-btn"
                style="background:<?= $btn_color ?>;color:white;<?= $sc==='selesai'?'opacity:.5;cursor:not-allowed;':'' ?>">
                <?= $btn_label ?>
              </button>
            </form>
          </div>
          <?php endwhile; ?>
          <?php if(!$has_booking): ?>
          <div class="empty-state" style="padding:30px 0;">
            <div class="empty-icon">📭</div>
            <p>Belum ada booking hari ini.</p>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Kalender -->
      <div class="card">
        <div class="card-head">
          <h3>Kalender Booking</h3>
        </div>
        <div class="card-body">
          <table class="calendar" style="width:100%;border-spacing:6px;border-collapse:separate;">
            <tr>
              <?php foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $h): ?>
              <th style="color:<?= $h==='Jum'?'var(--danger)':'var(--muted)' ?>;font-size:11px;padding:6px;text-align:center;background:none;"><?= $h ?></th>
              <?php endforeach; ?>
            </tr>
            <?php
            $tahun_kal  = (int)date('Y');
            $bulan_kal  = (int)date('m');
            $hari_ini_n = (int)date('j');
            $total_hari = (int)date('t');
            $awal_dow   = (int)date('w', mktime(0,0,0,$bulan_kal,1,$tahun_kal));

            $tgl_booking = [];
            $q_kal = mysqli_query($conn,"SELECT DISTINCT DAY(tanggal) as hari FROM pemesanan WHERE MONTH(tanggal)='$bulan_kal' AND YEAR(tanggal)='$tahun_kal'");
            while($rk = mysqli_fetch_assoc($q_kal)) $tgl_booking[] = (int)$rk['hari'];

            $col = 0;
            echo '<tr>';
            for($i=0;$i<$awal_dow;$i++){ echo '<td style="background:transparent;border:none;"></td>'; $col++; }
            for($d=1;$d<=$total_hari;$d++){
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
                $style = 'background:var(--navy);color:white;font-weight:700;';
                $extra = 'data-today="1"';
              } elseif($is_past || $is_jumat){
                $style = 'background:#e5e7eb;color:#9ca3af;cursor:not-allowed;opacity:.6;';
                $extra = 'data-disabled="1"';
              } elseif($ada_booking){
                $style = 'background:#dbeafe;color:#1e40af;font-weight:600;';
              } else {
                $style = 'background:var(--bg);';
              }
              $dot = $ada_booking && !$is_past && !$is_jumat
                   ? '<span style="display:block;width:5px;height:5px;border-radius:50%;background:var(--accent);margin:2px auto 0;"></span>' : '';
              echo "<td style='border-radius:8px;text-align:center;padding:10px 5px;font-size:12px;cursor:pointer;transition:.2s;{$style}' {$extra} data-tgl='{$tgl_str}' onclick='klikKalender(this)'>{$d}{$dot}</td>";
              $col++;
            }
            while($col<7){ echo '<td style="background:transparent;border:none;"></td>'; $col++; }
            echo '</tr>';
            ?>
          </table>

          <div id="panelKalender" style="display:none;margin-top:14px;background:var(--bg);border:1px solid var(--border);border-radius:12px;padding:16px;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
              <h4 id="panelTanggalLabel" style="font-size:13px;font-weight:700;color:var(--text);"></h4>
              <button onclick="document.getElementById('panelKalender').style.display='none'" style="background:none;border:none;color:var(--muted);font-size:18px;cursor:pointer;line-height:1;">&#215;</button>
            </div>
            <div id="panelKalenderIsi">
              <p style="color:var(--muted);font-size:13px;">Klik tanggal untuk melihat booking.</p>
            </div>
          </div>

        </div>
      </div>

    </div><!-- /grid-2 -->

    <?php endif; ?>


    <!-- ================================================
         BOOKING PAGE
    ================================================ -->
    <?php if($page == 'booking'): ?>

    <?php
    $slot_booked_today = [];
    $q_slot_today = mysqli_query($conn,"SELECT jam FROM pemesanan WHERE tanggal='".date('Y-m-d')."'");
    while($rs = mysqli_fetch_assoc($q_slot_today)) $slot_booked_today[] = $rs['jam'];
    ?>

    <div class="page-header">
      <h1>Tambah Booking</h1>
      <p>Buat booking baru untuk pelanggan secara manual.</p>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start;">

      <!-- Form -->
      <div class="form-card">
        <h3>Data Pelanggan</h3>

        <form method="POST" id="formBookingAdmin">
          <input type="hidden" name="tambah_pelanggan" value="1">

          <div class="form-group">
            <label class="form-label">Nama Pelanggan</label>
            <input type="text" name="nama_pelanggan" class="form-control" placeholder="Nama lengkap" required>
          </div>

          <div class="form-group">
            <label class="form-label">No Telepon</label>
            <input type="text" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" required>
          </div>

          <div class="form-group">
            <label class="form-label">Paket Layanan</label>
            <select name="id_paket" class="form-control" required>
              <option value="">Pilih Paket</option>
              <?php mysqli_data_seek($q_paket,0); while($p=mysqli_fetch_assoc($q_paket)): ?>
              <option value="<?= $p['id_paket'] ?>"><?= htmlspecialchars($p['nama_paket']) ?> — Rp <?= number_format($p['harga'],0,',','.') ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <input type="hidden" name="tanggal" id="inputTanggal" required>
          <input type="hidden" name="jam" id="inputJam" required>

          <div id="pilihanInfo" style="display:none;background:var(--accent-light);border:1px solid #bae6fd;border-radius:10px;padding:12px 14px;font-size:13px;color:#0369a1;margin-bottom:14px;">
            <div>📅 <strong id="labelTanggal">-</strong></div>
            <div style="margin-top:4px;">🕐 <strong id="labelJam">-</strong></div>
          </div>
          <div id="pilihanWarning" style="display:none;margin-bottom:14px;">
            <div class="alert alert-warning">⚠️ Pilih tanggal dan jam dari kalender di sebelah kanan.</div>
          </div>

          <button type="submit" class="btn btn-primary btn-full" onclick="return validasiForm()">Tambah Booking</button>
        </form>
      </div>

      <!-- Kalender Pilih Slot -->
      <div class="form-card">
        <h3>Pilih Tanggal &amp; Jam</h3>

        <div class="calendar-nav">
          <button class="cal-nav-btn" onclick="gantibulan(-1)">‹</button>
          <span class="cal-month-label" id="labelBulanTahun"></span>
          <button class="cal-nav-btn" onclick="gantibulan(1)">›</button>
        </div>

        <div class="calendar-header-row">
          <?php foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $h): ?>
          <div class="cal-day-label <?= $h==='Jum'?'friday':'' ?>"><?= $h ?></div>
          <?php endforeach; ?>
        </div>

        <div id="gridKalender"></div>

        <!-- Slot jam -->
        <div style="border-top:1px solid var(--border);padding-top:14px;">
          <div style="font-size:12px;font-weight:700;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px;">
            Slot Jam — <span id="labelTanggalSlot" style="color:var(--accent);">pilih tanggal dulu</span>
          </div>
          <div id="gridSlot" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
            <div style="grid-column:span 2;text-align:center;color:var(--muted);font-size:13px;padding:16px 0;">
              Klik tanggal untuk melihat slot jam
            </div>
          </div>
        </div>

        <!-- Legenda -->
        <div style="display:flex;gap:14px;margin-top:12px;flex-wrap:wrap;">
          <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--muted);">
            <span style="width:12px;height:12px;border-radius:3px;background:#e2f4ff;border:1px solid var(--accent);display:inline-block;"></span> Tersedia
          </div>
          <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--muted);">
            <span style="width:12px;height:12px;border-radius:3px;background:var(--navy);display:inline-block;"></span> Dipilih
          </div>
          <div style="display:flex;align-items:center;gap:6px;font-size:11px;color:var(--muted);">
            <span style="width:12px;height:12px;border-radius:3px;background:#fee2e2;border:1px solid var(--danger);display:inline-block;"></span> Penuh
          </div>
        </div>
      </div>

    </div><!-- /grid -->

    <script>
    const SEMUA_SLOT = ['08:00 - 09:00','09:00 - 10:00','10:00 - 11:00','11:00 - 12:00','13:00 - 14:00','14:00 - 15:00','15:00 - 16:00'];
    const BULAN_ADM = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    let tglAktif = null, jamAktif = null;
    let bulanView = new Date().getMonth(), tahunView = new Date().getFullYear();

    function gantibulan(a){ bulanView+=a; if(bulanView<0){bulanView=11;tahunView--;} if(bulanView>11){bulanView=0;tahunView++;} renderKalender(); }

    function renderKalender(){
      document.getElementById('labelBulanTahun').textContent = BULAN_ADM[bulanView]+' '+tahunView;
      const grid = document.getElementById('gridKalender');
      const today = new Date(); today.setHours(0,0,0,0);
      const awal = new Date(tahunView,bulanView,1).getDay();
      const total = new Date(tahunView,bulanView+1,0).getDate();
      let html = '';
      for(let i=0;i<awal;i++) html+='<div></div>';
      for(let d=1;d<=total;d++){
        const tgl=new Date(tahunView,bulanView,d), dow=tgl.getDay();
        const tglStr=tahunView+'-'+String(bulanView+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
        const isPast=tgl<today, isJumat=dow===5, isToday=tgl.getTime()===today.getTime(), isAktif=tglStr===tglAktif;
        let bg='#f7f8fc',color='var(--text)',cursor='pointer',border='1px solid transparent',opacity='1';
        if(isAktif){bg='var(--navy)';color='white';}
        else if(isToday){bg='#e0f2fe';border='1px solid var(--accent)';}
        else if(isPast||isJumat){bg='#e5e7eb';color='#9ca3af';cursor='not-allowed';opacity='.6';}
        const disabled=(isPast||isJumat)?'disabled':'';
        html+=`<div onclick="pilihTanggal('${tglStr}','${d} ${BULAN_ADM[bulanView]}')" ${disabled} style="text-align:center;padding:8px 2px;border-radius:8px;font-size:12px;font-weight:600;background:${bg};color:${color};cursor:${cursor};border:${border};opacity:${opacity};transition:.15s;">${d}</div>`;
      }
      grid.innerHTML = html;
    }

    function pilihTanggal(tglStr,labelStr){
      tglAktif=tglStr; jamAktif=null;
      document.getElementById('inputTanggal').value=tglStr;
      document.getElementById('inputJam').value='';
      document.getElementById('labelTanggalSlot').textContent=labelStr;
      document.getElementById('pilihanInfo').style.display='none';
      document.getElementById('labelTanggal').textContent=labelStr;
      renderKalender(); muatSlot(tglStr);
    }

    function muatSlot(tglStr){
      const grid=document.getElementById('gridSlot');
      grid.innerHTML='<div style="grid-column:span 2;text-align:center;color:var(--muted);font-size:13px;padding:16px 0;">Memuat slot...</div>';
      fetch('dashboard_admin.php?ajax_booking=1&tgl='+tglStr).then(r=>r.json()).then(data=>{
        const terpesan=data.map(b=>b.jam);
        grid.innerHTML=SEMUA_SLOT.map(slot=>{
          const penuh=terpesan.includes(slot), dipilih=slot===jamAktif;
          let bg=penuh?'#fee2e2':(dipilih?'var(--navy)':'#e2f4ff');
          let color=penuh?'var(--danger)':(dipilih?'white':'var(--text)');
          let border=penuh?'1px solid var(--danger)':(dipilih?'none':'1px solid var(--accent)');
          let cursor=penuh?'not-allowed':'pointer';
          let strike=penuh?'text-decoration:line-through;':'';
          return `<div onclick="pilihJam('${slot}')" ${penuh?'disabled':''} style="padding:10px;border-radius:10px;text-align:center;font-size:12px;font-weight:700;background:${bg};color:${color};border:${border};cursor:${cursor};${strike}transition:.15s;">${slot}${penuh?'<br><span style="font-size:10px;font-weight:400;">Penuh</span>':''}</div>`;
        }).join('');
      }).catch(()=>{
        grid.innerHTML='<div style="grid-column:span 2;color:var(--danger);font-size:13px;text-align:center;">Gagal memuat slot.</div>';
      });
    }

    function pilihJam(slot){
      jamAktif=slot;
      document.getElementById('inputJam').value=slot;
      document.getElementById('labelTanggal').textContent=document.getElementById('labelTanggalSlot').textContent;
      document.getElementById('labelJam').textContent=slot;
      document.getElementById('pilihanInfo').style.display='block';
      document.getElementById('pilihanWarning').style.display='none';
      muatSlot(tglAktif);
    }

    function validasiForm(){
      if(!tglAktif||!jamAktif){ document.getElementById('pilihanWarning').style.display='block'; return false; }
      return true;
    }
    renderKalender();
    </script>

    <?php endif; ?>


    <!-- ================================================
         MENU PAGE
    ================================================ -->
    <?php if($page == 'menu'): ?>

    <div class="page-header">
      <h1>Menu &amp; Paket Layanan</h1>
      <p>Kelola daftar paket cuci kendaraan Habibi Garage.</p>
    </div>

    <div style="display:grid;grid-template-columns:380px 1fr;gap:22px;align-items:start;">

      <!-- Form Tambah -->
      <div class="form-card">
        <h3>Tambah Paket Baru</h3>
        <form method="POST">
          <input type="hidden" name="aksi_menu" value="tambah">
          <div class="form-group">
            <label class="form-label">Nama Paket</label>
            <input type="text" name="nama_paket" class="form-control" placeholder="Nama paket layanan" required>
          </div>
          <div class="form-group">
            <label class="form-label">Harga (Rp)</label>
            <input type="number" name="harga" class="form-control" placeholder="Contoh: 50000" required>
          </div>
          <div class="form-group">
            <label class="form-label">Deskripsi</label>
            <input type="text" name="deskripsi" class="form-control" placeholder="Deskripsi singkat" required>
          </div>
          <button type="submit" class="btn btn-primary btn-full">+ Tambah Paket</button>
        </form>
      </div>

      <!-- Daftar Paket -->
      <div>
        <?php mysqli_data_seek($q_paket,0); while($paket=mysqli_fetch_assoc($q_paket)): ?>
        <div class="menu-item">
          <div class="menu-item-info">
            <h4><?= htmlspecialchars($paket['nama_paket']) ?></h4>
            <p>Rp <?= number_format($paket['harga'],0,',','.') ?> — <?= htmlspecialchars($paket['deskripsi']) ?></p>
          </div>
          <div class="action-row">
            <button class="btn btn-accent btn-sm" onclick="toggleEdit(<?= $paket['id_paket'] ?>)">Edit</button>
            <form method="POST" style="margin:0;">
              <input type="hidden" name="aksi_menu" value="hapus">
              <input type="hidden" name="id_paket" value="<?= $paket['id_paket'] ?>">
              <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Hapus paket ini?')">Hapus</button>
            </form>
          </div>
        </div>

        <div id="edit<?= $paket['id_paket'] ?>" style="display:none;background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;margin-bottom:14px;margin-top:-4px;">
          <form method="POST" style="display:flex;flex-direction:column;gap:10px;">
            <input type="hidden" name="aksi_menu" value="edit">
            <input type="hidden" name="id_paket" value="<?= $paket['id_paket'] ?>">
            <input type="text" name="nama_paket" class="form-control" value="<?= htmlspecialchars($paket['nama_paket']) ?>">
            <input type="number" name="harga" class="form-control" value="<?= $paket['harga'] ?>">
            <input type="text" name="deskripsi" class="form-control" value="<?= htmlspecialchars($paket['deskripsi']) ?>">
            <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
          </form>
        </div>
        <?php endwhile; ?>
      </div>

    </div>

    <?php endif; ?>


    <!-- ================================================
         RECAP PAGE
    ================================================ -->
    <?php if($page == 'recap'): ?>

    <?php
    $where = "WHERE p.status='lunas'";
    if(!empty($_GET['bulan']) && !empty($_GET['tahun'])){
      $bulan = (int)$_GET['bulan'];
      $tahun = (int)$_GET['tahun'];
      $where .= " AND MONTH(p.tanggal)='$bulan' AND YEAR(p.tanggal)='$tahun'";
    }
    $q_recap = mysqli_query($conn,"SELECT p.*,pl.nama_paket,pl.harga FROM pemesanan p JOIN paket_layanan pl ON p.id_paket=pl.id_paket $where ORDER BY p.tanggal DESC");
    $q_total = mysqli_query($conn,"SELECT SUM(pl.harga) as total FROM pemesanan p JOIN paket_layanan pl ON p.id_paket=pl.id_paket $where");
    $total_recap = mysqli_fetch_assoc($q_total)['total'] ?? 0;
    ?>

    <div class="page-header">
      <h1>Rekap Pendapatan</h1>
      <p>Laporan keuangan dan riwayat transaksi Habibi Garage.</p>
    </div>

    <!-- Stat Cards Recap -->
    <div class="stat-grid" style="margin-bottom:22px;">
      <div class="stat-card blue">
        <div class="stat-label"><div class="stat-icon blue">📅</div>Income Hari Ini</div>
        <div class="stat-value" style="font-size:20px;">Rp <?= number_format($income_today,0,',','.') ?></div>
        <div class="stat-sub">Pendapatan hari ini</div>
      </div>
      <div class="stat-card green">
        <div class="stat-label"><div class="stat-icon green">📆</div>Income Bulan Ini</div>
        <div class="stat-value" style="font-size:20px;">Rp <?= number_format($income_month,0,',','.') ?></div>
        <div class="stat-sub">Pendapatan bulan sekarang</div>
      </div>
      <div class="stat-card orange">
        <div class="stat-label"><div class="stat-icon orange">📊</div>Total Hasil Filter</div>
        <div class="stat-value" style="font-size:20px;">Rp <?= number_format($total_recap,0,',','.') ?></div>
        <div class="stat-sub">Total dari filter aktif</div>
      </div>
    </div>

    <!-- Filter -->
    <div class="form-card" style="margin-bottom:22px;">
      <h3>Filter Rekap</h3>
      <form method="GET" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
        <input type="hidden" name="page" value="recap">
        <div class="form-group" style="margin:0;min-width:160px;">
          <label class="form-label">Bulan</label>
          <select name="bulan" class="form-control">
            <option value="">Semua Bulan</option>
            <?php for($i=1;$i<=12;$i++): ?>
            <option value="<?= $i ?>" <?= (isset($_GET['bulan'])&&$_GET['bulan']==$i)?'selected':'' ?>><?= date('F',mktime(0,0,0,$i,1)) ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div class="form-group" style="margin:0;min-width:120px;">
          <label class="form-label">Tahun</label>
          <select name="tahun" class="form-control">
            <option value="">Semua Tahun</option>
            <?php for($y=date('Y');$y>=2023;$y--): ?>
            <option value="<?= $y ?>" <?= (isset($_GET['tahun'])&&$_GET['tahun']==$y)?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <button type="submit" class="btn btn-primary" style="align-self:flex-end;">Cari</button>
      </form>
    </div>

    <!-- Tabel Recap -->
    <?php
    $recap_rows = [];
    mysqli_data_seek($q_recap,0);
    while($r_tmp=mysqli_fetch_assoc($q_recap)) $recap_rows[]=$r_tmp;
    ?>
    <div class="table-wrap">
      <div class="table-head">
        <h3>Data Pendapatan</h3>
        <button onclick="exportExcel()" class="btn btn-success btn-sm">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="12" y1="18" x2="12" y2="12"/><polyline points="9 15 12 18 15 15"/></svg>
          Export Excel
        </button>
      </div>
      <table id="tabelRecap">
        <tr>
          <th>No</th><th>ID</th><th>Nama Pelanggan</th><th>Telepon</th>
          <th>Plat</th><th>Paket</th><th>Tanggal</th><th>Jam</th><th>Total (Rp)</th>
        </tr>
        <?php foreach($recap_rows as $i => $row): ?>
        <tr>
          <td><?= $i+1 ?></td>
          <td>#<?= $row['id_pemesanan'] ?></td>
          <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
          <td><?= htmlspecialchars($row['no_telepon']) ?></td>
          <td><strong><?= htmlspecialchars(strtoupper($row['plat_mobil'])) ?></strong></td>
          <td><?= htmlspecialchars($row['nama_paket']) ?></td>
          <td><?= date('d-m-Y',strtotime($row['tanggal'])) ?></td>
          <td><?= htmlspecialchars($row['jam']) ?></td>
          <td>Rp <?= number_format($row['harga'],0,',','.') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if(empty($recap_rows)): ?>
        <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:32px;">Tidak ada data untuk filter ini.</td></tr>
        <?php else: ?>
        <tr style="font-weight:800;background:#f0fdf4;">
          <td colspan="8" style="text-align:right;color:var(--text);">TOTAL PENDAPATAN</td>
          <td style="color:var(--success);">Rp <?= number_format($total_recap,0,',','.') ?></td>
        </tr>
        <?php endif; ?>
      </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
    function exportExcel(){
      <?php
      $label_filter = '';
      if(!empty($_GET['bulan'])&&!empty($_GET['tahun'])){
        $label_filter = date('F',mktime(0,0,0,(int)$_GET['bulan'],1)).'_'.(int)$_GET['tahun'];
      } else { $label_filter = 'Semua'; }
      ?>
      const namaFile = 'Recap_Habibi_Garage_<?= $label_filter ?>.xlsx';
      const info = [
        ['REKAP PENDAPATAN — HABIBI GARAGE'],
        ['Periode','<?= !empty($_GET['bulan'])&&!empty($_GET['tahun'])?date('F Y',mktime(0,0,0,(int)$_GET['bulan'],1,(int)$_GET['tahun'])):"Semua Data" ?>'],
        ['Tanggal Export','<?= date('d/m/Y H:i') ?>'],
        ['Total Pendapatan','Rp <?= number_format($total_recap,0,',','.') ?>'],
        [],
        ['No','ID Pemesanan','Nama Pelanggan','No. Telepon','Plat Mobil','Paket Layanan','Tanggal','Jam','Total (Rp)']
      ];
      const dataRows = <?php
        $js_rows=[];
        foreach($recap_rows as $i=>$r){
          $js_rows[]=[$i+1,$r['id_pemesanan'],$r['nama_pelanggan'],$r['no_telepon'],strtoupper($r['plat_mobil']),$r['nama_paket'],date('d-m-Y',strtotime($r['tanggal'])),$r['jam'],(int)$r['harga']];
        }
        echo json_encode($js_rows);
      ?>;
      const totalRow=['','','','','','','','TOTAL',<?= (int)$total_recap ?>];
      const allRows=[...info,...dataRows,[],totalRow];
      const ws=XLSX.utils.aoa_to_sheet(allRows);
      ws['!cols']=[{wch:5},{wch:14},{wch:22},{wch:16},{wch:12},{wch:22},{wch:12},{wch:18},{wch:16}];
      const wb=XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb,ws,'Rekap Pendapatan');
      XLSX.writeFile(wb,namaFile);
    }
    </script>

    <?php endif; ?>


    <!-- ================================================
         WALK-IN PAGE
    ================================================ -->
    <?php if($page == 'walkin'): ?>

    <?php
    $slot_walkin_booked = [];
    $q_slot_walkin = mysqli_query($conn,"SELECT jam FROM pemesanan WHERE tanggal='".date('Y-m-d')."'");
    while($rs=mysqli_fetch_assoc($q_slot_walkin)) $slot_walkin_booked[]=$rs['jam'];
    $semua_slot_walkin=['08:00 - 09:00','09:00 - 10:00','10:00 - 11:00','11:00 - 12:00','13:00 - 14:00','14:00 - 15:00','15:00 - 16:00'];
    $q_walkin_today=mysqli_query($conn,"SELECT p.*,pl.nama_paket,pl.harga FROM pemesanan p JOIN paket_layanan pl ON p.id_paket=pl.id_paket WHERE p.tanggal=CURDATE() AND p.status='lunas' ORDER BY p.created_at DESC");
    ?>

    <div class="page-header">
      <h1>Pelanggan Walk-In</h1>
      <p>Tambahkan pelanggan yang datang langsung ke bengkel.</p>
    </div>

    <?php if(isset($_GET['sukses'])&&is_numeric($_GET['sukses'])): ?>
    <div class="alert alert-success" style="margin-bottom:20px;">
      ✅ <strong>Pelanggan walk-in berhasil ditambahkan!</strong> ID Pemesanan: <strong>#<?= (int)$_GET['sukses'] ?></strong> — Status langsung Lunas.
    </div>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:22px;align-items:start;">

      <!-- Form Walk-In -->
      <div class="form-card">
        <h3>🚗 Tambah Pelanggan Walk-In</h3>
        <form method="POST" id="formWalkin" style="display:flex;flex-direction:column;gap:0;">
          <input type="hidden" name="tambah_walkin" value="1">
          <input type="hidden" name="jam" id="walkinJam" required>

          <div class="form-group">
            <label class="form-label">Nama Pelanggan</label>
            <input type="text" name="nama_pelanggan" class="form-control" placeholder="Nama lengkap" required>
          </div>
          <div class="form-group">
            <label class="form-label">No. Telepon</label>
            <input type="text" name="no_telepon" class="form-control" placeholder="08xxxxxxxxxx" required>
          </div>
          <div class="form-group">
            <label class="form-label">Plat Nomor Kendaraan</label>
            <input type="text" name="plat_mobil" class="form-control" placeholder="B 1234 XY" required
              oninput="this.value=this.value.toUpperCase()" style="text-transform:uppercase;">
          </div>
          <div class="form-group">
            <label class="form-label">Paket Layanan</label>
            <select name="id_paket" class="form-control" required>
              <option value="">Pilih Paket</option>
              <?php mysqli_data_seek($q_paket,0); while($p=mysqli_fetch_assoc($q_paket)): ?>
              <option value="<?= $p['id_paket'] ?>"><?= htmlspecialchars($p['nama_paket']) ?> — Rp <?= number_format($p['harga'],0,',','.') ?></option>
              <?php endwhile; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Slot Jam Hari Ini</label>
            <div style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;" id="slotGrid">
              <?php foreach($semua_slot_walkin as $slot):
                $terpakai=in_array($slot,$slot_walkin_booked);
              ?>
              <button type="button" class="slot-btn" data-slot="<?= $slot ?>"
                onclick="pilihSlotWalkin(this,'<?= $slot ?>')"
                <?= $terpakai?'disabled':'' ?>
                style="padding:10px 8px;border-radius:10px;font-size:12px;font-weight:700;
                  border:2px solid <?= $terpakai?'#fecaca':'#bae6fd' ?>;
                  background:<?= $terpakai?'#fee2e2':'#e0f2fe' ?>;
                  color:<?= $terpakai?'var(--danger)':'var(--text)' ?>;
                  cursor:<?= $terpakai?'not-allowed':'pointer' ?>;
                  text-decoration:<?= $terpakai?'line-through':'none' ?>;
                  font-family:inherit;transition:.15s;">
                <?= $slot ?><?= $terpakai?'<br><span style="font-size:10px;font-weight:400;">Terpakai</span>':'' ?>
              </button>
              <?php endforeach; ?>
            </div>
            <div id="slotPilihInfo" style="display:none;margin-top:8px;background:#f0fdf4;border:1px solid #86efac;border-radius:10px;padding:10px 14px;font-size:13px;color:#15803d;">
              ✅ Slot dipilih: <strong id="slotLabel">-</strong>
            </div>
            <div id="slotWarning" style="display:none;margin-top:6px;">
              <div class="alert alert-warning" style="margin:0;">⚠️ Pilih slot jam terlebih dahulu</div>
            </div>
          </div>

          <div class="alert alert-success" style="margin-bottom:14px;">
            ✅ <strong>Walk-in = langsung Lunas.</strong> Admin bertanggung jawab atas konfirmasi pembayaran tunai.
          </div>

          <button type="submit" class="btn btn-primary btn-full" onclick="return validasiWalkin()">
            + Tambahkan Pelanggan Walk-In
          </button>
        </form>
      </div>

      <!-- Daftar Walk-In Hari Ini -->
      <div class="card">
        <div class="card-head">
          <h3>📋 Walk-In Hari Ini</h3>
          <span><?= date('d F Y') ?></span>
        </div>
        <div class="card-body">
          <?php if(mysqli_num_rows($q_walkin_today)==0): ?>
          <div class="empty-state">
            <div class="empty-icon">🚗</div>
            <p>Belum ada walk-in hari ini</p>
          </div>
          <?php endif; ?>

          <?php while($wi=mysqli_fetch_assoc($q_walkin_today)):
            $sc=$wi['status_cuci'];
            $step=$sc==='belum_dicuci'?1:($sc==='diproses'?2:3);
            $badge_color=$sc==='belum_dicuci'?'var(--warning)':($sc==='diproses'?'#3b82f6':'var(--success)');
            $badge_label=$sc==='belum_dicuci'?'Antrian':($sc==='diproses'?'Dicuci':'Selesai');
          ?>
          <div class="booking-item" style="border-left:4px solid <?= $badge_color ?>;">
            <div class="booking-item-top">
              <div>
                <div class="booking-name"><?= htmlspecialchars($wi['nama_pelanggan']) ?></div>
                <div class="booking-paket">📞 <?= htmlspecialchars($wi['no_telepon']) ?> · 🚘 <?= htmlspecialchars(strtoupper($wi['plat_mobil'])) ?></div>
                <div class="booking-paket">🕐 <?= htmlspecialchars($wi['jam']) ?> · <?= htmlspecialchars($wi['nama_paket']) ?></div>
              </div>
              <div class="status-badge" style="background:<?= $badge_color ?>;"><?= $badge_label ?></div>
            </div>
            <div style="display:flex;gap:5px;margin-top:8px;">
              <?php foreach(['Antrian','Dicuci','Selesai'] as $i=>$s_label): $done=$step>=($i+1); ?>
              <div style="flex:1;height:5px;border-radius:3px;background:<?= $done?$badge_color:'var(--border)' ?>;"></div>
              <?php endforeach; ?>
            </div>
            <div style="font-size:11px;color:var(--muted);margin-top:4px;">Step <?= $step ?>/3 — <?= $badge_label ?></div>
          </div>
          <?php endwhile; ?>
        </div>
      </div>

    </div>

    <script>
    function pilihSlotWalkin(btn,slot){
      document.querySelectorAll('.slot-btn:not([disabled])').forEach(b=>{
        b.style.background='#e0f2fe';b.style.borderColor='#bae6fd';b.style.color='var(--text)';
      });
      btn.style.background='var(--navy)';btn.style.borderColor='var(--navy)';btn.style.color='white';
      document.getElementById('walkinJam').value=slot;
      document.getElementById('slotLabel').textContent=slot;
      document.getElementById('slotPilihInfo').style.display='block';
      document.getElementById('slotWarning').style.display='none';
    }
    function validasiWalkin(){
      if(!document.getElementById('walkinJam').value){
        document.getElementById('slotWarning').style.display='block'; return false;
      }
      return true;
    }
    </script>

    <?php endif; ?>


    <!-- ================================================
         KONFIRMASI PAGE
    ================================================ -->
    <?php if($page == 'konfirmasi'): ?>

    <?php
    $q_konfirmasi = mysqli_query($conn,"
        SELECT p.*,pl.nama_paket,pl.harga FROM pemesanan p
        JOIN paket_layanan pl ON p.id_paket=pl.id_paket
        WHERE p.status='pending' AND p.bukti_bayar IS NOT NULL AND p.bukti_bayar!=''
        ORDER BY p.created_at ASC
    ");
    $q_refund = mysqli_query($conn,"
        SELECT p.*,pl.nama_paket,pl.harga FROM pemesanan p
        JOIN paket_layanan pl ON p.id_paket=pl.id_paket
        WHERE p.status='dibatalkan' AND p.refund_status='menunggu' AND p.refund_nomor_rek IS NOT NULL
        ORDER BY p.created_at ASC
    ");
    $total_refund = mysqli_num_rows($q_refund);
    ?>

    <div class="page-header">
      <h1>Konfirmasi Pembayaran</h1>
      <p>Verifikasi bukti transfer dari pelanggan sebelum booking dikonfirmasi.</p>
    </div>

    <?php if(mysqli_num_rows($q_konfirmasi)==0): ?>
    <div class="card" style="margin-bottom:20px;">
      <div class="card-body" style="padding:22px;">
        <div class="empty-state">
          <div class="empty-icon">✅</div>
          <h3>Tidak ada pembayaran menunggu</h3>
          <p>Semua pembayaran sudah dikonfirmasi.</p>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php while($row=mysqli_fetch_assoc($q_konfirmasi)): ?>
    <div class="konfirmasi-card">
      <div class="konfirmasi-inner">
        <div>
          <p style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">Bukti Transfer</p>
          <a href="<?= htmlspecialchars($row['bukti_bayar']) ?>" target="_blank">
            <img src="<?= htmlspecialchars($row['bukti_bayar']) ?>" alt="Bukti Bayar"
              style="width:100%;max-width:200px;border-radius:12px;border:2px solid var(--border);object-fit:cover;cursor:zoom-in;transition:.2s;"
              onmouseover="this.style.borderColor='var(--accent)'" onmouseout="this.style.borderColor='var(--border)'">
          </a>
          <p style="font-size:11px;color:var(--muted);margin-top:6px;text-align:center;">Klik untuk perbesar</p>
        </div>
        <div>
          <p style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:12px;">Detail Transaksi</p>
          <table style="width:100%;font-size:13px;border-collapse:collapse;">
            <tr><td style="color:var(--muted);padding:5px 0;width:140px;">ID Pemesanan</td><td style="font-weight:600;">#<?= $row['id_pemesanan'] ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0;">Nama</td><td style="font-weight:600;"><?= htmlspecialchars($row['nama_pelanggan']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0;">Telepon</td><td><?= htmlspecialchars($row['no_telepon']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0;">Plat Mobil</td><td style="font-weight:600;text-transform:uppercase;"><?= htmlspecialchars($row['plat_mobil']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0;">Paket</td><td style="color:var(--accent);font-weight:600;"><?= htmlspecialchars($row['nama_paket']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0;">Tanggal</td><td><?= date('d M Y',strtotime($row['tanggal'])) ?> · <?= htmlspecialchars($row['jam']) ?></td></tr>
            <tr><td style="color:var(--muted);padding:5px 0;">Total Bayar</td><td style="font-size:18px;font-weight:800;color:var(--success);">Rp <?= number_format($row['harga'],0,',','.') ?></td></tr>
          </table>
          <div class="alert alert-warning" style="margin-top:12px;">⏳ Menunggu konfirmasi sejak: <strong><?= date('d M Y H:i',strtotime($row['created_at'])) ?></strong></div>
        </div>
      </div>

      <div class="konfirmasi-actions">
        <form method="POST" onsubmit="return confirm('Konfirmasi pembayaran ini?')">
          <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
          <input type="hidden" name="aksi_konfirmasi" value="konfirmasi">
          <button type="submit" class="btn btn-success btn-full">✅ Konfirmasi Lunas</button>
        </form>
        <div>
          <button type="button" class="btn btn-danger btn-full"
            onclick="bukaModalTolak(<?= $row['id_pemesanan'] ?>, '<?= htmlspecialchars(addslashes($row['nama_pelanggan'])) ?>', '<?= number_format($row['harga'],0,'.','.') ?>', <?= !empty($row['refund_nomor_rek']) ? 'true' : 'false' ?>, '<?= htmlspecialchars(addslashes($row['refund_bank'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($row['refund_nomor_rek'] ?? '')) ?>', '<?= htmlspecialchars(addslashes($row['refund_nama_rek'] ?? '')) ?>')">
            &#x274C; Tolak Pembayaran
          </button>
        </div>
        <a href="<?= htmlspecialchars($row['bukti_bayar']) ?>" target="_blank" class="btn btn-secondary btn-full" style="text-decoration:none;justify-content:center;">🔍 Lihat Fullscreen</a>
      </div>
    </div>
    <?php endwhile; ?>

    <!-- Refund -->
    <?php if($total_refund > 0): ?>
    <div style="margin-top:32px;">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:18px;">
        <h2 style="font-size:18px;font-weight:800;color:var(--text);">💸 Pengembalian Dana</h2>
        <span class="nav-badge" style="position:static;font-size:12px;padding:3px 10px;"><?= $total_refund ?> menunggu</span>
      </div>
      <div style="display:grid;gap:14px;">
        <?php mysqli_data_seek($q_refund,0); while($rr=mysqli_fetch_assoc($q_refund)): ?>
        <div style="background:var(--surface);border-radius:var(--radius);padding:22px 26px;box-shadow:var(--shadow);border:1px solid var(--border);border-left:5px solid var(--danger);display:grid;grid-template-columns:1fr auto;gap:24px;align-items:center;">
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:16px;">
            <div>
              <p style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;margin-bottom:4px;">Pelanggan</p>
              <p style="font-weight:800;font-size:15px;"><?= htmlspecialchars($rr['nama_pelanggan']) ?></p>
              <p style="font-size:12px;color:var(--muted);"><?= htmlspecialchars($rr['no_telepon']) ?></p>
            </div>
            <div>
              <p style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;margin-bottom:4px;">Paket Dibatalkan</p>
              <p style="font-weight:700;color:var(--accent);font-size:14px;"><?= htmlspecialchars($rr['nama_paket']) ?></p>
              <p style="font-size:12px;color:var(--muted);"><?= date('d M Y',strtotime($rr['tanggal'])) ?></p>
            </div>
            <div>
              <p style="font-size:11px;color:var(--muted);font-weight:700;text-transform:uppercase;margin-bottom:4px;">Nominal Refund</p>
              <p style="font-weight:800;font-size:20px;color:var(--success);">Rp <?= number_format($rr['harga'],0,',','.') ?></p>
            </div>
            <div style="background:#fff7ed;border-radius:12px;padding:14px 16px;border:1px solid #fed7aa;">
              <p style="font-size:11px;color:#92400e;font-weight:700;text-transform:uppercase;margin-bottom:6px;">Transfer ke</p>
              <p style="font-weight:800;font-size:15px;"><?= htmlspecialchars($rr['refund_bank']??'-') ?></p>
              <p style="font-size:15px;font-weight:800;letter-spacing:1px;margin:3px 0;"><?= htmlspecialchars($rr['refund_nomor_rek']??'-') ?></p>
              <p style="font-size:12px;color:var(--muted);">a/n <?= htmlspecialchars($rr['refund_nama_rek']??'-') ?></p>
            </div>
          </div>
          <form method="POST" onsubmit="return confirm('Tandai refund ini sudah ditransfer?')">
            <input type="hidden" name="id_pemesanan" value="<?= $rr['id_pemesanan'] ?>">
            <button type="submit" name="selesai_refund" class="btn btn-success">✅ Tandai Selesai</button>
          </form>
        </div>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>


    <!-- ================================================
         ADMIN PAGE
    ================================================ -->
    <?php if($page == 'admin'): ?>

    <div class="page-header">
      <h1>Manajemen Admin</h1>
      <p>Tambah akun admin baru untuk mengakses dashboard.</p>
    </div>

    <div style="max-width:480px;">
      <div class="form-card">
        <h3>Tambah Admin Baru</h3>
        <form method="POST" action="tambah_admin.php">
          <div class="form-group">
            <label class="form-label">No. Telepon</label>
            <input type="text" name="no_telepon" class="form-control" placeholder="No Telepon Admin" required>
          </div>
          <div class="form-group">
            <label class="form-label">Email</label>
            <input type="email" name="email" class="form-control" placeholder="Email Admin" required>
          </div>
          <div class="form-group">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="Password" required>
          </div>
          <button type="submit" class="btn btn-primary btn-full">Tambah Admin</button>
        </form>
      </div>
    </div>

    <?php endif; ?>

  </main>

</div><!-- /main-wrap -->

</div><!-- /layout -->

<!-- =====================================================
     SCRIPTS
===================================================== -->
<script>
// Sidebar toggle (mobile)
function toggleSidebar(){
  document.getElementById('sidebar').classList.toggle('open');
}

// Close sidebar on outside click (mobile)
document.addEventListener('click', function(e){
  const sb = document.getElementById('sidebar');
  const toggle = document.querySelector('.sidebar-toggle');
  if(sb.classList.contains('open') && !sb.contains(e.target) && !toggle.contains(e.target)){
    sb.classList.remove('open');
  }
});

// Toggle edit form (menu page)
function toggleEdit(id){
  const x = document.getElementById('edit'+id);
  x.style.display = x.style.display==='none' ? 'block' : 'none';
}

// Kalender dashboard
const BULAN_KAL = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

function klikKalender(el){
  if(el.dataset.disabled) return;
  const tgl = el.dataset.tgl;
  if(!tgl) return;

  document.querySelectorAll('.calendar td[data-tgl]').forEach(t=>{
    if(t.dataset.today){ t.style.background='var(--navy)'; t.style.color='white'; return; }
    if(t.dataset.disabled){ t.style.background='#e5e7eb'; t.style.color='#9ca3af'; return; }
    t.style.background='var(--bg)'; t.style.color='';
  });
  if(!el.dataset.today){ el.style.background='var(--accent)'; el.style.color='white'; }

  const panel=document.getElementById('panelKalender');
  const label=document.getElementById('panelTanggalLabel');
  const isi=document.getElementById('panelKalenderIsi');
  const parts=tgl.split('-');
  label.textContent='Booking '+parseInt(parts[2])+' '+BULAN_KAL[parseInt(parts[1])-1]+' '+parts[0];
  isi.innerHTML='<p style="color:var(--muted);font-size:13px;">Memuat...</p>';
  panel.style.display='block';

  fetch('dashboard_admin.php?ajax_booking=1&tgl='+tgl).then(r=>r.json()).then(data=>{
    if(!data.length){ isi.innerHTML='<p style="color:var(--muted);font-size:13px;">Tidak ada booking pada tanggal ini.</p>'; return; }
    const statusColor={belum_dicuci:'var(--warning)',diproses:'#3b82f6',selesai:'var(--success)'};
    const statusLabel={belum_dicuci:'Belum Dicuci',diproses:'Diproses',selesai:'Selesai'};
    isi.innerHTML=data.map(b=>`
      <div style="background:white;border-radius:10px;padding:11px 14px;margin-bottom:8px;border:1px solid var(--border);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;">
          <span style="font-weight:700;color:var(--text);font-size:13px;">${b.nama_pelanggan}</span>
          <span style="font-size:11px;font-weight:700;color:white;background:${statusColor[b.status_cuci]||'#9ca3af'};padding:2px 8px;border-radius:12px;">${statusLabel[b.status_cuci]||b.status_cuci}</span>
        </div>
        <div style="font-size:12px;color:var(--muted);">${b.nama_paket} · <strong>${b.jam}</strong></div>
        <div style="font-size:11px;color:var(--muted);margin-top:3px;">Plat: ${b.plat_mobil||'-'}</div>
      </div>
    `).join('');
  }).catch(()=>{ isi.innerHTML='<p style="color:var(--danger);font-size:13px;">Gagal memuat data.</p>'; });
}
</script>


<!-- ============================================================
     MODAL TOLAK PEMBAYARAN + OPSI REFUND (ADMIN)
     ============================================================ -->
<div id="overlayTolakAdmin" onclick="tutupModalTolak()"
  style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:1998;backdrop-filter:blur(3px);"></div>

<div id="modalTolakAdmin"
  style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);
         z-index:1999;width:94%;max-width:480px;background:#fff;border-radius:16px;
         box-shadow:0 24px 64px rgba(0,0,0,0.22);overflow:hidden;">

  <!-- Header -->
  <div style="background:#1a2236;padding:20px 24px 16px;position:relative;">
    <div style="font-size:22px;margin-bottom:6px;">&#x274C;</div>
    <h3 style="color:#fff;font-size:16px;font-weight:800;margin:0;">Tolak Pembayaran</h3>
    <p style="color:rgba(255,255,255,.55);font-size:12px;margin-top:4px;" id="modalTolakNama"></p>
    <button onclick="tutupModalTolak()"
      style="position:absolute;top:16px;right:16px;background:rgba(255,255,255,.1);
             border:none;color:#fff;width:28px;height:28px;border-radius:50%;
             font-size:16px;cursor:pointer;line-height:1;">&#x2715;</button>
  </div>

  <form method="POST" id="formTolakAdmin" style="padding:20px 24px 24px;">
    <input type="hidden" name="id_pemesanan" id="tolakIdPemesanan">
    <input type="hidden" name="aksi_konfirmasi" value="tolak">

    <!-- Pilihan refund -->
    <div style="margin-bottom:16px;">
      <label style="display:flex;align-items:flex-start;gap:12px;cursor:pointer;background:#fef2f2;border:1.5px solid #fecaca;border-radius:10px;padding:14px 16px;">
        <input type="checkbox" name="aktifkan_refund" id="chkRefund" onchange="toggleRefundAdmin()"
          style="margin-top:2px;accent-color:#ef4444;width:16px;height:16px;flex-shrink:0;">
        <div>
          <div style="font-size:13px;font-weight:800;color:#b91c1c;">Proses Pengembalian Dana (Refund)</div>
          <div style="font-size:12px;color:#ef4444;margin-top:2px;line-height:1.4;">
            Centang ini jika pelanggan sudah membayar dan berhak mendapat refund.
            Admin wajib transfer ke rekening pelanggan.
          </div>
        </div>
      </label>
    </div>

    <!-- Info rekening dari user -->
    <div id="refundAdminBlock" style="display:none;">
      <div id="infoRekUser" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 16px;margin-bottom:14px;font-size:13px;display:none;">
        <div style="font-size:11px;font-weight:800;color:#15803d;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">&#x1F4CB; Rekening dari Pelanggan</div>
        <div id="infoRekContent" style="color:#166534;line-height:1.8;"></div>
        <div style="font-size:11px;color:#15803d;margin-top:8px;">Kosongkan kolom di bawah jika ingin menggunakan rekening ini.</div>
      </div>

      <div style="font-size:12px;font-weight:800;color:#374151;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
        Atau isi / koreksi rekening refund:
      </div>

      <div style="margin-bottom:10px;">
        <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Nama Pemilik Rekening</label>
        <input type="text" name="manual_refund_nama" id="manualRefundNama" placeholder="Contoh: Budi Santoso"
          style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;font-family:inherit;">
      </div>
      <div style="margin-bottom:10px;">
        <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Bank / E-Wallet</label>
        <select name="manual_refund_bank" id="manualRefundBank"
          style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;background:#fff;font-family:inherit;">
          <option value="">-- Pilih Bank / E-Wallet --</option>
          <optgroup label="Bank">
            <option>BCA</option><option>BNI</option><option>BRI</option>
            <option>Mandiri</option><option>CIMB Niaga</option><option>BSI</option><option>Bank Lainnya</option>
          </optgroup>
          <optgroup label="E-Wallet">
            <option>GoPay</option><option>OVO</option><option>Dana</option><option>ShopeePay</option>
          </optgroup>
        </select>
      </div>
      <div style="margin-bottom:18px;">
        <label style="display:block;font-size:11px;font-weight:700;color:#374151;margin-bottom:5px;">Nomor Rekening / E-Wallet</label>
        <input type="text" name="manual_refund_nomor" id="manualRefundNomor" placeholder="Contoh: 1234567890" inputmode="numeric"
          style="width:100%;padding:9px 12px;border:1.5px solid #e5e7eb;border-radius:8px;font-size:13px;outline:none;font-family:inherit;">
      </div>

      <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:8px;padding:10px 12px;font-size:12px;color:#92400e;margin-bottom:16px;">
        &#x26A0;&#xFE0F; Setelah ditolak dengan opsi refund, transaksi akan muncul di bagian
        <strong>Pengembalian Dana</strong> dan perlu ditandai selesai setelah transfer.
      </div>
    </div>

    <button type="submit" id="btnSubmitTolak"
      style="width:100%;padding:13px;border:none;border-radius:10px;
             background:#ef4444;color:#fff;font-size:14px;font-weight:800;
             cursor:pointer;transition:.18s;"
      onmouseover="this.style.background='#dc2626'"
      onmouseout="this.style.background='#ef4444'"
      onclick="return konfirmasiTolak()">
      &#x274C; Tolak Pembayaran
    </button>
    <button type="button" onclick="tutupModalTolak()"
      style="width:100%;padding:10px;border:1.5px solid #e5e7eb;border-radius:10px;
             background:#fff;color:#6b7280;font-size:13px;font-weight:700;
             cursor:pointer;margin-top:8px;">
      Batal
    </button>
  </form>
</div>

<script>
var _tolakHasRefundData = false;
var _tolakRefundBank = '';
var _tolakRefundNomor = '';
var _tolakRefundNama = '';

function bukaModalTolak(id, nama, nominal, hasRefundData, refundBank, refundNomor, refundNama) {
  _tolakHasRefundData = hasRefundData;
  _tolakRefundBank    = refundBank;
  _tolakRefundNomor   = refundNomor;
  _tolakRefundNama    = refundNama;

  document.getElementById('tolakIdPemesanan').value = id;
  document.getElementById('modalTolakNama').textContent =
    nama + ' \u2014 Rp ' + nominal.replace('.', '.');
  document.getElementById('chkRefund').checked = false;
  document.getElementById('refundAdminBlock').style.display = 'none';
  document.getElementById('manualRefundNama').value  = '';
  document.getElementById('manualRefundBank').value  = '';
  document.getElementById('manualRefundNomor').value = '';
  document.getElementById('overlayTolakAdmin').style.display = 'block';
  document.getElementById('modalTolakAdmin').style.display   = 'block';
  document.body.style.overflow = 'hidden';
}

function tutupModalTolak() {
  document.getElementById('overlayTolakAdmin').style.display = 'none';
  document.getElementById('modalTolakAdmin').style.display   = 'none';
  document.body.style.overflow = '';
}

function toggleRefundAdmin() {
  var checked = document.getElementById('chkRefund').checked;
  document.getElementById('refundAdminBlock').style.display = checked ? 'block' : 'none';

  if (checked && _tolakHasRefundData) {
    var infoDiv  = document.getElementById('infoRekUser');
    var infoContent = document.getElementById('infoRekContent');
    infoDiv.style.display = 'block';
    infoContent.innerHTML =
      '<strong>Bank:</strong> ' + (_tolakRefundBank  || '-') + '<br>' +
      '<strong>No. Rek:</strong> ' + (_tolakRefundNomor || '-') + '<br>' +
      '<strong>a/n:</strong> ' + (_tolakRefundNama  || '-');
  } else {
    document.getElementById('infoRekUser').style.display = 'none';
  }
}

function konfirmasiTolak() {
  var chk = document.getElementById('chkRefund').checked;
  if (chk) {
    var nama  = document.getElementById('manualRefundNama').value.trim()  || _tolakRefundNama;
    var bank  = document.getElementById('manualRefundBank').value          || _tolakRefundBank;
    var nomor = document.getElementById('manualRefundNomor').value.trim() || _tolakRefundNomor;
    if (!nama || !bank || !nomor) {
      alert('Lengkapi data rekening untuk refund, atau pastikan pelanggan sudah mengisi data rekening.');
      return false;
    }
    return confirm('Tolak & proses refund ke ' + bank + ' ' + nomor + ' a/n ' + nama + '?');
  }
  return confirm('Yakin menolak pembayaran ini? Status akan menjadi Ditolak (tanpa refund).');
}

document.addEventListener('keydown', function(e) { if (e.key === 'Escape') tutupModalTolak(); });
</script>

</body>
</html>