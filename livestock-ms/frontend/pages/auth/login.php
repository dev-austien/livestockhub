<?php 
// You can add login logic here later
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AgriStock MS</title>
    <link rel="stylesheet" href="../css/auth.css"> 
</head>
<body>

    <div class="login-card">
        <h2>AgriStock MS</h2>
        <form action="../../../backend/auth.php" method="POST">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Login</button>
        </form>
        <p style="font-size: 12px; text-align: center; margin-top: 15px;">
            Don't have an account? <a href="register.php">Register here</a>
        </p>
    </div>

</body>
</html>