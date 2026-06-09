<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user_id = $_SESSION["user_id"];

// --- AJAX: trigger claim insertion from item_details.php ---
if (isset($_GET["ajax_trigger"])) {
    $item_id   = intval($_GET["item_id"] ?? 0);
    $item_type = $_GET["item_type"] ?? "Lost";
    $owner_id  = intval($_GET["owner_id"] ?? 0);

    if ($item_id > 0 && $owner_id > 0) {
        $check = $conn->prepare("SELECT id FROM claimed_items WHERE item_id = ? AND item_type = ? AND claimant_id = ?");
        $check->bind_param("isi", $item_id, $item_type, $current_user_id);
        $check->execute();

        if (!$check->get_result()->fetch_assoc()) {
            $table    = ($item_type === "Found") ? "items_found" : "items";
            $date_col = ($item_type === "Found") ? "date_found" : "date_missing";
            $time_col = ($item_type === "Found") ? "time_found" : "time_missing";

            $i_stmt = $conn->prepare("
                SELECT item_name, category, location, description, image,
                       $date_col AS item_date, $time_col AS item_time
                FROM $table WHERE id = ?
            ");
            $i_stmt->bind_param("i", $item_id);
            $i_stmt->execute();
            $item_data = $i_stmt->get_result()->fetch_assoc();

            if ($item_data) {
                $i_name     = $item_data['item_name'];
                $i_category = $item_data['category'];
                $i_location = $item_data['location'];
                $i_desc     = $item_data['description'];
                $i_image    = $item_data['image'] ?? '';
                $i_date     = $item_data['item_date'];
                $i_time     = $item_data['item_time'];

                $ins = $conn->prepare("
                    INSERT INTO claimed_items
                    (item_id, item_type, claimant_id, owner_id, status,
                     item_name, item_category, item_location, item_description,
                     item_image, item_date, item_time)
                    VALUES (?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->bind_param(
                    "isiisssssss",
                    $item_id,
                    $item_type,
                    $current_user_id,
                    $owner_id,
                    $i_name,
                    $i_category,
                    $i_location,
                    $i_desc,
                    $i_image,
                    $i_date,
                    $i_time
                );
                $ins->execute();
            }
        }
    }
    echo json_encode(["success" => true]);
    exit();
}

// --- Handle Confirm Claim ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["confirm_claim"])) {
    $claim_id  = intval($_POST["claim_id"]);
    $comments  = trim($_POST["comments"] ?? "");
    $proof_img = "";

    $verify = $conn->prepare("SELECT * FROM claimed_items WHERE id = ? AND owner_id = ? AND status = 'Pending'");
    $verify->bind_param("ii", $claim_id, $current_user_id);
    $verify->execute();
    $claim = $verify->get_result()->fetch_assoc();

    if ($claim) {
        if (isset($_FILES["proof_image"]) && $_FILES["proof_image"]["error"] === UPLOAD_ERR_OK) {
            $allowed   = ["image/jpeg", "image/png", "image/webp"];
            $file_type = mime_content_type($_FILES["proof_image"]["tmp_name"]);
            $file_size = $_FILES["proof_image"]["size"];

            if (in_array($file_type, $allowed) && $file_size <= 10 * 1024 * 1024) {
                $ext      = pathinfo($_FILES["proof_image"]["name"], PATHINFO_EXTENSION);
                $filename = "proof_" . uniqid() . "." . $ext;
                move_uploaded_file($_FILES["proof_image"]["tmp_name"], "uploads/" . $filename);
                $proof_img = "uploads/" . $filename;
            }
        }

        $upd = $conn->prepare("UPDATE claimed_items SET status = 'Claimed', comments = ?, proof_image = ?, confirmed_at = NOW() WHERE id = ?");
        $upd->bind_param("ssi", $comments, $proof_img, $claim_id);
        $upd->execute();

        $table = ($claim["item_type"] === "Found") ? "items_found" : "items";
        $del   = $conn->prepare("DELETE FROM $table WHERE id = ?");
        $del->bind_param("i", $claim["item_id"]);
        $del->execute();

        $_SESSION["claim_success"] = "Item successfully confirmed as claimed!";
    }

    header("Location: claimed_items.php");
    exit();
}

// --- Handle Cancel Claim ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_claim"])) {
    $claim_id = intval($_POST["claim_id"]);

    $verify = $conn->prepare("SELECT * FROM claimed_items WHERE id = ? AND owner_id = ? AND status = 'Pending'");
    $verify->bind_param("ii", $claim_id, $current_user_id);
    $verify->execute();
    $claim = $verify->get_result()->fetch_assoc();

    if ($claim) {
        // Simply delete from claimed_items only — dashboard not affected
        $del = $conn->prepare("DELETE FROM claimed_items WHERE id = ?");
        $del->bind_param("i", $claim_id);
        $del->execute();
    }

    header("Location: claimed_items.php");
    exit();
}

// --- Fetch claimed items for current user (as owner) ---
$claims_stmt = $conn->prepare("
    SELECT ci.*, u.fullname AS claimant_name
    FROM claimed_items ci
    JOIN users u ON u.id = ci.claimant_id
    WHERE ci.owner_id = ?
    ORDER BY ci.created_at DESC
");
$claims_stmt->bind_param("i", $current_user_id);
$claims_stmt->execute();
$claims = $claims_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Active claim for detail view
$active_claim_id = intval($_GET["claim"] ?? 0);
$active_claim    = null;
foreach ($claims as $c) {
    if ($c["id"] == $active_claim_id) {
        $active_claim = $c;
        break;
    }
}
if (!$active_claim && !empty($claims)) {
    $active_claim = $claims[0];
}

$success_msg = $_SESSION["claim_success"] ?? "";
unset($_SESSION["claim_success"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Claimed Items - ReClaimQR</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/claimed_items.css">
</head>
<body>
<div class="app-wrapper">
    <?php include "includes/navbar.php"; ?>

    <div class="main-content">

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
            <div class="claim-success-alert">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <polyline points="20 6 9 17 4 12"/>
                </svg>
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <div class="claimed-layout">

            <!-- Left Panel -->
            <div class="claimed-sidebar">
                <div class="claimed-sidebar-header">
                    <h2>Claimed Items</h2>
                </div>
                <div class="claimed-list">
                    <?php if (empty($claims)): ?>
                        <div class="claimed-empty">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                                <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                                <line x1="3" y1="6" x2="21" y2="6"/>
                                <path d="M16 10a4 4 0 0 1-8 0"/>
                            </svg>
                            <p>No claimed items yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($claims as $claim): ?>
                            <a href="claimed_items.php?claim=<?php echo $claim['id']; ?>"
                               class="claimed-list-item <?php echo ($active_claim && $active_claim['id'] == $claim['id']) ? 'active' : ''; ?>">
                                <div class="claimed-list-info">
                                    <span class="claimed-list-name">
                                        <?php echo htmlspecialchars($claim['item_name'] ?? 'Unknown Item'); ?>
                                    </span>
                                    <span class="claimed-list-claimant">
                                        by <?php echo htmlspecialchars($claim['claimant_name']); ?>
                                    </span>
                                </div>
                                <span class="claimed-status-badge status-<?php echo strtolower($claim['status']); ?>">
                                    <?php echo $claim['status']; ?>
                                </span>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right Panel -->
            <div class="claimed-detail-panel">
                <?php if ($active_claim): ?>
                    <div class="claimed-detail-scroll">
                        <h2 class="claimed-detail-title">Item's Details</h2>

                        <!-- Item Image -->
                        <div class="claimed-img-wrap">
                            <?php if (!empty($active_claim['item_image'])): ?>
                                <img src="<?php echo htmlspecialchars($active_claim['item_image']); ?>"
                                     alt="Item Image">
                            <?php else: ?>
                                <div class="claimed-no-img">
                                    <svg width="50" height="50" viewBox="0 0 24 24" fill="none" stroke="#ccc" stroke-width="1.5">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21,15 16,10 5,21"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                            <span class="claimed-type-badge badge-<?php echo strtolower($active_claim['item_type']); ?>">
                                <?php echo $active_claim['item_type']; ?>
                            </span>
                        </div>

                        <!-- Item Info Grid -->
                        <div class="claimed-info-grid">
                            <div class="claimed-info-block">
                                <span class="claimed-info-label">Item's Name :</span>
                                <div class="claimed-info-value">
                                    <?php echo htmlspecialchars($active_claim['item_name'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="claimed-info-block">
                                <span class="claimed-info-label">Item's Location :</span>
                                <div class="claimed-info-value">
                                    <?php echo htmlspecialchars($active_claim['item_location'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="claimed-info-block">
                                <span class="claimed-info-label">Item's Category :</span>
                                <div class="claimed-info-value">
                                    <?php echo htmlspecialchars($active_claim['item_category'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="claimed-info-block">
                                <span class="claimed-info-label">Date :</span>
                                <div class="claimed-info-value">
                                    <?php echo !empty($active_claim['item_date']) ? date("d F Y", strtotime($active_claim['item_date'])) : '-'; ?>
                                </div>
                            </div>
                            <div class="claimed-info-block">
                                <span class="claimed-info-label">Description :</span>
                                <div class="claimed-info-value">
                                    <?php echo htmlspecialchars($active_claim['item_description'] ?? '-'); ?>
                                </div>
                            </div>
                            <div class="claimed-info-block">
                                <span class="claimed-info-label">Time :</span>
                                <div class="claimed-info-value">
                                    <?php echo !empty($active_claim['item_time']) ? date("g:i A", strtotime($active_claim['item_time'])) : '-'; ?>
                                </div>
                            </div>
                        </div>

                        <?php if ($active_claim['status'] === 'Pending'): ?>
                            <form method="POST" action="" enctype="multipart/form-data" id="claimForm">
                                <input type="hidden" name="claim_id" value="<?php echo $active_claim['id']; ?>">

                                <div class="claimed-form-row">
                                    <div class="claimed-form-group">
                                        <label>Comments :</label>
                                        <textarea name="comments"
                                                  placeholder="Add any notes about this claim..."
                                                  rows="4"></textarea>
                                    </div>
                                    <div class="claimed-form-group">
                                        <label>Upload Proves of Claim :</label>
                                        <div class="claimed-upload-zone" id="claimUploadZone">
                                            <div class="claimed-upload-content" id="claimUploadContent">
                                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none"
                                                     stroke="#bbb" stroke-width="1.8"
                                                     stroke-linecap="round" stroke-linejoin="round">
                                                    <polyline points="16 16 12 12 8 16"/>
                                                    <line x1="12" y1="12" x2="12" y2="21"/>
                                                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                                                </svg>
                                                <p class="claimed-upload-text">Drag &amp; drop your photos here</p>
                                                <p class="claimed-upload-hint">Support for JPG, PNG, WebP files up to 10MB</p>
                                                <label class="claimed-upload-btn" for="proofInput">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none"
                                                         stroke="currentColor" stroke-width="2">
                                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                                        <polyline points="21,15 16,10 5,21"/>
                                                    </svg>
                                                    Choose Photos
                                                </label>
                                                <input type="file" name="proof_image" id="proofInput"
                                                       accept="image/jpeg,image/png,image/webp"
                                                       style="display:none;">
                                            </div>
                                            <div id="proofPreviewContainer" style="display:none;">
                                                <img id="proofPreviewImg" src="" alt="Preview">
                                                <button type="button" class="claimed-remove-photo"
                                                        id="removeProof">&#x2715;</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="claimed-action-btns">
                                    <button type="submit" name="confirm_claim"
                                            class="claimed-btn claimed-btn-confirm"
                                            onclick="return confirm('Are you sure you want to confirm this claim? The item will be removed from the dashboard.')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2.5"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <polyline points="20 6 9 17 4 12"/>
                                        </svg>
                                        Confirm Claim
                                    </button>
                                    <button type="submit" name="cancel_claim"
                                            class="claimed-btn claimed-btn-cancel"
                                            onclick="return confirm('Are you sure you want to cancel? The item will remain on the dashboard.')">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
                                             stroke="currentColor" stroke-width="2.5"
                                             stroke-linecap="round" stroke-linejoin="round">
                                            <line x1="18" y1="6" x2="6" y2="18"/>
                                            <line x1="6" y1="6" x2="18" y2="18"/>
                                        </svg>
                                        Cancel
                                    </button>
                                </div>
                            </form>

                        <?php elseif ($active_claim['status'] === 'Claimed'): ?>
                            <div class="claimed-history">
                                <div class="claimed-history-badge">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2.5"
                                         stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                    Item Successfully Claimed
                                </div>
                                <?php if (!empty($active_claim['confirmed_at'])): ?>
                                    <p class="claimed-history-date">
                                        Confirmed on <?php echo date("d F Y, g:i A", strtotime($active_claim['confirmed_at'])); ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($active_claim['comments'])): ?>
                                    <div class="claimed-history-comments">
                                        <strong>Comments:</strong>
                                        <p><?php echo htmlspecialchars($active_claim['comments']); ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($active_claim['proof_image'])): ?>
                                    <div class="claimed-proof-wrap">
                                        <strong>Proof of Claim:</strong>
                                        <img src="<?php echo htmlspecialchars($active_claim['proof_image']); ?>"
                                             alt="Proof" class="claimed-proof-img">
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                    </div>

                <?php else: ?>
                    <div class="claimed-no-selection">
                        <svg width="64" height="64" viewBox="0 0 24 24" fill="none"
                             stroke="#d0d4ff" stroke-width="1.5">
                            <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/>
                            <line x1="3" y1="6" x2="21" y2="6"/>
                            <path d="M16 10a4 4 0 0 1-8 0"/>
                        </svg>
                        <h3>No claimed items yet</h3>
                        <p>Items will appear here when someone contacts you about your reported items</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<script>
const proofInput    = document.getElementById('proofInput');
const previewCont   = document.getElementById('proofPreviewContainer');
const previewImg    = document.getElementById('proofPreviewImg');
const removeBtn     = document.getElementById('removeProof');
const uploadContent = document.getElementById('claimUploadContent');
const uploadZone    = document.getElementById('claimUploadZone');

if (proofInput) {
    proofInput.addEventListener('change', function () {
        if (this.files[0]) showProofPreview(this.files[0]);
    });
}

if (removeBtn) {
    removeBtn.addEventListener('click', function () {
        proofInput.value            = '';
        previewImg.src              = '';
        previewCont.style.display   = 'none';
        uploadContent.style.display = 'flex';
        uploadZone.classList.remove('has-preview');
    });
}

function showProofPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src              = e.target.result;
        previewCont.style.display   = 'flex';
        uploadContent.style.display = 'none';
        uploadZone.classList.add('has-preview');
    };
    reader.readAsDataURL(file);
}

if (uploadZone) {
    uploadZone.addEventListener('dragover', e => {
        e.preventDefault();
        uploadZone.classList.add('drag-over');
    });
    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('drag-over');
    });
    uploadZone.addEventListener('drop', e => {
        e.preventDefault();
        uploadZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            proofInput.files = dt.files;
            showProofPreview(file);
        }
    });
}

const alertEl = document.querySelector('.claim-success-alert');
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