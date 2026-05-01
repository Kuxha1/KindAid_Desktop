<?php
session_start();
include "admin/config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $query = "SELECT * FROM accounts WHERE email = '$email' LIMIT 1";
    $result = mysqli_query($conn, $query);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        $isValidPassword = false;

        // ✅ Case 1: Password already hashed
        if (password_verify($password, $user['password'])) {
            $isValidPassword = true;
        }

        // ✅ Case 2: Password stored as plain text
        elseif ($password === $user['password']) {
            $isValidPassword = true;

            // 🔒 Auto-convert to hashed password
            $newHash = password_hash($password, PASSWORD_DEFAULT);
            mysqli_query($conn, "UPDATE accounts 
                                 SET password='$newHash' 
                                 WHERE account_id=" . $user['account_id']);
        }

        if ($isValidPassword) {

                // Store session
                $_SESSION['account_id'] = $user['account_id'];
                $_SESSION['account_type'] = $user['account_type'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];

                header("Location: index.php");
                exit();

        } else {
            $error = "Invalid Password!";
        }

    } else {
        $error = "Email not found!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kind Aid Login</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style/main-style.css">
     <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
        }
    </style>
</head>
<body>

<div class="login-card">

    <img src="assets/logo(1).png"><br><br>

    <h1>Login</h1>

    <!-- Error Message -->
    <?php if($error != ""): ?>
        <p style="color:red; margin-bottom:15px;">
            <?php echo $error; ?>
        </p>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <input type="email" name="email" placeholder="Enter Email" required>
        </div>

        <div class="form-group">
            <input type="password" name="password" placeholder="Enter Password" required>
        </div>

        <button type="submit" class="btn">Login</button>

        <button type="button" class="btn"
            onclick="window.location.href='register.php'">
            Register
        </button>

    </form>

    <a href="forgot_password.php" class="forget-pass">FORGET PASSWORD?</a>
    <a href="admin/admin_login.php" class="forget-pass">ADMIN LOGIN →</a>

</div>

</body>
</html>