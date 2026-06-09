<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION["user_id"];
$errors   = [];
$successes = [];

// --- Fetch current user data ---
$user_stmt = $conn->prepare("SELECT id, fullname, email, password, phone FROM users WHERE id = ?");
$user_stmt->bind_param("i", $current_user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

// -------------------------------------------------------
// POST: Update Name
// -------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_name"])) {
    $new_name = trim($_POST["fullname"] ?? "");
    if (empty($new_name)) {
        $errors["name"] = "Name cannot be empty.";
    } elseif (strlen($new_name) < 2) {
        $errors["name"] = "Name must be at least 2 characters.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET fullname = ? WHERE id = ?");
        $stmt->bind_param("si", $new_name, $current_user_id);
        if ($stmt->execute()) {
            $_SESSION["fullname"] = $new_name;
            $successes["name"] = "Name updated successfully!";
            // Re-fetch user
            $user["fullname"] = $new_name;
        } else {
            $errors["name"] = "Something went wrong. Try again.";
        }
    }
}

// -------------------------------------------------------
// POST: Update Password
// -------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_password"])) {
    $old_pass  = $_POST["old_password"] ?? "";
    $new_pass  = trim($_POST["new_password"] ?? "");
    $conf_pass = trim($_POST["confirm_password"] ?? "");

    if (empty($old_pass) || empty($new_pass) || empty($conf_pass)) {
        $errors["password"] = "Please fill in all password fields.";
    } elseif (!password_verify($old_pass, $user["password"])) {
        $errors["password"] = "Your current password is incorrect.";
    } elseif ($new_pass !== $conf_pass) {
        $errors["password"] = "New passwords do not match.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=<>?]).{8,}$/', $new_pass)) {
        $errors["password"] = "Password must be 8+ chars with upper, lower, number & special character.";
    } else {
        $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed, $current_user_id);
        if ($stmt->execute()) {
            $successes["password"] = "Password changed successfully!";
            $user["password"] = $hashed;
        } else {
            $errors["password"] = "Something went wrong. Try again.";
        }
    }
}

// -------------------------------------------------------
// POST: Update Phone
// -------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["update_phone"])) {
    $new_phone = trim($_POST["phone"] ?? "");
    if (empty($new_phone)) {
        $errors["phone"] = "Phone number cannot be empty.";
    } elseif (!preg_match('/^[0-9\-\+\s]{8,15}$/', $new_phone)) {
        $errors["phone"] = "Please enter a valid phone number.";
    } else {
        $stmt = $conn->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $stmt->bind_param("si", $new_phone, $current_user_id);
        if ($stmt->execute()) {
            $successes["phone"] = "Phone number updated successfully!";
            $user["phone"] = $new_phone;
        } else {
            $errors["phone"] = "Something went wrong. Try again.";
        }
    }
}

