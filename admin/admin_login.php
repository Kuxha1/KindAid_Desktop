<?php
session_start();
include "config/db.php";

if (isset($_POST['login'])) {

    $email    = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $sql    = "SELECT * FROM admins WHERE email='$email'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {

        $admin      = $result->fetch_assoc();
        $dbPassword = $admin['password'];

        // 1️⃣ Hashed password
        if (password_verify($password, $dbPassword)) {

            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['email']    = $admin['email'];
            $_SESSION['role']     = $admin['role'];
            header("Location: dashboard.php");
            exit();

        // 2️⃣ Plain-text legacy — auto-upgrade
        } elseif ($password === $dbPassword) {

            $newHash = password_hash($password, PASSWORD_DEFAULT);
            $conn->query("UPDATE admins SET password='$newHash' WHERE admin_id=" . $admin['admin_id']);

            $_SESSION['admin_id'] = $admin['admin_id'];
            $_SESSION['email']    = $admin['email'];
            $_SESSION['role']     = $admin['role'];
            header("Location: dashboard.php");
            exit();

        } else {
            $error = "Invalid password. Please try again.";
        }

    } else {
        $error = "No account found with that email.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid — Admin Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin-style.css">
</head>
<body class="login-page-body">

    <div class="login-card">
        <div class="logo-wrap">
            <img src="/KindAid/assets/logo(1).png" alt="KindAid Logo">
        </div>
        <h1>Admin Login</h1>
        <p class="subtitle">Sign in to access your control panel</p>

        <?php if (!empty($error)) { ?>
            <div style="background:var(--error-soft); color:var(--error-dark); padding:10px 14px;
                        border-radius:8px; font-size:0.85rem; margin-bottom:16px; text-align:left;">
                <i class="fas fa-circle-exclamation" style="margin-right:6px;"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" placeholder="Admin Email" required
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Password" required>
            </div>
            <button type="submit" name="login" class="main-btn">
                <i class="fas fa-right-to-bracket" style="margin-right:6px;"></i>Sign In
            </button>
        </form>

        <a href="/KindAid/login.php" class="forget-pass">
            <i class="fas fa-arrow-left"></i> Back to User Login
        </a>
    </div>

</body>
</html>
