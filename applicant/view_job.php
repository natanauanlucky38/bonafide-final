<?php
include '../db.php';  // Database connection

// Ensure the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');
    exit();
}

// Fetch all active job postings from the database
$sql = "SELECT * FROM job_postings WHERE status = 'ACTIVE' ORDER BY created_at DESC";
$result = $conn->query($sql);
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

<?php include 'header.php'; ?>  <!-- Include a common header if needed -->

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
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['job_title']); ?></td>
                        <td><?php echo htmlspecialchars($row['company']); ?></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td><?php echo htmlspecialchars($row['min_salary']) . ' - ' . htmlspecialchars($row['max_salary']); ?></td>
                        <td><?php echo htmlspecialchars($row['description']); ?></td>
                        <td><?php echo htmlspecialchars($row['openings']); ?></td>
                        <td><?php echo htmlspecialchars($row['deadline']); ?></td>
                        <td>
                            <a href="apply_job.php?job_id=<?php echo $row['job_id']; ?>">Apply Now</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No active job postings at the moment.</p>
    <?php endif; ?>

</div>

<?php include 'footer.php'; ?>  <!-- Include a common footer if needed -->

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
