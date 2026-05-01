<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

/* ========================= UPDATE REPORT STATUS ========================= */
if (isset($_GET['update']) && isset($_GET['status'])) {
    $id     = (int)$_GET['update'];
    $status = $conn->real_escape_string($_GET['status']);
    $conn->query("UPDATE reports SET status='$status' WHERE report_id=$id");
    header("Location: manage_reports.php");
    exit();
}

/* ========================= DELETE REPORT ========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM reports WHERE report_id=$id");
    header("Location: manage_reports.php");
    exit();
}

/* ========================= DOWNLOAD ========================= */
if (isset($_GET['download_all'])) {
    $result = $conn->query("SELECT * FROM reports ORDER BY created_at DESC");
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=all_reports.txt");
    echo "Report ID | Reported By | Target Type | Target ID | Reason | Status | Created At\n";
    echo "-------------------------------------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['report_id'] . " | " . $row['reported_by'] . " | " . $row['target_type'] . " | " .
             $row['target_id'] . " | " . str_replace("\n", " ", $row['reason']) . " | " .
             $row['status'] . " | " . $row['created_at'] . "\n";
    }
    exit();
}

/* ========================= CHART DATA ========================= */
$typeResult = $conn->query("SELECT target_type, COUNT(*) as total FROM reports GROUP BY target_type");
$typeData = [];
while ($row = $typeResult->fetch_assoc()) { $typeData[$row['target_type']] = $row['total']; }

$statusResult = $conn->query("SELECT status, COUNT(*) as total FROM reports GROUP BY status");
$statusData = [];
while ($row = $statusResult->fetch_assoc()) { $statusData[$row['status']] = $row['total']; }

$monthlyResult = $conn->query("SELECT YEAR(created_at) AS year, MONTH(created_at) AS month_num, DATE_FORMAT(MIN(created_at),'%b') AS month, COUNT(*) AS total FROM reports GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at), MONTH(created_at)");
$months = []; $monthlyTotals = [];
while ($row = $monthlyResult->fetch_assoc()) { $months[] = $row['month']; $monthlyTotals[] = $row['total']; }

