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
                     SET application_status = 'WITHDRAWN', withdrawn_at = NOW() 
                     WHERE application_id = ? 
                     AND profile_id = (SELECT profile_id FROM profiles WHERE user_id = ?)";
    $stmt = $conn->prepare($withdraw_sql);
    $stmt->bind_param('ii', $application_id, $user_id);
    $stmt->execute();

    // Step 2: Delete answers related to the application from `application_answers`
    $delete_answers_sql = "DELETE FROM application_answers WHERE application_id = ?";
    $delete_stmt = $conn->prepare($delete_answers_sql);
    $delete_stmt->bind_param('i', $application_id);
    $delete_stmt->execute();

    // Step 3: Update `tbl_pipeline_stage` to set `withdrawn_at`
    $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                            SET withdrawn_at = NOW() 
                            WHERE application_id = ?";
    $pipeline_stmt = $conn->prepare($update_pipeline_sql);
    $pipeline_stmt->bind_param('i', $application_id);
    $pipeline_stmt->execute();

    // Step 4: Update `tbl_job_metrics` to increase `withdrawn_applicants` by 1
    $job_id_sql = "SELECT job_id FROM applications WHERE application_id = ?";
    $job_id_stmt = $conn->prepare($job_id_sql);
    $job_id_stmt->bind_param('i', $application_id);
    $job_id_stmt->execute();
    $job_id_result = $job_id_stmt->get_result();
    $job_id_row = $job_id_result->fetch_assoc();
    $job_id = $job_id_row['job_id'];

    $update_metrics_sql = "UPDATE tbl_job_metrics 
                           SET withdrawn_applicants = withdrawn_applicants + 1 
                           WHERE job_id = ?";
    $metrics_stmt = $conn->prepare($update_metrics_sql);
    $metrics_stmt->bind_param('i', $job_id);
    $metrics_stmt->execute();

    // Commit the transaction
    $conn->commit();
    echo "Application successfully withdrawn!";

    // Redirect to the job postings or applications page
    header('Location: view_job.php');
    exit();
} catch (Exception $e) {
    // Rollback transaction if something fails
    $conn->rollback();
    echo "Error withdrawing application: " . $e->getMessage();
}

$conn->close();
