<?php
// =============================================
// POST /mobile_api/profile/change_password.php
// Body: current_password, new_password, confirm_password
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$current  = trim($_POST['current_password'] ?? '');
$newPass  = trim($_POST['new_password'] ?? '');
$confirm  = trim($_POST['confirm_password'] ?? '');

if (empty($current) || empty($newPass) || empty($confirm)) {
    send_error("All password fields are required.");
}

if ($newPass !== $confirm) {
    send_error("New passwords do not match.");
}

if (strlen($newPass) < 6) {
    send_error("Password must be at least 6 characters.");
}

// Verify current password
$row = $conn->query("SELECT password FROM accounts WHERE account_id=$account_id")->fetch_assoc();
if (!$row) send_error("Account not found.", 404);

if (!password_verify($current, $row['password'])) {
    send_error("Current password is incorrect.");
}

$hashed = password_hash($newPass, PASSWORD_DEFAULT);
$conn->query("UPDATE accounts SET password='$hashed' WHERE account_id=$account_id");

send_success(null, "Password changed successfully.");
