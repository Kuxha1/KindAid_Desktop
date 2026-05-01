<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

/* ========================= APPROVE / REJECT ========================= */
// BUG FIX: was redirecting to user_verification.php — now redirects to ngo_verification.php
if (isset($_GET['approve'])) {
    $id = (int)$_GET['approve'];
    $conn->query("UPDATE accounts SET verified_status='Verified' WHERE account_id=$id");
    header("Location: ngo_verification.php");
    exit();
}

if (isset($_GET['reject'])) {
    $id = (int)$_GET['reject'];
    $conn->query("UPDATE accounts SET verified_status='Rejected' WHERE account_id=$id");
    header("Location: ngo_verification.php");
    exit();
}

/* ========================= DELETE ========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM accounts WHERE account_id=$id");
    header("Location: ngo_verification.php");
    exit();
}

/* ========================= FETCH NGOS ========================= */
$ngos = $conn->query("SELECT * FROM accounts WHERE account_type='NGO' ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — NGO Verification</title>
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
            <h2><i class="fas fa-building-ngo"></i> NGO Verification</h2>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Profile</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Documents</th>
                        <th>Actions</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $ngos->fetch_assoc()) { ?>
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
                            <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
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
                                <div class="action-links">
                                    <a href="?approve=<?php echo $row['account_id']; ?>" class="approve-link">
                                        <i class="fas fa-check"></i> Approve
                                    </a>
                                    <a href="?reject=<?php echo $row['account_id']; ?>" class="reject-link">
                                        <i class="fas fa-xmark"></i> Reject
                                    </a>
                                </div>
                            </td>
                            <td>
                                <a class="btn-delete" href="?delete=<?php echo $row['account_id']; ?>"
                                   onclick="return confirm('Delete this NGO account?');">
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
function openModal(src) { document.getElementById('modalImage').src = src; document.getElementById('imageModal').classList.add('open'); }
function closeModal() { document.getElementById('imageModal').classList.remove('open'); }
</script>

</body>
</html>