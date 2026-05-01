<?php
// =============================================
// KindAid Mobile API - JSON Response Helper
// =============================================

function send_response($success, $message = "", $data = null, $code = 200) {
    http_response_code($code);
    header("Content-Type: application/json");
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");

    $response = [
        "success" => $success,
        "message" => $message,
    ];

    if ($data !== null) {
        $response["data"] = $data;
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

function send_success($data = null, $message = "Success") {
    send_response(true, $message, $data, 200);
}

function send_error($message = "Error", $code = 400) {
    send_response(false, $message, null, $code);
}

function send_unauthorized($message = "Unauthorized. Please login.") {
    send_response(false, $message, null, 401);
}

// Handle OPTIONS preflight for CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type");
    http_response_code(200);
    exit();
}
