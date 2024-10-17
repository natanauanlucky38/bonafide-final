<?php
// Include database connection
include '../db.php';  // Adjust this path based on your directory structure

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = $_POST['application_id'];
    $decision = $_POST['decision'];

    // Fetch the job_id associated with the application_id
    $job_id_sql = "SELECT job_id FROM applications WHERE application_id = ?";
    $job_id_stmt = $conn->prepare($job_id_sql);
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
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $benefits = isset($_POST['benefits']) ? $_POST['benefits'] : null;
    $offer_remarks = isset($_POST['offer_remarks']) ? $_POST['offer_remarks'] : null;

    // Handle "Proceed to Interview" action
    if ($decision === 'interview') {
        $interview_sql = "INSERT INTO tbl_interview (application_id, interview_date, interview_type, meet_link, phone, recruiter_email, remarks) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($interview_sql);

        // Check if the statement was prepared successfully
        if ($stmt) {
            $stmt->bind_param("issssss", $application_id, $interview_time, $interview_type, $meeting_link, $recruiter_phone, $recruiter_email, $remarks);
            if ($stmt->execute()) {
                // Update the application status to 'INTERVIEW'
                $update_application_sql = "UPDATE applications SET application_status = 'INTERVIEW' WHERE application_id = ?";
                $stmt_update = $conn->prepare($update_application_sql);
                if ($stmt_update) {
                    $stmt_update->bind_param("i", $application_id);
                    $stmt_update->execute();
                }
                header('Location: application.php'); // Redirect back to application.php
                exit();
            } else {
                echo "Error executing interview query: " . $stmt->error;
            }
        } else {
            echo "Error preparing interview query: " . $conn->error;
        }

    // Handle "Proceed to Offer" action
    } elseif ($decision === 'offer') {
        // Use job_id instead of application_id
        $offer_sql = "INSERT INTO tbl_offer_details (job_id, salary, start_date, benefits, remarks) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($offer_sql);

        // Check if the statement was prepared successfully
        if ($stmt) {
            $stmt->bind_param("isdss", $job_id, $salary, $start_date, $benefits, $offer_remarks);
            if ($stmt->execute()) {
                // Update the application status to 'OFFERED'
                $update_application_sql = "UPDATE applications SET application_status = 'OFFERED' WHERE application_id = ?";
                $stmt_update = $conn->prepare($update_application_sql);
                if ($stmt_update) {
                    $stmt_update->bind_param("i", $application_id);
                    $stmt_update->execute();
                }
                header('Location: application.php'); // Redirect back to application.php
                exit();
            } else {
                echo "Error executing offer query: " . $stmt->error;
            }
        } else {
            echo "Error preparing offer query: " . $conn->error;
        }

    // Handle "Reject" action
    } elseif ($decision === 'reject') {
        $rejection_reason = $_POST['rejection_reason'];
        $reject_sql = "UPDATE applications SET application_status = 'REJECTED', rejection_reason = ? WHERE application_id = ?";
        $stmt = $conn->prepare($reject_sql);

        if ($stmt) {
            $stmt->bind_param("si", $rejection_reason, $application_id);
            if ($stmt->execute()) {
                header('Location: application.php'); // Redirect back to application.php
                exit();
            } else {
                echo "Error executing reject query: " . $stmt->error;
            }
        } else {
            echo "Error preparing reject query: " . $conn->error;
        }
    }
}
?>
