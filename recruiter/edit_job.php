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

// Fetch existing questionnaire questions for the job
$questions = [];
$questionSql = "SELECT * FROM questionnaire_template WHERE job_id = ?";
$questionStmt = $conn->prepare($questionSql);
$questionStmt->bind_param('i', $job_id);
$questionStmt->execute();
$questionResult = $questionStmt->get_result();
while ($row = $questionResult->fetch_assoc()) {
    $questions[] = $row;  // Fetch and store all existing questions
}
$questionStmt->close();

// Set has_questionnaire based on existing questions
$has_questionnaire = !empty($questions) ? 1 : 0;

// Handle form submission for updating the job
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate form inputs
    $job_title = strtoupper(trim($_POST['job_title']));
    $company = strtoupper(trim($_POST['company']));
    $location = strtoupper(trim($_POST['location']));
    $min_salary = trim($_POST['min_salary']);
    $max_salary = trim($_POST['max_salary']);
    $description = trim($_POST['description']);
    $openings = intval($_POST['openings']);
    $deadline = trim($_POST['deadline']);
    $status = trim($_POST['status']);

    // Process the form for updating questions
    foreach ($_POST['question_text'] as $question_id => $question_text) {
        $question_text = trim($question_text);
        $question_type = $_POST['question_type'][$question_id];
        $is_dealbreaker = isset($_POST['dealbreaker'][$question_id]) ? 1 : 0;
        $correct_answer = null;

        if ($question_type === 'YES_NO') {
            $correct_answer = isset($_POST['correct_answer'][$question_id]) ? strtoupper(trim($_POST['correct_answer'][$question_id])) : null;
        }

        // Check if the question_id is numeric, meaning it's an existing question to update
        if (is_numeric($question_id)) {
            // Update existing question
            $updateQuestionSql = "
                UPDATE questionnaire_template 
                SET question_text = ?, is_dealbreaker = ?, question_type = ?, correct_answer = ?
                WHERE question_id = ?
            ";
            $updateQuestionStmt = $conn->prepare($updateQuestionSql);
            $updateQuestionStmt->bind_param('sissi', $question_text, $is_dealbreaker, $question_type, $correct_answer, $question_id);
            $updateQuestionStmt->execute();
            $updateQuestionStmt->close();
        } else {
            // Insert new question if `question_id` is marked as 'new_'
            if (strpos($question_id, 'new_') !== false) {
                $insertQuestionSql = "
                    INSERT INTO questionnaire_template (job_id, question_text, question_type, is_required, is_dealbreaker, correct_answer)
                    VALUES (?, ?, ?, ?, ?, ?)
                ";
                $insertQuestionStmt = $conn->prepare($insertQuestionSql);
                $is_required = 1; // Assuming all questions are required
                $insertQuestionStmt->bind_param('issiis', $job_id, $question_text, $question_type, $is_required, $is_dealbreaker, $correct_answer);
                $insertQuestionStmt->execute();
                $insertQuestionStmt->close();
            }
        }
    }

    // Delete any questions that were not part of the submitted form
    if (isset($_POST['existing_question_ids'])) {
        $existing_question_ids = $_POST['existing_question_ids'];
        $existing_question_ids = implode(',', array_map('intval', $existing_question_ids)); // Safely format IDs
        $deleteSql = "
            DELETE FROM questionnaire_template 
            WHERE job_id = ? AND question_id NOT IN ($existing_question_ids)
        ";
        $deleteStmt = $conn->prepare($deleteSql);
        $deleteStmt->bind_param('i', $job_id);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    // Update job posting to ensure has_questionnaire is set correctly
    $updateJobSql = "
        UPDATE job_postings 
        SET job_title = ?, company = ?, location = ?, min_salary = ?, max_salary = ?, description = ?, openings = ?, deadline = ?, has_questionnaire = ?, status = ? 
        WHERE job_id = ? AND created_by = ?
    ";
    $updateJobStmt = $conn->prepare($updateJobSql);
    $updateJobStmt->bind_param('ssssssssisii', $job_title, $company, $location, $min_salary, $max_salary, $description, $openings, $deadline, $has_questionnaire, $status, $job_id, $_SESSION['user_id']);
    $updateJobStmt->execute();
    $updateJobStmt->close();

    // Redirect to view_job.php after successful update
    header("Location: view_job.php?job_id=" . $job_id);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job Posting</title>
    <link rel="stylesheet" href="styles.css">
    <script>
        let newQuestionCount = 0;

        function addQuestion() {
            const container = document.getElementById('questionnaire-container');
            newQuestionCount++;

            const questionDiv = document.createElement('div');
            questionDiv.innerHTML = `
                <div>
                    <label>Question (New):</label>
                    <input type="text" name="question_text[new_${newQuestionCount}]" required>
                    <label for="question_type">Type:</label>
                    <select name="question_type[new_${newQuestionCount}]" onchange="showChoices(this, 'new_${newQuestionCount}')">
                        <option value="TEXT">Text</option>
                        <option value="YES_NO">Yes/No</option>
                    </select>
                    <div id="choices_new_${newQuestionCount}" style="display:none;">
                        <div id="yes_no_choices_new_${newQuestionCount}" style="display:none;">
                            <label>Correct Answer:</label>
                            <select name="correct_answer[new_${newQuestionCount}]">
                                <option value="">Select Correct Answer</option>
                                <option value="YES">Yes</option>
                                <option value="NO">No</option>
                            </select>
                        </div>
                    </div>
                    <input type="checkbox" name="dealbreaker[new_${newQuestionCount}]">
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
                            <input type="text" name="question_text[<?php echo $question['question_id']; ?>]" value="<?php echo htmlspecialchars($question['question_text']); ?>" required>
                            <label for="question_type">Type:</label>
                            <select name="question_type[<?php echo $question['question_id']; ?>]" onchange="showChoices(this, '<?php echo $question['question_id']; ?>')">
                                <option value="TEXT" <?php echo ($question['question_type'] === 'TEXT') ? 'selected' : ''; ?>>Text</option>
                                <option value="YES_NO" <?php echo ($question['question_type'] === 'YES_NO') ? 'selected' : ''; ?>>Yes/No</option>
                            </select>
                            <div id="choices_<?php echo $question['question_id']; ?>" style="display:block;">
                                <?php if ($question['question_type'] === 'YES_NO'): ?>
                                    <div id="yes_no_choices_<?php echo $question['question_id']; ?>" style="display:block;">
                                        <label>Correct Answer:</label>
                                        <select name="correct_answer[<?php echo $question['question_id']; ?>]">
                                            <option value="">Select Correct Answer</option>
                                            <option value="YES" <?php echo ($question['correct_answer'] === 'YES') ? 'selected' : ''; ?>>Yes</option>
                                            <option value="NO" <?php echo ($question['correct_answer'] === 'NO') ? 'selected' : ''; ?>>No</option>
                                        </select>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <input type="checkbox" name="dealbreaker[<?php echo $question['question_id']; ?>]" <?php echo $question['is_dealbreaker'] ? 'checked' : ''; ?>>
                            <label>Dealbreaker</label>
                            <input type="hidden" name="existing_question_ids[]" value="<?php echo $question['question_id']; ?>">
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