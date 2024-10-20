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

// Start a transaction to ensure atomicity
$conn->begin_transaction();

try {
    // Fetch the job_id associated with this application
    $fetch_application_sql = "SELECT job_id FROM applications WHERE application_id = '$application_id'";
    $application_result = $conn->query($fetch_application_sql);
    if ($application_result->num_rows > 0) {
        $application_row = $application_result->fetch_assoc();
        $job_id = $application_row['job_id'];
    } else {
        throw new Exception("Error: Application not found for application_id $application_id");
    }

    if ($decision === 'interview') {
        // Gather interview details from the form
        $interview_time = $_POST['interview_time'];
        $interview_type = $_POST['interview_type'];
        $meeting_link = $_POST['meeting_link'];
        $recruiter_phone = $_POST['recruiter_phone'];
        $recruiter_email = $_POST['recruiter_email'];
        $remarks = $_POST['remarks'];

        // Update the application status to INTERVIEW
        $update_application_sql = "UPDATE applications SET application_status = 'INTERVIEW' WHERE application_id = '$application_id'";
        $conn->query($update_application_sql);

        // Check if a record exists in tbl_pipeline_stage for this application_id
        $check_pipeline_sql = "SELECT * FROM tbl_pipeline_stage WHERE application_id = '$application_id'";
        $pipeline_result = $conn->query($check_pipeline_sql);

        if ($pipeline_result->num_rows > 0) {
            // Update the existing record with screened_at and interviewed_at, and calculate durations
            $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                     SET screened_at = IF(screened_at IS NULL, NOW(), screened_at),
                                         interviewed_at = '$interview_time', 
                                         duration_applied_to_screened = TIMESTAMPDIFF(DAY, applied_at, screened_at), 
                                         duration_screened_to_interviewed = TIMESTAMPDIFF(DAY, screened_at, '$interview_time')
                                     WHERE application_id = '$application_id'";
            $conn->query($update_pipeline_sql);
        } else {
            // Insert a new record in tbl_pipeline_stage
            $insert_pipeline_sql = "INSERT INTO tbl_pipeline_stage 
                                    (application_id, applied_at, screened_at, interviewed_at, duration_applied_to_screened, duration_screened_to_interviewed)
                                    VALUES ('$application_id', NOW(), NOW(), '$interview_time', 0, TIMESTAMPDIFF(DAY, NOW(), '$interview_time'))";
            $conn->query($insert_pipeline_sql);
        }

        // Insert interview details in tbl_interview
        $insert_interview_sql = "INSERT INTO tbl_interview (application_id, interview_date, interview_type, meet_link, phone, recruiter_email, remarks)
                                 VALUES ('$application_id', '$interview_time', '$interview_type', '$meeting_link', '$recruiter_phone', '$recruiter_email', '$remarks')";
        $conn->query($insert_interview_sql);

        // Update job metrics: screened_applicants and interviewed_applicants
        $update_job_metrics_sql = "UPDATE tbl_job_metrics 
                                   SET screened_applicants = screened_applicants + 1, 
                                       interviewed_applicants = interviewed_applicants + 1 
                                   WHERE job_id = '$job_id'";
        $conn->query($update_job_metrics_sql);
    } elseif ($decision === 'offer') {
        // Gather offer details from the form
        $salary = $_POST['salary'];
        $start_date = $_POST['start_date'];
        $benefits = $_POST['benefits'];
        $offer_remarks = $_POST['offer_remarks'];

        // Update the application status to OFFERED
        $update_application_sql = "UPDATE applications SET application_status = 'OFFERED' WHERE application_id = '$application_id'";
        $conn->query($update_application_sql);

        // Fetch the interviewed_at date from tbl_pipeline_stage
        $fetch_pipeline_sql = "SELECT interviewed_at FROM tbl_pipeline_stage WHERE application_id = '$application_id'";
        $pipeline_result = $conn->query($fetch_pipeline_sql);

        if ($pipeline_result->num_rows > 0) {
            $pipeline_row = $pipeline_result->fetch_assoc();
            $interviewed_at = $pipeline_row['interviewed_at'];

            // Update offered_at and calculate the duration between interviewed_at and offered_at
            $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                     SET offered_at = NOW(),
                                         duration_interviewed_to_offered = TIMESTAMPDIFF(DAY, interviewed_at, NOW())
                                     WHERE application_id = '$application_id'";
            $conn->query($update_pipeline_sql);
        } else {
            throw new Exception("Error: Interview data not found for application_id $application_id");
        }

        // Insert offer details into tbl_offer_details
        $insert_offer_sql = "INSERT INTO tbl_offer_details (application_id, salary, start_date, benefits, remarks)
                             VALUES ('$application_id', '$salary', '$start_date', '$benefits', '$offer_remarks')";
        $conn->query($insert_offer_sql);

        // Update job metrics: offered_applicants
        $update_job_metrics_sql = "UPDATE tbl_job_metrics 
                                   SET offered_applicants = offered_applicants + 1 
                                   WHERE job_id = '$job_id'";
        $conn->query($update_job_metrics_sql);
    } elseif ($decision === 'deployment') {
        // Proceed to deployment
        $deployment_remarks = $_POST['deployment_remarks'];

        // Update the application status to DEPLOYED
        $update_application_sql = "UPDATE applications SET application_status = 'DEPLOYED' WHERE application_id = '$application_id'";
        $conn->query($update_application_sql);

        // Fetch the offered_at date from tbl_pipeline_stage
        $fetch_pipeline_sql = "SELECT offered_at FROM tbl_pipeline_stage WHERE application_id = '$application_id'";
        $pipeline_result = $conn->query($fetch_pipeline_sql);

        if ($pipeline_result->num_rows > 0) {
            $pipeline_row = $pipeline_result->fetch_assoc();
            $offered_at = $pipeline_row['offered_at'];

            // Update deployed_at and calculate the duration between offered_at and deployed_at
            $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                     SET deployed_at = NOW(),
                                         duration_offered_to_hired = TIMESTAMPDIFF(DAY, offered_at, NOW())
                                     WHERE application_id = '$application_id'";
            $conn->query($update_pipeline_sql);
        } else {
            throw new Exception("Error: Offer data not found for application_id $application_id");
        }

        // Insert deployment remarks into tbl_deployment_details if needed
        $insert_deployment_sql = "INSERT INTO tbl_deployment_details (application_id, deployment_remarks)
                                  VALUES ('$application_id', '$deployment_remarks')";
        $conn->query($insert_deployment_sql);

        // Update job metrics: successful_applicants
        $update_job_metrics_sql = "UPDATE tbl_job_metrics 
                                   SET successful_placements = successful_placements + 1 
                                   WHERE job_id = '$job_id'";
        $conn->query($update_job_metrics_sql);
    } elseif ($decision === 'reject') {
        // Handle rejection logic...
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
