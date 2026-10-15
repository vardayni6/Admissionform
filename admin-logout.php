<?php
require_once "session.php";

unset($_SESSION["admin_id"], $_SESSION["admin_name"]);

header("Location: admin-signin.php?loggedout=1");
exit();
