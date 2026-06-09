<?php
session_start();
require_once "config/db.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {

        $stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            if (password_verify($password, $user["password"])) {

                $_SESSION["user_id"] = $user["id"];
                $_SESSION["fullname"] = $user["fullname"];

                header("Location: dashboard.php");
                exit();

            } else {
                $error = "Invalid email or password.";
            }

        } else {
            $error = "Invalid email or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login - ReClaimQR</title>
    <link rel="stylesheet" href="css/login.css">
    <link rel="stylesheet" href="css/forgot_password.css">
</head>
<body>

<div class="container">
    <div class="login-box">
        <h2>Log In</h2>

        <?php if (!empty($error)) { ?>
            <div class="error"><?php echo $error; ?></div>
        <?php } ?>

        <form method="POST" action="">
            <label>Email</label>
            <input type="email" name="email" required>

            <label>Password</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="password" required>
                <span class="toggle" onclick="togglePassword()">👁</span>
            </div>

            <a href="forgot_password.php" class="forgot">Forgot Password?</a>

            <p class="register-text">
                If you don’t have account yet register here:
                <a href="register.php">Sign Up</a>
            </p>

            <button type="submit">Log In</button>
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
function togglePassword() {
    var pass = document.getElementById("password");
    if (pass.type === "password") {
        pass.type = "text";
    } else {
        pass.type = "password";
    }
}
</script>

</body>
</html>