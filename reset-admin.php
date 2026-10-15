<?php
/* =====================================================
   RUN THIS PAGE ONLY ONCE:  http://localhost/admissionform/reset-admin.php

   It sets the password of the admin  admin@example.com
   to  Admin@123   (so you are sure you can log in).
   The file deletes itself after running.
===================================================== */

require_once "db.php";

$email        = "admin@example.com";
$new_password = "Admin@123";
$hashed       = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = mysqli_prepare($conn, "UPDATE admins SET password = ? WHERE email = ?");
mysqli_stmt_bind_param($stmt, "ss", $hashed, $email);
mysqli_stmt_execute($stmt);
$found = (mysqli_stmt_affected_rows($stmt) > 0);
mysqli_stmt_close($stmt);

if ($found) {

    echo "Done. Admin login ->  Email: " . $email . "  |  Password: " . $new_password;
    echo "<br><br><a href='admin-signin.php'>Go to Admin Login</a>";

    /* delete this file so nobody else can use it */
    @unlink(__FILE__);

} else {
    echo "Admin not found. Import admissionform.sql into the database first.";
}
