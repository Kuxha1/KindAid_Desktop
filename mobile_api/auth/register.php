<?php
// =============================================
// POST /mobile_api/auth/register.php
// Register a new User or NGO
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

// Required fields
$name         = trim($_POST['name'] ?? '');
$email        = trim($_POST['email'] ?? '');
$password     = $_POST['password'] ?? '';
$account_type = trim($_POST['account_type'] ?? 'User');
$contact_no   = trim($_POST['contact_no'] ?? '');
$address      = trim($_POST['address'] ?? '');

// Validate
if (empty($name) || empty($email) || empty($password)) {
    send_error("Name, email and password are required.");
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    send_error("Invalid email format.");
}

if (strlen($password) < 6) {
    send_error("Password must be at least 6 characters.");
}

if (!in_array($account_type, ['User', 'NGO'])) {
    $account_type = 'User';
}

// Check if email already exists
$email_safe = $conn->real_escape_string($email);
$exists = $conn->query("SELECT account_id FROM accounts WHERE email='$email_safe'");
if ($exists->num_rows > 0) {
    send_error("An account with this email already exists.");
}

// Hash password
$hashed = password_hash($password, PASSWORD_BCRYPT);

// Insert
$name_safe    = $conn->real_escape_string($name);
$contact_safe = $conn->real_escape_string($contact_no);
$address_safe = $conn->real_escape_string($address);

$conn->query("
    INSERT INTO accounts (account_type, name, email, contact_no, password, address)
    VALUES ('$account_type','$name_safe','$email_safe','$contact_safe','$hashed','$address_safe')
");

$new_id = $conn->insert_id;

send_success(
    ["account_id" => $new_id],
    "Account created successfully."
);
