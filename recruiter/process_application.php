<?php
include '../db.php'; // Database connection

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

// Check if application_id and decision are provided
if (!isset($_POST['application_id']) || !isset($_POST['decision'])) {
    echo "Error: Application ID or decision not provided.";
    exit();
}

$application_id = (int)$_POST['application_id']; // Sanitize application_id
$decision = $_POST['decision'];

// Define the base URL for notifications
$base_url = "http://localhost/bonafide-final/applicant/application.php?application_id=";

// Start a transaction to ensure atomicity
$conn->begin_transaction();

try {
    // Fetch the job_id associated with this application
    $fetch_application_sql = "SELECT job_id FROM applications WHERE application_id = ?";
    $fetch_application_stmt = $conn->prepare($fetch_application_sql);
    $fetch_application_stmt->bind_param('i', $application_id);
    $fetch_application_stmt->execute();
    $application_result = $fetch_application_stmt->get_result();

    if ($application_result->num_rows > 0) {
        $application_row = $application_result->fetch_assoc();
        $job_id = $application_row['job_id'];
    } else {
        throw new Exception("Error: Application not found for application_id $application_id");
    }

    // Fetch the applicant's user_id from their profile
    $applicant_id_query = "SELECT user_id FROM profiles WHERE profile_id = 
                           (SELECT profile_id FROM applications WHERE application_id = ?)";
    $applicant_id_stmt = $conn->prepare($applicant_id_query);
    $applicant_id_stmt->bind_param('i', $application_id);
    $applicant_id_stmt->execute();
    $applicant_result = $applicant_id_stmt->get_result();
    $applicant = $applicant_result->fetch_assoc();
    $applicant_id = $applicant['user_id'];

    // Process decision-specific updates
    if ($decision === 'interview') {
        // Gather interview details from the form
        $interview_time = $_POST['interview_time'];
        $interview_type = $_POST['interview_type'];
        $meeting_link = $_POST['meeting_link'];
        $recruiter_phone = $_POST['recruiter_phone'];
        $recruiter_email = $_POST['recruiter_email'];
        $remarks = $_POST['remarks'];

        // Update application status to INTERVIEW
        $update_application_sql = "UPDATE applications SET application_status = 'INTERVIEW' WHERE application_id = ?";
        $update_application_stmt = $conn->prepare($update_application_sql);
        $update_application_stmt->bind_param('i', $application_id);
        $update_application_stmt->execute();

        // Update pipeline stage with screened_at and interviewed_at
        $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                SET screened_at = IF(screened_at IS NULL, NOW(), screened_at),
                                    interviewed_at = ?, 
                                    duration_applied_to_screened = TIMESTAMPDIFF(DAY, applied_at, screened_at), 
                                    duration_screened_to_interviewed = TIMESTAMPDIFF(DAY, screened_at, ?)
                                WHERE application_id = ?";
        $update_pipeline_stmt = $conn->prepare($update_pipeline_sql);
        $update_pipeline_stmt->bind_param('ssi', $interview_time, $interview_time, $application_id);
        $update_pipeline_stmt->execute();

        // Insert interview details in tbl_interview
        $insert_interview_sql = "INSERT INTO tbl_interview (application_id, interview_date, interview_type, meet_link, phone, recruiter_email, remarks)
                                 VALUES (?, ?, ?, ?, ?, ?, ?)";
        $insert_interview_stmt = $conn->prepare($insert_interview_sql);
        $insert_interview_stmt->bind_param('issssss', $application_id, $interview_time, $interview_type, $meeting_link, $recruiter_phone, $recruiter_email, $remarks);
        $insert_interview_stmt->execute();

        // Notify the applicant of the interview
        $subject = "Application Update: Interview Scheduled";
        $message = "An interview has been scheduled for your application. Interview Time: $interview_time. Please review the details.";
        $link = $base_url . $application_id;

        $insert_notif_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                             VALUES (?, 'Interview Scheduled', ?, ?, 0)";
        $insert_notif_stmt = $conn->prepare($insert_notif_sql);
        $insert_notif_stmt->bind_param('iss', $applicant_id, $subject, $link);
        $insert_notif_stmt->execute();
    } elseif ($decision === 'offer') {
        // Gather offer details from the form
        $salary = $_POST['salary'] ?? null;
        $start_date = $_POST['start_date'] ?? null;
        $benefits = $_POST['benefits'] ?? null;
        $offer_remarks = $_POST['offer_remarks'] ?? null;

        if (!$salary || !$start_date) {
            throw new Exception("Error: Salary and start date are required to make an offer.");
        }

        // Update application status to OFFERED
        $update_application_sql = "UPDATE applications SET application_status = 'OFFERED' WHERE application_id = ?";
        $update_application_stmt = $conn->prepare($update_application_sql);
        $update_application_stmt->bind_param('i', $application_id);
        $update_application_stmt->execute();

        // Update pipeline stage with offered_at
        $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                SET offered_at = NOW(),
                                    duration_interviewed_to_offered = TIMESTAMPDIFF(DAY, interviewed_at, NOW())
                                WHERE application_id = ?";
        $update_pipeline_stmt = $conn->prepare($update_pipeline_sql);
        $update_pipeline_stmt->bind_param('i', $application_id);
        $update_pipeline_stmt->execute();

        // Insert offer details into tbl_offer_details
        $insert_offer_sql = "INSERT INTO tbl_offer_details (job_id, salary, start_date, benefits, remarks)
                             VALUES (?, ?, ?, ?, ?)";
        $insert_offer_stmt = $conn->prepare($insert_offer_sql);
        $insert_offer_stmt->bind_param('idsss', $job_id, $salary, $start_date, $benefits, $offer_remarks);
        $insert_offer_stmt->execute();

        // Notify the applicant of the offer
        $subject = "Application Update: Offer Made";
        $message = "An offer has been made for your application. Salary: $salary, Start Date: $start_date. Please review the details.";
        $link = $base_url . $application_id;

        $insert_notif_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                             VALUES (?, 'Offer Made', ?, ?, 0)";
        $insert_notif_stmt = $conn->prepare($insert_notif_sql);
        $insert_notif_stmt->bind_param('iss', $applicant_id, $subject, $link);
        $insert_notif_stmt->execute();
    } elseif ($decision === 'deployment') {
        $deployment_remarks = $_POST['deployment_remarks'];

        // Update application status to DEPLOYED
        $update_application_sql = "UPDATE applications SET application_status = 'DEPLOYED' WHERE application_id = ?";
        $update_application_stmt = $conn->prepare($update_application_sql);
        $update_application_stmt->bind_param('i', $application_id);
        $update_application_stmt->execute();

        // Update pipeline stage with deployed_at
        $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                SET deployed_at = NOW(),
                                    duration_offered_to_hired = TIMESTAMPDIFF(DAY, offered_at, NOW()),
                                    total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW())
                                WHERE application_id = ?";
        $update_pipeline_stmt = $conn->prepare($update_pipeline_sql);
        $update_pipeline_stmt->bind_param('i', $application_id);
        $update_pipeline_stmt->execute();

        // Insert deployment details into tbl_deployment_details
        $insert_deployment_sql = "INSERT INTO tbl_deployment_details (application_id, deployment_date, deployment_remarks)
                                  VALUES (?, NOW(), ?)";
        $insert_deployment_stmt = $conn->prepare($insert_deployment_sql);
        $insert_deployment_stmt->bind_param('is', $application_id, $deployment_remarks);
        $insert_deployment_stmt->execute();

        // Notify the applicant about deployment
        $subject = "Application Update: Deployed";
        $message = "Congratulations! You have been deployed. Deployment remarks: $deployment_remarks.";
        $link = $base_url . $application_id;

        $insert_notif_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                             VALUES (?, 'Deployed', ?, ?, 0)";
        $insert_notif_stmt = $conn->prepare($insert_notif_sql);
        $insert_notif_stmt->bind_param('iss', $applicant_id, $subject, $link);
        $insert_notif_stmt->execute();
    } elseif ($decision === 'reject') {
        $rejection_reason = $conn->real_escape_string($_POST['rejection_reason']);

        // Update application status to REJECTED and set the rejection reason
        $update_application_sql = "UPDATE applications 
                                   SET application_status = 'REJECTED', rejection_reason = ? 
                                   WHERE application_id = ?";
        $update_application_stmt = $conn->prepare($update_application_sql);
        $update_application_stmt->bind_param('si', $rejection_reason, $application_id);
        $update_application_stmt->execute();

        // Update tbl_pipeline_stage to set rejected_at and calculate total duration
        $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                SET rejected_at = NOW(), 
                                    total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW()) 
                                WHERE application_id = ?";
        $update_pipeline_stmt = $conn->prepare($update_pipeline_sql);
        $update_pipeline_stmt->bind_param('i', $application_id);
        $update_pipeline_stmt->execute();

        // Notify applicant about rejection
        $subject = "Application Update: Rejected";
        $message = "We regret to inform you that your application has been rejected. Reason: $rejection_reason.";
        $link = $base_url . $application_id;

        $insert_notif_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                             VALUES (?, 'Application Rejected', ?, ?, 0)";
        $insert_notif_stmt = $conn->prepare($insert_notif_sql);
        $insert_notif_stmt->bind_param('iss', $applicant_id, $subject, $link);
        $insert_notif_stmt->execute();
    }

    // Commit the transaction
    $conn->commit();
    header('Location: application.php'); // Redirect to applications page
    exit();
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    echo "Error processing the application: " . $e->getMessage();
}
