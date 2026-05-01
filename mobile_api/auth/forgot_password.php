<?php
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";

// ── PHPMailer ────────────────────────────────────────────────────────────────
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . "/../phpmailer/PHPMailer.php";
require_once __DIR__ . "/../phpmailer/SMTP.php";
require_once __DIR__ . "/../phpmailer/Exception.php";

// ── SMTP Configuration ───────────────────────────────────────────────────────
// Replace these with your real Gmail credentials.
// For Gmail: enable "2-Step Verification" and create an App Password at
// https://myaccount.google.com/apppasswords
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'bcawork13082004@gmail.com');   // Gmail address
define('SMTP_PASSWORD', 'YOUR_APP_PASSWORD_HERE'); // ← paste 16-char App Password here
define('SMTP_FROM', 'proton.0900@gmail.com');   // Gmail address
define('SMTP_FROM_NAME', 'KindAid');

// ── Validate input ───────────────────────────────────────────────────────────
$email = trim($_POST['email'] ?? '');

if (empty($email)) {
    send_error("Email address is required.");
}

$email = $conn->real_escape_string($email);
$result = $conn->query("SELECT account_id, name FROM accounts WHERE email='$email' LIMIT 1");

if ($result->num_rows === 0) {
    send_error("No account found with that email address.");
}

$user = $result->fetch_assoc();
$otp = sprintf("%06d", mt_rand(100000, 999999));
$expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

$update = $conn->query("UPDATE accounts SET reset_otp='$otp', otp_expires_at='$expires_at' WHERE email='$email'");

if (!$update) {
    send_error("Failed to generate OTP. Please try again.");
}

// ── Send OTP via PHPMailer / Gmail SMTP ─────────────────────────────────────
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = SMTP_HOST;
    $mail->SMTPAuth = true;
    $mail->Username = SMTP_USERNAME;
    $mail->Password = SMTP_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = SMTP_PORT;
    $mail->SMTPDebug = 0; // 0 = off, 2 = full debug

    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
    $mail->addAddress($email, $user['name']);

    $mail->isHTML(true);
    $mail->Subject = 'KindAid – Password Reset OTP';
    $mail->Body = "
        <div style='font-family:Arial,sans-serif;max-width:480px;margin:auto;padding:32px;
                     border:1px solid #e0e0e0;border-radius:12px;'>
            <h2 style='color:#2E7D32;margin-top:0;'>KindAid Password Reset</h2>
            <p>Hello <strong>{$user['name']}</strong>,</p>
            <p>We received a request to reset your KindAid password. 
               Use the code below to proceed:</p>
            <div style='font-size:36px;font-weight:bold;letter-spacing:10px;
                        text-align:center;color:#2E7D32;padding:16px;
                        background:#F1F8E9;border-radius:8px;margin:20px 0;'>
                $otp
            </div>
            <p>This code will expire in <strong>15 minutes</strong>.</p>
            <p>If you did not request a password reset, please ignore this email.</p>
            <hr style='border:none;border-top:1px solid #e0e0e0;margin:24px 0;'>
            <p style='font-size:12px;color:#9E9E9E;'>The KindAid Team</p>
        </div>
    ";
    $mail->AltBody =
        "Hello {$user['name']},\n\n" .
        "Your KindAid password reset OTP is: $otp\n\n" .
        "This code will expire in 15 minutes.\n\n" .
        "If you did not request a password reset, please ignore this email.\n\n" .
        "– The KindAid Team";

    $mail->send();
    send_success(null, "An OTP has been sent to your email. Please check your inbox.");

} catch (Exception $e) {
    // Roll back the OTP so the user can try again
    $conn->query("UPDATE accounts SET reset_otp=NULL, otp_expires_at=NULL WHERE email='$email'");
    send_error("Could not send the email. Please try again later. (Error: {$mail->ErrorInfo})");
}
