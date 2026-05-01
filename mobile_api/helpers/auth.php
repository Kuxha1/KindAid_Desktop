<?php
// =============================================
// KindAid Mobile API - JWT Auth Helper
// =============================================

define("JWT_SECRET", "KindAid_Secret_2024_XYZ!@#");
define("JWT_EXPIRY", 60 * 60 * 24 * 30); // 30 days

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    $padded = str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT);
    return base64_decode($padded);
}

/**
 * Generate a JWT token
 */
function generate_jwt($account_id, $email) {
    $header = json_encode(["typ" => "JWT", "alg" => "HS256"]);
    $payload = json_encode([
        "account_id" => $account_id,
        "email"      => $email,
        "iat"        => time(),
        "exp"        => time() + JWT_EXPIRY
    ]);

    $base = base64url_encode($header) . "." . base64url_encode($payload);
    $signature = hash_hmac("sha256", $base, JWT_SECRET, true);

    return $base . "." . base64url_encode($signature);
}

/**
 * Verify and decode a JWT token.
 * Returns payload array on success, false on failure.
 */
function verify_jwt($token) {
    $parts = explode(".", $token);
    if (count($parts) !== 3) return false;

    [$header, $payload, $sig] = $parts;

    // Verify signature
    $base = $header . "." . $payload;
    $expected = base64url_encode(hash_hmac("sha256", $base, JWT_SECRET, true));

    if (!hash_equals($expected, $sig)) return false;

    // Decode payload
    $data = json_decode(base64url_decode($payload), true);
    if (!$data) return false;

    // Check expiry
    if (isset($data['exp']) && time() > $data['exp']) return false;

    return $data;
}

/**
 * Extract token from Authorization header
 */
function get_bearer_token() {
    $headers = null;

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $headers = $_SERVER['HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $headers = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    } elseif (isset($_SERVER['Authorization'])) {
        $headers = $_SERVER['Authorization'];
    } elseif (function_exists('getallheaders')) {
        // Case-insensitive header search
        $allHeaders = getallheaders();
        foreach ($allHeaders as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $headers = $value;
                break;
            }
        }
    } elseif (function_exists('apache_request_headers')) {
        $req = apache_request_headers();
        if (isset($req['Authorization'])) {
            $headers = $req['Authorization'];
        }
    }

    if ($headers && preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
        return $matches[1];
    }

    return null;
}
