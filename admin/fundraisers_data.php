<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

/* ========================= DELETE ========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM fundraiser WHERE fundraiser_id=$id");
    header("Location: fundraisers_data.php");
    exit();
}

/* ========================= CHART DATA ========================= */
$statusResult = $conn->query("SELECT verified_status, COUNT(*) as total FROM fundraiser GROUP BY verified_status");
$statusData = [];
while ($row = $statusResult->fetch_assoc()) { $statusData[$row['verified_status']] = $row['total']; }

$monthlyResult = $conn->query("SELECT YEAR(created_at) AS year, MONTH(created_at) AS month_num, DATE_FORMAT(MIN(created_at),'%b') AS month, SUM(collected_amount) AS total FROM fundraiser GROUP BY YEAR(created_at), MONTH(created_at) ORDER BY YEAR(created_at), MONTH(created_at)");
$months = []; $monthlyTotals = [];
while ($row = $monthlyResult->fetch_assoc()) { $months[] = $row['month']; $monthlyTotals[] = $row['total']; }

$topResult = $conn->query("SELECT title, collected_amount FROM fundraiser ORDER BY collected_amount DESC LIMIT 5");
$topTitles = []; $topAmounts = [];
while ($row = $topResult->fetch_assoc()) { $topTitles[] = $row['title']; $topAmounts[] = $row['collected_amount']; }

/* ========================= SEARCH ========================= */
$search = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search      = $conn->real_escape_string(trim($_GET['search']));
    $fundraisers = $conn->query("SELECT * FROM fundraiser WHERE title LIKE '%$search%' OR account_id LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $fundraisers = $conn->query("SELECT * FROM fundraiser ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Fundraisers Data</title>
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
            <h2><i class="fas fa-chart-bar"></i> Fundraisers Data</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <form method="GET" class="Search">
                    <input type="text" name="search" placeholder="Search by Title or Account ID"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-view" type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <button class="btn-secondary" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- CHARTS -->
        <div class="charts-row-3">
            <div class="chart-card">
                <h3><i class="fas fa-circle-half-stroke"></i> Verification Status</h3>
                <canvas id="statusChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-chart-line"></i> Monthly Collection</h3>
                <canvas id="monthlyChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-trophy"></i> Top 5 Fundraisers</h3>
                <canvas id="topChart"></canvas>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Account</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Goal / Collected</th>
                        <th>Progress</th>
                        <th>Images</th>
                        <th>Documents</th>
                        <th>Status</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $fundraisers->fetch_assoc()) {
                        $goal      = $row['goal_amount'];
                        $collected = $row['collected_amount'];
                        $pct       = $goal > 0 ? min(($collected / $goal) * 100, 100) : 0;
                    ?>
                        <tr>
                            <td>#<?php echo $row['fundraiser_id']; ?></td>
                            <td>#<?php echo $row['account_id']; ?></td>
                            <td style="max-width:130px;"><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td style="max-width:180px; color:var(--text-muted); font-size:0.82rem;"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <div style="font-size:0.82rem;">
                                    <strong>₹<?php echo number_format($goal); ?></strong><br>
                                    <span style="color:var(--success);">₹<?php echo number_format($collected); ?></span>
                                </div>
                            </td>
                            <td style="min-width:100px;">
                                <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo round($pct, 1); ?>%</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:<?php echo $pct; ?>%"></div>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <?php if ($row['image_1']) { ?>
                                    <img src="../uploads/<?php echo $row['image_1']; ?>" class="thumb-img" onclick="openModal(this.src)">
                                <?php } ?>
                                <?php if ($row['image_2']) { ?>
                                    <img src="../uploads/<?php echo $row['image_2']; ?>" class="thumb-img" onclick="openModal(this.src)">
                                <?php } ?>
                                <?php if (!$row['image_1'] && !$row['image_2']) { echo '<span style="color:var(--text-muted);font-size:0.8rem;">None</span>'; } ?>
                            </td>
                            <td>
                                <div class="pdf-links">
                                    <?php if ($row['document_1']) { ?>
                                        <a class="btn-view" href="../uploads/doc/<?php echo $row['document_1']; ?>" target="_blank">
                                            <i class="fas fa-file-pdf"></i> PDF 1
                                        </a>
                                    <?php } ?>
                                    <?php if ($row['document_2']) { ?>
                                        <a class="btn-view" href="../uploads/doc/<?php echo $row['document_2']; ?>" target="_blank">
                                            <i class="fas fa-file-pdf"></i> PDF 2
                                        </a>
                                    <?php } ?>
                                    <?php if (!$row['document_1'] && !$row['document_2']) { echo '<span style="color:var(--text-muted);font-size:0.8rem;">No Docs</span>'; } ?>
                                </div>
                            </td>
                            <td>
                                <?php
                                if ($row['verified_status'] == 'Verified')
                                    echo "<span class='badge status-verified'>Verified</span>";
                                elseif ($row['verified_status'] == 'Pending')
                                    echo "<span class='badge status-pending'>Pending</span>";
                                else
                                    echo "<span class='badge status-rejected'>Rejected</span>";
                                ?>
                            </td>
                            <td>
                                <a class="btn-delete" href="?delete=<?php echo $row['fundraiser_id']; ?>"
                                   onclick="return confirm('Delete this fundraiser?');">
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

