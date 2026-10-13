<?php
require_once "auth.php";
require_once "db.php";
require_once "session.php";

$student_id = $_SESSION["student_id"];

/* small function: print one "label : value" row */
function showRow($label, $value)
{
    if ($value === null || $value === "") {
        $value = "-";
    }
    echo '<div class="info-row"><span>' . h($label) . '</span><strong>' . h($value) . '</strong></div>';
}

/* ---------- registration details ---------- */

$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: logout.php");
    exit();
}

$locked = ($student["application_submitted"] == 1);

/* ---------- personal details ---------- */

$stmt = mysqli_prepare($conn, "SELECT * FROM personal_details WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$personal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* ---------- academic details ---------- */

$stmt = mysqli_prepare($conn, "SELECT * FROM academic_details WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$academic = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<div class="form-container">

    <div class="page-header">
        <h1>👤 My Profile</h1>
        <p>
            <?php echo $locked ? "Your application is submitted. Details can only be viewed." : "View your admission information"; ?>
        </p>
    </div>

    <!-- ============ REGISTRATION ============ -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">01</div>
            <h2>Registration Details</h2>
        </div>
        <div class="section-body">
            <?php
            showRow("Full Name", $student["fullname"]);
            showRow("Email", $student["email"]);
            showRow("Mobile", $student["mobile"]);
            showRow("Registered On", date("d M Y", strtotime($student["created_at"])));
            if ($locked) {
                showRow("Application Number", $student["application_number"]);
            }
            ?>
        </div>
    </div>

    <!-- ============ PERSONAL ============ -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">02</div>
            <h2>Personal, Family &amp; Address Details</h2>
            <?php if (!$locked) { ?>
                <a href="personal.php" class="edit-btn">Edit</a>
            <?php } ?>
        </div>
        <div class="section-body">
            <?php if ($personal) {
                showRow("Date of Birth", date("d M Y", strtotime($personal["date_of_birth"])));
                showRow("Gender", $personal["gender"]);
                showRow("Nationality", $personal["nationality"]);
                showRow("Mother Tongue", $personal["mother_tongue"]);
                showRow("Blood Group", $personal["blood_group"]);
                showRow("Aadhaar Number", $personal["aadhaar_number"]);
                showRow("Father's Name", $personal["father_name"]);
                showRow("Mother's Name", $personal["mother_name"]);
                showRow("Guardian's Name", $personal["guardian_name"]);
                showRow("Father's Occupation", $personal["father_occupation"]);
                showRow("Mother's Occupation", $personal["mother_occupation"]);
                showRow("Annual Family Income", $personal["annual_family_income"]);
                showRow("Parent / Guardian Mobile", $personal["parent_guardian_mobile"]);

                $permanent = trim($personal["permanent_address_line1"] . ", " . $personal["permanent_address_line2"] . ", "
                    . $personal["permanent_city_village"] . ", " . $personal["permanent_taluka"] . ", "
                    . $personal["permanent_district"] . ", " . $personal["permanent_state"] . ", "
                    . $personal["permanent_country"] . " - " . $personal["permanent_pin_code"], " ,");
                $permanent = str_replace(",  ,", ",", $permanent);

                $temporary = trim($personal["temporary_address_line1"] . ", " . $personal["temporary_address_line2"] . ", "
                    . $personal["temporary_city_village"] . ", " . $personal["temporary_taluka"] . ", "
                    . $personal["temporary_district"] . ", " . $personal["temporary_state"] . ", "
                    . $personal["temporary_country"] . " - " . $personal["temporary_pin_code"], " ,");
                $temporary = str_replace(",  ,", ",", $temporary);

                showRow("Permanent Address", $permanent);
                showRow("Temporary Address", $temporary);
            } else { ?>
                <p class="empty-text">Personal details are not filled yet. <a href="personal.php">Fill now →</a></p>
            <?php } ?>
        </div>
    </div>

    <!-- ============ ACADEMIC ============ -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">03</div>
            <h2>Academic Details</h2>
            <?php if (!$locked) { ?>
                <a href="academic.php" class="edit-btn">Edit</a>
            <?php } ?>
        </div>
        <div class="section-body">
            <?php if ($academic) {
                showRow("SSC School", $academic["ssc_school"]);
                showRow("SSC Board", $academic["ssc_board"]);
                showRow("SSC Passing Year", $academic["ssc_passing_year"]);
                showRow("SSC Marks", $academic["ssc_marks_obtained"] . " / " . $academic["ssc_total_marks"] . "  (" . $academic["ssc_percentage"] . "%)");
                showRow("Qualification", $academic["hsc_qualification"]);
                showRow("College / Institute", $academic["hsc_college"]);
                showRow("Board / University", $academic["hsc_board_university"]);
                showRow("Stream / Branch", $academic["hsc_stream_branch"]);
                showRow("Passing Year", $academic["hsc_passing_year"]);
                showRow("Marks", $academic["hsc_marks_obtained"] . " / " . $academic["hsc_total_marks"] . "  (" . $academic["hsc_percentage"] . "%)");
                showRow("Admission Type", $academic["admission_type"]);
                showRow("Course", $academic["course_branch"]);
                showRow("Preferred College", $academic["preferred_college"]);
            } else { ?>
                <p class="empty-text">Academic details are not filled yet. <a href="academic.php">Fill now →</a></p>
            <?php } ?>
        </div>
    </div>

    <!-- ============ DOCUMENTS ============ -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">04</div>
            <h2>Documents</h2>
            <a href="documents.php" class="edit-btn"><?php echo $locked ? "View" : "Manage"; ?></a>
        </div>
    </div>

    <div class="button-area">
        <a href="index.php" class="back-btn">← Back to Dashboard</a>
    </div>

</div>

<?php require_once "footer.php"; ?>
</body>
</html>
