<?php
include '../db.php';
session_start();

// Check if the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');
    exit();
}

// Check if the application_id is provided via GET
if (!isset($_GET['application_id'])) {
    echo "Error: Application ID not provided.";
    exit();
}

$application_id = (int)$_GET['application_id']; // Sanitize application_id

// Start a transaction to ensure atomicity
$conn->begin_transaction();

try {
    // Update the application status to 'WITHDRAWN' and set the withdrawn timestamp
    $withdraw_sql = "UPDATE applications SET application_status = 'WITHDRAWN', withdrawn_at = NOW() WHERE application_id = '$application_id'";
    if ($conn->query($withdraw_sql) === TRUE) {
        // Update the tbl_pipeline_stage to record the withdrawal
        $withdraw_pipeline_sql = "UPDATE tbl_pipeline_stage SET withdrawn_at = NOW() WHERE application_id = '$application_id'";
        if (!$conn->query($withdraw_pipeline_sql)) {
            throw new Exception("Error updating pipeline stage: " . $conn->error);
        }

        // Fetch the job_id related to the application
        $job_id_sql = "SELECT job_id FROM applications WHERE application_id = '$application_id'";
        $job_id_result = $conn->query($job_id_sql);
        
        if ($job_id_result->num_rows > 0) {
            $job_row = $job_id_result->fetch_assoc();
            $job_id = $job_row['job_id'];

            // Update tbl_job_metrics
            // Decrement total_applicants and increment withdrawn_applicants
            $update_metrics_sql = "UPDATE tbl_job_metrics 
                                   SET withdrawn_applicants = withdrawn_applicants + 1 
                                   WHERE job_id = '$job_id'";
                                   
            if (!$conn->query($update_metrics_sql)) {
                throw new Exception("Error updating job metrics: " . $conn->error);
            }
        }

        // Commit the transaction
        $conn->commit();

        // Redirect to the correct location
        header('Location: view_job.php');
        exit();
    } else {
        throw new Exception("Error updating application status: " . $conn->error);
    }
} catch (Exception $e) {
    // Rollback the transaction on error
    $conn->rollback();
    echo "Error withdrawing application: " . $e->getMessage();
}

$conn->close();
?>
