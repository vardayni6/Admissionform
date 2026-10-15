<?php
require_once "db.php";
require_once "session.php";

/* already logged in? go to dashboard */
if (isset($_SESSION["student_id"])) {
    header("Location: index.php");
    exit();
}

$message  = "";
$fullname = "";
$email    = "";
$mobile   = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname         = trim($_POST["fullname"] ?? "");
    $email            = trim($_POST["email"] ?? "");
    $mobile           = trim($_POST["mobile"] ?? "");
    $password         = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    /* ---------- VALIDATION ---------- */

    if ($fullname == "") {
        $message = "Full name is required.";

    } elseif (strlen($fullname) < 3 || strlen($fullname) > 30) {
        $message = "Full name must be between 3 and 30 characters.";

    } elseif (!preg_match("/^[A-Za-z ]+$/", $fullname)) {
        $message = "Full name can contain only letters and spaces.";

    } elseif ($email == "" || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";

    } elseif (strlen($email) > 50) {
        $message = "Email must not exceed 50 characters.";

    } elseif (!preg_match("/^[6-9][0-9]{9}$/", $mobile)) {
        $message = "Enter a valid 10-digit mobile number.";

    } elseif (strlen($password) < 8 || strlen($password) > 20) {
        $message = "Password must be between 8 and 20 characters.";

    } elseif (!preg_match("/[A-Z]/", $password)) {
        $message = "Password must contain at least one uppercase letter.";

    } elseif (!preg_match("/[a-z]/", $password)) {
        $message = "Password must contain at least one lowercase letter.";

    } elseif (!preg_match("/[0-9]/", $password)) {
        $message = "Password must contain at least one number.";

    } elseif (!preg_match("/[^A-Za-z0-9]/", $password)) {
        $message = "Password must contain at least one special character.";

    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";

    } else {

        /* ---------- EMAIL OR MOBILE ALREADY USED? ---------- */

        $sql  = "SELECT student_id FROM students WHERE email = ? OR mobile = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $email, $mobile);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        if (mysqli_stmt_num_rows($stmt) > 0) {

            $message = "This email or mobile number is already registered.";

        } else {

            /* ---------- SAVE STUDENT ---------- */

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $sql  = "INSERT INTO students (fullname, email, mobile, password) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "ssss", $fullname, $email, $mobile, $hashed_password);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: signin.php?registered=1");
                exit();
            } else {
                $message = "Registration failed. Please try again.";
            }
        }

        mysqli_stmt_close($stmt);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<main class="auth-page">
    <div class="auth-card">

        <div class="auth-icon">🎓</div>
        <h1>Student Registration</h1>
        <p class="subtitle">Create your account to start the admission</p>

        <?php if ($message != "") { ?>
            <div class="message error"><?php echo h($message); ?></div>
        <?php } ?>

        <form method="POST" novalidate>

            <div class="form-group">
                <label for="fullname">Full Name</label>
                <input type="text" id="fullname" name="fullname" placeholder="Enter your full name"
                       maxlength="30" value="<?php echo h($fullname); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" placeholder="Enter your email address"
                       maxlength="50" value="<?php echo h($email); ?>">
            </div>

            <div class="form-group">
                <label for="mobile">Mobile Number</label>
                <input type="text" id="mobile" name="mobile" placeholder="10-digit mobile number"
                       maxlength="10" value="<?php echo h($mobile); ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-wrapper">
                    <input type="password" id="password" name="password" placeholder="Create a strong password" maxlength="20">
                    <button type="button" class="show-password" onclick="togglePassword('password', this)">Show</button>
                </div>
                <div class="password-rules">
                    8-20 characters, with one uppercase, one lowercase, one number and one special character.
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-wrapper">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Re-enter your password" maxlength="20">
                    <button type="button" class="show-password" onclick="togglePassword('confirm_password', this)">Show</button>
                </div>
            </div>

            <button type="submit" class="primary-button">Create Account</button>

        </form>

        <p class="bottom-text">Already registered? <a href="signin.php">Sign In</a></p>

    </div>
</main>

<?php require_once "footer.php"; ?>
<script src="validation.js"></script>
</body>
</html>
