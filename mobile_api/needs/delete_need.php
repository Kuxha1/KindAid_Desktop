<?php
// =============================================
// POST /mobile_api/needs/delete_need.php
// Delete own need
// Body: need_id
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];
$need_id    = (int)($_POST['need_id'] ?? 0);

if (!$need_id) send_error("need_id is required.");

$check = $conn->query("SELECT account_id FROM needs WHERE need_id=$need_id")->fetch_assoc();
if (!$check) send_error("Need not found.", 404);
if ($check['account_id'] != $account_id) send_error("You can only delete your own needs.", 403);

$conn->query("DELETE FROM needs WHERE need_id=$need_id");
send_success(null, "Need deleted.");
