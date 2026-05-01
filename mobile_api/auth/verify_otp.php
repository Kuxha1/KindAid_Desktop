<?php
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";

$email = $_POST['email'] ?? '';
$otp = $_POST['otp'] ?? '';

if (empty($email) || empty($otp)) {
    send_error("Email and OTP are required.");
}

$email = $conn->real_escape_string($email);
$otp = $conn->real_escape_string($otp);

$result = $conn->query("SELECT otp_expires_at FROM accounts WHERE email='$email' AND reset_otp='$otp' LIMIT 1");

if ($result->num_rows === 0) {
    send_error("Invalid OTP or incorrect email.");
}

$row = $result->fetch_assoc();
$expires_at = strtotime($row['otp_expires_at']);

if (time() > $expires_at) {
    send_error("OTP has expired. Please request a new one.");
}

send_success(null, "OTP verified successfully.");
