<?php
include '../db.php';  // Database connection
session_start();

// Ensure the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');
    exit();
}

if (!isset($_GET['application_id'])) {
    echo "Invalid request!";
    exit();
}

$application_id = intval($_GET['application_id']);
$user_id = $_SESSION['user_id']; // Get logged-in user's ID

// Start a transaction to ensure atomic updates
$conn->begin_transaction();

try {
    // Step 1: Withdraw the application by updating the `applications` table
    $withdraw_sql = "UPDATE applications 
                     SET application_status = 'WITHDRAWN' 
                     WHERE application_id = ? 
                     AND profile_id = (SELECT profile_id FROM profiles WHERE user_id = ?)";
    $stmt = $conn->prepare($withdraw_sql);

    // Check if the statement was prepared correctly
    if (!$stmt) {
        throw new Exception("Error preparing application withdraw query: " . $conn->error);
    }

    $stmt->bind_param('ii', $application_id, $user_id);
    $stmt->execute();

    // Check if the update was successful
    if ($stmt->affected_rows == 0) {
        throw new Exception("Failed to withdraw the application.");
    }

    // Step 2: Create a notification for the recruiter
    // Retrieve recruiter_id associated with the job
    $recruiter_sql = "SELECT u.user_id FROM applications a 
                      JOIN job_postings j ON a.job_id = j.job_id 
                      JOIN users u ON j.created_by = u.user_id 
                      WHERE a.application_id = ?";
    $recruiter_stmt = $conn->prepare($recruiter_sql);
    $recruiter_stmt->bind_param('i', $application_id);
    $recruiter_stmt->execute();
    $recruiter_result = $recruiter_stmt->get_result();

    if ($recruiter_result->num_rows == 0) {
        throw new Exception("Failed to retrieve recruiter information.");
    }

    $recruiter_row = $recruiter_result->fetch_assoc();
    $recruiter_id = $recruiter_row['user_id'];

    // Insert the notification
    $notification_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                         VALUES (?, 'Application Withdrawn', 'Applicant has withdrawn their application.', ?, 0)";
    $notification_stmt = $conn->prepare($notification_sql);
    $link = "view_application.php?application_id=" . $application_id; // Create a link to the application view
    $notification_stmt->bind_param('is', $recruiter_id, $link);
    $notification_stmt->execute();

    // Step 2.1: Reset the `is_submitted` field for all requirements in `requirement_tracking` related to this application
    $reset_requirements_sql = "UPDATE requirement_tracking SET is_submitted = 0 WHERE application_id = ?";
    $reset_requirements_stmt = $conn->prepare($reset_requirements_sql);
    if (!$reset_requirements_stmt) {
        throw new Exception("Error preparing requirements reset query: " . $conn->error);
    }
    $reset_requirements_stmt->bind_param('i', $application_id);
    $reset_requirements_stmt->execute();

    // Step 3: Delete answers related to the application from `application_answers`
    $delete_answers_sql = "DELETE FROM application_answers WHERE application_id = ?";
    $delete_stmt = $conn->prepare($delete_answers_sql);

    if (!$delete_stmt) {
        throw new Exception("Error preparing delete answers query: " . $conn->error);
    }

    $delete_stmt->bind_param('i', $application_id);
    $delete_stmt->execute();

    // Step 4: Update `tbl_pipeline_stage` to set `withdrawn_at` and calculate `total_duration`
    $update_pipeline_sql = "
        UPDATE tbl_pipeline_stage 
        SET withdrawn_at = NOW(), 
            total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW()) 
        WHERE application_id = ?";
    $pipeline_stmt = $conn->prepare($update_pipeline_sql);

    if (!$pipeline_stmt) {
        throw new Exception("Error preparing pipeline stage update query: " . $conn->error);
    }

    $pipeline_stmt->bind_param('i', $application_id);
    $pipeline_stmt->execute();

    // Step 5: Update `tbl_job_metrics` to increase `withdrawn_applicants` by 1
    $job_id_sql = "SELECT job_id FROM applications WHERE application_id = ?";
    $job_id_stmt = $conn->prepare($job_id_sql);

    if (!$job_id_stmt) {
        throw new Exception("Error preparing job_id query: " . $conn->error);
    }

    $job_id_stmt->bind_param('i', $application_id);
    $job_id_stmt->execute();
    $job_id_result = $job_id_stmt->get_result();
    $job_id_row = $job_id_result->fetch_assoc();
    $job_id = $job_id_row['job_id'];

    $update_metrics_sql = "UPDATE tbl_job_metrics 
                           SET withdrawn_applicants = withdrawn_applicants + 1 
                           WHERE job_id = ?";
    $metrics_stmt = $conn->prepare($update_metrics_sql);

    if (!$metrics_stmt) {
        throw new Exception("Error preparing job metrics update query: " . $conn->error);
    }

    $metrics_stmt->bind_param('i', $job_id);
    $metrics_stmt->execute();

    // Check if the `withdrawn_applicants` update was successful
    if ($metrics_stmt->affected_rows == 0) {
        throw new Exception("Failed to update withdrawn_applicants in tbl_job_metrics.");
    }

    // Step 6: Delete from `tbl_interview` if an interview is scheduled
    $delete_interview_sql = "DELETE FROM tbl_interview WHERE application_id = ?";
    $interview_stmt = $conn->prepare($delete_interview_sql);

    if (!$interview_stmt) {
        throw new Exception("Error preparing interview delete query: " . $conn->error);
    }

    $interview_stmt->bind_param('i', $application_id);
    $interview_stmt->execute();

    // Step 7: Delete the corresponding record from `tbl_offer_details`
    $delete_offer_sql = "DELETE FROM tbl_offer_details WHERE job_id = ?";
    $offer_stmt = $conn->prepare($delete_offer_sql);

    if (!$offer_stmt) {
        throw new Exception("Error preparing offer delete query: " . $conn->error);
    }

    $offer_stmt->bind_param('i', $job_id);
    $offer_stmt->execute();

    // Commit the transaction
    $conn->commit();
    echo "Application successfully withdrawn!";

    // Redirect to the applications page
    header('Location: application.php'); // Redirect to the application page
    exit();
} catch (Exception $e) {
    // Rollback transaction if something fails
    $conn->rollback();
    echo "Error withdrawing application: " . $e->getMessage();
}

$conn->close();
