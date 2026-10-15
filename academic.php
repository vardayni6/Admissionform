<?php
require_once "auth.php";
require_once "db.php";
require_once "session.php";

$student_id = $_SESSION["student_id"];

/* =====================================================
   APPLICATION ALREADY SUBMITTED? -> no editing
===================================================== */

$stmt = mysqli_prepare($conn, "SELECT application_submitted FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($student && $student["application_submitted"] == 1) {
    header("Location: profile.php");
    exit();
}


/* =====================================================
   FIELDS  (same names as the academic_details table)
   percentages are NOT here - we calculate them
===================================================== */

$fields = [
    "ssc_school", "ssc_board", "ssc_passing_year", "ssc_total_marks", "ssc_marks_obtained",
    "hsc_qualification", "hsc_college", "hsc_board_university", "hsc_stream_branch",
    "hsc_passing_year", "hsc_total_marks", "hsc_marks_obtained",
    "admission_type", "course_branch", "preferred_college"
];

/* dropdown options */
$ssc_boards      = ["Maharashtra Board", "CBSE", "ICSE", "Other"];
$qualifications  = ["HSC", "Diploma"];
$admission_types = ["CAP", "Institute Level", "Management Quota", "Other"];

/* $courses comes from db.php  (CS, ENTC, AIDS) */

$d = array_fill_keys($fields, "");

$message      = "";
$message_type = "";


/* =====================================================
   GET SAVED DETAILS
===================================================== */

$stmt = mysqli_prepare($conn, "SELECT * FROM academic_details WHERE student_id = ? LIMIT 1");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$saved = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($saved) {
    foreach ($fields as $field) {
        $d[$field] = $saved[$field] ?? "";
    }
}


