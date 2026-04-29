<?php
session_start();

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login_admin.php");
    exit;
}

// Proteksi login
if (!isset($_SESSION['status']) || $_SESSION['status'] !== 'login') {
    header("Location: login_admin.php");
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
// FLOW STATUS ADMIN (FIXED)
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

        // langsung arahkan ke bukti
        echo "<script>
            alert('Pembayaran berhasil dikonfirmasi!');
            window.location.href='bukti.php?id=$id';
        </script>";
        exit;
    }

    // BATAL
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

    if ($aksi === 'edit') {
        $id_paket   = mysqli_real_escape_string($conn, $_POST['id_paket']);
        $nama_paket = mysqli_real_escape_string($conn, $_POST['nama_paket']);
        $harga      = mysqli_real_escape_string($conn, $_POST['harga']);
        $deskripsi  = mysqli_real_escape_string($conn, $_POST['deskripsi']);

        mysqli_query($conn, "
            UPDATE paket_layanan 
            SET nama_paket='$nama_paket', harga='$harga', deskripsi='$deskripsi'
            WHERE id_paket='$id_paket'
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
    $plat_mobil     = mysqli_real_escape_string($conn, $_POST['plat_mobil']);
    $jenis_mobil    = mysqli_real_escape_string($conn, $_POST['jenis_mobil']);
    $warna_mobil    = mysqli_real_escape_string($conn, $_POST['warna_mobil']);
    $id_paket       = mysqli_real_escape_string($conn, $_POST['id_paket']);
    $tanggal        = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam            = mysqli_real_escape_string($conn, $_POST['jam']);

    mysqli_query($conn, "
        INSERT INTO pemesanan (
            nama_pelanggan, no_telepon, plat_mobil, jenis_mobil, warna_mobil,
            id_paket, tanggal, jam, status, created_at
        )
        VALUES (
            '$nama_pelanggan', '$no_telepon', '$plat_mobil', '$jenis_mobil', '$warna_mobil',
            '$id_paket', '$tanggal', '$jam', 'proses', NOW()
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

// pending (menunggu admin cek)
$q_konfirmasi = mysqli_query($conn, "
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.status = 'pending'
    ORDER BY p.tanggal ASC, p.jam ASC
");

// recap lunas
$q_recap = mysqli_query($conn, "
    SELECT p.*, pl.nama_paket, pl.harga
    FROM pemesanan p
    JOIN paket_layanan pl ON p.id_paket = pl.id_paket
    WHERE p.status = 'lunas'
    ORDER BY p.created_at DESC
    LIMIT 50
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
        <li class="menu-item" data-target="pengaturan-section">Pengaturan Menu</li>

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

    <!-- Dashboard -->
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

        <!-- Tambah Pelanggan Langsung -->
        <div class="card">
            <h3>Tambah Pelanggan Langsung</h3>
            <form method="POST" class="form-paket">
                <input type="hidden" name="aksi_pelanggan" value="tambah_langsung">

                <div class="form-grid">
                    <input type="text" name="nama_pelanggan" placeholder="Nama Pelanggan" required>
                    <input type="text" name="no_telepon" placeholder="No Telepon" required>
                    <input type="text" name="plat_mobil" placeholder="Plat Mobil" required>
                    <input type="text" name="jenis_mobil" placeholder="Jenis Mobil" required>
                    <input type="text" name="warna_mobil" placeholder="Warna Mobil" required>

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

        <!-- Semua Booking -->
        <div class="card">
            <h3>Semua Booking Masuk</h3>
            <table class="table" id="mainTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pelanggan</th>
                        <th>Kendaraan</th>
                        <th>Paket</th>
                        <th>Jadwal</th>
                        <th>Status</th>
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
                            <?= htmlspecialchars($row['plat_mobil']) ?><br>
                            <small><?= htmlspecialchars($row['jenis_mobil']) ?> · <?= htmlspecialchars($row['warna_mobil']) ?></small>
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

                            if ($status == 'proses') {
                                $badgeClass = 'badge-proses';
                            } elseif ($status == 'lunas') {
                                $badgeClass = 'badge-lunas';
                            } elseif ($status == 'dibatalkan') {
                                $badgeClass = 'badge-batal';
                            }
                            ?>
                            <span class="badge <?= $badgeClass ?>">
                                <?= ucfirst($row['status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada): ?>
                        <tr class="empty-row"><td colspan="6">Belum ada booking masuk.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Konfirmasi -->
    <div id="konfirmasi-section" class="content-section">
        <h1>Konfirmasi Pembayaran</h1>
        <p class="subtitle">Validasi bukti transfer pelanggan, lalu konfirmasi atau batalkan.</p>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pelanggan</th>
                        <th>Kendaraan</th>
                        <th>Paket & Harga</th>
                        <th>Jadwal</th>
                        <th>Bukti Bayar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no2 = 1; $ada2 = false; while ($row = mysqli_fetch_assoc($q_konfirmasi)): $ada2 = true; ?>
                    <tr>
                        <td><?= $no2++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong><br>
                            <small><?= htmlspecialchars($row['no_telepon']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['plat_mobil']) ?><br>
                            <small><?= htmlspecialchars($row['jenis_mobil']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['nama_paket']) ?><br>
                            <strong>Rp <?= number_format($row['harga'], 0, ',', '.') ?></strong>
                        </td>
                        <td>
                            <?= date('d/m/Y', strtotime($row['tanggal'])) ?><br>
                            <small><?= htmlspecialchars($row['jam']) ?></small>
                        </td>
                        <td>
                            <?php if (!empty($row['bukti_bayar'])): ?>
                                <a href="<?= htmlspecialchars($row['bukti_bayar']) ?>" target="_blank" class="bukti-link">&#128247; Lihat Bukti</a>
                            <?php else: ?>
                                <span style="color:#aaa;font-size:13px;">Belum diunggah</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <form method="POST" class="aksi-form">
                                <input type="hidden" name="id_pemesanan" value="<?= $row['id_pemesanan'] ?>">
                                <button type="submit" name="aksi" value="lunas" class="btn-lunas" onclick="return confirm('Konfirmasi pembayaran ini?')">&#10003; Lunas</button>
                                <button type="submit" name="aksi" value="batal" class="btn-batal" onclick="return confirm('Batalkan pesanan ini?')">&#10007; Batal</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada2): ?>
                        <tr class="empty-row"><td colspan="7">Tidak ada pembayaran yang perlu dikonfirmasi.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recap -->
    <div id="recap-section" class="content-section">
        <h1>Recap & Income</h1>
        <p class="subtitle">Ringkasan pendapatan harian, bulanan, dan riwayat transaksi selesai.</p>

        <div class="stats-container">
            <div class="stat-card green">
                <span>Pendapatan Hari Ini</span>
                <h2>Rp <?= number_format($income_today, 0, ',', '.') ?></h2>
            </div>

            <div class="stat-card blue">
                <span>Pendapatan Bulan Ini</span>
                <h2>Rp <?= number_format($income_month, 0, ',', '.') ?></h2>
            </div>
        </div>

        <div class="card">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Pelanggan</th>
                        <th>Kendaraan</th>
                        <th>Paket</th>
                        <th>Jadwal</th>
                        <th>Harga</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no3 = 1; $ada3 = false; $total_recap = 0; while ($row = mysqli_fetch_assoc($q_recap)): $ada3 = true; $total_recap += $row['harga']; ?>
                    <tr>
                        <td><?= $no3++ ?></td>
                        <td>
                            <strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong><br>
                            <small><?= htmlspecialchars($row['no_telepon']) ?></small>
                        </td>
                        <td>
                            <?= htmlspecialchars($row['plat_mobil']) ?><br>
                            <small><?= htmlspecialchars($row['jenis_mobil']) ?> · <?= htmlspecialchars($row['warna_mobil']) ?></small>
                        </td>
                        <td><?= htmlspecialchars($row['nama_paket']) ?></td>
                        <td>
                            <?= date('d/m/Y', strtotime($row['tanggal'])) ?><br>
                            <small><?= htmlspecialchars($row['jam']) ?></small>
                        </td>
                        <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                        <td><span class="badge badge-lunas">Lunas</span></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if (!$ada3): ?>
                        <tr class="empty-row"><td colspan="7">Belum ada transaksi yang lunas.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="card card-total">
            <h4>Total Pendapatan (Semua Waktu)</h4>
            <h2>Rp <?= number_format($total_recap, 0, ',', '.') ?></h2>
        </div>
    </div>

    <!-- Pengaturan Menu -->
    <div id="pengaturan-section" class="content-section">
        <h1>Pengaturan Menu</h1>
        <p class="subtitle">Kelola paket layanan yang tampil ke pelanggan.</p>

        <div class="card">
            <h3>Tambah Paket</h3>
            <form method="POST" class="form-paket">
                <input type="hidden" name="aksi_menu" value="tambah">
                <input type="text" name="nama_paket" placeholder="Nama Paket" required>
                <input type="number" name="harga" placeholder="Harga" required>
                <textarea name="deskripsi" placeholder="Deskripsi Paket" required></textarea>
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
                    <?php $no4 = 1; while ($paket = mysqli_fetch_assoc($q_paket)): ?>
                    <tr>
                        <form method="POST">
                            <td><?= $no4++ ?></td>
                            <td><input type="text" name="nama_paket" value="<?= htmlspecialchars($paket['nama_paket']) ?>" required></td>
                            <td><input type="number" name="harga" value="<?= $paket['harga'] ?>" required></td>
                            <td><textarea name="deskripsi" required><?= htmlspecialchars($paket['deskripsi']) ?></textarea></td>
                            <td>
                                <input type="hidden" name="id_paket" value="<?= $paket['id_paket'] ?>">
                                <button type="submit" name="aksi_menu" value="edit" class="btn-lunas">Edit</button>
                                <button type="submit" name="aksi_menu" value="hapus" class="btn-batal" onclick="return confirm('Hapus paket ini?')">Hapus</button>
                            </td>
                        </form>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
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