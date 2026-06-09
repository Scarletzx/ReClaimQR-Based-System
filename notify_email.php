<?php
session_start();
require_once "config/db.php";

// Load PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once "PHPMailer/src/Exception.php";
require_once "PHPMailer/src/PHPMailer.php";
require_once "PHPMailer/src/SMTP.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user_id   = $_SESSION["user_id"];
$current_user_name = $_SESSION["fullname"] ?? "A user";

$item_id   = intval($_GET["item_id"] ?? 0);
$item_type = $_GET["item_type"] ?? "Lost";
$to_email  = trim($_GET["to"] ?? "");

if ($item_id <= 0 || empty($to_email)) {
    header("Location: dashboard.php");
    exit();
}

// Fetch item details
$table  = ($item_type === "Found") ? "items_found" : "items";
$i_stmt = $conn->prepare("SELECT item_name, category, location, description FROM $table WHERE id = ?");
$i_stmt->bind_param("i", $item_id);
$i_stmt->execute();
$item = $i_stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: dashboard.php");
    exit();
}

$sent  = false;
$error = "";

// --- Send email using PHPMailer + Gmail SMTP ---
$mail = new PHPMailer(true);

try {
    // ============================================
    // SMTP CONFIGURATION — update these settings
    // ============================================
    $mail->isSMTP();
    $mail->Host       = 'mail.priorisys.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'helpdesk@reclaim.priorisys.com';    // ← your Gmail address
    $mail->Password   = 'Wafi020708!';    // ← your 16-char App Password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Sender & Recipient
    $mail->setFrom('helpdesk@reclaim.priorisys.com', 'ReClaimQR System');
    $mail->addAddress($to_email);

    // Email content
    $mail->isHTML(true);
    $mail->Subject = 'ReClaimQR - Someone Found Your Item: ' . $item['item_name'];
    $mail->Body    = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 30px; background: #f8f9ff; border-radius: 16px;'>
        <div style='text-align: center; margin-bottom: 24px;'>
            <h1 style='color: #7c83fd; font-size: 24px; margin: 0;'>ReClaimQR</h1>
            <p style='color: #888; font-size: 13px; margin-top: 4px;'>Lost & Found System</p>
        </div>

        <div style='background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px;'>
            <h2 style='color: #1e1e2e; font-size: 18px; margin-bottom: 16px;'>📦 Item Found Notification</h2>
            <p style='color: #555; font-size: 14px; line-height: 1.6;'>
                Hello! <strong>{$current_user_name}</strong> found an item on ReClaimQR that may belong to you.
            </p>
        </div>

        <div style='background: white; border-radius: 12px; padding: 24px; margin-bottom: 20px;'>
            <h3 style='color: #7c83fd; font-size: 15px; margin-bottom: 14px;'>Item Details</h3>
            <table style='width: 100%; border-collapse: collapse;'>
                <tr>
                    <td style='padding: 8px 0; color: #888; font-size: 13px; width: 120px;'>Item Name</td>
                    <td style='padding: 8px 0; color: #1e1e2e; font-size: 13px; font-weight: bold;'>{$item['item_name']}</td>
                </tr>
                <tr style='border-top: 1px solid #f0f2ff;'>
                    <td style='padding: 8px 0; color: #888; font-size: 13px;'>Category</td>
                    <td style='padding: 8px 0; color: #1e1e2e; font-size: 13px; font-weight: bold;'>{$item['category']}</td>
                </tr>
                <tr style='border-top: 1px solid #f0f2ff;'>
                    <td style='padding: 8px 0; color: #888; font-size: 13px;'>Location</td>
                    <td style='padding: 8px 0; color: #1e1e2e; font-size: 13px; font-weight: bold;'>{$item['location']}</td>
                </tr>
                <tr style='border-top: 1px solid #f0f2ff;'>
                    <td style='padding: 8px 0; color: #888; font-size: 13px;'>Description</td>
                    <td style='padding: 8px 0; color: #1e1e2e; font-size: 13px; font-weight: bold;'>{$item['description']}</td>
                </tr>
                <tr style='border-top: 1px solid #f0f2ff;'>
                    <td style='padding: 8px 0; color: #888; font-size: 13px;'>Status</td>
                    <td style='padding: 8px 0;'>
                        <span style='background: #7c83fd; color: white; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;'>{$item_type}</span>
                    </td>
                </tr>
            </table>
        </div>

        <div style='text-align: center; margin-bottom: 20px;'>
            <a href='https://reclaim.priorisys.com/login.php'
               style='display: inline-block; padding: 13px 32px; background: #7c83fd; color: white;
                      border-radius: 25px; text-decoration: none; font-weight: bold; font-size: 14px;'>
                Login to ReClaimQR
            </a>
        </div>

        <p style='color: #bbb; font-size: 12px; text-align: center;'>
            This is an automated notification from ReClaimQR. Please do not reply to this email.
        </p>
    </div>
    ";

    $mail->AltBody = "Hello! {$current_user_name} found an item on ReClaimQR that may belong to you.\n\nItem: {$item['item_name']}\nCategory: {$item['category']}\nLocation: {$item['location']}\nDescription: {$item['description']}\n\nPlease login to ReClaimQR to coordinate the return.";

    $mail->send();
    $sent = true;

} catch (Exception $e) {
    $error = "Email could not be sent. Error: {$mail->ErrorInfo}";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Notification - ReClaimQR</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/items_detail.css">
</head>
<body>
<div class="app-wrapper">
    <?php include "includes/navbar.php"; ?>
    <div class="main-content">
        <div class="topbar">
            <div></div>
            <div class="topbar-right">
                <div class="user-avatar">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none"
                         stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
            </div>
        </div>
        <div class="page-body">
            <div class="notify-result-wrap">
                <div class="notify-result-card">
                    <?php if ($sent): ?>
                        <div class="notify-result-icon success">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                                 stroke="#2ecc71" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <h2>Email Sent!</h2>
                        <p>A notification email has been sent to
                           <strong><?php echo htmlspecialchars($to_email); ?></strong>
                           about <strong><?php echo htmlspecialchars($item['item_name']); ?></strong>.
                        </p>
                    <?php else: ?>
                        <div class="notify-result-icon error">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none"
                                 stroke="#ff4d4d" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <h2>Email Failed</h2>
                        <p><?php echo htmlspecialchars($error); ?></p>
                    <?php endif; ?>
                    <a href="item_details.php?id=<?php echo $item_id; ?>&type=<?php echo $item_type; ?>"
                       class="notify-back-btn">
                        ← Back to Item Details
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>