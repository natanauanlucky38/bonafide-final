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
        $target_file = __DIR__ . "/uploads/" . $new_file_name;

        // Move the uploaded file to the target directory
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $_SESSION['resume_file'] = $target_file;
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
        // Insert application data
        $insert_application_sql = "INSERT INTO applications (job_id, profile_id, resume, application_status, time_applied, recruiter_id, referral_source)
                                   VALUES ('$job_id', '$profile_id', '{$_SESSION['resume_file']}', 'APPLIED', NOW(), (SELECT created_by FROM job_postings WHERE job_id = '$job_id'), '$referral_source')";
        $conn->query($insert_application_sql);
        $application_id = $conn->insert_id;

        // Insert qualifications, skills, and work experience into `profile_details`
        foreach ($qualifications as $qualification) {
            $qualification = $conn->real_escape_string($qualification);
            $insert_qualification_sql = "INSERT INTO profile_details (profile_id, detail_value, qualifications)
                                         VALUES ('$profile_id', '$qualification', 'qualification')";
            $conn->query($insert_qualification_sql);
        }

        foreach ($skills as $skill) {
            $skill = $conn->real_escape_string($skill);
            $insert_skill_sql = "INSERT INTO profile_details (profile_id, detail_value, skills)
                                 VALUES ('$profile_id', '$skill', 'skill')";
            $conn->query($insert_skill_sql);
        }

        foreach ($work_experience as $experience) {
            $experience = $conn->real_escape_string($experience);
            $insert_experience_sql = "INSERT INTO profile_details (profile_id, detail_value, work_experience)
                                      VALUES ('$profile_id', '$experience', 'work_experience')";
            $conn->query($insert_experience_sql);
        }

        // Insert the questionnaire answers into the `application_answers` table
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $question_id => $answer) {
                $answer = $conn->real_escape_string($answer);
                $insert_answer_sql = "INSERT INTO application_answers (application_id, question_id, answer_text)
                                      VALUES ('$application_id', '$question_id', '$answer')";
                $conn->query($insert_answer_sql);
            }
        }

        // Insert into tbl_pipeline_stage
        $insert_pipeline_sql = "INSERT INTO tbl_pipeline_stage (application_id, applied_at) VALUES ('$application_id', NOW())";
        $conn->query($insert_pipeline_sql);

        // Update metrics in tbl_job_metrics
        $metrics_check_sql = "SELECT * FROM tbl_job_metrics WHERE job_id = '$job_id'";
        $metrics_check_result = $conn->query($metrics_check_sql);

        if ($metrics_check_result->num_rows > 0) {
            // Update referral-specific applicant counts based on referral source
            if ($referral_source === 'referral_applicants') {
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET total_applicants = total_applicants + 1, 
                                           referral_applicants = referral_applicants + 1 
                                       WHERE job_id = '$job_id'";
            } elseif ($referral_source === 'social_media_applicants') {
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET total_applicants = total_applicants + 1, 
                                           social_media_applicants = social_media_applicants + 1 
                                       WHERE job_id = '$job_id'";
            } elseif ($referral_source === 'career_site_applicants') {
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET total_applicants = total_applicants + 1, 
                                           career_site_applicants = career_site_applicants + 1 
                                       WHERE job_id = '$job_id'";
            }
        } else {
            // First applicant for the job; insert the initial metric values
            $update_metrics_sql = "INSERT INTO tbl_job_metrics (job_id, total_applicants, referral_applicants, social_media_applicants, career_site_applicants)
                                   VALUES ('$job_id', 1, 
                                   IF('$referral_source' = 'referral_applicants', 1, 0), 
                                   IF('$referral_source' = 'social_media_applicants', 1, 0), 
                                   IF('$referral_source' = 'career_site_applicants', 1, 0))";
        }
        $conn->query($update_metrics_sql);

        // Commit the transaction
        $conn->commit();

        // Redirect after successful submission
        header('Location: view_job.php');
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
        <input type="file" name="resume" id="resume" required><br>

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