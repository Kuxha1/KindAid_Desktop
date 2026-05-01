<?php
session_start();
include "config/db.php";

if (!isset($_SESSION['email'])) {
    header("Location: admin_login.php");
    exit();
}

/* ========================= DELETE TRANSACTION ========================= */
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM transactions WHERE transaction_id=$id");
    header("Location: payments_verification.php");
    exit();
}

/* ========================= DOWNLOAD ========================= */
if (isset($_GET['download_all'])) {
    $result = $conn->query("SELECT * FROM transactions ORDER BY transaction_date DESC");
    header("Content-Type: text/plain");
    header("Content-Disposition: attachment; filename=all_transactions.txt");
    echo "Transaction ID | Fundraiser ID | Donor ID | Amount | Method | Status | Date\n";
    echo "-------------------------------------------------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo $row['transaction_id'] . " | " . $row['fundraiser_id'] . " | " . $row['donor_id'] . " | " .
             $row['amount'] . " | " . $row['payment_method'] . " | " . $row['payment_status'] . " | " .
             $row['transaction_date'] . "\n";
    }
    exit();
}

/* ========================= FETCH ========================= */
$transactions = $conn->query("SELECT * FROM transactions ORDER BY transaction_date DESC");

/* ========================= SUMMARY STATS ========================= */
$totalTx        = $conn->query("SELECT COUNT(*) as t FROM transactions")->fetch_assoc()['t'];
$completedTx    = $conn->query("SELECT COUNT(*) as t FROM transactions WHERE payment_status='Completed'")->fetch_assoc()['t'];
$totalCollected = $conn->query("SELECT SUM(amount) as t FROM transactions WHERE payment_status='Completed'")->fetch_assoc()['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KindAid Admin — Payments Verification</title>
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
            <h2><i class="fas fa-credit-card"></i> Payments / Transactions</h2>
            <a class="btn-secondary" href="?download_all=1">
                <i class="fas fa-download"></i> Download All
            </a>
        </div>

        <!-- SUMMARY CARDS -->
        <div class="stats-grid" style="margin-bottom:24px;">
            <div class="card">
                <h3><i class="fas fa-receipt" style="margin-right:5px;color:var(--secondary);"></i>Total Transactions</h3>
                <div class="value"><?php echo number_format($totalTx); ?></div>
            </div>
            <div class="card">
                <h3><i class="fas fa-circle-check" style="margin-right:5px;color:var(--success);"></i>Completed</h3>
                <div class="value"><?php echo number_format($completedTx); ?></div>
            </div>
            <div class="card">
                <h3><i class="fas fa-indian-rupee-sign" style="margin-right:5px;color:var(--primary);"></i>Total Collected</h3>
                <div class="value">₹<?php echo number_format($totalCollected, 0); ?></div>
            </div>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Fundraiser ID</th>
                        <th>Donor ID</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Delete</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $transactions->fetch_assoc()) { ?>
                        <tr>
                            <td><strong>#<?php echo $row['transaction_id']; ?></strong></td>
                            <td>#<?php echo $row['fundraiser_id']; ?></td>
                            <td>#<?php echo $row['donor_id']; ?></td>
                            <td><strong style="color:var(--success);">₹<?php echo number_format($row['amount'], 2); ?></strong></td>
                            <td>
                                <span class="badge" style="background:var(--accent-soft); color:var(--accent-dark);">
                                    <?php echo htmlspecialchars($row['payment_method']); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ($row['payment_status'] == 'Completed')
                                    echo "<span class='badge status-verified'>Completed</span>";
                                elseif ($row['payment_status'] == 'Pending')
                                    echo "<span class='badge status-pending'>Pending</span>";
                                else
                                    echo "<span class='badge status-rejected'>Failed</span>";
                                ?>
                            </td>
                            <td style="white-space:nowrap;"><?php echo date('M d, Y', strtotime($row['transaction_date'])); ?></td>
                            <td>
                                <a class="btn-delete" href="?delete=<?php echo $row['transaction_id']; ?>"
                                   onclick="return confirm('Delete this transaction record?');">
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

</body>
</html>