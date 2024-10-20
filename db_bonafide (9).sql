-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 20, 2024 at 04:09 AM
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
  `time_applied` datetime NOT NULL,
  `recruiter_id` int(11) NOT NULL,
  `qualifications` text DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `work_experience` text DEFAULT NULL,
  `withdrawn_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `applications`
--

INSERT INTO `applications` (`application_id`, `job_id`, `profile_id`, `resume`, `application_status`, `rejection_reason`, `referral_source`, `time_applied`, `recruiter_id`, `qualifications`, `skills`, `work_experience`, `withdrawn_at`) VALUES
(47, 282, 39, 'C:xampphtdocsonafide-finalapplicant/uploads/45-39-20241020_040627-job_282.pdf', 'INTERVIEW', NULL, 'referral_applicants', '2024-10-20 10:06:27', 4, 'none', 'none', 'none', NULL);

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
(187, 47, 530, 'YES'),
(188, 47, 531, 'Not fine');

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
(282, 'IT ADMIN', 'INTEARCH', 'PULO', 10000.00, 60000.00, 'None', 10, 4, '2024-10-31', 'ACTIVE', NULL, '2024-10-20 10:06:06', 1);

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
(39, 45, '123', '123', 123, '123', '123', 'SINGLE', '123', '123', '8159732E', 'PRIMARY', '123', '123', NULL);

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
(530, 282, 'are u flexible?', 1, 'YES_NO', 1, 'YES'),
(531, 282, 'How r u', 1, 'TEXT', 0, NULL);

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
(36, 45, 45, NULL, 0);

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
(42, 47, '2024-11-08 10:06:00', 'Online', 'sdvdgteg', '09283772932', 'a@g.c', 'none');

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
  `referral_applicants` int(11) DEFAULT 0,
  `social_media_applicants` int(11) DEFAULT 0,
  `career_site_applicants` int(11) DEFAULT 0,
  `withdrawn_applicants` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_job_metrics`
--

INSERT INTO `tbl_job_metrics` (`metric_id`, `job_id`, `time_to_fill`, `total_applicants`, `screened_applicants`, `interviewed_applicants`, `offered_applicants`, `successful_placements`, `referral_applicants`, `social_media_applicants`, `career_site_applicants`, `withdrawn_applicants`) VALUES
(54, 282, NULL, 1, 0, 0, 0, 0, 0, 0, 0, 0);

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
  `hired_at` datetime DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `withdrawn_at` datetime DEFAULT NULL,
  `duration_applied_to_screened` int(11) DEFAULT 0,
  `duration_screened_to_interviewed` int(11) DEFAULT 0,
  `duration_interviewed_to_offered` int(11) DEFAULT 0,
  `duration_offered_to_hired` int(11) DEFAULT 0,
  `total_duration` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pipeline_stage`
--

INSERT INTO `tbl_pipeline_stage` (`stage_id`, `application_id`, `applied_at`, `screened_at`, `interviewed_at`, `offered_at`, `hired_at`, `rejected_at`, `withdrawn_at`, `duration_applied_to_screened`, `duration_screened_to_interviewed`, `duration_interviewed_to_offered`, `duration_offered_to_hired`, `total_duration`) VALUES
(49, 47, '2024-10-20 10:06:27', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0, 0, 0);

-- --------------------------------------------------------

--
-- Table structure for table `tbl_user_activity`
--

CREATE TABLE `tbl_user_activity` (
  `activity_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `last_online` datetime NOT NULL DEFAULT current_timestamp(),
  `activity_type` enum('Login','Job Search','Application Submitted','Job Created','Profile Updated') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('APPLICANT','RECRUITER') NOT NULL,
  `last_login` datetime DEFAULT NULL,
  `registration_date` datetime NOT NULL DEFAULT current_timestamp(),
  `status` enum('ACTIVE','INACTIVE','BANNED') DEFAULT 'ACTIVE'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`user_id`, `email`, `password`, `role`, `last_login`, `registration_date`, `status`) VALUES
(4, 'a@g.c', '$2y$10$Q70tcXHZeo1jUlhcNPGG8uKupYuOHuJ5E2MLrjVaTyhb34csMffPa', 'RECRUITER', '2024-10-20 02:38:32', '2024-10-15 15:16:34', 'ACTIVE'),
(45, '1@g.c', '$2y$10$Hhc/qDLR4t3Tpf5RsdpjkuZdXCSORpGdVVANBo8ZW8Sxh0d3hhx1G', 'APPLICANT', NULL, '2024-10-20 02:40:11', 'ACTIVE');

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
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `profiles`
--
ALTER TABLE `profiles`
  ADD PRIMARY KEY (`profile_id`),
  ADD KEY `user_id` (`user_id`);

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
-- Indexes for table `tbl_user_activity`
--
ALTER TABLE `tbl_user_activity`
  ADD PRIMARY KEY (`activity_id`),
  ADD KEY `user_id` (`user_id`);

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
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=48;

--
-- AUTO_INCREMENT for table `application_answers`
--
ALTER TABLE `application_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=189;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=283;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=40;

--
-- AUTO_INCREMENT for table `questionnaire_template`
--
ALTER TABLE `questionnaire_template`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=532;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT for table `tbl_interview`
--
ALTER TABLE `tbl_interview`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;

--
-- AUTO_INCREMENT for table `tbl_job_metrics`
--
ALTER TABLE `tbl_job_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=55;

--
-- AUTO_INCREMENT for table `tbl_offer_details`
--
ALTER TABLE `tbl_offer_details`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- AUTO_INCREMENT for table `tbl_pipeline_stage`
--
ALTER TABLE `tbl_pipeline_stage`
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=50;

--
-- AUTO_INCREMENT for table `tbl_user_activity`
--
ALTER TABLE `tbl_user_activity`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=46;

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
-- Constraints for table `profiles`
--
ALTER TABLE `profiles`
  ADD CONSTRAINT `profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE;

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

--
-- Constraints for table `tbl_user_activity`
--
ALTER TABLE `tbl_user_activity`
  ADD CONSTRAINT `tbl_user_activity_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
