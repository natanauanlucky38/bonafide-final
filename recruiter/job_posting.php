<?php
// Include necessary files
include '../db.php';  // Include database connection

// Check if the user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: login.php');
    exit();
}

$errors = [];
$successMessage = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form inputs
    $job_title = trim($_POST['job_title']);
    $company = trim($_POST['company']);
    $location = trim($_POST['location']);
    $min_salary = trim($_POST['min_salary']);
    $max_salary = trim($_POST['max_salary']);
    $description = trim($_POST['description']);
    $openings = intval($_POST['openings']);
    $deadline = trim($_POST['deadline']);
    $has_questionnaire = isset($_POST['has_questionnaire']) ? 1 : 0;

    // Questionnaire data
    $questions = $_POST['questions'] ?? [];
    $dealbreakers = $_POST['dealbreakers'] ?? [];

    // Basic validation
    if (empty($job_title) || empty($company) || empty($location) || empty($openings) || empty($deadline)) {
        $errors[] = "Please fill in all required fields.";
    }

    // If no errors, insert into the database
    if (empty($errors)) {
        $created_by = $_SESSION['user_id']; // Recruiter's user ID

        // Insert job posting into the database
        $sql = "
            INSERT INTO job_postings (job_title, company, location, min_salary, max_salary, description, openings, created_by, deadline, has_questionnaire)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sssssisiis', $job_title, $company, $location, $min_salary, $max_salary, $description, $openings, $created_by, $deadline, $has_questionnaire);

        if ($stmt->execute()) {
            $job_id = $stmt->insert_id;  // Get the ID of the newly created job posting

            // If the job has a questionnaire, insert the questions
            if ($has_questionnaire && !empty($questions)) {
                $questionSql = "
                    INSERT INTO questionnaire_template (job_id, question_text, is_required, is_dealbreaker)
                    VALUES (?, ?, ?, ?)
                ";
                $questionStmt = $conn->prepare($questionSql);

                foreach ($questions as $index => $questionText) {
                    $is_dealbreaker = isset($dealbreakers[$index]) ? 1 : 0;
                    $is_required = 1;  // Assuming all questions are required

                    // Now bind the parameters
                    $questionStmt->bind_param('isii', $job_id, $questionText, $is_required, $is_dealbreaker);
                    $questionStmt->execute();
                }
                $questionStmt->close();
            }

            $successMessage = "Job posting created successfully with the questionnaire!";
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
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <script>
        // JavaScript function to add more questionnaire fields dynamically
        function addQuestion() {
            const container = document.getElementById('questionnaire-container');
            const questionIndex = container.children.length;  // Number of questions added

            const questionDiv = document.createElement('div');
            questionDiv.innerHTML = `
                <div>
                    <label>Question ${questionIndex + 1}:</label>
                    <input type="text" name="questions[]" required>
                    <input type="checkbox" name="dealbreakers[${questionIndex}]">
                    <label>Dealbreaker</label>
                    <button type="button" onclick="removeQuestion(this)">Remove</button>
                </div>
            `;

            container.appendChild(questionDiv);
        }

        // JavaScript function to remove a question field
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

    <!-- Display errors -->
    <?php if (!empty($errors)): ?>
        <div class="error-messages">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- Display success message -->
    <?php if (!empty($successMessage)): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($successMessage); ?>
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
            <textarea id="description" name="description"></textarea>
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
            <input type="checkbox" id="has_questionnaire" name="has_questionnaire" onchange="document.getElementById('questionnaire-section').style.display = this.checked ? 'block' : 'none';">
            <label for="has_questionnaire">Include Questionnaire</label>
        </div>

        <!-- Questionnaire Section -->
        <div id="questionnaire-section" style="display:none;">
            <h3>Questionnaire</h3>
            <div id="questionnaire-container">
                <!-- Dynamic questions will be added here -->
            </div>
            <button type="button" onclick="addQuestion()">Add Question</button>
        </div>

        <div>
            <button type="submit">Create Job Posting</button>
        </div>
    </form>

</div>

<?php include 'footer.php'; ?>

</body>
</html>
