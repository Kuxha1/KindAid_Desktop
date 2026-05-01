<?php
// =============================================
// POST /mobile_api/auth/login.php
// Authenticate and return JWT token
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../helpers/auth.php";
require_once __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$email    = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    send_error("Email and password are required.");
}

$email_safe = $conn->real_escape_string($email);

$result = $conn->query("
    SELECT account_id, name, email, password, account_type,
           profile_photo, verified_status, bio
    FROM accounts
    WHERE email='$email_safe'
    LIMIT 1
");

if ($result->num_rows === 0) {
    send_error("No account found with this email.", 404);
}

$account = $result->fetch_assoc();

if (!password_verify($password, $account['password'])) {
    send_error("Incorrect password.", 401);
}

// Generate JWT
$token = generate_jwt($account['account_id'], $account['email']);

// Build profile photo URL
$photo = !empty($account['profile_photo'])
    ? UPLOADS_URL . $account['profile_photo']
    : BASE_URL . "assets/default-user.png";

send_success([
    "token" => $token,
    "user"  => [
        "account_id"      => $account['account_id'],
        "name"            => $account['name'],
        "email"           => $account['email'],
        "account_type"    => $account['account_type'],
        "verified_status" => $account['verified_status'],
        "bio"             => $account['bio'],
        "profile_photo"   => $photo,
    ]
], "Login successful.");
