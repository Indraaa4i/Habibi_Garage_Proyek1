<?php
session_start(); // Memulai session untuk menjaga status login
include '../user/koneksi.php';

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Mencari data admin berdasarkan email dan password
    $query = mysqli_query($conn, "SELECT * FROM login_admin WHERE email='$email' AND password='$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {
        // Jika data ditemukan
        $_SESSION['status'] = "login";
        header("location:dashboard_admin.php"); // Ganti dengan halaman tujuan setelah login
    } else {
        // Jika data tidak ditemukan, balikkan ke login_admin.php dengan parameter pesan=gagal
        header("location:login_admin.php?pesan=gagal");
    }
}
?>


<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

   
    <link rel="stylesheet" href="../css/form_login admin.css">
</head>
<body>

<div class="container">
    <h2>Login Admin</h2>
    
    <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'gagal'): ?>
        <p style="color: red; text-align: center;">Email atau Password Salah!</p>
    <?php endif; ?>

    <form method="POST" action="login_admin.php">
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" placeholder="Masukkan email" required>
        </div>

        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" placeholder="Masukkan password" required>
        </div>

        <button type="submit" name="submit">Login</button>
    </form>
</div>

</body>
</html>