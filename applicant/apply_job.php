<?php
include '../db.php';  // Database connection
include 'sidebar.php';
include 'header.php';

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

// Define upload directory for resume files
$uploadDir = realpath(dirname(__FILE__) . '/../applicant/uploads') . '/';

// Fetch job details to display
$job_sql = "SELECT * FROM job_postings WHERE job_id = ?";
$job_stmt = $conn->prepare($job_sql);
$job_stmt->bind_param("i", $job_id);
$job_stmt->execute();
$job_result = $job_stmt->get_result();
if ($job_result->num_rows == 0) {
    echo "Job not found.";
    exit();
}
$job = $job_result->fetch_assoc();

// Fetch the user's profile_id
$profile_sql = "SELECT profile_id FROM profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();

if ($profile_result->num_rows > 0) {
    $profile_row = $profile_result->fetch_assoc();
    $profile_id = $profile_row['profile_id'];
} else {
    echo "Error: Profile not found.";
    exit();
}

// Fetch the questionnaire for the job (if any)
$question_sql = "SELECT * FROM questionnaire_template WHERE job_id = ?";
$question_stmt = $conn->prepare($question_sql);
$question_stmt->bind_param("i", $job_id);
$question_stmt->execute();
$question_result = $question_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the resume file
    $file = $_FILES['resume'];
    $file_name = basename($file['name']);
    $file_type = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    // Validate resume file type and size
    $valid_file_types = ['pdf', 'doc', 'docx'];
    if (!in_array($file_type, $valid_file_types)) {
        $error_message = "Only PDF, DOC, and DOCX files are allowed.";
    } elseif ($file['size'] > 5000000) {
        $error_message = "The file is too large. Maximum size is 5MB.";
    } else {
        $timestamp = date('Ymd_His');
        $new_file_name = $user_id . '-' . $profile_id . '-' . $timestamp . '-job_' . $job_id . '.' . $file_type;
        $target_file = $uploadDir . $new_file_name;

        // Move the uploaded file to the target directory
        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $_SESSION['resume_file'] = $target_file; // Store the file path for later database use
        } else {
            $error_message = "There was an error uploading your resume.";
        }
    }

    if (!isset($error_message)) {
        // Collect additional data from the form
        $qualifications = $_POST['qualifications'] ?? [];
        $skills = $_POST['skills'] ?? [];
        $work_experience = $_POST['work_experience'] ?? [];
        $referral_source = $_POST['referral_source'];
        $answers = $_POST['answers'] ?? []; // Store questionnaire answers

        $conn->begin_transaction();
        try {
            // Check if an application already exists for this job and user
            $check_application_sql = "SELECT application_id, application_status FROM applications WHERE job_id = ? AND profile_id = ?";
            $check_application_stmt = $conn->prepare($check_application_sql);
            if (!$check_application_stmt) {
                throw new Exception("Error preparing statement: " . $conn->error);
            }
            $check_application_stmt->bind_param('ii', $job_id, $profile_id);
            $check_application_stmt->execute();
            $check_application_stmt->store_result();

            if ($check_application_stmt->num_rows > 0) {
                // Application exists; re-apply or update
                $check_application_stmt->bind_result($application_id, $application_status);
                $check_application_stmt->fetch();

                if ($application_status == 'WITHDRAWN') {
                    $update_metrics_sql = "UPDATE tbl_job_metrics 
                                           SET withdrawn_applicants = withdrawn_applicants - 1 
                                           WHERE job_id = ?";
                    $metrics_stmt = $conn->prepare($update_metrics_sql);
                    if (!$metrics_stmt) {
                        throw new Exception("Error preparing metrics statement: " . $conn->error);
                    }
                    $metrics_stmt->bind_param('i', $job_id);
                    $metrics_stmt->execute();
                }

                // Update application with new resume and status
                $update_application_sql = "UPDATE applications 
                                           SET resume = ?, application_status = 'APPLIED', referral_source = ?, recruiter_id = (SELECT created_by FROM job_postings WHERE job_id = ?)
                                           WHERE application_id = ?";
                $update_application_stmt = $conn->prepare($update_application_sql);
                if (!$update_application_stmt) {
                    throw new Exception("Error preparing application update statement: " . $conn->error);
                }
                $update_application_stmt->bind_param('ssii', $_SESSION['resume_file'], $referral_source, $job_id, $application_id);
                $update_application_stmt->execute();

                // Update pipeline stage
                $update_pipeline_sql = "UPDATE tbl_pipeline_stage 
                                        SET applied_at = NOW(), withdrawn_at = NULL, total_duration = 0 
                                        WHERE application_id = ?";
                $update_pipeline_stmt = $conn->prepare($update_pipeline_sql);
                if (!$update_pipeline_stmt) {
                    throw new Exception("Error preparing pipeline update statement: " . $conn->error);
                }
                $update_pipeline_stmt->bind_param('i', $application_id);
                $update_pipeline_stmt->execute();
            } else {
                // Insert new application
                $insert_application_sql = "INSERT INTO applications (job_id, profile_id, resume, application_status, recruiter_id, referral_source)
                                           VALUES (?, ?, ?, 'APPLIED', (SELECT created_by FROM job_postings WHERE job_id = ?), ?)";
                $insert_application_stmt = $conn->prepare($insert_application_sql);
                if (!$insert_application_stmt) {
                    throw new Exception("Error preparing application insertion statement: " . $conn->error);
                }
                $insert_application_stmt->bind_param('iisis', $job_id, $profile_id, $_SESSION['resume_file'], $job_id, $referral_source);
                $insert_application_stmt->execute();
                $application_id = $conn->insert_id;

                // Insert new pipeline stage
                $insert_pipeline_sql = "INSERT INTO tbl_pipeline_stage (application_id, applied_at) 
                                        VALUES (?, NOW())";
                $insert_pipeline_stmt = $conn->prepare($insert_pipeline_sql);
                if (!$insert_pipeline_stmt) {
                    throw new Exception("Error preparing pipeline insertion statement: " . $conn->error);
                }
                $insert_pipeline_stmt->bind_param('i', $application_id);
                $insert_pipeline_stmt->execute();

                // Insert Qualifications
                if (!empty($qualifications)) {
                    foreach ($qualifications as $qualification) {
                        if (!empty($qualification)) { // Ensure qualification is not empty
                            $insert_qualifications_sql = "INSERT INTO profile_details (profile_id, qualifications) VALUES (?, ?)";
                            $insert_qualifications_stmt = $conn->prepare($insert_qualifications_sql);
                            $insert_qualifications_stmt->bind_param('is', $profile_id, $qualification);
                            $insert_qualifications_stmt->execute();
                            $insert_qualifications_stmt->close();
                        }
                    }
                }

                // Insert Skills
                if (!empty($skills)) {
                    foreach ($skills as $skill) {
                        if (!empty($skill)) { // Ensure skill is not empty
                            $insert_skills_sql = "INSERT INTO profile_details (profile_id, skills) VALUES (?, ?)";
                            $insert_skills_stmt = $conn->prepare($insert_skills_sql);
                            $insert_skills_stmt->bind_param('is', $profile_id, $skill);
                            $insert_skills_stmt->execute();
                            $insert_skills_stmt->close();
                        }
                    }
                }

                // Insert Work Experience
                if (!empty($work_experience)) {
                    foreach ($work_experience as $experience) {
                        if (!empty($experience)) { // Ensure experience is not empty
                            $insert_work_experience_sql = "INSERT INTO profile_details (profile_id, work_experience) VALUES (?, ?)";
                            $insert_work_experience_stmt = $conn->prepare($insert_work_experience_sql);
                            $insert_work_experience_stmt->bind_param('is', $profile_id, $experience);
                            $insert_work_experience_stmt->execute();
                            $insert_work_experience_stmt->close();
                        }
                    }
                }

                // Update job metrics to increase total applicants and increment based on referral source
                $metrics_column = '';
                switch ($referral_source) {
                    case 'referral_applicants':
                        $metrics_column = 'referral_applicants';
                        break;
                    case 'social_media_applicants':
                        $metrics_column = 'social_media_applicants';
                        break;
                    case 'career_site_applicants':
                        $metrics_column = 'career_site_applicants';
                        break;
                }
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET total_applicants = total_applicants + 1, $metrics_column = $metrics_column + 1 
                                       WHERE job_id = ?";
                $metrics_stmt = $conn->prepare($update_metrics_sql);
                if (!$metrics_stmt) {
                    throw new Exception("Error preparing metrics update statement: " . $conn->error);
                }
                $metrics_stmt->bind_param('i', $job_id);
                $metrics_stmt->execute();
            }

            // Automatically fill requirement_tracking with the req_id and application_id if they don't already exist
            $requirements_sql = "SELECT req_id FROM requirement WHERE job_id = ?";
            $requirements_stmt = $conn->prepare($requirements_sql);
            if (!$requirements_stmt) {
                echo "Error preparing requirements selection statement: " . $conn->error;
                exit();
            }
            $requirements_stmt->bind_param('i', $job_id);
            $requirements_stmt->execute();
            $requirements_result = $requirements_stmt->get_result();

            // Prepare the requirement_tracking insertion statement with a conditional check
            $insert_requirements_sql = "
    INSERT INTO requirement_tracking (req_id, application_id, is_submitted)
    SELECT ?, ?, 0
    FROM DUAL
    WHERE NOT EXISTS (
        SELECT 1 FROM requirement_tracking WHERE req_id = ? AND application_id = ?
    )
";
            $insert_requirements_stmt = $conn->prepare($insert_requirements_sql);
            if (!$insert_requirements_stmt) {
                echo "Error preparing requirement_tracking insertion statement: " . $conn->error;
            } else {
                while ($row = $requirements_result->fetch_assoc()) {
                    $req_id = $row['req_id'];
                    // Bind parameters and execute only if the combination does not exist
                    $insert_requirements_stmt->bind_param('iiii', $req_id, $application_id, $req_id, $application_id);
                    $insert_requirements_stmt->execute();
                }
            }

            // Insert questionnaire answers
            if (!empty($answers)) {
                $insert_answer_sql = "INSERT INTO application_answers (application_id, question_id, answer_text) VALUES (?, ?, ?)";
                $insert_answer_stmt = $conn->prepare($insert_answer_sql);
                foreach ($answers as $question_id => $answer_text) {
                    $insert_answer_stmt->bind_param('iis', $application_id, $question_id, $answer_text);
                    $insert_answer_stmt->execute();
                }
                $insert_answer_stmt->close();
            }

            // Fetch recruiter ID and send notification
            $recruiter_id_query = "SELECT created_by FROM job_postings WHERE job_id = ?";
            $recruiter_stmt = $conn->prepare($recruiter_id_query);
            if (!$recruiter_stmt) {
                throw new Exception("Error preparing recruiter ID retrieval statement: " . $conn->error);
            }
            $recruiter_stmt->bind_param('i', $job_id);
            $recruiter_stmt->execute();
            $recruiter_result = $recruiter_stmt->get_result();
            $recruiter_data = $recruiter_result->fetch_assoc();
            $recruiter_id = $recruiter_data['created_by'];

            // Insert notification
            $notification_title = "New Application Submitted";
            $notification_subject = "A new application has been submitted for the job: " . $job['job_title'];
            $notification_link = "view_application.php?application_id=" . $application_id;

            $insert_notification_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                                        VALUES (?, ?, ?, ?, 0)";
            $insert_notification_stmt = $conn->prepare($insert_notification_sql);
            if (!$insert_notification_stmt) {
                throw new Exception("Error preparing notification insertion statement: " . $conn->error);
            }
            $insert_notification_stmt->bind_param('isss', $recruiter_id, $notification_title, $notification_subject, $notification_link);
            $insert_notification_stmt->execute();

            // Commit the transaction
            $conn->commit();

            // Redirect after successful submission
            header('Location: view_job.php');
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            echo "Error processing the application: " . $e->getMessage();
        }
    }
}
?>


