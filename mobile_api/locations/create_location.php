<?php
// =============================================
// POST /mobile_api/locations/create_location.php
// Add a geo location marker
// Body: latitude, longitude, geo_type, description,
//       image_1 (file, optional), image_2 (file, optional)
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$latitude    = (float)($_POST['latitude'] ?? 0);
$longitude   = (float)($_POST['longitude'] ?? 0);
$geo_type    = $conn->real_escape_string($_POST['geo_type'] ?? 'POINT');
$description = $conn->real_escape_string(trim($_POST['description'] ?? ''));

if (empty($description)) {
    send_error("description is required.");
}

$valid_types = ['ADDRESS', 'POINT'];
if (!in_array($geo_type, $valid_types)) {
    $geo_type = 'POINT';
}

$image1 = $image2 = "";

if (!empty($_FILES['image_1']['name'])) {
    $image1 = time() . "_1_" . basename($_FILES['image_1']['name']);
    move_uploaded_file($_FILES['image_1']['tmp_name'], __DIR__ . "/../../uploads/" . $image1);
}

if (!empty($_FILES['image_2']['name'])) {
    $image2 = time() . "_2_" . basename($_FILES['image_2']['name']);
    move_uploaded_file($_FILES['image_2']['tmp_name'], __DIR__ . "/../../uploads/" . $image2);
}

$conn->query("
    INSERT INTO geo_locations (account_id, latitude, longitude, geo_type, description, image_1, image_2)
    VALUES ('$account_id','$latitude','$longitude','$geo_type','$description','$image1','$image2')
");

send_success(["geo_id" => $conn->insert_id], "Location added.");
