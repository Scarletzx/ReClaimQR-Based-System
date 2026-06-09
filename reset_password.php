<?php
session_start();
require_once "config/db.php";

// Redirect if already logged in
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$token     = trim($_GET["token"] ?? "");
$email     = trim($_GET["email"] ?? "");
$token_hash = hash("sha256", $token);

$page_state   = "form";   // "form" | "invalid" | "expired" | "success"
$errors       = [];
$user         = null;

// -------------------------------------------------------
// Validate token on page load
// -------------------------------------------------------
if (empty($token) || empty($email)) {
    $page_state = "invalid";
} else {
    $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ?");
    $stmt->bind_param("ss", $email, $token_hash);
    $stmt->execute();
    $reset = $stmt->get_result()->fetch_assoc();

    if (!$reset) {
        $page_state = "invalid";
    } elseif (strtotime($reset["expires_at"]) < time()) {
        $page_state = "expired";
        // Clean up expired token
        $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $del->bind_param("s", $email);
        $del->execute();
    } else {
        // Token valid — fetch user
        $u_stmt = $conn->prepare("SELECT id, fullname FROM users WHERE email = ?");
        $u_stmt->bind_param("s", $email);
        $u_stmt->execute();
        $user = $u_stmt->get_result()->fetch_assoc();

        if (!$user) {
            $page_state = "invalid";
        }
    }
}