/* =====================================================
   FORM SUBMITTED
===================================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    foreach ($fields as $field) {
        $d[$field] = trim($_POST[$field] ?? "");
    }

    /* ---------- required fields (stream / branch is optional) ---------- */

    $required = [
        "ssc_school"           => "SSC school name",
        "ssc_board"            => "SSC board",
        "ssc_passing_year"     => "SSC passing year",
        "ssc_total_marks"      => "SSC total marks",
        "ssc_marks_obtained"   => "SSC marks obtained",
        "hsc_qualification"    => "Qualification (HSC / Diploma)",
        "hsc_college"          => "College / institute",
        "hsc_board_university" => "Board / university",
        "hsc_passing_year"     => "HSC / Diploma passing year",
        "hsc_total_marks"      => "HSC / Diploma total marks",
        "hsc_marks_obtained"   => "HSC / Diploma marks obtained",
        "admission_type"       => "Admission type",
        "course_branch"        => "Course",
        "preferred_college"    => "Preferred college"
    ];

    foreach ($required as $key => $label) {
        if ($d[$key] === "") {
            $message = $label . " is required.";
            break;
        }
    }

    /* ---------- other checks ---------- */

    $this_year = (int)date("Y");

    if ($message == "") {

        if (!in_array($d["ssc_board"], $ssc_boards, true)) {
            $message = "Please select a valid SSC board.";

        } elseif (!in_array($d["hsc_qualification"], $qualifications, true)) {
            $message = "Please select HSC or Diploma.";

        } elseif (!in_array($d["admission_type"], $admission_types, true)) {
            $message = "Please select a valid admission type.";

        } elseif (!in_array($d["course_branch"], $courses, true)) {
            $message = "Please select one of the 3 courses.";

        } elseif (!ctype_digit($d["ssc_total_marks"]) || !ctype_digit($d["ssc_marks_obtained"])
               || !ctype_digit($d["hsc_total_marks"]) || !ctype_digit($d["hsc_marks_obtained"])) {
            $message = "Marks must be whole numbers.";

        } elseif (!ctype_digit($d["ssc_passing_year"]) || !ctype_digit($d["hsc_passing_year"])) {
            $message = "Passing year must be a 4-digit year.";

        } elseif ((int)$d["ssc_total_marks"] <= 0) {
            $message = "SSC total marks must be greater than 0.";

        } elseif ((int)$d["ssc_marks_obtained"] > (int)$d["ssc_total_marks"]) {
            $message = "SSC marks obtained cannot be more than total marks.";

        } elseif ((int)$d["hsc_total_marks"] <= 0) {
            $message = "HSC / Diploma total marks must be greater than 0.";

        } elseif ((int)$d["hsc_marks_obtained"] > (int)$d["hsc_total_marks"]) {
            $message = "HSC / Diploma marks obtained cannot be more than total marks.";

        } elseif ((int)$d["ssc_passing_year"] < 2000 || (int)$d["ssc_passing_year"] > $this_year) {
            $message = "SSC passing year must be between 2000 and " . $this_year . ".";

        } elseif ((int)$d["hsc_passing_year"] < 2000 || (int)$d["hsc_passing_year"] > $this_year + 1) {
            $message = "HSC / Diploma passing year must be between 2000 and " . ($this_year + 1) . ".";

        } elseif ((int)$d["hsc_passing_year"] < (int)$d["ssc_passing_year"]) {
            $message = "HSC / Diploma passing year cannot be before SSC passing year.";
        }
    }

    /* ---------- save in database ---------- */

    if ($message == "") {

        /* percentage is calculated here, not typed by the student */
        $ssc_percentage = round(((int)$d["ssc_marks_obtained"] / (int)$d["ssc_total_marks"]) * 100, 2);
        $hsc_percentage = round(((int)$d["hsc_marks_obtained"] / (int)$d["hsc_total_marks"]) * 100, 2);

        /* first time -> INSERT, already saved -> UPDATE  (student_id is UNIQUE) */

        $sql = "INSERT INTO academic_details
                (student_id, ssc_school, ssc_board, ssc_passing_year, ssc_total_marks, ssc_marks_obtained, ssc_percentage,
                 hsc_qualification, hsc_college, hsc_board_university, hsc_stream_branch, hsc_passing_year,
                 hsc_total_marks, hsc_marks_obtained, hsc_percentage,
                 admission_type, course_branch, preferred_college)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                 ssc_school = VALUES(ssc_school),
                 ssc_board = VALUES(ssc_board),
                 ssc_passing_year = VALUES(ssc_passing_year),
                 ssc_total_marks = VALUES(ssc_total_marks),
                 ssc_marks_obtained = VALUES(ssc_marks_obtained),
                 ssc_percentage = VALUES(ssc_percentage),
                 hsc_qualification = VALUES(hsc_qualification),
                 hsc_college = VALUES(hsc_college),
                 hsc_board_university = VALUES(hsc_board_university),
                 hsc_stream_branch = VALUES(hsc_stream_branch),
                 hsc_passing_year = VALUES(hsc_passing_year),
                 hsc_total_marks = VALUES(hsc_total_marks),
                 hsc_marks_obtained = VALUES(hsc_marks_obtained),
                 hsc_percentage = VALUES(hsc_percentage),
                 admission_type = VALUES(admission_type),
                 course_branch = VALUES(course_branch),
                 preferred_college = VALUES(preferred_college)";

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            die("SQL Prepare Error: " . mysqli_error($conn));
        }

        /* i = number, s = text, d = decimal (18 values in total) */
        mysqli_stmt_bind_param(
            $stmt,
            "issiiidssssiiidsss",
            $student_id,
            $d["ssc_school"], $d["ssc_board"], $d["ssc_passing_year"], $d["ssc_total_marks"], $d["ssc_marks_obtained"], $ssc_percentage,
            $d["hsc_qualification"], $d["hsc_college"], $d["hsc_board_university"], $d["hsc_stream_branch"], $d["hsc_passing_year"],
            $d["hsc_total_marks"], $d["hsc_marks_obtained"], $hsc_percentage,
            $d["admission_type"], $d["course_branch"], $d["preferred_college"]
        );

        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            if (($_POST["action"] ?? "") == "continue") {
                header("Location: documents.php");
            } else {
                header("Location: index.php");
            }
            exit();

        } else {
            $message = "Could not save details. Please try again.";
        }

        mysqli_stmt_close($stmt);
    }

    $message_type = "error";
}

/* percentage to show in the (read-only) boxes */
$ssc_percentage_show = "";
$hsc_percentage_show = "";

