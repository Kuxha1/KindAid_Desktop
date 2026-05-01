<?php
// =============================================
// GET /mobile_api/profile/get_profile.php
// Get own profile or another user's profile
// Query: ?account_id=X (optional)
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

$auth = require_auth();
$my_id = (int)$auth['account_id'];

// View own profile or another user's
$view_id = isset($_GET['account_id']) ? (int)$_GET['account_id'] : $my_id;

// Fetch account
$account = $conn->query("
    SELECT account_id, account_type, name, email, contact_no,
           address, profile_photo, bio, verified_status, document_1, document_2, created_at
    FROM accounts
    WHERE account_id=$view_id
")->fetch_assoc();

if (!$account) {
    send_error("User not found.", 404);
}

$account['profile_photo'] = !empty($account['profile_photo'])
    ? UPLOADS_URL . $account['profile_photo']
    : BASE_URL . "assets/default-user.png";
$account['document_1'] = !empty($account['document_1']) ? DOCS_URL . $account['document_1'] : null;
$account['document_2'] = !empty($account['document_2']) ? DOCS_URL . $account['document_2'] : null;

// Fetch posts
$posts_res = $conn->query("
    SELECT post_id, description, image_1, image_2, likes, liked_users, created_at, status
    FROM community_forum
    WHERE account_id=$view_id AND status='Active'
    ORDER BY created_at DESC
");
$posts = [];
while ($row = $posts_res->fetch_assoc()) {
    $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
    $row['image_2'] = !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null;
    $row['account_id'] = $view_id;
    $row['name'] = $account['name'];
    $row['profile_photo'] = $account['profile_photo'];
    $row['verified_status'] = $account['verified_status'];
    $row['is_owner'] = (bool)($view_id === $my_id);
    $pid = (int)$row['post_id'];
    $cc = $conn->query("SELECT COUNT(*) as c FROM comments WHERE post_id=$pid")->fetch_assoc();
    $row['comment_count'] = (int)($cc['c'] ?? 0);

    // Is liked by viewer (likes stored as CSV in liked_users column)
    $liked_users = $row['liked_users'] ?? '';
    $liked_arr = $liked_users ? explode(",", $liked_users) : [];
    $row['is_liked'] = in_array($my_id, $liked_arr);

    $posts[] = $row;
}

// Fetch needs
$needs_res = $conn->query("
    SELECT need_id, goal_title, description, progress_percent, goal_status, image_1, image_2, created_at
    FROM needs
    WHERE account_id=$view_id
    ORDER BY created_at DESC
");
$needs = [];
while ($row = $needs_res->fetch_assoc()) {
    $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
    $row['image_2'] = !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null;
    $row['account_id'] = $view_id;
    $row['name'] = $account['name'];
    $row['profile_photo'] = $account['profile_photo'];
    $row['verified_status'] = $account['verified_status'];
    $row['is_owner'] = (bool)($view_id === $my_id);
    $needs[] = $row;
}

// Fetch fundraisers
$funds_res = $conn->query("
    SELECT fundraiser_id, title, description, goal_amount, collected_amount,
           verified_status, image_1, image_2, document_1, document_2, created_at
    FROM fundraiser
    WHERE account_id=$view_id
    ORDER BY created_at DESC
");
$fundraisers = [];
while ($row = $funds_res->fetch_assoc()) {
    $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
    $row['image_2'] = !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null;
    $row['document_1'] = !empty($row['document_1']) ? DOCS_URL . $row['document_1'] : null;
    $row['document_2'] = !empty($row['document_2']) ? DOCS_URL . $row['document_2'] : null;
    $row['account_id'] = $view_id;
    $row['name'] = $account['name'];
    $row['profile_photo'] = $account['profile_photo'];
    $row['is_owner'] = (bool)($view_id === $my_id);
    $goal   = (float)$row['goal_amount'];
    $raised = (float)$row['collected_amount'];
    $row['progress_percent'] = $goal > 0 ? round(($raised / $goal) * 100, 1) : 0;
    $fundraisers[] = $row;
}

// Fetch locations
$locs_res = $conn->query("
    SELECT geo_id, latitude, longitude, geo_type, description, image_1, image_2, goal_status, created_at
    FROM geo_locations
    WHERE account_id=$view_id
    ORDER BY created_at DESC
");
$locations = [];
while ($row = $locs_res->fetch_assoc()) {
    $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
    $row['image_2'] = !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null;
    $row['account_id'] = $view_id;
    $row['name'] = $account['name'];
    $row['profile_photo'] = $account['profile_photo'];
    $row['verified_status'] = $account['verified_status'];
    $row['is_owner'] = (bool)($view_id === $my_id);
    $locations[] = $row;
}

send_success([
    "account"     => $account,
    "is_own"      => (bool)($view_id === $my_id),
    "posts"       => $posts,
    "needs"       => $needs,
    "fundraisers" => $fundraisers,
    "locations"   => $locations,
]);
