<?php
include '../db.php';  // Database connection
include 'sidebar.php';

// Ensure the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Fetch active job postings excluding the ones the user has been rejected for
$sql = "
    SELECT jp.* 
    FROM job_postings jp
    LEFT JOIN applications a ON jp.job_id = a.job_id 
    AND a.profile_id = (SELECT profile_id FROM profiles WHERE user_id = ?)
    WHERE (a.application_status != 'REJECTED' OR a.application_status IS NULL)
    AND jp.status = 'ACTIVE'
    ORDER BY jp.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing job postings query: " . $conn->error);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings</title>
    <link rel="stylesheet" href="styles.css"> <!-- Include your CSS styles here -->
</head>

<body>

    <?php include 'header.php'; ?> <!-- Include a common header if needed -->

    <div class="container">
        <h2>Active Job Postings</h2>

        <?php if ($result->num_rows > 0): ?>
            <table border="1">
                <thead>
                    <tr>
                        <th>Job Title</th>
                        <th>Company</th>
                        <th>Location</th>
                        <th>Salary Range</th>
                        <th>Description</th>
                        <th>Openings</th>
                        <th>Deadline</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()):
                        $job_id = $row['job_id'];

                        // Check if the user has already applied for this job
                        $application_check_sql = "SELECT application_id, application_status FROM applications 
                                              WHERE job_id = '$job_id' AND profile_id = 
                                              (SELECT profile_id FROM profiles WHERE user_id = '$user_id') 
                                              AND application_status != 'WITHDRAWN'";
                        $application_check_result = $conn->query($application_check_sql);

                        $already_applied = $application_check_result->num_rows > 0;
                    ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['job_title']); ?></td>
                            <td><?php echo htmlspecialchars($row['company']); ?></td>
                            <td><?php echo htmlspecialchars($row['location']); ?></td>
                            <td><?php echo htmlspecialchars($row['min_salary']) . ' - ' . htmlspecialchars($row['max_salary']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <td><?php echo htmlspecialchars($row['openings']); ?></td>
                            <td><?php echo htmlspecialchars($row['deadline']); ?></td>
                            <td>
                                <?php if ($already_applied): ?>
                                    <span>Already Applied</span>
                                <?php else: ?>
                                    <a href="apply_job.php?job_id=<?php echo $row['job_id']; ?>">Apply Now</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No active job postings at the moment.</p>
        <?php endif; ?>

    </div>

    <?php include 'footer.php'; ?> <!-- Include a common footer if needed -->

</body>

</html>

<?php
// Close the database connection
$conn->close();
?>