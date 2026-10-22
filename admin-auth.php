
<?php
/* =====================================================
   ADMIN AUTHENTICATION GUARD
   Include this file at the top of every admin page.
===================================================== */

require_once __DIR__ . "/session.php";
require_once __DIR__ . "/jwt_config.php";
require_once __DIR__ . "/vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Function to redirect unauthenticated users.
function redirectAdminToLogin()
{
    // Remove the JWT cookie.
    setcookie(JWT_COOKIE_NAME, "", [
        "expires"  => time() - 3600,
        "path"     => JWT_COOKIE_PATH,
        "httponly" => true,
        "secure"   => false,
        "samesite" => "Lax"
    ]);

    // Clear the session.
    $_SESSION = [];
    session_destroy();

    header("Location: admin-signin.php");
    exit();
}

// 1. Check whether an admin session exists.
if (!isset($_SESSION["admin_id"])) {
    redirectAdminToLogin();
}

// 2. Read the JWT cookie.
$token = $_COOKIE[JWT_COOKIE_NAME] ?? "";

// Reject a missing token.
if (!is_string($token) || $token === "") {
    redirectAdminToLogin();
}

try {
    // 3. Verify the JWT signature and expiration.
    $decoded = JWT::decode(
        $token,
        new Key(JWT_SECRET, "HS256")
    );

    // 4. Ensure the token belongs to this admin.
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
    // Invalid, expired or incorrectly signed token.
    redirectAdminToLogin();
}