/* ========================= SEARCH ========================= */
$search = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search  = $conn->real_escape_string(trim($_GET['search']));
    $reports = $conn->query("SELECT * FROM reports WHERE reported_by LIKE '%$search%' OR reason LIKE '%$search%' OR target_id LIKE '%$search%' OR target_type LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $reports = $conn->query("SELECT * FROM reports ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Manage Reports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin-style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>
<body>

<?php include "includes/sidebar.php"; ?>
<div class="main-wrapper">
    <?php include "includes/header.php"; ?>

    <div class="table-container">

        <div class="table-title">
            <h2><i class="fas fa-flag"></i> Manage Reports</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap;">
                <form method="GET" class="Search">
                    <input type="text" name="search" placeholder="Search by Account / Description / Type"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-view" type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <a class="btn-secondary" href="?download_all=1">
                    <i class="fas fa-download"></i> Download TXT
                </a>
                <button class="btn-secondary" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- CHARTS -->
        <div class="charts-row-3" style="margin-bottom:24px;">
            <div class="chart-card">
                <h3><i class="fas fa-tags"></i> Reports by Target Type</h3>
                <canvas id="typeChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-circle-half-stroke"></i> Reports by Status</h3>
                <canvas id="statusChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Monthly Report Trend</h3>
                <canvas id="monthlyChart"></canvas>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Report ID</th>
                        <th>Reported By</th>
                        <th>Target Type</th>
                        <th>Target ID</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $reports->fetch_assoc()) {
                        $user = $conn->query("SELECT name FROM accounts WHERE account_id=" . (int)$row['reported_by'])->fetch_assoc();
                    ?>
                        <tr>
                            <td><strong>#<?php echo $row['report_id']; ?></strong></td>
                            <td>
                                <?php echo $user ? htmlspecialchars($user['name']) : 'Unknown'; ?>
                                <span style="font-size:0.78rem; color:var(--text-muted);">(#<?php echo $row['reported_by']; ?>)</span>
                            </td>
                            <td>
                                <span class="badge" style="background:var(--secondary-soft); color:var(--secondary-dark);">
                                    <?php echo htmlspecialchars(ucfirst($row['target_type'])); ?>
                                </span>
                            </td>
                            <td>#<?php echo $row['target_id']; ?></td>
                            <td style="max-width:240px; color:var(--text-muted); font-size:0.83rem;"><?php echo htmlspecialchars($row['reason']); ?></td>
                            <td>
                                <?php
                                if ($row['status'] == 'Pending')
                                    echo "<span class='badge status-pending'>Pending</span>";
                                elseif ($row['status'] == 'Reviewed')
                                    echo "<span class='badge status-verified'>Reviewed</span>";
                                else
                                    echo "<span class='badge status-rejected'>Action Taken</span>";
                                ?>
                            </td>
                            <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="action-links">
                                    <a href="?update=<?php echo $row['report_id']; ?>&status=Reviewed" class="approve-link">
                                        <i class="fas fa-eye"></i> Review
                                    </a>
                                    <a href="?update=<?php echo $row['report_id']; ?>&status=Action_Taken" class="reject-link">
                                        <i class="fas fa-gavel"></i> Action
                                    </a>
                                </div>
                            </td>
                            <td>
                                <a class="btn-delete" href="?delete=<?php echo $row['report_id']; ?>"
                                   onclick="return confirm('Delete this report?');">
                                   <i class="fas fa-trash"></i> Delete
                                </a>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div><!-- /table-scroll -->
    </div><!-- /table-container -->
</div><!-- /main-wrapper -->

<script>
Chart.defaults.color = '#64748B';
Chart.defaults.font.family = "'Inter', sans-serif";
const PRIMARY = '#610C9F', SECONDARY = '#1E3A8A', ACCENT = '#54C6EB', SUCCESS = '#16A34A', WARNING = '#F59E0B', ERROR = '#DC2626';

// Pie — Reports by Target Type
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: ['Account', 'Need', 'Post', 'Fundraiser', 'Comment', 'Location'],
        datasets: [{ data: [<?= $typeData['Account'] ?? 0 ?>, <?= $typeData['need'] ?? 0 ?>, <?= $typeData['Post'] ?? 0 ?>, <?= $typeData['Fundraiser'] ?? 0 ?>, <?= $typeData['Comment'] ?? 0 ?>, <?= $typeData['location'] ?? 0 ?>],
            backgroundColor: [SECONDARY, WARNING, SUCCESS, PRIMARY, ACCENT, ERROR], borderWidth: 0, hoverOffset: 8 }]
    },
    options: { plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }, cutout: '55%' }
});

// Pie — Reports by Status
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Reviewed', 'Action Taken'],
        datasets: [{ data: [<?= $statusData['Pending'] ?? 0 ?>, <?= $statusData['Reviewed'] ?? 0 ?>, <?= $statusData['Action_Taken'] ?? 0 ?>],
            backgroundColor: [WARNING, SUCCESS, ERROR], borderWidth: 0, hoverOffset: 8 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '60%' }
});

// Line — Monthly Trend
new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{ label: 'Reports Per Month', data: <?= json_encode($monthlyTotals) ?>,
            borderColor: ERROR, backgroundColor: 'rgba(220,38,38,0.08)', fill: true, tension: 0.4, pointRadius: 4, borderWidth: 2 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});
</script>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

    doc.setFontSize(16);
    doc.setTextColor(21, 40, 94);
    doc.text('KindAid \u2014 Reports Data', 40, 40);
    doc.setFontSize(9);
    doc.setTextColor(100, 116, 139);
    doc.text('Generated: ' + new Date().toLocaleString(), 40, 58);

    // Skip: Actions (col 7), Delete (col 8)
    const skipCols = [7, 8];
    const table = document.querySelector('table');
    const headers = [], rows = [];

    table.querySelectorAll('thead tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('th').forEach((th, i) => { if (!skipCols.includes(i)) row.push(th.innerText.trim()); });
        headers.push(row);
    });
    table.querySelectorAll('tbody tr').forEach(tr => {
        const row = [];
        tr.querySelectorAll('td').forEach((td, i) => { if (!skipCols.includes(i)) row.push(td.innerText.trim()); });
        rows.push(row);
    });

    doc.autoTable({
        head: headers, body: rows, startY: 70,
        styles: { fontSize: 8, cellPadding: 4, overflow: 'linebreak' },
        headStyles: { fillColor: [220, 38, 38], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [241, 245, 249] },
        margin: { left: 40, right: 40 }
    });

    doc.save('KindAid_Reports.pdf');
}
</script>

</body>
</html>