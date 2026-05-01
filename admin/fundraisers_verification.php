<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

/* ========================= APPROVE / REJECT ========================= */
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $conn->query("UPDATE fundraiser SET verified_status='Verified' WHERE fundraiser_id=$id");
    header("Location: fundraisers_verification.php");
    exit();
}

if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $conn->query("UPDATE fundraiser SET verified_status='Rejected' WHERE fundraiser_id=$id");
    header("Location: fundraisers_verification.php");
    exit();
}

/* ========================= DELETE ========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM fundraiser WHERE fundraiser_id=$id");
    header("Location: fundraisers_verification.php");
    exit();
}

/* ========================= DOWNLOAD ========================= */
if (isset($_GET['download_all'])) {
    $result = $conn->query("SELECT * FROM fundraiser ORDER BY created_at DESC");
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=all_fundraisers.txt");
    echo "ID | Account ID | Title | Description | Goal | Collected | Status | Created At\n";
    echo "------------------------------------------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['fundraiser_id'] . " | " . $row['account_id'] . " | " . $row['title'] . " | " .
             str_replace("\n", " ", $row['description']) . " | " . $row['goal_amount'] . " | " .
             $row['collected_amount'] . " | " . $row['verified_status'] . " | " . $row['created_at'] . "\n";
    }
    exit();
}

/* ========================= FETCH ========================= */
$fundraisers = $conn->query("SELECT * FROM fundraiser ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Fundraisers Verification</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="includes/admin-style.css">
</head>
<body>

<?php include "includes/sidebar.php"; ?>
<div class="main-wrapper">
    <?php include "includes/header.php"; ?>

    <div class="table-container">
        <div class="table-title">
            <h2><i class="fas fa-hand-holding-heart"></i> Fundraisers Verification</h2>
            <a class="btn-secondary" href="?download_all=1">
                <i class="fas fa-download"></i> Download Data
            </a>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Account ID</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Goal</th>
                        <th>Collected</th>
                        <th>Images</th>
                        <th>Documents</th>
                        <th>Status</th>
                        <th>Actions</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $fundraisers->fetch_assoc()) { ?>
                        <tr>
                            <td><strong>#<?php echo $row['fundraiser_id']; ?></strong></td>
                            <td>#<?php echo $row['account_id']; ?></td>
                            <td style="max-width:140px;"><strong><?php echo htmlspecialchars($row['title']); ?></strong></td>
                            <td style="max-width:160px; color:var(--text-muted); font-size:0.82rem;"><?php echo htmlspecialchars($row['description']); ?></td>
                            <td>₹<?php echo number_format($row['goal_amount']); ?></td>
                            <td>₹<?php echo number_format($row['collected_amount']); ?></td>

                            <!-- IMAGES -->
                            <td style="text-align:center;">
                                <?php if ($row['image_1']) { ?>
                                    <img src="../uploads/<?php echo $row['image_1']; ?>"
                                         class="thumb-img" onclick="openModal(this.src)">
                                <?php } ?>
                                <?php if ($row['image_2']) { ?>
                                    <img src="../uploads/<?php echo $row['image_2']; ?>"
                                         class="thumb-img" onclick="openModal(this.src)">
                                <?php } ?>
                                <?php if (!$row['image_1'] && !$row['image_2']) { echo '<span style="color:var(--text-muted);font-size:0.8rem;">None</span>'; } ?>
                            </td>

                            <!-- DOCUMENTS -->
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

                            <!-- STATUS -->
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

                            <!-- ACTIONS -->
                            <td>
                                <div class="action-links">
                                    <a href="?approve=<?php echo $row['fundraiser_id']; ?>" class="approve-link">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="?reject=<?php echo $row['fundraiser_id']; ?>" class="reject-link">
                                        <i class="fas fa-xmark"></i> Reject
                                    </a>
                                </div>
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
</script>

</body>
</html>