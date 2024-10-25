<?php
include '../db.php';  // Database connection

// Ensure the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');
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

    // Step 2: Delete answers related to the application from `application_answers`
    $delete_answers_sql = "DELETE FROM application_answers WHERE application_id = ?";
    $delete_stmt = $conn->prepare($delete_answers_sql);

    if (!$delete_stmt) {
        throw new Exception("Error preparing delete answers query: " . $conn->error);
    }

    $delete_stmt->bind_param('i', $application_id);
    $delete_stmt->execute();

    // Step 3: Update `tbl_pipeline_stage` to set `withdrawn_at` and calculate `total_duration`
    // Calculate the total duration in days between `applied_at` and `withdrawn_at`
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

    // Step 4: Update `tbl_job_metrics` to increase `withdrawn_applicants` by 1
    // First, retrieve the job ID associated with the application
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

    // Now update `withdrawn_applicants` in `tbl_job_metrics`
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

    // Step 5: Delete from `tbl_interview` if an interview is scheduled
    $delete_interview_sql = "DELETE FROM tbl_interview WHERE application_id = ?";
    $interview_stmt = $conn->prepare($delete_interview_sql);

    if (!$interview_stmt) {
        throw new Exception("Error preparing interview delete query: " . $conn->error);
    }

    $interview_stmt->bind_param('i', $application_id);
    $interview_stmt->execute();

    // Step 6: Delete the corresponding record from `tbl_offer_details`
    $delete_offer_sql = "DELETE FROM tbl_offer_details WHERE job_id = ?";
    $offer_stmt = $conn->prepare($delete_offer_sql);

    if (!$offer_stmt) {
        throw new Exception("Error preparing offer delete query: " . $conn->error);
    }

    $offer_stmt->bind_param('i', $job_id);
    $offer_stmt->execute();

    // Commit the transaction
    $conn->commit();
    echo "Application and offer successfully withdrawn!";

    // Redirect to the job postings or applications page
    header('Location: application.php');
    exit();
} catch (Exception $e) {
    // Rollback transaction if something fails
    $conn->rollback();
    echo "Error withdrawing application: " . $e->getMessage();
}

$conn->close();
