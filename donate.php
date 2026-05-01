<?php
session_start();
include "admin/config/db.php";

/* ==========================
CHECK LOGIN
========================== */

if (!isset($_SESSION['account_id'])) {
    header("Location: login.php");
    exit();
}

$donor_id = $_SESSION['account_id'];


/* ==========================
CHECK FORM SUBMISSION
========================== */

if ($_SERVER['REQUEST_METHOD'] == "POST") {

    $fundraiser_id = (int)$_POST['fundraiser_id'];
    $amount = (float)$_POST['amount'];
    $payment_method = $conn->real_escape_string($_POST['payment_method']);

    if ($amount <= 0) {
        header("Location: fundraiser.php");
        exit();
    }


/* ==========================
INSERT TRANSACTION
========================== */

$conn->query("
INSERT INTO transactions
(fundraiser_id, donor_id, amount, payment_method, payment_status)
VALUES
('$fundraiser_id', '$donor_id', '$amount', '$payment_method', 'Completed')
");


/* ==========================
UPDATE FUNDRAISER AMOUNT
========================== */

$conn->query("
UPDATE fundraiser
SET collected_amount = collected_amount + $amount
WHERE fundraiser_id = $fundraiser_id
");


/* ==========================
REDIRECT
========================== */

header("Location: fundraiser.php");
exit();

}

?>