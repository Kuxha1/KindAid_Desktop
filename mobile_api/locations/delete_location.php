<?php
// =============================================
// POST /mobile_api/locations/delete_location.php
// Delete own location marker
// Body: geo_id
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];
$geo_id     = (int)($_POST['geo_id'] ?? 0);

if (!$geo_id) send_error("geo_id is required.");

$check = $conn->query("SELECT account_id FROM geo_locations WHERE geo_id=$geo_id")->fetch_assoc();
if (!$check) send_error("Location not found.", 404);
if ($check['account_id'] != $account_id) send_error("You can only delete your own locations.", 403);

$conn->query("DELETE FROM geo_locations WHERE geo_id=$geo_id");
send_success(null, "Location deleted.");
