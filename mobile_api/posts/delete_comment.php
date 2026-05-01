<?php
// =============================================
// POST /mobile_api/posts/delete_comment.php
// Delete own comment
// Body: comment_id
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];
$comment_id = (int)($_POST['comment_id'] ?? 0);

if (!$comment_id) {
    send_error("comment_id is required.");
}

$check = $conn->query("
    SELECT account_id FROM comments WHERE comment_id=$comment_id
")->fetch_assoc();

if (!$check) {
    send_error("Comment not found.", 404);
}

if ($check['account_id'] != $account_id) {
    send_error("You can only delete your own comments.", 403);
}

$conn->query("DELETE FROM comments WHERE comment_id=$comment_id");
send_success(null, "Comment deleted.");
