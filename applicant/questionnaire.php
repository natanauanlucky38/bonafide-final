<?php
session_start();
include '../db.php';

if (!isset($_SESSION['email'])) {
    header('Location: login.php');
    exit();
}

// Check if job_id is provided
if (!isset($_SESSION['job_id'])) {
    echo "Error: Job ID not found.";
    exit();
}

$job_id = $_SESSION['job_id'];
$user_id = $_SESSION['user_id'];

// Fetch profile_id for the logged-in user
$profile_sql = "SELECT profile_id FROM profiles WHERE user_id = '$user_id'";
$profile_result = $conn->query($profile_sql);

if ($profile_result->num_rows > 0) {
    $profile_row = $profile_result->fetch_assoc();
    $profile_id = $profile_row['profile_id'];
} else {
    echo "Error: Profile not found.";
    exit();
}

// Fetch the questionnaire for the job
$questionnaire_sql = "SELECT * FROM questionnaire_template WHERE job_id = '$job_id'";
$questionnaire_result = $conn->query($questionnaire_sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Apply for Job: <?php echo $job_id; ?></title>
</head>
<body>
    <h2>Apply for Job: <?php echo $job_id; ?></h2>

    <form method="POST" action="submit_application.php" enctype="multipart/form-data">
        <div>
            <label>Upload Resume:</label>
            <input type="file" name="resume" required>
        </div>

        <h3>Job Questionnaire</h3>

        <?php if ($questionnaire_result->num_rows > 0): ?>
            <?php while ($question = $questionnaire_result->fetch_assoc()): ?>
                <div class="form-group">
                    <label><?php echo htmlspecialchars($question['question_text']); ?></label>
                    
                    <!-- Show different inputs based on question type -->
                    <?php if ($question['question_type'] === 'TEXT'): ?>
                        <input type="text" name="answers[<?php echo $question['question_id']; ?>]" required>
                    
                    <?php elseif ($question['question_type'] === 'YES_NO'): ?>
                        <select name="answers[<?php echo $question['question_id']; ?>]" required>
                            <option value="YES">Yes</option>
                            <option value="NO">No</option>
                        </select>
                    
                    <?php elseif ($question['question_type'] === 'MULTIPLE_CHOICE'): ?>
                        <div>
                            <label>Choices:</label><br>
                            <label>A: <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="A" required> <?php echo htmlspecialchars($question['choice_a']); ?></label><br>
                            <label>B: <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="B" required> <?php echo htmlspecialchars($question['choice_b']); ?></label><br>
                            <label>C: <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="C" required> <?php echo htmlspecialchars($question['choice_c']); ?></label><br>
                            <label>D: <input type="radio" name="answers[<?php echo $question['question_id']; ?>]" value="D" required> <?php echo htmlspecialchars($question['choice_d']); ?></label><br>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No questionnaire available for this job.</p>
        <?php endif; ?>

        <button type="submit">Submit Application</button>
    </form>
</body>
</html>

<?php
$conn->close();
?>
