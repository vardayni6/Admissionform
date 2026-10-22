<?php
require_once "admin-auth.php";
require_once "db.php";
require_once "session.php";

/* =====================================================
   GET STUDENT ID
===================================================== */

$student_id = (int)($_GET["id"] ?? 0);

if ($student_id <= 0) {
    header("Location: admin-students.php");
    exit();
}

/* =====================================================
   CSRF TOKEN
===================================================== */

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

/* =====================================================
   HELPER: PRINT LABEL AND VALUE
===================================================== */

function showRow($label, $value)
{
    if ($value === null || $value === "") {
        $value = "-";
    }

    echo '<div class="info-row">';
    echo '<span>' . h($label) . '</span>';
    echo '<strong>' . h($value) . '</strong>';
    echo '</div>';
}

/* =====================================================
   FETCH STUDENT
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM students WHERE student_id = ? LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$student = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: admin-students.php");
    exit();
}

/* =====================================================
   HANDLE ADMIN REVIEW SUBMISSION
===================================================== */

$review_error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    /* Validate CSRF token */

    $submitted_token = $_POST["csrf_token"] ?? "";

    if (
        !is_string($submitted_token)
        || !hash_equals($csrf_token, $submitted_token)
    ) {
        $review_error = "Invalid form request. Please refresh the page and try again.";
    } else {

        $admin_action = $_POST["admin_action"] ?? "";
        $admin_remark = trim($_POST["admin_remark"] ?? "");

        /* Validate allowed actions */

        $allowed_actions = [
            "Accepted",
            "Changes Requested"
        ];

        if (!in_array($admin_action, $allowed_actions, true)) {

            $review_error = "Please select a valid application decision.";

        } elseif ((int)$student["application_submitted"] !== 1) {

            $review_error = "You cannot review an application that has not been submitted.";

        } elseif (
            $admin_action === "Changes Requested"
            && $admin_remark === ""
        ) {

            $review_error = "Please explain the required corrections before sending the application back.";

        } else {

            /*
             * Update the existing student record.
             *
             * The application number is NOT changed.
             * The student's submitted form is NOT deleted.
             */

            $application_status = $admin_action;

            $review_status = "Reviewed";

            $stmt = mysqli_prepare(
                $conn,
                "UPDATE students
                 SET application_status = ?,
                     review_status = ?,
                     admin_action = ?,
                     admin_remark = ?,
                     reviewed_at = NOW()
                 WHERE student_id = ?
                   AND application_submitted = 1"
            );

            if (!$stmt) {

                $review_error = "Unable to prepare the review update.";

            } else {

                mysqli_stmt_bind_param(
                    $stmt,
                    "ssssi",
                    $application_status,
                    $review_status,
                    $admin_action,
                    $admin_remark,
                    $student_id
                );

                if (mysqli_stmt_execute($stmt)) {

                    $updated_rows = mysqli_stmt_affected_rows($stmt);

                    mysqli_stmt_close($stmt);

                    /*
                     * The submitted application number is not
                     * regenerated or modified here.
                     */

                    header(
                        "Location: admin-student-view.php?id="
                        . $student_id
                        . "&review=success"
                    );

                    exit();

                } else {

                    $review_error = "Unable to save the review. Please try again.";

                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
}

/* =====================================================
   FETCH PERSONAL DETAILS
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM personal_details
     WHERE student_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$personal = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

/* =====================================================
   FETCH ACADEMIC DETAILS
===================================================== */

$stmt = mysqli_prepare(
    $conn,
    "SELECT * FROM academic_details
     WHERE student_id = ?
     LIMIT 1"
);

mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$academic = mysqli_fetch_assoc(
    mysqli_stmt_get_result($stmt)
);

mysqli_stmt_close($stmt);

/* =====================================================
   FETCH DOCUMENTS
===================================================== */

$document_labels = [
    "student_photo"     => "Student Photo",
    "ssc_marksheet"     => "SSC Marksheet",
    "hsc_diploma"       => "HSC / Diploma Marksheet",
    "caste_certificate" => "Caste Certificate",
    "aadhaar_card"      => "Aadhaar Card",
    "pan_card"          => "PAN Card"
];

$uploaded = [];

$stmt = mysqli_prepare(
    $conn,
    "SELECT document_type, original_filename, file_path, uploaded_at
     FROM student_documents
     WHERE student_id = ?"
);

mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $uploaded[$row["document_type"]] = $row;
}

mysqli_stmt_close($stmt);

/* =====================================================
   APPLICATION AND REVIEW STATUS
===================================================== */

$submitted = ((int)$student["application_submitted"] === 1);

$application_status = $student["application_status"] ?? "Draft";

$review_status = $student["review_status"] ?? "Not Reviewed";

$admin_action = $student["admin_action"] ?? "";

$admin_remark = $student["admin_remark"] ?? "";

/*
 * Existing submitted applications might still have the
 * default application_status value "Draft".
 *
 * Display those as "Submitted" until they receive a decision.
 */

if (
    $submitted
    && ($application_status === "" || $application_status === "Draft")
) {
    $application_status = "Submitted";
}

/* =====================================================
   SUCCESS MESSAGE
===================================================== */

$review_success = (
    isset($_GET["review"])
    && $_GET["review"] === "success"
);

/* =====================================================
   DATE HELPER
===================================================== */

function formatDateTime($value, $format = "d M Y, h:i A")
{
    if (empty($value)) {
        return "-";
    }

    $timestamp = strtotime($value);

    if ($timestamp === false) {
        return "-";
    }

    return date($format, $timestamp);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>
        <?php echo h($student["fullname"]); ?>
        | Admission Dashboard
    </title>

    <link rel="stylesheet" href="style.css">

    <link rel="icon"
          href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">

    <style>
        .review-panel {
            margin-top: 25px;
            border: 1px solid #dbe3ed;
            border-radius: 12px;
            overflow: hidden;
            background: #ffffff;
        }

        .review-panel-header {
            padding: 18px 22px;
            background: #f1f5f9;
            border-bottom: 1px solid #dbe3ed;
        }

        .review-panel-header h2 {
            margin: 0;
            font-size: 21px;
        }

        .review-panel-body {
            padding: 22px;
        }

        .review-status-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
            margin-bottom: 22px;
        }

        .review-status-card {
            padding: 15px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
        }

        .review-status-card span {
            display: block;
            color: #64748b;
            font-size: 13px;
            margin-bottom: 8px;
        }

        .review-status-card strong {
            display: block;
            overflow-wrap: anywhere;
        }

        .review-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
        }

        .badge-accepted,
        .badge-reviewed {
            background: #dcfce7;
            color: #166534;
        }

        .badge-changes {
            background: #fee2e2;
            color: #991b1b;
        }

        .badge-not-reviewed {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-submitted {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .remark-box {
            margin: 18px 0;
            padding: 18px;
            border: 1px solid #f4d18b;
            border-radius: 9px;
            background: #fffbeb;
        }

        .remark-box h3 {
            margin: 0 0 10px;
            color: #92400e;
            font-size: 16px;
        }

        .remark-box p {
            margin: 0;
            color: #78350f;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: pre-wrap;
        }

        .review-form label {
            display: block;
            margin: 18px 0 8px;
            font-weight: 600;
        }

        .review-form select,
        .review-form textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font: inherit;
        }

        .review-form textarea {
            min-height: 130px;
            resize: vertical;
        }

        .review-help {
            margin-top: 7px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        .review-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .review-submit {
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            font: inherit;
            font-weight: 600;
            cursor: pointer;
            color: #ffffff;
            background: #1769aa;
        }

        .review-submit:hover {
            background: #12548a;
        }

        .review-alert {
            padding: 14px 16px;
            margin: 18px 0;
            border-radius: 8px;
            line-height: 1.6;
        }

        .review-alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .review-alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .review-note {
            margin-top: 14px;
            color: #64748b;
            font-size: 13px;
            line-height: 1.6;
        }

        @media (max-width: 700px) {
            .review-status-grid {
                grid-template-columns: 1fr;
            }

            .review-panel-body {
                padding: 15px;
            }
        }
    </style>

</head>

<body>

<?php require_once "admin-header.php"; ?>

<div class="form-container">

    <!-- ============ PAGE HEADER ============ -->

    <div class="page-header">

        <h1><?php echo h($student["fullname"]); ?></h1>

        <p>

            <?php if ($submitted) { ?>

                <span class="status-badge status-done">
                    Submitted
                </span>

                &nbsp; Application No:

                <strong>
                    <?php echo h($student["application_number"] ?: "-"); ?>
                </strong>

            <?php } else { ?>

                <span class="status-badge status-pending">
                    In Process
                </span>

                &nbsp; Application not submitted yet

            <?php } ?>

        </p>

    </div>

    <!-- ============ REVIEW FEEDBACK ============ -->

    <?php if ($review_success) { ?>

        <div class="review-alert review-alert-success">
            Application review saved successfully.
        </div>

    <?php } ?>

    <?php if ($review_error !== "") { ?>

        <div class="review-alert review-alert-error">
            <?php echo h($review_error); ?>
        </div>

    <?php } ?>

    <!-- ============ REGISTRATION DETAILS ============ -->

    <div class="form-section">

        <div class="section-header">
            <div class="section-number">01</div>
            <h2>Registration Details</h2>
        </div>

        <div class="section-body">

            <?php

            showRow("Student ID", $student["student_id"]);

            showRow("Full Name", $student["fullname"]);

            showRow("Email", $student["email"]);

            showRow("Mobile", $student["mobile"]);

            showRow(
                "Registered On",
                formatDateTime($student["created_at"], "d M Y")
            );

            if ($submitted) {

                showRow(
                    "Submitted On",
                    formatDateTime($student["submitted_at"])
                );

            }

            ?>

        </div>

    </div>

    <!-- ============ ACADEMIC DETAILS ============ -->

    <div class="form-section">

        <div class="section-header">
            <div class="section-number">02</div>
            <h2>Academic Details</h2>
        </div>

        <div class="section-body">

            <?php if ($academic) { ?>

                <?php

                showRow("Course", $academic["course_branch"]);

                showRow("Admission Type", $academic["admission_type"]);

                showRow("Preferred College", $academic["preferred_college"]);

                showRow("SSC School", $academic["ssc_school"]);

                showRow("SSC Board", $academic["ssc_board"]);

                showRow("SSC Passing Year", $academic["ssc_passing_year"]);

                showRow(
                    "SSC Marks",
                    $academic["ssc_marks_obtained"]
                    . " / "
                    . $academic["ssc_total_marks"]
                    . " ("
                    . $academic["ssc_percentage"]
                    . "%)"
                );

                showRow("Qualification", $academic["hsc_qualification"]);

                showRow("College / Institute", $academic["hsc_college"]);

                showRow("Board / University", $academic["hsc_board_university"]);

                showRow("Stream / Branch", $academic["hsc_stream_branch"]);

                showRow("Passing Year", $academic["hsc_passing_year"]);

                showRow(
                    "Marks",
                    $academic["hsc_marks_obtained"]
                    . " / "
                    . $academic["hsc_total_marks"]
                    . " ("
                    . $academic["hsc_percentage"]
                    . "%)"
                );

                ?>

            <?php } else { ?>

                <p class="empty-text">
                    Student has not filled academic details yet.
                </p>

            <?php } ?>

        </div>

    </div>

    <!-- ============ PERSONAL DETAILS ============ -->

    <div class="form-section">

        <div class="section-header">
            <div class="section-number">03</div>
            <h2>Personal, Family &amp; Address Details</h2>
        </div>

        <div class="section-body">

            <?php if ($personal) { ?>

                <?php

                showRow(
                    "Date of Birth",
                    formatDateTime($personal["date_of_birth"], "d M Y")
                );

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

                showRow(
                    "Annual Family Income",
                    $personal["annual_family_income"]
                );

                showRow(
                    "Parent / Guardian Mobile",
                    $personal["parent_guardian_mobile"]
                );
                
                $permanent = implode(", ", array_filter([
                    $personal["permanent_address_line1"] ?? "",
                    $personal["permanent_address_line2"] ?? "",
                    $personal["permanent_city_village"] ?? "",
                    $personal["permanent_taluka"] ?? "",
                    $personal["permanent_district"] ?? "",
                    $personal["permanent_state"] ?? "",
                    $personal["permanent_country"] ?? ""
                ], function ($value) {
                    return trim((string)$value) !== "";
                }));

                if (!empty($personal["permanent_pin_code"])) {
                    $permanent .= " - " . $personal["permanent_pin_code"];
                }

                $temporary = implode(", ", array_filter([
                    $personal["temporary_address_line1"] ?? "",
                    $personal["temporary_address_line2"] ?? "",
                    $personal["temporary_city_village"] ?? "",
                    $personal["temporary_taluka"] ?? "",
                    $personal["temporary_district"] ?? "",
                    $personal["temporary_state"] ?? "",
                    $personal["temporary_country"] ?? ""
                ], function ($value) {
                    return trim((string)$value) !== "";
                }));

                if (!empty($personal["temporary_pin_code"])) {
                    $temporary .= " - " . $personal["temporary_pin_code"];
                }

                showRow("Permanent Address", $permanent);

                showRow("Temporary Address", $temporary);

                ?>

            <?php } else { ?>

                <p class="empty-text">
                    Student has not filled personal details yet.
                </p>

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

                    <span>
                        <?php echo h($label); ?>
                    </span>

                    <?php if (isset($uploaded[$key])) { ?>

                        <strong>

                            <a
                                href="<?php echo h($uploaded[$key]["file_path"]); ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="table-link"
                            >
                                View
                                <?php echo h($uploaded[$key]["original_filename"]); ?>
                            </a>

                        </strong>

                    <?php } else { ?>

                        <strong class="doc-status pending">
                            Not uploaded
                        </strong>

                    <?php } ?>

                </div>

            <?php } ?>

        </div>

    </div>

    <!-- ============ APPLICATION REVIEW ============ -->

    <div class="review-panel">

        <div class="review-panel-header">
            <h2>Application Review</h2>
        </div>

        <div class="review-panel-body">

            <div class="review-status-grid">

                <div class="review-status-card">

                    <span>Application Status</span>

                    <?php

                    $applicationBadge = "badge-submitted";

                    if ($application_status === "Accepted") {
                        $applicationBadge = "badge-accepted";
                    } elseif ($application_status === "Changes Requested") {
                        $applicationBadge = "badge-changes";
                    } elseif ($application_status === "Draft") {
                        $applicationBadge = "badge-not-reviewed";
                    }

                    ?>

                    <strong>
                        <span class="review-badge <?php echo h($applicationBadge); ?>">
                            <?php echo h($application_status); ?>
                        </span>
                    </strong>

                </div>

                <div class="review-status-card">

                    <span>Review Status</span>

                    <strong>

                        <?php if ($review_status === "Reviewed") { ?>

                            <span class="review-badge badge-reviewed">
                                Reviewed
                            </span>

                        <?php } else { ?>

                            <span class="review-badge badge-not-reviewed">
                                Not Reviewed
                            </span>

                        <?php } ?>

                    </strong>

                </div>

                <div class="review-status-card">

                    <span>Last Reviewed</span>

                    <strong>
                        <?php
                        echo h(
                            formatDateTime($student["reviewed_at"] ?? null)
                        );
                        ?>
                    </strong>

                </div>

            </div>

            <!-- Existing admin remark -->

            <?php if ($admin_remark !== "") { ?>

                <div class="remark-box">

                    <h3>
                        Previous Admin Remark
                    </h3>

                    <p><?php echo h($admin_remark); ?></p>

                </div>

            <?php } ?>

            <?php if (!$submitted) { ?>

                <div class="review-alert review-alert-error">

                    This student has not submitted the application yet.
                    You can review it after the student submits it.

                </div>

            <?php } else { ?>

                <h3>Submit Application Decision</h3>

                <p class="review-note">
                    Select the decision after checking the student's
                    information and uploaded documents.
                </p>

                <form
                    method="POST"
                    action="admin-student-view.php?id=<?php echo (int)$student_id; ?>"
                    class="review-form"
                    id="applicationReviewForm"
                >

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo h($csrf_token); ?>"
                    >

                    <!-- Admin decision -->

                    <label for="admin_action">
                        Application Decision
                    </label>

                    <select
                        name="admin_action"
                        id="admin_action"
                        required
                    >

                        <option value="">
                            -- Select Decision --
                        </option>

                        <option
                            value="Accepted"
                            <?php
                            echo $admin_action === "Accepted"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Accept Application
                        </option>

                        <option
                            value="Changes Requested"
                            <?php
                            echo $admin_action === "Changes Requested"
                                ? "selected"
                                : "";
                            ?>
                        >
                            Send Back for Correction
                        </option>

                    </select>

                    <!-- Admin remark -->

                    <label for="admin_remark">
                        Remark for Student
                    </label>

                    <textarea
                        name="admin_remark"
                        id="admin_remark"
                        maxlength="5000"
                        placeholder="Explain which details or documents need correction..."
                    ><?php echo h($admin_remark); ?></textarea>

                    <p class="review-help" id="remarkHelp">
                        If you send the application back, explain clearly
                        what the student needs to correct.
                    </p>

                    <!-- Submit review -->

                    <div class="review-buttons">

                        <button
                            type="submit"
                            class="review-submit"
                            id="reviewSubmitButton"
                        >
                            Submit Review
                        </button>

                        <a
                            href="admin-students.php"
                            class="back-btn"
                        >
                            ← Back to Students
                        </a>

                    </div>

                    <p class="review-note">
                        Submitting a decision updates the existing
                        application. It does not generate a new
                        application number.
                    </p>

                </form>

            <?php } ?>

        </div>

    </div>

</div>

<?php require_once "footer.php"; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {

    const form = document.getElementById("applicationReviewForm");

    if (!form) {
        return;
    }

    const actionSelect = document.getElementById("admin_action");
    const remarkField = document.getElementById("admin_remark");
    const remarkHelp = document.getElementById("remarkHelp");

    function updateRemarkRequirement() {

        const needsCorrection =
            actionSelect.value === "Changes Requested";

        remarkField.required = needsCorrection;

        if (needsCorrection) {

            remarkHelp.textContent =
                "Required: explain exactly which details or documents " +
                "the student must correct before resubmitting.";

        } else {

            remarkHelp.textContent =
                "Optional for acceptance. You can provide a confirmation message.";

        }
    }

    actionSelect.addEventListener("change", updateRemarkRequirement);

    updateRemarkRequirement();

    form.addEventListener("submit", function (event) {

        const action = actionSelect.value;
        const remark = remarkField.value.trim();

        if (!action) {

            event.preventDefault();

            alert("Please select an application decision.");

            actionSelect.focus();

            return;
        }

        if (action === "Changes Requested" && remark === "") {

            event.preventDefault();

            alert("Please explain the required corrections.");

            remarkField.focus();

            return;
        }

        const message = action === "Accepted"
            ? "Are you sure you want to accept this application?"
            : "Are you sure you want to send this application back for correction?";

        if (!confirm(message)) {
            event.preventDefault();
        }

    });

});
</script>

</body>
</html>