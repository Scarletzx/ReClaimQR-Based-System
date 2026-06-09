<?php
require_once "db.php";

if ($conn) {
    echo "Database connection SUCCESS ✅";
} else {
    echo "Database connection FAILED ❌";
}
?>