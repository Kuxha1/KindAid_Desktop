<?php
// =============================================
// POST /mobile_api/posts/delete_post.php
// Delete own post
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

// Ownership check
$check = $conn->query("
    SELECT account_id FROM community_forum WHERE post_id=$post_id
")->fetch_assoc();

if (!$check) {
    send_error("Post not found.", 404);
}

if ($check['account_id'] != $account_id) {
    send_error("You can only delete your own posts.", 403);
}

$conn->query("DELETE FROM community_forum WHERE post_id=$post_id");
send_success(null, "Post deleted.");
