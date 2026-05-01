<?php
// Sidebar uses PHP_SELF to highlight the active link
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <img src="/KindAid/assets/logo(4).png" alt="KindAid">
    </div>

    <nav class="sidebar-nav">

        <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-pie"></i> Dashboard
        </a>

        <div class="nav-group-title">Verifications</div>
        <a href="user_verification.php" class="nav-link <?= $currentPage === 'user_verification.php' ? 'active' : '' ?>">
            <i class="fas fa-user-check"></i> Profile Verification
        </a>
        <a href="ngo_verification.php" class="nav-link <?= $currentPage === 'ngo_verification.php' ? 'active' : '' ?>">
            <i class="fas fa-building-ngo"></i> NGO Verification
        </a>
        <a href="fundraisers_verification.php" class="nav-link <?= $currentPage === 'fundraisers_verification.php' ? 'active' : '' ?>">
            <i class="fas fa-hand-holding-heart"></i> Fundraisers Verification
        </a>
        <a href="payments_verification.php" class="nav-link <?= $currentPage === 'payments_verification.php' ? 'active' : '' ?>">
            <i class="fas fa-credit-card"></i> Payments Verification
        </a>

        <div class="nav-group-title">Data Management</div>
        <a href="fundraisers_data.php" class="nav-link <?= $currentPage === 'fundraisers_data.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar"></i> Fundraisers Data
        </a>
        <a href="account_data.php" class="nav-link <?= $currentPage === 'account_data.php' ? 'active' : '' ?>">
            <i class="fas fa-users"></i> Accounts Data
        </a>
        <a href="post_data.php" class="nav-link <?= $currentPage === 'post_data.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt"></i> Post Data
        </a>
        <a href="needs_data.php" class="nav-link <?= $currentPage === 'needs_data.php' ? 'active' : '' ?>">
            <i class="fas fa-list-check"></i> Needs Post Data
        </a>
        <a href="geo_data.php" class="nav-link <?= $currentPage === 'geo_data.php' ? 'active' : '' ?>">
            <i class="fas fa-map-marker-alt"></i> Location Data
        </a>

        <div class="nav-group-title">System</div>
        <a href="manage_reports.php" class="nav-link <?= $currentPage === 'manage_reports.php' ? 'active' : '' ?>">
            <i class="fas fa-flag"></i> Reports
        </a>
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'SuperAdmin') { ?>
        <a href="admin_data.php" class="nav-link <?= $currentPage === 'admin_data.php' ? 'active' : '' ?>">
            <i class="fas fa-user-shield"></i> Manage Admins
        </a>
        <?php } ?>

        <a href="logout.php" class="nav-link logout-link">
            <i class="fas fa-right-from-bracket"></i> Logout
        </a>
    </nav>
</div>

<script>
// Sidebar toggle for mobile
document.addEventListener('DOMContentLoaded', function () {
    var toggle   = document.getElementById('menuToggle');
    var sidebar  = document.getElementById('adminSidebar');
    var overlay  = document.getElementById('sidebarOverlay');

    if (toggle) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            overlay.classList.remove('active');
        });
    }
});
</script>
