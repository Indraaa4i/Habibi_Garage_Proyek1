<?php 
session_start();
include 'koneksi.php'; 

// AJAX: ambil slot yang sudah terpesan pada tanggal tertentu
if(isset($_GET['ajax_slot'])){
    $tgl = mysqli_real_escape_string($conn, $_GET['tgl']);
    $q = mysqli_query($conn,"SELECT jam FROM pemesanan WHERE tanggal='$tgl'");
    $rows = [];
    while($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    header('Content-Type: application/json');
    echo json_encode($rows);
    exit;
}
if (!empty($_SESSION['user_login'])) {
    $nama_awal = $_SESSION['nama_pelanggan'] ?? '';
    $telp_awal = $_SESSION['no_handphone']   ?? '';
    $plat_awal = $_SESSION['plat_mobil']     ?? '';

    // Daftar plat dari session (sudah diinisialisasi di profil.php)
    // Jika belum ada (user langsung buka form_booking), bangun dari DB
    if (!isset($_SESSION['daftar_plat'])) {
        $no_hp_esc = mysqli_real_escape_string($conn, $telp_awal);
        $q_plat = mysqli_query($conn,
            "SELECT UPPER(REPLACE(plat_mobil,' ','')) as plat
             FROM pemesanan
             WHERE no_telepon = '$no_hp_esc'
             GROUP BY UPPER(REPLACE(plat_mobil,' ',''))
             ORDER BY MAX(id_pemesanan) DESC"
        );
        $daftar = [];
        while ($rp = mysqli_fetch_assoc($q_plat)) {
            $daftar[] = $rp['plat'];
        }
        $plat_login = strtoupper(str_replace(' ', '', $plat_awal));
        if (!in_array($plat_login, $daftar)) {
            array_unshift($daftar, $plat_login);
        } else {
            $daftar = array_merge([$plat_login], array_diff($daftar, [$plat_login]));
        }
        $_SESSION['daftar_plat'] = $daftar;
    }

    $daftar_plat = $_SESSION['daftar_plat'];
    $is_login    = true;
} else {
    $nama_awal   = '';
    $telp_awal   = '';
    $plat_awal   = '';
    $daftar_plat = [];
    $is_login    = false;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['proses_booking'])) {
    
    $nama     = mysqli_real_escape_string($conn, $_POST['nama']);
    $telp     = mysqli_real_escape_string($conn, $_POST['telepon']);
    $id_paket = mysqli_real_escape_string($conn, $_POST['id_paket']); 
    $tanggal  = mysqli_real_escape_string($conn, $_POST['tanggal']);
    $jam      = mysqli_real_escape_string($conn, $_POST['jam']);

    // Plat: jika login, bisa pilih dari dropdown atau isi manual
    if ($is_login && !empty($_POST['plat_pilih']) && $_POST['plat_pilih'] !== '__manual__') {
        $plat = strtoupper(mysqli_real_escape_string($conn, $_POST['plat_pilih']));
    } else {
        $plat = strtoupper(mysqli_real_escape_string($conn, $_POST['plat']));
    }

    // Pengecekan jadwal
    $cek_jadwal = mysqli_query($conn, "SELECT id_pemesanan FROM pemesanan 
                                      WHERE tanggal = '$tanggal' AND jam = '$jam'");

    if (mysqli_num_rows($cek_jadwal) > 0) {
        echo "<script>
                alert('Maaf, jadwal pada tanggal $tanggal jam $jam sudah penuh. Silakan pilih waktu lain.');
                window.history.back();
              </script>";
        exit;
    } else {
        $sql = "INSERT INTO pemesanan (id_paket, nama_pelanggan, plat_mobil, no_telepon, tanggal, jam)
                VALUES ('$id_paket', '$nama', '$plat', '$telp', '$tanggal', '$jam')";

        if (mysqli_query($conn, $sql)) {
            $id_terakhir = mysqli_insert_id($conn);
            // Tambahkan plat baru ke session jika belum ada
            if ($is_login && !in_array(strtoupper(str_replace(' ','',$plat)), 
                array_map(fn($p) => strtoupper(str_replace(' ','',$p)), $_SESSION['daftar_plat']))) {
                $_SESSION['daftar_plat'][] = strtoupper($plat);
            }
            echo "<script>
                    alert('Jadwal tersedia! Menuju halaman pembayaran...');
                    window.location.href='pembayaran.php?id=" . $id_terakhir . "';
                  </script>";
            exit;
        } else {
            echo "<script>alert('Gagal menyimpan: " . mysqli_error($conn) . "');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Habibi Garage - Booking</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/form.css">
    <style>
        .plat-option-group { margin-bottom: 0; }

        .plat-selector {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .plat-radio-label {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 16px;
            border-radius: 50px;
            border: 2px solid #dee2e6;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: .04em;
            cursor: pointer;
            transition: .15s;
            background: #fff;
        }

        .plat-radio-label:hover {
            border-color: #00c8e0;
            background: rgba(0,200,224,.08);
        }

        .plat-radio-label input[type="radio"] { display: none; }

        .plat-radio-label.selected {
            border-color: #00c8e0;
            background: rgba(0,200,224,.15);
            color: #0f1b2d;
        }

        .plat-manual-toggle {
            font-size: 12px;
            color: #6c757d;
            cursor: pointer;
            text-decoration: underline;
            display: inline-block;
            margin-top: 4px;
        }

        .plat-manual-toggle:hover { color: #00c8e0; }

        #wrapPlatManual { display: none; margin-top: 10px; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <div class="row g-0 full-height">
        
        <div class="col-lg-5 left-section d-flex flex-column justify-content-center p-4 p-md-5">
            <div class="content-wrapper">
                <div class="header-mobile-flex d-flex align-items-center gap-3 mb-4">
                    <img src="../img/logo.png" alt="Habibi Garage" class="garage-logo">
                    <div class="cta-text-wrapper">
                        <h1 class="call-to-action mb-0">Atur Jadwalmu</h1>
                        <p class="description mb-0 d-none d-md-block">Pesan jadwal Anda dalam sekejap</p>
                    </div>
                </div>
                <p class="description d-md-none">Pesan jadwal Anda dalam sekejap</p>

                <?php if ($is_login): ?>
                <div style="margin-top:20px; padding:14px 18px; background:rgba(0,200,224,.1);
                            border-radius:12px; border:1px solid rgba(0,200,224,.3);">
                    <div style="font-size:12px; font-weight:700; color:#00c8e0; margin-bottom:6px;">
                        ✅ Profil Terdeteksi
                        
                    </div>
                    <div style="font-size:13px; font-weight:700; color:#fff;">
                        <?= htmlspecialchars($nama_awal) ?>
                    </div>
                    <div style="font-size:12px; color:rgba(255,255,255,.6); margin-top:2px;">
                        <?= htmlspecialchars($telp_awal) ?> · <?= count($daftar_plat) ?> plat terdaftar
                    </div>
                    <a href="profil.php" style="font-size:11px;color:#00c8e0;display:inline-block;margin-top:8px;">
                        Kelola Profil →
                    </a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-lg-7 right-section d-flex align-items-center p-4 p-md-5">
            <div class="form-wrapper w-100">
                <form id="bookingForm" action="form_booking.php" method="POST" class="row g-4">

                    <div class="col-md-7">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control shadow-sm"
                               value="<?= htmlspecialchars($nama_awal) ?>"
                               <?= $is_login ? 'readonly style="background:#f8f9fa;"' : '' ?>
                               required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">No. Telepon</label>
                        <input type="tel" name="telepon" class="form-control shadow-sm"
                               value="<?= htmlspecialchars($telp_awal) ?>"
                               <?= $is_login ? 'readonly style="background:#f8f9fa;"' : '' ?>
                               required>
                    </div>

                    <!-- PLAT NOMOR: pilih dari profil atau input manual -->
                    <div class="col-12">
                        <label class="form-label">Plat Nomor</label>

                        <?php if ($is_login && count($daftar_plat) > 0): ?>
                            <!-- Pilih dari daftar plat -->
                            <div class="plat-selector" id="platSelector">
                                <?php foreach ($daftar_plat as $idx => $plat_opt): ?>
                                    <label class="plat-radio-label <?= $idx === 0 ? 'selected' : '' ?>">
                                        <input type="radio" name="plat_pilih"
                                               value="<?= htmlspecialchars(strtoupper($plat_opt)) ?>"
                                               <?= $idx === 0 ? 'checked' : '' ?>
                                               onchange="handlePlatSelect(this)">
                                        <?= htmlspecialchars(strtoupper($plat_opt)) ?>
                                        <?php if ($idx === 0): ?>
                                            <span style="font-size:10px;background:#00c8e0;color:#0f1b2d;
                                                         border-radius:20px;padding:1px 7px;">Utama</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endforeach; ?>
                                <!-- Opsi plat baru -->
                                <label class="plat-radio-label" id="labelPlatBaru">
                                    <input type="radio" name="plat_pilih"
                                           value="__manual__"
                                           onchange="handlePlatSelect(this)">
                                    + Plat Baru
                                </label>
                            </div>

                            <!-- Input manual (muncul saat pilih "Plat Baru") -->
                            <div id="wrapPlatManual">
                                <input type="text" name="plat" id="platManual"
                                       class="form-control shadow-sm"
                                       placeholder="Contoh: B 5678 AB"
                                       style="text-transform:uppercase"
                                       maxlength="12">
                                <small class="text-muted">Plat ini akan otomatis ditambahkan ke profil Anda.</small>
                            </div>

                            <!-- Hidden input agar form tetap valid saat tidak pilih plat baru -->
                            <input type="hidden" name="plat" id="platHidden" value="">

                        <?php else: ?>
                            <!-- User belum login: input manual biasa -->
                            <input type="text" name="plat" class="form-control shadow-sm"
                                   style="text-transform:uppercase"
                                   value="<?= htmlspecialchars($plat_awal) ?>"
                                   placeholder="Contoh: B 1234 XY"
                                   required>
                        <?php endif; ?>
                    </div>

                    <div class="col-12">
                        <label class="form-label">Pilih Layanan</label>
                        <select name="id_paket" class="form-select shadow-sm" required>
                            <option value="" disabled selected>Pilih paket cuci...</option>
                            <?php
                            $id_pilihan  = $_GET['id_paket'] ?? '';
                            $query_paket = mysqli_query($conn, "SELECT * FROM paket_layanan");
                            while ($p = mysqli_fetch_array($query_paket)) {
                                $selected = ($p['id_paket'] == $id_pilihan) ? 'selected' : '';
                                echo "<option value='$p[id_paket]' $selected>$p[nama_paket] - Rp "
                                     . number_format($p['harga'], 0, ',', '.') . "</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <!-- Pilih Tanggal & Jam: Kalender + Slot Interaktif -->
                    <div class="col-12">
                        <label class="form-label">Tanggal & Jam</label>

                        <!-- Hidden inputs untuk form submit -->
                        <input type="hidden" name="tanggal" id="inputTanggal" required>
                        <input type="hidden" name="jam"     id="inputJam"     required>

                        <!-- Info pilihan aktif -->
                        <div id="pilihanInfo" style="display:none;background:rgba(0,200,224,.1);border:1px solid rgba(0,200,224,.35);border-radius:12px;padding:13px 16px;font-size:13px;color:#0f1b2d;margin-bottom:14px;">
                            📅 <strong id="labelTanggalUser">-</strong> &nbsp;·&nbsp; 🕐 <strong id="labelJamUser">-</strong>
                        </div>
                        <div id="pilihanWarning" style="display:none;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:12px;font-size:13px;color:#92400e;margin-bottom:14px;">
                            ⚠️ Pilih tanggal dan jam terlebih dahulu.
                        </div>

                        <!-- Header navigasi bulan -->
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;">
                            <button type="button" onclick="gantiBulanUser(-1)" style="background:#f1f5f9;color:#0f1b2d;border-radius:8px;padding:5px 14px;font-size:18px;border:none;cursor:pointer;">‹</button>
                            <span id="labelBulanTahunUser" style="font-weight:700;font-size:14px;color:#0f1b2d;"></span>
                            <button type="button" onclick="gantiBulanUser(1)"  style="background:#f1f5f9;color:#0f1b2d;border-radius:8px;padding:5px 14px;font-size:18px;border:none;cursor:pointer;">›</button>
                        </div>

                        <!-- Header hari -->
                        <div style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:4px;">
                            <?php foreach(['Min','Sen','Sel','Rab','Kam','Jum','Sab'] as $h): ?>
                            <div style="text-align:center;font-size:11px;font-weight:700;color:<?= $h==='Jum' ? '#ef5350' : '#6c757d' ?>;padding:3px 0;"><?= $h ?></div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Grid kalender -->
                        <div id="gridKalenderUser" style="display:grid;grid-template-columns:repeat(7,1fr);gap:3px;margin-bottom:14px;"></div>

                        <!-- Slot jam -->
                        <div style="border-top:1px solid #dee2e6;padding-top:12px;">
                            <div style="font-size:12px;font-weight:700;color:#0f1b2d;margin-bottom:8px;">
                                Slot Jam — <span id="labelTanggalSlotUser" style="color:#00c8e0;">pilih tanggal dulu</span>
                            </div>
                            <div id="gridSlotUser" style="display:grid;grid-template-columns:repeat(2,1fr);gap:8px;">
                                <div style="grid-column:span 2;text-align:center;color:#9ca3af;font-size:13px;padding:14px 0;">
                                    Klik tanggal untuk melihat slot jam
                                </div>
                            </div>
                        </div>

                        <!-- Legenda -->
                        <div style="display:flex;gap:14px;margin-top:12px;flex-wrap:wrap;">
                            <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:#555;">
                                <span style="width:12px;height:12px;border-radius:3px;background:#e2f4ff;border:1px solid #ffffff;display:inline-block;"></span> Tersedia
                            </div>
                            <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:#555;">
                                <span style="width:12px;height:12px;border-radius:3px;background:#0f1b2d;display:inline-block;"></span> Dipilih
                            </div>
                            <div style="display:flex;align-items:center;gap:5px;font-size:12px;color:#555;">
                                <span style="width:12px;height:12px;border-radius:3px;background:#fee2e2;border:1px solid #ff0000;display:inline-block;"></span> Penuh
                            </div>
                        </div>
                    </div>

                    <div class="col-12 mt-5">
                        <button type="submit" name="proses_booking"
                                class="btn btn-payment w-100 py-3 text-uppercase fw-bold">
                            Lanjutkan Pembayaran
                        </button>
                    </div>

                </form>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/form.js"></script>
<script>
// ============================================================
// KALENDER + SLOT JAM INTERAKTIF (form_booking user)
// ============================================================
const SEMUA_SLOT_U = [
  '08:00 - 09:00','09:00 - 10:00','10:00 - 11:00','11:00 - 12:00',
  '13:00 - 14:00','14:00 - 15:00','15:00 - 16:00'
];
const BULAN_U = ['Januari','Februari','Maret','April','Mei','Juni',
                 'Juli','Agustus','September','Oktober','November','Desember'];

let tglAktifU  = null;
let jamAktifU  = null;
let bulanViewU = new Date().getMonth();
let tahunViewU = new Date().getFullYear();

function gantiBulanUser(arah){
  bulanViewU += arah;
  if(bulanViewU < 0){ bulanViewU = 11; tahunViewU--; }
  if(bulanViewU > 11){ bulanViewU = 0; tahunViewU++; }
  renderKalenderUser();
}

function renderKalenderUser(){
  document.getElementById('labelBulanTahunUser').textContent =
    BULAN_U[bulanViewU] + ' ' + tahunViewU;

  const grid  = document.getElementById('gridKalenderUser');
  const today = new Date(); today.setHours(0,0,0,0);
  const awal  = new Date(tahunViewU, bulanViewU, 1).getDay();
  const total = new Date(tahunViewU, bulanViewU+1, 0).getDate();

  let html = '';
  for(let i=0;i<awal;i++) html += '<div></div>';

  for(let d=1;d<=total;d++){
    const tgl    = new Date(tahunViewU, bulanViewU, d);
    const dow    = tgl.getDay();
    const tglStr = tahunViewU+'-'+String(bulanViewU+1).padStart(2,'0')+'-'+String(d).padStart(2,'0');
    const isPast   = tgl < today;
    const isJumat  = dow === 5;
    const isToday  = tgl.getTime() === today.getTime();
    const isAktif  = tglStr === tglAktifU;

    let bg='#f7f8fc', color='#0f1b2d', cursor='pointer', border='1px solid transparent', opacity='1';
    if(isAktif)      { bg='#0f1b2d'; color='white'; }
    else if(isToday) { bg='#e0f2fe'; border='1px solid #00c8e0'; }
    else if(isPast||isJumat){ bg='#e5e7eb'; color='#9ca3af'; cursor='not-allowed'; opacity='.6'; }

    const disabled = (isPast||isJumat) ? 'onclick="return false;"' : `onclick="pilihTanggalUser('${tglStr}','${d} ${BULAN_U[bulanViewU]}')"`;
    html += `<div ${disabled}
      style="text-align:center;padding:7px 2px;border-radius:8px;font-size:12px;font-weight:600;
             background:${bg};color:${color};cursor:${cursor};border:${border};opacity:${opacity};
             transition:.15s;user-select:none;">
      ${d}
    </div>`;
  }
  grid.innerHTML = html;
}

function pilihTanggalUser(tglStr, labelStr){
  tglAktifU = tglStr;
  jamAktifU = null;
  document.getElementById('inputTanggal').value = tglStr;
  document.getElementById('inputJam').value = '';
  document.getElementById('labelTanggalSlotUser').textContent = labelStr;
  document.getElementById('pilihanInfo').style.display = 'none';
  document.getElementById('labelTanggalUser').textContent = labelStr;
  renderKalenderUser();
  muatSlotUser(tglStr);
}

function muatSlotUser(tglStr){
  const grid = document.getElementById('gridSlotUser');
  grid.innerHTML = '<div style="grid-column:span 2;text-align:center;color:#9ca3af;font-size:13px;padding:14px 0;">Memuat slot...</div>';

  fetch('form_booking.php?ajax_slot=1&tgl=' + tglStr)
    .then(r => r.json())
    .then(data => {
      const terpesan = data.map(b => b.jam);
      grid.innerHTML = SEMUA_SLOT_U.map(slot => {
        const penuh   = terpesan.includes(slot);
        const dipilih = slot === jamAktifU;
        let bg     = penuh ? '#fee2e2' : (dipilih ? '#0f1b2d' : '#e2f4ff');
        let color  = penuh ? '#ef4444' : (dipilih ? 'white'   : '#0f1b2d');
        let border = penuh ? '1px solid #ef4444' : (dipilih ? 'none' : '1px solid #00c8e0');
        let cursor = penuh ? 'not-allowed' : 'pointer';
        let strike = penuh ? 'text-decoration:line-through;' : '';
        const cb = penuh ? '' : `onclick="pilihJamUser('${slot}')"`;
        return `<div ${cb}
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

function pilihJamUser(slot){
  jamAktifU = slot;
  document.getElementById('inputJam').value = slot;
  const info = document.getElementById('pilihanInfo');
  document.getElementById('labelTanggalUser').textContent =
    document.getElementById('labelTanggalSlotUser').textContent;
  document.getElementById('labelJamUser').textContent = slot;
  info.style.display = 'block';
  document.getElementById('pilihanWarning').style.display = 'none';
  muatSlotUser(tglAktifU);
}

// Override submit untuk validasi tanggal & jam
document.getElementById('bookingForm').addEventListener('submit', function(e){
  if(!tglAktifU || !jamAktifU){
    e.preventDefault();
    document.getElementById('pilihanWarning').style.display = 'block';
    document.getElementById('gridKalenderUser').scrollIntoView({behavior:'smooth',block:'center'});
  }
});

// Init kalender
renderKalenderUser();
</script>
<script>
// Highlight plat yang dipilih
document.querySelectorAll('.plat-radio-label input[type="radio"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        document.querySelectorAll('.plat-radio-label').forEach(function(l) {
            l.classList.remove('selected');
        });
        this.closest('.plat-radio-label').classList.add('selected');
    });
});

function handlePlatSelect(radio) {
    var wrapManual = document.getElementById('wrapPlatManual');
    var platManual = document.getElementById('platManual');

    if (radio.value === '__manual__') {
        wrapManual.style.display = 'block';
        platManual.required = true;
    } else {
        wrapManual.style.display = 'none';
        platManual.required = false;
        platManual.value = '';
    }
}
</script>
</body>
</html>