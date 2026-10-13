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
   LIST OF FIELDS  (same names as the personal_details table)
===================================================== */

$fields = [
    "date_of_birth", "gender", "nationality", "mother_tongue", "blood_group", "aadhaar_number",
    "father_name", "mother_name", "guardian_name", "father_occupation", "mother_occupation",
    "annual_family_income", "parent_guardian_mobile",
    "permanent_address_line1", "permanent_address_line2", "permanent_city_village", "permanent_taluka",
    "permanent_district", "permanent_state", "permanent_country", "permanent_pin_code",
    "temporary_address_line1", "temporary_address_line2", "temporary_city_village", "temporary_taluka",
    "temporary_district", "temporary_state", "temporary_country", "temporary_pin_code"
];

/* dropdown options */
$genders      = ["Male", "Female", "Other"];
$blood_groups = ["A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"];
$incomes      = ["Below ₹1 Lakh", "₹1 Lakh - ₹2.5 Lakhs", "₹2.5 Lakhs - ₹5 Lakhs", "₹5 Lakhs - ₹10 Lakhs", "Above ₹10 Lakhs"];

/* $d keeps the value of every field (empty to start with) */
$d = array_fill_keys($fields, "");
$d["nationality"]       = "Indian";
$d["permanent_country"] = "India";
$d["temporary_country"] = "India";

$message      = "";
$message_type = "";


/* =====================================================
   GET SAVED DETAILS (if the student filled before)
===================================================== */

