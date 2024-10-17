<?php
include '../db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['application_id'], $_POST['decision'])) {
    $application_id = (int)$_POST['application_id'];
    $decision = $_POST['decision'];

    if ($decision === 'reject' && !empty($_POST['rejection_reason'])) {
        $rejection_reason = $conn->real_escape_string($_POST['rejection_reason']);

        // Update application status to REJECTED and add rejection reason
        $reject_sql = "UPDATE applications SET application_status = 'REJECTED', rejection_reason = '$rejection_reason' WHERE application_id = '$application_id'";
        if ($conn->query($reject_sql)) {
            // Update pipeline stage
            $pipeline_sql = "
                UPDATE tbl_pipeline_stage 
                SET stage = 'REJECTED', rejection_reason = '$rejection_reason', rejected_at = NOW(), updated_at = NOW() 
                WHERE application_id = '$application_id'
            ";
            $conn->query($pipeline_sql);

            // Update job metrics
            $job_id_sql = "SELECT job_id FROM applications WHERE application_id = '$application_id'";
            $job_id_result = $conn->query($job_id_sql);
            $job_row = $job_id_result->fetch_assoc();
            $job_id = $job_row['job_id'];
            $metrics_sql = "UPDATE tbl_job_metrics SET rejected_applicants = rejected_applicants + 1 WHERE job_id = '$job_id'";
            $conn->query($metrics_sql);
            
            echo "Application rejected successfully.";
        } else {
            echo "Error rejecting application: " . $conn->error;
        }
    } elseif ($decision === 'interview') {
        // Capture interview details
        $interview_time = $conn->real_escape_string($_POST['interview_time']);
        $interview_type = $conn->real_escape_string($_POST['interview_type']);
        $meeting_link = $conn->real_escape_string($_POST['meeting_link']);
        $recruiter_phone = $conn->real_escape_string($_POST['recruiter_phone']);
        $recruiter_email = $conn->real_escape_string($_POST['recruiter_email']);
        $remarks = $conn->real_escape_string($_POST['remarks']);

        // Update application status to INTERVIEW
        $interview_sql = "UPDATE applications SET application_status = 'INTERVIEW' WHERE application_id = '$application_id'";
        if ($conn->query($interview_sql)) {
            // Insert into tbl_interview
            $interview_insert_sql = "
                INSERT INTO tbl_interview (application_id, interview_date, interview_type, meet_link, phone, recruiter_email, remarks) 
                VALUES ('$application_id', '$interview_time', '$interview_type', '$meeting_link', '$recruiter_phone', '$recruiter_email', '$remarks')
            ";
            $conn->query($interview_insert_sql);

            // Update pipeline stage and set the screened_at date
            $pipeline_sql = "
                UPDATE tbl_pipeline_stage 
                SET screened_at = NOW()
                WHERE application_id = '$application_id'
            ";

            // Ensure the pipeline update query runs correctly
            if ($conn->query($pipeline_sql)) {
                // Also update the stage in applications table
                $update_stage_sql = "UPDATE applications SET stage = 'INTERVIEW' WHERE application_id = '$application_id'";
                $conn->query($update_stage_sql);
                
                echo "Interview scheduled successfully.";
            } else {
                echo "Error updating pipeline stage: " . $conn->error; // This will help debug if it fails again
            }
        } else {
            echo "Error scheduling interview: " . $conn->error;
        }
    }
}

$conn->close();
?>
