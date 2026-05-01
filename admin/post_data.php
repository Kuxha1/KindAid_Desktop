<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

/* ========================= ACTIONS ========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM community_forum WHERE post_id=$id");
    header("Location: post_data.php"); exit();
}
if (isset($_GET['active'])) {
    $id = (int)$_GET['active'];
    $conn->query("UPDATE community_forum SET status='Active' WHERE post_id=$id");
    header("Location: post_data.php"); exit();
}
if (isset($_GET['remove'])) {
    $id = (int)$_GET['remove'];
    $conn->query("UPDATE community_forum SET status='Removed' WHERE post_id=$id");
    header("Location: post_data.php"); exit();
}

/* ========================= STATUS SUMMARY ========================= */
$totalPosts    = $conn->query("SELECT COUNT(*) as t FROM community_forum")->fetch_assoc()['t'];
$activePosts   = $conn->query("SELECT COUNT(*) as t FROM community_forum WHERE status='Active'")->fetch_assoc()['t'];
$reportedPosts = $conn->query("SELECT COUNT(*) as t FROM community_forum WHERE status='Reported'")->fetch_assoc()['t'];
$removedPosts  = $conn->query("SELECT COUNT(*) as t FROM community_forum WHERE status='Removed'")->fetch_assoc()['t'];
$activePercent   = $totalPosts ? round(($activePosts / $totalPosts) * 100, 1) : 0;
$reportedPercent = $totalPosts ? round(($reportedPosts / $totalPosts) * 100, 1) : 0;
$removedPercent  = $totalPosts ? round(($removedPosts / $totalPosts) * 100, 1) : 0;

/* ========================= SEARCH ========================= */
$search = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search = $conn->real_escape_string(trim($_GET['search']));
    $posts  = $conn->query("SELECT * FROM community_forum WHERE description LIKE '%$search%' OR account_id LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $posts = $conn->query("SELECT * FROM community_forum ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Post Data</title>
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
            <h2><i class="fas fa-file-alt"></i> Posts Data</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <form method="GET" class="Search">
                    <input type="text" name="search" placeholder="Search by Description or Account ID"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-view" type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <button class="btn-secondary" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- POST STATUS SUMMARY -->
        <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:24px;">
            <div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:4px; font-weight:600;">
                    <i class="fas fa-circle-check" style="color:var(--success);"></i> Active — <?php echo $activePercent; ?>%
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $activePercent; ?>%"></div></div>
            </div>
            <div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:4px; font-weight:600;">
                    <i class="fas fa-flag" style="color:var(--warning);"></i> Reported — <?php echo $reportedPercent; ?>%
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $reportedPercent; ?>%; background:var(--warning);"></div></div>
            </div>
            <div>
                <div style="font-size:0.8rem; color:var(--text-muted); margin-bottom:4px; font-weight:600;">
                    <i class="fas fa-ban" style="color:var(--error);"></i> Removed — <?php echo $removedPercent; ?>%
                </div>
                <div class="progress-bar"><div class="progress-fill" style="width:<?php echo $removedPercent; ?>%; background:var(--error);"></div></div>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Likes</th>
                        <th>Account ID</th>
                        <th>Description</th>
                        <th>Images</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $posts->fetch_assoc()) {
                        $images = [];
                        if (!empty($row['image_1'])) $images[] = "../uploads/" . $row['image_1'];
                        if (!empty($row['image_2'])) $images[] = "../uploads/" . $row['image_2'];
                    ?>
                        <tr>
                            <td>#<?php echo $row['post_id']; ?></td>
                            <td><i class="fas fa-heart" style="color:var(--error);font-size:0.8rem;"></i> <?php echo $row['likes']; ?></td>
                            <td>#<?php echo $row['account_id']; ?></td>
                            <td style="max-width:260px; color:var(--text-muted); font-size:0.85rem;"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td style="text-align:center;">
                                <?php foreach ($images as $img) { ?>
                                    <img src="<?php echo $img; ?>" class="thumb-img"
                                         onclick="openModal(<?php echo htmlspecialchars(json_encode($images)); ?>, '<?php echo $img; ?>')">
                                <?php } ?>
                                <?php if (empty($images)) { echo '<span style="color:var(--text-muted);font-size:0.8rem;">None</span>'; } ?>
                            </td>
                            <td>
                                <?php
                                if ($row['status'] == 'Active')
                                    echo "<span class='badge status-verified'>Active</span>";
                                elseif ($row['status'] == 'Reported')
                                    echo "<span class='badge status-pending'>Reported</span>";
                                else
                                    echo "<span class='badge status-rejected'>Removed</span>";
                                ?>
                            </td>
                            <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                            <td>
                                <div class="action-links">
                                    <?php if ($row['status'] != 'Active') { ?>
                                        <a href="?active=<?php echo $row['post_id']; ?>" class="approve-link">
                                            <i class="fas fa-check"></i> Activate
                                        </a>
                                    <?php } ?>
                                    <?php if ($row['status'] != 'Removed') { ?>
                                        <a href="?remove=<?php echo $row['post_id']; ?>" class="reject-link"
                                           onclick="return confirm('Remove this post?');">
                                            <i class="fas fa-ban"></i> Remove
                                        </a>
                                    <?php } ?>
                                </div>
                            </td>
                            <td>
                                <a class="btn-delete" href="?delete=<?php echo $row['post_id']; ?>"
                                   onclick="return confirm('Permanently delete this post?');">
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

<!-- IMAGE MODAL with slideshow -->
<div id="imageModal" class="modal">
    <div class="modal-inner">
        <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
        <img id="modalImage" src="" alt="Post image">
        <div style="display:flex; justify-content:center; gap:16px; margin-top:12px;">
            <button onclick="prevImage()" style="background:rgba(255,255,255,0.15);border:none;color:white;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:1rem;">&#10094;</button>
            <button onclick="nextImage()" style="background:rgba(255,255,255,0.15);border:none;color:white;padding:8px 16px;border-radius:8px;cursor:pointer;font-size:1rem;">&#10095;</button>
        </div>
    </div>
</div>

<script>
let imagesArray = [], currentIndex = 0;

function openModal(images, clickedImage) {
    imagesArray = images;
    currentIndex = imagesArray.indexOf(clickedImage);
    document.getElementById('modalImage').src = clickedImage;
    document.getElementById('imageModal').classList.add('open');
}
function closeModal() {
    document.getElementById('imageModal').classList.remove('open');
}
function nextImage() {
    currentIndex = (currentIndex + 1) % imagesArray.length;
    document.getElementById('modalImage').src = imagesArray[currentIndex];
}
function prevImage() {
    currentIndex = (currentIndex - 1 + imagesArray.length) % imagesArray.length;
    document.getElementById('modalImage').src = imagesArray[currentIndex];
}
document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

    doc.setFontSize(16);
    doc.setTextColor(21, 40, 94);
    doc.text('KindAid \u2014 Posts Data', 40, 40);
    doc.setFontSize(9);
    doc.setTextColor(100, 116, 139);
    doc.text('Generated: ' + new Date().toLocaleString(), 40, 58);

    // Skip: Images (col 4), Actions (col 7), Delete (col 8)
    const skipCols = [4, 7, 8];
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

    doc.save('KindAid_Posts_Data.pdf');
}
</script>

</body>
</html>