// -------------------------------------------------------
// POST: Logout
// -------------------------------------------------------
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["logout"])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Mask email: show first 5 chars then ****
function maskEmail($email) {
    $at = strpos($email, '@');
    if ($at === false) return str_repeat('*', strlen($email));
    $local = substr($email, 0, $at);
    $domain = substr($email, $at);
    $visible = substr($local, 0, min(5, strlen($local)));
    $masked = $visible . str_repeat('*', max(0, strlen($local) - strlen($visible)));
    return $masked . $domain;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Your Personal - ReClaimQR</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/settings.css">
    <link rel="stylesheet" href="css/settings_personal.css">
</head>
<body>
<div class="app-wrapper">
    <?php include "includes/navbar.php"; ?>

    <div class="main-content">

        <!-- Topbar -->
        <div class="topbar">
            <div></div>
            <div class="topbar-right">
                <a href="settings_personal.php" class="user-avatar" title="<?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
            </div>
        </div>

        <div class="settings-layout">

            <!-- LEFT: Settings Sidebar -->
            <div class="settings-sidebar">
                <div class="settings-sidebar-header">
                    <h2>Settings</h2>
                </div>
                <div class="settings-menu">
                    <a href="settings_items.php" class="settings-menu-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        Your Item
                    </a>
                    <a href="settings_personal.php" class="settings-menu-item active">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Your Personal
                    </a>
                </div>
            </div>

            <!-- RIGHT: Personal Detail Panel -->
            <div class="sp-panel">

                <div class="sp-panel-header">
                    <h3>Your Personal</h3>
                </div>

                <div class="sp-body">

                    <!-- Avatar -->
                    <div class="sp-avatar-wrap">
                        <div class="sp-avatar">
                            <svg width="64" height="64" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 12c2.7 0 4.8-2.1 4.8-4.8S14.7 2.4 12 2.4 7.2 4.5 7.2 7.2 9.3 12 12 12zm0 2.4c-3.2 0-9.6 1.6-9.6 4.8v2.4h19.2v-2.4c0-3.2-6.4-4.8-9.6-4.8z"/>
                            </svg>
                        </div>
                    </div>

                    <!-- Info Card -->
                    <div class="sp-card">

                        <!-- ===== NAME ===== -->
                        <div class="sp-field-group <?php echo isset($errors['name']) ? 'has-error' : (isset($successes['name']) ? 'has-success' : ''); ?>">
                            <label class="sp-label">Name :</label>
                            <div class="sp-field-row">
                                <div class="sp-input-wrap">
                                    <input type="text"
                                           id="nameInput"
                                           class="sp-input"
                                           value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>"
                                           placeholder="Enter your name"
                                           readonly>
                                </div>
                                <button type="button" class="sp-icon-btn" id="nameEditBtn" title="Edit name"
                                        onclick="openModal('nameModal')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                            </div>
                            <?php if (isset($errors['name'])): ?>
                                <span class="sp-msg sp-msg-error"><?php echo htmlspecialchars($errors['name']); ?></span>
                            <?php elseif (isset($successes['name'])): ?>
                                <span class="sp-msg sp-msg-success"><?php echo htmlspecialchars($successes['name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- ===== EMAIL ===== -->
                        <div class="sp-field-group">
                            <label class="sp-label">Email :</label>
                            <div class="sp-field-row">
                                <div class="sp-input-wrap">
                                    <input type="text"
                                           id="emailInput"
                                           class="sp-input"
                                           value="<?php echo htmlspecialchars(maskEmail($user['email'] ?? '')); ?>"
                                           data-real="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                           data-masked="<?php echo htmlspecialchars(maskEmail($user['email'] ?? '')); ?>"
                                           readonly>
                                </div>
                                <button type="button" class="sp-icon-btn sp-eye-btn" id="emailEyeBtn"
                                        title="Toggle email visibility"
                                        onclick="toggleEmailVisibility()">
                                    <!-- Eye closed (default) -->
                                    <svg id="emailEyeOff" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                    <!-- Eye open (hidden by default) -->
                                    <svg id="emailEyeOn" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                            </div>
                            <span class="sp-note">Email address cannot be changed.</span>
                        </div>

                        <!-- ===== PASSWORD ===== -->
                        <div class="sp-field-group <?php echo isset($errors['password']) ? 'has-error' : (isset($successes['password']) ? 'has-success' : ''); ?>">
                            <label class="sp-label">Password :</label>
                            <div class="sp-field-row">
                                <div class="sp-input-wrap">
                                    <input type="text"
                                           id="passwordInput"
                                           class="sp-input sp-password-dots"
                                           value="<?php echo htmlspecialchars($user['password'] ?? ''); ?>"
                                           data-masked="true"
                                           readonly>
                                </div>
                                <button type="button" class="sp-icon-btn sp-eye-btn" title="Toggle password visibility"
                                        onclick="togglePasswordVisibility()">
                                    <svg id="passEyeOff" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                                        <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                                        <line x1="1" y1="1" x2="23" y2="23"/>
                                    </svg>
                                    <svg id="passEyeOn" width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="display:none;">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </button>
                                <button type="button" class="sp-icon-btn" title="Change password"
                                        onclick="openModal('passwordModal')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                            </div>
                            <?php if (isset($errors['password'])): ?>
                                <span class="sp-msg sp-msg-error"><?php echo htmlspecialchars($errors['password']); ?></span>
                            <?php elseif (isset($successes['password'])): ?>
                                <span class="sp-msg sp-msg-success"><?php echo htmlspecialchars($successes['password']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- ===== PHONE ===== -->
                        <div class="sp-field-group <?php echo isset($errors['phone']) ? 'has-error' : (isset($successes['phone']) ? 'has-success' : ''); ?>">
                            <label class="sp-label">Phone No. :</label>
                            <div class="sp-field-row">
                                <div class="sp-input-wrap">
                                    <input type="text"
                                           id="phoneInput"
                                           class="sp-input"
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                           placeholder="e.g. 01X-XXXXXXX"
                                           readonly>
                                </div>
                                <button type="button" class="sp-icon-btn" title="Edit phone number"
                                        onclick="openModal('phoneModal')">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                    </svg>
                                </button>
                            </div>
                            <?php if (isset($errors['phone'])): ?>
                                <span class="sp-msg sp-msg-error"><?php echo htmlspecialchars($errors['phone']); ?></span>
                            <?php elseif (isset($successes['phone'])): ?>
                                <span class="sp-msg sp-msg-success"><?php echo htmlspecialchars($successes['phone']); ?></span>
                            <?php endif; ?>
                        </div>

                        <!-- ===== LOG OUT ===== -->
                        <div class="sp-logout-wrap">
                            <form method="POST" action="">
                                <button type="submit" name="logout"
                                        class="sp-logout-btn"
                                        onclick="return confirm('Are you sure you want to log out?')">
                                    Log Out
                                </button>
                            </form>
                        </div>

                    </div><!-- end sp-card -->
                </div><!-- end sp-body -->
            </div><!-- end sp-panel -->

        </div><!-- end settings-layout -->
    </div><!-- end main-content -->
</div><!-- end app-wrapper -->


<!-- ============================================================
     MODAL: Edit Name
     ============================================================ -->
<div class="sp-modal-overlay" id="nameModal">
    <div class="sp-modal">
        <div class="sp-modal-header">
            <h3>Edit Name</h3>
            <button class="sp-modal-close" onclick="closeModal('nameModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="">
            <div class="sp-modal-body">
                <label class="sp-modal-label">New Name</label>
                <input type="text" name="fullname" class="sp-modal-input"
                       value="<?php echo htmlspecialchars($user['fullname'] ?? ''); ?>"
                       placeholder="Enter your full name" required>
                <p class="sp-modal-note">This name will be visible to others in the messaging system.</p>
            </div>
            <div class="sp-modal-footer">
                <button type="button" class="sp-modal-btn sp-modal-btn-cancel" onclick="closeModal('nameModal')">Cancel</button>
                <button type="submit" name="update_name" class="sp-modal-btn sp-modal-btn-save">Save Name</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: Change Password
     ============================================================ -->
<div class="sp-modal-overlay" id="passwordModal">
    <div class="sp-modal">
        <div class="sp-modal-header">
            <h3>Change Password</h3>
            <button class="sp-modal-close" onclick="closeModal('passwordModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="" id="changePasswordForm">
            <div class="sp-modal-body">

                <label class="sp-modal-label">Current Password</label>
                <div class="sp-modal-pass-wrap">
                    <input type="password" name="old_password" id="oldPassInput"
                           class="sp-modal-input" placeholder="Enter current password" required>
                    <button type="button" class="sp-modal-eye" onclick="toggleModalPass('oldPassInput', this)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>

                <label class="sp-modal-label" style="margin-top:14px;">New Password</label>
                <div class="sp-modal-pass-wrap">
                    <input type="password" name="new_password" id="newPassInput"
                           class="sp-modal-input" placeholder="Enter new password" required>
                    <button type="button" class="sp-modal-eye" onclick="toggleModalPass('newPassInput', this)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>

                <label class="sp-modal-label" style="margin-top:14px;">Confirm New Password</label>
                <div class="sp-modal-pass-wrap">
                    <input type="password" name="confirm_password" id="confPassInput"
                           class="sp-modal-input" placeholder="Re-enter new password" required>
                    <button type="button" class="sp-modal-eye" onclick="toggleModalPass('confPassInput', this)">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/>
                            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/>
                            <line x1="1" y1="1" x2="23" y2="23"/>
                        </svg>
                    </button>
                </div>

                <div class="sp-modal-rules">
                    <p>Password requirements:</p>
                    <ul>
                        <li>Minimum 8 characters</li>
                        <li>At least one uppercase (A-Z)</li>
                        <li>At least one lowercase (a-z)</li>
                        <li>At least one number (0-9)</li>
                        <li>At least one special character (!@#$% etc.)</li>
                    </ul>
                </div>
            </div>
            <div class="sp-modal-footer">
                <button type="button" class="sp-modal-btn sp-modal-btn-cancel" onclick="closeModal('passwordModal')">Cancel</button>
                <button type="submit" name="update_password" class="sp-modal-btn sp-modal-btn-save">Save Password</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     MODAL: Edit Phone
     ============================================================ -->
<div class="sp-modal-overlay" id="phoneModal">
    <div class="sp-modal">
        <div class="sp-modal-header">
            <h3>Edit Phone Number</h3>
            <button class="sp-modal-close" onclick="closeModal('phoneModal')">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>
        <form method="POST" action="">
            <div class="sp-modal-body">
                <label class="sp-modal-label">Phone Number</label>
                <input type="text" name="phone" class="sp-modal-input"
                       value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                       placeholder="e.g. 01X-XXXXXXX" required>
                <p class="sp-modal-note">Format: 01X-XXXXXXX or +601XXXXXXXXX</p>
            </div>
            <div class="sp-modal-footer">
                <button type="button" class="sp-modal-btn sp-modal-btn-cancel" onclick="closeModal('phoneModal')">Cancel</button>
                <button type="submit" name="update_phone" class="sp-modal-btn sp-modal-btn-save">Save Phone</button>
            </div>
        </form>
    </div>
</div>


<script>
// -------------------------------------------------------
// Modal helpers
// -------------------------------------------------------
function openModal(id) {
    document.getElementById(id).classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
    document.body.style.overflow = '';
}
// Close on backdrop click
document.querySelectorAll('.sp-modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) closeModal(this.id);
    });
});
// Close on Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.sp-modal-overlay.active').forEach(m => closeModal(m.id));
    }
});

