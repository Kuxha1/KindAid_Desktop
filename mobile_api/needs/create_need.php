<?php
// =============================================
// POST /mobile_api/needs/create_need.php
// Create a new need
// Body: goal_title, description, image_1 (file), image_2 (file)
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$goal_title  = $conn->real_escape_string(trim($_POST['goal_title'] ?? ''));
$description = $conn->real_escape_string(trim($_POST['description'] ?? ''));

if (empty($goal_title) || empty($description)) {
    send_error("goal_title and description are required.");
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
    INSERT INTO needs (account_id, goal_title, description, image_1, image_2)
    VALUES ('$account_id','$goal_title','$description','$image1','$image2')
");

send_success(["need_id" => $conn->insert_id], "Need created.");
