<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

/* ======================== DELETE ======================== */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM accounts WHERE account_id=$id");
    header("Location: account_data.php");
    exit();
}

/* ======================== SEARCH ======================== */
$search = "";
if (isset($_GET['search']) && trim($_GET['search']) !== '') {
    $search   = $conn->real_escape_string(trim($_GET['search']));
    $accounts = $conn->query("SELECT * FROM accounts WHERE name LIKE '%$search%' OR email LIKE '%$search%' OR account_type LIKE '%$search%' ORDER BY created_at DESC");
} else {
    $accounts = $conn->query("SELECT * FROM accounts ORDER BY created_at DESC");
}

/* ======================== VERIFIED % ======================== */
$totalAccounts    = $conn->query("SELECT COUNT(*) as t FROM accounts")->fetch_assoc()['t'];
$verifiedAccounts = $conn->query("SELECT COUNT(*) as t FROM accounts WHERE verified_status='Verified'")->fetch_assoc()['t'];
$verifiedPercent  = $totalAccounts > 0 ? round(($verifiedAccounts / $totalAccounts) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Accounts Data</title>
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
            <h2><i class="fas fa-users"></i> Accounts Data</h2>
            <div style="display:flex; gap:8px; flex-wrap:wrap; align-items:center;">
                <form method="GET" class="Search">
                    <input type="text" name="search" placeholder="Search by Name / Email / Type"
                           value="<?php echo htmlspecialchars($search); ?>">
                    <button class="btn-view" type="submit"><i class="fas fa-search"></i> Search</button>
                </form>
                <button class="btn-secondary" onclick="downloadPDF()">
                    <i class="fas fa-file-pdf"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- Verification Progress -->
        <div class="verified-summary">
            <span>Verification Rate</span>
            <strong><?php echo $verifiedPercent; ?>% verified</strong>
        </div>
        <div class="progress-bar" style="margin-bottom:24px;">
            <div class="progress-fill" style="width:<?php echo $verifiedPercent; ?>%"></div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>ID</th>
                        <th>Type</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact</th>
                        <th>Address</th>
                        <th>Bio</th>
                        <th>Status</th>
                        <th>Documents</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $accounts->fetch_assoc()) { ?>
                        <tr>
                            <td style="text-align:center;">
                                <?php if (!empty($row['profile_photo'])) { ?>
                                    <img src="../uploads/<?php echo $row['profile_photo']; ?>"
                                         class="thumb-avatar" onclick="openModal(this.src)">
                                <?php } else { ?>
                                    <img src="../assets/default-user.png" class="thumb-avatar">
                                <?php } ?>
                            </td>
                            <td>#<?php echo $row['account_id']; ?></td>
                            <td>
                                <span class="badge" style="background:var(--primary-soft);color:var(--primary-dark);">
                                    <?php echo htmlspecialchars($row['account_type']); ?>
                                </span>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><?php echo htmlspecialchars($row['contact_no']); ?></td>
                            <td style="max-width:120px;"><?php echo htmlspecialchars($row['address']); ?></td>
                            <td style="max-width:160px; color:var(--text-muted);"><?php echo htmlspecialchars($row['bio']); ?></td>
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
                                <?php
                                $doc1 = !empty($row['document_1']);
                                $doc2 = !empty($row['document_2']);
                                if (!$doc1 && !$doc2) {
                                    echo "<span style='color:var(--text-muted);font-size:0.8rem;'>No Docs</span>";
                                } else { ?>
                                    <div class="pdf-links">
                                        <?php if ($doc1) { ?>
                                            <a class="btn-view" href="../uploads/doc/<?php echo $row['document_1']; ?>" target="_blank">
                                                <i class="fas fa-file-pdf"></i> PDF 1
                                            </a>
                                        <?php } ?>
                                        <?php if ($doc2) { ?>
                                            <a class="btn-view" href="../uploads/doc/<?php echo $row['document_2']; ?>" target="_blank">
                                                <i class="fas fa-file-pdf"></i> PDF 2
                                            </a>
                                        <?php } ?>
                                    </div>
                                <?php } ?>
                            </td>
                            <td>
                                <a class="btn-delete" href="?delete=<?php echo $row['account_id']; ?>"
                                   onclick="return confirm('Delete this account? This cannot be undone.');">
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
        <img id="modalImage" src="" alt="Profile photo">
    </div>
</div>

<script>
function openModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').classList.add('open');
}
function closeModal() {
    document.getElementById('imageModal').classList.remove('open');
}
</script>

<script>
function downloadPDF() {
    const { jsPDF } = window.jspdf;
    const doc = new jsPDF({ orientation: 'landscape', unit: 'pt', format: 'a4' });

    // Title
    doc.setFontSize(16);
    doc.setTextColor(21, 40, 94);
    doc.text('KindAid — Accounts Data', 40, 40);
    doc.setFontSize(9);
    doc.setTextColor(100, 116, 139);
    doc.text('Generated: ' + new Date().toLocaleString(), 40, 58);

    // Collect table data (skip Profile image column & Action column)
    const table = document.querySelector('table');
    const headers = [];
    const rows = [];
    // Skip col index 0 (Profile) and last col (Action)
    const skipCols = [0, table.querySelectorAll('thead th').length - 1];

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
        head: headers,
        body: rows,
        startY: 70,
        styles: { fontSize: 8, cellPadding: 4, overflow: 'linebreak' },
        headStyles: { fillColor: [21, 40, 94], textColor: 255, fontStyle: 'bold' },
        alternateRowStyles: { fillColor: [241, 245, 249] },
        margin: { left: 40, right: 40 },
        tableWidth: 'auto'
    });

    doc.save('KindAid_Accounts_Data.pdf');
}
</script>

</body>
</html>