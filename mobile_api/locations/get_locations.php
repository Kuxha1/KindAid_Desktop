<?php
// =============================================
// GET /mobile_api/locations/get_locations.php
// Fetch all geo location markers
// Query: ?user_id=X
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$userFilter = "";
if (!empty($_GET['user_id'])) {
    $uid = (int)$_GET['user_id'];
    $userFilter = "AND geo_locations.account_id=$uid";
}

$result = $conn->query("
    SELECT geo_locations.*, accounts.name, accounts.profile_photo, accounts.verified_status
    FROM geo_locations
    JOIN accounts ON geo_locations.account_id = accounts.account_id
    WHERE 1=1 $userFilter
    ORDER BY geo_locations.created_at DESC
");

$locations = [];
while ($row = $result->fetch_assoc()) {
    $locations[] = [
        "geo_id"          => $row['geo_id'],
        "account_id"      => $row['account_id'],
        "name"            => $row['name'],
        "profile_photo"   => !empty($row['profile_photo'])
                                ? UPLOADS_URL . $row['profile_photo']
                                : BASE_URL . "assets/default-user.png",
        "verified_status" => $row['verified_status'],
        "latitude"        => (float)$row['latitude'],
        "longitude"       => (float)$row['longitude'],
        "geo_type"        => $row['geo_type'],
        "description"     => $row['description'],
        "image_1"         => !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null,
        "image_2"         => !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null,
        "goal_status"     => $row['goal_status'],
        "is_owner"        => (bool)($row['account_id'] == $account_id),
        "created_at"      => $row['created_at'],
    ];
}

send_success($locations);
