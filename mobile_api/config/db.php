<?php
// =============================================
// KindAid Mobile API - Database Configuration
// Reuses the shared DB connection from the web app.
// admin/config/db.php already handles connection errors.
// =============================================
include __DIR__ . "/../../admin/config/db.php";

// Base URL for media files (images/docs) dynamically assigned
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define("BASE_URL",    $protocol . $host . "/KindAid/");
define("UPLOADS_URL", BASE_URL . "uploads/");
define("DOCS_URL",    BASE_URL . "uploads/doc/");