<!-- IMAGE MODAL -->
<div id="imageModal" class="modal" onclick="closeModal()">
    <div class="modal-inner">
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <img id="modalImage" src="" alt="Fundraiser image">
    </div>
</div>

<script>
function openModal(src) { document.getElementById('modalImage').src = src; document.getElementById('imageModal').classList.add('open'); }
function closeModal() { document.getElementById('imageModal').classList.remove('open'); }

const PRIMARY = '#610C9F', SECONDARY = '#1E3A8A', ACCENT = '#54C6EB',
      SUCCESS = '#16A34A', WARNING = '#F59E0B', ERROR = '#DC2626';

Chart.defaults.color = '#64748B';
Chart.defaults.font.family = "'Inter', sans-serif";

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Verified', 'Rejected'],
        datasets: [{ data: [<?= $statusData['Pending'] ?? 0 ?>, <?= $statusData['Verified'] ?? 0 ?>, <?= $statusData['Rejected'] ?? 0 ?>], backgroundColor: [WARNING, SUCCESS, ERROR], borderWidth: 0, hoverOffset: 8 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '60%' }
});

new Chart(document.getElementById('monthlyChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode($months) ?>,
        datasets: [{ label: 'Monthly Collection (₹)', data: <?= json_encode($monthlyTotals) ?>, borderColor: PRIMARY, backgroundColor: 'rgba(97,12,159,0.08)', fill: true, tension: 0.4, pointRadius: 4, borderWidth: 2 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('topChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($topTitles) ?>,
        datasets: [{ label: 'Top 5 (₹)', data: <?= json_encode($topAmounts) ?>, backgroundColor: PRIMARY, borderRadius: 6, borderWidth: 0 }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: '#F1F5F9' } }, y: { grid: { display: false } } } }
});
</script>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

    doc.setFontSize(16);
    doc.setTextColor(21, 40, 94);
    doc.text('KindAid — Fundraisers Data', 40, 40);
    doc.setFontSize(9);
    doc.setTextColor(100, 116, 139);
    doc.text('Generated: ' + new Date().toLocaleString(), 40, 58);

    // Skip: Images (col 6), Documents (col 7), Delete (col 9)
    const skipCols = [6, 7, 9];
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
        headStyles: { fillColor: [97, 12, 159], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [241, 245, 249] },
        margin: { left: 40, right: 40 }
    });

    doc.save('KindAid_Fundraisers_Data.pdf');
}
</script>

</body>
</html>