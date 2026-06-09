<?php
session_start();
require_once "config/db.php";

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);
    $confirmPassword = trim($_POST["confirm_password"]);

    if (empty($email) || empty($password) || empty($confirmPassword)) {
        $error = "Please fill in all fields.";
    } 
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    }
    elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    }
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=<>?]).{8,}$/', $password)) {
        $error = "Password does not meet security requirements.";
    }
    else {

        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            $error = "Email already registered.";
        } 
        else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
            $stmt->bind_param("ss", $email, $hashedPassword);

            if ($stmt->execute()) {
                $success = "Registration successful! You can now login.";
            } else {
                $error = "Something went wrong.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register - ReClaimQR</title>
    <link rel="stylesheet" href="css/register.css">
</head>
<body>

<div class="container">

    <div class="register-box">
        <h2>Sign Up</h2>

        <?php if (!empty($error)) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <?php if (!empty($success)) { ?>
            <div class="success"><?php echo $success; ?></div>
        <?php } ?>

        <form method="POST" onsubmit="return validatePassword()">

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Enter Your Password</label>
            <input type="password" name="password" id="password" required>

            <label>Re-enter Your Password</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <div class="password-rules">
                <ul>
                    <li>Minimum 8 characters</li>
                    <li>At least one uppercase (A-Z)</li>
                    <li>At least one lowercase (a-z)</li>
                    <li>At least one number (0-9)</li>
                    <li>At least one special character (!@#$% etc)</li>
                </ul>
            </div>

            <button type="submit">Sign Up</button>

            <p class="login-link">
                Already have an account?
                <a href="login.php">Login</a>
            </p>

        </form>
    </div>

    <div class="welcome-box">
    <h3>Welcome to</h3>

    <div class="brand-logo">
        <div class="brand-icon">
            <svg width="54" height="54" viewBox="0 0 28 28" fill="none">
                <rect x="2" y="2" width="10" height="10" rx="2" stroke="#7c83fd" stroke-width="2.5"/>
                <rect x="16" y="2" width="10" height="10" rx="2" stroke="#7c83fd" stroke-width="2.5"/>
                <rect x="2" y="16" width="10" height="10" rx="2" stroke="#7c83fd" stroke-width="2.5"/>
                <rect x="18" y="18" width="2" height="2" fill="#7c83fd"/>
                <rect x="22" y="18" width="2" height="2" fill="#7c83fd"/>
                <rect x="18" y="22" width="2" height="2" fill="#7c83fd"/>
                <rect x="22" y="22" width="2" height="2" fill="#7c83fd"/>
                <rect x="20" y="20" width="2" height="2" fill="#7c83fd"/>
            </svg>
        </div>
        <div class="brand-name">
            <span class="brand-re">Re</span><span class="brand-claim">Claim</span><span class="brand-qr">QR</span>
        </div>
    </div>

    <p class="tagline">Scan. Find. Claim</p>
</div>

</div>

<script>
function validatePassword() {

    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm_password").value;

    const regex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_\-+=<>?]).{8,}$/;

    if (!regex.test(password)) {
        alert("Password does not meet security requirements.");
        return false;
    }

    if (password !== confirmPassword) {
        alert("Passwords do not match.");
        return false;
    }

    return true;
}
</script>

</body>
</html>