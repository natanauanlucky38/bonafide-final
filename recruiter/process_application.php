<?php
// Include database connection
include '../db.php';  // Adjust this path based on your directory structure

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = $_POST['application_id'];
    $decision = $_POST['decision'];

    // Fetch the job_id and timestamps from the `applications` table
    $job_id_sql = "SELECT job_id FROM applications WHERE application_id = ?";
    $job_id_stmt = $conn->prepare($job_id_sql);

    if (!$job_id_stmt) {
        die("Error preparing job_id query: " . $conn->error);
    }

    $job_id_stmt->bind_param("i", $application_id);
    $job_id_stmt->execute();
    $job_id_result = $job_id_stmt->get_result();
    $job_id_row = $job_id_result->fetch_assoc();
    $job_id = $job_id_row['job_id'];

    // Initialize variables to capture form input
    $interview_time = isset($_POST['interview_time']) ? $_POST['interview_time'] : null;
    $interview_type = isset($_POST['interview_type']) ? $_POST['interview_type'] : null;
    $meeting_link = isset($_POST['meeting_link']) ? $_POST['meeting_link'] : null;
    $recruiter_phone = isset($_POST['recruiter_phone']) ? $_POST['recruiter_phone'] : null;
    $recruiter_email = isset($_POST['recruiter_email']) ? $_POST['recruiter_email'] : null;
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : null;

    $salary = isset($_POST['salary']) ? $_POST['salary'] : null;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;  // Used as hired_at
    $benefits = isset($_POST['benefits']) ? $_POST['benefits'] : null;
    $offer_remarks = isset($_POST['offer_remarks']) ? $_POST['offer_remarks'] : null;

    $deployment_remarks = isset($_POST['deployment_remarks']) ? $_POST['deployment_remarks'] : null;

    // Handle "Proceed to Interview" action
    if ($decision === 'interview') {
        // Insert interview details into tbl_interview
        $interview_sql = "INSERT INTO tbl_interview (application_id, interview_date, interview_type, meet_link, phone, recruiter_email, remarks) 
                          VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($interview_sql);

        if (!$stmt) {
            die("Error preparing interview query: " . $conn->error);
        }

        $stmt->bind_param("issssss", $application_id, $interview_time, $interview_type, $meeting_link, $recruiter_phone, $recruiter_email, $remarks);
        if ($stmt->execute()) {
            // Update the application status to 'INTERVIEW'
            $update_application_sql = "UPDATE applications SET application_status = 'INTERVIEW' WHERE application_id = ?";
            $stmt_update = $conn->prepare($update_application_sql);
            if (!$stmt_update) {
                die("Error preparing application update query: " . $conn->error);
            }
            $stmt_update->bind_param("i", $application_id);
            $stmt_update->execute();

            // Update `screened_at`, `interviewed_at`, and calculate durations in `tbl_pipeline_stage`
            $update_pipeline_sql = "
                UPDATE tbl_pipeline_stage 
                SET screened_at = NOW(), 
                    interviewed_at = ?, 
                    duration_applied_to_screened = DATEDIFF(NOW(), applied_at),
                    duration_screened_to_interviewed = IF(screened_at IS NOT NULL, DATEDIFF(?, screened_at), 0)
                WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($update_pipeline_sql);
            if (!$pipeline_stmt) {
                die("Error preparing pipeline update query: " . $conn->error);
            }
            $pipeline_stmt->bind_param("ssi", $interview_time, $interview_time, $application_id);
            $pipeline_stmt->execute();

            // Increment the `screened_applicants` and `interviewed_applicants` count in tbl_job_metrics
            $update_metrics_sql = "UPDATE tbl_job_metrics 
                                   SET screened_applicants = screened_applicants + 1, 
                                       interviewed_applicants = interviewed_applicants + 1 
                                   WHERE job_id = ?";
            $metrics_stmt = $conn->prepare($update_metrics_sql);
            if (!$metrics_stmt) {
                die("Error preparing job metrics update query: " . $conn->error);
            }
            $metrics_stmt->bind_param("i", $job_id);
            $metrics_stmt->execute();

            header('Location: application.php'); // Redirect back to application.php
            exit();
        } else {
            die("Error executing interview query: " . $stmt->error);
        }
    }

    // Handle "Proceed to Offer" action
    elseif ($decision === 'offer') {
        // Insert offer details into tbl_offer_details
        $offer_sql = "INSERT INTO tbl_offer_details (job_id, salary, start_date, benefits, remarks) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($offer_sql);

        if (!$stmt) {
            die("Error preparing offer query: " . $conn->error);
        }

        $stmt->bind_param("issss", $job_id, $salary, $start_date, $benefits, $offer_remarks);
        if ($stmt->execute()) {
            // Update the application status to 'OFFERED'
            $update_application_sql = "UPDATE applications SET application_status = 'OFFERED' WHERE application_id = ?";
            $stmt_update = $conn->prepare($update_application_sql);
            if (!$stmt_update) {
                die("Error preparing application update query: " . $conn->error);
            }
            $stmt_update->bind_param("i", $application_id);
            $stmt_update->execute();

            // Ensure interviewed_at is not NULL before calculating the duration
            $interviewed_sql = "SELECT interviewed_at FROM tbl_pipeline_stage WHERE application_id = ?";
            $stmt_check = $conn->prepare($interviewed_sql);
            if (!$stmt_check) {
                die("Error preparing interviewed_at query: " . $conn->error);
            }
            $stmt_check->bind_param("i", $application_id);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            $row_check = $result_check->fetch_assoc();
            $interviewed_at = $row_check['interviewed_at'];

            // Only calculate the duration if both `interviewed_at` and `offered_at` are set
            if (!empty($interviewed_at)) {
                // Update `offered_at` and calculate duration in `tbl_pipeline_stage`
                $update_pipeline_sql = "
                    UPDATE tbl_pipeline_stage 
                    SET offered_at = NOW(), 
                        duration_interviewed_to_offered = DATEDIFF(NOW(), interviewed_at)
                    WHERE application_id = ?";
                $pipeline_stmt = $conn->prepare($update_pipeline_sql);
                if (!$pipeline_stmt) {
                    die("Error preparing pipeline update query: " . $conn->error);
                }
                $pipeline_stmt->bind_param("i", $application_id);
                $pipeline_stmt->execute();
            } else {
                // If `interviewed_at` is not set, don't calculate the duration
                $update_pipeline_sql = "
                    UPDATE tbl_pipeline_stage 
                    SET offered_at = NOW()
                    WHERE application_id = ?";
                $pipeline_stmt = $conn->prepare($update_pipeline_sql);
                if (!$pipeline_stmt) {
                    die("Error preparing pipeline update query: " . $conn->error);
                }
                $pipeline_stmt->bind_param("i", $application_id);
                $pipeline_stmt->execute();
            }

            // Increment the `offered_applicants` count in tbl_job_metrics
            $update_metrics_sql = "UPDATE tbl_job_metrics 
                                   SET offered_applicants = offered_applicants + 1 
                                   WHERE job_id = ?";
            $metrics_stmt = $conn->prepare($update_metrics_sql);
            if (!$metrics_stmt) {
                die("Error preparing job metrics update query: " . $conn->error);
            }
            $metrics_stmt->bind_param("i", $job_id);
            $metrics_stmt->execute();

            header('Location: application.php'); // Redirect back to application.php
            exit();
        } else {
            die("Error executing offer query: " . $stmt->error);
        }
    }

    // Handle "Proceed to Deployment" action
    elseif ($decision === 'deployment') {
        // Update application status to 'DEPLOYED'
        $deployment_sql = "UPDATE applications SET application_status = 'DEPLOYED' WHERE application_id = ?";
        $stmt_update = $conn->prepare($deployment_sql);
        if (!$stmt_update) {
            die("Error preparing deployment query: " . $conn->error);
        }
        $stmt_update->bind_param("i", $application_id);
        if ($stmt_update->execute()) {
            // Update `hired_at` and calculate durations in `tbl_pipeline_stage`
            $update_pipeline_sql = "
                UPDATE tbl_pipeline_stage 
                SET hired_at = NOW(),
                    duration_offered_to_hired = IF(offered_at IS NOT NULL, DATEDIFF(?, offered_at), 0),
                    total_duration = DATEDIFF(?, applied_at)  -- calculate total duration based on start_date
                WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($update_pipeline_sql);
            if (!$pipeline_stmt) {
                die("Error preparing pipeline update query: " . $conn->error);
            }
            $pipeline_stmt->bind_param("ssi", $start_date, $start_date, $application_id);
            $pipeline_stmt->execute();

            // Increment `successful_placements` in tbl_job_metrics
            $update_metrics_sql = "UPDATE tbl_job_metrics SET successful_placements = successful_placements + 1 WHERE job_id = ?";
            $metrics_stmt = $conn->prepare($update_metrics_sql);
            if (!$metrics_stmt) {
                die("Error preparing job metrics update query: " . $conn->error);
            }
            $metrics_stmt->bind_param("i", $job_id);
            $metrics_stmt->execute();

            header('Location: application.php'); // Redirect back to application.php
            exit();
        } else {
            die("Error executing deployment query: " . $stmt_update->error);
        }
    }

    // Handle "Reject" action
    elseif ($decision === 'reject') {
        $rejection_reason = $_POST['rejection_reason'];
        $reject_sql = "UPDATE applications SET application_status = 'REJECTED', rejection_reason = ? WHERE application_id = ?";
        $stmt = $conn->prepare($reject_sql);

        if (!$stmt) {
            die("Error preparing reject query: " . $conn->error);
        }

        $stmt->bind_param("si", $rejection_reason, $application_id);
        if ($stmt->execute()) {
            header('Location: application.php'); // Redirect back to application.php
            exit();
        } else {
            die("Error executing reject query: " . $stmt->error);
        }
    }
}
