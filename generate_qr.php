<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$error       = "";
$qr_data     = null;
$unique_code = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id          = $_SESSION["user_id"];
    $owner_name       = trim($_POST["owner_name"]);
    $owner_phone      = trim($_POST["owner_phone"]);
    $owner_faculty    = trim($_POST["owner_faculty"]);
    $owner_email      = trim($_POST["owner_email"]);
    $item_name        = trim($_POST["item_name"]);
    $item_category    = trim($_POST["item_category"]);
    $item_description = trim($_POST["item_description"]);

    if (empty($owner_name) || empty($owner_phone) || empty($owner_faculty) || empty($owner_email) || empty($item_name) || empty($item_category) || empty($item_description)) {
        $error = "Please fill in all required fields.";
    } elseif (!isset($_FILES["item_image"]) || $_FILES["item_image"]["error"] !== UPLOAD_ERR_OK) {
        $error = "Please upload a photo of the item.";
    } elseif (!preg_match('/^[0-9\-\+\s]{7,15}$/', $owner_phone)) {
        $error = "Phone number must contain numbers only (e.g., 011-12345678).";
    } elseif (!filter_var($owner_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Handle image upload
        $item_image = "";
        if (isset($_FILES["item_image"]) && $_FILES["item_image"]["error"] === UPLOAD_ERR_OK) {
            $allowed   = ["image/jpeg", "image/png", "image/webp"];
            $file_type = mime_content_type($_FILES["item_image"]["tmp_name"]);
            $file_size = $_FILES["item_image"]["size"];

            if (!in_array($file_type, $allowed)) {
                $error = "Only JPG, PNG, and WebP images are allowed.";
            } elseif ($file_size > 10 * 1024 * 1024) {
                $error = "File size must not exceed 10MB.";
            } else {
                $ext        = pathinfo($_FILES["item_image"]["name"], PATHINFO_EXTENSION);
                $filename   = "qr_" . uniqid() . "." . $ext;
                move_uploaded_file($_FILES["item_image"]["tmp_name"], "uploads/" . $filename);
                $item_image = "uploads/" . $filename;
            }
        }

        if (empty($error)) {
            $unique_code = bin2hex(random_bytes(16));
            $scan_url = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/scan.php?code=" . $unique_code;

            $stmt = $conn->prepare("INSERT INTO qr_items (user_id, unique_code, owner_name, owner_phone, owner_faculty, owner_email, item_name, item_category, item_description, item_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssssss", $user_id, $unique_code, $owner_name, $owner_phone, $owner_faculty, $owner_email, $item_name, $item_category, $item_description, $item_image);

            if ($stmt->execute()) {
                $qr_data = $scan_url;
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generate QR-Code - ReClaimQR</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/Qr_Code.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>
<body>
<div class="app-wrapper">
    <?php include "includes/navbar.php"; ?>

    <div class="main-content">

        <div class="topbar">
            <div></div>
            <div class="topbar-right">
                <a href="settings_personal.php" class="user-avatar" title="<?php echo htmlspecialchars($_SESSION['fullname'] ?? 'User'); ?>">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
            </div>
        </div>

        <div class="page-body">

            <!-- Page Header -->
            <div class="qr-page-header">
                <div class="qr-cloud qr-cloud-left"></div>
                <div class="qr-cloud qr-cloud-right"></div>
                <h1>Generate QR-Code</h1>
                <p>Let's Generate QR-Tagging for Your Personal Items</p>
            </div>

            <!-- Alert -->
            <?php if (!empty($error)): ?>
                <div class="qr-alert qr-alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10"/>
                        <line x1="12" y1="8" x2="12" y2="12"/>
                        <line x1="12" y1="16" x2="12.01" y2="16"/>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($qr_data): ?>
                <!-- QR Result -->
                <div class="qr-result-overlay">
                    <div class="qr-result-card">
                        <div class="qr-success-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#2ecc71" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"/>
                            </svg>
                        </div>
                        <h2>Your QR Code is Ready!</h2>
                        <p class="qr-subtitle">Download and attach it to your item. Anyone who scans it will see your contact details.</p>
                        <div id="qrcode"></div>
                        <div class="qr-actions">
                            <button onclick="downloadQR()" class="qr-download-btn">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                    <polyline points="7 10 12 15 17 10"/>
                                    <line x1="12" y1="15" x2="12" y2="3"/>
                                </svg>
                                Download QR Code
                            </button>
                            <a href="generate_qr.php" class="qr-new-btn">+ Generate Another</a>
                        </div>
                    </div>
                </div>

                <script>
                window.addEventListener('load', function () {
                    new QRCode(document.getElementById("qrcode"), {
                        text: "<?php echo addslashes($qr_data); ?>",
                        width: 220,
                        height: 220,
                        colorDark: "#1e1e2e",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                });

                function downloadQR() {
                    setTimeout(() => {
                        const canvas = document.querySelector('#qrcode canvas');
                        if (canvas) {
                            const link = document.createElement('a');
                            link.download = 'ReclaimQR_<?php echo $unique_code; ?>.png';
                            link.href = canvas.toDataURL('image/png');
                            link.click();
                        }
                    }, 500);
                }
                </script>

            <?php else: ?>
                <!-- *** UPDATED: Added enctype for file upload *** -->
                <form method="POST" action="" id="generateQrForm" enctype="multipart/form-data">

                    <!-- Owner's Information -->
                    <div class="qr-card">
                        <h3 class="qr-section-title">Owner's Information <span class="qr-required">*</span></h3>

                        <div class="qr-row">
                            <div class="qr-group">
                                <label>Name <span class="qr-required">*</span></label>
                                <input type="text" name="owner_name"
                                       placeholder="Enter Your Name"
                                       value="<?php echo htmlspecialchars($_POST['owner_name'] ?? ($_SESSION['fullname'] ?? '')); ?>"
                                       required>
                            </div>
                            <div class="qr-group">
                                <label>Phone No. <span class="qr-required">*</span></label>
                                <input type="text" name="owner_phone"
                                       placeholder="e.g., 01X-XXXXXXX"
                                       value="<?php echo htmlspecialchars($_POST['owner_phone'] ?? ''); ?>"
                                       required>
                            </div>
                        </div>

                        <div class="qr-row">
                            <div class="qr-group">
                                <label>Faculty/Organisation <span class="qr-required">*</span></label>
                                <div class="qr-select-wrapper">
                                    <select name="owner_faculty" required>
                                        <option value="" disabled <?php echo empty($_POST['owner_faculty']) ? 'selected' : ''; ?>>Select Faculty/Organisation</option>
                                        <option value="Faculty of Economics and Business"                   <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Economics and Business')                   ? 'selected' : ''; ?>>Faculty of Economics and Business</option>
                                        <option value="Faculty of Engineering"                              <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Engineering')                              ? 'selected' : ''; ?>>Faculty of Engineering</option>
                                        <option value="Faculty of Applied and Creative Arts"                <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Applied and Creative Arts')                ? 'selected' : ''; ?>>Faculty of Applied and Creative Arts</option>
                                        <option value="Faculty of Cognitive Sciences and Human Development" <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Cognitive Sciences and Human Development') ? 'selected' : ''; ?>>Faculty of Cognitive Sciences and Human Development</option>
                                        <option value="Faculty of Medicine and Health Sciences"             <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Medicine and Health Sciences')             ? 'selected' : ''; ?>>Faculty of Medicine and Health Sciences</option>
                                        <option value="Faculty of Social Sciences and Humanities"           <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Social Sciences and Humanities')           ? 'selected' : ''; ?>>Faculty of Social Sciences and Humanities</option>
                                        <option value="Faculty of Resource Science and Technology"          <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Resource Science and Technology')          ? 'selected' : ''; ?>>Faculty of Resource Science and Technology</option>
                                        <option value="Faculty of Computer Science and Information Technology" <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Computer Science and Information Technology') ? 'selected' : ''; ?>>Faculty of Computer Science and Information Technology</option>
                                        <option value="Faculty of Language and Communication"               <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Language and Communication')               ? 'selected' : ''; ?>>Faculty of Language and Communication</option>
                                        <option value="Faculty of Built Environment"                        <?php echo (($_POST['owner_faculty'] ?? '') === 'Faculty of Built Environment')                        ? 'selected' : ''; ?>>Faculty of Built Environment</option>
                                    </select>
                                </div>
                            </div>
                            <div class="qr-group">
                                <label>Email <span class="qr-required">*</span></label>
                                <input type="email" name="owner_email"
                                       placeholder="your.email@gmail.com"
                                       value="<?php echo htmlspecialchars($_POST['owner_email'] ?? ''); ?>"
                                       required>
                            </div>
                        </div>
                    </div>

                    <!-- Item's Information -->
                    <div class="qr-card">
                        <h3 class="qr-section-title">Item's Information <span class="qr-required">*</span></h3>

                        <div class="qr-item-grid">
                            <div class="qr-item-left">
                                <div class="qr-group">
                                    <label>Item's Name <span class="qr-required">*</span></label>
                                    <input type="text" name="item_name"
                                           placeholder="e.g., Macbook Pro, Iphone 11"
                                           value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>"
                                           required>
                                </div>
                                <div class="qr-group" style="margin-top: 16px;">
                                    <label>Item's Category <span class="qr-required">*</span></label>
                                    <div class="qr-select-wrapper">
                                        <select name="item_category" required>
                                            <option value="" disabled <?php echo empty($_POST['item_category']) ? 'selected' : ''; ?>>Select Category</option>
                                            <option value="Electronics" <?php echo (($_POST['item_category'] ?? '') === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                                            <option value="Accessory"   <?php echo (($_POST['item_category'] ?? '') === 'Accessory')   ? 'selected' : ''; ?>>Accessory</option>
                                            <option value="Clothing"    <?php echo (($_POST['item_category'] ?? '') === 'Clothing')    ? 'selected' : ''; ?>>Clothing</option>
                                            <option value="Documents"   <?php echo (($_POST['item_category'] ?? '') === 'Documents')   ? 'selected' : ''; ?>>Documents</option>
                                            <option value="Others"      <?php echo (($_POST['item_category'] ?? '') === 'Others')      ? 'selected' : ''; ?>>Others</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="qr-group">
                                <label>Item's Description <span class="qr-required">*</span></label>
                                <textarea name="item_description"
                                          placeholder="e.g., colour, condition and etc."
                                          rows="6" required><?php echo htmlspecialchars($_POST['item_description'] ?? ''); ?></textarea>
                            </div>
                        </div>

                                            <!-- Image Upload Section -->
                    <div class="qr-upload-section">
                        <label class="qr-upload-label">
                            Upload Item's Photo <span class="qr-required">*</span>
                        </label>
                        <div class="qr-upload-zone" id="qrUploadZone">
                            <div class="qr-upload-content" id="qrUploadContent">
                                <svg width="32" height="32" viewBox="0 0 24 24" fill="none"
                                     stroke="#bbb" stroke-width="1.8"
                                     stroke-linecap="round" stroke-linejoin="round">
                                    <polyline points="16 16 12 12 8 16"/>
                                    <line x1="12" y1="12" x2="12" y2="21"/>
                                    <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                                </svg>
                                <p class="qr-upload-text">Drag &amp; drop your photo here</p>
                                <p class="qr-upload-hint">Support for JPG, PNG, WebP files up to 10MB</p>
                                <label class="qr-upload-btn">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2">
                                        <rect x="3" y="3" width="18" height="18" rx="2"/>
                                        <circle cx="8.5" cy="8.5" r="1.5"/>
                                        <polyline points="21,15 16,10 5,21"/>
                                    </svg>
                                    Choose Photo
                                    <input type="file" name="item_image" id="qrImageInput"
                                           accept="image/jpeg,image/png,image/webp"
                                           style="display:none;">
                                </label>
                            </div>
                            <div class="qr-preview-container" id="qrPreviewContainer" style="display:none;">
                                <img id="qrPreviewImg" src="" alt="Preview">
                                <button type="button" class="qr-remove-photo" id="qrRemovePhoto">&#x2715;</button>
                            </div>
                        </div>
                    </div>

                    </div>

                    <!-- Submit -->
                    <div class="qr-submit-row">
                        <button type="submit" class="qr-submit-btn">Generate QR</button>
                    </div>

                </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// *** NEW: Image upload preview ***
const qrImageInput    = document.getElementById('qrImageInput');
const qrUploadZone    = document.getElementById('qrUploadZone');
const qrUploadContent = document.getElementById('qrUploadContent');
const qrPreviewCont   = document.getElementById('qrPreviewContainer');
const qrPreviewImg    = document.getElementById('qrPreviewImg');
const qrRemovePhoto   = document.getElementById('qrRemovePhoto');

if (qrImageInput) {
    qrImageInput.addEventListener('change', function () {
        if (this.files[0]) {
            showQrPreview(this.files[0]);
            if (qrUploadZone) qrUploadZone.classList.remove('upload-error');
        }
    });
}

if (qrRemovePhoto) {
    qrRemovePhoto.addEventListener('click', function () {
        qrImageInput.value            = '';
        qrPreviewImg.src              = '';
        qrPreviewCont.style.display   = 'none';
        qrUploadContent.style.display = 'flex';
        qrUploadZone.classList.remove('has-preview');
    });
}

function showQrPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
        qrPreviewImg.src              = e.target.result;
        qrPreviewCont.style.display   = 'flex';
        qrUploadContent.style.display = 'none';
        qrUploadZone.classList.add('has-preview');
    };
    reader.readAsDataURL(file);
}

if (qrUploadZone) {
    qrUploadZone.addEventListener('dragover', e => {
        e.preventDefault();
        qrUploadZone.classList.add('drag-over');
    });
    qrUploadZone.addEventListener('dragleave', () => {
        qrUploadZone.classList.remove('drag-over');
    });
    qrUploadZone.addEventListener('drop', e => {
        e.preventDefault();
        qrUploadZone.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (file) {
            const dt = new DataTransfer();
            dt.items.add(file);
            qrImageInput.files = dt.files;
            showQrPreview(file);
        }
    });
}

// Form validation
const form = document.getElementById('generateQrForm');
if (form) {
    form.addEventListener('submit', function(e) {
        let valid = true;
        this.querySelectorAll('[required]').forEach(f => {
            f.classList.remove('qr-input-error');
            if (!f.value.trim()) {
                f.classList.add('qr-input-error');
                valid = false;
            }
        });
        // Check photo is uploaded
        if (!qrImageInput || !qrImageInput.files || qrImageInput.files.length === 0) {
            if (qrUploadZone) qrUploadZone.classList.add('upload-error');
            valid = false;
        } else {
            if (qrUploadZone) qrUploadZone.classList.remove('upload-error');
        }
        if (!valid) {
            e.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }
    });
}
</script>

</body>
</html>