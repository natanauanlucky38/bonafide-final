<?php
include '../db.php';  // Database connection
include 'sidebar.php';

// Ensure the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// Check if a search term is provided
$search_term = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';

// Fetch active job postings excluding the ones the user has been rejected for
$sql = "
    SELECT jp.* 
    FROM job_postings jp
    LEFT JOIN applications a ON jp.job_id = a.job_id 
    AND a.profile_id = (SELECT profile_id FROM profiles WHERE user_id = ?)
    WHERE (a.application_status != 'REJECTED' OR a.application_status IS NULL)
    AND jp.status = 'ACTIVE'
    AND (jp.job_title LIKE ? OR jp.company LIKE ?)
    ORDER BY jp.created_at DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Error preparing job postings query: " . $conn->error);
}
$stmt->bind_param('iss', $user_id, $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Postings</title>
    <link rel="stylesheet" href="applicant_styles.css"> <!-- Link to the external CSS file -->
</head>

<body class="view_job-main-content">

    <?php include 'header.php'; ?>
    <div class="wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="view_job-container">
            <h2>Active Job Postings</h2>

            <form method="GET" action="view_job.php" class="search-bar">
                <input type="text" name="search" placeholder="Search by job title or company"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                <button type="submit">Search</button>
            </form>

            <?php if ($result->num_rows > 0): ?>
                <div class="job-cards">
                    <?php while ($row = $result->fetch_assoc()):
                        $job_id = $row['job_id'];
                        $already_applied = false;
                        $application_check_sql = "SELECT application_id, application_status FROM applications 
                                                  WHERE job_id = '$job_id' AND profile_id = 
                                                  (SELECT profile_id FROM profiles WHERE user_id = '$user_id') 
                                                  AND application_status != 'WITHDRAWN'";
                        $application_check_result = $conn->query($application_check_sql);
                        $already_applied = $application_check_result->num_rows > 0;
                        $share_link = "http://" . $_SERVER['HTTP_HOST'] . "/bonafide-final/applicant/view_job.php?job_id=" . $job_id;
                    ?>
                        <div class="job-card">
                            <h3><?php echo htmlspecialchars($row['job_title']); ?></h3>
                            <div class="company"><?php echo htmlspecialchars($row['company']); ?></div>
                            <div class="location"><?php echo htmlspecialchars($row['location']); ?></div>
                            <div class="salary">₱<?php echo htmlspecialchars($row['min_salary']); ?> - ₱<?php echo htmlspecialchars($row['max_salary']); ?></div>
                            <p class="description"><?php echo htmlspecialchars($row['description']); ?></p>
                            <div class="details">
                                <div>
                                    <?php if ($already_applied): ?>
                                        <span class="already-applied">Already Applied</span>
                                    <?php else: ?>
                                        <a href="apply_job.php?job_id=<?php echo $row['job_id']; ?>" class="apply-btn">Apply Now</a>
                                    <?php endif; ?>
                                </div>
                                <button onclick="copyToClipboard('<?php echo $share_link; ?>')" class="copy-link-btn">Copy Link</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p>No active job postings at the moment.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function copyToClipboard(link) {
            navigator.clipboard.writeText(link).then(() => {
                alert("Link copied to clipboard!");
            }).catch(err => {
                alert("Failed to copy link: ", err);
            });
        }
    </script>


</body>
<?php include 'footer.php'; ?>

</html>
<?php
// Close the database connection
$conn->close();

?>