<?php
include '../db.php';  // Database connection

// Check if the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Ensure the job_id is provided
if (!isset($_GET['job_id'])) {
    echo "Error: Job ID not found.";
    exit();
}

$job_id = (int)$_GET['job_id']; // Sanitize job_id
$_SESSION['job_id'] = $job_id; // Store job_id in session

// Fetch job details to display
$job_sql = "SELECT * FROM job_postings WHERE job_id = $job_id";
$job_result = $conn->query($job_sql);
if ($job_result->num_rows == 0) {
    echo "Job not found.";
    exit();
}
$job = $job_result->fetch_assoc();

// Fetch the user's profile_id
$profile_sql = "SELECT profile_id FROM profiles WHERE user_id = '$user_id'";
$profile_result = $conn->query($profile_sql);

if ($profile_result->num_rows > 0) {
    $profile_row = $profile_result->fetch_assoc();
    $profile_id = $profile_row['profile_id'];
} else {
    echo "Error: Profile not found.";
    exit();
}

// Check if the user has previously applied for this job (including withdrawn applications)
$existing_application_sql = "SELECT * FROM applications WHERE job_id = '$job_id' AND profile_id = '$profile_id'";
$existing_application_result = $conn->query($existing_application_sql);
$existing_application = $existing_application_result->fetch_assoc();

$reapplying = false; // To check if the user is reapplying after withdrawal

if ($existing_application) {
    if ($existing_application['application_status'] == 'WITHDRAWN') {
        // If the user previously withdrew their application, allow them to re-apply by updating the existing application
        $reapplying = true;
    } else {
        // If the user already applied and didn't withdraw, show an error message
        echo "You have already applied for this job. You cannot apply again unless your application is withdrawn.";
        exit();
    }
}

// Fetch the questionnaire for the job (if any)
$question_sql = "SELECT * FROM questionnaire_template WHERE job_id = $job_id";
$question_result = $conn->query($question_sql);

$target_dir = __DIR__ . "/uploads/";  // Define the upload directory

// Create the directory if it doesn't exist
if (!is_dir($target_dir)) {
    mkdir($target_dir, 0777, true); // Create directory with permissions
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Handle file upload
    $file = $_FILES['resume'];
    $file_name = basename($file['name']);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Check for valid file type and size
    $valid_file_types = ['pdf', 'doc', 'docx'];
    if (!in_array($file_type, $valid_file_types)) {
        $error_message = "Only PDF, DOC, and DOCX files are allowed.";
    } elseif ($file['size'] > 5000000) {
        $error_message = "The file is too large. Maximum size is 5MB.";
    } else {
        // Rename the file with the format: user_id-profile_name-timestamp-job_id.extension
        $timestamp = date('Ymd_His'); // Current timestamp in format Y-m-d_H-i-s
        $new_file_name = $user_id . '-' . $profile_id . '-' . $timestamp . '-job_' . $job_id . '.' . $file_type;
        $target_file = $target_dir . $new_file_name; // Full path to the renamed file

        // Move the uploaded file to the target directory
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $_SESSION['resume_file'] = $target_file; // Store the renamed resume path in session
            $success_message = "Resume uploaded successfully!";
        } else {
            $error_message = "There was an error uploading your resume.";
        }
    }

    // Get the values of the new fields from the form
    $referral_source = $conn->real_escape_string($_POST['referral_source']);
    $qualifications = $conn->real_escape_string($_POST['qualifications']);
    $skills = $conn->real_escape_string($_POST['skills']);
    $work_experience = $conn->real_escape_string($_POST['work_experience']);

    // Start a transaction to ensure atomicity of the insert/update operations
    $conn->begin_transaction();

    try {
        if ($reapplying) {
            // Update the withdrawn application with the new details
            $application_id = $existing_application['application_id'];
            $update_application_sql = "UPDATE applications 
                                       SET application_status = 'APPLIED', 
                                           resume = '{$_SESSION['resume_file']}', 
                                           time_applied = NOW(),
                                           referral_source = '$referral_source', 
                                           qualifications = '$qualifications', 
                                           skills = '$skills', 
                                           work_experience = '$work_experience',
                                           withdrawn_at = NULL  -- Set withdrawn_at to NULL when reapplying
                                       WHERE application_id = '$application_id'";
            $conn->query($update_application_sql);

            // Delete any previous answers and insert new ones
            $delete_answers_sql = "DELETE FROM application_answers WHERE application_id = '$application_id'";
            $conn->query($delete_answers_sql);

            // Decrease withdrawn_applicants in job metrics since user is re-applying
            $update_metrics_sql = "UPDATE tbl_job_metrics 
                                   SET withdrawn_applicants = withdrawn_applicants - 1 
                                   WHERE job_id = '$job_id'";
            $conn->query($update_metrics_sql);

            // Update pipeline stage: set withdrawn_at to NULL and update applied_at
            $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                    SET withdrawn_at = NULL, applied_at = NOW() 
                                    WHERE application_id = '$application_id'";
            $conn->query($update_pipeline_sql);
        } else {
            // Insert into the applications table (new application)
            $insert_application_sql = "INSERT INTO applications (job_id, profile_id, resume, application_status, time_applied, recruiter_id, referral_source, qualifications, skills, work_experience)
                                       VALUES ('$job_id', '$profile_id', '{$_SESSION['resume_file']}', 'APPLIED', NOW(), (SELECT created_by FROM job_postings WHERE job_id = '$job_id'), '$referral_source', '$qualifications', '$skills', '$work_experience')";
            $conn->query($insert_application_sql);
            $application_id = $conn->insert_id;

            // Update total_applicants in job metrics
            $metrics_check_sql = "SELECT * FROM tbl_job_metrics WHERE job_id = '$job_id'";
            $metrics_check_result = $conn->query($metrics_check_sql);

            if ($metrics_check_result->num_rows > 0) {
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET total_applicants = total_applicants + 1 
                                       WHERE job_id = '$job_id'";
            } else {
                $update_metrics_sql = "INSERT INTO tbl_job_metrics (job_id, total_applicants) VALUES ('$job_id', 1)";
            }
            $conn->query($update_metrics_sql);

            // Insert into tbl_pipeline_stage (new pipeline entry)
            $insert_pipeline_sql = "INSERT INTO tbl_pipeline_stage (application_id, applied_at) VALUES ('$application_id', NOW())";
            $conn->query($insert_pipeline_sql);

            // Update the referral source counts in tbl_job_metrics
            if ($referral_source === 'referral_applicants') {
                $update_referral_sql = "UPDATE tbl_job_metrics 
                                        SET referral_applicants = referral_applicants + 1 
                                        WHERE job_id = '$job_id'";
                $conn->query($update_referral_sql);
            } elseif ($referral_source === 'social_media_applicants') {
                $update_social_media_sql = "UPDATE tbl_job_metrics 
                                            SET social_media_applicants = social_media_applicants + 1 
                                            WHERE job_id = '$job_id'";
                $conn->query($update_social_media_sql);
            } elseif ($referral_source === 'career_site_applicants') {
                $update_career_site_sql = "UPDATE tbl_job_metrics 
                                           SET career_site_applicants = career_site_applicants + 1 
                                           WHERE job_id = '$job_id'";
                $conn->query($update_career_site_sql);
            }
        }

        // Insert answers into the application_answers table
        if (isset($_POST['answers'])) {
            foreach ($_POST['answers'] as $question_id => $answer) {
                $answer = $conn->real_escape_string($answer);
                $insert_answer_sql = "INSERT INTO application_answers (application_id, question_id, answer_text)
                                      VALUES ('$application_id', '$question_id', '$answer')";
                $conn->query($insert_answer_sql);
            }
        }

        // Commit the transaction
        $conn->commit();

        // Redirect to view_jobs.php after successful submission
        header('Location: view_job.php');
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "Error processing the application: " . $e->getMessage();
    }

    exit();
}
?>

