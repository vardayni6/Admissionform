<?php
require_once __DIR__ . "/jwt_auth.php";
require_once __DIR__ . "/db.php";

// Continue with the existing page code below.
$current_page = basename($_SERVER["PHP_SELF"]);
?>
<header class="main-header admin-header">
    <div class="header-container">

        <a href="admin-dashboard.php" class="logo">🎓 Admission Dashboard <small>Admin</small></a>

        <nav>
            <a href="admin-dashboard.php" class="<?php echo $current_page == "admin-dashboard.php" ? "active" : ""; ?>">Dashboard</a>
            <a href="admin-students.php" class="<?php echo ($current_page == "admin-students.php" || $current_page == "admin-student-view.php") ? "active" : ""; ?>">Students</a>
        </nav>

        <div class="student-info">
            <span class="student-name"><?php echo h($_SESSION["admin_name"]); ?></span>
            <a href="admin-logout.php" class="logout-link">Logout</a>
        </div>

    </div>
</header>
