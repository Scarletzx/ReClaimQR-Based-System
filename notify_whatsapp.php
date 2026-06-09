<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$current_user_name = $_SESSION["fullname"] ?? "A user";

$item_id   = intval($_GET["item_id"] ?? 0);
$item_type = $_GET["item_type"] ?? "Lost";
$phone     = preg_replace('/[^0-9]/', '', $_GET["phone"] ?? "");

if ($item_id <= 0 || empty($phone)) {
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

// Build WhatsApp message
$wa_text = urlencode(
    "Hello! 👋\n\n" .
    "I found an item on *ReClaimQR* that might be yours!\n\n" .
    "📦 *Item Details:*\n" .
    "• Name     : {$item['item_name']}\n" .
    "• Category : {$item['category']}\n" .
    "• Location : {$item['location']}\n" .
    "• Status   : {$item_type}\n\n" .
    "Please log in to ReClaimQR to claim it or message me directly.\n\n" .
    "— {$current_user_name} via ReClaimQR"
);

// Redirect directly to WhatsApp
$wa_url = "https://wa.me/{$phone}?text={$wa_text}";
header("Location: $wa_url");
exit();