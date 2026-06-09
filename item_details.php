<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION["user_id"];

// Get item id and type from URL
$item_id   = intval($_GET["id"] ?? 0);
$item_type = $_GET["type"] ?? "Lost";

if ($item_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

// Fetch item from correct table
$table    = ($item_type === "Found") ? "items_found" : "items";
$date_col = ($item_type === "Found") ? "date_found" : "date_missing";
$time_col = ($item_type === "Found") ? "time_found" : "time_missing";

$stmt = $conn->prepare("
    SELECT i.*, u.fullname, u.email, i.contact_info, i.$date_col AS item_date, i.$time_col AS item_time
    FROM $table i
    JOIN users u ON u.id = i.user_id
    WHERE i.id = ?
");
$stmt->bind_param("i", $item_id);
$stmt->execute();
$item = $stmt->get_result()->fetch_assoc();

if (!$item) {
    header("Location: dashboard.php");
    exit();
}

$is_owner       = ($item["user_id"] == $current_user_id);
$reporter_id    = $item["user_id"];
$reporter_name  = $item["fullname"];
$reporter_email = $item["email"];
$reporter_phone = $item["contact_info"];

// Extract WhatsApp number (digits only)
$wa_number = preg_replace('/[^0-9]/', '', $reporter_phone);
// Convert Malaysian local number 01X to 601X
if (substr($wa_number, 0, 1) === '0') {
    $wa_number = '6' . $wa_number;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Details - ReClaimQR</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/items_detail.css">
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

        <div class="page-body">

            <!-- Item Details Card -->
            <div class="detail-overlay">
                <div class="detail-card">

                    <!-- Header -->
                    <div class="detail-header">
                        <h2>Item's Details</h2>
                        <a href="dashboard.php" class="detail-close">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </a>
                    </div>

                    <!-- Item Image -->
                    <div class="detail-image-wrap">
                        <?php if (!empty($item['image'])): ?>
                            <img src="<?php echo htmlspecialchars($item['image']); ?>"
                                 alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                        <?php else: ?>
                            <div class="detail-no-image">
                                <svg width="60" height="60" viewBox="0 0 24 24" fill="none"
                                     stroke="#ccc" stroke-width="1.5">
                                    <rect x="3" y="3" width="18" height="18" rx="2"/>
                                    <circle cx="8.5" cy="8.5" r="1.5"/>
                                    <polyline points="21,15 16,10 5,21"/>
                                </svg>
                                <p>No photo available</p>
                            </div>
                        <?php endif; ?>
                        <span class="detail-badge badge-<?php echo strtolower($item_type); ?>">
                            <?php echo $item_type; ?>
                        </span>
                    </div>

                    <!-- Item Info Grid -->
                    <div class="detail-info-grid">
                        <div class="detail-info-block">
                            <span class="detail-info-label">Item's Name :</span>
                            <div class="detail-info-value"><?php echo htmlspecialchars($item['item_name']); ?></div>
                        </div>
                        <div class="detail-info-block">
                            <span class="detail-info-label">Item's Location :</span>
                            <div class="detail-info-value"><?php echo htmlspecialchars($item['location']); ?></div>
                        </div>
                        <div class="detail-info-block">
                            <span class="detail-info-label">Item's Category :</span>
                            <div class="detail-info-value"><?php echo htmlspecialchars($item['category']); ?></div>
                        </div>
                        <div class="detail-info-block">
                            <span class="detail-info-label">Date :</span>
                            <div class="detail-info-value">
                                <?php echo date("d F Y", strtotime($item['item_date'])); ?>
                            </div>
                        </div>
                        <div class="detail-info-block">
                            <span class="detail-info-label">Description :</span>
                            <div class="detail-info-value"><?php echo htmlspecialchars($item['description']); ?></div>
                        </div>
                        <div class="detail-info-block">
                            <span class="detail-info-label">Time :</span>
                            <div class="detail-info-value">
                                <?php echo date("g:i A", strtotime($item['item_time'])); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <?php if (!$is_owner): ?>
                        <div class="detail-actions">
                            <!-- *** UPDATED: Message Button now triggers claim *** -->
                            <a href="message.php?chat=<?php echo $reporter_id; ?>&item_id=<?php echo $item_id; ?>&item_type=<?php echo $item_type; ?>"
                               onclick="triggerClaim()"
                               class="detail-btn detail-btn-message">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                                </svg>
                                Message
                            </a>

                            <!-- *** UPDATED: Notify Button now triggers claim too *** -->
                            <button class="detail-btn detail-btn-notify"
                                    onclick="triggerClaimThenNotify()">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                                    <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                </svg>
                                Notify
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="detail-owner-note">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                            This is your reported item.
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Notify Modal -->
            <?php if (!$is_owner): ?>
            <div class="notify-modal-overlay" id="notifyModal">
                <div class="notify-modal">
                    <div class="notify-modal-header">
                        <h3>Send Notification</h3>
                        <button class="notify-close" onclick="closeNotifyModal()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2.5"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <line x1="18" y1="6" x2="6" y2="18"/>
                                <line x1="6" y1="6" x2="18" y2="18"/>
                            </svg>
                        </button>
                    </div>
                    <p class="notify-modal-sub">
                        Choose how you want to notify
                        <strong><?php echo htmlspecialchars($reporter_name); ?></strong>
                        about this item.
                    </p>
                    <div class="notify-options">

                        <!-- Email Notify -->
                        <a href="notify_email.php?item_id=<?php echo $item_id; ?>&item_type=<?php echo $item_type; ?>&to=<?php echo urlencode($reporter_email); ?>"
                           class="notify-option notify-option-email">
                            <div class="notify-option-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
                                    <polyline points="22,6 12,13 2,6"/>
                                </svg>
                            </div>
                            <div class="notify-option-info">
                                <span class="notify-option-title">Email Notification</span>
                                <span class="notify-option-desc"><?php echo htmlspecialchars($reporter_email); ?></span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>

                        <!-- WhatsApp Notify -->
                        <a href="notify_whatsapp.php?item_id=<?php echo $item_id; ?>&item_type=<?php echo $item_type; ?>&phone=<?php echo urlencode($wa_number); ?>"
                           class="notify-option notify-option-whatsapp">
                            <div class="notify-option-icon">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                                     stroke="currentColor" stroke-width="2"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                                </svg>
                            </div>
                            <div class="notify-option-info">
                                <span class="notify-option-title">WhatsApp Notification</span>
                                <span class="notify-option-desc"><?php echo htmlspecialchars($reporter_phone); ?></span>
                            </div>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none"
                                 stroke="currentColor" stroke-width="2"
                                 stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 18 15 12 9 6"/>
                            </svg>
                        </a>

                    </div>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// *** ADDED: Trigger claim functions ***
function triggerClaim() {
    fetch('claimed_items.php?ajax_trigger=1&item_id=<?php echo $item_id; ?>&item_type=<?php echo $item_type; ?>&owner_id=<?php echo $reporter_id; ?>', {
        method: 'GET'
    });
}

function triggerClaimThenNotify() {
    triggerClaim();
    openNotifyModal();
}

// Existing modal functions — unchanged
function openNotifyModal() {
    document.getElementById('notifyModal').classList.add('active');
}
function closeNotifyModal() {
    document.getElementById('notifyModal').classList.remove('active');
}
document.getElementById('notifyModal') &&
document.getElementById('notifyModal').addEventListener('click', function(e) {
    if (e.target === this) closeNotifyModal();
});
</script>
</body>
</html>