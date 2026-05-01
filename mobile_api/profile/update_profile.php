<?php
// =============================================
// POST /mobile_api/profile/update_profile.php
// Multipart: name, email, bio, address, contact_no
// Files: profile_photo, document_1, document_2
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

$name       = $conn->real_escape_string(trim($_POST['name'] ?? ''));
$email      = $conn->real_escape_string(trim($_POST['email'] ?? ''));
$bio        = $conn->real_escape_string(trim($_POST['bio'] ?? ''));
$address    = $conn->real_escape_string(trim($_POST['address'] ?? ''));
$contact_no = $conn->real_escape_string(trim($_POST['contact_no'] ?? ''));

if (empty($name)) {
    send_error("Name cannot be empty.");
}

// Get current values for files
$current = $conn->query("SELECT profile_photo, document_1, document_2 FROM accounts WHERE account_id=$account_id")->fetch_assoc();
$profile_photo = $current['profile_photo'];
$doc1 = $current['document_1'];
$doc2 = $current['document_2'];

// Profile photo upload
if (!empty($_FILES['profile_photo']['name'])) {
    $profile_photo = time() . "_" . basename($_FILES['profile_photo']['name']);
    move_uploaded_file($_FILES['profile_photo']['tmp_name'], __DIR__ . "/../../uploads/" . $profile_photo);
}

// Document 1 upload
if (!empty($_FILES['document_1']['name'])) {
    $doc1 = time() . "_" . basename($_FILES['document_1']['name']);
    move_uploaded_file($_FILES['document_1']['tmp_name'], __DIR__ . "/../../uploads/doc/" . $doc1);
}

// Document 2 upload
if (!empty($_FILES['document_2']['name'])) {
    $doc2 = time() . "_" . basename($_FILES['document_2']['name']);
    move_uploaded_file($_FILES['document_2']['tmp_name'], __DIR__ . "/../../uploads/doc/" . $doc2);
}

$conn->query("
    UPDATE accounts SET
        name='$name',
        email='$email',
        bio='$bio',
        address='$address',
        contact_no='$contact_no',
        profile_photo='$profile_photo',
        document_1='$doc1',
        document_2='$doc2'
    WHERE account_id=$account_id
");

send_success(null, "Profile updated successfully.");
