<?php
// =============================================
// POST /mobile_api/posts/create_post.php
// Create a new community post
// Body: description, image_1 (file, optional), image_2 (file, optional)
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$description = $conn->real_escape_string(trim($_POST['description'] ?? ''));

if (empty($description)) {
    send_error("Post description cannot be empty.");
}

$image1 = "";
$image2 = "";

if (!empty($_FILES['image_1']['name'])) {
    $image1 = time() . "_1_" . basename($_FILES['image_1']['name']);
    move_uploaded_file($_FILES['image_1']['tmp_name'], __DIR__ . "/../../uploads/" . $image1);
}

if (!empty($_FILES['image_2']['name'])) {
    $image2 = time() . "_2_" . basename($_FILES['image_2']['name']);
    move_uploaded_file($_FILES['image_2']['tmp_name'], __DIR__ . "/../../uploads/" . $image2);
}

$conn->query("
    INSERT INTO community_forum (account_id, description, image_1, image_2)
    VALUES ('$account_id','$description','$image1','$image2')
");

$post_id = $conn->insert_id;

send_success(["post_id" => $post_id], "Post created successfully.");
