<?php
include '../db.php';  // Include database connection
include 'header.php';
include 'sidebar.php';

// Check if the user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

$errors = [];
$successMessage = "";

// Handle job deletion
if (isset($_GET['delete_job_id'])) {
    $job_id = intval($_GET['delete_job_id']);

    // Begin transaction to ensure atomicity of multiple delete operations
    $conn->begin_transaction();

    try {
        // Delete associated questions before deleting the job posting
        $deleteQuestionsSql = "DELETE FROM questionnaire_template WHERE job_id = ?";
        $deleteQuestionsStmt = $conn->prepare($deleteQuestionsSql);
        $deleteQuestionsStmt->bind_param('i', $job_id);
        $deleteQuestionsStmt->execute();
        $deleteQuestionsStmt->close();

        // Delete associated profile_details
        $deleteProfileDetailsSql = "
            DELETE pd 
            FROM profile_details pd 
            JOIN applications a ON pd.profile_id = a.profile_id 
            WHERE a.job_id = ?";
        $deleteProfileDetailsStmt = $conn->prepare($deleteProfileDetailsSql);
        $deleteProfileDetailsStmt->bind_param('i', $job_id);
        $deleteProfileDetailsStmt->execute();
        $deleteProfileDetailsStmt->close();

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

        // Commit the transaction
        $conn->commit();
    } catch (Exception $e) {
        // Rollback the transaction if any error occurs
        $conn->rollback();
        $errors[] = "Failed to delete job and associated details: " . $e->getMessage();
    }
}

// Search functionality: Check if a search term is provided
$search_term = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

// Fetch job postings with search filter for each status
$statuses = ['DRAFT', 'ACTIVE', 'ARCHIVED'];
$jobs_by_status = [];

foreach ($statuses as $status) {
    $sql = "SELECT job_id, job_title, company, location, min_salary, max_salary, description, openings, status, deadline 
            FROM job_postings 
            WHERE created_by = ? 
            AND status = ? 
            AND (job_title LIKE ? OR company LIKE ?)
            ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isss', $_SESSION['user_id'], $status, $search_term, $search_term);
    $stmt->execute();
    $jobs_by_status[$status] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Job Postings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="recruiter_styles.css">

</head>

<body class="view_job-main-content">

    <div class="content-area">
        <h2>Available Job Postings</h2>

        <!-- Search Bar -->
        <form method="GET" action="view_job.php">
            <input type="text" name="search" placeholder="Search by job title or company" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Search</button>
        </form>

        <div>
            <a href="job_posting.php" class="button">
                <i class="fas fa-plus"></i> Create New Job
            </a>
        </div><br>

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

        <!-- Job Sections by Status -->
        <?php foreach ($jobs_by_status as $status => $jobs): ?>
            <h3 class="<?php echo strtolower($status); ?>-jobs"><?php echo ucfirst(strtolower($status)); ?> Jobs</h3>
            <?php if (empty($jobs)): ?>
                <p>No <?php echo strtolower($status); ?> jobs found.</p>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="job-card">
                        <h4><?php echo htmlspecialchars($job['job_title']); ?> at <?php echo htmlspecialchars($job['company']); ?></h4>
                        <div class="job-details">
                            <div><strong>Location:</strong> <?php echo htmlspecialchars($job['location']); ?></div>
                            <div><strong>Salary Range: ₱ </strong> <?php echo htmlspecialchars($job['min_salary']); ?> - <?php echo htmlspecialchars($job['max_salary']); ?></div>
                            <div><strong>Description:</strong> <?php echo htmlspecialchars($job['description']); ?></div>
                            <div><strong>Openings:</strong> <?php echo htmlspecialchars($job['openings']); ?></div>
                            <div><strong>Status:</strong> <?php echo htmlspecialchars($job['status']); ?></div>
                            <div><strong>Deadline:</strong> <?php echo htmlspecialchars($job['deadline']); ?></div>
                        </div>
                        <div class="job-actions">
                            <a href="edit_job.php?job_id=<?php echo $job['job_id']; ?>">Edit</a>
                            <a href="view_job.php?delete_job_id=<?php echo $job['job_id']; ?>" onclick="return confirm('Are you sure you want to delete this job?')">Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>



</body>
<?php include 'footer.php'; ?>

</html>

<?php
// Close the database connection
$conn->close();
?>