<?php
require_once "auth.php";
require_once "db.php";
require_once "session.php";

$student_id = $_SESSION["student_id"];

/* =====================================================
   STUDENT DETAILS (name is used in the file name)
===================================================== */

$stmt = mysqli_prepare($conn, "SELECT fullname, application_submitted FROM students WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$student = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$student) {
    header("Location: logout.php");
    exit();
}

/* after final submission documents can only be viewed */
$locked = ($student["application_submitted"] == 1);

/* first word of the name, letters and numbers only  ->  "Shravani" */
$name_parts = explode(" ", trim($student["fullname"]));
$first_name = preg_replace("/[^A-Za-z0-9]/", "", $name_parts[0]);
if ($first_name == "") {
    $first_name = "student";
}


/* =====================================================
   THE 6 DOCUMENTS
   key  = value saved in student_documents.document_type
   type = image -> JPG/PNG (saved in uploads/img)
          pdf   -> PDF     (saved in uploads/pdf)
===================================================== */

$documents = [
    "student_photo"     => ["label" => "Student Photo",            "type" => "image"],
    "ssc_marksheet"     => ["label" => "SSC Marksheet",            "type" => "pdf"],
    "hsc_diploma"       => ["label" => "HSC / Diploma Marksheet",  "type" => "pdf"],
    "caste_certificate" => ["label" => "Caste Certificate",        "type" => "pdf"],
    "aadhaar_card"      => ["label" => "Aadhaar Card",             "type" => "pdf"],
    "pan_card"          => ["label" => "PAN Card",                 "type" => "pdf"]
];

$max_size = 2 * 1024 * 1024;   // 2 MB

$errors  = [];
$success = "";


/* =====================================================
   UPLOAD FORM SUBMITTED
===================================================== */

if ($_SERVER["REQUEST_METHOD"] == "POST" && !$locked) {

    $uploaded_now = 0;

    /* PHP drops everything when the total upload is bigger than post_max_size (8 MB in XAMPP) */
    if (empty($_POST) && empty($_FILES) && isset($_SERVER["CONTENT_LENGTH"]) && $_SERVER["CONTENT_LENGTH"] > 0) {
        $errors[] = "The files are too big to upload together. Please upload 2 or 3 documents at a time.";
    }

    foreach ($documents as $doc_key => $doc) {

        /* nothing chosen for this document -> skip */
        if (!isset($_FILES[$doc_key]) || $_FILES[$doc_key]["error"] == UPLOAD_ERR_NO_FILE) {
            continue;
        }

        $file = $_FILES[$doc_key];

        if ($file["error"] != UPLOAD_ERR_OK) {
            $errors[] = $doc["label"] . ": upload failed. Please try again.";
            continue;
        }

        if ($file["size"] > $max_size) {
            $errors[] = $doc["label"] . ": file is bigger than 2 MB.";
            continue;
        }

        /* ---------- check file type ---------- */

        $extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));

        if ($doc["type"] == "image") {
            $allowed_extensions = ["jpg", "jpeg", "png"];
            $allowed_mimes      = ["image/jpeg", "image/png"];
            $folder             = "uploads/img/";
            $type_text          = "JPG or PNG image";
        } else {
            $allowed_extensions = ["pdf"];
            $allowed_mimes      = ["application/pdf"];
            $folder             = "uploads/pdf/";
            $type_text          = "PDF file";
        }

        if (!in_array($extension, $allowed_extensions, true)) {
            $errors[] = $doc["label"] . ": only " . $type_text . " is allowed.";
            continue;
        }

        /* check the real content of the file (if fileinfo is enabled) */
        if (function_exists("finfo_open")) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $file["tmp_name"]);
            finfo_close($finfo);

            if (!in_array($mime, $allowed_mimes, true)) {
                $errors[] = $doc["label"] . ": only " . $type_text . " is allowed.";
                continue;
            }
        }

        /* ---------- new file name ----------
           firstname_studentid_doctype_date_time.ext
           example: Shravani_1_pan_card_20260827_163829.pdf   */

        $stored_filename = $first_name . "_" . $student_id . "_" . $doc_key . "_" . date("Ymd_His") . "." . $extension;
        $file_path       = $folder . $stored_filename;

        /* full path on the computer (the database keeps the short path) */
        $full_folder = __DIR__ . "/" . $folder;
        $full_path   = __DIR__ . "/" . $file_path;

        if (!is_dir($full_folder)) {
            mkdir($full_folder, 0777, true);
        }

        /* ---------- old file of this document (to delete after replace) ---------- */

        $stmt = mysqli_prepare($conn, "SELECT file_path FROM student_documents WHERE student_id = ? AND document_type = ?");
        mysqli_stmt_bind_param($stmt, "is", $student_id, $doc_key);
        mysqli_stmt_execute($stmt);
        $old = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        /* ---------- move file to uploads folder ---------- */

        if (!move_uploaded_file($file["tmp_name"], $full_path)) {
            $errors[] = $doc["label"] . ": could not save the file. Check the uploads folder.";
            continue;
        }

        /* ---------- save in database (INSERT, or UPDATE if already uploaded) ---------- */

        $sql = "INSERT INTO student_documents
                (student_id, document_type, original_filename, stored_filename, file_path)
                VALUES (?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                original_filename = VALUES(original_filename),
                stored_filename   = VALUES(stored_filename),
                file_path         = VALUES(file_path),
                uploaded_at       = CURRENT_TIMESTAMP";

        $original = basename($file["name"]);

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issss", $student_id, $doc_key, $original, $stored_filename, $file_path);

        if (mysqli_stmt_execute($stmt)) {

            $uploaded_now++;

            /* delete the old file from the folder */
            if ($old && $old["file_path"] != $file_path && is_file(__DIR__ . "/" . $old["file_path"])) {
                unlink(__DIR__ . "/" . $old["file_path"]);
            }

        } else {
            $errors[] = $doc["label"] . ": could not save in database.";
            unlink($full_path);
        }

        mysqli_stmt_close($stmt);
    }

    if ($uploaded_now > 0) {
        $success = $uploaded_now . " document(s) uploaded successfully.";
    } elseif (count($errors) == 0) {
        $errors[] = "Please choose at least one file to upload.";
    }
}


