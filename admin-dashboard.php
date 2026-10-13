<?php
require_once "admin-auth.php";
require_once "db.php";
require_once "session.php";

/* =====================================================
   1. TOTAL NUMBERS
===================================================== */

$sql = "SELECT COUNT(*) AS total,
               COALESCE(SUM(application_submitted = 1), 0) AS submitted
        FROM students";

$row = mysqli_fetch_assoc(mysqli_query($conn, $sql));

$total_students = (int)$row["total"];
$submitted      = (int)$row["submitted"];
$not_submitted  = $total_students - $submitted;


/* =====================================================
   2. NUMBERS FOR EACH COURSE (CS, ENTC, AIDS)
   For every course: how many chose it, and how many of them submitted
===================================================== */

$course_total     = [];
$course_submitted = [];

$sql = "SELECT a.course_branch,
               COUNT(*) AS chosen,
               SUM(s.application_submitted = 1) AS submitted
        FROM academic_details a
        JOIN students s ON s.student_id = a.student_id
        GROUP BY a.course_branch";

$result = mysqli_query($conn, $sql);

while ($row = mysqli_fetch_assoc($result)) {
    $course_total[$row["course_branch"]]     = (int)$row["chosen"];
    $course_submitted[$row["course_branch"]] = (int)$row["submitted"];
}


/* =====================================================
   3. LATEST 5 SUBMITTED APPLICATIONS
===================================================== */

$sql = "SELECT s.student_id, s.fullname, s.application_number, s.submitted_at, a.course_branch
        FROM students s
        LEFT JOIN academic_details a ON a.student_id = s.student_id
        WHERE s.application_submitted = 1
        ORDER BY s.submitted_at DESC
        LIMIT 5";

$latest = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "admin-header.php"; ?>

<main class="dashboard">
    <div class="dashboard-container">

        <section class="welcome-section">
            <div>
                <p class="welcome-small">🎓 Admin Dashboard</p>
                <h1>Welcome, <?php echo h($_SESSION["admin_name"]); ?> 👋</h1>
                <p class="welcome-text">Here is the summary of all student admissions.</p>
            </div>
        </section>

        <!-- ============ TOTAL NUMBERS ============ -->
        <section class="stat-grid">

            <div class="stat-card">
                <span class="stat-number"><?php echo $total_students; ?></span>
                <span class="stat-label">Registered Students</span>
            </div>

            <div class="stat-card stat-green">
                <span class="stat-number"><?php echo $submitted; ?></span>
                <span class="stat-label">Applications Submitted</span>
            </div>

            <div class="stat-card stat-orange">
                <span class="stat-number"><?php echo $not_submitted; ?></span>
                <span class="stat-label">Not Submitted Yet</span>
            </div>

        </section>

        <!-- ============ 3 COURSES ============ -->
        <section class="dashboard-section">
            <h2 class="section-title">Courses</h2>

            <div class="dashboard-cards">

                <?php foreach ($courses as $short => $full) {
                    $chosen = $course_total[$full] ?? 0;
                    $done   = $course_submitted[$full] ?? 0;
                ?>
                    <div class="dashboard-card">
                        <div class="card-icon">🎓</div>
                        <h3><?php echo h($short); ?></h3>
                        <p><?php echo h($full); ?></p>

                        <div class="course-numbers">
                            <div><strong><?php echo $chosen; ?></strong><span>Selected</span></div>
                            <div><strong><?php echo $done; ?></strong><span>Submitted</span></div>
                        </div>

                        <a href="admin-students.php?course=<?php echo h($short); ?>" class="dashboard-button">
                            View Students →
                        </a>
                    </div>
                <?php } ?>

            </div>
        </section>

        <!-- ============ LATEST APPLICATIONS ============ -->
        <section class="dashboard-section">
            <h2 class="section-title">Latest Submitted Applications</h2>

            <div class="table-box">
                <table>
                    <thead>
                        <tr>
                            <th>Application No.</th>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Submitted On</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($latest) == 0) { ?>
                            <tr><td colspan="5" class="empty-cell">No application submitted yet.</td></tr>
                        <?php } ?>

                        <?php while ($row = mysqli_fetch_assoc($latest)) { ?>
                            <tr>
                                <td><?php echo h($row["application_number"]); ?></td>
                                <td><?php echo h($row["fullname"]); ?></td>
                                <td><?php echo h($row["course_branch"]); ?></td>
                                <td><?php echo h(date("d M Y, h:i A", strtotime($row["submitted_at"]))); ?></td>
                                <td><a href="admin-student-view.php?id=<?php echo (int)$row["student_id"]; ?>" class="table-link">View</a></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </section>

    </div>
</main>

<?php require_once "footer.php"; ?>
</body>
</html>