if ((int)$d["ssc_total_marks"] > 0 && $d["ssc_marks_obtained"] !== "") {
    $ssc_percentage_show = round(((int)$d["ssc_marks_obtained"] / (int)$d["ssc_total_marks"]) * 100, 2);
}
if ((int)$d["hsc_total_marks"] > 0 && $d["hsc_marks_obtained"] !== "") {
    $hsc_percentage_show = round(((int)$d["hsc_marks_obtained"] / (int)$d["hsc_total_marks"]) * 100, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Details | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<div class="form-container">

    <div class="page-header">
        <h1>🎓 Academic Details</h1>
        <p>Enter your education details and choose your course</p>
    </div>

    <?php if ($message != "") { ?>
        <div class="message <?php echo $message_type; ?>"><?php echo h($message); ?></div>
    <?php } ?>

    <form method="POST" autocomplete="off">

        <!-- ================= 01 SSC ================= -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-number">01</div>
                <h2>SSC / 10th Information</h2>
            </div>
            <div class="section-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label>School Name <span>*</span></label>
                        <input type="text" name="ssc_school" value="<?php echo h($d["ssc_school"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Board <span>*</span></label>
                        <select name="ssc_board" required>
                            <option value="">Select Board</option>
                            <?php foreach ($ssc_boards as $item) { ?>
                                <option value="<?php echo h($item); ?>" <?php echo ($d["ssc_board"] == $item) ? "selected" : ""; ?>>
                                    <?php echo h($item); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Passing Year <span>*</span></label>
                        <input type="number" name="ssc_passing_year" min="2000" max="<?php echo date("Y"); ?>"
                               placeholder="e.g. 2022" value="<?php echo h($d["ssc_passing_year"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Total Marks <span>*</span></label>
                        <input type="number" id="ssc_total_marks" name="ssc_total_marks" min="1"
                               value="<?php echo h($d["ssc_total_marks"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Marks Obtained <span>*</span></label>
                        <input type="number" id="ssc_marks_obtained" name="ssc_marks_obtained" min="0"
                               value="<?php echo h($d["ssc_marks_obtained"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Percentage</label>
                        <input type="text" id="ssc_percentage" value="<?php echo h($ssc_percentage_show); ?>" readonly>
                        <div class="hint">Calculated automatically</div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= 02 HSC / DIPLOMA ================= -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-number">02</div>
                <h2>HSC / Diploma Information</h2>
            </div>
            <div class="section-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Qualification <span>*</span></label>
                        <select name="hsc_qualification" required>
                            <option value="">Select Qualification</option>
                            <?php foreach ($qualifications as $item) { ?>
                                <option value="<?php echo h($item); ?>" <?php echo ($d["hsc_qualification"] == $item) ? "selected" : ""; ?>>
                                    <?php echo h($item); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>College / Institute <span>*</span></label>
                        <input type="text" name="hsc_college" value="<?php echo h($d["hsc_college"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Board / University <span>*</span></label>
                        <input type="text" name="hsc_board_university" placeholder="e.g. MSBTE, SPPU"
                               value="<?php echo h($d["hsc_board_university"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Stream / Branch</label>
                        <input type="text" name="hsc_stream_branch" placeholder="e.g. Science, Computer"
                               value="<?php echo h($d["hsc_stream_branch"]); ?>">
                        <div class="hint">Optional</div>
                    </div>

                    <div class="form-group">
                        <label>Passing Year <span>*</span></label>
                        <input type="number" name="hsc_passing_year" min="2000" max="<?php echo date("Y") + 1; ?>"
                               placeholder="e.g. 2025" value="<?php echo h($d["hsc_passing_year"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Total Marks <span>*</span></label>
                        <input type="number" id="hsc_total_marks" name="hsc_total_marks" min="1"
                               value="<?php echo h($d["hsc_total_marks"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Marks Obtained <span>*</span></label>
                        <input type="number" id="hsc_marks_obtained" name="hsc_marks_obtained" min="0"
                               value="<?php echo h($d["hsc_marks_obtained"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Percentage</label>
                        <input type="text" id="hsc_percentage" value="<?php echo h($hsc_percentage_show); ?>" readonly>
                        <div class="hint">Calculated automatically</div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= 03 ADMISSION ================= -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-number">03</div>
                <h2>Admission Details</h2>
            </div>
            <div class="section-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Admission Type <span>*</span></label>
                        <select name="admission_type" required>
                            <option value="">Select Admission Type</option>
                            <?php foreach ($admission_types as $item) { ?>
                                <option value="<?php echo h($item); ?>" <?php echo ($d["admission_type"] == $item) ? "selected" : ""; ?>>
                                    <?php echo h($item); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Course <span>*</span></label>
                        <select name="course_branch" required>
                            <option value="">Select Course</option>
                            <?php foreach ($courses as $short => $full) { ?>
                                <option value="<?php echo h($full); ?>" <?php echo ($d["course_branch"] == $full) ? "selected" : ""; ?>>
                                    <?php echo h($full); ?> (<?php echo h($short); ?>)
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group full">
                        <label>Preferred College <span>*</span></label>
                        <input type="text" name="preferred_college" placeholder="Enter preferred college"
                               value="<?php echo h($d["preferred_college"]); ?>" required>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= BUTTONS ================= -->
        <div class="button-area">
            <a href="personal.php" class="back-btn">← Back</a>
            <div class="button-group">
                <button type="submit" name="action" value="dashboard" class="save-btn secondary">Save</button>
                <button type="submit" name="action" value="continue" class="save-btn">Save &amp; Continue →</button>
            </div>
        </div>

    </form>
</div>

<?php require_once "footer.php"; ?>
<script src="validation.js"></script>
<script>
    setupPercentage("ssc_total_marks", "ssc_marks_obtained", "ssc_percentage");
    setupPercentage("hsc_total_marks", "hsc_marks_obtained", "hsc_percentage");
</script>
</body>
</html>
