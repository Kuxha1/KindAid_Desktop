<?php
// =============================================
// POST /mobile_api/profile/upload_photo.php
// Upload or update profile photo
// Body: profile_photo (file)
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

if (empty($_FILES['profile_photo']['name'])) {
    send_error("No photo file provided.");
}

$allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif', 'image/webp'];
$fileType     = mime_content_type($_FILES['profile_photo']['tmp_name']);

if (!in_array($fileType, $allowedTypes)) {
    send_error("Invalid file type. Only JPEG, PNG, GIF, WEBP allowed.");
}

$filename = time() . "_" . $account_id . "_" . basename($_FILES['profile_photo']['name']);
$dest     = __DIR__ . "/../../uploads/" . $filename;

if (!move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
    send_error("Failed to upload file. Check server permissions.", 500);
}

$safe = $conn->real_escape_string($filename);
$conn->query("UPDATE accounts SET profile_photo='$safe' WHERE account_id=$account_id");

send_success(
    ["photo_url" => UPLOADS_URL . $filename],
    "Profile photo updated."
);
