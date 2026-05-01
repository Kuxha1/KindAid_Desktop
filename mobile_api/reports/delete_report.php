<?php
// =============================================
// POST /mobile_api/reports/delete_report.php
// Body: report_id
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_error("Method not allowed. Use POST.", 405);
}

$auth       = require_auth();
$account_id = (int)$auth['account_id'];
$report_id  = (int)($_POST['report_id'] ?? 0);

if (!$report_id) {
    send_error("report_id is required.");
}

$conn->query("
    DELETE FROM reports
    WHERE report_id=$report_id AND reported_by=$account_id
");

if ($conn->affected_rows > 0) {
    send_success(null, "Report deleted.");
} else {
    send_error("Report not found or not yours.", 404);
}
