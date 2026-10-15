<?php
require_once "db.php";
require_once "session.php";

/* already logged in? go to dashboard */
if (isset($_SESSION["student_id"])) {
    header("Location: index.php");
    exit();
}

$message      = "";
$message_type = "";
$email        = "";

if (isset($_GET["registered"])) {
    $message      = "Registration successful. Please sign in.";
    $message_type = "success";
}

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

        /* find the student with this email */
        $sql  = "SELECT student_id, fullname, password FROM students WHERE email = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $student = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        /* check the password */
        if ($student && password_verify($password, $student["password"])) {

            session_regenerate_id(true);

            $_SESSION["student_id"] = $student["student_id"];
            $_SESSION["fullname"]   = $student["fullname"];

            header("Location: index.php");
            exit();

        } else {

            $message      = "Invalid email or password.";
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
    <title>Student Sign In | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<main class="auth-page">
    <div class="auth-card">

        <div class="auth-icon">🎓</div>
        <h1>Student Sign In</h1>
        <p class="subtitle">Welcome back! Sign in to continue your admission</p>

        <?php if ($message != "") { ?>
            <div class="message <?php echo $message_type; ?>"><?php echo h($message); ?></div>
        <?php } ?>

        <form method="POST" novalidate>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email"
                       value="<?php echo h($email); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Enter your password">
                    <button type="button" class="show-password" onclick="togglePassword('password', this)">Show</button>
                </div>
            </div>

            <button type="submit" class="primary-button">Sign In</button>

        </form>

        <p class="bottom-text">New student? <a href="registration.php">Register here</a></p>
        <p class="bottom-text"><a href="admin-signin.php">Admin Login &rarr;</a></p>

    </div>
</main>

<?php require_once "footer.php"; ?>
<script src="validation.js"></script>
</body>
</html>
