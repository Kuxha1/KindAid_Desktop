<?php
// =============================================
// GET /mobile_api/profile/get_settings.php
// Returns user profile + transactions + reports + comments
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../config/db.php";
require_once __DIR__ . "/../middleware/auth_middleware.php";

$auth       = require_auth();
$account_id = (int)$auth['account_id'];

// ── User profile ──
$user = $conn->query("SELECT * FROM accounts WHERE account_id=$account_id")->fetch_assoc();

if (!$user) send_error("Account not found.", 404);

$profile = [
    "account_id"      => (int)$user['account_id'],
    "name"            => $user['name'],
    "email"           => $user['email'],
    "account_type"    => $user['account_type'],
    "verified_status" => $user['verified_status'],
    "bio"             => $user['bio'] ?? '',
    "contact_no"      => $user['contact_no'] ?? '',
    "address"         => $user['address'] ?? '',
    "profile_photo"   => !empty($user['profile_photo'])
                            ? UPLOADS_URL . $user['profile_photo']
                            : BASE_URL . "assets/default-user.png",
    "document_1"      => !empty($user['document_1']) ? DOCS_URL . $user['document_1'] : null,
    "document_2"      => !empty($user['document_2']) ? DOCS_URL . $user['document_2'] : null,
    "created_at"      => $user['created_at'],
];

// ── Transactions ──
$t_res = $conn->query("
    SELECT transactions.*, fundraiser.title
    FROM transactions
    JOIN fundraiser ON transactions.fundraiser_id = fundraiser.fundraiser_id
    WHERE donor_id = $account_id
    ORDER BY transaction_date DESC
");
$transactions = [];
while ($t = $t_res->fetch_assoc()) {
    $transactions[] = [
        "transaction_id" => (int)$t['transaction_id'],
        "fundraiser_id"  => (int)$t['fundraiser_id'],
        "title"          => $t['title'],
        "amount"         => (float)$t['amount'],
        "payment_method" => $t['payment_method'],
        "payment_status" => $t['payment_status'],
        "transaction_date" => $t['transaction_date'],
    ];
}

// ── Reports ──
$r_res = $conn->query("
    SELECT * FROM reports
    WHERE reported_by = $account_id
    ORDER BY created_at DESC
");
$reports = [];
while ($r = $r_res->fetch_assoc()) {
    $reports[] = [
        "report_id"   => (int)$r['report_id'],
        "target_type" => $r['target_type'],
        "target_id"   => (int)$r['target_id'],
        "reason"      => $r['reason'],
        "status"      => $r['status'],
        "created_at"  => $r['created_at'],
    ];
}

// ── Comments ──
$c_res = $conn->query("
    SELECT comments.*, community_forum.description AS post_description
    FROM comments
    JOIN community_forum ON comments.post_id = community_forum.post_id
    WHERE comments.account_id = $account_id
    ORDER BY comments.created_at DESC
");
$comments = [];
while ($c = $c_res->fetch_assoc()) {
    $comments[] = [
        "comment_id"       => (int)$c['comment_id'],
        "post_id"          => (int)$c['post_id'],
        "comment_text"     => $c['comment_text'],
        "post_description" => substr($c['post_description'], 0, 60),
        "created_at"       => $c['created_at'],
    ];
}

send_success([
    "profile"      => $profile,
    "transactions" => $transactions,
    "reports"      => $reports,
    "comments"     => $comments,
]);