// -------------------------------------------------------
// Handle POST: Save new password
// -------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && $page_state === "form") {
    $new_pass  = $_POST["new_password"] ?? "";
    $conf_pass = $_POST["confirm_password"] ?? "";

    if (empty($new_pass) || empty($conf_pass)) {
        $errors[] = "Please fill in both password fields.";
    } elseif ($new_pass !== $conf_pass) {
        $errors[] = "Passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=<>?]).{8,}$/', $new_pass)) {
        $errors[] = "Password must be at least 8 characters and include uppercase, lowercase, a number, and a special character.";
    } else {
        // Re-validate token on POST too (prevent replay)
        $check = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ?");
        $check->bind_param("ss", $email, $token_hash);
        $check->execute();
        $reset_check = $check->get_result()->fetch_assoc();

        if (!$reset_check || strtotime($reset_check["expires_at"]) < time()) {
            $page_state = "expired";
        } else {
            // Update password
            $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
            $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $upd->bind_param("ss", $hashed, $email);
            $upd->execute();

            // Delete used token
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param("s", $email);
            $del->execute();

            $page_state = "success";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - ReClaimQR</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/forgot_password.css">
</head>
<body>
<div class="container">

    <!-- LEFT: Content Side -->
    <div class="login-box">

        <!-- ===== INVALID TOKEN ===== -->
        <?php if ($page_state === "invalid"): ?>
            <div class="fp-state-card">
                <div class="fp-state-icon fp-state-error">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="15" y1="9" x2="9" y2="15"/>
                        <line x1="9" y1="9" x2="15" y2="15"/>
                    </svg>
                </div>
                <h2>Invalid Reset Link</h2>
                <p>This password reset link is invalid or has already been used. Please request a new one.</p>
                <a href="forgot_password.php" class="fp-submit-btn" style="display:inline-block;text-align:center;text-decoration:none;margin-top:8px;">Request New Link</a>
                <a href="login.php" class="fp-back-link-btn">Back to Login</a>
            </div>

        <!-- ===== EXPIRED TOKEN ===== -->
        <?php elseif ($page_state === "expired"): ?>
            <div class="fp-state-card">
                <div class="fp-state-icon fp-state-warning">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                </div>
                <h2>Link Expired</h2>
                <p>Your password reset link has expired. Reset links are only valid for <strong>1 hour</strong>. Please request a new one.</p>
                <a href="forgot_password.php" class="fp-submit-btn" style="display:inline-block;text-align:center;text-decoration:none;margin-top:8px;">Request New Link</a>
                <a href="login.php" class="fp-back-link-btn">Back to Login</a>
            </div>

        <!-- ===== SUCCESS ===== -->
        <?php elseif ($page_state === "success"): ?>
            <div class="fp-state-card">
                <div class="fp-state-icon fp-state-success">
                    <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <h2>Password Reset!</h2>
                <p>Your password has been successfully changed. You can now log in with your new password.</p>
                <a href="login.php" class="fp-submit-btn" style="display:inline-block;text-align:center;text-decoration:none;margin-top:8px;">Go to Login</a>
            </div>

        <!-- ===== FORM ===== -->
        <?php else: ?>
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
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                </svg>
            </div>

            <h2>Set New Password</h2>
            <p class="fp-subtitle">
                Hi <strong><?php echo htmlspecialchars($user['fullname']); ?></strong>!
                Choose a strong new password for your account.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="fp-alert fp-alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <?php echo htmlspecialchars($errors[0]); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <label>New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="new_password" id="newPassword"
                           placeholder="Enter new password" required>
                    <span class="toggle" onclick="togglePass('newPassword', this)">👁</span>
                </div>

                <label>Confirm New Password</label>
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confPassword"
                           placeholder="Re-enter new password" required>
                    <span class="toggle" onclick="togglePass('confPassword', this)">👁</span>
                </div>

                <!-- Password strength indicator -->
                <div class="fp-strength-wrap">
                    <div class="fp-strength-bar">
                        <div class="fp-strength-fill" id="strengthFill"></div>
                    </div>
                    <span class="fp-strength-label" id="strengthLabel">Enter a password</span>
                </div>

                <!-- Requirements checklist -->
                <ul class="fp-requirements">
                    <li id="req-length">At least 8 characters</li>
                    <li id="req-upper">One uppercase letter (A-Z)</li>
                    <li id="req-lower">One lowercase letter (a-z)</li>
                    <li id="req-number">One number (0-9)</li>
                    <li id="req-special">One special character (!@#$%...)</li>
                </ul>

                <button type="submit" class="fp-submit-btn">Reset Password</button>
            </form>
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

<script>
function togglePass(inputId, btn) {
    const input = document.getElementById(inputId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    btn.textContent = input.type === 'password' ? '👁' : '🙈';
}

// -------------------------------------------------------
// Live password strength checker
// -------------------------------------------------------
const newPassInput = document.getElementById('newPassword');
if (newPassInput) {
    newPassInput.addEventListener('input', function () {
        const val     = this.value;
        const fill    = document.getElementById('strengthFill');
        const label   = document.getElementById('strengthLabel');

        const checks = {
            'req-length':  val.length >= 8,
            'req-upper':   /[A-Z]/.test(val),
            'req-lower':   /[a-z]/.test(val),
            'req-number':  /[0-9]/.test(val),
            'req-special': /[!@#$%^&*()\-_+=<>?]/.test(val)
        };

        let score = 0;
        for (const [id, passed] of Object.entries(checks)) {
            const el = document.getElementById(id);
            if (passed) {
                el.classList.add('passed');
                score++;
            } else {
                el.classList.remove('passed');
            }
        }

        const pct = (score / 5) * 100;
        fill.style.width = pct + '%';

        if (score <= 1)      { fill.style.background = '#ff4d4d'; label.textContent = 'Very Weak'; label.style.color = '#ff4d4d'; }
        else if (score === 2){ fill.style.background = '#ff944d'; label.textContent = 'Weak';      label.style.color = '#ff944d'; }
        else if (score === 3){ fill.style.background = '#ffc107'; label.textContent = 'Fair';      label.style.color = '#ffc107'; }
        else if (score === 4){ fill.style.background = '#7bc67e'; label.textContent = 'Good';      label.style.color = '#7bc67e'; }
        else                 { fill.style.background = '#27ae60'; label.textContent = 'Strong ✓';  label.style.color = '#27ae60'; }
    });
}
</script>
</body>
</html>
