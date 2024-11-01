-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Nov 01, 2024 at 10:56 PM
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
(101, 1, 10, 'resume_path/101.pdf', 'APPLIED', NULL, 'career_site_applicants', 7),
(102, 2, 11, 'resume_path/102.pdf', 'WITHDRAWN', NULL, 'social_media_applicants', 7),
(103, 3, 12, 'resume_path/103.pdf', 'OFFERED', NULL, 'referral_applicants', 8),
(104, 4, 13, 'resume_path/104.pdf', 'INTERVIEW', NULL, 'career_site_applicants', 8),
(105, 5, 14, 'resume_path/105.pdf', 'SCREENING', NULL, 'social_media_applicants', 7),
(106, 6, 15, 'resume_path/106.pdf', 'REJECTED', 'Lack of experience', 'career_site_applicants', 8),
(107, 7, 16, 'resume_path/107.pdf', 'INTERVIEW', NULL, 'referral_applicants', 7),
(108, 8, 17, 'resume_path/108.pdf', 'REJECTED', 'Skills mismatch', 'career_site_applicants', 7),
(109, 9, 18, 'resume_path/109.pdf', 'OFFERED', NULL, 'social_media_applicants', 8),
(110, 10, 19, 'resume_path/110.pdf', 'DEPLOYED', NULL, 'referral_applicants', 7),
(111, 4, 1, 'C:\\xampp\\htdocs\\bonafide-final\\applicant\\uploads/8-1-20241101_214043-job_4.pdf', 'DEPLOYED', NULL, 'referral_applicants', 7);

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
(1, 'Software Engineer', 'Tech Innovators', 'New York', 70000.00, 100000.00, 'Develop and maintain software applications.', 3, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:47:41', '2024-10-15 10:30:00', 1),
(2, 'Data Scientist', 'Data Corp', 'San Francisco', 80000.00, 120000.00, 'Analyze data and develop predictive models.', 5, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:47:41', '2024-10-17 11:45:00', 1),
(3, 'UX Designer', 'Creative Studio', 'Los Angeles', 60000.00, 85000.00, 'Design user-friendly interfaces.', 2, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:54:21', '2024-10-18 09:20:00', 1),
(4, 'Project Manager', 'BuildWorks', 'Seattle', 90000.00, 110000.00, 'Manage software development projects.', 1, 7, '2024-12-15', 'ARCHIVED', '2024-11-02 05:18:21', '2024-10-22 08:50:00', 1),
(5, 'QA Engineer', 'Tech Innovators', 'New York', 50000.00, 70000.00, 'Ensure software quality.', 4, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:47:41', '2024-10-23 13:15:00', 1),
(6, 'Network Engineer', 'NetSecure', 'Chicago', 65000.00, 95000.00, 'Maintain network security.', 2, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:47:41', '2024-10-24 16:30:00', 1),
(7, 'Digital Marketer', 'MarketMax', 'Austin', 55000.00, 75000.00, 'Create and manage marketing campaigns.', 3, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:47:41', '2024-10-26 15:00:00', 1),
(8, 'Data Engineer', 'Data Corp', 'San Francisco', 75000.00, 105000.00, 'Build data infrastructure.', 5, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:47:41', '2024-10-27 17:25:00', 1),
(9, 'Sales Manager', 'BizGroup', 'Houston', 60000.00, 80000.00, 'Lead sales team to achieve targets.', 4, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:47:41', '2024-10-29 14:45:00', 1),
(10, 'IT Support Specialist', 'Tech Solutions', 'Boston', 45000.00, 60000.00, 'Provide technical support to clients.', 6, 7, '2024-11-01', 'ARCHIVED', '2024-11-01 22:47:41', '2024-10-30 12:00:00', 1);

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
(1, 7, 'New Application Submitted', 'A new application has been submitted for the job: Project Manager', 'view_application.php?application_id=111', 1, '2024-11-01 20:40:43'),
(2, 8, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 20:44:02'),
(3, 8, 'Job Offer', 'You have received a job offer.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 20:44:25'),
(4, 8, 'Deployment Completed', 'You have been successfully deployed.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 20:44:41'),
(5, 8, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 20:53:29'),
(6, 8, 'Job Offer', 'You have received a job offer.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 20:53:54'),
(7, 8, 'Deployment Completed', 'You have been successfully deployed.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 20:54:09'),
(8, 8, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:03:41'),
(9, 8, 'Job Offer', 'You have received a job offer.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:04:02'),
(10, 8, 'Deployment Completed', 'You have been successfully deployed.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:04:13'),
(11, 8, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:11:53'),
(12, 8, 'Job Offer', 'You have received a job offer.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:12:14'),
(13, 8, 'Deployment Completed', 'You have been successfully deployed.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:12:23'),
(14, 8, 'Interview Scheduled', 'An interview has been scheduled for your application.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:14:47'),
(15, 8, 'Job Offer', 'You have received a job offer.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:15:06'),
(16, 8, 'Deployment Completed', 'You have been successfully deployed.', 'http://localhost/bonafide-final/applicant/application.php?application_id=111', 0, '2024-11-01 21:18:21');

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
(1, 8, '123', '123', 21, '123', '123', 'SINGLE', NULL, NULL, '67F80492', 'POSTGRADUATE', '123', '123', 'BSIT');

-- --------------------------------------------------------

--
-- Table structure for table `profile_details`
--

CREATE TABLE `profile_details` (
  `detail_id` int(11) NOT NULL,
  `profile_id` int(11) NOT NULL,
  `detail_value` text NOT NULL,
  `qualifications` varchar(255) DEFAULT NULL,
  `skills` varchar(255) DEFAULT NULL,
  `work_experience` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profile_details`
--

INSERT INTO `profile_details` (`detail_id`, `profile_id`, `detail_value`, `qualifications`, `skills`, `work_experience`) VALUES
(1, 1, 'PHP', 'qualification', NULL, NULL),
(2, 1, 'HTML', NULL, 'skill', NULL),
(3, 2, 'CSS', NULL, 'skill', NULL),
(4, 2, 'JavaScript', 'qualification', NULL, NULL),
(5, 3, 'Python', NULL, 'skill', NULL),
(6, 3, 'Django', 'qualification', NULL, NULL),
(7, 4, 'SQL', NULL, 'skill', NULL),
(8, 4, 'Database Management', 'qualification', NULL, NULL),
(9, 5, 'React', NULL, 'skill', NULL),
(10, 5, 'Front-End Development', 'qualification', NULL, NULL),
(11, 6, 'Java', NULL, 'skill', NULL),
(12, 6, 'Backend Development', 'qualification', NULL, NULL),
(13, 7, 'Project Management', NULL, NULL, 'work_experience'),
(14, 8, 'Leadership', 'qualification', NULL, NULL),
(15, 9, 'Sales', NULL, NULL, 'work_experience'),
(16, 9, 'Marketing', NULL, 'skill', NULL),
(17, 10, 'Customer Service', 'qualification', NULL, NULL),
(18, 10, 'Technical Support', NULL, NULL, 'work_experience'),
(19, 1, 'Git', NULL, 'skill', NULL),
(20, 2, 'Agile Methodology', 'qualification', NULL, NULL);

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

-- --------------------------------------------------------

--
-- Table structure for table `requirement`
--

CREATE TABLE `requirement` (
  `req_id` int(11) NOT NULL,
  `job_id` int(11) NOT NULL,
  `requirement` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `requirement_tracking`
--

CREATE TABLE `requirement_tracking` (
  `tracking_id` int(11) NOT NULL,
  `req_id` int(11) NOT NULL,
  `application_id` int(11) NOT NULL,
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

--
-- Dumping data for table `tbl_deployment_details`
--

INSERT INTO `tbl_deployment_details` (`deployment_id`, `application_id`, `deployment_date`, `deployment_remarks`) VALUES
(1, 111, '2024-11-02 04:44:41', 'congrats'),
(2, 111, '2024-11-02 04:54:09', 'none'),
(3, 111, '2024-11-02 05:04:13', 'none'),
(4, 111, '2024-11-02 05:12:23', 'none'),
(6, 111, '2024-11-02 05:18:21', 'none');

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
(1, 111, '2024-11-03 04:43:00', 'Online', 'https://www.facebook.com/', '09548875234', 'r@g.c', 'none'),
(2, 111, '2024-11-03 04:52:00', 'Online', 'https://www.facebook.com/', '09283772932', 'r@g.c', 'none'),
(3, 111, '2024-11-03 05:03:00', 'Online', 'https://www.facebook.com/', '097544754', 'a@g.c', 'none'),
(4, 111, '2024-11-03 05:11:00', 'Online', 'https://www.facebook.com/', '0965634334', 'a@g.c', 'none'),
(5, 111, '2024-11-03 05:14:00', 'Online', 'https://www.facebook.com/', '0956654', 'a@g.c', 'none');

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
(1, 1, 17, 200, 180, 150, 100, 80, 50, 30, 70, 100, 20),
(2, 2, 15, 250, 220, 180, 120, 90, 60, 40, 80, 130, 30),
(3, 3, 14, 150, 130, 110, 90, 0, 40, 20, 50, 80, 10),
(4, 4, 10, 301, 275, 245, 155, 1, 80, 61, 100, 140, 40),
(5, 5, 9, 100, 90, 70, 50, 30, 20, 10, 30, 50, 5),
(6, 6, 8, 220, 200, 170, 140, 100, 70, 50, 90, 80, 20),
(7, 7, 6, 120, 110, 95, 70, 50, 30, 20, 40, 60, 10),
(8, 8, 5, 270, 250, 210, 160, 120, 90, 70, 110, 100, 25),
(9, 9, 3, 80, 70, 55, 45, 35, 20, 15, 25, 40, 5),
(10, 10, 2, 140, 130, 100, 80, 60, 40, 30, 50, 60, 15);

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
(1, 4, 60000.00, '2024-11-08', 'none', 'none'),
(2, 4, 60000.00, '2024-11-06', 'none', 'none'),
(3, 4, 60000.00, '2024-11-06', 'none', 'none'),
(4, 4, 60000.00, '2024-11-09', 'none', 'none'),
(5, 4, 60000.00, '2024-11-06', 'none', 'none');

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
(1, 101, '2024-10-01 09:00:00', '2024-10-05 10:00:00', '2024-10-10 11:00:00', '2024-10-15 12:00:00', '2024-10-20 13:00:00', NULL, NULL, 4, 5, 5, 5, 19),
(2, 102, '2024-10-02 09:30:00', '2024-10-06 10:30:00', '2024-10-11 11:30:00', '2024-10-16 12:30:00', NULL, '2024-10-21 13:30:00', NULL, 4, 5, 5, NULL, 14),
(3, 103, '2024-10-03 08:00:00', '2024-10-07 09:00:00', '2024-10-12 10:00:00', NULL, NULL, '2024-10-18 11:00:00', NULL, 4, 5, NULL, NULL, 9),
(4, 104, '2024-10-04 08:30:00', '2024-10-08 09:30:00', NULL, NULL, NULL, NULL, '2024-10-14 10:30:00', 4, NULL, NULL, NULL, 4),
(5, 105, '2024-10-05 07:00:00', '2024-10-09 08:00:00', '2024-10-14 09:00:00', '2024-10-19 10:00:00', '2024-10-24 11:00:00', NULL, NULL, 4, 5, 5, 5, 19),
(6, 106, '2024-10-06 06:30:00', '2024-10-10 07:30:00', NULL, NULL, NULL, '2024-10-16 08:30:00', NULL, 4, NULL, NULL, NULL, 4),
(7, 107, '2024-10-07 06:00:00', '2024-10-11 07:00:00', '2024-10-16 08:00:00', NULL, NULL, '2024-10-21 09:00:00', NULL, 4, 5, NULL, NULL, 9),
(8, 108, '2024-10-08 05:30:00', NULL, NULL, NULL, NULL, '2024-10-12 06:30:00', NULL, NULL, NULL, NULL, NULL, 4),
(9, 109, '2024-10-09 05:00:00', '2024-10-13 06:00:00', '2024-10-18 07:00:00', NULL, NULL, NULL, NULL, 4, 5, NULL, NULL, 9),
(10, 110, '2024-10-10 04:30:00', NULL, NULL, NULL, NULL, '2024-10-15 05:30:00', NULL, NULL, NULL, NULL, NULL, 5),
(11, 111, '2024-11-02 04:40:43', '2024-11-02 05:14:47', '2024-11-03 05:14:00', '2024-11-02 05:15:06', '2024-11-02 05:18:21', NULL, NULL, 0, 0, 0, 0, 0);

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
(7, 'r@g.c', '$2y$10$x9bfW4AoYfaPmEB8v9oqce1b6LQRmw07wNd30YyYZfQyMecFsjnuS', 'RECRUITER', '2024-11-01 22:03:03', '2024-10-30 23:42:02', 'ACTIVE'),
(8, 'a@g.c', '$2y$10$4gXsZ2PPk2Hivg/iX/jhfu8PlaxNWNOR8ZTO9mouWDJVjq4DMe346', 'APPLICANT', '2024-11-02 01:46:29', '2024-10-30 23:44:19', 'ACTIVE');

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
-- Indexes for table `requirement`
--
ALTER TABLE `requirement`
  ADD PRIMARY KEY (`req_id`),
  ADD KEY `job_id` (`job_id`);

--
-- Indexes for table `requirement_tracking`
--
ALTER TABLE `requirement_tracking`
  ADD PRIMARY KEY (`tracking_id`),
  ADD KEY `req_id` (`req_id`),
  ADD KEY `application_id` (`application_id`);

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
  MODIFY `application_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=112;

--
-- AUTO_INCREMENT for table `application_answers`
--
ALTER TABLE `application_answers`
  MODIFY `answer_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `job_postings`
--
ALTER TABLE `job_postings`
  MODIFY `job_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `notification_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `profiles`
--
ALTER TABLE `profiles`
  MODIFY `profile_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `profile_details`
--
ALTER TABLE `profile_details`
  MODIFY `detail_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `questionnaire_template`
--
ALTER TABLE `questionnaire_template`
  MODIFY `question_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `referrals`
--
ALTER TABLE `referrals`
  MODIFY `referral_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requirement`
--
ALTER TABLE `requirement`
  MODIFY `req_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `requirement_tracking`
--
ALTER TABLE `requirement_tracking`
  MODIFY `tracking_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tbl_deployment_details`
--
ALTER TABLE `tbl_deployment_details`
  MODIFY `deployment_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tbl_interview`
--
ALTER TABLE `tbl_interview`
  MODIFY `interview_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_job_metrics`
--
ALTER TABLE `tbl_job_metrics`
  MODIFY `metric_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `tbl_offer_details`
--
ALTER TABLE `tbl_offer_details`
  MODIFY `offer_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `tbl_pipeline_stage`
--
ALTER TABLE `tbl_pipeline_stage`
  MODIFY `stage_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

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
-- Constraints for table `requirement`
--
ALTER TABLE `requirement`
  ADD CONSTRAINT `requirement_ibfk_1` FOREIGN KEY (`job_id`) REFERENCES `job_postings` (`job_id`) ON DELETE CASCADE;

--
-- Constraints for table `requirement_tracking`
--
ALTER TABLE `requirement_tracking`
  ADD CONSTRAINT `requirement_tracking_ibfk_1` FOREIGN KEY (`req_id`) REFERENCES `requirement` (`req_id`) ON DELETE CASCADE,
  ADD CONSTRAINT `requirement_tracking_ibfk_2` FOREIGN KEY (`application_id`) REFERENCES `applications` (`application_id`) ON DELETE CASCADE;

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
