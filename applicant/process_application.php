<?php
session_start();
include '../db.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$job_id = $_SESSION['job_id'];
$user_id = $_SESSION['user_id'];

// Fetch profile_id for the logged-in user
$profile_sql = "SELECT profile_id FROM profiles WHERE user_id = '$user_id'";
$profile_result = $conn->query($profile_sql);

if ($profile_result->num_rows > 0) {
    $profile_row = $profile_result->fetch_assoc();
    $profile_id = $profile_row['profile_id'];

    // Handle file upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/';
        $resume_name = basename($_FILES['resume']['name']);
        $resume_path = $upload_dir . $resume_name;

        // Ensure the uploads directory exists
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);  // create directory if it doesn't exist
        }

        // Move the uploaded file
        if (move_uploaded_file($_FILES['resume']['tmp_name'], $resume_path)) {
            $_SESSION['resume_file'] = $resume_path;
        } else {
            echo "Error uploading the resume.";
            exit();
        }
    } else {
        echo "Error: No resume uploaded or file upload error.";
        exit();
    }

    // Start the transaction
    $conn->begin_transaction();

    try {
        // Check for withdrawn applications
        $withdrawn_sql = "SELECT application_id FROM applications WHERE job_id = '$job_id' AND profile_id = '$profile_id' AND application_status = 'WITHDRAWN'";
        $withdrawn_result = $conn->query($withdrawn_sql);

        if ($withdrawn_result->num_rows > 0) {
            // Re-apply by updating the withdrawn application
            $withdrawn_row = $withdrawn_result->fetch_assoc();
            $application_id = $withdrawn_row['application_id'];

            $update_application_sql = "UPDATE applications 
                                       SET application_status = 'SCREENING', time_applied = NOW(), resume = '{$_SESSION['resume_file']}' 
                                       WHERE application_id = '$application_id'";
            $conn->query($update_application_sql);

            // Update the pipeline stage
            $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                    SET applied_at = NOW(), withdrawn_at = NULL 
                                    WHERE application_id = '$application_id'";
            $conn->query($update_pipeline_sql);
        } else {
            // Insert a new application
            $insert_application_sql = "INSERT INTO applications (job_id, profile_id, resume, application_status, time_applied) 
                                       VALUES ('$job_id', '$profile_id', '{$_SESSION['resume_file']}', 'SCREENING', NOW())";
            $conn->query($insert_application_sql);
            $application_id = $conn->insert_id;

            // Insert into the pipeline
            $insert_pipeline_sql = "INSERT INTO tbl_pipeline_stage (application_id, applied_at) VALUES ('$application_id', NOW())";
            $conn->query($insert_pipeline_sql);
        }

        // Insert questionnaire answers if provided
        if (isset($_POST['answers'])) {
            foreach ($_POST['answers'] as $question_id => $answer) {
                $answer = $conn->real_escape_string($answer);  // escape special characters in answer
                $insert_answer_sql = "INSERT INTO application_answers (application_id, question_id, answer_text) 
                                      VALUES ('$application_id', '$question_id', '$answer')";
                $conn->query($insert_answer_sql);
            }
        }

        // Commit the transaction
        $conn->commit();

        // Clear session variables and redirect
        unset($_SESSION['job_id'], $_SESSION['resume_file']);
        header("Location: job_postings.php?success=1");
        exit();

    } catch (Exception $e) {
        // Rollback the transaction in case of error
        $conn->rollback();
        echo "Error processing the application: " . $e->getMessage();
    }

} else {
    echo "Error: Profile not found.";
}
?>