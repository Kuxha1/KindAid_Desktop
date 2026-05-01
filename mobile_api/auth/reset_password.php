<?php
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";

$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';
$new_pass = $_POST['new_password'] ?? '';

if (empty($email) || empty($otp) || empty($new_pass)) {
    send_error("All fields are required.");
}

$email = $conn->real_escape_string($email);
$otp = $conn->real_escape_string($otp);

// Verify OTP again
$result = $conn->query("SELECT otp_expires_at FROM accounts WHERE email='$email' AND reset_otp='$otp' LIMIT 1");

if ($result->num_rows === 0) {
    send_error("Invalid request or OTP.");
}

$row = $result->fetch_assoc();
if (time() > strtotime($row['otp_expires_at'])) {
    send_error("OTP has expired.");
}

$hash = password_hash($new_pass, PASSWORD_BCRYPT);
$update = $conn->query("UPDATE accounts SET password='$hash', reset_otp=NULL, otp_expires_at=NULL WHERE email='$email'");

if ($update) {
    send_success(null, "Your password has been reset successfully. You can now login.");
} else {
    send_error("Failed to reset password. Please try again.");
}
