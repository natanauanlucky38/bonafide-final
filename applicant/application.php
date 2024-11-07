<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'sidebar.php';

// Check if the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');
    exit();
}

include 'header.php';

$user_id = $_SESSION['user_id'];

// Get the application ID from the URL to highlight the specific row
$highlightedApplicationId = isset($_GET['application_id']) ? $_GET['application_id'] : null;

$applications_sql = "
    SELECT a.application_id, a.application_status, a.rejection_reason, a.resume,
           j.job_title, j.company, j.location,
           i.interview_date, i.interview_type, i.meet_link,
           a.job_id
    FROM applications a
    JOIN job_postings j ON a.job_id = j.job_id
    LEFT JOIN (
        SELECT application_id, interview_date, interview_type, meet_link
        FROM tbl_interview
        WHERE (application_id, interview_date) IN (
            SELECT application_id, MAX(interview_date)
            FROM tbl_interview
            GROUP BY application_id
        )
    ) i ON a.application_id = i.application_id
    WHERE a.profile_id = (SELECT profile_id FROM profiles WHERE user_id = ?)
    AND a.application_status != 'WITHDRAWN'
    ORDER BY a.application_id DESC
";


$applications_stmt = $conn->prepare($applications_sql);
if (!$applications_stmt) {
    die("Error preparing applications query: " . $conn->error);
}

$applications_stmt->bind_param('i', $user_id);
$applications_stmt->execute();
$applications_result = $applications_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
    <link rel="stylesheet" href="applicant_styles.css"> <!-- Include your CSS styles here -->
</head>

<body class="application-main-content">

    <div class="application-container">
        <h1>My Applications</h1>
        <table>
            <thead>
                <tr>
                    <th>Job Title</th>
                    <th>Company</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Details</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($applications_result->num_rows > 0): ?>
                    <?php while ($application = $applications_result->fetch_assoc()): ?>
                        <tr id="application-<?php echo $application['application_id']; ?>" class="<?php echo ($application['application_id'] == $highlightedApplicationId) ? 'highlight-row' : ''; ?>">
                            <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                            <td><?php echo htmlspecialchars($application['company']); ?></td>
                            <td><?php echo htmlspecialchars($application['location']); ?></td>
                            <td>
                                <?php
                                switch ($application['application_status']) {
                                    case 'APPLIED':
                                        echo 'Application Submitted';
                                        break;
                                    case 'SCREENING':
                                        echo 'Screening';
                                        break;
                                    case 'INTERVIEW':
                                        echo 'Interview Scheduled';
                                        break;
                                    case 'OFFERED':
                                        echo 'Offer Made';
                                        break;
                                    case 'DEPLOYED':
                                        echo 'Deployed';
                                        break;
                                    case 'REJECTED':
                                        echo 'Rejected';
                                        break;
                                    default:
                                        echo 'Unknown Status';
                                }
                                ?>
                            </td>
                            <td>
                                <?php if ($application['application_status'] == 'INTERVIEW'): ?>
                                    <p><strong>Interview Date:</strong> <?php echo date('F d, Y h:i A', strtotime($application['interview_date'])); ?></p>
                                    <p><strong>Interview Type:</strong> <?php echo htmlspecialchars($application['interview_type']); ?></p>
                                    <p><strong>Meeting Link:</strong> <a href="<?php echo htmlspecialchars($application['meet_link']); ?>" target="_blank">Join Interview</a></p>
                                <?php elseif ($application['application_status'] == 'REJECTED'): ?>
                                    <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($application['rejection_reason']); ?></p>
                                <?php elseif ($application['application_status'] == 'OFFERED'): ?>
                                    <?php
                                    $offer_sql = "SELECT salary, start_date, benefits, remarks FROM tbl_offer_details WHERE job_id = ?";
                                    $offer_stmt = $conn->prepare($offer_sql);
                                    if (!$offer_stmt) {
                                        die("Error preparing offer query: " . $conn->error);
                                    }
                                    $offer_stmt->bind_param('i', $application['job_id']);
                                    $offer_stmt->execute();
                                    $offer_result = $offer_stmt->get_result();

                                    if ($offer_result && $offer_result->num_rows > 0):
                                        $offer = $offer_result->fetch_assoc();
                                    ?>
                                        <p><strong>Salary:</strong> <?php echo htmlspecialchars($offer['salary']); ?></p>
                                        <p><strong>Start Date:</strong> <?php echo htmlspecialchars($offer['start_date']); ?></p>
                                        <p><strong>Benefits:</strong> <?php echo htmlspecialchars($offer['benefits']); ?></p>
                                        <p><strong>Remarks:</strong> <?php echo htmlspecialchars($offer['remarks']); ?></p>
                                    <?php else: ?>
                                        <p>No offer details available.</p>
                                    <?php endif; ?>
                                <?php elseif ($application['application_status'] == 'DEPLOYED'): ?>
                                    <?php
                                    $deployment_sql = "SELECT deployment_remarks FROM tbl_deployment_details WHERE application_id = ?";
                                    $deployment_stmt = $conn->prepare($deployment_sql);
                                    if (!$deployment_stmt) {
                                        die("Error preparing deployment query: " . $conn->error);
                                    }
                                    $deployment_stmt->bind_param('i', $application['application_id']);
                                    $deployment_stmt->execute();
                                    $deployment_result = $deployment_stmt->get_result();

                                    if ($deployment_result && $deployment_result->num_rows > 0):
                                        $deployment = $deployment_result->fetch_assoc();
                                    ?>
                                        <p><strong>Deployment Remarks:</strong> <?php echo htmlspecialchars($deployment['deployment_remarks']); ?></p>
                                    <?php else: ?>
                                        <p>No deployment details available.</p>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <p>No additional details available.</p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($application['application_status'] != 'REJECTED' && $application['application_status'] != 'WITHDRAWN' && $application['application_status'] != 'DEPLOYED'): ?>
                                    <!-- Withdraw button with confirmation dialog -->
                                    <form action="withdraw_application.php" method="GET" onsubmit="return confirm('Are you sure you want to withdraw this application?');">
                                        <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                        <button type="submit">Withdraw Application</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">No applications found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div style="height: 500px;"></div>


    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const highlightedRowId = 'application-<?php echo $highlightedApplicationId; ?>';
            const highlightedRow = document.getElementById(highlightedRowId);

            // Remove highlight from all rows except the highlighted row
            document.querySelectorAll('.highlight-row').forEach(row => {
                if (row.id !== highlightedRowId) {
                    row.classList.remove('highlight-row');
                }
            });

            if (highlightedRow) {
                setTimeout(() => {
                    highlightedRow.classList.remove('highlight-row');
                }, 1000);
            }
        });
    </script>

</body>

<?php include 'footer.php'; ?>

</html>

<?php
$conn->close();
?>