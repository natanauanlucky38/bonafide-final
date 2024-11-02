<?php
include '../db.php';  // Include database connection
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
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <style>
        /* General Reset */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f5f5f5;
            color: #333;
        }

        /* Content Area Styling */
        .content-area {
            width: 90%;
            max-width: 1000px;
            margin: 2em auto;
            background: #fff;
            padding: 2em;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        h2 {
            font-size: 1.8em;
            color: #333;
            margin-bottom: 0.5em;
            text-align: center;
        }

        /* Search Bar */
        form {
            display: flex;
            justify-content: center;
            margin-bottom: 1.5em;
        }

        form input[type="text"] {
            width: 300px;
            padding: 0.5em;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        form button {
            padding: 0.5em 1em;
            border: none;
            background-color: #007bff;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 0.5em;
            transition: background-color 0.3s ease;
        }

        form button:hover {
            background-color: #0056b3;
        }

        /* Success and Error Messages */
        .success-message,
        .error-messages {
            text-align: center;
            margin: 1em 0;
            padding: 1em;
            border-radius: 4px;
        }

        .success-message {
            background-color: #d4edda;
            color: #155724;
        }

        .error-messages {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Job Sections by Status */
        h3 {
            color: #555;
            font-size: 1.5em;
            margin: 1em 0 0.5em;
        }

        .draft-jobs {
            color: #6c757d;
        }

        .active-jobs {
            color: #28a745;
        }

        .archived-jobs {
            color: #6c757d;
        }

        /* Job Cards */
        .job-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 1em;
            margin-bottom: 1em;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: #fdfdfd;
        }

        .job-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .job-card h4 {
            font-size: 1.3em;
            color: #333;
            margin-bottom: 0.5em;
        }

        .job-details {
            display: flex;
            flex-wrap: wrap;
            font-size: 0.9em;
            color: #555;
        }

        .job-details div {
            flex: 1 1 50%;
            margin: 0.5em 0;
        }

        .job-actions {
            margin-top: 1em;
        }

        .job-actions a {
            padding: 0.4em 0.8em;
            text-decoration: none;
            color: #fff;
            background-color: #007bff;
            border-radius: 4px;
            transition: background-color 0.3s ease;
            margin-right: 0.5em;
            font-size: 0.9em;
        }

        .job-actions a:hover {
            background-color: #0056b3;
        }

        /* Create Job Button */
        .button {
            display: inline-block;
            padding: 0.6em 1.2em;
            background-color: #28a745;
            color: #fff;
            text-decoration: none;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        .button:hover {
            background-color: #218838;
        }

        /* Table Styles for Card Layouts */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1em;
        }

        th,
        td {
            padding: 0.6em;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f7f7f7;
            font-weight: bold;
            color: #333;
        }

        td:last-child {
            text-align: right;
        }
    </style>
</head>

<body>

    <?php include 'header.php'; ?>

    <div class="content-area">
        <h2>Available Job Postings</h2>

        <!-- Search Bar -->
        <form method="GET" action="view_job.php">
            <input type="text" name="search" placeholder="Search by job title or company" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
            <button type="submit">Search</button>
        </form>

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

    <?php include 'footer.php'; ?>

</body>

</html>

<?php
// Close the database connection
$conn->close();
?>