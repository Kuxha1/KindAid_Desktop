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
    $conn->query("DELETE FROM needs WHERE need_id=$id");
    header("Location: needs_data.php"); exit();
}

/* ========================= CHANGE STATUS ========================= */
if (isset($_GET['status']) && isset($_GET['id'])) {
    $id     = (int)$_GET['id'];
    $status = $conn->real_escape_string($_GET['status']);
    $conn->query("UPDATE needs SET goal_status='$status' WHERE need_id=$id");
    header("Location: needs_data.php"); exit();
}

/* ========================= STATUS COUNTS ========================= */
$statusSummary = $conn->query("SELECT goal_status, COUNT(*) as total FROM needs GROUP BY goal_status");
$statusCounts  = ['Pending' => 0, 'Ongoing' => 0, 'Fulfilled' => 0];
$totalNeeds    = 0;
while ($row = $statusSummary->fetch_assoc()) {
    if (array_key_exists($row['goal_status'], $statusCounts))
        $statusCounts[$row['goal_status']] = $row['total'];
    $totalNeeds += $row['total'];
}
$pendingPct   = $totalNeeds ? round(($statusCounts['Pending']   / $totalNeeds) * 100, 1) : 0;
$ongoingPct   = $totalNeeds ? round(($statusCounts['Ongoing']   / $totalNeeds) * 100, 1) : 0;
$fulfilledPct = $totalNeeds ? round(($statusCounts['Fulfilled'] / $totalNeeds) * 100, 1) : 0;

