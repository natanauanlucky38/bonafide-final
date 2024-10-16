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

// Handle form submission for creating a job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_title'])) {
    // Sanitize and validate form inputs
    $job_title = trim($_POST['job_title']);
    $company = trim($_POST['company']);
    $location = trim($_POST['location']);
    $min_salary = trim($_POST['min_salary']);
    $max_salary = trim($_POST['max_salary']);
    $description = trim($_POST['description']); 
    $openings = intval($_POST['openings']);
    $deadline = trim($_POST['deadline']);
    $status = trim($_POST['status']);
    $has_questionnaire = isset($_POST['has_questionnaire']) ? 1 : 0;

    // Basic validation for required fields
    if (empty($job_title) || empty($company) || empty($location) || empty($openings) || empty($deadline) || empty($description) || empty($status)) {
        $errors[] = "Please fill in all required fields.";
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

            // Insert questionnaire if applicable
            if ($has_questionnaire) {
                $questions = $_POST['questions'] ?? [];
                $question_types = $_POST['question_types'] ?? [];
                $dealbreakers = $_POST['dealbreakers'] ?? [];
                $choices = $_POST['choices'] ?? [];
                $correct_answers = $_POST['correct_answers'] ?? [];

                $questionSql = "
                    INSERT INTO questionnaire_template (job_id, question_text, question_type, is_required, is_dealbreaker, choice_a, choice_b, choice_c, choice_d, correct_answer)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ";
                $questionStmt = $conn->prepare($questionSql);

                foreach ($questions as $index => $questionText) {
                    $is_dealbreaker = isset($dealbreakers[$index]) ? 1 : 0;
                    $is_required = 1;  
                    $question_type = $question_types[$index];

                    $choice_a = $choice_b = $choice_c = $choice_d = null;

                    if ($question_type === 'MULTIPLE_CHOICE') {
                        $choice_a = trim($choices[$index]['a'] ?? '');
                        $choice_b = trim($choices[$index]['b'] ?? '');
                        $choice_c = trim($choices[$index]['c'] ?? '');
                        $choice_d = trim($choices[$index]['d'] ?? '');
                    }

                    // Correct answer handling
                    $correct_answer = trim($correct_answers[$index] ?? '');
                    if ($question_type === 'YES_NO') {
                        $correct_answer = strtoupper($correct_answer) === 'YES' ? 'YES' : 'NO';
                    } elseif ($question_type === 'MULTIPLE_CHOICE') {
                        $correct_answer = strtoupper($correct_answer);
                    }

                    // Ensure question text and correct answer are not empty before insertion
                    if (!empty($questionText) && !empty($correct_answer)) {
                        $questionStmt->bind_param('issiiissss', $job_id, $questionText, $question_type, $is_required, $is_dealbreaker, $choice_a, $choice_b, $choice_c, $choice_d, $correct_answer);
                        $questionStmt->execute();
                    }
                }
                $questionStmt->close();
            }

            $successMessage = "Job posting created successfully!";
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
                        <option value="MULTIPLE_CHOICE">Multiple Choice</option>
                    </select>
                    <div id="choices_${questionIndex}" style="display:none;">
                        <div id="multiple_choices_${questionIndex}" style="display:none;">
                            <label>Choices (A-D):</label>
                            <input type="text" name="choices[${questionIndex}][a]" placeholder="Choice A">
                            <input type="text" name="choices[${questionIndex}][b]" placeholder="Choice B">
                            <input type="text" name="choices[${questionIndex}][c]" placeholder="Choice C">
                            <input type="text" name="choices[${questionIndex}][d]" placeholder="Choice D">
                            <label>Correct Answer:</label>
                            <select name="correct_answers[]">
                                <option value="">Select Correct Answer</option>
                                <option value="A">A</option>
                                <option value="B">B</option>
                                <option value="C">C</option>
                                <option value="D">D</option>
                            </select>
                        </div>
                        <div id="yes_no_choices_${questionIndex}" style="display:none;">
                            <label>Correct Answer:</label>
                            <select name="correct_answers[]">
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
            const multipleChoicesDiv = document.getElementById(`multiple_choices_${questionIndex}`);
            const yesNoChoicesDiv = document.getElementById(`yes_no_choices_${questionIndex}`);

            // Hide both choices initially
            choicesDiv.style.display = 'none';
            multipleChoicesDiv.style.display = 'none';
            yesNoChoicesDiv.style.display = 'none';

            if (selectElement.value === 'MULTIPLE_CHOICE') {
                choicesDiv.style.display = 'block';
                multipleChoicesDiv.style.display = 'block'; // Show multiple choice inputs
            } else if (selectElement.value === 'YES_NO') {
                choicesDiv.style.display = 'block';
                yesNoChoicesDiv.style.display = 'block'; // Show yes/no choices
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

        <!-- Include Questionnaire -->
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