/* =====================================================
   GET UPLOADED DOCUMENTS  (after any upload above)
===================================================== */

$uploaded = [];

$stmt = mysqli_prepare($conn, "SELECT document_type, original_filename, file_path, uploaded_at FROM student_documents WHERE student_id = ?");
mysqli_stmt_bind_param($stmt, "i", $student_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $uploaded[$row["document_type"]] = $row;
}
mysqli_stmt_close($stmt);

$all_uploaded = (count($uploaded) >= count($documents));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<div class="form-container">

    <div class="page-header">
        <h1>📄 Documents</h1>
        <p>
            <?php echo $locked ? "Your uploaded documents" : "Photo: JPG / PNG &middot; Other documents: PDF &middot; Maximum 2 MB each"; ?>
        </p>
    </div>

    <?php if ($success != "") { ?>
        <div class="message success"><?php echo h($success); ?></div>
    <?php } ?>

    <?php foreach ($errors as $error) { ?>
        <div class="message error"><?php echo h($error); ?></div>
    <?php } ?>

    <form method="POST" enctype="multipart/form-data">

        <div class="form-section">
            <div class="section-body">

                <?php foreach ($documents as $doc_key => $doc) { ?>

                    <div class="document-row">

                        <div class="document-info">
                            <h3><?php echo h($doc["label"]); ?></h3>

                            <?php if (isset($uploaded[$doc_key])) { ?>
                                <p class="doc-status done">
                                    ✓ Uploaded:
                                    <a href="<?php echo h($uploaded[$doc_key]["file_path"]); ?>" target="_blank">
                                        <?php echo h($uploaded[$doc_key]["original_filename"]); ?>
                                    </a>
                                </p>
                            <?php } else { ?>
                                <p class="doc-status pending">Not uploaded yet</p>
                            <?php } ?>
                        </div>

                        <?php if (!$locked) { ?>
                            <div class="document-upload">
                                <input type="file" name="<?php echo h($doc_key); ?>"
                                       accept="<?php echo $doc["type"] == "image" ? ".jpg,.jpeg,.png" : ".pdf"; ?>">
                                <?php if (isset($uploaded[$doc_key])) { ?>
                                    <div class="hint">Choose a new file only if you want to replace it</div>
                                <?php } ?>
                            </div>
                        <?php } ?>

                    </div>

                <?php } ?>

            </div>
        </div>

        <div class="button-area">
            <a href="<?php echo $locked ? "index.php" : "academic.php"; ?>" class="back-btn">← Back</a>

            <?php if (!$locked) { ?>
                <div class="button-group">
                    <button type="submit" class="save-btn">Upload Documents</button>
                    <a href="index.php" class="save-btn secondary link-btn">Go to Dashboard</a>
                </div>
            <?php } ?>
        </div>

    </form>
</div>

<?php require_once "footer.php"; ?>
</body>
</html>
