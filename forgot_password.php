<?php
session_start();
require_once "config/db.php";

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once "PHPMailer/src/Exception.php";
require_once "PHPMailer/src/PHPMailer.php";
require_once "PHPMailer/src/SMTP.php";

$message      = "";
$message_type = ""; // "success" or "error"

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");

    if (empty($email)) {
        $message      = "Please enter your email address.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message      = "Please enter a valid email address.";
        $message_type = "error";
    } else {
        // Check if email exists in DB
        $stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Always show success message even if email not found (security best practice)
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Generate secure token
            $token     = bin2hex(random_bytes(32)); // 64-char hex token
            $token_hash = hash("sha256", $token);   // store hashed version
            $expires_at = date("Y-m-d H:i:s", time() + 3600); // 1 hour expiry

            // Delete any old tokens for this email
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();

            // Store hashed token in DB
            $ins = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("sss", $email, $token_hash, $expires_at);
            $ins->execute();

            // Build reset link (using plain token, not hash)
            $protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
            $host       = $_SERVER['HTTP_HOST'];
            $script_dir = dirname($_SERVER['SCRIPT_NAME']);
            $reset_link = $protocol . "://" . $host . $script_dir . "/reset_password.php?token=" . urlencode($token) . "&email=" . urlencode($email);

            // Send email via PHPMailer
            $mail = new PHPMailer(true);
            try {
                   $mail->isSMTP();
            $mail->Host       = 'mail.priorisys.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'helpdesk@reclaim.priorisys.com';    // ← your Gmail address
            $mail->Password   = 'Wafi020708!';    // ← your 16-char App Password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
        
            // Sender & Recipient
            $mail->setFrom('helpdesk@reclaim.priorisys.com', 'ReClaimQR System');
            
                $mail->addAddress($email, $user['fullname']);

                $mail->isHTML(true);
                $mail->Subject = 'ReClaimQR - Reset Your Password';
                $mail->Body    = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #f8f9ff; border-radius: 16px;'>
                    <div style='text-align: center; margin-bottom: 28px;'>
                        <h1 style='color: #7c83fd; font-size: 26px; margin: 0;'>ReClaimQR</h1>
                        <p style='color: #888; font-size: 13px; margin-top: 4px;'>Lost &amp; Found System</p>
                    </div>

                    <div style='background: white; border-radius: 14px; padding: 28px; margin-bottom: 20px;'>
                        <h2 style='color: #1e1e2e; font-size: 18px; margin-bottom: 10px;'>🔐 Password Reset Request</h2>
                        <p style='color: #555; font-size: 14px; line-height: 1.7; margin-bottom: 6px;'>
                            Hi <strong>" . htmlspecialchars($user['fullname']) . "</strong>,
                        </p>
                        <p style='color: #555; font-size: 14px; line-height: 1.7;'>
                            We received a request to reset your password for your ReClaimQR account.
                            Click the button below to set a new password. This link will expire in <strong>1 hour</strong>.
                        </p>
                    </div>

                    <div style='text-align: center; margin: 28px 0;'>
                        <a href='" . $reset_link . "'
                           style='display: inline-block; padding: 14px 38px; background: #7c83fd; color: white;
                                  border-radius: 30px; text-decoration: none; font-weight: bold; font-size: 15px;
                                  box-shadow: 0 4px 14px rgba(124,131,253,0.35);'>
                            Reset My Password
                        </a>
                    </div>

                    <div style='background: white; border-radius: 14px; padding: 18px 24px; margin-bottom: 20px;'>
                        <p style='color: #888; font-size: 13px; margin: 0; line-height: 1.7;'>
                            If the button above doesn't work, copy and paste this link into your browser:
                        </p>
                        <p style='color: #7c83fd; font-size: 12px; word-break: break-all; margin-top: 8px;'>
                            " . $reset_link . "
                        </p>
                    </div>

                    <div style='background: #fff5f5; border-radius: 14px; padding: 16px 24px; margin-bottom: 20px; border-left: 4px solid #ff6b6b;'>
                        <p style='color: #e53e3e; font-size: 13px; margin: 0; line-height: 1.6;'>
                            ⚠️ If you did not request a password reset, please ignore this email.
                            Your password will remain unchanged.
                        </p>
                    </div>

                    <p style='color: #bbb; font-size: 12px; text-align: center; margin-top: 20px;'>
                        This is an automated email from ReClaimQR. Please do not reply.
                    </p>
                </div>
                ";
                $mail->AltBody = "Hi " . $user['fullname'] . ",\n\nReset your password using this link (expires in 1 hour):\n" . $reset_link . "\n\nIf you didn't request this, ignore this email.";

                $mail->send();

            } catch (Exception $e) {
                // Silently fail — don't reveal mail errors to user
                error_log("Password reset mail error: " . $mail->ErrorInfo);
            }
        }

        // Always show this — prevents email enumeration attack
        $message      = "If that email is registered, a password reset link has been sent. Please check your inbox (and spam folder).";
        $message_type = "success";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - ReClaimQR</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/forgot_password.css">
</head>
<body>
<div class="container">

    <!-- LEFT: Form Side -->
    <div class="login-box">
        <div class="fp-back-link">
            <a href="login.php">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="15 18 9 12 15 6"/>
                </svg>
                Back to Login
            </a>
        </div>

        <div class="fp-icon">
            <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
        </div>

        <h2>Forgot Password?</h2>
        <p class="fp-subtitle">No worries! Enter your registered email and we'll send you a reset link.</p>

        <?php if (!empty($message)): ?>
            <div class="fp-alert fp-alert-<?php echo $message_type; ?>">
                <?php if ($message_type === 'success'): ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                <?php else: ?>
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                <?php endif; ?>
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($message_type !== 'success'): ?>
        <form method="POST" action="">
            <label>Email Address</label>
            <input type="email" name="email"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                   placeholder="Enter your registered email"
                   required autofocus>

            <button type="submit" class="fp-submit-btn">Send Reset Link</button>
        </form>
        <?php else: ?>
            <div class="fp-success-actions">
                <a href="login.php" class="fp-back-btn">Back to Login</a>
                <p class="fp-resend-note">
                    Didn't receive it?
                    <a href="forgot_password.php">Try again</a>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- RIGHT: Welcome Side -->
    <div class="welcome-box">
        <h3>Welcome to</h3>
        <h1>ReClaimQR</h1>
        <div class="logo-placeholder"></div>
        <p>Scan. Find. Claim</p>
    </div>

</div>
</body>
</html>
