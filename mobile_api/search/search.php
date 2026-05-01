<?php
// =============================================
// GET /mobile_api/search/search.php
// Global search across all modules
// Query: ?q=search_term
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

$auth = require_auth();

$q = trim($_GET['q'] ?? '');

if (empty($q)) {
    send_error("Search query 'q' is required.");
}

$s = $conn->real_escape_string($q);

// Users / NGOs
$u_res = $conn->query("
    SELECT account_id, name, account_type, profile_photo, verified_status
    FROM accounts
    WHERE name LIKE '%$s%' OR email LIKE '%$s%' OR bio LIKE '%$s%'
    ORDER BY verified_status='Verified' DESC
    LIMIT 10
");
$users = [];
while ($row = $u_res->fetch_assoc()) {
    $row['profile_photo'] = !empty($row['profile_photo'])
        ? UPLOADS_URL . $row['profile_photo']
        : BASE_URL . "assets/default-user.png";
    $users[] = $row;
}

// Needs
$n_res = $conn->query("
    SELECT need_id, account_id, goal_title, description, goal_status, image_1
    FROM needs
    WHERE goal_title LIKE '%$s%' OR description LIKE '%$s%'
    ORDER BY created_at DESC LIMIT 10
");
$needs = [];
while ($row = $n_res->fetch_assoc()) {
    $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
    $needs[] = $row;
}

// Fundraisers
$f_res = $conn->query("
    SELECT fundraiser_id, account_id, title, description, goal_amount, collected_amount, image_1
    FROM fundraiser
    WHERE title LIKE '%$s%' OR description LIKE '%$s%'
    ORDER BY created_at DESC LIMIT 10
");
$fundraisers = [];
while ($row = $f_res->fetch_assoc()) {
    $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
    $fundraisers[] = $row;
}

// Locations
$l_res = $conn->query("
    SELECT geo_id, account_id, latitude, longitude, geo_type, description, goal_status
    FROM geo_locations
    WHERE description LIKE '%$s%'
    ORDER BY created_at DESC LIMIT 10
");
$locations = [];
while ($row = $l_res->fetch_assoc()) {
    $row['latitude']  = (float)$row['latitude'];
    $row['longitude'] = (float)$row['longitude'];
    $locations[] = $row;
}

// Community Posts
$p_res = $conn->query("
    SELECT community_forum.post_id, community_forum.account_id,
           community_forum.description, community_forum.image_1,
           community_forum.likes, community_forum.created_at,
           accounts.name
    FROM community_forum
    JOIN accounts ON community_forum.account_id = accounts.account_id
    WHERE community_forum.status='Active'
    AND (community_forum.description LIKE '%$s%' OR accounts.name LIKE '%$s%')
    ORDER BY community_forum.created_at DESC LIMIT 10
");
$posts = [];
while ($row = $p_res->fetch_assoc()) {
    $row['image_1'] = !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null;
    $posts[] = $row;
}

send_success([
    "query"      => $q,
    "users"      => $users,
    "needs"      => $needs,
    "fundraisers"=> $fundraisers,
    "locations"  => $locations,
    "posts"      => $posts,
]);
