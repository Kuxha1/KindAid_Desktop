<?php
session_start();
include "admin/config/db.php";

// If already logged in, redirect to dashboard
if (isset($_SESSION['account_id'])) {
    header("Location: index.php");
    exit();
}

$step = 'email'; // email → otp → reset
$email = '';
$error = '';
$success = '';

// ── Step 1: Send OTP ──────────────────────────────────
if (isset($_POST['send_otp'])) {

    $email = $conn->real_escape_string(trim($_POST['email']));

    // Check if email exists
    $result = $conn->query("SELECT account_id, name FROM accounts WHERE email='$email' LIMIT 1");

    if ($result->num_rows === 0) {
        $error = "No account found with this email address.";
        $step = 'email';
    } else {
        $user = $result->fetch_assoc();
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires = date('Y-m-d H:i:s', time() + 600); // 10 minutes

        $conn->query("UPDATE accounts SET reset_otp='$otp', otp_expires_at='$expires' WHERE email='$email'");

        // ── Send OTP via PHPMailer (same credentials as mobile) ──
        require_once "mobile_api/phpmailer/PHPMailer.php";
        require_once "mobile_api/phpmailer/SMTP.php";
        require_once "mobile_api/phpmailer/Exception.php";

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'your-email-address@gmail.com';// Gmail address
            $mail->Password = 'YOUR_APP_PASSWORD_HERE';// ← paste 16-char App Password here
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('your-email-address@gmail.com', 'KindAid'); //App Name
            $mail->addAddress($email, $user['name']);

            $mail->isHTML(true);
            $mail->Subject = "KindAid - Password Reset Code";
            $mail->Body = "
            <div style='font-family: Inter, Arial, sans-serif; max-width: 500px; margin: auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 16px;'>
                <div style='text-align: center; margin-bottom: 20px;'>
                    <h1 style='color: #610C9F; margin: 0; font-size: 24px;'>KindAid</h1>
                    <p style='color: #64748b; font-size: 14px;'>Password Reset Request</p>
                </div>
                <p style='color: #334155; font-size: 15px;'>Hello <strong>{$user['name']}</strong>,</p>
                <p style='color: #334155; font-size: 15px;'>Your verification code is:</p>
                <div style='text-align: center; margin: 24px 0;'>
                    <div style='display: inline-block; background: linear-gradient(135deg, #610C9F, #8C3FD4); color: white; font-size: 32px; font-weight: 800; letter-spacing: 8px; padding: 16px 32px; border-radius: 12px;'>
                        $otp
                    </div>
                </div>
                <p style='color: #64748b; font-size: 13px; text-align: center;'>This code expires in <strong>10 minutes</strong>.</p>
                <hr style='border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;'>
                <p style='color: #94a3b8; font-size: 12px; text-align: center;'>If you didn't request this, please ignore this email.</p>
            </div>
            ";
            $mail->AltBody = "Your KindAid password reset OTP is: $otp (Expires in 10 minutes)";

            $mail->send();

            $step = 'otp';
            $success = "A 6-digit verification code has been sent to your email.";

        } catch (\Exception $e) {
            $error = "Failed to send email. Please try again later.";
            $step = 'email';
        }
    }
}

// ── Step 2: Verify OTP ────────────────────────────────
if (isset($_POST['verify_otp'])) {

    $email = $conn->real_escape_string(trim($_POST['email']));
    $otp = $conn->real_escape_string(trim($_POST['otp']));

    $result = $conn->query("SELECT otp_expires_at FROM accounts WHERE email='$email' AND reset_otp='$otp' LIMIT 1");

    if ($result->num_rows === 0) {
        $error = "Invalid OTP or email. Please try again.";
        $step = 'otp';
    } else {
        $row = $result->fetch_assoc();
        if (time() > strtotime($row['otp_expires_at'])) {
            $error = "OTP has expired. Please request a new one.";
            $step = 'email';
        } else {
            $step = 'reset';
            $success = "OTP verified! Enter your new password.";
        }
    }
}

