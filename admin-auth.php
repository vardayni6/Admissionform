<?php
/* =====================================================
   ADMIN GUARD
   Put   require_once "admin-auth.php";   at the top of
   every admin page.
===================================================== */

require_once "session.php";

if (!isset($_SESSION["admin_id"])) {
    header("Location: admin-signin.php");
    exit();
}
