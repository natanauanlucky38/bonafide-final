<?php
// Include necessary files
include '../db.php';  // Include database connection

// Check if the user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

$errors = [];
$successMessage = "";

// Handle form submission for creating a job posting and requirements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_title'])) {
    // Sanitize and validate form inputs
    $job_title = strtoupper(trim($_POST['job_title']));  // Convert to uppercase
    $company = strtoupper(trim($_POST['company']));      // Convert to uppercase
    $location = strtoupper(trim($_POST['location']));    // Convert to uppercase
    $min_salary = trim($_POST['min_salary']);
    $max_salary = trim($_POST['max_salary']);
    $description = trim($_POST['description']);
    $openings = intval($_POST['openings']);
    $deadline = trim($_POST['deadline']);
    $status = trim($_POST['status']);
    $has_questionnaire = isset($_POST['has_questionnaire']) ? 1 : 0;
    $requirement_names = $_POST['requirement_names'] ?? []; // Capture requirements
    $questions = $_POST['questions'] ?? [];
    $question_types = $_POST['question_types'] ?? [];
    $dealbreakers = $_POST['dealbreakers'] ?? [];
    $correct_answers = $_POST['correct_answers'] ?? [];

    // Basic validation for required fields
    if (empty($job_title) || empty($company) || empty($location) || empty($openings) || empty($deadline) || empty($description) || empty($status)) {
        $errors[] = "Please fill in all required fields.";
    }

    // Validate salary fields and remove any formatting (e.g., "₱") before storing
    $min_salary = str_replace('₱', '', $min_salary);
    $max_salary = str_replace('₱', '', $max_salary);

    if (!is_numeric($min_salary) || !is_numeric($max_salary)) {
        $errors[] = "Please enter valid numeric values for salary.";
    } elseif (floatval($min_salary) > floatval($max_salary)) {
        $errors[] = "Minimum salary cannot be greater than maximum salary.";
    }

    // Validate deadline
    if (!preg_match('/\d{4}-\d{2}-\d{2}/', $deadline)) {
        $errors[] = "Invalid date format for deadline.";
    }

    // Insert into the database if no errors
    if (empty($errors)) {
        $created_by = $_SESSION['user_id'];

        // Insert job posting into the database
        $sql = "
            INSERT INTO job_postings (job_title, company, location, min_salary, max_salary, description, openings, created_by, deadline, has_questionnaire, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssssisss', $job_title, $company, $location, $min_salary, $max_salary, $description, $openings, $created_by, $deadline, $has_questionnaire, $status);

        if ($stmt->execute()) {
            $job_id = $stmt->insert_id;

            // Insert requirements into the requirements table
            $requirementSql = "
                INSERT INTO requirement (job_id, requirement)
                VALUES (?, ?)
            ";
            $requirementStmt = $conn->prepare($requirementSql);

            foreach ($requirement_names as $requirement_name) {
                $requirement_name = strtoupper($requirement_name);
                $requirementStmt->bind_param('is', $job_id, $requirement_name);
                if (!$requirementStmt->execute()) {
                    $errors[] = "Error inserting requirement: " . $requirementStmt->error;
                }
            }
            $requirementStmt->close();


            // Insert questionnaire if applicable
            if ($has_questionnaire && !empty($questions)) {
                $questionSql = "
                    INSERT INTO questionnaire_template (job_id, question_text, question_type, is_required, is_dealbreaker, correct_answer)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                $questionStmt = $conn->prepare($questionSql);

                foreach ($questions as $index => $questionText) {
                    $is_dealbreaker = isset($dealbreakers[$index]) ? 1 : 0;
                    $is_required = 1;  // Assuming all questions are required
                    $question_type = $question_types[$index];
                    $correct_answer = $question_type === 'YES_NO' ? strtoupper(trim($correct_answers[$index] ?? '')) : null;

                    // Ensure correct answer is "YES" or "NO" for YES_NO type
                    if ($question_type === 'YES_NO' && ($correct_answer !== 'YES' && $correct_answer !== 'NO')) {
                        $correct_answer = null;
                    }

                    if (!empty($questionText)) {
                        $questionStmt->bind_param(
                            'issiis',
                            $job_id,
                            $questionText,
                            $question_type,
                            $is_required,
                            $is_dealbreaker,
                            $correct_answer
                        );
                        if (!$questionStmt->execute()) {
                            $errors[] = "Error inserting question: " . $questionStmt->error;
                        }
                    }
                }
                $questionStmt->close();
            }

            // Insert into tbl_job_metrics
            $metricSql = "
                INSERT INTO tbl_job_metrics (job_id, total_applicants, interviewed_applicants, offered_applicants, successful_placements, referral_applicants, social_media_applicants, career_site_applicants, withdrawn_applicants)
                VALUES (?, 0, 0, 0, 0, 0, 0, 0, 0)
            ";
            $metricStmt = $conn->prepare($metricSql);
            $metricStmt->bind_param('i', $job_id);
            $metricStmt->execute();
            $metricStmt->close();

            // Insert initial pipeline stage data into tbl_pipeline_stage
            $pipelineSql = "
                INSERT INTO tbl_pipeline_stage (application_id, applied_at)
                VALUES (NULL, NOW())
            ";
            $pipelineStmt = $conn->prepare($pipelineSql);
            $pipelineStmt->execute();
            $pipelineStmt->close();

            $successMessage = "Job posting, requirements, and questionnaire created successfully!";
            header("Location: job_posting.php");  // Refresh to reset form
            exit();
        } else {
            $errors[] = "Failed to create job posting. Please try again.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Job Posting</title>
    <link rel="stylesheet" href="recruiter_styles.css"> <!-- Link to your CSS file -->
    <script>
        function addRequirement() {
            const container = document.getElementById('requirements-container');
            const requirementDiv = document.createElement('div');
            requirementDiv.innerHTML = `
                <input type="text" name="requirement_names[]" placeholder="Enter requirement" required>
                <button type="button" onclick="removeRequirement(this)">Remove</button>
            `;
            container.appendChild(requirementDiv);
        }

        function removeRequirement(button) {
            button.parentElement.remove();
        }

        function addQuestion() {
            const container = document.getElementById('questionnaire-container');
            const questionIndex = container.children.length;

            const questionDiv = document.createElement('div');
            questionDiv.innerHTML = `
                <div>
                    <label>Question ${questionIndex + 1}:</label>
                    <input type="text" name="questions[]" required>
                    <label for="question_type">Type:</label>
                    <select name="question_types[]" onchange="showChoices(this, ${questionIndex})">
                        <option value="TEXT">Text</option>
                        <option value="YES_NO">Yes/No</option>
                    </select>
                    <div id="choices_${questionIndex}" style="display:none;">
                        <div id="yes_no_choices_${questionIndex}" style="display:none;">
                            <label>Correct Answer:</label>
                            <select name="correct_answers[${questionIndex}]">
                                <option value="">Select Correct Answer</option>
                                <option value="YES">Yes</option>
                                <option value="NO">No</option>
                            </select>
                        </div>
                    </div>
                    <input type="checkbox" name="dealbreakers[${questionIndex}]">
                    <label>Dealbreaker</label>
                    <button type="button" onclick="removeQuestion(this)">Remove</button>
                </div>
            `;

            container.appendChild(questionDiv);
        }

        function showChoices(selectElement, questionIndex) {
            const choicesDiv = document.getElementById(`choices_${questionIndex}`);
            const yesNoChoicesDiv = document.getElementById(`yes_no_choices_${questionIndex}`);

            choicesDiv.style.display = 'none';
            yesNoChoicesDiv.style.display = 'none';

            if (selectElement.value === 'YES_NO') {
                choicesDiv.style.display = 'block';
                yesNoChoicesDiv.style.display = 'block';
            }
        }

        function removeQuestion(button) {
            const questionDiv = button.parentElement;
            questionDiv.remove();
        }
    </script>
</head>

<body>

    <?php include 'header.php'; ?>
    <?php include 'sidebar.php'; ?>

    <div class="content-area">
        <h2>Create Job Posting</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            <div class="success-message">
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
        <?php endif; ?>

        <!-- Job Posting Form -->
        <form method="POST" action="job_posting.php">
            <div>
                <label for="job_title">Job Title:</label>
                <input type="text" id="job_title" name="job_title" required>
            </div>
            <div>
                <label for="company">Company:</label>
                <input type="text" id="company" name="company" required>
            </div>
            <div>
                <label for="location">Location:</label>
                <input type="text" id="location" name="location" required>
            </div>
            <div>
                <label for="min_salary">Minimum Salary:</label>
                <input type="text" id="min_salary" name="min_salary">
            </div>
            <div>
                <label for="max_salary">Maximum Salary:</label>
                <input type="text" id="max_salary" name="max_salary">
            </div>
            <div>
                <label for="description">Job Description:</label>
                <textarea id="description" name="description" required></textarea>
            </div>
            <div>
                <label for="openings">Number of Openings:</label>
                <input type="number" id="openings" name="openings" required>
            </div>
            <div>
                <label for="deadline">Application Deadline:</label>
                <input type="date" id="deadline" name="deadline" required>
            </div>
            <div>
                <label for="status">Status:</label>
                <select id="status" name="status" required>
                    <option value="DRAFT">Draft</option>
                    <option value="ACTIVE">Active</option>
                    <option value="ARCHIVED">Archived</option>
                </select>
            </div>

            <div>
                <input type="checkbox" id="has_questionnaire" name="has_questionnaire" onchange="document.getElementById('questionnaire-section').style.display = this.checked ? 'block' : 'none';">
                <label for="has_questionnaire">Include Questionnaire</label>
            </div>

            <!-- Questionnaire Section -->
            <div id="questionnaire-section" style="display:none;">
                <h3>Questionnaire</h3>
                <div id="questionnaire-container">
                </div>
                <button type="button" onclick="addQuestion()">Add Question</button>
            </div>

            <!-- Requirement Form -->
            <h3>Add Requirements</h3>
            <div id="requirements-container">
                <input type="text" name="requirement_names[]" placeholder="Enter requirement" required>
            </div>
            <button type="button" onclick="addRequirement()">Add Another Requirement</button>

            <div>
                <button type="submit">Create Job Posting</button>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>

</body>

</html>

<?php
$conn->close();
?>