<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

/* ========================= SUMMARY CARDS ========================= */
$totalAccounts   = $conn->query("SELECT COUNT(*) as t FROM accounts")->fetch_assoc()['t'];
$totalNGO        = $conn->query("SELECT COUNT(*) as t FROM accounts WHERE account_type='NGO'")->fetch_assoc()['t'];
$totalFundraisers= $conn->query("SELECT COUNT(*) as t FROM fundraiser")->fetch_assoc()['t'];
$totalDonations  = $conn->query("SELECT SUM(amount) as t FROM transactions WHERE payment_status='Completed'")->fetch_assoc()['t'] ?? 0;
$pendingReports  = $conn->query("SELECT COUNT(*) as t FROM reports WHERE status='Pending'")->fetch_assoc()['t'];

/* ========================= ACCOUNT TYPE PIE ========================= */
$userCount = $conn->query("SELECT COUNT(*) as t FROM accounts WHERE account_type='User'")->fetch_assoc()['t'];
$ngoCount  = $conn->query("SELECT COUNT(*) as t FROM accounts WHERE account_type='NGO'")->fetch_assoc()['t'];

/* ========================= MONTHLY REGISTRATIONS ========================= */
$userRegData = $conn->query("SELECT MONTH(created_at) as month, COUNT(*) as total FROM accounts WHERE account_type='User' GROUP BY MONTH(created_at)");
$userMonths = []; $userCounts = [];
while ($row = $userRegData->fetch_assoc()) { $userMonths[] = $row['month']; $userCounts[] = $row['total']; }

$ngoRegData = $conn->query("SELECT MONTH(created_at) as month, COUNT(*) as total FROM accounts WHERE account_type='NGO' GROUP BY MONTH(created_at)");
$ngoMonths = []; $ngoCounts = [];
while ($row = $ngoRegData->fetch_assoc()) { $ngoMonths[] = $row['month']; $ngoCounts[] = $row['total']; }

/* ========================= REPORT STATUS BAR ========================= */
$reportData = $conn->query("SELECT status, COUNT(*) as total FROM reports GROUP BY status");
$reportStatus = []; $reportCounts = [];
while ($row = $reportData->fetch_assoc()) { $reportStatus[] = $row['status']; $reportCounts[] = $row['total']; }

/* ========================= MONTHLY DONATIONS ========================= */
$donationData = $conn->query("SELECT MONTH(transaction_date) as month, SUM(amount) as total FROM transactions WHERE payment_status='Completed' GROUP BY MONTH(transaction_date)");
$donationMonths = []; $donationTotals = [];
while ($row = $donationData->fetch_assoc()) { $donationMonths[] = $row['month']; $donationTotals[] = $row['total']; }

/* ========================= FUNDRAISER SUCCESS RATE ========================= */
$successData = $conn->query("SELECT AVG((collected_amount / goal_amount) * 100) as avg_success FROM fundraiser WHERE goal_amount > 0")->fetch_assoc();
$avgSuccess = round($successData['avg_success'] ?? 0, 2);

/* ========================= PAYMENT METHOD ========================= */
$paymentData = $conn->query("SELECT payment_method, COUNT(*) as total FROM transactions WHERE payment_status='Completed' GROUP BY payment_method");
$paymentLabels = []; $paymentCounts = [];
while ($row = $paymentData->fetch_assoc()) { $paymentLabels[] = $row['payment_method']; $paymentCounts[] = $row['total']; }

/* ========================= NEEDS & GEO ========================= */
$needsData = $conn->query("SELECT goal_status, COUNT(*) as total FROM needs GROUP BY goal_status");
$needsLabels = []; $needsCounts = [];
while ($row = $needsData->fetch_assoc()) { $needsLabels[] = $row['goal_status']; $needsCounts[] = $row['total']; }

