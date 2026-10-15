<?php
require_once "session.php";

/* remove only the student login, keep everything else */
unset($_SESSION["student_id"], $_SESSION["fullname"]);

header("Location: signin.php?loggedout=1");
exit();
