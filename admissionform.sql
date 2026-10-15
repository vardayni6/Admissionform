-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 07, 2026 at 07:58 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `admissionform`
--

-- --------------------------------------------------------

--
-- Table structure for table `academic_details`
--

CREATE TABLE `academic_details` (
  `id` int(11) NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `ssc_school` varchar(255) NOT NULL,
  `ssc_board` varchar(100) NOT NULL,
  `ssc_passing_year` year(4) NOT NULL,
  `ssc_total_marks` int(11) NOT NULL,
  `ssc_marks_obtained` int(11) NOT NULL,
  `ssc_percentage` decimal(5,2) NOT NULL,
  `hsc_qualification` varchar(50) NOT NULL,
  `hsc_college` varchar(255) NOT NULL,
  `hsc_board_university` varchar(255) NOT NULL,
  `hsc_stream_branch` varchar(100) DEFAULT NULL,
  `hsc_passing_year` year(4) NOT NULL,
  `hsc_total_marks` int(11) NOT NULL,
  `hsc_marks_obtained` int(11) NOT NULL,
  `hsc_percentage` decimal(5,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `admission_type` varchar(100) DEFAULT NULL,
  `course_branch` varchar(255) DEFAULT NULL,
  `preferred_college` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `academic_details`
--

INSERT INTO `academic_details` (`id`, `student_id`, `ssc_school`, `ssc_board`, `ssc_passing_year`, `ssc_total_marks`, `ssc_marks_obtained`, `ssc_percentage`, `hsc_qualification`, `hsc_college`, `hsc_board_university`, `hsc_stream_branch`, `hsc_passing_year`, `hsc_total_marks`, `hsc_marks_obtained`, `hsc_percentage`, `created_at`, `updated_at`, `admission_type`, `course_branch`, `preferred_college`) VALUES
(4, 3, 'Abhinav', 'Maharashtra Board', '2023', 500, 440, 88.00, 'Diploma', 'TSSMS', 'MSBTE', 'CS', '2026', 800, 650, 81.25, '2026-09-17 09:17:40', '2026-09-17 11:06:30', 'CAP', 'B.E. Computer Science', 'Keystone'),
(5, 2, 'sfs school', 'Maharashtra Board', '2020', 500, 380, 76.00, 'Diploma', 'BVP', 'msbte', 'cs', '2023', 800, 700, 87.50, '2026-09-17 10:54:24', '2026-09-17 11:07:10', 'CAP', 'B.E. Computer Science', 'BVP'),
(6, 1, 'HHCP', 'Maharashtra Board', '2026', 500, 380, 76.00, 'Diploma', 'BVP', 'MSBTE', 'Cmputer Technology', '2023', 800, 660, 82.50, '2026-09-17 11:03:12', '2026-09-17 11:03:12', 'CAP', 'B.E. Artificial Intelligence & Data Science', 'TSSMS'),
(7, 5, 'abhinav', 'Maharashtra Board', '2023', 500, 380, 76.00, 'HSC', 'SP', 'sppu', 'Science', '2025', 700, 470, 67.14, '2026-09-22 17:30:57', '2026-09-22 17:30:57', 'Institute Level', 'B.E. Electronics & Telecommunication', 'JSMP');

-- --------------------------------------------------------

--
-- Table structure for table `admins`
--

CREATE TABLE `admins` (
  `admin_id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admins`
--

INSERT INTO `admins` (`admin_id`, `fullname`, `email`, `password`, `created_at`) VALUES
(1, 'Vardayni Bidve', 'vardaynibidve@gmail.com', '$2y$10$mx.XURdLQIAEifnMtzss6OZ6VTbs4.AuhGwPKTc4Mvl9lkcf9w9Wu', '2026-09-22 18:35:38'),
(2, 'Admin', 'admin@example.com', '$2y$10$5cnwlaPXUojU6RieLkq07O8cfQO/yZa7CJ.egx8b9pcZh2ZSgZwqe', '2026-09-23 05:53:43');

-- --------------------------------------------------------

--
-- Table structure for table `personal_details`
--

CREATE TABLE `personal_details` (
  `id` int(11) NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `date_of_birth` date NOT NULL,
  `gender` varchar(20) NOT NULL,
  `nationality` varchar(50) NOT NULL,
  `mother_tongue` varchar(50) NOT NULL,
  `blood_group` varchar(10) NOT NULL,
  `aadhaar_number` varchar(12) DEFAULT NULL,
  `father_name` varchar(100) NOT NULL,
  `mother_name` varchar(100) NOT NULL,
  `guardian_name` varchar(100) DEFAULT NULL,
  `father_occupation` varchar(100) NOT NULL,
  `mother_occupation` varchar(100) NOT NULL,
  `annual_family_income` varchar(50) NOT NULL,
  `parent_guardian_mobile` varchar(10) NOT NULL,
  `permanent_address_line1` varchar(255) DEFAULT NULL,
  `permanent_address_line2` varchar(255) DEFAULT NULL,
  `permanent_city_village` varchar(100) DEFAULT NULL,
  `permanent_taluka` varchar(100) DEFAULT NULL,
  `permanent_district` varchar(100) DEFAULT NULL,
  `permanent_state` varchar(100) DEFAULT NULL,
  `permanent_country` varchar(100) DEFAULT 'India',
  `permanent_pin_code` varchar(6) DEFAULT NULL,
  `temporary_address_line1` varchar(255) DEFAULT NULL,
  `temporary_address_line2` varchar(255) DEFAULT NULL,
  `temporary_city_village` varchar(100) DEFAULT NULL,
  `temporary_taluka` varchar(100) DEFAULT NULL,
  `temporary_district` varchar(100) DEFAULT NULL,
  `temporary_state` varchar(100) DEFAULT NULL,
  `temporary_country` varchar(100) DEFAULT 'India',
  `temporary_pin_code` varchar(6) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `personal_details`
--

INSERT INTO `personal_details` (`id`, `student_id`, `date_of_birth`, `gender`, `nationality`, `mother_tongue`, `blood_group`, `aadhaar_number`, `father_name`, `mother_name`, `guardian_name`, `father_occupation`, `mother_occupation`, `annual_family_income`, `parent_guardian_mobile`, `permanent_address_line1`, `permanent_address_line2`, `permanent_city_village`, `permanent_taluka`, `permanent_district`, `permanent_state`, `permanent_country`, `permanent_pin_code`, `temporary_address_line1`, `temporary_address_line2`, `temporary_city_village`, `temporary_taluka`, `temporary_district`, `temporary_state`, `temporary_country`, `temporary_pin_code`, `created_at`, `updated_at`) VALUES
(1, 1, '2004-09-06', 'Female', 'Indian', 'Marathi', 'B+', '743312437869', 'Dhananjay Ashok Bidve', 'Dhanashri Dhanajay Bidve', '', 'Business', 'House wife', '₹2.5 Lakhs - ₹5 Lakhs', '9881118167', 'plot no 29 , shree ram maratha society', 'Taljai Pathar', 'pune', 'Haveli', 'pune', 'Maharashtra', 'India', '411043', 'plot no 29 , shree ram maratha society', 'Taljai Pathar', 'pune', 'Haveli', 'pune', 'Maharashtra', 'India', '411043', '2026-09-09 07:25:32', '2026-09-09 07:25:32'),
(2, 2, '2004-07-10', 'Female', 'Indian', 'Marathi', 'AB+', '743312437869', 'mahendra shivram arote', 'madhuri mahendra arote', '', 'Business', 'House wife', '₹5 Lakhs - ₹10 Lakhs', '9011345841', 'ekdant heights', 'pathardi phata', 'nashik', 'nashik', 'nashik', 'Maharashtra', 'India', '422010', 'ekdant heights', 'pathardi phata', 'nashik', 'nashik', 'nashik', 'Maharashtra', 'India', '422010', '2026-09-09 09:13:31', '2026-09-09 09:13:31'),
(3, 3, '2027-05-11', 'Male', 'Indian', 'Marathi', 'B+', '', 'Dhananjay Ashok Bidve', 'Dhanashri Dhanajay Bidve', '', 'Business', 'House wife', '₹1 Lakh - ₹2.5 Lakhs', '7490145678', 'Taljai Pathar', 'shree ram maratha society, plot no 29', 'pune', 'Haveli', 'pune', 'Maharashtra', 'India', '411043', 'Taljai Pathar', 'shree ram maratha society, plot no 29', 'pune', 'Haveli', 'pune', 'Maharashtra', 'India', '411043', '2026-09-09 09:28:16', '2026-09-09 09:28:16'),
(4, 5, '2007-05-11', 'Male', 'Indian', 'Marathi', 'B+', '', 'Dhananjay Ashok Bidve', 'Dhanashri Dhanajay Bidve', '', 'Business', 'House wife', '₹1 Lakh - ₹2.5 Lakhs', '8769543278', 'Taljai Pathar', 'shree ram maratha society, plot no 29', 'pune', 'Haveli', 'pune', 'Maharashtra', 'India', '411043', 'Taljai Pathar', 'shree ram maratha society, plot no 29', 'pune', 'Haveli', 'pune', 'Maharashtra', 'India', '411043', '2026-09-22 17:28:34', '2026-09-22 17:28:34');

-- --------------------------------------------------------

--
-- Table structure for table `students`
--

CREATE TABLE `students` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `mobile` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `application_submitted` tinyint(1) NOT NULL DEFAULT 0,
  `submitted_at` datetime DEFAULT NULL,
  `application_number` varchar(30) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `students`
--

INSERT INTO `students` (`student_id`, `fullname`, `email`, `mobile`, `password`, `created_at`, `application_submitted`, `submitted_at`, `application_number`) VALUES
(1, 'Shravani Bidve', 'bidveshravani73@gmail.com', '7558293567', '$2y$10$iRcNatGde3A9m28XbE4eYuZKZC3ULq8xhiSzd0UqugNeogFpgpKKq', '2026-08-27 07:25:16', 0, NULL, NULL),
(2, 'rutuja arote', 'rutujaarote10@gmail.com', '8483835932', '$2y$10$IVsfKGXb7NpuE4mLFN1/FujZUSpr78wN5YjjCJy73UO.fxQ57uAOu', '2026-09-09 09:11:04', 1, '2026-09-17 16:24:55', 'ADM2026000002'),
(3, 'parth Bidve', 'parthbidve522@gmail.com', '7490483217', '$2y$10$DZ7u38x1Ub1yW8DhSSAXxOenu5WM5KHwrqsxOA1VyG23rw2mwNwWe', '2026-09-09 09:22:44', 1, '2026-09-17 16:20:05', 'ADM2026000003'),
(4, 'Smriti Saxena', 'smritisaxena1@gmail.com', '8956432367', '$2y$10$Q6c9/Agp.Lw5qZr0StY.MuYY8891uQLA1XKTT0.tJLhxDmS4n3ML.', '2026-09-17 11:09:54', 0, NULL, NULL),
(5, 'krushna bidve', 'krushnabidve@gmail.com', '8967453215', '$2y$10$9h9TRhb5HbRNXQ.BC2gks.F8ZNo/OqLAexpIKYIvyBd8tgxr91abW', '2026-09-22 17:24:44', 1, '2026-09-22 23:04:51', 'ADM2026000005');

-- --------------------------------------------------------

--
-- Table structure for table `student_documents`
--

CREATE TABLE `student_documents` (
  `id` int(11) NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `document_type` varchar(50) NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `student_documents`
--

INSERT INTO `student_documents` (`id`, `student_id`, `document_type`, `original_filename`, `stored_filename`, `file_path`, `uploaded_at`) VALUES
(1, 1, 'student_photo', 'IMG-20170704-WA0021.jpg', 'Shravani_1_student_photo_20260827_150738.jpg', 'uploads/img/Shravani_1_student_photo_20260827_150738.jpg', '2026-08-27 09:37:39'),
(2, 1, 'ssc_marksheet', 'shravLc school (1).pdf', 'Shravani_1_ssc_marksheet_20260827_150738.pdf', 'uploads/pdf/Shravani_1_ssc_marksheet_20260827_150738.pdf', '2026-08-27 09:37:39'),
(3, 1, 'hsc_diploma', 'diploma L.C.pdf', 'Shravani_1_hsc_diploma_20260827_150738.pdf', 'uploads/pdf/Shravani_1_hsc_diploma_20260827_150738.pdf', '2026-08-27 09:37:39'),
(4, 1, 'caste_certificate', 'caste c me.pdf', 'Shravani_1_caste_certificate_20260827_150738.pdf', 'uploads/pdf/Shravani_1_caste_certificate_20260827_150738.pdf', '2026-08-27 09:37:39'),
(5, 1, 'aadhaar_card', 'adhar s.pdf', 'Shravani_1_aadhaar_card_20260827_150738.pdf', 'uploads/pdf/Shravani_1_aadhaar_card_20260827_150738.pdf', '2026-08-27 09:37:39'),
(7, 1, 'pan_card', 'BE Final fees.pdf', 'Shravani_1_pan_card_20260827_163829.pdf', 'uploads/pdf/Shravani_1_pan_card_20260827_163829.pdf', '2026-08-27 11:08:29'),
(8, 2, 'student_photo', '25pratidnyapatr.jpeg', 'rutuja_2_student_photo_20260909_144900.jpeg', 'uploads/img/rutuja_2_student_photo_20260909_144900.jpeg', '2026-09-09 09:19:00'),
(9, 2, 'ssc_marksheet', 'shravLc school (1).pdf', 'rutuja_2_ssc_marksheet_20260909_144900.pdf', 'uploads/pdf/rutuja_2_ssc_marksheet_20260909_144900.pdf', '2026-09-09 09:19:00'),
(10, 2, 'hsc_diploma', 'diploma L.C.pdf', 'rutuja_2_hsc_diploma_20260909_144900.pdf', 'uploads/pdf/rutuja_2_hsc_diploma_20260909_144900.pdf', '2026-09-09 09:19:00'),
(11, 2, 'caste_certificate', 'sem 3 SE certificate .pdf', 'rutuja_2_caste_certificate_20260909_144900.pdf', 'uploads/pdf/rutuja_2_caste_certificate_20260909_144900.pdf', '2026-09-09 09:19:00'),
(12, 2, 'aadhaar_card', 'adhar s.pdf', 'rutuja_2_aadhaar_card_20260909_144900.pdf', 'uploads/pdf/rutuja_2_aadhaar_card_20260909_144900.pdf', '2026-09-09 09:19:00'),
(13, 2, 'pan_card', 'TE fees R.pdf', 'rutuja_2_pan_card_20260909_144900.pdf', 'uploads/pdf/rutuja_2_pan_card_20260909_144900.pdf', '2026-09-09 09:19:00'),
(14, 3, 'student_photo', '2025 FORM C.jpg', 'parth_3_student_photo_20260909_150240.jpg', 'uploads/img/parth_3_student_photo_20260909_150240.jpg', '2026-09-09 09:32:40'),
(15, 3, 'ssc_marksheet', 'bonafide parth24-25.pdf', 'parth_3_ssc_marksheet_20260909_150240.pdf', 'uploads/pdf/parth_3_ssc_marksheet_20260909_150240.pdf', '2026-09-09 09:32:40'),
(16, 3, 'hsc_diploma', 'DSE26149159 Application Print.pdf', 'parth_3_hsc_diploma_20260909_150240.pdf', 'uploads/pdf/parth_3_hsc_diploma_20260909_150240.pdf', '2026-09-09 09:32:40'),
(18, 3, 'aadhaar_card', 'cap3.pdf', 'parth_3_aadhaar_card_20260909_150240.pdf', 'uploads/pdf/parth_3_aadhaar_card_20260909_150240.pdf', '2026-09-09 09:32:40'),
(19, 3, 'pan_card', '25MAHADBT.pdf', 'parth_3_pan_card_20260909_150240.pdf', 'uploads/pdf/parth_3_pan_card_20260909_150240.pdf', '2026-09-09 09:32:40'),
(21, 3, 'caste_certificate', 'cap3.pdf', 'parth_3_caste_certificate_20260909_151515.pdf', 'uploads/pdf/parth_3_caste_certificate_20260909_151515.pdf', '2026-09-09 09:45:15'),
(22, 5, 'student_photo', 'photo.jpg', 'krushna_5_student_photo_20260922_230235.jpg', 'uploads/img/krushna_5_student_photo_20260922_230235.jpg', '2026-09-22 17:32:35'),
(23, 5, 'ssc_marksheet', 'k 12th mksheet.pdf', 'krushna_5_ssc_marksheet_20260922_230235.pdf', 'uploads/pdf/krushna_5_ssc_marksheet_20260922_230235.pdf', '2026-09-22 17:32:35'),
(24, 5, 'hsc_diploma', 'k 12th mksheet.pdf', 'krushna_5_hsc_diploma_20260922_230235.pdf', 'uploads/pdf/krushna_5_hsc_diploma_20260922_230235.pdf', '2026-09-22 17:32:35'),
(25, 5, 'caste_certificate', 'caste c k.pdf', 'krushna_5_caste_certificate_20260922_230235.pdf', 'uploads/pdf/krushna_5_caste_certificate_20260922_230235.pdf', '2026-09-22 17:32:35'),
(26, 5, 'aadhaar_card', 'Krishna adhar.pdf', 'krushna_5_aadhaar_card_20260922_230235.pdf', 'uploads/pdf/krushna_5_aadhaar_card_20260922_230235.pdf', '2026-09-22 17:32:35'),
(27, 5, 'pan_card', 'Krishna Lc.pdf', 'krushna_5_pan_card_20260922_230235.pdf', 'uploads/pdf/krushna_5_pan_card_20260922_230235.pdf', '2026-09-22 17:32:35');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `academic_details`
--
ALTER TABLE `academic_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `student_id` (`student_id`),
  ADD UNIQUE KEY `uq_academic_student` (`student_id`);

--
-- Indexes for table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`admin_id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `personal_details`
--
ALTER TABLE `personal_details`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_personal_student` (`student_id`);

--
-- Indexes for table `students`
--
ALTER TABLE `students`
  ADD PRIMARY KEY (`student_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD UNIQUE KEY `email_2` (`email`),
  ADD UNIQUE KEY `mobile` (`mobile`),
  ADD UNIQUE KEY `uq_application_number` (`application_number`);

--
-- Indexes for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_student_document` (`student_id`,`document_type`),
  ADD UNIQUE KEY `uq_document_student_type` (`student_id`,`document_type`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `academic_details`
--
ALTER TABLE `academic_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `admins`
--
ALTER TABLE `admins`
  MODIFY `admin_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `personal_details`
--
ALTER TABLE `personal_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `students`
--
ALTER TABLE `students`
  MODIFY `student_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `student_documents`
--
ALTER TABLE `student_documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `academic_details`
--
ALTER TABLE `academic_details`
  ADD CONSTRAINT `fk_academic_student` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `personal_details`
--
ALTER TABLE `personal_details`
  ADD CONSTRAINT `personal_details_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;

--
-- Constraints for table `student_documents`
--
ALTER TABLE `student_documents`
  ADD CONSTRAINT `student_documents_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `students` (`student_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
