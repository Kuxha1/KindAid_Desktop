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
    $conn->query("DELETE FROM geo_locations WHERE geo_id=$id");
    header("Location: geo_data.php"); exit();
}

/* ========================= STATUS CHANGE ========================= */
if (isset($_GET['pending']))   { $id = (int)$_GET['pending'];   $conn->query("UPDATE geo_locations SET goal_status='Pending'   WHERE geo_id=$id"); header("Location: geo_data.php"); exit(); }
if (isset($_GET['ongoing']))   { $id = (int)$_GET['ongoing'];   $conn->query("UPDATE geo_locations SET goal_status='Ongoing'   WHERE geo_id=$id"); header("Location: geo_data.php"); exit(); }
if (isset($_GET['completed'])) { $id = (int)$_GET['completed']; $conn->query("UPDATE geo_locations SET goal_status='Completed' WHERE geo_id=$id"); header("Location: geo_data.php"); exit(); }

/* ========================= CHART DATA ========================= */
$typeResult = $conn->query("SELECT a.account_type, COUNT(g.geo_id) as total FROM geo_locations g JOIN accounts a ON g.account_id = a.account_id GROUP BY a.account_type");
$typeData = [];
while ($row = $typeResult->fetch_assoc()) { $typeData[$row['account_type']] = $row['total']; }

$statusResult = $conn->query("SELECT goal_status, COUNT(*) as total FROM geo_locations GROUP BY goal_status");
$statusData = [];
while ($row = $statusResult->fetch_assoc()) { $statusData[$row['goal_status']] = $row['total']; }

/* ========================= SEARCH ========================= */
$search = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = $conn->real_escape_string(trim($_GET['search']));
    $geo = $conn->query("SELECT * FROM geo_locations WHERE description LIKE '%$search%' OR account_id LIKE '%$search%' OR geo_type LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $geo = $conn->query("SELECT * FROM geo_locations ORDER BY created_at DESC");
}

