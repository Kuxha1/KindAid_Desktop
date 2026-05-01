<?php
// =============================================
// GET /mobile_api/posts/get_comments.php
// Fetch comments for a specific post
// Query: ?post_id=X
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

$auth       = require_auth();
$account_id = (int)$auth['account_id'];
$post_id    = (int)($_GET['post_id'] ?? 0);

if (!$post_id) {
    send_error("post_id is required.");
}

$result = $conn->query("
    SELECT comments.comment_id, comments.account_id, comments.comment_text, comments.created_at,
           accounts.name, accounts.profile_photo
    FROM comments
    JOIN accounts ON comments.account_id = accounts.account_id
    WHERE comments.post_id=$post_id
    ORDER BY comments.created_at ASC
");

$comments = [];
while ($row = $result->fetch_assoc()) {
    $comments[] = [
        "comment_id"    => $row['comment_id'],
        "account_id"    => $row['account_id'],
        "name"          => $row['name'],
        "profile_photo" => !empty($row['profile_photo'])
                            ? UPLOADS_URL . $row['profile_photo']
                            : BASE_URL . "assets/default-user.png",
        "comment_text"  => $row['comment_text'],
        "is_owner"      => (bool)($row['account_id'] == $account_id),
        "created_at"    => $row['created_at'],
    ];
}

send_success($comments);
