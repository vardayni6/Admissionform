<?php
require_once "db.php";
require_once "session.php";

/* already logged in as admin? go to admin dashboard */
if (isset($_SESSION["admin_id"])) {
    header("Location: admin-dashboard.php");
    exit();
}

$message      = "";
$message_type = "";
$email        = "";

if (isset($_GET["loggedout"])) {
    $message      = "You have been logged out.";
    $message_type = "success";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email    = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($email == "" || $password == "") {

        $message      = "Please enter email and password.";
        $message_type = "error";

    } else {

        /* find the admin with this email (admins table) */
        $sql  = "SELECT admin_id, fullname, password FROM admins WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $admin = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($admin && password_verify($password, $admin["password"])) {

            session_regenerate_id(true);

            $_SESSION["admin_id"]   = $admin["admin_id"];
            $_SESSION["admin_name"] = $admin["fullname"];

            header("Location: admin-dashboard.php");
            exit();

        } else {

            $message      = "Invalid admin email or password.";
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<main class="auth-page">
    <div class="auth-card">

        <div class="auth-icon">🛠️</div>
        <h1>Admin Login</h1>
        <p class="subtitle">Sign in to manage student admissions</p>

        <?php if ($message != "") { ?>
            <div class="message <?php echo $message_type; ?>"><?php echo h($message); ?></div>
        <?php } ?>

        <form method="POST" novalidate>

            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" placeholder="Enter admin email"
                       value="<?php echo h($email); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter password">
                    <button type="button" class="show-password" onclick="togglePassword('password', this)">Show</button>
                </div>
            </div>

            <button type="submit" class="primary-button">Login</button>

        </form>

        <p class="bottom-text"><a href="signin.php">← Student Sign In</a></p>

    </div>
</main>

<?php require_once "footer.php"; ?>
<script src="validation.js"></script>
</body>
</html>