<!-- HTML for the job application form -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Apply for Job</title>
</head>

<body>
    <h2>Apply for Job: <?php echo htmlspecialchars($job['job_title']); ?></h2>
    <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
    <p><strong>Salary Range:</strong> <?php echo htmlspecialchars($job['min_salary']); ?> - <?php echo htmlspecialchars($job['max_salary']); ?></p>

    <form action="apply_job.php?job_id=<?php echo $job_id; ?>" method="POST" enctype="multipart/form-data">
        <label for="resume">Upload Resume:</label>
        <input type="file" name="resume" id="resume" required><br>

        <label for="referral_source">How did you hear about this job?</label>
        <select name="referral_source" id="referral_source" required>
            <option value="referral_applicants">Employee Referral</option>
            <option value="social_media_applicants">Social Media</option>
            <option value="career_site_applicants">Career Website</option>
        </select><br>

        <label for="qualifications">Qualifications:</label><br>
        <textarea name="qualifications" id="qualifications" rows="4" cols="50" required></textarea><br>

        <label for="skills">Skills:</label><br>
        <textarea name="skills" id="skills" rows="4" cols="50" required></textarea><br>

        <label for="work_experience">Work Experience:</label><br>
        <textarea name="work_experience" id="work_experience" rows="4" cols="50" required></textarea><br>

        <!-- Display job-specific questions (if any) -->
        <?php if ($question_result->num_rows > 0): ?>
            <h3>Job Questionnaire</h3>
            <?php while ($question = $question_result->fetch_assoc()): ?>
                <label><?php echo htmlspecialchars($question['question_text']); ?></label><br>

                <!-- Display input based on question type -->
                <?php if ($question['question_type'] === 'TEXT'): ?>
                    <input type="text" name="answers[<?php echo $question['question_id']; ?>]" required><br>

                <?php elseif ($question['question_type'] === 'YES_NO'): ?>
                    <select name="answers[<?php echo $question['question_id']; ?>]" required>
                        <option value="YES">Yes</option>
                        <option value="NO">No</option>
                    </select><br>

                <?php elseif ($question['question_type'] === 'MULTIPLE_CHOICE'): ?>
                    <!-- Radio buttons for multiple choice -->
                    <?php if (!empty($question['choice_a'])): ?>
                        <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="A" required> <?php echo htmlspecialchars($question['choice_a']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($question['choice_b'])): ?>
                        <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="B" required> <?php echo htmlspecialchars($question['choice_b']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($question['choice_c'])): ?>
                        <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="C" required> <?php echo htmlspecialchars($question['choice_c']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($question['choice_d'])): ?>
                        <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="D" required> <?php echo htmlspecialchars($question['choice_d']); ?><br>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endwhile; ?>
        <?php endif; ?>

        <button type="submit">Submit Application</button>
    </form>
</body>

</html>

<?php
$conn->close();
?>