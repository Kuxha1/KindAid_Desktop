<?php
// =============================================
// POST /mobile_api/reports/report.php
// Submit a report for any content type
// Body: target_type, target_id, reason
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth        = require_auth();
$reported_by = (int)$auth['account_id'];

$target_type = $conn->real_escape_string($_POST['target_type'] ?? '');
$target_id   = (int)($_POST['target_id'] ?? 0);
$reason      = $conn->real_escape_string(trim($_POST['reason'] ?? ''));

$valid_types = ['Account', 'need', 'Post', 'Fundraiser', 'Comment', 'location'];

if (!in_array($target_type, $valid_types)) {
    send_error("Invalid target_type. Must be one of: " . implode(", ", $valid_types));
}

if (!$target_id || empty($reason)) {
    send_error("target_id and reason are required.");
}

// Prevent duplicate reports from the same user
$existing = $conn->query("
    SELECT report_id FROM reports
    WHERE reported_by=$reported_by
    AND target_type='$target_type'
    AND target_id=$target_id
");

if ($existing->num_rows > 0) {
    send_error("You have already reported this item.", 409);
}

$conn->query("
    INSERT INTO reports (reported_by, target_type, target_id, reason)
    VALUES ($reported_by,'$target_type',$target_id,'$reason')
");

send_success(["report_id" => $conn->insert_id], "Report submitted. Admins will review it.");
