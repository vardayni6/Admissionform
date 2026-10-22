
<?php

require_once __DIR__ . "/session.php";
require_once __DIR__ . "/jwt_config.php";
require_once __DIR__ . "/vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// If the admin is not logged in, reject the request.
if (!isset($_SESSION["admin_id"])) {
    header("Location: admin-signin.php");
    exit();
}

// Read the JWT from the browser cookie.
$token = $_COOKIE[JWT_COOKIE_NAME] ?? "";

// A missing token means the admin is not authenticated.
if (!is_string($token) || $token === "") {
    session_unset();
    session_destroy();

    header("Location: admin-signin.php");
    exit();
}

try {
    // Verify the signature and expiry using HS256.
    $decoded = JWT::decode(
        $token,
        new Key(JWT_SECRET, "HS256")
    );

    // Check that the token belongs to this session's admin.
    if (
        !isset($decoded->sub) ||
        (string) $decoded->sub !== (string) $_SESSION["admin_id"] ||
        !isset($decoded->iss) ||
        $decoded->iss !== JWT_ISSUER
    ) {
        throw new UnexpectedValueException(
            "Invalid admin token"
        );
    }

} catch (Throwable $e) {
    // The token is invalid, expired or could not be verified.
    setcookie(JWT_COOKIE_NAME, "", [
        "expires" => time() - 3600,
        "path" => JWT_COOKIE_PATH,
        "httponly" => true,
        "samesite" => "Lax",
        "secure" => false
    ]);

    session_unset();
    session_destroy();

    header("Location: admin-signin.php");
    exit();
}
