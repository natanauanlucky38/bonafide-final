-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2024 at 06:50 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_bonafide`
--

-- --------------------------------------------------------

--
-- Table structure for table `applications`
--

CREATE TABLE `applications` (
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `resume` varchar(255) DEFAULT NULL,
  `application_status` enum('APPLIED','SCREENING','INTERVIEW','OFFERED','DEPLOYED','REJECTED','WITHDRAWN') NOT NULL,
  `rejection_reason` text DEFAULT NULL,
  `referral_source` enum('referral_applicants','social_media_applicants','career_site_applicants') DEFAULT 'career_site_applicants',
  `recruiter_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `profile_id`, `resume`, `application_status`, `rejection_reason`, `referral_source`, `recruiter_id`) VALUES
(1, 1, 1, '8-1-20241031_062856-job_1.pdf', 'INTERVIEW', NULL, 'referral_applicants', 7),
(2, 1, 6, '13-6-20241030_172535-job_1.pdf', 'INTERVIEW', NULL, 'referral_applicants', 7);

-- --------------------------------------------------------

--
-- Table structure for table `application_answers`
--

CREATE TABLE `application_answers` (
  `answer_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `question_id` int(11) NOT NULL,
  `answer_text` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `application_answers`
--

INSERT INTO `application_answers` (`answer_id`, `application_id`, `question_id`, `answer_text`) VALUES
(3, 2, 1, 'YES'),
(4, 2, 2, 'none'),
(5, 1, 1, 'YES'),
(6, 1, 2, 'no');

-- --------------------------------------------------------

--
-- Table structure for table `job_postings`
--

CREATE TABLE `job_postings` (
  `job_id` int(11) NOT NULL,
  `job_title` varchar(100) NOT NULL,
  `company` varchar(255) NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `min_salary` decimal(10,2) DEFAULT NULL,
  `max_salary` decimal(10,2) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `openings` int(11) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `deadline` date NOT NULL,
  `status` enum('DRAFT','ACTIVE','ARCHIVED') DEFAULT 'DRAFT',
  `filled_date` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `has_questionnaire` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `job_postings`
--

INSERT INTO `job_postings` (`job_id`, `job_title`, `company`, `location`, `min_salary`, `max_salary`, `description`, `openings`, `created_by`, `deadline`, `status`, `filled_date`, `created_at`, `has_questionnaire`) VALUES
(1, 'IT ADMIN', 'NEXPERIA', 'PULO', 20000.00, 60000.00, 'none', 5, 7, '2024-11-02', 'ACTIVE', NULL, '2024-10-30 23:43:48', 1);

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `subject` text NOT NULL,
  `link` varchar(255) NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`notification_id`, `user_id`, `title`, `subject`, `link`, `is_read`, `created_at`) VALUES
(1, 7, 'New Application Submitted', 'A new application has been submitted for the job: IT ADMIN', 'view_application.php?application_id=1', 1, '2024-10-30 15:44:57'),
(2, 8, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=1', 1, '2024-10-30 15:46:27'),
(3, 8, 'Job Offer', 'You have received a job offer.', 'http://localhost/bonafide-final/applicant/application.php?application_id=1', 1, '2024-10-30 15:47:15'),
(4, 7, 'New Application Submitted', 'A new application has been submitted for the job: IT ADMIN', 'view_application.php?application_id=2', 1, '2024-10-30 16:25:35'),
(5, 13, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=2', 1, '2024-10-30 16:38:17'),
(6, 8, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=1', 1, '2024-10-31 05:25:07'),
(7, 7, 'Application Withdrawn', 'Applicant has withdrawn their application.', 'view_application.php?application_id=1', 0, '2024-10-31 05:28:12'),
(8, 7, 'New Application Submitted', 'A new application has been submitted for the job: IT ADMIN', 'view_application.php?application_id=1', 0, '2024-10-31 05:28:56'),
(9, 8, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=1', 1, '2024-10-31 05:29:36');

-- --------------------------------------------------------

--
-- Table structure for table `profiles`
--

CREATE TABLE `profiles` (
  `profile_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `fname` varchar(100) NOT NULL,
  `lname` varchar(100) NOT NULL,
  `age` int(11) DEFAULT NULL,
  `phone` varchar(45) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `civil_status` varchar(45) DEFAULT NULL,
  `linkedin_link` varchar(255) DEFAULT NULL,
  `facebook_link` varchar(255) DEFAULT NULL,
  `referral_code` varchar(100) DEFAULT NULL,
  `education_level` enum('PRIMARY','SECONDARY','TERTIARY','POSTGRADUATE') DEFAULT NULL,
  `school_graduated` varchar(255) DEFAULT NULL,
  `year_graduated` varchar(4) DEFAULT NULL,
  `degree` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profiles`
--

INSERT INTO `profiles` (`profile_id`, `user_id`, `fname`, `lname`, `age`, `phone`, `address`, `civil_status`, `linkedin_link`, `facebook_link`, `referral_code`, `education_level`, `school_graduated`, `year_graduated`, `degree`) VALUES
(1, 8, '123', '123', 21, '123', '123', 'SINGLE', NULL, NULL, '67F80492', 'POSTGRADUATE', '123', '123', 'BSIT'),
(4, 11, 'Mark', 'Gomez', 20, '09296675422', 'San Isidro, Cabuyao, Laguna', 'SINGLE', NULL, NULL, 'F194C99A', 'POSTGRADUATE', 'PnC', '2025', 'BSIT'),
(6, 13, 'Eric', 'Baylosis', 21, '09296675422', 'Cabuyao, Laguna', 'Single', 'www.linkedin/eric', '', 'B08B45F7', 'POSTGRADUATE', 'PnC', '2025', 'BSIT');

-- --------------------------------------------------------

--
-- Table structure for table `profile_details`
--

CREATE TABLE `profile_details` (
  `detail_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `detail_value` text NOT NULL,
  `qualifications` varchar(255) NOT NULL,
  `skills` varchar(255) NOT NULL,
  `work_experience` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile_details`
--

INSERT INTO `profile_details` (`detail_id`, `profile_id`, `detail_value`, `qualifications`, `skills`, `work_experience`) VALUES
(1, 1, 'none', 'qualification', '', ''),
(2, 1, 'none', '', 'skill', ''),
(3, 1, 'none', '', '', 'work_experience'),
(4, 6, 'none', 'qualification', '', ''),
(5, 6, 'none', '', 'skill', ''),
(6, 6, 'none', '', '', 'work_experience');

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_template`
--

CREATE TABLE `questionnaire_template` (
  `question_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `question_type` enum('TEXT','YES_NO') DEFAULT 'TEXT',
  `is_dealbreaker` tinyint(1) DEFAULT 0,
  `correct_answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questionnaire_template`
--

INSERT INTO `questionnaire_template` (`question_id`, `job_id`, `question_text`, `is_required`, `question_type`, `is_dealbreaker`, `correct_answer`) VALUES
(1, 1, 'are u flexible?', 1, 'YES_NO', 1, 'YES'),
(2, 1, 'How r u', 1, 'TEXT', 0, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `referrals`
--

CREATE TABLE `referrals` (
  `referral_id` int(11) NOT NULL,
  `referred_user_id` int(11) NOT NULL,
  `referrer_user_id` int(11) NOT NULL,
  `referral_code` varchar(100) DEFAULT NULL,
  `points` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `referrals`
--

INSERT INTO `referrals` (`referral_id`, `referred_user_id`, `referrer_user_id`, `referral_code`, `points`) VALUES
(1, 11, 8, '67F80492', 2),
(3, 13, 8, '67F80492', 1);

-- --------------------------------------------------------

--
-- Table structure for table `requirements_tracking`
--

CREATE TABLE `requirements_tracking` (
  `requirement_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `requirement_name` varchar(255) NOT NULL,
  `is_submitted` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_deployment_details`
--

CREATE TABLE `tbl_deployment_details` (
  `deployment_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `deployment_date` datetime NOT NULL,
  `deployment_remarks` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_interview`
--

CREATE TABLE `tbl_interview` (
  `interview_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `interview_date` datetime NOT NULL,
  `interview_type` enum('Online','Face-to-Face') NOT NULL,
  `meet_link` varchar(255) NOT NULL,
  `phone` varchar(45) NOT NULL,
  `recruiter_email` varchar(255) NOT NULL,
  `remarks` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_interview`
--

INSERT INTO `tbl_interview` (`interview_id`, `application_id`, `interview_date`, `interview_type`, `meet_link`, `phone`, `recruiter_email`, `remarks`) VALUES
(2, 2, '2024-11-09 00:37:00', 'Online', 'https://www.facebook.com/', '09647475', 'r@g.c', 'none'),
(4, 1, '2024-11-02 13:29:00', 'Online', 'https://www.facebook.com/', '0965653', 'r@g.c', 'none');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_job_metrics`
--

CREATE TABLE `tbl_job_metrics` (
  `metric_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `time_to_fill` int(11) DEFAULT NULL,
  `total_applicants` int(11) DEFAULT 0,
  `screened_applicants` int(45) NOT NULL DEFAULT 0,
  `interviewed_applicants` int(11) DEFAULT 0,
  `offered_applicants` int(45) NOT NULL DEFAULT 0,
  `successful_placements` int(11) DEFAULT 0,
  `rejected_applicants` int(45) NOT NULL DEFAULT 0,
  `referral_applicants` int(11) DEFAULT 0,
  `social_media_applicants` int(11) DEFAULT 0,
  `career_site_applicants` int(11) DEFAULT 0,
  `withdrawn_applicants` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_job_metrics`
--

INSERT INTO `tbl_job_metrics` (`metric_id`, `job_id`, `time_to_fill`, `total_applicants`, `screened_applicants`, `interviewed_applicants`, `offered_applicants`, `successful_placements`, `rejected_applicants`, `referral_applicants`, `social_media_applicants`, `career_site_applicants`, `withdrawn_applicants`) VALUES
(1, 1, NULL, 2, 4, 4, 1, 0, 0, 2, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_offer_details`
--

CREATE TABLE `tbl_offer_details` (
  `offer_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `salary` decimal(10,2) NOT NULL,
  `start_date` date NOT NULL,
  `benefits` varchar(255) DEFAULT NULL,
  `remarks` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tbl_pipeline_stage`
--

CREATE TABLE `tbl_pipeline_stage` (
  `stage_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
  `applied_at` datetime NOT NULL DEFAULT current_timestamp(),
  `screened_at` datetime DEFAULT NULL,
  `interviewed_at` datetime DEFAULT NULL,
  `offered_at` datetime DEFAULT NULL,
  `deployed_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `withdrawn_at` datetime DEFAULT NULL,
  `duration_applied_to_screened` int(11) DEFAULT NULL,
  `duration_screened_to_interviewed` int(11) DEFAULT NULL,
  `duration_interviewed_to_offered` int(11) DEFAULT NULL,
  `duration_offered_to_hired` int(11) DEFAULT NULL,
  `total_duration` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pipeline_stage`
--

INSERT INTO `tbl_pipeline_stage` (`stage_id`, `application_id`, `applied_at`, `screened_at`, `interviewed_at`, `offered_at`, `deployed_at`, `rejected_at`, `withdrawn_at`, `duration_applied_to_screened`, `duration_screened_to_interviewed`, `duration_interviewed_to_offered`, `duration_offered_to_hired`, `total_duration`) VALUES
(1, 1, '2024-10-31 13:28:56', '2024-10-31 13:29:36', '2024-11-02 13:29:00', '2024-10-30 23:47:15', NULL, NULL, NULL, 0, 1, -4, NULL, 0),
(2, 2, '2024-10-31 00:25:35', '2024-10-31 00:38:17', '2024-11-09 00:37:00', NULL, NULL, NULL, NULL, 0, 8, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('APPLICANT','RECRUITER','USER_ADMIN') NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('ACTIVE','INACTIVE','BANNED') DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role`, `last_login`, `registration_date`, `status`) VALUES
(5, 'u@g.c', '123', 'USER_ADMIN', '2024-10-30 23:41:24', '2024-10-30 23:34:35', 'ACTIVE'),
(7, 'r@g.c', '$2y$10$x9bfW4AoYfaPmEB8v9oqce1b6LQRmw07wNd30YyYZfQyMecFsjnuS', 'RECRUITER', '2024-10-31 00:37:32', '2024-10-30 23:42:02', 'ACTIVE'),
(8, 'a@g.c', '$2y$10$4gXsZ2PPk2Hivg/iX/jhfu8PlaxNWNOR8ZTO9mouWDJVjq4DMe346', 'APPLICANT', NULL, '2024-10-30 23:44:19', 'ACTIVE'),
(11, 'a2@g.c', '$2y$10$pmd5xLwkHiLoHz9bLF70tOMFZJ0aQETKrcu807m.6x3Mg0Kg6.uXO', 'APPLICANT', NULL, '2024-10-31 00:05:12', 'ACTIVE'),
(13, 'a3@g.c', '$2y$10$/rmDnVk.hRYrK2zsXcgsI.GLNTpC7x3PQbtOWdvZZW7HWU47Y/G2e', 'APPLICANT', NULL, '2024-10-31 00:23:02', 'ACTIVE');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `applications`
--
ALTER TABLE `applications`
  ADD PRIMARY KEY (`application_id`),
  ADD KEY `job_id` (`job_id`),
  ADD KEY `profile_id` (`profile_id`),
  ADD KEY `recruiter_id` (`recruiter_id`);

--
-- Indexes for table `application_answers`
--
ALTER TABLE `application_answers`
  ADD PRIMARY KEY (`answer_id`),
  ADD KEY `application_id` (`application_id`),
  ADD KEY `question_id` (`question_id`);

--
-- Indexes for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD PRIMARY KEY (`job_id`),
  ADD KEY `job_postings_ibfk_1` (`created_by`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`notification_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `profile_details`
--
ALTER TABLE `profile_details`
  ADD PRIMARY KEY (`detail_id`),
  ADD KEY `profile_id` (`profile_id`);

--
-- Indexes for table `questionnaire_template`
--
ALTER TABLE `questionnaire_template`
  ADD PRIMARY KEY (`question_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `referrals`
--
ALTER TABLE `referrals`
  ADD PRIMARY KEY (`referral_id`),
  ADD KEY `referrals_ibfk_1` (`referred_user_id`),
  ADD KEY `referrals_ibfk_2` (`referrer_user_id`);

--
-- Indexes for table `requirements_tracking`
--
ALTER TABLE `requirements_tracking`
  ADD PRIMARY KEY (`requirement_id`),
  ADD KEY `fk_application_id` (`application_id`),
  ADD KEY `fk_job_id` (`job_id`);

--
-- Indexes for table `tbl_deployment_details`
--
ALTER TABLE `tbl_deployment_details`
  ADD PRIMARY KEY (`deployment_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `tbl_interview`
--
ALTER TABLE `tbl_interview`
  ADD PRIMARY KEY (`interview_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `tbl_job_metrics`
--
ALTER TABLE `tbl_job_metrics`
  ADD PRIMARY KEY (`metric_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `tbl_offer_details`
--
ALTER TABLE `tbl_offer_details`
  ADD PRIMARY KEY (`offer_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `tbl_pipeline_stage`
--
ALTER TABLE `tbl_pipeline_stage`
  ADD PRIMARY KEY (`stage_id`),
  ADD KEY `application_id` (`application_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `applications`
--
ALTER TABLE `applications`
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `application_answers`
--
ALTER TABLE `application_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `profile_details`
--
ALTER TABLE `profile_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `questionnaire_template`
--
ALTER TABLE `questionnaire_template`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `requirements_tracking`
--
ALTER TABLE `requirements_tracking`
  MODIFY `requirement_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_deployment_details`
--
ALTER TABLE `tbl_deployment_details`
  MODIFY `deployment_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_interview`
--
ALTER TABLE `tbl_interview`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tbl_job_metrics`
--
ALTER TABLE `tbl_job_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_offer_details`
--
ALTER TABLE `tbl_offer_details`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tbl_pipeline_stage`
--
ALTER TABLE `tbl_pipeline_stage`
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `applications`
--
ALTER TABLE `applications`
  ADD CONSTRAINT `applications_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_2` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `applications_ibfk_3` FOREIGN KEY (`recruiter_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `application_answers`
--
ALTER TABLE `application_answers`
  ADD CONSTRAINT `application_answers_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `application_answers_ibfk_2` FOREIGN KEY (`question_id`) REFERENCES `questionnaire_template` (`question_id`) ON DELETE CASCADE;

--
-- Constraints for table `job_postings`
--
ALTER TABLE `job_postings`
  ADD CONSTRAINT `job_postings_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `profile_details`
--
ALTER TABLE `profile_details`
  ADD CONSTRAINT `profile_details_ibfk_1` FOREIGN KEY (`profile_id`) REFERENCES `profiles` (`profile_id`) ON DELETE CASCADE;

--
-- Constraints for table `questionnaire_template`
--
ALTER TABLE `questionnaire_template`
  ADD CONSTRAINT `questionnaire_template_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `referrals`
--
ALTER TABLE `referrals`
  ADD CONSTRAINT `referrals_ibfk_1` FOREIGN KEY (`referred_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `referrals_ibfk_2` FOREIGN KEY (`referrer_user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

--
-- Constraints for table `requirements_tracking`
--
ALTER TABLE `requirements_tracking`
  ADD CONSTRAINT `fk_application_id` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_job_id` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_deployment_details`
--
ALTER TABLE `tbl_deployment_details`
  ADD CONSTRAINT `tbl_deployment_details_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_interview`
--
ALTER TABLE `tbl_interview`
  ADD CONSTRAINT `tbl_interview_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_job_metrics`
--
ALTER TABLE `tbl_job_metrics`
  ADD CONSTRAINT `tbl_job_metrics_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_offer_details`
--
ALTER TABLE `tbl_offer_details`
  ADD CONSTRAINT `tbl_offer_details_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `tbl_pipeline_stage`
--
ALTER TABLE `tbl_pipeline_stage`
  ADD CONSTRAINT `tbl_pipeline_stage_ibfk_1` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