/* ========================= SEARCH ========================= */
$search = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = $conn->real_escape_string(trim($_GET['search']));
    $needs  = $conn->query("SELECT * FROM needs WHERE goal_title LIKE '%$search%' OR description LIKE '%$search%' OR account_id LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $needs = $conn->query("SELECT * FROM needs ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Needs Data</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin-style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.2/jspdf.plugin.autotable.min.js"></script>
</head>
<body>

<?php include "includes/sidebar.php"; ?>
<div class="main-wrapper">
    <?php include "includes/header.php"; ?>

    <div class="table-container">

        <div class="table-title">
            <h2><i class="fas fa-list-check"></i> Needs Data</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <form method="GET" class="Search">
                    <input type="text" name="search" placeholder="Search by Title / Description / Account ID"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-view" type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <button class="btn-secondary" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- STATUS OVERVIEW -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:24px;">
            <div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:4px; font-weight:600;">
                    <i class="fas fa-clock" style="color:var(--warning);"></i> Pending (<?php echo $statusCounts['Pending']; ?>) — <?php echo $pendingPct; ?>%
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $pendingPct; ?>%; background:var(--warning);"></div></div>
            </div>
            <div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:4px; font-weight:600;">
                    <i class="fas fa-spinner" style="color:var(--secondary);"></i> Ongoing (<?php echo $statusCounts['Ongoing']; ?>) — <?php echo $ongoingPct; ?>%
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $ongoingPct; ?>%; background:var(--secondary);"></div></div>
            </div>
            <div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:4px; font-weight:600;">
                    <i class="fas fa-circle-check" style="color:var(--success);"></i> Fulfilled (<?php echo $statusCounts['Fulfilled']; ?>) — <?php echo $fulfilledPct; ?>%
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $fulfilledPct; ?>%"></div></div>
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
                        <th>Progress</th>
                        <th>Images</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Change Status</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $needs->fetch_assoc()) {
                        $images = [];
                        if (!empty($row['image_1'])) $images[] = "../uploads/" . $row['image_1'];
                        if (!empty($row['image_2'])) $images[] = "../uploads/" . $row['image_2'];
                    ?>
                        <tr>
                            <td>#<?php echo $row['need_id']; ?></td>
                            <td>#<?php echo $row['account_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($row['goal_title']); ?></strong></td>
                            <td style="max-width:220px; color:var(--text-muted); font-size:0.82rem;"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td style="min-width:90px;">
                                <span style="font-size:0.8rem; color:var(--text-muted);"><?php echo $row['progress_percent']; ?>%</span>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width:<?php echo $row['progress_percent']; ?>%"></div>
                                </div>
                            </td>
                            <td style="text-align:center;">
                                <?php foreach ($images as $img) { ?>
                                    <img src="<?php echo $img; ?>" class="thumb-img"
                                         onclick="openModal(<?php echo htmlspecialchars(json_encode($images)); ?>, '<?php echo $img; ?>')">
                                <?php } ?>
                                <?php if (empty($images)) echo '<span style="color:var(--text-muted);font-size:0.8rem;">None</span>'; ?>
                            </td>
                            <td>
                                <?php
                                if ($row['goal_status'] == 'Pending')
                                    echo "<span class='badge status-pending'>Pending</span>";
                                elseif ($row['goal_status'] == 'Ongoing')
                                    echo "<span class='badge status-verified'>Ongoing</span>";
                                else
                                    echo "<span class='badge status-rejected'>Fulfilled</span>";
                                ?>
                            </td>
                            <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="action-links" style="flex-direction:column; gap:4px;">
                                    <?php if ($row['goal_status'] != 'Pending') { ?>
                                        <a href="?status=Pending&id=<?php echo $row['need_id']; ?>" class="reject-link">Pending</a>
                                    <?php } ?>
                                    <?php if ($row['goal_status'] != 'Ongoing') { ?>
                                        <a href="?status=Ongoing&id=<?php echo $row['need_id']; ?>" class="approve-link">Ongoing</a>
                                    <?php } ?>
                                    <?php if ($row['goal_status'] != 'Fulfilled') { ?>
                                        <a href="?status=Fulfilled&id=<?php echo $row['need_id']; ?>"
                                           style="background:var(--primary-soft);color:var(--primary-dark);padding:4px 10px;border-radius:6px;font-size:0.82rem;font-weight:600;">Fulfilled</a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <a class="btn-delete" href="?delete=<?php echo $row['need_id']; ?>"
                                   onclick="return confirm('Delete this need?');">
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

<div id="imageModal" class="modal">
    <div class="modal-inner">
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <img id="modalImage" src="" alt="Need image">
        <div style="display:flex; justify-content:center; gap:16px; margin-top:12px;">
            <button onclick="prevImage()" style="background:rgba(255,255,255,0.15);border:none;color:white;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:1rem;">&#10094;</button>
            <button onclick="nextImage()" style="background:rgba(255,255,255,0.15);border:none;color:white;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:1rem;">&#10095;</button>
        </div>
    </div>
</div>

<script>
let imagesArray = [], currentIndex = 0;
function openModal(images, img) { imagesArray = images; currentIndex = imagesArray.indexOf(img); document.getElementById('modalImage').src = img; document.getElementById('imageModal').classList.add('open'); }
function closeModal() { document.getElementById('imageModal').classList.remove('open'); }
function nextImage() { currentIndex = (currentIndex + 1) % imagesArray.length; document.getElementById('modalImage').src = imagesArray[currentIndex]; }
function prevImage() { currentIndex = (currentIndex - 1 + imagesArray.length) % imagesArray.length; document.getElementById('modalImage').src = imagesArray[currentIndex]; }
document.getElementById('imageModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
</script>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

    doc.setFontSize(16);
    doc.setTextColor(21, 40, 94);
    doc.text('KindAid \u2014 Needs Data', 40, 40);
    doc.setFontSize(9);
    doc.setTextColor(100, 116, 139);
    doc.text('Generated: ' + new Date().toLocaleString(), 40, 58);

    // Skip: Images (col 5), Change Status (col 8), Delete (col 9)
    const skipCols = [5, 8, 9];
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

    doc.save('KindAid_Needs_Data.pdf');
}
</script>

</body>
</html>