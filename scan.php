<?php
require_once "config/db.php";

$code = trim($_GET['code'] ?? '');
$item = null;

if (!empty($code)) {
    $stmt = $conn->prepare("SELECT * FROM qr_items WHERE unique_code = ?");
    $stmt->bind_param("s", $code);
    $stmt->execute();
    $result = $stmt->get_result();
    $item   = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Item Found - ReClaimQR</title>
    <link rel="stylesheet" href="css/Qr_Code.css">
</head>
<body class="scan-page">
    <div class="scan-wrapper">
        <div class="scan-card">
            <div class="scan-logo">
                <svg width="32" height="32" viewBox="0 0 28 28" fill="none">
                    <rect x="2" y="2" width="10" height="10" rx="2" stroke="#7c83fd" stroke-width="2.5"/>
                    <rect x="16" y="2" width="10" height="10" rx="2" stroke="#7c83fd" stroke-width="2.5"/>
                    <rect x="2" y="16" width="10" height="10" rx="2" stroke="#7c83fd" stroke-width="2.5"/>
                    <rect x="18" y="18" width="2" height="2" fill="#7c83fd"/>
                    <rect x="22" y="18" width="2" height="2" fill="#7c83fd"/>
                    <rect x="18" y="22" width="2" height="2" fill="#7c83fd"/>
                    <rect x="22" y="22" width="2" height="2" fill="#7c83fd"/>
                    <rect x="20" y="20" width="2" height="2" fill="#7c83fd"/>
                </svg>
                <span>ReclaimQR</span>
            </div>

            <?php if ($item): ?>

                <!-- *** ADDED: Item image shown at top *** -->
                <?php if (!empty($item['item_image'])): ?>
                    <img src="<?php echo htmlspecialchars($item['item_image']); ?>"
                         alt="<?php echo htmlspecialchars($item['item_name']); ?>"
                         class="scan-item-image">
                <?php else: ?>
                    <div class="scan-item-no-image">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2"/>
                            <circle cx="8.5" cy="8.5" r="1.5"/>
                            <polyline points="21,15 16,10 5,21"/>
                        </svg>
                    </div>
                <?php endif; ?>

                <div class="scan-badge">Item Found</div>
                <h2 class="scan-item-name"><?php echo htmlspecialchars($item['item_name']); ?></h2>
                <p class="scan-category"><?php echo htmlspecialchars($item['item_category']); ?></p>

                <div class="scan-divider"></div>

                <h3 class="scan-section-title">Owner's Information</h3>
                <div class="scan-info-grid">
                    <div class="scan-info-row">
                        <span class="scan-info-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                            Name
                        </span>
                        <span class="scan-info-value"><?php echo htmlspecialchars($item['owner_name']); ?></span>
                    </div>
                    <div class="scan-info-row">
                        <span class="scan-info-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 5.55 5.55l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/></svg>
                            Phone
                        </span>
                        <span class="scan-info-value"><?php echo htmlspecialchars($item['owner_phone']); ?></span>
                    </div>
                    <div class="scan-info-row">
                        <span class="scan-info-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                            Faculty
                        </span>
                        <span class="scan-info-value"><?php echo htmlspecialchars($item['owner_faculty']); ?></span>
                    </div>
                    <div class="scan-info-row">
                        <span class="scan-info-label">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                            Email
                        </span>
                        <span class="scan-info-value"><?php echo htmlspecialchars($item['owner_email']); ?></span>
                    </div>
                </div>

                <div class="scan-divider"></div>

                <h3 class="scan-section-title">Item's Description</h3>
                <p class="scan-description"><?php echo htmlspecialchars($item['item_description']); ?></p>

                <div class="scan-contact-btns">
                    <a href="tel:<?php echo htmlspecialchars($item['owner_phone']); ?>" class="scan-call-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.18 2 2 0 0 1 3.6 1h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.6a16 16 0 0 0 5.55 5.55l.96-.96a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 21 16.92z"/></svg>
                        Call Owner
                    </a>
                    <a href="mailto:<?php echo htmlspecialchars($item['owner_email']); ?>" class="scan-email-btn">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                        Email Owner
                    </a>
                </div>

            <?php else: ?>
                <div class="scan-not-found">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ddd" stroke-width="1.5">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <h2>QR Code Not Found</h2>
                    <p>This QR code is invalid or has been removed.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>