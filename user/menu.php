<?php 
include 'koneksi.php'; 
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Habibi Garage - Katalog Layanan</title>
  <link rel="stylesheet" href="../css/menu.css" /> <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet" />
</head>
<body>

  <header class="header">
    <div class="header-inner">
      <div class="logo-wrap">
        <img src="../img/logo.png" alt="Logo" class="logo" />
      </div>
      <ul class="nav-links">
        <li><a href="#">Keep Enjoy And Take ur Time</a></li>
      </ul>
    </div>
  </header>

  <div class="page-body">
    <div class="toolbar">
      <h2 class="section-title">Pilih Layanan</h2>
    </div>

    <div class="product-grid">
      <?php
      
      $query = mysqli_query($conn, "SELECT * FROM paket_layanan");
      
      while($row = mysqli_fetch_array($query)) {
      ?>
        <div class="card">
          <div class="card-img-wrap">
            <img src="../img/mobil.png" alt="Mobil" class="card-img" />
          </div>
          <div class="card-body">
            <h3 class="card-title"><?php echo $row['nama_paket']; ?></h3>
            <p class="card-price">Rp <?php echo number_format($row['harga'], 0, ',', '.'); ?></p>
            
            <button class="add-btn" onclick="window.location.href='form_booking.php?id_paket=<?php echo $row['id_paket']; ?>'">
              Booking Sekarang
            </button>
          </div>
        </div>
      <?php } ?>
    </div>
  </div>

</body>
</html>