$geoData = $conn->query("SELECT goal_status, COUNT(*) as total FROM geo_locations GROUP BY goal_status");
$geoLabels = []; $geoCounts = [];
while ($row = $geoData->fetch_assoc()) { $geoLabels[] = $row['goal_status']; $geoCounts[] = $row['total']; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

<?php include "includes/sidebar.php"; ?>

<div class="main-wrapper">
    <?php include "includes/header.php"; ?>

    <div class="page-body">

        <!-- ===== PAGE HEADER ===== -->
        <div class="page-header">
            <i class="fas fa-chart-pie"></i>
            <h2>Dashboard Overview</h2>
        </div>

        <!-- ===== STAT CARDS ===== -->
        <div class="stats-grid">
            <div class="card">
                <h3><i class="fas fa-users" style="margin-right:5px;color:var(--primary);"></i>Total Accounts</h3>
                <div class="value"><?php echo number_format($totalAccounts); ?></div>
            </div>
            <div class="card">
                <h3><i class="fas fa-building-ngo" style="margin-right:5px;color:var(--secondary);"></i>Total NGOs</h3>
                <div class="value"><?php echo number_format($totalNGO); ?></div>
            </div>
            <div class="card">
                <h3><i class="fas fa-hand-holding-heart" style="margin-right:5px;color:var(--primary-light);"></i>Fundraisers</h3>
                <div class="value"><?php echo number_format($totalFundraisers); ?></div>
            </div>
            <div class="card">
                <h3><i class="fas fa-indian-rupee-sign" style="margin-right:5px;color:var(--success);"></i>Total Donations</h3>
                <div class="value">₹<?php echo number_format($totalDonations, 0); ?></div>
            </div>
            <div class="card card-alert">
                <h3><i class="fas fa-flag" style="margin-right:5px;"></i>Pending Reports</h3>
                <div class="value"><?php echo $pendingReports; ?></div>
            </div>
        </div>

        <!-- ===== CHARTS ROW 3 ===== -->
        <div class="charts-row-3">
            <div class="chart-card">
                <h3><i class="fas fa-users"></i> Account Type Distribution</h3>
                <canvas id="accountTypeChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-bullseye"></i> Avg. Fundraiser Success Rate</h3>
                <canvas id="successChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-credit-card"></i> Payment Method Distribution</h3>
                <canvas id="paymentChart"></canvas>
            </div>
        </div>

        <!-- ===== CHARTS GRID 2 ===== -->
        <div class="charts-grid">
            <div class="chart-card">
                <h3><i class="fas fa-user-plus"></i> Monthly User Registrations</h3>
                <canvas id="userRegChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-building-ngo"></i> Monthly NGO Registrations</h3>
                <canvas id="ngoRegChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-flag"></i> Report Status Overview</h3>
                <canvas id="reportStatusChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-indian-rupee-sign"></i> Monthly Donations</h3>
                <canvas id="donationChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-list-check"></i> Needs Completion Status</h3>
                <canvas id="needsChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-map-marker-alt"></i> Geo Help Status</h3>
                <canvas id="geoChart"></canvas>
            </div>
        </div>

    </div><!-- /page-body -->
</div><!-- /main-wrapper -->

<script>
// Brand palette for charts
const PRIMARY   = '#610C9F';
const PRIMARY_L = '#8C3FD4';
const SECONDARY = '#1E3A8A';
const ACCENT    = '#54C6EB';
const SUCCESS   = '#16A34A';
const WARNING   = '#F59E0B';
const ERROR     = '#DC2626';
const GRAY      = '#E2E8F0';

Chart.defaults.color       = '#64748B';
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.font.size   = 12;

// Pie — Account Type
new Chart(document.getElementById('accountTypeChart'), {
    type: 'doughnut',
    data: {
        labels: ['Users', 'NGOs'],
        datasets: [{ data: [<?= $userCount ?>, <?= $ngoCount ?>], backgroundColor: [SECONDARY, ACCENT], borderWidth: 0, hoverOffset: 8 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '65%' }
});

// Doughnut — Success Rate
new Chart(document.getElementById('successChart'), {
    type: 'doughnut',
    data: {
        labels: ['Success %', 'Remaining %'],
        datasets: [{ data: [<?= $avgSuccess ?>, <?= 100 - $avgSuccess ?>], backgroundColor: [SUCCESS, GRAY], borderWidth: 0 }]
    },
    options: { cutout: '80%', plugins: { legend: { position: 'bottom' } } }
});

// Pie — Payment Methods
new Chart(document.getElementById('paymentChart'), {
    type: 'pie',
    data: {
        labels: <?= json_encode($paymentLabels) ?>,
        datasets: [{ data: <?= json_encode($paymentCounts) ?>, backgroundColor: [PRIMARY, SECONDARY, ACCENT, SUCCESS, WARNING], borderWidth: 0, hoverOffset: 8 }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

// Line — Monthly User Registrations
new Chart(document.getElementById('userRegChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($userMonths) ?>,
        datasets: [{ label: 'Users Registered', data: <?= json_encode($userCounts) ?>, borderColor: SECONDARY, backgroundColor: 'rgba(30,58,138,0.08)', fill: true, tension: 0.4, pointRadius: 4, borderWidth: 2 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

// Line — Monthly NGO Registrations
new Chart(document.getElementById('ngoRegChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($ngoMonths) ?>,
        datasets: [{ label: 'NGOs Registered', data: <?= json_encode($ngoCounts) ?>, borderColor: ACCENT, backgroundColor: 'rgba(84,198,235,0.08)', fill: true, tension: 0.4, pointRadius: 4, borderWidth: 2 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

// Bar — Report Status
new Chart(document.getElementById('reportStatusChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($reportStatus) ?>,
        datasets: [{ label: 'Reports', data: <?= json_encode($reportCounts) ?>, backgroundColor: [WARNING, ACCENT, ERROR], borderRadius: 6, borderWidth: 0 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

// Line — Monthly Donations
new Chart(document.getElementById('donationChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($donationMonths) ?>,
        datasets: [{ label: 'Donations (₹)', data: <?= json_encode($donationTotals) ?>, borderColor: PRIMARY, backgroundColor: 'rgba(97,12,159,0.08)', fill: true, tension: 0.4, pointRadius: 4, borderWidth: 2 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

// Bar — Needs Status
new Chart(document.getElementById('needsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($needsLabels) ?>,
        datasets: [{ label: 'Needs', data: <?= json_encode($needsCounts) ?>, backgroundColor: [SUCCESS, PRIMARY_L, ERROR], borderRadius: 6, borderWidth: 0 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

// Bar — Geo Status
new Chart(document.getElementById('geoChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($geoLabels) ?>,
        datasets: [{ label: 'Geo Goals', data: <?= json_encode($geoCounts) ?>, backgroundColor: [SUCCESS, PRIMARY_L, WARNING], borderRadius: 6, borderWidth: 0 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});
</script>

</body>
</html>