<!-- HTML for the job application form -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Apply for Job</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="applicant_styles.css"> <!-- Include your CSS styles here -->

    <script>
        function addField(type) {
            let container = document.getElementById(type + '-container');
            let input = document.createElement('input');
            input.type = 'text';
            input.name = type + '[]';
            input.placeholder = 'Enter ' + type.replace('_', ' ');
            container.appendChild(input);
            container.appendChild(document.createElement('br'));
        }

        function removeField(type) {
            let container = document.getElementById(type + '-container');
            if (container.children.length > 1) {
                container.removeChild(container.lastChild);
                container.removeChild(container.lastChild);
            }
        }
    </script>
</head>

<body class="apply_job-main-conntent">

    <div class="apply_job-container">
        <h2>Apply for Job: <?php echo htmlspecialchars($job['job_title']); ?></h2>
        <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
        <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
        <p><strong>Salary Range: </strong>₱<?php echo htmlspecialchars($job['min_salary']); ?> - ₱<?php echo htmlspecialchars($job['max_salary']); ?></p>


        <form action="apply_job.php?job_id=<?php echo $job_id; ?>" method="POST" enctype="multipart/form-data">
            <label for="resume">Upload Resume:</label>
            <input type="file" name="resume" id="resume" required accept=".pdf,.doc,.docx"><br>

            <label for="referral_source">How did you hear about this job?</label>
            <select name="referral_source" id="referral_source" required>
                <option value="referral_applicants">Employee Referral</option>
                <option value="social_media_applicants">Social Media</option>
                <option value="career_site_applicants">Career Website</option>
            </select><br>

            <label for="qualifications">Qualifications:</label>
            <div id="qualifications-container">
                <input type="text" name="qualifications[]" placeholder="Enter qualification"><br>
            </div>
            <button type="button" onclick="addField('qualifications')"><i class="fas fa-plus"></i> Add Qualification</button>
            <button type="button" onclick="removeField('qualifications')"><i class="fas fa-minus"></i> Remove Qualification</button><br>

            <label for="skills">Skills:</label>
            <div id="skills-container">
                <input type="text" name="skills[]" placeholder="Enter skill"><br>
            </div>
            <button type="button" onclick="addField('skills')"><i class="fas fa-plus"></i> Add Skill</button>
            <button type="button" onclick="removeField('skills')"><i class="fas fa-minus"></i> Remove Skill</button><br>

            <label for="work_experience">Work Experience:</label>
            <div id="work_experience-container">
                <input type="text" name="work_experience[]" placeholder="Enter work experience"><br>
            </div>
            <button type="button" onclick="addField('work_experience')"><i class="fas fa-plus"></i> Add Work Experience</button>
            <button type="button" onclick="removeField('work_experience')"><i class="fas fa-minus"></i> Remove Work Experience</button><br>


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

    </div>
    <div style="height: 500px;"></div>
</body>


<?php
include 'footer.php';
?>

</html>

<?php
$conn->close();
?>