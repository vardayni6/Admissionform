<?php
require_once __DIR__ . "/session.php";
require_once __DIR__ . "/jwt_config.php";

setcookie(JWT_COOKIE_NAME, "", [
    "expires" => time() - 3600,
    "path" => JWT_COOKIE_PATH,
    "httponly" => true,
    "secure" => false,
    "samesite" => "Lax"
]);

$_SESSION = [];
session_destroy();

header("Location: admin-signin.php?loggedout=1");
exit();