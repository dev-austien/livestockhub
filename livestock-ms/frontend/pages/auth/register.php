<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - AgriStock MS</title>
    <link rel="stylesheet" href="../../css/auth.css">
</head>
<body>

    <div class="login-card">
        <h2>Create Account</h2>
        <p style="text-align: center; color: #666; font-size: 0.9rem;">Join the AgriStock community</p>
        
        <form action="../../../backend/register_process.php" method="POST">
            <div style="display: flex; gap: 10px;">
                <div style="flex: 1;">
                    <label for="first_name">First Name</label>
                    <input type="text" id="first_name" name="first_name" required>
                </div>
                <div style="flex: 1;">
                    <label for="last_name">Last Name</label>
                    <input type="text" id="last_name" name="last_name" required>
                </div>
            </div>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" required>
            
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>

            <label for="phone">Phone Number</label>
            <input type="number" id="phone" name="phone">

            <label for="role">Register as:</label>
            <select id="role" name="role" class="auth-select" style="width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px;">
                <option value="buyer">Buyer / Consumer</option>
                <option value="farmer">Livestock Farmer</option>
            </select>
            
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required>

            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            
            <button type="submit" name="register">Create Account</button>
        </form>

        <p style="font-size: 12px; text-align: center; margin-top: 15px;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>

</body>
</html>