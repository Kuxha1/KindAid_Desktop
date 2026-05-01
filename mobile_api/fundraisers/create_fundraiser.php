<?php
// =============================================
// POST /mobile_api/fundraisers/create_fundraiser.php
// Create a fundraiser
// Body: title, description, goal_amount,
//       image_1, image_2 (files), document_1, document_2 (files)
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$title       = $conn->real_escape_string(trim($_POST['title'] ?? ''));
$description = $conn->real_escape_string(trim($_POST['description'] ?? ''));
$goal_amount = (float)($_POST['goal_amount'] ?? 0);

if (empty($title) || empty($description) || $goal_amount <= 0) {
    send_error("title, description and goal_amount are required.");
}

$uploadsDir = __DIR__ . "/../../uploads/";
$docsDir    = __DIR__ . "/../../uploads/doc/";

$image1 = $image2 = $doc1 = $doc2 = "";

if (!empty($_FILES['image_1']['name'])) {
    $image1 = time() . "_1_" . basename($_FILES['image_1']['name']);
    move_uploaded_file($_FILES['image_1']['tmp_name'], $uploadsDir . $image1);
}
if (!empty($_FILES['image_2']['name'])) {
    $image2 = time() . "_2_" . basename($_FILES['image_2']['name']);
    move_uploaded_file($_FILES['image_2']['tmp_name'], $uploadsDir . $image2);
}
if (!empty($_FILES['document_1']['name'])) {
    $doc1 = time() . "_doc1_" . basename($_FILES['document_1']['name']);
    move_uploaded_file($_FILES['document_1']['tmp_name'], $docsDir . $doc1);
}
if (!empty($_FILES['document_2']['name'])) {
    $doc2 = time() . "_doc2_" . basename($_FILES['document_2']['name']);
    move_uploaded_file($_FILES['document_2']['tmp_name'], $docsDir . $doc2);
}

$conn->query("
    INSERT INTO fundraiser (account_id, title, description, goal_amount, image_1, image_2, document_1, document_2)
    VALUES ('$account_id','$title','$description','$goal_amount','$image1','$image2','$doc1','$doc2')
");

send_success(["fundraiser_id" => $conn->insert_id], "Fundraiser created.");
