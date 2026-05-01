<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

include __DIR__ . "/../config/db.php";

$admin_id  = $_SESSION['admin_id'];
$result    = $conn->query("SELECT name, role FROM admins WHERE admin_id = $admin_id");
$adminData = $result->fetch_assoc();
?>

<div class="topbar">
    <div class="topbar-left">
        <!-- Mobile hamburger — toggled by sidebar.php script -->
        <button id="menuToggle" class="menu-toggle" aria-label="Toggle menu">
            <i class="fas fa-bars"></i>
        </button>
        <span class="topbar-title">KindAid Admin</span>
    </div>

    <div class="admin-info">
        <span class="admin-name">
            <i class="fas fa-circle-user" style="margin-right:4px; color:var(--accent-light);"></i>
            <?php echo htmlspecialchars($adminData['name']); ?>
        </span>
        <span class="role-badge"><?php echo htmlspecialchars($adminData['role']); ?></span>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </div>
</div>