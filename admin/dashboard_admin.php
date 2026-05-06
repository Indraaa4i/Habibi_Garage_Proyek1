<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: ../user/landing_page.php");
    exit;
}

// Proteksi login
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: ../user/landing_page.php");
    exit;
}

include '../user/koneksi.php';


// ─────────────────────────────────────────────
// STATISTIK
// ─────────────────────────────────────────────
$hari_ini = date('Y-m-d');

// total booking hari ini
$q_booking_hari = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM pemesanan 
    WHERE tanggal = '$hari_ini'
");
$total_booking = mysqli_fetch_assoc($q_booking_hari)['total'];

// pending (user sudah upload bukti)
$q_pending = mysqli_query($conn, "
    SELECT COUNT(*) as total 
    FROM pemesanan 
    WHERE status = 'pending'
");
$total_pending = mysqli_fetch_assoc($q_pending)['total'];

// income hari ini
$q_income_today = mysqli_query($conn, "
    SELECT SUM(pl.harga) as total
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.status = 'lunas'
    AND DATE(p.tanggal) = CURDATE()
");
$income_today = mysqli_fetch_assoc($q_income_today)['total'] ?? 0;

// income bulan ini
$q_income_month = mysqli_query($conn, "
    SELECT SUM(pl.harga) as total
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.status = 'lunas'
    AND MONTH(p.tanggal) = MONTH(CURDATE())
    AND YEAR(p.tanggal) = YEAR(CURDATE())
");
$income_month = mysqli_fetch_assoc($q_income_month)['total'] ?? 0;


// ─────────────────────────────────────────────
// FLOW STATUS ADMIN
// ─────────────────────────────────────────────
if (isset($_POST['aksi']) && isset($_POST['id_pemesanan'])) {

    $id   = mysqli_real_escape_string($conn, $_POST['id_pemesanan']);
    $aksi = $_POST['aksi'];

    // KONFIRMASI PEMBAYARAN → LUNAS
    if ($aksi === 'lunas') {
        mysqli_query($conn, "
            UPDATE pemesanan 
            SET status='lunas'
            WHERE id_pemesanan='$id'
        ");

        echo "<script>
            alert('Pembayaran berhasil dikonfirmasi!');
            window.location.href='bukti.php?id=$id';
        </script>";
        exit;
    }

    // TOLAK PEMBAYARAN
    if ($aksi === 'batal') {
        mysqli_query($conn, "
            UPDATE pemesanan 
            SET status='dibatalkan'
            WHERE id_pemesanan='$id'
        ");
    }

    header("Location: dashboard_admin.php");
    exit;
}


// ─────────────────────────────────────────────
// UPDATE STATUS CUCI
// ─────────────────────────────────────────────
if (isset($_POST['aksi_cuci']) && isset($_POST['id_pemesanan'])) {

    $id = mysqli_real_escape_string($conn, $_POST['id_pemesanan']);
    $status_cuci = mysqli_real_escape_string($conn, $_POST['status_cuci']);

    mysqli_query($conn, "
        UPDATE pemesanan
        SET status_cuci = '$status_cuci'
        WHERE id_pemesanan = '$id'
    ");

    header("Location: dashboard_admin.php");
    exit;
}


// ─────────────────────────────────────────────
// CRUD PAKET
// ─────────────────────────────────────────────
if (isset($_POST['aksi_menu'])) {

    $aksi = $_POST['aksi_menu'];

    if ($aksi === 'tambah') {
        $nama_paket = mysqli_real_escape_string($conn, $_POST['nama_paket']);
        $harga      = mysqli_real_escape_string($conn, $_POST['harga']);
        $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']);

        mysqli_query($conn, "
            INSERT INTO paket_layanan (nama_paket, harga, deskripsi)
            VALUES ('$nama_paket', '$harga', '$deskripsi')
        ");
    }

    if ($aksi === 'hapus') {
        $id_paket = mysqli_real_escape_string($conn, $_POST['id_paket']);

        mysqli_query($conn, "
            DELETE FROM paket_layanan 
            WHERE id_paket='$id_paket'
        ");
    }

    header("Location: dashboard_admin.php");
    exit;
}


// ─────────────────────────────────────────────
// TAMBAH PELANGGAN LANGSUNG (WALK-IN)
// ─────────────────────────────────────────────
if (isset($_POST['aksi_pelanggan']) && $_POST['aksi_pelanggan'] === 'tambah_langsung') {

    $nama_pelanggan = mysqli_real_escape_string($conn, $_POST['nama_pelanggan']);
    $no_telepon     = mysqli_real_escape_string($conn, $_POST['no_telepon']);
    $id_paket       = mysqli_real_escape_string($conn, $_POST['id_paket']);
    $tanggal        = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam            = mysqli_real_escape_string($conn, $_POST['jam']);

    mysqli_query($conn, "
        INSERT INTO pemesanan (
            nama_pelanggan, no_telepon, id_paket, tanggal, jam, status, status_cuci, created_at
        )
        VALUES (
            '$nama_pelanggan', '$no_telepon', '$id_paket', '$tanggal', '$jam', 'pending', 'belum_dicuci', NOW()
        )
    ");

    header("Location: dashboard_admin.php");
    exit;
}


// ─────────────────────────────────────────────
// QUERY DATA
// ─────────────────────────────────────────────

// semua booking
$q_antrian = mysqli_query($conn, "
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    ORDER BY p.created_at DESC
");

// pending
$q_konfirmasi = mysqli_query($conn, "
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.status = 'pending'
    ORDER BY p.tanggal ASC, p.jam ASC
");

// paket
$q_paket = mysqli_query($conn, "
    SELECT * FROM paket_layanan 
    ORDER BY id_paket DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Habibi Garage</title>
    <link rel="stylesheet" href="../css/dashboard_admin.css">
</head>
<body>

<div class="sidebar">
    <img src="../img/logo.png" alt="Habibi Garage" class="sidebar-logo">
    <ul id="menu">
        <li class="menu-item active" data-target="dashboard-section">Dashboard</li>

        <li class="menu-item" data-target="konfirmasi-section">
            Konfirmasi Pembayaran
            <?php if ($total_pending > 0): ?>
                <span class="notif-badge"><?= $total_pending ?></span>
            <?php endif; ?>
        </li>

        <li class="menu-item" data-target="recap-section">Recap & Income</li>
        <li class="menu-item" data-target="pengaturan-section">Pengaturan</li>
        <li class="menu-item logout-item" onclick="confirmLogout()">Logout</li>
    </ul>
</div>

<div class="main">
    <div class="topbar">
        <input type="text" class="search" placeholder="Cari data pelanggan..." id="searchInput" oninput="filterTable()">
        <div class="topbar-right">
            <div class="topbar-date"><?= date('d F Y') ?></div>
            <button class="refresh-btn" onclick="location.reload()">&#8635; Refresh</button>
        </div>
    </div>

    <!-- DASHBOARD -->
    <div id="dashboard-section" class="content-section active">
        <h1>Dashboard</h1>
        <p class="subtitle">Monitoring operasional real-time dari database.</p>

        <div class="stats-container">
            <div class="stat-card blue">
                <span>Booking Hari Ini</span>
                <h2><?= $total_booking ?></h2>
            </div>
            <div class="stat-card orange">
                <span>Menunggu Konfirmasi</span>
                <h2><?= $total_pending ?></h2>
            </div>
        </div>

        <div class="card">
            <h3>Tambah Pelanggan Langsung</h3>
            <form method="POST" class="form-paket">
                <input type="hidden" name="aksi_pelanggan" value="tambah_langsung">

                <div class="form-grid">
                    <input type="text" name="nama_pelanggan" placeholder="Nama Pelanggan" required>
                    <input type="text" name="no_telepon" placeholder="No Telepon" required>

                    <select name="id_paket" required>
                        <option value="">Pilih Paket</option>
                        <?php
                        $paket_form = mysqli_query($conn, "SELECT * FROM paket_layanan ORDER BY nama_paket ASC");
                        while ($p = mysqli_fetch_assoc($paket_form)):
                        ?>
                            <option value="<?= $p['id_paket'] ?>">
                                <?= htmlspecialchars($p['nama_paket']) ?> - Rp <?= number_format($p['harga'], 0, ',', '.') ?>
                            </option>
                        <?php endwhile; ?>
                    </select>

                    <input type="date" name="tanggal" required>
                    <input type="time" name="jam" required>
                </div>

                <button type="submit" class="btn-lunas">Tambah Pelanggan</button>
            </form>
        </div>

        <div class="card">
            <h3>Semua Booking Masuk</h3>
            <table class="table" id="mainTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pelanggan</th>
                        <th>Paket</th>
                        <th>Jadwal</th>
                        <th>Status Bayar</th>
                        <th>Status Cuci</th>
                        <th>Aksi Cuci</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; $ada = false; while ($row = mysqli_fetch_assoc($q_antrian)): $ada = true; ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong><br>
                            <small><?= htmlspecialchars($row['no_telepon']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['nama_paket']) ?><br>
                            <small>Rp <?= number_format($row['harga'], 0, ',', '.') ?></small>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($row['tanggal'])) ?><br>
                            <small><?= htmlspecialchars($row['jam']) ?></small>
                        </td>

                        <td>
                            <?php
                            $status = strtolower($row['status']);
                            $badgeClass = 'badge-pending';

                            if ($status == 'lunas') {
                                $badgeClass = 'badge-lunas';
                            } elseif ($status == 'dibatalkan') {
                                $badgeClass = 'badge-batal';
                            }
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>

                        <td>
                            <?php
                            $cuci = strtolower($row['status_cuci']);
                            $badgeCuci = 'badge-batal';

                            if ($cuci == 'diproses') {
                                $badgeCuci = 'badge-proses';
                            } elseif ($cuci == 'selesai') {
                                $badgeCuci = 'badge-lunas';
                            }
                            ?>
                            <span class="badge <?= $badgeCuci ?>">
                                <?= ucfirst(str_replace('_', ' ', $row['status_cuci'])) ?>
                            </span>
                        </td>

                        <td>
                            <form method="POST">
                                <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">

                                <select name="status_cuci" required>
                                    <option value="belum_dicuci" <?= $row['status_cuci'] == 'belum_dicuci' ? 'selected' : '' ?>>Belum Dicuci</option>
                                    <option value="diproses" <?= $row['status_cuci'] == 'diproses' ? 'selected' : '' ?>>Diproses</option>
                                    <option value="selesai" <?= $row['status_cuci'] == 'selesai' ? 'selected' : '' ?>>Selesai</option>
                                </select>

                                <button type="submit" name="aksi_cuci" class="btn-lunas" style="margin-top:5px;">
                                    Update
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada): ?>
                        <tr class="empty-row"><td colspan="7">Belum ada booking masuk.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- KONFIRMASI PEMBAYARAN -->
    <div id="konfirmasi-section" class="content-section">
        <h1>Konfirmasi Pembayaran</h1>
        <p class="subtitle">Cek bukti pembayaran pelanggan dan konfirmasi status pembayaran.</p>

        <div class="card">
            <h3>Daftar Menunggu Konfirmasi</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pelanggan</th>
                        <th>Paket</th>
                        <th>Jadwal</th>
                        <th>Bukti Bayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 1; $ada = false; mysqli_data_seek($q_konfirmasi, 0); while ($row = mysqli_fetch_assoc($q_konfirmasi)): $ada = true; ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong><br>
                            <small><?= htmlspecialchars($row['no_telepon']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['nama_paket']) ?><br>
                            <small>Rp <?= number_format($row['harga'], 0, ',', '.') ?></small>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($row['tanggal'])) ?><br>
                            <small><?= htmlspecialchars($row['jam']) ?></small>
                        </td>
                        <td>
                            <?php if (!empty($row['bukti_bayar'])): ?>
                                <a href="<?= htmlspecialchars($row['bukti_bayar']) ?>" target="_blank" class="btn-lunas">
                                    Lihat Bukti</a>
                                    <?php else: ?>
                                        <span>Tidak ada bukti</span>
                                        <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
                                <input type="hidden" name="aksi" value="lunas">
                                <button type="submit" class="btn-lunas">✔ Lunas</button>
                            </form>

                            <form method="POST" style="display:inline-block;">
                                <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
                                <input type="hidden" name="aksi" value="batal">
                                <button type="submit" class="btn-batal">✖ Tolak</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada): ?>
                        <tr class="empty-row"><td colspan="6">Belum ada pembayaran yang menunggu konfirmasi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RECAP -->
    <div id="recap-section" class="content-section">
        <h1>Recap & Income</h1>
        <p class="subtitle">Ringkasan pendapatan harian, bulanan, dan histori transaksi.</p>

        <div class="stats-container">
            <div class="stat-card green">
                <span>Income Hari Ini</span>
                <h2>Rp <?= number_format($income_today, 0, ',', '.') ?></h2>
            </div>
            <div class="stat-card blue">
                <span>Income Bulan Ini</span>
                <h2>Rp <?= number_format($income_month, 0, ',', '.') ?></h2>
            </div>
        </div>

        <div class="card">
            <h3>Cari Recap Bulanan</h3>

            <form method="GET" class="form-paket" style="margin-bottom:20px;">
                <div class="form-grid">
                    <select name="bulan">
                        <option value="">Pilih Bulan</option>
                        <?php for($i=1; $i<=12; $i++): ?>
                            <option value="<?= $i ?>" <?= (isset($_GET['bulan']) && $_GET['bulan'] == $i) ? 'selected' : '' ?>>
                                <?= date('F', mktime(0,0,0,$i,1)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <select name="tahun">
                        <option value="">Pilih Tahun</option>
                        <?php for($y=date('Y'); $y>=2023; $y--): ?>
                            <option value="<?= $y ?>" <?= (isset($_GET['tahun']) && $_GET['tahun'] == $y) ? 'selected' : '' ?>>
                                <?= $y ?>
                            </option>
                        <?php endfor; ?>
                    </select>

                    <button type="submit" class="btn-lunas">Cari Recap</button>
                </div>
            </form>

            <?php
            $where = "WHERE p.status='lunas'";
            if (!empty($_GET['bulan']) && !empty($_GET['tahun'])) {
                $bulan = (int) $_GET['bulan'];
                $tahun = (int) $_GET['tahun'];
                $where .= " AND MONTH(p.tanggal)='$bulan' AND YEAR(p.tanggal)='$tahun'";
            }

            $q_filter = mysqli_query($conn, "
                SELECT p.*, pl.nama_paket, pl.harga
                FROM pemesanan p
                JOIN paket_layanan pl ON p.id_paket = pl.id_paket
                $where
                ORDER BY p.tanggal DESC, p.jam DESC
            ");

            $q_total_filter = mysqli_query($conn, "
                SELECT SUM(pl.harga) as total
                FROM pemesanan p
                JOIN paket_layanan pl ON p.id_paket = pl.id_paket
                $where
            ");
            $total_filter = mysqli_fetch_assoc($q_total_filter)['total'] ?? 0;
            ?>

            <div style="margin-bottom:15px;">
                <strong>Total Pendapatan:</strong> Rp <?= number_format($total_filter, 0, ',', '.') ?>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pelanggan</th>
                        <th>Paket</th>
                        <th>Tanggal</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; $ada=false; while($row=mysqli_fetch_assoc($q_filter)): $ada=true; ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($row['nama_pelanggan']) ?></td>
                        <td><?= htmlspecialchars($row['nama_paket']) ?></td>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(!$ada): ?>
                        <tr class="empty-row"><td colspan="5">Tidak ada data recap.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PENGATURAN -->
    <div id="pengaturan-section" class="content-section">
        <h1>Pengaturan</h1>
        <p class="subtitle">Kelola paket layanan dan admin.</p>

        <div class="card">
            <h3>Tambah Paket</h3>
            <form method="POST" class="form-paket">
                <input type="hidden" name="aksi_menu" value="tambah">

                <div class="form-grid">
                    <input type="text" name="nama_paket" placeholder="Nama Paket" required>
                    <input type="number" name="harga" placeholder="Harga" required>
                    <input type="text" name="deskripsi" placeholder="Deskripsi" required>
                </div>

                <button type="submit" class="btn-lunas">Tambah Paket</button>
            </form>
        </div>

        <div class="card">
            <h3>Daftar Paket</h3>
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nama Paket</th>
                        <th>Harga</th>
                        <th>Deskripsi</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no=1; mysqli_data_seek($q_paket, 0); while($paket=mysqli_fetch_assoc($q_paket)): ?>
                    <tr>
                        <td><?= $no++ ?></td>
                        <td><?= htmlspecialchars($paket['nama_paket']) ?></td>
                        <td>Rp <?= number_format($paket['harga'], 0, ',', '.') ?></td>
                        <td><?= htmlspecialchars($paket['deskripsi']) ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Hapus paket ini?')">
                                <input type="hidden" name="aksi_menu" value="hapus">
                                <input type="hidden" name="id_paket" value="<?= $paket['id_paket'] ?>">
                                <button type="submit" class="btn-batal">Hapus</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="card">
            <h3>Tambah Admin Baru</h3>
            <form method="POST" action="tambah_admin.php" class="form-paket">
                <div class="form-grid">
                    <input type="text" name="no_telepon" placeholder="No Telepon Admin" required>
                    <input type="email" name="email" placeholder="Email Admin" required>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn-lunas">Tambah Admin</button>
            </form>
        </div>
    </div>
</div>

<script src="../js/dashboard_admin.js"></script>
<script>
function confirmLogout() {
    const keluar = confirm("Yakin ingin logout?");
    if (keluar) {
        window.location.href = "dashboard_admin.php?logout=true";
    }
}
</script>
</body>
</html>