/* ========================= MAP DATA ========================= */
$mapData = $conn->query("SELECT * FROM geo_locations");
$locations = [];
while ($row = $mapData->fetch_assoc()) { $locations[] = $row; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Geo Locations</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin-style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
    <style>
        #map { height: 360px; border-radius: var(--border-radius); z-index: 1; }
    </style>
</head>
<body>

<?php include "includes/sidebar.php"; ?>
<div class="main-wrapper">
    <?php include "includes/header.php"; ?>

    <div class="table-container">

        <div class="table-title">
            <h2><i class="fas fa-map-marker-alt"></i> Geo Locations</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <form method="GET" class="Search">
                    <input type="text" name="search" placeholder="Search by Account / Description / Type"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-view" type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <button class="btn-secondary" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- CHARTS + MAP -->
        <div class="charts-row-3" style="margin-bottom:24px;">
            <div class="chart-card">
                <h3><i class="fas fa-users"></i> Locations by Account Type</h3>
                <canvas id="accountTypeChart"></canvas>
            </div>
            <div class="chart-card">
                <h3><i class="fas fa-circle-half-stroke"></i> Status Distribution</h3>
                <canvas id="geoStatusChart"></canvas>
            </div>
            <div class="chart-card" style="padding:0; overflow:hidden;">
                <div id="map"></div>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Account ID</th>
                        <th>Latitude</th>
                        <th>Longitude</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Change Status</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $geo->fetch_assoc()) { ?>
                        <tr>
                            <td>#<?php echo $row['geo_id']; ?></td>
                            <td>#<?php echo $row['account_id']; ?></td>
                            <td><?php echo $row['latitude']; ?></td>
                            <td><?php echo $row['longitude']; ?></td>
                            <td>
                                <span class="badge" style="background:var(--accent-soft); color:var(--accent-dark);">
                                    <?php echo htmlspecialchars($row['geo_type']); ?>
                                </span>
                            </td>
                            <td style="max-width:200px; color:var(--text-muted); font-size:0.82rem;"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>
                                <?php
                                if ($row['goal_status'] == 'Pending')
                                    echo "<span class='badge status-pending'>Pending</span>";
                                elseif ($row['goal_status'] == 'Ongoing')
                                    echo "<span class='badge status-verified'>Ongoing</span>";
                                else
                                    echo "<span class='badge' style='background:var(--primary-soft);color:var(--primary-dark);'>Completed</span>";
                                ?>
                            </td>
                            <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="action-links" style="flex-direction:column; gap:4px;">
                                    <?php if ($row['goal_status'] != 'Pending') { ?>
                                        <a href="?pending=<?php echo $row['geo_id']; ?>" class="reject-link">Pending</a>
                                    <?php } ?>
                                    <?php if ($row['goal_status'] != 'Ongoing') { ?>
                                        <a href="?ongoing=<?php echo $row['geo_id']; ?>" class="approve-link">Ongoing</a>
                                    <?php } ?>
                                    <?php if ($row['goal_status'] != 'Completed') { ?>
                                        <a href="?completed=<?php echo $row['geo_id']; ?>"
                                           style="background:var(--primary-soft);color:var(--primary-dark);padding:4px 10px;border-radius:6px;font-size:0.82rem;font-weight:600;">Completed</a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <a class="btn-delete" href="?delete=<?php echo $row['geo_id']; ?>"
                                   onclick="return confirm('Delete this location?');">
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

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
Chart.defaults.color = '#64748B';
Chart.defaults.font.family = "'Inter', sans-serif";
const PRIMARY = '#610C9F', SECONDARY = '#1E3A8A', ACCENT = '#54C6EB', SUCCESS = '#16A34A', WARNING = '#F59E0B';

// Leaflet Map
var map = L.map('map').setView([20.5937, 78.9629], 5);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

var locations = <?php echo json_encode($locations); ?>;
locations.forEach(function(loc) {
    if (loc.latitude && loc.longitude) {
        L.marker([loc.latitude, loc.longitude]).addTo(map)
            .bindPopup('<b>#' + loc.geo_id + '</b><br>Account: #' + loc.account_id + '<br>Status: ' + loc.goal_status + '<br>' + loc.description);
    }
});

// Bar chart — Account Type
new Chart(document.getElementById('accountTypeChart'), {
    type: 'bar',
    data: {
        labels: ['User', 'NGO'],
        datasets: [{ data: [<?= $typeData['User'] ?? 0 ?>, <?= $typeData['NGO'] ?? 0 ?>],
            backgroundColor: [SECONDARY, ACCENT], borderRadius: 6, borderWidth: 0, label: 'Locations' }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } }
});

// Pie chart — Status
new Chart(document.getElementById('geoStatusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Pending', 'Ongoing', 'Completed'],
        datasets: [{ data: [<?= $statusData['Pending'] ?? 0 ?>, <?= $statusData['Ongoing'] ?? 0 ?>, <?= $statusData['Completed'] ?? 0 ?>],
            backgroundColor: [WARNING, SECONDARY, SUCCESS], borderWidth: 0, hoverOffset: 8 }]
    },
    options: { plugins: { legend: { position: 'bottom' } }, cutout: '60%' }
});
</script>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

    doc.setFontSize(16);
    doc.setTextColor(21, 40, 94);
    doc.text('KindAid \u2014 Geo Locations Data', 40, 40);
    doc.setFontSize(9);
    doc.setTextColor(100, 116, 139);
    doc.text('Generated: ' + new Date().toLocaleString(), 40, 58);

    // Skip: Change Status (col 8), Delete (col 9)
    const skipCols = [8, 9];
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
        headStyles: { fillColor: [21, 40, 94], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [241, 245, 249] },
        margin: { left: 40, right: 40 }
    });

    doc.save('KindAid_Geo_Locations.pdf');
}
</script>

</body>
</html>