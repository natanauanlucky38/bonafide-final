<?php
// Include database connection
include '../db.php';  // Adjust this path based on your directory structure

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = $_POST['application_id'];
    $decision = $_POST['decision'];

    // Fetch the job_id and relevant timestamps from the applications and tbl_pipeline_stage tables
    $job_id_sql = "SELECT a.job_id, p.applied_at, p.screened_at, p.interviewed_at, p.offered_at 
                   FROM tbl_pipeline_stage p
                   JOIN applications a ON p.application_id = a.application_id
                   WHERE p.application_id = ?";
    $job_id_stmt = $conn->prepare($job_id_sql);

    if (!$job_id_stmt) {
        die("Error preparing job_id query: " . $conn->error);
    }

    $job_id_stmt->bind_param("i", $application_id);
    $job_id_stmt->execute();
    $job_id_result = $job_id_stmt->get_result();
    $job_id_row = $job_id_result->fetch_assoc();
    $job_id = $job_id_row['job_id'];
    $applied_at = $job_id_row['applied_at'];
    $screened_at = $job_id_row['screened_at'];
    $interviewed_at = $job_id_row['interviewed_at'];
    $offered_at = $job_id_row['offered_at'];

    // Initialize variables to capture form input
    $interview_time = isset($_POST['interview_time']) ? $_POST['interview_time'] : null;
    $interview_type = isset($_POST['interview_type']) ? $_POST['interview_type'] : null;
    $meeting_link = isset($_POST['meeting_link']) ? $_POST['meeting_link'] : null;
    $recruiter_phone = isset($_POST['recruiter_phone']) ? $_POST['recruiter_phone'] : null;
    $recruiter_email = isset($_POST['recruiter_email']) ? $_POST['recruiter_email'] : null;
    $remarks = isset($_POST['remarks']) ? $_POST['remarks'] : null;

    $salary = isset($_POST['salary']) ? $_POST['salary'] : null;
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $benefits = isset($_POST['benefits']) ? $_POST['benefits'] : null;
    $offer_remarks = isset($_POST['offer_remarks']) ? $_POST['offer_remarks'] : null;

    $accept_offer_remarks = isset($_POST['accept_offer_remarks']) ? $_POST['accept_offer_remarks'] : null;

    // Handle "Proceed to Interview" action
    if ($decision === 'interview') {
        // Insert interview details
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

            // Update screened_at to current timestamp and calculate `duration_applied_to_screened`
            $update_pipeline_sql = "
                UPDATE tbl_pipeline_stage 
                SET screened_at = NOW(), 
                    duration_applied_to_screened = DATEDIFF(NOW(), applied_at) 
                WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($update_pipeline_sql);
            $pipeline_stmt->bind_param("i", $application_id);
            $pipeline_stmt->execute();

            // Calculate `duration_screened_to_interviewed`
            $duration_interviewed = "UPDATE tbl_pipeline_stage 
                                     SET duration_screened_to_interviewed = DATEDIFF(interviewed_at, screened_at) 
                                     WHERE application_id = ?";
            $duration_stmt_interviewed = $conn->prepare($duration_interviewed);
            $duration_stmt_interviewed->bind_param("i", $application_id);
            $duration_stmt_interviewed->execute();

            // Update screened_applicants and interviewed_applicants in tbl_job_metrics
            $update_metrics_sql = "UPDATE tbl_job_metrics 
                                   SET screened_applicants = screened_applicants + 1, interviewed_applicants = interviewed_applicants + 1 
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
    } elseif ($decision === 'offer') {
        // Handle "Proceed to Offer" action
        $offer_sql = "INSERT INTO tbl_offer_details (job_id, salary, start_date, benefits, remarks) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($offer_sql);

        if (!$stmt) {
            die("Error preparing offer query: " . $conn->error);
        }

        $stmt->bind_param("isdss", $job_id, $salary, $start_date, $benefits, $offer_remarks);
        if ($stmt->execute()) {
            // Update the application status to 'OFFERED' and `offered_at`
            $update_application_sql = "UPDATE applications SET application_status = 'OFFERED' WHERE application_id = ?";
            $stmt_update = $conn->prepare($update_application_sql);
            if (!$stmt_update) {
                die("Error preparing application update query: " . $conn->error);
            }
            $stmt_update->bind_param("i", $application_id);
            $stmt_update->execute();

            // Update `offered_at` in `tbl_pipeline_stage`
            $update_pipeline_sql = "UPDATE tbl_pipeline_stage SET offered_at = NOW() WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($update_pipeline_sql);
            $pipeline_stmt->bind_param("i", $application_id);
            $pipeline_stmt->execute();

            // Calculate `duration_interviewed_to_offered`
            $duration_offered = "UPDATE tbl_pipeline_stage 
                                 SET duration_interviewed_to_offered = DATEDIFF(offered_at, interviewed_at) 
                                 WHERE application_id = ?";
            $duration_stmt_offered = $conn->prepare($duration_offered);
            $duration_stmt_offered->bind_param("i", $application_id);
            $duration_stmt_offered->execute();

            // Update offered_applicants in tbl_job_metrics
            $update_metrics_sql = "UPDATE tbl_job_metrics SET offered_applicants = offered_applicants + 1 WHERE job_id = ?";
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
    } elseif ($decision === 'accept_offer') {
        // Handle "Accept Offer" action
        $accept_offer_sql = "UPDATE applications SET application_status = 'ACCEPTED', accept_offer_remarks = ? WHERE application_id = ?";
        $stmt_accept = $conn->prepare($accept_offer_sql);

        if (!$stmt_accept) {
            die("Error preparing accept offer query: " . $conn->error);
        }

        $stmt_accept->bind_param("si", $accept_offer_remarks, $application_id);
        $stmt_accept->execute();

        // After accepting the offer, you could perform other updates like setting accepted_at in tbl_pipeline_stage or metrics
        $update_pipeline_sql = "UPDATE tbl_pipeline_stage SET accepted_at = NOW() WHERE application_id = ?";
        $pipeline_stmt = $conn->prepare($update_pipeline_sql);
        $pipeline_stmt->bind_param("i", $application_id);
        $pipeline_stmt->execute();

        header('Location: application.php'); // Redirect back to application.php
        exit();
    } elseif ($decision === 'reject') {
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
