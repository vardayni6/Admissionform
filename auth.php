<?php
/* =====================================================
   STUDENT GUARD
   Put   require_once "auth.php";   at the top of every
   page that only a logged-in student can open.
===================================================== */

require_once "session.php";

if (!isset($_SESSION["student_id"])) {
    header("Location: signin.php");
    exit();
}
