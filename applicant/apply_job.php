<?php
// Make sure session is started and database is connected
session_start();
include '../db.php';

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id']; // Get logged-in user's ID

// Ensure the job_id is provided
if (!isset($_GET['job_id'])) {
    echo "Error: Job ID not found.";
    exit();
}

$job_id = (int)$_GET['job_id']; // Sanitize job_id

// Fetch job details (optional, just to display job details)
$job_sql = "SELECT * FROM job_postings WHERE job_id = $job_id";
$job_result = $conn->query($job_sql);
if ($job_result->num_rows == 0) {
    echo "Job not found.";
    exit();
}
$job = $job_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // File upload
    if (isset($_FILES['resume']) && $_FILES['resume']['error'] == 0) {
        $file_tmp = $_FILES['resume']['tmp_name'];
        $file_name = basename($_FILES['resume']['name']);
        $upload_dir = 'uploads/';
        $resume_path = $upload_dir . $file_name;

        // Move the uploaded file to the correct directory
        if (move_uploaded_file($file_tmp, $resume_path)) {
            $_SESSION['resume_file'] = $resume_path; // Store resume path in session
        } else {
            echo "Failed to upload resume.";
            exit();
        }
    }

    // Save answers to the questionnaire (if any)
    if (isset($_POST['answers'])) {
        $answers = $_POST['answers'];
        $_SESSION['answers'] = $answers; // Store answers in session
    }

    // Redirect to the questionnaire page or directly submit if no questionnaire
    header("Location: questionnaire.php?job_id=$job_id");
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
    <form action="apply_job.php?job_id=<?php echo $job_id; ?>" method="POST" enctype="multipart/form-data">
        <label for="resume">Upload Resume:</label>
        <input type="file" name="resume" id="resume" required><br>

        <!-- Display job-specific questions (if any) -->
        <?php
        $question_sql = "SELECT * FROM questionnaire_template WHERE job_id = $job_id";
        $question_result = $conn->query($question_sql);

        if ($question_result->num_rows > 0): ?>
            <h3>Job Questionnaire</h3>
            <?php while ($question = $question_result->fetch_assoc()): ?>
                <label><?php echo htmlspecialchars($question['question_text']); ?></label><br>
                <input type="text" name="answers[<?php echo $question['question_id']; ?>]" required><br>
            <?php endwhile; ?>
        <?php endif; ?>

        <button type="submit">Submit Application</button>
    </form>
</body>
</html>
