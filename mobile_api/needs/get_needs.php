<?php
// =============================================
// GET /mobile_api/needs/get_needs.php
// Fetch all needs (with search + user filter)
// Query: ?search=X  ?user_id=X
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$userFilter  = "";
$searchQuery = "";

if (!empty($_GET['user_id'])) {
    $uid = (int)$_GET['user_id'];
    $userFilter = "AND needs.account_id=$uid";
}

if (!empty($_GET['search'])) {
    $s = $conn->real_escape_string(trim($_GET['search']));
    $searchQuery = "AND (needs.goal_title LIKE '%$s%' OR needs.description LIKE '%$s%' OR accounts.name LIKE '%$s%')";
}

$result = $conn->query("
    SELECT needs.*, accounts.name, accounts.profile_photo, accounts.verified_status
    FROM needs
    JOIN accounts ON needs.account_id = accounts.account_id
    WHERE 1=1 $userFilter $searchQuery
    ORDER BY needs.created_at DESC
");

$needs = [];
while ($row = $result->fetch_assoc()) {
    $needs[] = [
        "need_id"         => $row['need_id'],
        "account_id"      => $row['account_id'],
        "name"            => $row['name'],
        "profile_photo"   => !empty($row['profile_photo'])
                                ? UPLOADS_URL . $row['profile_photo']
                                : BASE_URL . "assets/default-user.png",
        "verified_status" => $row['verified_status'],
        "goal_title"      => $row['goal_title'],
        "description"     => $row['description'],
        "progress_percent"=> (int)$row['progress_percent'],
        "goal_status"     => $row['goal_status'],
        "image_1"         => !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null,
        "image_2"         => !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null,
        "is_owner"        => (bool)($row['account_id'] == $account_id),
        "created_at"      => $row['created_at'],
    ];
}

send_success($needs);
