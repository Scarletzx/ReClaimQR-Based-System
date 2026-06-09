<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<div class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-icon">
            <svg width="26" height="26" viewBox="0 0 28 28" fill="none">
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
        <span class="logo-text">ReclaimQR</span>
    </div>

    <nav class="sidebar-nav">
        <a href="dashboard.php" class="nav-item <?php echo ($currentPage == 'dashboard.php') ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/>
                </svg>
            </span>
            <span class="nav-label">Dashboard</span>
        </a>

        <a href="report_lost.php" class="nav-item <?php echo ($currentPage == 'report_lost.php') ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                    <polyline points="14,2 14,8 20,8"/>
                    <line x1="12" y1="18" x2="12" y2="12"/>
                    <line x1="9" y1="15" x2="15" y2="15"/>
                </svg>
            </span>
            <span class="nav-label">Report Lost Item</span>
        </a>

        <a href="report_found.php" class="nav-item <?php echo ($currentPage == 'report_found.php') ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    <line x1="11" y1="8" x2="11" y2="14"/>
                    <line x1="8" y1="11" x2="14" y2="11"/>
                </svg>
            </span>
            <span class="nav-label">Report Found Item</span>
        </a>

        <a href="generate_qr.php" class="nav-item <?php echo ($currentPage == 'generate_qr.php') ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="5" height="5"/><rect x="16" y="3" width="5" height="5"/>
                    <rect x="3" y="16" width="5" height="5"/>
                    <path d="M21 16h-3v3"/><path d="M21 21v.01"/><path d="M12 7v3h3"/>
                    <path d="M12 3v.01"/><path d="M12 12v.01"/><path d="M3 12h.01"/><path d="M7 12h3v3"/>
                </svg>
            </span>
            <span class="nav-label">Generate-QR</span>
        </a>

        <a href="message.php" class="nav-item <?php echo ($currentPage == 'message.php') ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                </svg>
            </span>
            <span class="nav-label">Message</span>
        </a>

        <a href="claimed_items.php" class="nav-item <?php echo ($currentPage == 'claimed_items.php') ? 'active' : ''; ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                    <line x1="3" y1="6" x2="21" y2="6"/>
                    <path d="M16 10a4 4 0 0 1-8 0"/>
                </svg>
            </span>
            <span class="nav-label">Claimed Item</span>
        </a>

        <a href="settings_items.php" class="nav-item <?php echo ($currentPage=='settings_items.php' || $currentPage=='settings_personal.php')?'active':''; ?>">
            <span class="nav-icon">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/>
                    <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </span>
            <span class="nav-label">Settings</span>
        </a>
    </nav>
</div>