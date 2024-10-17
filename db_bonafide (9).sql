-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 17, 2024 at 10:08 AM
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
(29, 212, 29, 'uploads/Reviewer ITP111.pdf', 'WITHDRAWN', NULL, 'referral_applicants', '2024-10-17 14:44:45', 4, 'None', 'None', 'None', '2024-10-17 15:23:45'),
(30, 213, 29, 'uploads/Prelim AT - Natanauan, Lucky.docx', 'OFFERED', NULL, 'referral_applicants', '2024-10-17 15:30:10', 4, 'zzz', 'zzzz', 'zzzzz', NULL);

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
(71, 29, 385, 'YES'),
(72, 29, 386, 'B'),
(73, 30, 387, 'YES'),
(74, 30, 388, 'B');

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
(212, 'IT ADMIN', 'NEXPERIA', 'PULO', 10000.00, 40000.00, 'None', 10, 4, '2024-10-18', 'ACTIVE', NULL, '2024-10-17 14:42:41', 1),
(213, 'WEB DEVELOPER', 'INTEARCH', 'PULO', 10000.00, 40000.00, 'None', 10, 4, '2024-10-30', 'ACTIVE', NULL, '2024-10-17 15:29:17', 1),
(218, 'Software Developer', 'Tech Innovators', 'New York, NY', 60000.00, 90000.00, 'Develop and maintain software applications.', 5, 4, '2024-11-01', 'ACTIVE', NULL, '2024-10-15 00:00:00', 1),
(219, 'Marketing Specialist', 'Creative Solutions', 'Los Angeles, CA', 50000.00, 70000.00, 'Handle company marketing campaigns and strategy.', 3, 4, '2024-12-01', 'ACTIVE', NULL, '2024-10-10 00:00:00', 0),
(220, 'Data Analyst', 'Data Wizards', 'San Francisco, CA', 55000.00, 85000.00, 'Analyze data sets and provide insights.', 2, 4, '2024-11-15', 'ACTIVE', NULL, '2024-10-12 00:00:00', 1),
(221, 'Project Manager', 'BuildIT Corp.', 'Chicago, IL', 70000.00, 95000.00, 'Oversee project timelines and deliverables.', 1, 4, '2024-10-30', 'ACTIVE', NULL, '2024-10-09 00:00:00', 1),
(222, 'UI/UX Designer', 'Creative Minds', 'Seattle, WA', 60000.00, 85000.00, 'Design user interfaces and experiences for web and mobile apps.', 2, 4, '2024-12-15', 'ACTIVE', NULL, '2024-10-16 00:00:00', 1),
(223, 'Network Engineer', 'TechConnect', 'Austin, TX', 65000.00, 90000.00, 'Manage and maintain company network infrastructure.', 4, 4, '2024-11-30', 'ACTIVE', NULL, '2024-10-17 00:00:00', 1),
(224, 'Human Resources Manager', 'PeopleFirst', 'Denver, CO', 70000.00, 95000.00, 'Oversee recruitment and employee relations for the company.', 1, 4, '2024-12-10', 'ACTIVE', NULL, '2024-10-17 00:00:00', 0),
(225, 'Sales Executive', 'Global Sales Corp.', 'Miami, FL', 50000.00, 75000.00, 'Drive sales and business development activities in the region.', 3, 4, '2024-11-25', 'ACTIVE', NULL, '2024-10-14 00:00:00', 0),
(226, 'DevOps Engineer', 'Cloud Solutions', 'San Diego, CA', 75000.00, 110000.00, 'Manage CI/CD pipelines and cloud infrastructure.', 2, 4, '2024-11-20', 'ACTIVE', NULL, '2024-10-13 00:00:00', 1),
(227, 'Customer Support Specialist', 'Friendly Tech', 'Phoenix, AZ', 40000.00, 60000.00, 'Provide customer support and resolve technical issues.', 5, 4, '2024-11-10', 'ACTIVE', NULL, '2024-10-12 00:00:00', 1);

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
(29, 35, '123', '123', 123, '123', '123', 'SINGLE', '123', '123', 'CA82EF0E', 'PRIMARY', '123', '123', NULL),
(30, 36, '22', '22', 22, '22', '22', 'SINGLE', '22', '22', 'E4CF11E5', 'PRIMARY', '22', '22', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `questionnaire_template`
--

CREATE TABLE `questionnaire_template` (
  `question_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `question_text` text NOT NULL,
  `is_required` tinyint(1) DEFAULT 1,
  `question_type` enum('TEXT','MULTIPLE_CHOICE','YES_NO') DEFAULT 'TEXT',
  `is_dealbreaker` tinyint(1) DEFAULT 0,
  `choice_a` varchar(255) DEFAULT NULL,
  `choice_b` varchar(255) DEFAULT NULL,
  `choice_c` varchar(255) DEFAULT NULL,
  `choice_d` varchar(255) DEFAULT NULL,
  `correct_answer` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `questionnaire_template`
--

INSERT INTO `questionnaire_template` (`question_id`, `job_id`, `question_text`, `is_required`, `question_type`, `is_dealbreaker`, `choice_a`, `choice_b`, `choice_c`, `choice_d`, `correct_answer`) VALUES
(385, 212, 'are u flexible?', 1, 'YES_NO', 1, NULL, NULL, NULL, NULL, 'YES'),
(386, 212, 'q1', 1, 'MULTIPLE_CHOICE', 0, '1', '2', '3', '4', ''),
(387, 213, 'are u flexible?', 1, 'YES_NO', 1, NULL, NULL, NULL, NULL, 'YES'),
(388, 213, 'How r u', 1, 'MULTIPLE_CHOICE', 0, '1', '11', '111', '1111', ''),
(395, 218, 'What design tools are you proficient with?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(396, 218, 'Have you worked on mobile app design before?', 1, 'YES_NO', 0, NULL, NULL, NULL, NULL, 'YES'),
(397, 218, 'Which design principle is most important for user interfaces?', 1, 'MULTIPLE_CHOICE', 1, 'Consistency', 'Aesthetics', 'Accessibility', 'Responsiveness', 'C'),
(398, 219, 'What networking certifications do you hold?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(399, 219, 'Have you configured a router using BGP?', 1, 'YES_NO', 1, NULL, NULL, NULL, NULL, 'YES'),
(400, 219, 'Which network security protocol is best for VPNs?', 1, 'MULTIPLE_CHOICE', 0, 'IPSec', 'SSL', 'PPTP', 'IKEv2', 'A'),
(401, 220, 'What is your experience in handling recruitment?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(402, 220, 'Have you handled employee conflicts before?', 1, 'YES_NO', 1, NULL, NULL, NULL, NULL, 'YES'),
(403, 220, 'What is the most important skill for an HR Manager?', 1, 'MULTIPLE_CHOICE', 1, 'Communication', 'Empathy', 'Negotiation', 'Problem Solving', 'B'),
(404, 221, 'What is your sales quota achievement from the past year?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(405, 221, 'Have you worked with CRM systems before?', 1, 'YES_NO', 0, NULL, NULL, NULL, NULL, 'YES'),
(406, 221, 'Which sales strategy is the most effective?', 1, 'MULTIPLE_CHOICE', 1, 'Consultative Selling', 'Transactional Selling', 'Solution Selling', 'SPIN Selling', 'C'),
(407, 222, 'What CI/CD tools are you familiar with?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(408, 222, 'Have you deployed applications using Docker?', 1, 'YES_NO', 0, NULL, NULL, NULL, NULL, 'YES'),
(409, 222, 'Which cloud provider do you prefer for deploying applications?', 1, 'MULTIPLE_CHOICE', 1, 'AWS', 'Azure', 'Google Cloud', 'DigitalOcean', 'A'),
(410, 223, 'Do you have experience with helpdesk software?', 1, 'YES_NO', 0, NULL, NULL, NULL, NULL, 'YES'),
(411, 223, 'Which skill is most important for customer support?', 1, 'MULTIPLE_CHOICE', 1, 'Problem-Solving', 'Communication', 'Technical Knowledge', 'Patience', 'B'),
(412, 224, 'What marketing tools have you used?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(413, 224, 'Have you managed a digital marketing campaign before?', 1, 'YES_NO', 0, NULL, NULL, NULL, NULL, 'YES'),
(414, 224, 'Which marketing strategy do you find most effective?', 1, 'MULTIPLE_CHOICE', 1, 'Content Marketing', 'Social Media Marketing', 'Email Marketing', 'SEO', 'A'),
(415, 225, 'What data analysis tools are you familiar with?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(416, 225, 'Have you worked with large datasets before?', 1, 'YES_NO', 1, NULL, NULL, NULL, NULL, 'YES'),
(417, 225, 'Which visualization tool do you prefer for data analysis?', 1, 'MULTIPLE_CHOICE', 1, 'Tableau', 'PowerBI', 'Looker', 'Excel', 'A'),
(418, 226, 'Have you managed cross-functional teams before?', 1, 'YES_NO', 1, NULL, NULL, NULL, NULL, 'YES'),
(419, 226, 'What project management software have you used?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(420, 226, 'Which project management methodology do you prefer?', 1, 'MULTIPLE_CHOICE', 1, 'Agile', 'Waterfall', 'Scrum', 'Kanban', 'C'),
(421, 227, 'What programming languages are you proficient in?', 1, 'TEXT', 1, NULL, NULL, NULL, NULL, NULL),
(422, 227, 'Do you have experience with agile methodologies?', 1, 'YES_NO', 1, NULL, NULL, NULL, NULL, 'YES'),
(423, 227, 'Which version control tool do you prefer?', 1, 'MULTIPLE_CHOICE', 1, 'Git', 'SVN', 'Mercurial', 'Other', 'A');

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
(23, 35, 35, NULL, 0),
(24, 36, 36, NULL, 0),
(25, 36, 35, 'CA82EF0E', 1);

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
(20, 29, '2024-10-31 15:02:00', 'Online', 'dffdsh', '4324', 'a@g.c', 'None'),
(21, 30, '2024-10-30 15:30:00', 'Online', 'xfgsdff', '123242', 'a@g.c', 'asffsfs');

-- --------------------------------------------------------

--
-- Table structure for table `tbl_job_metrics`
--

CREATE TABLE `tbl_job_metrics` (
  `metric_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `time_to_fill` int(11) DEFAULT NULL,
  `total_applicants` int(11) DEFAULT 0,
  `interviewed_applicants` int(11) DEFAULT 0,
  `successful_placements` int(11) DEFAULT 0,
  `referral_applicants` int(11) DEFAULT 0,
  `social_media_applicants` int(11) DEFAULT 0,
  `career_site_applicants` int(11) DEFAULT 0,
  `withdrawn_applicants` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_job_metrics`
--

INSERT INTO `tbl_job_metrics` (`metric_id`, `job_id`, `time_to_fill`, `total_applicants`, `interviewed_applicants`, `successful_placements`, `referral_applicants`, `social_media_applicants`, `career_site_applicants`, `withdrawn_applicants`) VALUES
(20, 212, NULL, 1, 0, 0, 0, 0, 0, 1),
(21, 213, NULL, 1, 0, 0, 0, 0, 0, 0);

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

--
-- Dumping data for table `tbl_offer_details`
--

INSERT INTO `tbl_offer_details` (`offer_id`, `job_id`, `salary`, `start_date`, `benefits`, `remarks`) VALUES
(7, 212, 123123.00, '0000-00-00', '123123', '123123'),
(8, 213, 40000.00, '0000-00-00', 'None', 'None');

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
  `days_in_applied_stage` int(11) DEFAULT 0,
  `days_in_screened_stage` int(11) DEFAULT 0,
  `days_in_interviewed_stage` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tbl_pipeline_stage`
--

INSERT INTO `tbl_pipeline_stage` (`stage_id`, `application_id`, `applied_at`, `screened_at`, `interviewed_at`, `offered_at`, `hired_at`, `rejected_at`, `withdrawn_at`, `days_in_applied_stage`, `days_in_screened_stage`, `days_in_interviewed_stage`) VALUES
(31, 29, '2024-10-17 14:44:45', NULL, NULL, NULL, NULL, NULL, '2024-10-17 15:23:45', 0, 0, 0),
(32, 30, '2024-10-17 15:30:10', NULL, NULL, NULL, NULL, NULL, NULL, 0, 0, 0);

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
(4, 'a@g.c', '$2y$10$Q70tcXHZeo1jUlhcNPGG8uKupYuOHuJ5E2MLrjVaTyhb34csMffPa', 'RECRUITER', '2024-10-17 09:18:40', '2024-10-15 15:16:34', 'ACTIVE'),
(35, '1@g.c', '$2y$10$3e2vw1jRY9hWXwEF3M/1lORcWrIZ7nzh.pPemAqvbKtngHBdoEhci', 'APPLICANT', '2024-10-17 09:15:53', '2024-10-17 05:55:10', 'ACTIVE'),
(36, '2@g.c', '$2y$10$RfGZ6Dq29ecFw0VQQuYB4eUS2eF/psjSvov3nC5kyVIeiWtZoxDwO', 'APPLICANT', NULL, '2024-10-17 05:55:41', 'ACTIVE');

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
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `application_answers`
--
ALTER TABLE `application_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=75;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=228;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `questionnaire_template`
--
ALTER TABLE `questionnaire_template`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=424;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT for table `tbl_interview`
--
ALTER TABLE `tbl_interview`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tbl_job_metrics`
--
ALTER TABLE `tbl_job_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `tbl_offer_details`
--
ALTER TABLE `tbl_offer_details`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `tbl_pipeline_stage`
--
ALTER TABLE `tbl_pipeline_stage`
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT for table `tbl_user_activity`
--
ALTER TABLE `tbl_user_activity`
  MODIFY `activity_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

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
