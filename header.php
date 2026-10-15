<?php
require_once "session.php";
require_once "db.php";
?>
<header class="main-header">
    <div class="header-container">

        <a href="<?php echo isset($_SESSION["student_id"]) ? "index.php" : "signin.php"; ?>" class="logo">
            🎓 Admission Dashboard
        </a>

        <?php if (isset($_SESSION["student_id"])) { ?>

            <nav>
                <a href="index.php">Home</a>
                <a href="aboutus.php">About Us</a>
                <a href="profile.php">Profile</a>
            </nav>

            <div class="student-info">
                <span class="student-name"><?php echo h($_SESSION["fullname"]); ?></span>
                <a href="logout.php" class="logout-link">Logout</a>
            </div>

        <?php } else { ?>

            <div class="auth-links">
                <a href="signin.php">Student Sign In</a>
                <a href="registration.php" class="register-link">Register</a>
                <a href="admin-signin.php">Admin Login</a>
            </div>

        <?php } ?>

    </div>
</header>