// ── Step 3: Reset Password ────────────────────────────
if (isset($_POST['reset_password'])) {

    $email = $conn->real_escape_string(trim($_POST['email']));
    $otp = $conn->real_escape_string(trim($_POST['otp']));
    $new_pass = $_POST['new_password'];
    $confirm = $_POST['confirm_password'];

    if ($new_pass !== $confirm) {
        $error = "Passwords do not match.";
        $step = 'reset';
    } elseif (strlen($new_pass) < 6) {
        $error = "Password must be at least 6 characters.";
        $step = 'reset';
    } else {
        // Verify OTP again for security
        $result = $conn->query("SELECT otp_expires_at FROM accounts WHERE email='$email' AND reset_otp='$otp' LIMIT 1");

        if ($result->num_rows === 0) {
            $error = "Invalid request. Please restart the process.";
            $step = 'email';
        } else {
            $row = $result->fetch_assoc();
            if (time() > strtotime($row['otp_expires_at'])) {
                $error = "OTP has expired. Please request a new one.";
                $step = 'email';
            } else {
                $hash = password_hash($new_pass, PASSWORD_BCRYPT);
                $conn->query("UPDATE accounts SET password='$hash', reset_otp=NULL, otp_expires_at=NULL WHERE email='$email'");
                $success = "Password reset successfully! You can now login.";
                $step = 'done';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>KindAid — Reset Password</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"
        rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="style/main-style.css" />
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: "#610C9F",
                        "primary-dark": "#4A0980",
                        accent: "#54C6EB"
                    },
                    fontFamily: {
                        display: ["Inter", "sans-serif"]
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .material-symbols-outlined {
            font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
            vertical-align: middle;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.6);
            box-shadow: 0 8px 32px rgba(97, 12, 159, 0.08), 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .otp-input {
            width: 48px;
            height: 56px;
            text-align: center;
            font-size: 24px;
            font-weight: 700;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            outline: none;
            transition: all 0.2s;
        }

        .otp-input:focus {
            border-color: #610C9F;
            box-shadow: 0 0 0 3px rgba(97, 12, 159, 0.12);
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 24px;
        }

        .step-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #e2e8f0;
            transition: all 0.3s;
        }

        .step-dot.active {
            background: #610C9F;
            transform: scale(1.2);
        }

        .step-dot.done {
            background: #22c55e;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(16px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-card {
            animation: fadeInUp 0.4s ease;
        }

        .btn-primary {
            background: linear-gradient(135deg, #610C9F, #8C3FD4);
            color: white;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 14px;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            width: 100%;
        }

        .btn-primary:hover {
            opacity: 0.9;
            transform: translateY(-1px);
            box-shadow: 0 4px 16px rgba(97, 12, 159, 0.25);
        }

        .input-field {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 14px;
            outline: none;
            transition: all 0.2s;
        }

        .input-field:focus {
            border-color: #610C9F;
            box-shadow: 0 0 0 3px rgba(97, 12, 159, 0.1);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-4"
    style="background: linear-gradient(135deg, #f0e6ff 0%, #e8f4fd 50%, #fce7f3 100%);">

    <div class="glass-card rounded-2xl p-8 w-full max-w-md animate-card">

        <!-- Logo -->
        <div class="text-center mb-6">
            <a href="login.php">
                <img src="assets/logo(4).png" class="h-12 mx-auto mb-3">
            </a>
            <h1 class="text-xl font-bold text-slate-900">Reset Password</h1>
            <p class="text-sm text-slate-500 mt-1">We'll help you get back into your account</p>
        </div>

        <!-- Step Indicator -->
        <div class="step-indicator">
            <div class="step-dot <?php echo ($step === 'email') ? 'active' : (($step !== 'email') ? 'done' : ''); ?>">
            </div>
            <div
                class="step-dot <?php echo ($step === 'otp') ? 'active' : (($step === 'reset' || $step === 'done') ? 'done' : ''); ?>">
            </div>
            <div class="step-dot <?php echo ($step === 'reset') ? 'active' : (($step === 'done') ? 'done' : ''); ?>">
            </div>
        </div>

        <!-- Error / Success Messages -->
        <?php if ($error) { ?>
            <div
                class="bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3 rounded-xl mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">error</span>
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php } ?>

        <?php if ($success) { ?>
            <div
                class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-xl mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-base">check_circle</span>
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php } ?>

        <!-- ═══ Step 1: Enter Email ═══ -->
        <?php if ($step === 'email') { ?>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Email Address</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">mail</span>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>"
                            placeholder="Enter your registered email" class="input-field pl-10">
                    </div>
                </div>

                <button type="submit" name="send_otp" class="btn-primary flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">send</span>
                    Send Verification Code
                </button>
            </form>
        <?php } ?>

        <!-- ═══ Step 2: Enter OTP ═══ -->
        <?php if ($step === 'otp') { ?>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <div class="text-center mb-5">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-primary to-purple-400 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="material-symbols-outlined text-white" style="font-size:28px;">mark_email_read</span>
                    </div>
                    <p class="text-sm text-slate-500">
                        Code sent to <strong><?php echo htmlspecialchars($email); ?></strong>
                    </p>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-3 text-center">Enter 6-digit code</label>
                    <div class="flex justify-center gap-2" id="otpContainer">
                        <input type="text" maxlength="1" class="otp-input" onkeyup="moveOtp(this, event)" autofocus>
                        <input type="text" maxlength="1" class="otp-input" onkeyup="moveOtp(this, event)">
                        <input type="text" maxlength="1" class="otp-input" onkeyup="moveOtp(this, event)">
                        <input type="text" maxlength="1" class="otp-input" onkeyup="moveOtp(this, event)">
                        <input type="text" maxlength="1" class="otp-input" onkeyup="moveOtp(this, event)">
                        <input type="text" maxlength="1" class="otp-input" onkeyup="moveOtp(this, event)">
                    </div>
                    <input type="hidden" name="otp" id="otpHidden">
                </div>

                <button type="submit" name="verify_otp" class="btn-primary" onclick="collectOtp()">
                    Verify Code
                </button>

                <p class="text-center mt-4 text-sm text-slate-500">
                    Didn't receive it?
                    <a href="forgot_password.php" class="text-primary font-semibold hover:underline">Resend</a>
                </p>
            </form>

            <script>
                function moveOtp(el, e) {
                    if (e.key === 'Backspace' && el.value === '' && el.previousElementSibling) {
                        el.previousElementSibling.focus();
                    } else if (el.value.length === 1 && el.nextElementSibling) {
                        el.nextElementSibling.focus();
                    }
                }
                function collectOtp() {
                    const inputs = document.querySelectorAll('#otpContainer input');
                    let otp = '';
                    inputs.forEach(i => otp += i.value);
                    document.getElementById('otpHidden').value = otp;
                }
                // Allow paste
                document.getElementById('otpContainer').addEventListener('paste', function (e) {
                    e.preventDefault();
                    const paste = (e.clipboardData || window.clipboardData).getData('text').trim();
                    const inputs = this.querySelectorAll('input');
                    for (let i = 0; i < Math.min(paste.length, inputs.length); i++) {
                        inputs[i].value = paste[i];
                    }
                    if (inputs[Math.min(paste.length, inputs.length) - 1]) {
                        inputs[Math.min(paste.length, inputs.length) - 1].focus();
                    }
                });
            </script>
        <?php } ?>

        <!-- ═══ Step 3: New Password ═══ -->
        <?php if ($step === 'reset') { ?>
            <form method="POST">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                <input type="hidden" name="otp" value="<?php echo htmlspecialchars($_POST['otp'] ?? ''); ?>">

                <div class="text-center mb-5">
                    <div
                        class="w-16 h-16 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-3">
                        <span class="material-symbols-outlined text-white" style="font-size:28px;">lock_reset</span>
                    </div>
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">New Password</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock</span>
                        <input type="password" name="new_password" required minlength="6" placeholder="Minimum 6 characters"
                            class="input-field pl-10">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-semibold text-slate-700 mb-2">Confirm Password</label>
                    <div class="relative">
                        <span
                            class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">lock_open</span>
                        <input type="password" name="confirm_password" required minlength="6"
                            placeholder="Re-enter your password" class="input-field pl-10">
                    </div>
                </div>

                <button type="submit" name="reset_password" class="btn-primary flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">check</span>
                    Reset Password
                </button>
            </form>
        <?php } ?>

        <!-- ═══ Step 4: Done ═══ -->
        <?php if ($step === 'done') { ?>
            <div class="text-center py-4">
                <div
                    class="w-20 h-20 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4">
                    <span class="material-symbols-outlined text-white" style="font-size:36px;">check_circle</span>
                </div>
                <h2 class="text-lg font-bold text-slate-900 mb-2">All Done!</h2>
                <p class="text-sm text-slate-500 mb-6">Your password has been reset successfully.</p>
                <a href="login.php" class="btn-primary inline-flex items-center justify-center gap-2">
                    <span class="material-symbols-outlined text-lg">login</span>
                    Go to Login
                </a>
            </div>
        <?php } ?>

        <!-- Back to Login -->
        <?php if ($step !== 'done') { ?>
            <p class="text-center mt-5 text-sm text-slate-500">
                Remember your password?
                <a href="login.php" class="text-primary font-semibold hover:underline">Login</a>
            </p>
        <?php } ?>

    </div>

</body>

</html>