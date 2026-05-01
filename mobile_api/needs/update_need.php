<?php
// =============================================
// POST /mobile_api/needs/update_need.php
// Update a need's details or status
// Body: need_id, goal_title, description, progress_percent, goal_status
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$need_id         = (int)($_POST['need_id'] ?? 0);
$goal_title      = $conn->real_escape_string(trim($_POST['goal_title'] ?? ''));
$description     = $conn->real_escape_string(trim($_POST['description'] ?? ''));
$progress_percent = min(100, max(0, (int)($_POST['progress_percent'] ?? 0)));
$goal_status     = $_POST['goal_status'] ?? 'Pending';

if (!$need_id || empty($goal_title)) {
    send_error("need_id and goal_title are required.");
}

$valid_statuses = ['Pending', 'Ongoing', 'Fulfilled'];
if (!in_array($goal_status, $valid_statuses)) {
    $goal_status = 'Pending';
}

// Ownership check
$check = $conn->query("SELECT account_id FROM needs WHERE need_id=$need_id")->fetch_assoc();
if (!$check) send_error("Need not found.", 404);
if ($check['account_id'] != $account_id) send_error("You can only update your own needs.", 403);

$conn->query("
    UPDATE needs
    SET goal_title='$goal_title',
        description='$description',
        progress_percent=$progress_percent,
        goal_status='$goal_status'
    WHERE need_id=$need_id AND account_id=$account_id
");

send_success(null, "Need updated.");
