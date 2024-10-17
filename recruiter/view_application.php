<?php
// Include database connection
include '../db.php';  // Adjust this path according to your structure

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: login.php');
    exit();
}

// Check if application_id is provided
if (!isset($_GET['application_id'])) {
    echo "Error: Application ID not provided.";
    exit();
}

$application_id = (int)$_GET['application_id']; // Sanitize application_id

// Fetch application details with job title and applicant email
$application_sql = "
    SELECT a.*, p.fname AS applicant_fname, p.lname AS applicant_lname, j.job_title
    FROM applications a
    JOIN profiles p ON a.profile_id = p.profile_id
    JOIN job_postings j ON a.job_id = j.job_id
    WHERE a.application_id = ?
";

$stmt = $conn->prepare($application_sql);

// Check if the prepare() failed
if ($stmt === false) {
    echo "Error preparing statement: " . $conn->error;
    exit();
}

$stmt->bind_param("i", $application_id); // Bind the parameter
$stmt->execute();
$application_result = $stmt->get_result();

// Check for query errors
if ($application_result === false) {
    echo "Error retrieving application: " . $conn->error;
    exit();
}

if ($application_result->num_rows == 0) {
    echo "Application not found.";
    exit();
}

// Fetch application data
$application = $application_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application</title>
</head>
<body>
    <h2>Application Details for <?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?></h2>
    
    <p><strong>Job Title:</strong> <?php echo htmlspecialchars($application['job_title']); ?></p>
    <p><strong>Applicant Name:</strong> <?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?></p>
    <p><strong>Application Status:</strong> <?php echo htmlspecialchars($application['application_status']); ?></p>
    <p><strong>Qualifications:</strong> <?php echo htmlspecialchars($application['qualifications']); ?></p>
    <p><strong>Skills:</strong> <?php echo htmlspecialchars($application['skills']); ?></p>
    <p><strong>Work Experience:</strong> <?php echo htmlspecialchars($application['work_experience']); ?></p>

    <!-- Optional: Show rejection reason if applicable -->
    <?php if ($application['application_status'] == 'REJECTED' && !empty($application['rejection_reason'])): ?>
        <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($application['rejection_reason']); ?></p>
    <?php endif; ?>

    <p><strong>Referral Source:</strong> <?php echo htmlspecialchars($application['referral_source']); ?></p>

    <a href="application.php">Back to Applications</a>

</body>
</html>

<?php
// Close the database connection
$stmt->close();  // Close the prepared statement
$conn->close();  // Close the database connection
?>
