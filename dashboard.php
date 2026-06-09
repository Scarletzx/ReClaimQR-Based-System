<?php
session_start();
require_once "config/db.php";

// Redirect to login if not logged in
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$fullname = $_SESSION["fullname"] ?? "User";

// Fetch items from DB - both lost and found
$filter = $_GET['filter'] ?? 'all';
$search = trim($_GET['search'] ?? '');

// UNION query from both tables
$where_lost  = "1=1";
$where_found = "1=1";

if ($filter !== 'all') {
    $cat = $conn->real_escape_string($filter);
    $where_lost  .= " AND category = '$cat'";
    $where_found .= " AND category = '$cat'";
}

if (!empty($search)) {
    $s = $conn->real_escape_string($search);
    $where_lost  .= " AND (item_name LIKE '%$s%' OR description LIKE '%$s%' OR location LIKE '%$s%')";
    $where_found .= " AND (item_name LIKE '%$s%' OR description LIKE '%$s%' OR location LIKE '%$s%')";
}

$sql = "
    SELECT id, 'Lost' AS type, item_name, category, location, description, image, created_at
    FROM items WHERE $where_lost
    UNION ALL
    SELECT id, 'Found' AS type, item_name, category, location, description, image, created_at
    FROM items_found WHERE $where_found
    ORDER BY created_at DESC
";

$result = $conn->query($sql);
$items  = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ReClaimQR</title>
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>

<div class="app-wrapper">
    <!-- Sidebar Navigation -->
    <?php include "includes/navbar.php"; ?>

    <!-- Main Content -->
    <div class="main-content">

        <!-- Top Bar -->
        <div class="topbar">
            <div></div>
            <div class="topbar-right">
                <a href="settings_personal.php" class="user-avatar" title="<?php echo htmlspecialchars($fullname); ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
            </div>
        </div>

        <!-- Page Body -->
        <div class="page-body">

            <!-- Hero Banner -->
            <div class="hero-banner">
                <div class="hero-text">
                    <h1>Let's Find Your Items !</h1>
                    <form method="GET" action="dashboard.php" class="search-form">
                        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                        <div class="search-bar">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#999" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"/>
                                <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                            </svg>
                            <input type="text" name="search" placeholder="Search your lost items" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </form>
                </div>
                <div class="hero-graphic">
                    <div class="cloud-shape"></div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="filter-tabs">
                <a href="?filter=all&search=<?php echo urlencode($search); ?>" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">All Items</a>
                <a href="?filter=Electronics&search=<?php echo urlencode($search); ?>" class="filter-btn <?php echo $filter === 'Electronics' ? 'active' : ''; ?>">Electronics</a>
                <a href="?filter=Accessory&search=<?php echo urlencode($search); ?>" class="filter-btn <?php echo $filter === 'Accessory' ? 'active' : ''; ?>">Accessory</a>
                <a href="?filter=Others&search=<?php echo urlencode($search); ?>" class="filter-btn <?php echo $filter === 'Others' ? 'active' : ''; ?>">Others</a>
            </div>

            <!-- Recent Items Section -->
            <div class="section-header">
                <h2>Recent Items</h2>
            </div>

            <?php if (empty($items)): ?>
                <div class="no-items">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    <p>No items found.</p>
                </div>
            <?php else: ?>
                <div class="items-grid">
                    <?php foreach ($items as $item): ?>
                        <a href="item_details.php?id=<?php echo $item['id']; ?>&type=<?php echo $item['type']; ?>" class="item-card">
                            <div class="item-image">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>">
                                <?php else: ?>
                                    <div class="no-image">
                                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                                            <rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/>
                                            <polyline points="21,15 16,10 5,21"/>
                                        </svg>
                                    </div>
                                <?php endif; ?>
                                <span class="item-badge badge-<?php echo strtolower($item['type']); ?>">
                                    <?php echo htmlspecialchars($item['type']); ?>
                                </span>
                            </div>
                            <div class="item-info">
                                <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                <p class="item-desc">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="9" y1="21" x2="9" y2="9"/></svg>
                                    <?php echo htmlspecialchars($item['description'] ?? ''); ?>
                                </p>
                                <p class="item-location">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                    <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div><!-- end page-body -->
    </div><!-- end main-content -->
</div><!-- end app-wrapper -->

<script>
const searchInput = document.querySelector('.search-bar input');
let searchTimer;
if (searchInput) {
    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            this.closest('form').submit();
        }, 500);
    });
}
</script>

</body>
</html>