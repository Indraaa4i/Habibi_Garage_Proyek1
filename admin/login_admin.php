<?php
include '../user/koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM login_admin WHERE email='$email' AND password='$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        
        echo "Login berhasil!";
       
    } else {
        
        echo "Email atau password salah!";
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
    <h2>Login</h2>
    
   <form method="POST" action="login_admin.php">
    
    <div class="input-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="Masukkan email" required>
    </div>

    <div class="input-group">
        <label>Password</label>
        <input type="password" name="password" placeholder="Masukkan password" required>
    </div>

    <button type="submit">Login</button>

</form>
</div>


<script src="../js/form_login_admin.js"></script>

</body>
</html>