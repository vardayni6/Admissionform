<?php
require_once "admin-auth.php";
require_once "db.php";
require_once "session.php";

/* =====================================================
   FILTERS
===================================================== */

$course_filter = $_GET["course"] ?? "";
$status_filter = $_GET["status"] ?? "";

/* Only accept valid course keys */
if (!isset($courses[$course_filter])) {
    $course_filter = "";
}

/* Only accept valid submission filters */
if ($status_filter !== "submitted" && $status_filter !== "pending") {
    $status_filter = "";
}

/* =====================================================
   BUILD QUERY
===================================================== */

$sql = "SELECT
            s.student_id,
            s.fullname,
            s.application_submitted,
            s.application_number,
            s.application_status,
            s.review_status,
            s.admin_action,
            s.admin_remark,
            s.submitted_at,
            s.resubmitted_at,
            s.created_at,
            a.course_branch,
            a.admission_type,
            a.hsc_percentage
        FROM students s
        LEFT JOIN academic_details a
            ON a.student_id = s.student_id
        WHERE 1 = 1";

$types  = "";
$values = [];

/* Course filter */
if ($course_filter !== "") {
    $sql .= " AND a.course_branch = ?";
    $types .= "s";
    $values[] = $courses[$course_filter];
}

/* Submission filter */
if ($status_filter === "submitted") {
    $sql .= " AND s.application_submitted = 1";
} elseif ($status_filter === "pending") {
    $sql .= " AND s.application_submitted = 0";
}

/* Search filter optional 
if ($search !== "") {
    $sql .= " AND (
        s.fullname LIKE ?
        OR s.email LIKE ?
        OR s.mobile LIKE ?
        OR s.application_number LIKE ?
    )";

    $types .= "ssss";

    $like = "%" . $search . "%";

    $values[] = $like;
    $values[] = $like;
    $values[] = $like;
    $values[] = $like;
} */

/*
 * Priority:
 * 1. Resubmitted applications awaiting review.
 * 2. Other submitted applications awaiting review.
 * 3. Remaining applications.
 *
 * Newest applications appear first within each priority.
 */

$sql .= " ORDER BY
    CASE
        WHEN s.application_submitted = 1
             AND s.review_status = 'Not Reviewed'
             AND s.application_status = 'Resubmitted'
        THEN 1

        WHEN s.application_submitted = 1
             AND s.review_status = 'Not Reviewed'
        THEN 2

        ELSE 3
    END ASC,
    COALESCE(
        s.resubmitted_at,
        s.submitted_at,
        s.created_at
    ) DESC,
    s.student_id DESC";

/* Execute query */
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Unable to prepare the student query.");
}

if ($types !== "") {
    mysqli_stmt_bind_param($stmt, $types, ...$values);
}

mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

$students = [];

while ($row = mysqli_fetch_assoc($result)) {
    $students[] = $row;
}

mysqli_stmt_close($stmt);

/* =====================================================
   COURSE TAB LINKS if $search filtter is set then include it in the tabLink function as $link .= "&q=" .(urlencode($search));
===================================================== */

function tabLink($course, $status_filter)
{
    $link = "admin-students.php?course=" . urlencode($course);

    if ($status_filter !== "") {
        $link .= "&status=" . urlencode($status_filter);
    }

    return $link;
}

/* =====================================================
   HELPER: GET APPLICATION STATUS
===================================================== */

