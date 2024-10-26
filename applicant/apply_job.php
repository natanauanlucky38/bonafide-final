<?php
include '../db.php';  // Database connection
include 'sidebar.php';

// Check if the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');
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

// Fetch the questionnaire for the job (if any)
$question_sql = "SELECT * FROM questionnaire_template WHERE job_id = $job_id";
$question_result = $conn->query($question_sql);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the resume file
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
        $timestamp = date('Ymd_His');
        $new_file_name = $user_id . '-' . $profile_id . '-' . $timestamp . '-job_' . $job_id . '.' . $file_type;
        $target_file = "applicant/uploads/" . $new_file_name; // Update the path format as required

        // Move the uploaded file to the target directory
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $_SESSION['resume_file'] = $target_file; // Store the relative path
        } else {
            $error_message = "There was an error uploading your resume.";
        }
    }

    // Collect qualifications, skills, and work experience from the form
    $qualifications = $_POST['qualifications'] ?? [];
    $skills = $_POST['skills'] ?? [];
    $work_experience = $_POST['work_experience'] ?? [];

    // Get the referral source from the form
    $referral_source = $_POST['referral_source'];

    // Start a transaction to ensure atomicity of the insert/update operations
    $conn->begin_transaction();
    try {
        // Check if an application already exists
        $check_application_sql = "SELECT application_id, application_status FROM applications WHERE job_id = ? AND profile_id = ?";
        $check_application_stmt = $conn->prepare($check_application_sql);
        $check_application_stmt->bind_param('ii', $job_id, $profile_id);
        $check_application_stmt->execute();
        $check_application_stmt->store_result();

        if ($check_application_stmt->num_rows > 0) {
            // Re-apply (Update existing application)
            $check_application_stmt->bind_result($application_id, $application_status);
            $check_application_stmt->fetch();

            // If the previous application was withdrawn, update withdrawn_applicants
            if ($application_status == 'WITHDRAWN') {
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET withdrawn_applicants = withdrawn_applicants - 1 
                                       WHERE job_id = ?";
                $metrics_stmt = $conn->prepare($update_metrics_sql);
                $metrics_stmt->bind_param('i', $job_id);
                $metrics_stmt->execute();
            }

            // Update the application and re-apply
            $update_application_sql = "UPDATE applications 
                                       SET resume = ?, application_status = 'APPLIED', referral_source = ?, recruiter_id = (SELECT created_by FROM job_postings WHERE job_id = ?)
                                       WHERE application_id = ?";
            $update_application_stmt = $conn->prepare($update_application_sql);
            $update_application_stmt->bind_param('ssii', $_SESSION['resume_file'], $referral_source, $job_id, $application_id);
            $update_application_stmt->execute();

            // Update the pipeline stage, reset withdrawn_at and total_duration
            $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                    SET applied_at = NOW(), withdrawn_at = NULL, total_duration = 0 
                                    WHERE application_id = ?";
            $update_pipeline_stmt = $conn->prepare($update_pipeline_sql);
            $update_pipeline_stmt->bind_param('i', $application_id);
            $update_pipeline_stmt->execute();
        } else {
            // Insert new application
            $insert_application_sql = "INSERT INTO applications (job_id, profile_id, resume, application_status, recruiter_id, referral_source)
                                       VALUES (?, ?, ?, 'APPLIED', (SELECT created_by FROM job_postings WHERE job_id = ?), ?)";
            $insert_application_stmt = $conn->prepare($insert_application_sql);
            $insert_application_stmt->bind_param('iisis', $job_id, $profile_id, $_SESSION['resume_file'], $job_id, $referral_source);
            $insert_application_stmt->execute();
            $application_id = $conn->insert_id;

            // Insert new pipeline stage
            $insert_pipeline_sql = "INSERT INTO tbl_pipeline_stage (application_id, applied_at) 
                                    VALUES (?, NOW())";
            $insert_pipeline_stmt = $conn->prepare($insert_pipeline_sql);
            $insert_pipeline_stmt->bind_param('i', $application_id);
            $insert_pipeline_stmt->execute();

            // Increment total_applicants in tbl_job_metrics for a new application only
            $metrics_check_sql = "SELECT * FROM tbl_job_metrics WHERE job_id = ?";
            $metrics_check_stmt = $conn->prepare($metrics_check_sql);
            $metrics_check_stmt->bind_param('i', $job_id);
            $metrics_check_stmt->execute();
            $metrics_check_result = $metrics_check_stmt->get_result();

            if ($metrics_check_result->num_rows > 0) {
                // Update referral-specific applicant counts based on referral source
                if ($referral_source === 'referral_applicants') {
                    $update_metrics_sql = "UPDATE tbl_job_metrics 
                                           SET total_applicants = total_applicants + 1, 
                                               referral_applicants = referral_applicants + 1 
                                           WHERE job_id = ?";
                } elseif ($referral_source === 'social_media_applicants') {
                    $update_metrics_sql = "UPDATE tbl_job_metrics 
                                           SET total_applicants = total_applicants + 1, 
                                               social_media_applicants = social_media_applicants + 1 
                                           WHERE job_id = ?";
                } elseif ($referral_source === 'career_site_applicants') {
                    $update_metrics_sql = "UPDATE tbl_job_metrics 
                                           SET total_applicants = total_applicants + 1, 
                                               career_site_applicants = career_site_applicants + 1 
                                           WHERE job_id = ?";
                }
                $update_metrics_stmt = $conn->prepare($update_metrics_sql);
                $update_metrics_stmt->bind_param('i', $job_id);
                $update_metrics_stmt->execute();
            } else {
                // First applicant for the job; insert the initial metric values
                $insert_metrics_sql = "INSERT INTO tbl_job_metrics (job_id, total_applicants, referral_applicants, social_media_applicants, career_site_applicants)
                                       VALUES (?, 1, 
                                       IF(? = 'referral_applicants', 1, 0), 
                                       IF(? = 'social_media_applicants', 1, 0), 
                                       IF(? = 'career_site_applicants', 1, 0))";
                $insert_metrics_stmt = $conn->prepare($insert_metrics_sql);
                $insert_metrics_stmt->bind_param('isss', $job_id, $referral_source, $referral_source, $referral_source);
                $insert_metrics_stmt->execute();
            }
        }

        // Insert answers into application_answers
        if (isset($_POST['answers'])) {
            $insert_answer_stmt = $conn->prepare("INSERT INTO application_answers (application_id, question_id, answer_text) VALUES (?, ?, ?)");

            foreach ($_POST['answers'] as $question_id => $answer_text) {
                $insert_answer_stmt->bind_param('iis', $application_id, $question_id, $answer_text);
                $insert_answer_stmt->execute();
            }

            $insert_answer_stmt->close();
        }

        // Insert qualifications, skills, and work experience into profile_details
        if (!empty($qualifications)) {
            $insert_qualification_stmt = $conn->prepare("INSERT INTO profile_details (profile_id, detail_value, qualifications, skills, work_experience) VALUES (?, ?, 'qualification', '', '')");
            foreach ($qualifications as $qualification) {
                $insert_qualification_stmt->bind_param('is', $profile_id, $qualification);
                $insert_qualification_stmt->execute();
            }
        }

        if (!empty($skills)) {
            $insert_skills_stmt = $conn->prepare("INSERT INTO profile_details (profile_id, detail_value, qualifications, skills, work_experience) VALUES (?, ?, '', 'skill', '')");
            foreach ($skills as $skill) {
                $insert_skills_stmt->bind_param('is', $profile_id, $skill);
                $insert_skills_stmt->execute();
            }
        }

        if (!empty($work_experience)) {
            $insert_experience_stmt = $conn->prepare("INSERT INTO profile_details (profile_id, detail_value, qualifications, skills, work_experience) VALUES (?, ?, '', '', 'work_experience')");
            foreach ($work_experience as $experience) {
                $insert_experience_stmt->bind_param('is', $profile_id, $experience);
                $insert_experience_stmt->execute();
            }
        }

        // Fetch recruiter ID for notification
        $recruiter_id_query = "SELECT created_by FROM job_postings WHERE job_id = ?";
        $recruiter_stmt = $conn->prepare($recruiter_id_query);
        $recruiter_stmt->bind_param('i', $job_id);
        $recruiter_stmt->execute();
        $recruiter_result = $recruiter_stmt->get_result();
        $recruiter_data = $recruiter_result->fetch_assoc();
        $recruiter_id = $recruiter_data['created_by'];

        // Insert notification for recruiter about the new application
        $notification_title = "New Application Submitted";
        $notification_subject = "A new application has been submitted for the job: " . $job['job_title'];
        $notification_link = "view_application.php?application_id=" . $application_id;

        $insert_notification_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                                    VALUES (?, ?, ?, ?, 0)";
        $insert_notification_stmt = $conn->prepare($insert_notification_sql);
        $insert_notification_stmt->bind_param('isss', $recruiter_id, $notification_title, $notification_subject, $notification_link);
        $insert_notification_stmt->execute();

        // Commit the transaction
        $conn->commit();

        // Redirect after successful submission
        header('Location: view_job.php'); // Ensure this points to the correct page
        exit();
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollback();
        echo "Error processing the application: " . $e->getMessage();
    }
}
?>

