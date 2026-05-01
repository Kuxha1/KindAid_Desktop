<?php
// =============================================
// POST /mobile_api/locations/update_location.php
// Update a geo location's status
// Body: geo_id, goal_status
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$geo_id      = (int)($_POST['geo_id'] ?? 0);
$goal_status = $conn->real_escape_string(trim($_POST['goal_status'] ?? ''));

if ($geo_id <= 0) {
    send_error("geo_id is required.");
}

$valid_statuses = ['Pending', 'Ongoing', 'Completed'];
if (!in_array($goal_status, $valid_statuses)) {
    send_error("Invalid goal_status. Must be Pending, Ongoing, or Completed.");
}

// Verify ownership
$check = $conn->query("SELECT account_id FROM geo_locations WHERE geo_id = '$geo_id'");
if ($check->num_rows === 0) {
    send_error("Location not found.");
}
$loc = $check->fetch_assoc();
if ((int)$loc['account_id'] !== $account_id) {
    send_error("Unauthorized to update this location.", 403);
}

// Update
$conn->query("UPDATE geo_locations SET goal_status = '$goal_status' WHERE geo_id = '$geo_id'");

send_success(["geo_id" => $geo_id, "goal_status" => $goal_status], "Location status updated successfully.");
