<?php
// =============================================
// POST /mobile_api/posts/like_post.php
// Toggle like / unlike on a post
// Body: post_id
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];
$post_id    = (int)($_POST['post_id'] ?? 0);

if (!$post_id) {
    send_error("post_id is required.");
}

$post = $conn->query("
    SELECT likes, liked_users FROM community_forum WHERE post_id=$post_id
")->fetch_assoc();

if (!$post) {
    send_error("Post not found.", 404);
}

$liked_array = $post['liked_users'] ? explode(",", $post['liked_users']) : [];

if (in_array($account_id, $liked_array)) {
    // UNLIKE
    $liked_array = array_values(array_diff($liked_array, [$account_id]));
    $new_likes   = max(0, $post['likes'] - 1);
    $liked       = 0;
} else {
    // LIKE
    $liked_array[] = $account_id;
    $new_likes     = $post['likes'] + 1;
    $liked         = 1;
}

$liked_string = $conn->real_escape_string(implode(",", $liked_array));
$conn->query("
    UPDATE community_forum
    SET likes=$new_likes, liked_users='$liked_string'
    WHERE post_id=$post_id
");

send_success([
    "liked" => $liked,
    "likes" => $new_likes,
]);
