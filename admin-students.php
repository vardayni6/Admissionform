<?php
require_once "admin-auth.php";
require_once "db.php";
require_once "session.php";

/* =====================================================
   FILTERS (from the address bar)
   ?course=CS   ?status=submitted   ?q=name
===================================================== */

$course_filter = $_GET["course"] ?? "";
$status_filter = $_GET["status"] ?? "";
$search        = trim($_GET["q"] ?? "");

/* only accept CS / ENTC / AIDS */
if (!isset($courses[$course_filter])) {
    $course_filter = "";
}

if ($status_filter != "submitted" && $status_filter != "pending") {
    $status_filter = "";
}

/* =====================================================
   BUILD THE QUERY
===================================================== */

$sql = "SELECT s.student_id, s.fullname, s.email, s.mobile, s.application_submitted, s.application_number,
               a.course_branch, a.admission_type, a.hsc_percentage
        FROM students s
        LEFT JOIN academic_details a ON a.student_id = s.student_id
        WHERE 1 = 1";

$types  = "";
$values = [];

if ($course_filter != "") {
    $sql     .= " AND a.course_branch = ?";
    $types   .= "s";
    $values[] = $courses[$course_filter];
}

if ($status_filter == "submitted") {
    $sql .= " AND s.application_submitted = 1";
} elseif ($status_filter == "pending") {
    $sql .= " AND s.application_submitted = 0";
}

if ($search != "") {
    $sql     .= " AND (s.fullname LIKE ? OR s.email LIKE ? OR s.mobile LIKE ? OR s.application_number LIKE ?)";
    $types   .= "ssss";
    $like     = "%" . $search . "%";
    $values[] = $like;
    $values[] = $like;
    $values[] = $like;
    $values[] = $like;
}

$sql .= " ORDER BY s.student_id DESC";

$stmt = mysqli_prepare($conn, $sql);

if ($types != "") {
    mysqli_stmt_bind_param($stmt, $types, ...$values);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$students = [];
while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}
mysqli_stmt_close($stmt);

/* link for the course tabs (keeps status + search) */
function tabLink($course, $status_filter, $search)
{
    $link = "admin-students.php?course=" . urlencode($course);
    if ($status_filter != "") {
        $link .= "&status=" . urlencode($status_filter);
    }
    if ($search != "") {
        $link .= "&q=" . urlencode($search);
    }
    return $link;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "admin-header.php"; ?>

<div class="wide-container">

    <div class="page-header">
        <h1>👨‍🎓 Students</h1>
        <p>Open a student to see full details and documents</p>
    </div>

    <!-- ============ COURSE TABS ============ -->
    <div class="tabs">
        <a href="<?php echo tabLink("", $status_filter, $search); ?>"
           class="<?php echo $course_filter == "" ? "active" : ""; ?>">All</a>

        <?php foreach ($courses as $short => $full) { ?>
            <a href="<?php echo tabLink($short, $status_filter, $search); ?>"
               class="<?php echo $course_filter == $short ? "active" : ""; ?>"
               title="<?php echo h($full); ?>"><?php echo h($short); ?></a>
        <?php } ?>
    </div>

    <!-- ============ SEARCH + STATUS ============ -->
    <form method="GET" class="filter-bar">

        <input type="hidden" name="course" value="<?php echo h($course_filter); ?>">

        <input type="text" name="q" placeholder="Search name, email, mobile or application no."
               value="<?php echo h($search); ?>">

        <select name="status">
            <option value="">All Status</option>
            <option value="submitted" <?php echo $status_filter == "submitted" ? "selected" : ""; ?>>Submitted</option>
            <option value="pending"   <?php echo $status_filter == "pending" ? "selected" : ""; ?>>Not Submitted</option>
        </select>

        <button type="submit" class="save-btn">Search</button>
        <a href="admin-students.php" class="save-btn secondary link-btn">Reset</a>

    </form>

    <!-- ============ TABLE ============ -->
    <div class="table-box">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Application No.</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Course</th>
                    <th>Admission Type</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>

                <?php if (count($students) == 0) { ?>
                    <tr><td colspan="9" class="empty-cell">No students found.</td></tr>
                <?php } ?>

                <?php foreach ($students as $number => $row) { ?>
                    <tr>
                        <td><?php echo $number + 1; ?></td>
                        <td><?php echo h($row["application_number"] ?: "-"); ?></td>
                        <td><?php echo h($row["fullname"]); ?></td>
                        <td><?php echo h($row["email"]); ?></td>
                        <td><?php echo h($row["mobile"]); ?></td>
                        <td><?php echo h($row["course_branch"] ?: "-"); ?></td>
                        <td><?php echo h($row["admission_type"] ?: "-"); ?></td>
                        <td>
                            <?php if ($row["application_submitted"] == 1) { ?>
                                <span class="status-badge status-done">Submitted</span>
                            <?php } else { ?>
                                <span class="status-badge status-pending">In Process</span>
                            <?php } ?>
                        </td>
                        <td><a href="admin-student-view.php?id=<?php echo (int)$row["student_id"]; ?>" class="table-link">View</a></td>
                    </tr>
                <?php } ?>

            </tbody>
        </table>
    </div>

    <p class="result-count">Total: <?php echo count($students); ?> student(s)</p>

</div>

<?php require_once "footer.php"; ?>
</body>
</html>


