<?php
session_start(); 
include '../user/koneksi.php';

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

   
    $query = mysqli_query($conn, "SELECT * FROM login_admin WHERE email='$email' AND password='$password'");
    $cek = mysqli_num_rows($query);

    if ($cek > 0) {
        
        $_SESSION['status'] = "login";
        header("location:dashboard_admin.php");
    } else {
       
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
    <link rel="stylesheet" href="../css/login_admin.css">
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