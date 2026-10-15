<?php
require_once "admin-auth.php";
require_once "db.php";
require_once "session.php";

$student_id = (int)($_GET["id"] ?? 0);

/* small function: print one "label : value" row */
function showRow($label, $value)
{
    if ($value === null || $value === "") {
        $value = "-";
    }
    echo '<div class="info-row"><span>' . h($label) . '</span><strong>' . h($value) . '</strong></div>';
}

/* ---------- student ---------- */

$stmt = mysqli_prepare($conn, "SELECT * FROM students WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: admin-students.php");
    exit();
}

/* ---------- personal ---------- */

$stmt = mysqli_prepare($conn, "SELECT * FROM personal_details WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$personal = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* ---------- academic ---------- */

$stmt = mysqli_prepare($conn, "SELECT * FROM academic_details WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$academic = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

/* ---------- documents ---------- */

$document_labels = [
    "student_photo"     => "Student Photo",
    "ssc_marksheet"     => "SSC Marksheet",
    "hsc_diploma"       => "HSC / Diploma Marksheet",
    "caste_certificate" => "Caste Certificate",
    "aadhaar_card"      => "Aadhaar Card",
    "pan_card"          => "PAN Card"
];

$uploaded = [];

$stmt = mysqli_prepare($conn, "SELECT document_type, original_filename, file_path, uploaded_at FROM student_documents WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($result)) {
    $uploaded[$row["document_type"]] = $row;
}
mysqli_stmt_close($stmt);

$submitted = ($student["application_submitted"] == 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($student["fullname"]); ?> | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "admin-header.php"; ?>

<div class="form-container">

    <div class="page-header">
        <h1><?php echo h($student["fullname"]); ?></h1>
        <p>
            <?php if ($submitted) { ?>
                <span class="status-badge status-done">Submitted</span>
                &nbsp; Application No: <strong><?php echo h($student["application_number"]); ?></strong>
            <?php } else { ?>
                <span class="status-badge status-pending">In Process</span>
                &nbsp; Application not submitted yet
            <?php } ?>
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
            if ($submitted) {
                showRow("Submitted On", date("d M Y, h:i A", strtotime($student["submitted_at"])));
            }
            ?>
        </div>
    </div>

    <!-- ============ ACADEMIC ============ -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">02</div>
            <h2>Academic Details</h2>
        </div>
        <div class="section-body">
            <?php if ($academic) {
                showRow("Course", $academic["course_branch"]);
                showRow("Admission Type", $academic["admission_type"]);
                showRow("Preferred College", $academic["preferred_college"]);
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
            } else { ?>
                <p class="empty-text">Student has not filled academic details yet.</p>
            <?php } ?>
        </div>
    </div>

    <!-- ============ PERSONAL ============ -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">03</div>
            <h2>Personal, Family &amp; Address Details</h2>
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
                <p class="empty-text">Student has not filled personal details yet.</p>
            <?php } ?>
        </div>
    </div>

    <!-- ============ DOCUMENTS ============ -->
    <div class="form-section">
        <div class="section-header">
            <div class="section-number">04</div>
            <h2>Documents</h2>
        </div>
        <div class="section-body">
            <?php foreach ($document_labels as $key => $label) { ?>
                <div class="info-row">
                    <span><?php echo h($label); ?></span>
                    <?php if (isset($uploaded[$key])) { ?>
                        <strong>
                            <a href="<?php echo h($uploaded[$key]["file_path"]); ?>" target="_blank" class="table-link">
                                View <?php echo h($uploaded[$key]["original_filename"]); ?>
                            </a>
                        </strong>
                    <?php } else { ?>
                        <strong class="doc-status pending">Not uploaded</strong>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>

    <div class="button-area">
        <a href="admin-students.php" class="back-btn">← Back to Students</a>
    </div>

</div>

<?php require_once "footer.php"; ?>
</body>
</html>
