<?php
// =============================================
// GET /mobile_api/posts/get_posts.php
// Fetch community posts (with search + user filter)
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
    $userFilter = "AND community_forum.account_id=$uid";
}

if (!empty($_GET['search'])) {
    $s = $conn->real_escape_string(trim($_GET['search']));
    $searchQuery = "AND (community_forum.description LIKE '%$s%' OR accounts.name LIKE '%$s%')";
}

$result = $conn->query("
    SELECT community_forum.post_id, community_forum.account_id,
           community_forum.description, community_forum.image_1,
           community_forum.image_2, community_forum.likes,
           community_forum.liked_users, community_forum.created_at,
           accounts.name, accounts.profile_photo, accounts.verified_status
    FROM community_forum
    JOIN accounts ON community_forum.account_id = accounts.account_id
    WHERE community_forum.status='Active'
    $userFilter
    $searchQuery
    ORDER BY community_forum.created_at DESC
");

$posts = [];
while ($row = $result->fetch_assoc()) {

    // Check if current user liked this post
    $liked_array = $row['liked_users'] ? explode(",", $row['liked_users']) : [];
    $is_liked    = in_array($account_id, $liked_array);

    // Comment count
    $cc = $conn->query("SELECT COUNT(*) as total FROM comments WHERE post_id={$row['post_id']}")->fetch_assoc();

    $posts[] = [
        "post_id"         => $row['post_id'],
        "account_id"      => $row['account_id'],
        "name"            => $row['name'],
        "profile_photo"   => !empty($row['profile_photo'])
                                ? UPLOADS_URL . $row['profile_photo']
                                : BASE_URL . "assets/default-user.png",
        "verified_status" => $row['verified_status'],
        "description"     => $row['description'],
        "image_1"         => !empty($row['image_1']) ? UPLOADS_URL . $row['image_1'] : null,
        "image_2"         => !empty($row['image_2']) ? UPLOADS_URL . $row['image_2'] : null,
        "likes"           => (int)$row['likes'],
        "is_liked"        => $is_liked,
        "comment_count"   => (int)$cc['total'],
        "is_owner"        => (bool)($row['account_id'] == $account_id),
        "created_at"      => $row['created_at'],
    ];
}

send_success($posts);