// -------------------------------------------------------
// Email visibility toggle
// -------------------------------------------------------
let emailVisible = false;
function toggleEmailVisibility() {
    const input   = document.getElementById('emailInput');
    const eyeOff  = document.getElementById('emailEyeOff');
    const eyeOn   = document.getElementById('emailEyeOn');
    emailVisible  = !emailVisible;
    input.value   = emailVisible ? input.dataset.real : input.dataset.masked;
    eyeOff.style.display = emailVisible ? 'none' : '';
    eyeOn.style.display  = emailVisible ? '' : 'none';
}

// -------------------------------------------------------
// Password visibility toggle (main display)
// -------------------------------------------------------
let passVisible = false;
const PASS_PLACEHOLDER = '••••••••••••••';
const passInputEl = document.getElementById('passwordInput');

// Show dots initially (never show hash to user)
passInputEl.value = PASS_PLACEHOLDER;
passInputEl.classList.add('sp-password-dots');

function togglePasswordVisibility() {
    const eyeOff = document.getElementById('passEyeOff');
    const eyeOn  = document.getElementById('passEyeOn');
    passVisible  = !passVisible;

    if (passVisible) {
        // Reveal actual password via AJAX
        fetch('settings_personal.php?get_pass=1')
            .then(r => r.json())
            .then(d => {
                if (d.pass) {
                    passInputEl.value = d.pass;
                    passInputEl.classList.remove('sp-password-dots');
                }
            });
        eyeOff.style.display = 'none';
        eyeOn.style.display  = '';
    } else {
        passInputEl.value = PASS_PLACEHOLDER;
        passInputEl.classList.add('sp-password-dots');
        eyeOff.style.display = '';
        eyeOn.style.display  = 'none';
    }
}

