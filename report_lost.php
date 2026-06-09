<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id      = $_SESSION["user_id"];
    $item_name    = trim($_POST["item_name"]);
    $category     = trim($_POST["category"]);
    $location     = trim($_POST["location"]);
    $description  = trim($_POST["description"]);
    $date_missing = trim($_POST["date_missing"]);
    $time_missing = trim($_POST["time_missing"]);
    $contact_name = trim($_POST["contact_name"]);
    $contact_info = trim($_POST["contact_info"]);

    if (empty($item_name) || empty($category) || empty($location) || empty($description) || empty($date_missing) || empty($time_missing) || empty($contact_name) || empty($contact_info)) {
        $error = "Please fill in all required fields.";
    } elseif (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
        $error = "Please upload a photo of the item.";
    } elseif (!empty($_POST['date_missing']) && $_POST['date_missing'] > date('Y-m-d')) {
        $error = "Please enter the correct date. Future dates are not allowed.";
    } else {
        $image_path = "";
        if (isset($_FILES["photo"]) && $_FILES["photo"]["error"] === UPLOAD_ERR_OK) {
            $allowed   = ["image/jpeg", "image/png", "image/webp"];
            $file_type = mime_content_type($_FILES["photo"]["tmp_name"]);
            $file_size = $_FILES["photo"]["size"];

            if (!in_array($file_type, $allowed)) {
                $error = "Only JPG, PNG, and WebP images are allowed.";
            } elseif ($file_size > 10 * 1024 * 1024) {
                $error = "File size must not exceed 10MB.";
            } else {
                $ext        = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
                $filename   = "lost_" . uniqid() . "." . $ext;
                $upload_dir = "uploads/";
                move_uploaded_file($_FILES["photo"]["tmp_name"], $upload_dir . $filename);
                $image_path = $upload_dir . $filename;
            }
        }

        if (empty($error)) {
            $stmt = $conn->prepare("INSERT INTO items (user_id, type, item_name, category, location, description, date_missing, time_missing, contact_name, contact_info, image, created_at) VALUES (?, 'Lost', ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param("isssssssss", $user_id, $item_name, $category, $location, $description, $date_missing, $time_missing, $contact_name, $contact_info, $image_path);
            if ($stmt->execute()) {
                $success = "Your lost item has been reported successfully!";
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
    <title>Report Lost Item - ReClaimQR</title>
    <link rel="stylesheet" href="css/report_lost.css">
</head>
<body>
<div class="app-wrapper">
    <?php include "includes/navbar.php"; ?>
    <div class="main-content">

        <div class="topbar">
            <div></div>
            <div class="topbar-right">
                <a href="settings_personal.php" class="user-avatar">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </a>
            </div>
        </div>

        <div class="page-body">

            <!-- Page Header with clouds -->
            <div class="form-page-header">
                <div class="cloud-deco cloud-left"></div>
                <div class="cloud-deco cloud-right"></div>
                <h1>Report Lost Items</h1>
                <p>Fill in your item details, so we can help you find your item</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"/></svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="" enctype="multipart/form-data" id="reportLostForm">

                <!-- Item Information Card -->
                <div class="form-card">
                    <h3 class="form-section-title">Item's Information</h3>

                    <div class="form-row three-col">
                        <div class="form-group">
                            <label>Item's Name <span class="required">*</span></label>
                            <input type="text" name="item_name" placeholder="e.g., Macbook Pro, Iphone 11"
                                   value="<?php echo htmlspecialchars($_POST['item_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Item's Category <span class="required">*</span></label>
                            <div class="select-wrapper">
                                <select name="category" required>
                                    <option value="" disabled <?php echo empty($_POST['category']) ? 'selected' : ''; ?>>Select Category</option>
                                    <option value="Electronics" <?php echo (($_POST['category'] ?? '') === 'Electronics') ? 'selected' : ''; ?>>Electronics</option>
                                    <option value="Accessory"   <?php echo (($_POST['category'] ?? '') === 'Accessory')   ? 'selected' : ''; ?>>Accessory</option>
                                    <option value="Clothing"    <?php echo (($_POST['category'] ?? '') === 'Clothing')    ? 'selected' : ''; ?>>Clothing</option>
                                    <option value="Documents"   <?php echo (($_POST['category'] ?? '') === 'Documents')   ? 'selected' : ''; ?>>Documents</option>
                                    <option value="Others"      <?php echo (($_POST['category'] ?? '') === 'Others')      ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Location <span class="required">*</span></label>
                            <div class="select-wrapper">
                                <select name="location" required>
                                    <option value="" disabled <?php echo empty($_POST['location']) ? 'selected' : ''; ?>>Select Location</option>
                                    <option value="Library"                <?php echo (($_POST['location'] ?? '') === 'Library')                ? 'selected' : ''; ?>>Library</option>
                                    <option value="Cafeteria"              <?php echo (($_POST['location'] ?? '') === 'Cafeteria')              ? 'selected' : ''; ?>>Cafeteria</option>
                                    <option value="Lecture Hall"           <?php echo (($_POST['location'] ?? '') === 'Lecture Hall')           ? 'selected' : ''; ?>>Lecture Hall</option>
                                    <option value="Faculty of Business"    <?php echo (($_POST['location'] ?? '') === 'Faculty of Business')    ? 'selected' : ''; ?>>Faculty of Business</option>
                                    <option value="Faculty of Engineering" <?php echo (($_POST['location'] ?? '') === 'Faculty of Engineering') ? 'selected' : ''; ?>>Faculty of Engineering</option>
                                    <option value="Student Pavilion"       <?php echo (($_POST['location'] ?? '') === 'Student Pavilion')       ? 'selected' : ''; ?>>Student Pavilion</option>
                                    <option value="Sports Complex"         <?php echo (($_POST['location'] ?? '') === 'Sports Complex')         ? 'selected' : ''; ?>>Sports Complex</option>
                                    <option value="Others"                 <?php echo (($_POST['location'] ?? '') === 'Others')                 ? 'selected' : ''; ?>>Others</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-row two-col">
                        <div class="form-group">
                            <label>Item's Description <span class="required">*</span></label>
                            <textarea name="description" placeholder="Describe item's details" rows="5" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Date of Missing <span class="required">*</span></label>
                            <input type="date" name="date_missing"
                               value="<?php echo htmlspecialchars($_POST['date_missing'] ?? ''); ?>"
                               max="<?php echo date('Y-m-d'); ?>"
                               required>
                            <label style="margin-top:16px; display:block;">Time <span class="required">*</span></label>
                            <input type="time" name="time_missing"
                                   value="<?php echo htmlspecialchars($_POST['time_missing'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Upload + Contact Row -->
                <div class="form-row two-col bottom-cards">

                    <!-- Upload Photo -->
                    <div class="form-card">
                        <h3 class="form-section-title">Upload Photo <span class="required">*</span></h3>
                        <div class="upload-zone" id="uploadZone">
                            <div class="upload-content">
                                <div class="upload-icon">
                                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#bbb" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="16 16 12 12 8 16"/>
                                        <line x1="12" y1="12" x2="12" y2="21"/>
                                        <path d="M20.39 18.39A5 5 0 0 0 18 9h-1.26A8 8 0 1 0 3 16.3"/>
                                    </svg>
                                </div>
                                <p class="upload-text">Drag &amp; drop your photos here</p>
                                <p class="upload-hint">Support for JPG, PNG, WebP files up to 10MB</p>
                                <label class="upload-btn">
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21,15 16,10 5,21"/></svg>
                                    Choose Photos
                                    <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/png,image/webp" style="display:none;">
                                </label>
                            </div>
                            <div id="previewContainer" class="preview-container" style="display:none;">
                                <img id="previewImg" src="" alt="Preview">
                                <button type="button" class="remove-photo" id="removePhoto">&#x2715;</button>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information -->
                    <div class="form-card">
                        <h3 class="form-section-title">Contact Information <span class="required">*</span></h3>
                        <div class="form-group">
                            <label>Name <span class="required">*</span></label>
                            <input type="text" name="contact_name" placeholder="Your name"
                                   value="<?php echo htmlspecialchars($_POST['contact_name'] ?? ($_SESSION['fullname'] ?? '')); ?>" required>
                        </div>
                        <div class="form-group" style="margin-top:16px;">
                            <label>Contact <span class="required">*</span></label>
                            <input type="text" name="contact_info" placeholder="your.email@gmail.com / 01X-XXXXXX"
                                   value="<?php echo htmlspecialchars($_POST['contact_info'] ?? ''); ?>" required>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="form-submit-row">
                    <button type="submit" class="submit-btn">Submit</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
const uploadZone  = document.getElementById('uploadZone');
const photoInput  = document.getElementById('photoInput');
const previewCont = document.getElementById('previewContainer');
const previewImg  = document.getElementById('previewImg');
const removePhoto = document.getElementById('removePhoto');

function showPreview(file) {
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        previewCont.style.display = 'flex';
        uploadZone.classList.add('has-preview');
    };
    reader.readAsDataURL(file);
}

photoInput.addEventListener('change', function () {
    if (this.files[0]) {
        showPreview(this.files[0]);
        uploadZone.classList.remove('upload-error');
    }
});

removePhoto.addEventListener('click', function () {
    photoInput.value = '';
    previewImg.src   = '';
    previewCont.style.display = 'none';
    uploadZone.classList.remove('has-preview');
});

uploadZone.addEventListener('dragover',  e => { e.preventDefault(); uploadZone.classList.add('drag-over'); });
uploadZone.addEventListener('dragleave', ()  => uploadZone.classList.remove('drag-over'));
uploadZone.addEventListener('drop', e => {
    e.preventDefault();
    uploadZone.classList.remove('drag-over');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        photoInput.files = dt.files;
        showPreview(file);
    }
});

document.getElementById('reportLostForm').addEventListener('submit', function(e) {
    let valid = true;
    this.querySelectorAll('[required]').forEach(f => {
        f.classList.remove('input-error');
        if (!f.value.trim()) { f.classList.add('input-error'); valid = false; }
    });
    // Check photo is uploaded
    if (!photoInput.files || photoInput.files.length === 0) {
        uploadZone.classList.add('upload-error');
        valid = false;
    } else {
        uploadZone.classList.remove('upload-error');
    }
    if (!valid) { e.preventDefault(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
});
</script>
</body>
</html>