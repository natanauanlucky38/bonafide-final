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

// Check if `job_id` is provided in the URL
if (!isset($_GET['job_id'])) {
    echo "No job ID provided!";
    exit();
}

$job_id = intval($_GET['job_id']);  // Sanitize job_id

// Fetch the job details from the database
$sql = "SELECT * FROM job_postings WHERE job_id = ? AND created_by = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $job_id, $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$job = $result->fetch_assoc();

if (!$job) {
    echo "Job not found or you do not have permission to edit this job.";
    exit();
}

$stmt->close();

// Fetch existing questionnaire for the job
$questions = [];
$questionSql = "SELECT * FROM questionnaire_template WHERE job_id = ?";
$questionStmt = $conn->prepare($questionSql);
$questionStmt->bind_param('i', $job_id);
$questionStmt->execute();
$questionResult = $questionStmt->get_result();
while ($row = $questionResult->fetch_assoc()) {
    $questions[] = $row;
}
$questionStmt->close();

// Set has_questionnaire based on existing questions
$has_questionnaire = !empty($questions) ? 1 : 0;

// Handle form submission for updating the job
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form inputs
    $job_title = strtoupper(trim($_POST['job_title'])); // Convert to uppercase
    $company = strtoupper(trim($_POST['company'])); // Convert to uppercase
    $location = strtoupper(trim($_POST['location'])); // Convert to uppercase
    $min_salary = trim($_POST['min_salary']);
    $max_salary = trim($_POST['max_salary']);
    $description = trim($_POST['description']);
    $openings = intval($_POST['openings']);
    $deadline = trim($_POST['deadline']);
    $status = trim($_POST['status']);
    $postedQuestions = $_POST['questions'] ?? [];
    $dealbreakers = $_POST['dealbreakers'] ?? [];
    $question_types = $_POST['question_types'] ?? [];
    $correct_answers = $_POST['correct_answers'] ?? [];
    $choices = $_POST['choices'] ?? [];

    // Check if at least one question is being submitted
    $has_questionnaire = !empty($postedQuestions) ? 1 : 0;

    // Basic validation for required fields
    if (empty($job_title) || empty($company) || empty($location) || empty($openings) || empty($deadline) || empty($description) || empty($status)) {
        $errors[] = "Please fill in all required fields.";
    }

    // Validate deadline to ensure it is not a past date
    if (strtotime($deadline) < time()) {
        $errors[] = "The application deadline cannot be a past date.";
    }

    // Validate salary range
    if (!empty($min_salary) && !empty($max_salary) && $min_salary > $max_salary) {
        $errors[] = "Minimum salary cannot be greater than maximum salary.";
    }

    // If no errors, update the database
    if (empty($errors)) {
        // Update job posting
        $sql = "
            UPDATE job_postings 
            SET job_title = ?, company = ?, location = ?, min_salary = ?, max_salary = ?, description = ?, openings = ?, deadline = ?, has_questionnaire = ?, status = ? 
            WHERE job_id = ? AND created_by = ?
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssssisii', $job_title, $company, $location, $min_salary, $max_salary, $description, $openings, $deadline, $has_questionnaire, $status, $job_id, $_SESSION['user_id']);

        if ($stmt->execute()) {
            // Update existing questions
            $existingQuestionIds = array_column($questions, 'question_id'); // Get IDs of existing questions
            $postedQuestionIds = $_POST['question_ids'] ?? []; // Get IDs of posted questions

            // Loop through posted questions and update or insert
            foreach ($postedQuestions as $index => $questionText) {
                $is_dealbreaker = isset($dealbreakers[$index]) ? 1 : 0;
                $question_type = $question_types[$index];
                $correct_answer = strtoupper(trim($correct_answers[$index] ?? ''));

                // Prepare choice values for multiple choice questions
                $choice_a = $choice_b = $choice_c = $choice_d = null;
                if ($question_type === 'MULTIPLE_CHOICE') {
                    $choice_a = trim($choices[$index]['a'] ?? '');
                    $choice_b = trim($choices[$index]['b'] ?? '');
                    $choice_c = trim($choices[$index]['c'] ?? '');
                    $choice_d = trim($choices[$index]['d'] ?? '');
                }

                // Check if question exists for updating
                if (isset($postedQuestionIds[$index]) && !empty($postedQuestionIds[$index])) {
                    // Update existing question
                    $updateQuestionSql = "
                        UPDATE questionnaire_template 
                        SET question_text = ?, is_dealbreaker = ?, question_type = ?, correct_answer = ?, choice_a = ?, choice_b = ?, choice_c = ?, choice_d = ?
                        WHERE question_id = ?
                    ";
                    $updateQuestionStmt = $conn->prepare($updateQuestionSql);
                    $updateQuestionStmt->bind_param('sissssssi', $questionText, $is_dealbreaker, $question_type, $correct_answer, $choice_a, $choice_b, $choice_c, $choice_d, $postedQuestionIds[$index]);
                    $updateQuestionStmt->execute();
                    $updateQuestionStmt->close();
                } else {
                    // Insert new question
                    $insertQuestionSql = "
                        INSERT INTO questionnaire_template (job_id, question_text, question_type, is_required, is_dealbreaker, correct_answer, choice_a, choice_b, choice_c, choice_d)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ";
                    $insertQuestionStmt = $conn->prepare($insertQuestionSql);
                    $is_required = 1; // Assuming all questions are required
                    $insertQuestionStmt->bind_param('issiiissss', $job_id, $questionText, $question_type, $is_required, $is_dealbreaker, $correct_answer, $choice_a, $choice_b, $choice_c, $choice_d);
                    $insertQuestionStmt->execute();
                    $insertQuestionStmt->close();
                }
            }

            // Handle deletion of questions that are no longer in the posted questions
            foreach ($existingQuestionIds as $existingQuestionId) {
                if (!in_array($existingQuestionId, $postedQuestionIds)) {
                    // Delete question if it is not in posted questions
                    $deleteQuestionSql = "
                        DELETE FROM questionnaire_template 
                        WHERE question_id = ?
                    ";
                    $deleteQuestionStmt = $conn->prepare($deleteQuestionSql);
                    $deleteQuestionStmt->bind_param('i', $existingQuestionId);
                    $deleteQuestionStmt->execute();
                    $deleteQuestionStmt->close();
                }
            }

            // Update the job posting again to ensure has_questionnaire is set correctly
            $updateJobSql = "
                UPDATE job_postings 
                SET has_questionnaire = ? 
                WHERE job_id = ? AND created_by = ?
            ";
            $updateJobStmt = $conn->prepare($updateJobSql);
            $updateJobStmt->bind_param('iii', $has_questionnaire, $job_id, $_SESSION['user_id']);
            $updateJobStmt->execute();
            $updateJobStmt->close();

            // Redirect to view_job.php after successful update
            header("Location: view_job.php?job_id=" . $job_id); // Redirect to view_job.php after successful update
            exit(); // Ensure no further code is executed after redirection
        } else {
            $errors[] = "Failed to update job. Please try again.";
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
    <title>Edit Job Posting</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <script>
        function addQuestion() {
            const container = document.getElementById('questionnaire-container');
            const questionIndex = container.children.length;  // Number of questions added

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

            // Show the appropriate choices based on the selected type
            if (selectElement.value === 'MULTIPLE_CHOICE') {
                choicesDiv.style.display = 'block';
                multipleChoicesDiv.style.display = 'block'; // Show multiple choice inputs
            } else if (selectElement.value === 'YES_NO') {
                choicesDiv.style.display = 'block';
                yesNoChoicesDiv.style.display = 'block'; // Show yes/no choices
            } else if (selectElement.value === 'TEXT') {
                choicesDiv.style.display = 'none'; // No choices needed for text input
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
    <h2>Edit Job Posting</h2>

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

    <!-- Edit Job Form -->
    <form method="POST" action="edit_job.php?job_id=<?php echo $job_id; ?>">
        <div>
            <label for="job_title">Job Title:</label>
            <input type="text" id="job_title" name="job_title" value="<?php echo htmlspecialchars($job['job_title']); ?>" required>
        </div>
        <div>
            <label for="company">Company:</label>
            <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($job['company']); ?>" required>
        </div>
        <div>
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($job['location']); ?>" required>
        </div>
        <div>
            <label for="min_salary">Minimum Salary:</label>
            <input type="text" id="min_salary" name="min_salary" value="<?php echo htmlspecialchars($job['min_salary']); ?>">
        </div>
        <div>
            <label for="max_salary">Maximum Salary:</label>
            <input type="text" id="max_salary" name="max_salary" value="<?php echo htmlspecialchars($job['max_salary']); ?>">
        </div>
        <div>
            <label for="description">Job Description:</label>
            <textarea id="description" name="description" required><?php echo htmlspecialchars($job['description']); ?></textarea>
        </div>
        <div>
            <label for="openings">Number of Openings:</label>
            <input type="number" id="openings" name="openings" value="<?php echo htmlspecialchars($job['openings']); ?>" required>
        </div>
        <div>
            <label for="deadline">Application Deadline:</label>
            <input type="date" id="deadline" name="deadline" value="<?php echo htmlspecialchars($job['deadline']); ?>" min="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div>
            <label for="status">Status:</label>
            <select id="status" name="status" required>
                <option value="DRAFT" <?php echo ($job['status'] === 'DRAFT') ? 'selected' : ''; ?>>Draft</option>
                <option value="ACTIVE" <?php echo ($job['status'] === 'ACTIVE') ? 'selected' : ''; ?>>Active</option>
                <option value="ARCHIVED" <?php echo ($job['status'] === 'ARCHIVED') ? 'selected' : ''; ?>>Archived</option>
            </select>
        </div>

        <!-- Questionnaire Section -->
        <div id="questionnaire-section">
            <h3>Questionnaire</h3>
            <div id="questionnaire-container">
                <?php foreach ($questions as $index => $question): ?>
                    <div>
                        <label>Question <?php echo $index + 1; ?>:</label>
                        <input type="text" name="questions[]" value="<?php echo htmlspecialchars($question['question_text']); ?>" required>
                        <label for="question_type">Type:</label>
                        <select name="question_types[]" onchange="showChoices(this, <?php echo $index; ?>)">
                            <option value="TEXT" <?php echo ($question['question_type'] === 'TEXT') ? 'selected' : ''; ?>>Text</option>
                            <option value="YES_NO" <?php echo ($question['question_type'] === 'YES_NO') ? 'selected' : ''; ?>>Yes/No</option>
                            <option value="MULTIPLE_CHOICE" <?php echo ($question['question_type'] === 'MULTIPLE_CHOICE') ? 'selected' : ''; ?>>Multiple Choice</option>
                        </select>
                        <div id="choices_<?php echo $index; ?>" style="display:block;">
                            <?php if ($question['question_type'] === 'MULTIPLE_CHOICE'): ?>
                                <div id="multiple_choices_<?php echo $index; ?>" style="display:block;">
                                    <label>Choices (A-D):</label>
                                    <input type="text" name="choices[<?php echo $index; ?>][a]" placeholder="Choice A" value="<?php echo htmlspecialchars($question['choice_a']); ?>">
                                    <input type="text" name="choices[<?php echo $index; ?>][b]" placeholder="Choice B" value="<?php echo htmlspecialchars($question['choice_b']); ?>">
                                    <input type="text" name="choices[<?php echo $index; ?>][c]" placeholder="Choice C" value="<?php echo htmlspecialchars($question['choice_c']); ?>">
                                    <input type="text" name="choices[<?php echo $index; ?>][d]" placeholder="Choice D" value="<?php echo htmlspecialchars($question['choice_d']); ?>">
                                    <label>Correct Answer:</label>
                                    <select name="correct_answers[]">
                                        <option value="">Select Correct Answer</option>
                                        <option value="A" <?php echo ($question['correct_answer'] === 'A') ? 'selected' : ''; ?>>A</option>
                                        <option value="B" <?php echo ($question['correct_answer'] === 'B') ? 'selected' : ''; ?>>B</option>
                                        <option value="C" <?php echo ($question['correct_answer'] === 'C') ? 'selected' : ''; ?>>C</option>
                                        <option value="D" <?php echo ($question['correct_answer'] === 'D') ? 'selected' : ''; ?>>D</option>
                                    </select>
                                </div>
                            <?php elseif ($question['question_type'] === 'YES_NO'): ?>
                                <div id="yes_no_choices_<?php echo $index; ?>" style="display:block;">
                                    <label>Correct Answer:</label>
                                    <select name="correct_answers[]">
                                        <option value="">Select Correct Answer</option>
                                        <option value="YES" <?php echo ($question['correct_answer'] === 'YES') ? 'selected' : ''; ?>>Yes</option>
                                        <option value="NO" <?php echo ($question['correct_answer'] === 'NO') ? 'selected' : ''; ?>>No</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>
                        <input type="hidden" name="question_ids[]" value="<?php echo $question['question_id']; ?>">
                        <input type="checkbox" name="dealbreakers[<?php echo $index; ?>]" <?php echo $question['is_dealbreaker'] ? 'checked' : ''; ?>>
                        <label>Dealbreaker</label>
                        <button type="button" onclick="removeQuestion(this)">Remove</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addQuestion()">Add Question</button>
        </div>

        <div>
            <button type="submit">Update Job Posting</button>
        </div>
    </form>

</div>

<?php include 'footer.php'; ?>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
