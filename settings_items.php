<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION["user_id"];

// --- Handle Delete Item ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["delete_item"])) {
    $qr_id = intval($_POST["qr_id"]);
    $verify = $conn->prepare("SELECT * FROM qr_items WHERE id = ? AND user_id = ?");
    $verify->bind_param("ii", $qr_id, $current_user_id);
    $verify->execute();
    $qr_item = $verify->get_result()->fetch_assoc();

    if ($qr_item) {
        // Delete image file if exists
        if (!empty($qr_item['item_image']) && file_exists($qr_item['item_image'])) {
            unlink($qr_item['item_image']);
        }
        $del = $conn->prepare("DELETE FROM qr_items WHERE id = ?");
        $del->bind_param("i", $qr_id);
        $del->execute();
        $_SESSION["settings_success"] = "Item deleted successfully.";
    }
    header("Location: settings_items.php");
    exit();
}

// --- Fetch user's QR items ---
$stmt = $conn->prepare("SELECT * FROM qr_items WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $current_user_id);
$stmt->execute();
$qr_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Active item for detail view
$active_id   = intval($_GET["item"] ?? 0);
$active_item = null;
foreach ($qr_items as $q) {
    if ($q["id"] == $active_id) {
        $active_item = $q;
        break;
    }
}
if (!$active_item && !empty($qr_items)) {
    $active_item = $qr_items[0];
}

$success_msg = $_SESSION["settings_success"] ?? "";
unset($_SESSION["settings_success"]);

// Build scan URL for QR generation
$scan_url = "";
if ($active_item) {
    $scan_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/scan.php?code=" . $active_item['unique_code'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Your Items - ReClaimQR</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/settings.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
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

        <!-- Success Alert -->
        <?php if (!empty($success_msg)): ?>
            <div class="settings-alert-success">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <div class="settings-layout">

            <!-- Left: Settings Menu -->
            <div class="settings-sidebar">
                <div class="settings-sidebar-header">
                    <h2>Settings</h2>
                </div>
                <div class="settings-menu">
                    <a href="settings_items.php"
                       class="settings-menu-item active">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        Your Item
                    </a>
                    <a href="settings_personal.php"
                       class="settings-menu-item">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                             stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        Your Personal
                    </a>
                </div>
            </div>

            <!-- Middle: Items Grid -->
            <div class="settings-items-panel">
                <?php if (empty($qr_items)): ?>
                    <div class="settings-empty">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        <p>No items registered yet.</p>
                        <a href="generate_qr.php" class="settings-generate-btn">Generate QR Now</a>
                    </div>
                <?php else: ?>
                    <div class="settings-items-grid">
                        <?php foreach ($qr_items as $qi): ?>
                            <a href="settings_items.php?item=<?php echo $qi['id']; ?>"
                               class="settings-item-card <?php echo ($active_item && $active_item['id'] == $qi['id']) ? 'active' : ''; ?>">
                                <div class="settings-item-img">
                                    <?php if (!empty($qi['item_image'])): ?>
                                        <img src="<?php echo htmlspecialchars($qi['item_image']); ?>"
                                             alt="<?php echo htmlspecialchars($qi['item_name']); ?>">
                                    <?php else: ?>
                                        <div class="settings-item-no-img">
                                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                                                <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                <circle cx="8.5" cy="8.5" r="1.5"/>
                                                <polyline points="21,15 16,10 5,21"/>
                                            </svg>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="settings-item-info">
                                    <h4><?php echo htmlspecialchars($qi['item_name']); ?></h4>
                                    <span>
                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        </svg>
                                        <?php echo htmlspecialchars($qi['item_category']); ?>
                                    </span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right: Item Detail + QR -->
            <div class="settings-detail-panel">
                <?php if ($active_item): ?>

                    <div class="settings-detail-header">
                        <h3>Item's Details</h3>
                    </div>

                    <div class="settings-detail-body">

                        <!-- Image + QR side by side -->
                        <div class="settings-img-qr-row">
                            <div class="settings-detail-img">
                                <?php if (!empty($active_item['item_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($active_item['item_image']); ?>"
                                         alt="<?php echo htmlspecialchars($active_item['item_name']); ?>">
                                <?php else: ?>
                                    <div class="settings-detail-no-img">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                                            <circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21,15 16,10 5,21"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="settings-qr-box">
                                <div id="settingsQrCode"></div>
                            </div>
                        </div>

                        <!-- Info Grid -->
                        <div class="settings-info-grid">
                            <div class="settings-info-block">
                                <span class="settings-info-label">Name :</span>
                                <div class="settings-info-value"><?php echo htmlspecialchars($active_item['owner_name']); ?></div>
                            </div>
                            <div class="settings-info-block">
                                <span class="settings-info-label">Item's Name :</span>
                                <div class="settings-info-value"><?php echo htmlspecialchars($active_item['item_name']); ?></div>
                            </div>
                            <div class="settings-info-block">
                                <span class="settings-info-label">Faculty/Organisation :</span>
                                <div class="settings-info-value"><?php echo htmlspecialchars($active_item['owner_faculty']); ?></div>
                            </div>
                            <div class="settings-info-block">
                                <span class="settings-info-label">Item's Category :</span>
                                <div class="settings-info-value"><?php echo htmlspecialchars($active_item['item_category']); ?></div>
                            </div>
                            <div class="settings-info-block">
                                <span class="settings-info-label">Phone No. :</span>
                                <div class="settings-info-value"><?php echo htmlspecialchars($active_item['owner_phone']); ?></div>
                            </div>
                            <div class="settings-info-block">
                                <span class="settings-info-label">Item's Description :</span>
                                <div class="settings-info-value"><?php echo htmlspecialchars($active_item['item_description']); ?></div>
                            </div>
                            <div class="settings-info-block">
                                <span class="settings-info-label">Email :</span>
                                <div class="settings-info-value"><?php echo htmlspecialchars($active_item['owner_email']); ?></div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="settings-action-btns">
                            <button onclick="downloadSettingsQR()" class="settings-btn settings-btn-download">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Download QR
                            </button>

                            <form method="POST" action="" style="display:inline;"
                                  onsubmit="return confirm('Are you sure you want to delete this item? This cannot be undone.')">
                                <input type="hidden" name="qr_id" value="<?php echo $active_item['id']; ?>">
                                <button type="submit" name="delete_item" class="settings-btn settings-btn-delete">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="3 6 5 6 21 6"/>
                                        <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/>
                                        <path d="M10 11v6"/><path d="M14 11v6"/>
                                        <path d="M9 6V4h6v2"/>
                                    </svg>
                                    Delete Item
                                </button>
                            </form>
                        </div>

                    </div>

                <?php else: ?>
                    <div class="settings-no-selection">
                        <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#d0d4ff" stroke-width="1.5">
                            <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                            <path d="M21 16h-3v3"/><path d="M21 21v.01"/>
                        </svg>
                        <h3>Select an item</h3>
                        <p>Click on an item to view its details and QR code</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
<?php if ($active_item && $scan_url): ?>
window.addEventListener('load', function () {
    new QRCode(document.getElementById("settingsQrCode"), {
        text: "<?php echo addslashes($scan_url); ?>",
        width: 130,
        height: 130,
        colorDark: "#1e1e2e",
        colorLight: "#ffffff",
        correctLevel: QRCode.CorrectLevel.H
    });
});

function downloadSettingsQR() {
    setTimeout(() => {
        const canvas = document.querySelector('#settingsQrCode canvas');
        if (canvas) {
            const link = document.createElement('a');
            link.download = 'ReclaimQR_<?php echo $active_item['unique_code']; ?>.png';
            link.href = canvas.toDataURL('image/png');
            link.click();
        }
    }, 500);
}
<?php endif; ?>

// Auto dismiss success alert
const alertEl = document.querySelector('.settings-alert-success');
if (alertEl) {
    setTimeout(() => {
        alertEl.style.opacity    = '0';
        alertEl.style.transition = 'opacity 0.5s';
        setTimeout(() => alertEl.remove(), 500);
    }, 4000);
}
</script>

</body>
</html>