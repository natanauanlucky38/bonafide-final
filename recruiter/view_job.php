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

// Handle job deletion
if (isset($_GET['delete_job_id'])) {
    $job_id = intval($_GET['delete_job_id']);

    // Delete associated questions before deleting the job posting
    $deleteQuestionsSql = "DELETE FROM questionnaire_template WHERE job_id = ?";
    $deleteQuestionsStmt = $conn->prepare($deleteQuestionsSql);
    $deleteQuestionsStmt->bind_param('i', $job_id);
    $deleteQuestionsStmt->execute();
    $deleteQuestionsStmt->close();

    // Delete job posting
    $deleteJobSql = "DELETE FROM job_postings WHERE job_id = ? AND created_by = ?";
    $deleteJobStmt = $conn->prepare($deleteJobSql);
    $deleteJobStmt->bind_param('ii', $job_id, $_SESSION['user_id']);

    if ($deleteJobStmt->execute()) {
        $successMessage = "Job deleted successfully!";
    } else {
        $errors[] = "Failed to delete job. Please try again.";
    }

    $deleteJobStmt->close();
}

// Fetch all job postings to display in the job list
$sql = "SELECT job_id, job_title, company, location, min_salary, max_salary, description, openings, status, deadline FROM job_postings WHERE created_by = ? ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$jobs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Job Postings</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="content-area">
    <h2>Available Job Postings</h2>

    <div>
        <a href="job_posting.php" class="button">Create New Job</a>
    </div>
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

    <?php if (empty($jobs)): ?>
        <p>No jobs found. Create a new job to get started.</p>
    <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Salary Range</th>
                    <th>Description</th>
                    <th>Openings</th>
                    <th>Status</th>
                    <th>Application Deadline</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($job['job_title']); ?></td>
                        <td><?php echo htmlspecialchars($job['company']); ?></td>
                        <td><?php echo htmlspecialchars($job['location']); ?></td>
                        <td><?php echo htmlspecialchars($job['min_salary']); ?> - <?php echo htmlspecialchars($job['max_salary']); ?></td>
                        <td><?php echo htmlspecialchars($job['description']); ?></td>
                        <td><?php echo htmlspecialchars($job['openings']); ?></td>
                        <td><?php echo htmlspecialchars($job['status']); ?></td>
                        <td><?php echo htmlspecialchars($job['deadline']); ?></td>
                        <td>
                            <a href="edit_job.php?job_id=<?php echo $job['job_id']; ?>">Edit</a> |
                            <a href="view_job.php?delete_job_id=<?php echo $job['job_id']; ?>" onclick="return confirm('Are you sure you want to delete this job?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
