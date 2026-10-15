<?php
/* =====================================================
   DATABASE CONNECTION
   Database name = admissionform (import admissionform.sql first)
===================================================== */

mysqli_report(MYSQLI_REPORT_OFF);          // we check errors ourselves
date_default_timezone_set("Asia/Kolkata"); // India time

$host     = "localhost";
$username = "root";
$password = "";               // XAMPP default is empty
$database = "admissionform";

$conn = mysqli_connect($host, $username, $password, $database);

if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");


/* =====================================================
   THE 3 COURSES
   Used in the student Academic form (dropdown)
   and in the Admin section (cards + filter).
   short name => full name saved in academic_details.course_branch
===================================================== */

$courses = [
    "CS"   => "B.E. Computer Science",
    "ENTC" => "B.E. Electronics & Telecommunication",
    "AIDS" => "B.E. Artificial Intelligence & Data Science"
];


/* =====================================================
   SMALL HELPER: make text safe to print in HTML
   h($name)  is the same as  htmlspecialchars($name)
===================================================== */

function h($text)
{
    return htmlspecialchars((string)$text, ENT_QUOTES, "UTF-8");
}
