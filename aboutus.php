<?php
require_once "auth.php";
require_once "db.php";
require_once "session.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us | Admission Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🎓</text></svg>">
</head>
<body>

<?php require_once "header.php"; ?>

<div class="form-container">

    <div class="page-header">
        <h1>🎓 About Us</h1>
        <p>Online admission for engineering courses</p>
    </div>

    <div class="form-section">
        <div class="section-body">
            <p class="about-text">
                The Admission Dashboard helps students apply for Engineering admission online.
                Fill your details, upload your documents and submit your application from one place.
            </p>
        </div>
    </div>

    <div class="form-section">
        <div class="section-header">
            <div class="section-number">1</div>
            <h2>Courses Available</h2>
        </div>
        <div class="section-body">
            <div class="course-list">
                <?php foreach ($courses as $short => $full) { ?>
                    <div class="course-item">
                        <strong><?php echo h($short); ?></strong>
                        <span><?php echo h($full); ?></span>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="section-header">
            <div class="section-number">2</div>
            <h2>How to Apply</h2>
        </div>
        <div class="section-body">
            <ol class="about-steps">
                <li>Register and sign in to your account.</li>
                <li>Fill your Personal Details.</li>
                <li>Fill your Academic Details and choose your course.</li>
                <li>Upload all 6 documents.</li>
                <li>Check everything and press Submit Application.</li>
            </ol>
        </div>
    </div>

    <div class="form-section">
        <div class="section-header">
            <div class="section-number">3</div>
            <h2>Need Help?</h2>
        </div>
        <div class="section-body">
            <p class="about-text">Contact the admission office:</p>
            <p class="about-text">📧 admission@example.com</p>
            <p class="about-text">📞 +91 XXXXX XXXXX</p>
        </div>
    </div>

</div>

<?php require_once "footer.php"; ?>
</body>
</html>