<!-- HTML for the job application form -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Apply for Job</title>
    <script>
        // Function to add new input fields for qualifications, skills, and work experience
        function addField(type) {
            let container = document.getElementById(type + '-container');
            let input = document.createElement('input');
            input.type = 'text';
            input.name = type + '[]'; // Array input
            input.placeholder = 'Enter ' + type.replace('_', ' ');
            container.appendChild(input);
            container.appendChild(document.createElement('br'));
        }

        function removeField(type) {
            let container = document.getElementById(type + '-container');
            if (container.children.length > 1) {
                container.removeChild(container.lastChild); // Remove the last input
                container.removeChild(container.lastChild); // Remove the last <br>
            }
        }
    </script>
</head>

<body>
    <h2>Apply for Job: <?php echo htmlspecialchars($job['job_title']); ?></h2>
    <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
    <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
    <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
    <p><strong>Salary Range:</strong> <?php echo htmlspecialchars($job['min_salary']); ?> - <?php echo htmlspecialchars($job['max_salary']); ?></p>

    <form action="apply_job.php?job_id=<?php echo $job_id; ?>" method="POST" enctype="multipart/form-data">
        <label for="resume">Upload Resume:</label>
        <input type="file" name="resume" id="resume" required accept=".pdf,.doc,.docx"><br>

        <!-- Dropdown for referral source -->
        <label for="referral_source">How did you hear about this job?</label>
        <select name="referral_source" id="referral_source" required>
            <option value="referral_applicants">Employee Referral</option>
            <option value="social_media_applicants">Social Media</option>
            <option value="career_site_applicants">Career Website</option>
        </select><br>

        <!-- Dynamic Qualification input fields -->
        <label for="qualifications">Qualifications:</label>
        <div id="qualifications-container">
            <input type="text" name="qualifications[]" placeholder="Enter qualification"><br>
        </div>
        <button type="button" onclick="addField('qualifications')">Add Qualification</button>
        <button type="button" onclick="removeField('qualifications')">Remove Qualification</button><br>

        <!-- Dynamic Skills input fields -->
        <label for="skills">Skills:</label>
        <div id="skills-container">
            <input type="text" name="skills[]" placeholder="Enter skill"><br>
        </div>
        <button type="button" onclick="addField('skills')">Add Skill</button>
        <button type="button" onclick="removeField('skills')">Remove Skill</button><br>

        <!-- Dynamic Work Experience input fields -->
        <label for="work_experience">Work Experience:</label>
        <div id="work_experience-container">
            <input type="text" name="work_experience[]" placeholder="Enter work experience"><br>
        </div>
        <button type="button" onclick="addField('work_experience')">Add Work Experience</button>
        <button type="button" onclick="removeField('work_experience')">Remove Work Experience</button><br>

        <!-- Job-specific questions (if any) -->
        <?php if ($question_result->num_rows > 0): ?>
            <h3>Job Questionnaire</h3>
            <?php while ($question = $question_result->fetch_assoc()): ?>
                <label><?php echo htmlspecialchars($question['question_text']); ?></label><br>
                <?php if ($question['question_type'] === 'TEXT'): ?>
                    <input type="text" name="answers[<?php echo $question['question_id']; ?>]" required><br>
                <?php elseif ($question['question_type'] === 'YES_NO'): ?>
                    <select name="answers[<?php echo $question['question_id']; ?>]" required>
                        <option value="YES">Yes</option>
                        <option value="NO">No</option>
                    </select><br>
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