function getApplicationStatus($row)
{
    $status = $row["application_status"] ?? "Draft";

    /*
     * Existing submitted applications may still have
     * the default database value "Draft".
     *
     * Display them as Submitted until their workflow
     * status is updated.
     */

    if (
        $row["application_submitted"] == 1
        && ($status === "" || $status === "Draft")
    ) {
        return "Submitted";
    }

    return $status;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Students | Admission Dashboard</title>

    <link rel="stylesheet" href="style.css">

    <link rel="icon"
          href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">

    <style>
        .review-badge {
            display: inline-block;
            padding: 6px 11px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        .review-not-reviewed {
            background: #fff0cc;
            color: #925400;
        }

        .review-reviewed {
            background: #d9f5e3;
            color: #087443;
        }

        .application-accepted {
            background: #d9f5e3;
            color: #087443;
        }

        .application-changes {
            background: #ffe3e3;
            color: #a61b1b;
        }

        .application-resubmitted {
            background: #dcecff;
            color: #145da0;
        }

        .application-submitted {
            background: #e8edf4;
            color: #34445a;
        }

        .application-draft {
            background: #eeeeee;
            color: #555555;
        }

        .remark-preview {
            max-width: 240px;
            white-space: normal;
            overflow-wrap: anywhere;
            line-height: 1.5;
            font-size: 13px;
            color: #475569;
        }

        .review-required {
            color: #b45309;
            font-weight: 600;
        }

        .table-box {
            overflow-x: auto;
        }

        .table-box table {
            min-width: 1250px;
        }

        .table-box th,
        .table-box td {
            vertical-align: middle;
        }
    </style>
</head>

<body>

<?php require_once "admin-header.php"; ?>

<div class="wide-container">

    <div class="page-header">
        <h1>👨‍🎓 Students</h1>

        <p>
            Review applications, check documents,
            and manage student corrections.
        </p>
    </div>

    <!-- ============ COURSE TABS ============ $search is required here if use it add it in tabLink-->

    <div class="tabs">

        <a href="<?php echo h(tabLink("", $status_filter)); ?>"
           class="<?php echo $course_filter === "" ? "active" : ""; ?>">
            All
        </a>

        <?php foreach ($courses as $short => $full) { ?>

            <a href="<?php echo h(tabLink($short, $status_filter)); ?>"
               class="<?php echo $course_filter === $short ? "active" : ""; ?>"
               title="<?php echo h($full); ?>">

                <?php echo h($short); ?>

            </a>

        <?php } ?>

    </div>

    <!-- ============ SEARCH + STATUS ============ 

    <form method="GET" class="filter-bar">

        <input
            type="hidden"
            name="course"
            value="<?php echo h($course_filter); ?>"
        >

        <input
            type="text"
            name="q"
            placeholder="Search name, email, mobile or application no."
            value="<?php echo h($search); ?>"
        >

        <select name="status">

            <option value="">All Status</option>

            <option
                value="submitted"
                <?php echo $status_filter === "submitted" ? "selected" : ""; ?>
            >
                Submitted
            </option>

            <option
                value="pending"
                <?php echo $status_filter === "pending" ? "selected" : ""; ?>
            >
                Not Submitted
            </option>

        </select>

        <button type="submit" class="save-btn">
            Search
        </button>

        <a href="admin-students.php"
           class="save-btn secondary link-btn">
            Reset
        </a>

    </form> -->

    <!-- ============ STUDENT TABLE ============ -->

    <div class="table-box">

        <table>

            <thead>

                <tr>
                    <th>#</th>
                    <th>Application No.</th>
                    <th>Name</th>
                    <th>Course</th>
                    <th>Admission Type</th>
                    <th>Application Status</th>
                    <th>Review Status</th>
                    <th>Action / Remark</th>
                    <th>View</th>
                </tr>

            </thead>

            <tbody>

            <?php if (count($students) === 0) { ?>

                <tr>
                    <td colspan="11" class="empty-cell">
                        No students found.
                    </td>
                </tr>

            <?php } ?>

            <?php foreach ($students as $number => $row) { ?>

                <?php
                $applicationStatus = getApplicationStatus($row);

                $reviewStatus = $row["review_status"] ?? "Not Reviewed";

                $adminAction = $row["admin_action"] ?? "";

                $remark = trim($row["admin_remark"] ?? "");

                if ($reviewStatus === "") {
                    $reviewStatus = "Not Reviewed";
                }

                /*
                 * Review status badge.
                 */

                $reviewClass = (
                    $reviewStatus === "Reviewed"
                ) ? "review-reviewed" : "review-not-reviewed";

                /*
                 * Application status badge.
                 */

                $applicationClass = "application-draft";

                if ($applicationStatus === "Accepted") {
                    $applicationClass = "application-accepted";
                } elseif ($applicationStatus === "Changes Requested") {
                    $applicationClass = "application-changes";
                } elseif ($applicationStatus === "Resubmitted") {
                    $applicationClass = "application-resubmitted";
                } elseif ($applicationStatus === "Submitted") {
                    $applicationClass = "application-submitted";
                }
                ?>

                <tr>

                    <td>
                        <?php echo $number + 1; ?>
                    </td>

                    <td>
                        <?php echo h($row["application_number"] ?: "-"); ?>
                    </td>

                    <td>
                        <?php echo h($row["fullname"]); ?>
                    </td>

                    <td>
                        <?php echo h($row["course_branch"] ?: "-"); ?>
                    </td>

                    <td>
                        <?php echo h($row["admission_type"] ?: "-"); ?>
                    </td>

                    <!-- Application status -->

                    <td>

                        <?php if ($row["application_submitted"] == 1) { ?>

                            <span class="review-badge <?php echo h($applicationClass); ?>">
                                <?php echo h($applicationStatus); ?>
                            </span>

                        <?php } else { ?>

                            <span class="status-badge status-pending">
                                In Process
                            </span>

                        <?php } ?>

                    </td>

                    <!-- Review status -->

                    <td>

                        <?php if ($row["application_submitted"] == 1) { ?>

                            <span class="review-badge <?php echo h($reviewClass); ?>">
                                <?php echo h($reviewStatus); ?>
                            </span>

                        <?php } else { ?>

                            <span class="review-badge review-not-reviewed">
                                Not Submitted
                            </span>

                        <?php } ?>

                    </td>

                    <!-- Admin action and remark -->

                    <td>

                        <?php if ($row["application_submitted"] != 1) { ?>

                            <span class="remark-preview">
                                Application not submitted
                            </span>

                        <?php } elseif ($reviewStatus === "Not Reviewed") { ?>

                            <span class="review-required">
                                Pending Review
                            </span>

                            <?php if ($applicationStatus === "Resubmitted") { ?>
                                <br>
                                <small>Student resubmitted</small>
                            <?php } ?>

                        <?php } else { ?>

                            <strong>
                                <?php echo h($adminAction ?: $applicationStatus); ?>
                            </strong>

                            <?php if ($remark !== "") { ?>

                                <div class="remark-preview">
                                    <?php
                                    echo h(
                                        strlen($remark) > 100
                                            ? substr($remark, 0, 100) . "..."
                                            : $remark
                                    );
                                    ?>
                                </div>

                            <?php } ?>

                        <?php } ?>

                    </td>

                    <!-- View application -->

                    <td>

                        <a
                            href="admin-student-view.php?id=<?php echo (int)$row["student_id"]; ?>"
                            class="table-link"
                        >
                            View
                        </a>

                    </td>

                </tr>

            <?php } ?>

            </tbody>

        </table>

    </div>

    <p class="result-count">
        Total: <?php echo count($students); ?> student(s)
    </p>

</div>

<?php require_once "footer.php"; ?>

</body>
</html>
