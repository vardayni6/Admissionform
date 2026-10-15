<?php
require_once "auth.php";
require_once "db.php";
require_once "session.php";

$student_id = $_SESSION["student_id"];

/* =====================================================
   1. STUDENT + APPLICATION STATUS
   “Is the currently logged-in student’s application submitted? 
   If yes, when was it submitted and what is their application number?”
===================================================== */

$sql  = "SELECT application_submitted, submitted_at, application_number FROM students WHERE student_id = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* student row missing (deleted account) -> logout */
if (!$student) {
    header("Location: logout.php");
    exit();
}

$application_submitted = ($student["application_submitted"] == 1);


/* =====================================================
   2. PERSONAL DETAILS FILLED?
===================================================== */

$stmt = mysqli_prepare($conn, "SELECT id FROM personal_details WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
$personal_completed = (mysqli_stmt_num_rows($stmt) > 0);
mysqli_stmt_close($stmt);


/* =====================================================
   3. ACADEMIC DETAILS FILLED?  (also get selected course)
===================================================== */

$course_branch = "";

$stmt = mysqli_prepare($conn, "SELECT course_branch FROM academic_details WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$academic = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$academic_completed = ($academic ? true : false);

if ($academic) {
    $course_branch = $academic["course_branch"];
}


/* =====================================================
   4. DOCUMENTS - all 6 uploaded?
===================================================== */

$stmt = mysqli_prepare($conn, "SELECT COUNT(*) AS total FROM student_documents WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

$uploaded_count      = (int)$row["total"];
$documents_completed = ($uploaded_count >= 6);


/* =====================================================
   5. STATUS TEXT
===================================================== */

$all_completed = ($personal_completed && $academic_completed && $documents_completed);

if ($application_submitted) {
    $application_status = "Submitted";
} elseif ($all_completed) {
    $application_status = "Ready to Submit";
} else {
    $application_status = "In Process";
}

/* messages coming from other pages */
$message      = "";
$message_type = "";

if (isset($_GET["submitted"])) {
    $message      = "Your application has been submitted successfully.";
    $message_type = "success";
}

if (isset($_GET["error"])) {
    $message      = $_GET["error"];
    $message_type = "error";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<main class="dashboard">
    <div class="dashboard-container">

        <!-- ============ WELCOME ============ -->
        <section class="welcome-section">
            <div>
                <p class="welcome-small">🎓 Admission Dashboard</p>
                <h1>Welcome, <?php echo h($_SESSION["fullname"]); ?> 👋</h1>
                <p class="welcome-text">
                    Application Status:
                    <span class="status-badge status-<?php echo $application_submitted ? "done" : "pending"; ?>">
                        <?php echo h($application_status); ?>
                    </span>
                </p>
            </div>
        </section>

        <?php if ($message != "") { ?>
            <div class="message <?php echo $message_type; ?>"><?php echo h($message); ?></div>
        <?php } ?>

        <!-- ============ SUBMITTED BOX ============ -->
        <?php if ($application_submitted) { ?>
            <section class="submitted-box">
                <h2>✅ Application submitted successfully</h2>
                <p>Application Number: <strong><?php echo h($student["application_number"]); ?></strong></p>
                <?php if ($course_branch != "") { ?>
                    <p>Selected Course: <strong><?php echo h($course_branch); ?></strong></p>
                <?php } ?>
                <p>Submitted on: <?php echo h(date("d M Y, h:i A", strtotime($student["submitted_at"]))); ?></p>
            </section>
        <?php } ?>

        <!-- ============ 3 CARDS ============ -->
        <section class="dashboard-section">
            <h2 class="section-title">
                <?php echo $application_submitted ? "Your Application" : "Complete Your Application"; ?>
            </h2>

            <div class="dashboard-cards">

                <!-- PERSONAL -->
                <div class="dashboard-card">
                    <div class="card-icon">📝</div>
                    <h3>Personal Details</h3>
                    <p>Personal, family and address information.</p>
                    <span class="tag <?php echo $personal_completed ? "tag-done" : "tag-pending"; ?>">
                        <?php echo $personal_completed ? "✓ Completed" : "Pending"; ?>
                    </span>
                    <?php if ($application_submitted) { ?>
                        <a href="profile.php" class="dashboard-button">View</a>
                    <?php } else { ?>
                        <a href="personal.php" class="dashboard-button">
                            <?php echo $personal_completed ? "Edit Details →" : "Fill Details →"; ?>
                        </a>
                    <?php } ?>
                </div>

                <!-- ACADEMIC -->
                <div class="dashboard-card">
                    <div class="card-icon">🎓</div>
                    <h3>Academic Details</h3>
                    <p>SSC, HSC / Diploma marks and course selection.</p>
                    <span class="tag <?php echo $academic_completed ? "tag-done" : "tag-pending"; ?>">
                        <?php echo $academic_completed ? "✓ Completed" : "Pending"; ?>
                    </span>
                    <?php if ($application_submitted) { ?>
                        <a href="profile.php" class="dashboard-button">View</a>
                    <?php } else { ?>
                        <a href="academic.php" class="dashboard-button">
                            <?php echo $academic_completed ? "Edit Details →" : "Fill Details →"; ?>
                        </a>
                    <?php } ?>
                </div>

                <!-- DOCUMENTS -->
                <div class="dashboard-card">
                    <div class="card-icon">📄</div>
                    <h3>Documents</h3>
                    <p>Photo, SSC, HSC/Diploma, caste, Aadhaar and PAN.</p>
                    <span class="tag <?php echo $documents_completed ? "tag-done" : "tag-pending"; ?>">
                        <?php echo $documents_completed ? "✓ All 6 uploaded" : $uploaded_count . " of 6 uploaded"; ?>
                    </span>
                    <a href="documents.php" class="dashboard-button">
                        <?php
                        if ($application_submitted) {
                            echo "View";
                        } else {
                            echo $documents_completed ? "Manage Documents →" : "Upload Documents →";
                        }
                        ?>
                    </a>
                </div>

            </div>
        </section>

        <!-- ============ FINAL SUBMIT ============ -->
        <?php if (!$application_submitted) { ?>
            <section class="submit-section">

                <?php if ($all_completed) { ?>

                    <p>All steps are completed. Check your details once, then submit your application.
                       You cannot edit it after submission.</p>

                    <form action="submit.php" method="POST"
                          onsubmit="return confirm('Submit your application? You cannot edit it after this.');">
                        <button type="submit" class="submit-button">Submit Application</button>
                    </form>

                <?php } else { ?>

                    <p>Complete Personal Details, Academic Details and upload all 6 documents to submit your application.</p>
                    <button type="button" class="submit-button disabled" disabled>Submit Application</button>

                <?php } ?>

            </section>
        <?php } ?>

    </div>
</main>

<?php require_once "footer.php"; ?>
</body>
</html>