// -------------------------------------------------------
// Toggle show/hide inside password modal
// -------------------------------------------------------
function toggleModalPass(inputId, btn) {
    const input = document.getElementById(inputId);
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    // Swap icon
    const svgEyeOff = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`;
    const svgEyeOn  = `<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
    btn.innerHTML = isHidden ? svgEyeOn : svgEyeOff;
}

// -------------------------------------------------------
// Auto-open modal if there was an error on POST
// -------------------------------------------------------
<?php if (isset($errors['name'])): ?>
    window.addEventListener('load', () => openModal('nameModal'));
<?php elseif (isset($errors['password'])): ?>
    window.addEventListener('load', () => openModal('passwordModal'));
<?php elseif (isset($errors['phone'])): ?>
    window.addEventListener('load', () => openModal('phoneModal'));
<?php endif; ?>
</script>

<?php
// -------------------------------------------------------
// AJAX: Return plain-text password for "view" feature
// Note: We store the hash; we reveal the hash string itself.
// -------------------------------------------------------
if (isset($_GET['get_pass']) && isset($_SESSION['user_id'])) {
    // We can only show the hash since passwords are hashed.
    // Instead, store raw password encrypted in session on login — but that is
    // a security risk. As a safe alternative: show a note to user.
    // Here we return the hashed value truncated for UX (not real reveal).
    // For a proper "reveal", the real password must be stored — which is bad practice.
    // We'll return a message to inform user instead.
    header('Content-Type: application/json');
    echo json_encode(['pass' => '[Passwords are hashed for security — use Change Password to update it]']);
    exit();
}
?>

</body>
</html>