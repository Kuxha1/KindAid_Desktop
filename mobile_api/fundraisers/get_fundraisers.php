<?php
// =============================================
// GET /mobile_api/fundraisers/get_fundraisers.php
// Fetch all fundraisers (with search + user filter)
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
    $userFilter = "AND fundraiser.account_id=$uid";
}

if (!empty($_GET['search'])) {
    $s = $conn->real_escape_string(trim($_GET['search']));
    $searchQuery = "AND (fundraiser.title LIKE '%$s%' OR fundraiser.description LIKE '%$s%' OR accounts.name LIKE '%$s%')";
}

$result = $conn->query("
    SELECT fundraiser.*, accounts.name, accounts.profile_photo, accounts.verified_status
    FROM fundraiser
    JOIN accounts ON fundraiser.account_id = accounts.account_id
    WHERE 1=1 $userFilter $searchQuery
    ORDER BY fundraiser.created_at DESC
");

$fundraisers = [];
while ($row = $result->fetch_assoc()) {
    $goal   = (float)$row['goal_amount'];
    $raised = (float)$row['collected_amount'];
    $pct    = $goal > 0 ? round(($raised / $goal) * 100, 1) : 0;

    $fundraisers[] = [
        "fundraiser_id"    => $row['fundraiser_id'],
        "account_id"       => $row['account_id'],
        "name"             => $row['name'],
        "profile_photo"    => !empty($row['profile_photo'])
                                ? UPLOADS_URL . $row['profile_photo']
                                : BASE_URL . "assets/default-user.png",
        "verified_status"  => $row['verified_status'],
        "title"            => $row['title'],
        "description"      => $row['description'],
        "goal_amount"      => $goal,
        "collected_amount" => $raised,
        "progress_percent" => $pct,
        "fund_verified"    => $row['verified_status'],
        "image_1"          => !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null,
        "image_2"          => !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null,
        "document_1"       => !empty($row['document_1']) ? DOCS_URL . $row['document_1'] : null,
        "document_2"       => !empty($row['document_2']) ? DOCS_URL . $row['document_2'] : null,
        "is_owner"         => (bool)($row['account_id'] == $account_id),
        "created_at"       => $row['created_at'],
    ];
}

send_success($fundraisers);