$stmt = mysqli_prepare($conn, "SELECT * FROM personal_details WHERE student_id = ? LIMIT 1");
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

    /* take every value from the form */
    foreach ($fields as $field) {
        $d[$field] = trim($_POST[$field] ?? "");
    }

    /* "Same as permanent address" checkbox */
    if (isset($_POST["same_as_permanent"])) {
        $d["temporary_address_line1"] = $d["permanent_address_line1"];
        $d["temporary_address_line2"] = $d["permanent_address_line2"];
        $d["temporary_city_village"]  = $d["permanent_city_village"];
        $d["temporary_taluka"]        = $d["permanent_taluka"];
        $d["temporary_district"]      = $d["permanent_district"];
        $d["temporary_state"]         = $d["permanent_state"];
        $d["temporary_country"]       = $d["permanent_country"];
        $d["temporary_pin_code"]      = $d["permanent_pin_code"];
    }

    /* ---------- required fields ---------- */

    $required = [
        "date_of_birth"           => "Date of birth",
        "gender"                  => "Gender",
        "nationality"             => "Nationality",
        "mother_tongue"           => "Mother tongue",
        "blood_group"             => "Blood group",
        "father_name"             => "Father's name",
        "mother_name"             => "Mother's name",
        "father_occupation"       => "Father's occupation",
        "mother_occupation"       => "Mother's occupation",
        "annual_family_income"    => "Annual family income",
        "parent_guardian_mobile"  => "Parent / guardian mobile",
        "permanent_address_line1" => "Permanent address line 1",
        "permanent_city_village"  => "Permanent city / village",
        "permanent_taluka"        => "Permanent taluka",
        "permanent_district"      => "Permanent district",
        "permanent_state"         => "Permanent state",
        "permanent_country"       => "Permanent country",
        "permanent_pin_code"      => "Permanent PIN code",
        "temporary_address_line1" => "Temporary address line 1",
        "temporary_city_village"  => "Temporary city / village",
        "temporary_taluka"        => "Temporary taluka",
        "temporary_district"      => "Temporary district",
        "temporary_state"         => "Temporary state",
        "temporary_country"       => "Temporary country",
        "temporary_pin_code"      => "Temporary PIN code"
    ];

    foreach ($required as $key => $label) {
        if ($d[$key] === "") {
            $message = $label . " is required.";
            break;
        }
    }

    /* ---------- other checks ---------- */

    if ($message == "") {

        $dob = DateTime::createFromFormat("Y-m-d", $d["date_of_birth"]);

        if (!$dob || $dob->format("Y-m-d") !== $d["date_of_birth"]) {
            $message = "Please enter a valid date of birth.";

        } elseif ($dob > new DateTime("today")) {
            $message = "Date of birth cannot be in the future.";

        } elseif (!in_array($d["gender"], $genders, true)) {
            $message = "Please select a valid gender.";

        } elseif (!in_array($d["blood_group"], $blood_groups, true)) {
            $message = "Please select a valid blood group.";

        } elseif (!in_array($d["annual_family_income"], $incomes, true)) {
            $message = "Please select a valid family income.";

        } elseif ($d["aadhaar_number"] !== "" && !preg_match("/^[0-9]{12}$/", $d["aadhaar_number"])) {
            $message = "Aadhaar number must be exactly 12 digits.";

        } elseif (!preg_match("/^[6-9][0-9]{9}$/", $d["parent_guardian_mobile"])) {
            $message = "Enter a valid 10-digit parent / guardian mobile number.";

        } elseif (!preg_match("/^[0-9]{6}$/", $d["permanent_pin_code"])) {
            $message = "Permanent PIN code must be 6 digits.";

        } elseif (!preg_match("/^[0-9]{6}$/", $d["temporary_pin_code"])) {
            $message = "Temporary PIN code must be 6 digits.";
        }
    }

    /* ---------- save in database ---------- */

    if ($message == "") {

        /*
           One query for both cases:
           - first time  -> INSERT a new row
           - already saved -> UPDATE the same row (student_id is a UNIQUE key)
        */

        $columns = implode(", ", $fields);
        $marks   = implode(", ", array_fill(0, count($fields), "?"));

        $updates = [];
        foreach ($fields as $field) {
            $updates[] = $field . " = VALUES(" . $field . ")";
        }

        $sql = "INSERT INTO personal_details (student_id, " . $columns . ")
                VALUES (?, " . $marks . ")
                ON DUPLICATE KEY UPDATE " . implode(", ", $updates);

        $stmt = mysqli_prepare($conn, $sql);

        if (!$stmt) {
            die("SQL Prepare Error: " . mysqli_error($conn));
        }

        $types  = "i" . str_repeat("s", count($fields));
        $values = [$student_id];
        foreach ($fields as $field) {
            $values[] = $d[$field];
        }

        mysqli_stmt_bind_param($stmt, $types, ...$values);

        if (mysqli_stmt_execute($stmt)) {

            mysqli_stmt_close($stmt);

            /* which button was clicked? */
            if (($_POST["action"] ?? "") == "continue") {
                header("Location: academic.php");
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personal Details | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<div class="form-container">

    <div class="page-header">
        <h1>📝 Personal Details</h1>
        <p>Enter your personal, family and address information</p>
    </div>

    <?php if ($message != "") { ?>
        <div class="message <?php echo $message_type; ?>"><?php echo h($message); ?></div>
    <?php } ?>

    <form method="POST" autocomplete="off">

        <!-- ================= 01 PERSONAL ================= -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-number">01</div>
                <h2>Personal Information</h2>
            </div>
            <div class="section-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Date of Birth <span>*</span></label>
                        <input type="date" name="date_of_birth" max="<?php echo date("Y-m-d"); ?>"
                               value="<?php echo h($d["date_of_birth"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Gender <span>*</span></label>
                        <select name="gender" required>
                            <option value="">Select Gender</option>
                            <?php foreach ($genders as $item) { ?>
                                <option value="<?php echo h($item); ?>" <?php echo ($d["gender"] == $item) ? "selected" : ""; ?>>
                                    <?php echo h($item); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Nationality <span>*</span></label>
                        <input type="text" name="nationality" placeholder="Enter your nationality"
                               value="<?php echo h($d["nationality"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Mother Tongue <span>*</span></label>
                        <input type="text" name="mother_tongue" placeholder="Enter your mother tongue"
                               value="<?php echo h($d["mother_tongue"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Blood Group <span>*</span></label>
                        <select name="blood_group" required>
                            <option value="">Select Blood Group</option>
                            <?php foreach ($blood_groups as $item) { ?>
                                <option value="<?php echo h($item); ?>" <?php echo ($d["blood_group"] == $item) ? "selected" : ""; ?>>
                                    <?php echo h($item); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Aadhaar Number</label>
                        <input type="text" name="aadhaar_number" placeholder="Enter 12-digit Aadhaar number"
                               maxlength="12" value="<?php echo h($d["aadhaar_number"]); ?>">
                        <div class="hint">Optional</div>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= 02 FAMILY ================= -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-number">02</div>
                <h2>Family Information</h2>
            </div>
            <div class="section-body">
                <div class="form-grid">

                    <div class="form-group">
                        <label>Father's Name <span>*</span></label>
                        <input type="text" name="father_name" placeholder="Enter father's full name"
                               value="<?php echo h($d["father_name"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Mother's Name <span>*</span></label>
                        <input type="text" name="mother_name" placeholder="Enter mother's full name"
                               value="<?php echo h($d["mother_name"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Guardian's Name</label>
                        <input type="text" name="guardian_name" placeholder="Enter guardian's name"
                               value="<?php echo h($d["guardian_name"]); ?>">
                        <div class="hint">Optional</div>
                    </div>

                    <div class="form-group">
                        <label>Father's Occupation <span>*</span></label>
                        <input type="text" name="father_occupation" placeholder="Enter father's occupation"
                               value="<?php echo h($d["father_occupation"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Mother's Occupation <span>*</span></label>
                        <input type="text" name="mother_occupation" placeholder="Enter mother's occupation"
                               value="<?php echo h($d["mother_occupation"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Annual Family Income <span>*</span></label>
                        <select name="annual_family_income" required>
                            <option value="">Select Income</option>
                            <?php foreach ($incomes as $item) { ?>
                                <option value="<?php echo h($item); ?>" <?php echo ($d["annual_family_income"] == $item) ? "selected" : ""; ?>>
                                    <?php echo h($item); ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Parent / Guardian Mobile <span>*</span></label>
                        <input type="text" name="parent_guardian_mobile" placeholder="Enter 10-digit mobile number"
                               maxlength="10" value="<?php echo h($d["parent_guardian_mobile"]); ?>" required>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= 03 PERMANENT ADDRESS ================= -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-number">03</div>
                <h2>Permanent Address</h2>
            </div>
            <div class="section-body">
                <div class="form-grid">

                    <div class="form-group full">
                        <label>Address Line 1 <span>*</span></label>
                        <input type="text" id="permanent_address_line1" name="permanent_address_line1"
                               placeholder="House No., Building, Street"
                               value="<?php echo h($d["permanent_address_line1"]); ?>" required>
                    </div>

                    <div class="form-group full">
                        <label>Address Line 2</label>
                        <input type="text" id="permanent_address_line2" name="permanent_address_line2"
                               placeholder="Area, Landmark"
                               value="<?php echo h($d["permanent_address_line2"]); ?>">
                    </div>

                    <div class="form-group">
                        <label>City / Village <span>*</span></label>
                        <input type="text" id="permanent_city_village" name="permanent_city_village"
                               value="<?php echo h($d["permanent_city_village"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Taluka <span>*</span></label>
                        <input type="text" id="permanent_taluka" name="permanent_taluka"
                               value="<?php echo h($d["permanent_taluka"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>District <span>*</span></label>
                        <input type="text" id="permanent_district" name="permanent_district"
                               value="<?php echo h($d["permanent_district"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>State <span>*</span></label>
                        <input type="text" id="permanent_state" name="permanent_state"
                               value="<?php echo h($d["permanent_state"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Country <span>*</span></label>
                        <input type="text" id="permanent_country" name="permanent_country"
                               value="<?php echo h($d["permanent_country"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>PIN Code <span>*</span></label>
                        <input type="text" id="permanent_pin_code" name="permanent_pin_code"
                               placeholder="Enter 6-digit PIN" maxlength="6"
                               value="<?php echo h($d["permanent_pin_code"]); ?>" required>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= 04 TEMPORARY ADDRESS ================= -->
        <div class="form-section">
            <div class="section-header">
                <div class="section-number">04</div>
                <h2>Temporary Address</h2>
            </div>
            <div class="section-body">

                <label class="checkbox-line">
                    <input type="checkbox" id="same_as_permanent" name="same_as_permanent">
                    Same as permanent address
                </label>

                <div class="form-grid">

                    <div class="form-group full">
                        <label>Address Line 1 <span>*</span></label>
                        <input type="text" id="temporary_address_line1" name="temporary_address_line1"
                               placeholder="House No., Building, Street"
                               value="<?php echo h($d["temporary_address_line1"]); ?>" required>
                    </div>

                    <div class="form-group full">
                        <label>Address Line 2</label>
                        <input type="text" id="temporary_address_line2" name="temporary_address_line2"
                               placeholder="Area, Landmark"
                               value="<?php echo h($d["temporary_address_line2"]); ?>">
                    </div>

                    <div class="form-group">
                        <label>City / Village <span>*</span></label>
                        <input type="text" id="temporary_city_village" name="temporary_city_village"
                               value="<?php echo h($d["temporary_city_village"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Taluka <span>*</span></label>
                        <input type="text" id="temporary_taluka" name="temporary_taluka"
                               value="<?php echo h($d["temporary_taluka"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>District <span>*</span></label>
                        <input type="text" id="temporary_district" name="temporary_district"
                               value="<?php echo h($d["temporary_district"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>State <span>*</span></label>
                        <input type="text" id="temporary_state" name="temporary_state"
                               value="<?php echo h($d["temporary_state"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Country <span>*</span></label>
                        <input type="text" id="temporary_country" name="temporary_country"
                               value="<?php echo h($d["temporary_country"]); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>PIN Code <span>*</span></label>
                        <input type="text" id="temporary_pin_code" name="temporary_pin_code"
                               placeholder="Enter 6-digit PIN" maxlength="6"
                               value="<?php echo h($d["temporary_pin_code"]); ?>" required>
                    </div>

                </div>
            </div>
        </div>

        <!-- ================= BUTTONS ================= -->
        <div class="button-area">
            <a href="index.php" class="back-btn">← Back</a>
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
    setupSameAddress();
</script>
</body>
</html>
