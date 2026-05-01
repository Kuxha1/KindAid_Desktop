<?php
// =============================================
// KindAid Mobile API - Auth Middleware
// Validates Bearer JWT token on protected routes
// =============================================
require_once __DIR__ . "/../helpers/response.php";
require_once __DIR__ . "/../helpers/auth.php";

function require_auth() {
    $token = get_bearer_token();

    if (!$token) {
        send_unauthorized("No token provided. Please login.");
    }

    $payload = verify_jwt($token);

    if (!$payload) {
        send_unauthorized("Invalid or expired token. Please login again.");
    }

    return $payload; // Returns ['account_id', 'email', 'iat', 'exp']
}
