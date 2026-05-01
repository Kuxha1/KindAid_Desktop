<?php
// =============================================
// POST /mobile_api/posts/add_comment.php
// Add a comment to a post
// Body: post_id, comment_text
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$post_id      = (int)($_POST['post_id'] ?? 0);
$comment_text = $conn->real_escape_string(trim($_POST['comment_text'] ?? ''));

if (!$post_id || empty($comment_text)) {
    send_error("post_id and comment_text are required.");
}

$conn->query("
    INSERT INTO comments (post_id, account_id, comment_text)
    VALUES ('$post_id','$account_id','$comment_text')
");

$comment_id = $conn->insert_id;

send_success(["comment_id" => $comment_id], "Comment added.");
