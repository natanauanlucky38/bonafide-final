<?php
// Include necessary files
include '../db.php';  // Database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if job_id is passed in the URL
if (!isset($_GET['job_id']) || empty($_GET['job_id'])) {
    echo "Error: No job specified!";
    exit();
}

$job_id = intval($_GET['job_id']);  // Get the job_id from URL and sanitize

// Fetch job details from the database
$sql = "SELECT * FROM job_postings WHERE job_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $job_id);
$stmt->execute();
$result = $stmt->get_result();

// Check if job exists
if ($result->num_rows === 0) {
    echo "Error: Job not found!";
    exit();
}

$job = $result->fetch_assoc();  // Fetch job details
$stmt->close();

// Fetch questionnaire if available
$questions = [];
if ($job['has_questionnaire']) {
    $questionnaireSql = "SELECT * FROM questionnaire_template WHERE job_id = ?";
    $questionnaireStmt = $conn->prepare($questionnaireSql);
    $questionnaireStmt->bind_param('i', $job_id);
    $questionnaireStmt->execute();
    $questionsResult = $questionnaireStmt->get_result();

    while ($row = $questionsResult->fetch_assoc()) {
        $questions[] = $row;
    }

    $questionnaireStmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Details</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="content-area">
    <h2>Job Details: <?php echo htmlspecialchars($job['job_title']); ?></h2>

    <div>
        <h3>Job Information</h3>
        <p><strong>Company:</strong> <?php echo htmlspecialchars($job['company']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></p>
        <p><strong>Salary Range:</strong> <?php echo htmlspecialchars($job['min_salary']); ?> - <?php echo htmlspecialchars($job['max_salary']); ?></p>
        <p><strong>Job Description:</strong> <?php echo nl2br(htmlspecialchars($job['description'])); ?></p>
        <p><strong>Number of Openings:</strong> <?php echo htmlspecialchars($job['openings']); ?></p>
        <p><strong>Application Deadline:</strong> 
            <?php echo ($job['deadline'] === '0000-00-00') ? 'No deadline' : htmlspecialchars($job['deadline']); ?>
        </p>
        <p><strong>Status:</strong> <?php echo htmlspecialchars($job['status']); ?></p>
        <p><strong>Created At:</strong> <?php echo htmlspecialchars($job['created_at']); ?></p>
    </div>

    <?php if ($job['has_questionnaire'] && !empty($questions)): ?>
        <div>
            <h3>Questionnaire</h3>
            <ul>
                <?php foreach ($questions as $question): ?>
                    <li>
                        <strong>Question:</strong> <?php echo htmlspecialchars($question['question_text']); ?>
                        <?php if ($question['is_dealbreaker']): ?>
                            <span style="color: red;">(Dealbreaker)</span>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
