<?php
require_once "auth.php";
require_once "db.php";
require_once "session.php";

$student_id = $_SESSION["student_id"];

/* only the "Submit Application" button (POST) can open this page */
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: index.php");
    exit();
}

/* ---------- already submitted? ---------- */

$stmt = mysqli_prepare($conn, "SELECT application_submitted FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student || $student["application_submitted"] == 1) {
    header("Location: index.php");
    exit();
}

/* ---------- is everything completed? ---------- */

$stmt = mysqli_prepare($conn, "SELECT id FROM personal_details WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$personal_ok = (mysqli_stmt_num_rows($stmt) > 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT id FROM academic_details WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$academic_ok = (mysqli_stmt_num_rows($stmt) > 0);
mysqli_stmt_close($stmt);

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM student_documents WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
$documents_ok = ((int)$row["total"] >= 6);

if (!$personal_ok || !$academic_ok || !$documents_ok) {
    header("Location: index.php?error=" . urlencode("Please complete all steps before submitting."));
    exit();
}

/* ---------- create application number and submit ----------
   example: ADM2026000005  = ADM + year + student id (6 digits) */

$application_number = "ADM" . date("Y") . str_pad($student_id, 6, "0", STR_PAD_LEFT);
$submitted_at       = date("Y-m-d H:i:s");

$sql = "UPDATE students
        SET application_submitted = 1, submitted_at = ?, application_number = ?
        WHERE student_id = ? AND application_submitted = 0";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ssi", $submitted_at, $application_number, $student_id);

if (mysqli_stmt_execute($stmt)) {
    header("Location: index.php?submitted=1");
} else {
    header("Location: index.php?error=" . urlencode("Could not submit the application. Please try again."));
}

mysqli_stmt_close($stmt);
exit();
