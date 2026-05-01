<?php
// =============================================
// POST /mobile_api/fundraisers/donate.php
// Process a donation
// Body: fundraiser_id, amount, payment_method
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth          = require_auth();
$donor_id      = (int)$auth['account_id'];

$fundraiser_id  = (int)($_POST['fundraiser_id'] ?? 0);
$amount         = (float)($_POST['amount'] ?? 0);
$payment_method = $conn->real_escape_string($_POST['payment_method'] ?? 'Online');

if (!$fundraiser_id || $amount <= 0) {
    send_error("fundraiser_id and a positive amount are required.");
}

$valid_methods = ['Online', 'UPI', 'Card'];
if (!in_array($payment_method, $valid_methods)) {
    $payment_method = 'Online';
}

// Cannot donate to own fundraiser
$fund = $conn->query("
    SELECT account_id, title, goal_amount, collected_amount
    FROM fundraiser WHERE fundraiser_id=$fundraiser_id
")->fetch_assoc();

if (!$fund) send_error("Fundraiser not found.", 404);
if ($fund['account_id'] == $donor_id) send_error("You cannot donate to your own fundraiser.");

// Insert transaction
$conn->query("
    INSERT INTO transactions (fundraiser_id, donor_id, amount, payment_method, payment_status)
    VALUES ('$fundraiser_id','$donor_id','$amount','$payment_method','Completed')
");

$transaction_id = $conn->insert_id;

// Update collected amount
$conn->query("
    UPDATE fundraiser
    SET collected_amount = collected_amount + $amount
    WHERE fundraiser_id = $fundraiser_id
");

// Fetch updated fundraiser data
$updated = $conn->query("
    SELECT collected_amount, goal_amount FROM fundraiser WHERE fundraiser_id=$fundraiser_id
")->fetch_assoc();

$new_collected = (float)$updated['collected_amount'];
$goal          = (float)$updated['goal_amount'];
$new_percent   = $goal > 0 ? round(($new_collected / $goal) * 100, 1) : 0;

send_success([
    "transaction_id"   => $transaction_id,
    "collected_amount" => $new_collected,
    "goal_amount"      => $goal,
    "progress_percent" => $new_percent,
], "Donation successful. Thank you!");
