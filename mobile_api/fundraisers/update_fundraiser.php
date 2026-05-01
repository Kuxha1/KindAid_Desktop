<?php
// =============================================
// POST /mobile_api/fundraisers/update_fundraiser.php
// Update a fundraiser
// Body: fundraiser_id, title, description, goal_amount
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth          = require_auth();
$account_id    = (int)$auth['account_id'];
$fundraiser_id = (int)($_POST['fundraiser_id'] ?? 0);
$title         = $conn->real_escape_string(trim($_POST['title'] ?? ''));
$description   = $conn->real_escape_string(trim($_POST['description'] ?? ''));
$goal_amount   = (float)($_POST['goal_amount'] ?? 0);

if (!$fundraiser_id || empty($title) || $goal_amount <= 0) {
    send_error("fundraiser_id, title and goal_amount are required.");
}

$check = $conn->query("SELECT account_id FROM fundraiser WHERE fundraiser_id=$fundraiser_id")->fetch_assoc();
if (!$check) send_error("Fundraiser not found.", 404);
if ($check['account_id'] != $account_id) send_error("You can only update your own fundraisers.", 403);

$conn->query("
    UPDATE fundraiser
    SET title='$title', description='$description', goal_amount='$goal_amount'
    WHERE fundraiser_id=$fundraiser_id AND account_id=$account_id
");

send_success(null, "Fundraiser updated.");
