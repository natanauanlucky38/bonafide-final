<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure

// Check if the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');
    exit();
}

include 'header.php';

$user_id = $_SESSION['user_id'];

// Fetch all active applications submitted by the logged-in applicant
$applications_sql = "
    SELECT a.application_id, a.application_status, a.rejection_reason, a.time_applied,
           j.job_title, j.company, j.location,
           i.interview_date, i.interview_type, i.meet_link,
           a.job_id
    FROM applications a
    JOIN job_postings j ON a.job_id = j.job_id
    LEFT JOIN tbl_interview i ON a.application_id = i.application_id
    WHERE a.profile_id = (SELECT profile_id FROM profiles WHERE user_id = $user_id)
    AND a.application_status != 'WITHDRAWN'
    ORDER BY a.time_applied DESC
";

$applications_result = $conn->query($applications_sql);

if (!$applications_result) {
    die("Error fetching applications: " . $conn->error);
}

// Handle accept or withdraw offer actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $application_id = $_POST['application_id'];
    $job_id = $_POST['job_id'];

    if (isset($_POST['accept_offer'])) {
        // Accept the offer, update application status to 'ACCEPTED'
        $accept_sql = "UPDATE applications SET application_status = 'ACCEPTED' WHERE application_id = ?";
        $stmt = $conn->prepare($accept_sql);
        $stmt->bind_param('i', $application_id);
        if ($stmt->execute()) {
            echo "Offer accepted!";
        } else {
            echo "Error accepting offer: " . $conn->error;
        }
    }

    if (isset($_POST['withdraw_offer'])) {
        // Withdraw the offer, update application status to 'WITHDRAWN'
        $withdraw_sql = "UPDATE applications SET application_status = 'WITHDRAWN', withdrawn_at = NOW() WHERE application_id = ?";
        $stmt = $conn->prepare($withdraw_sql);
        $stmt->bind_param('i', $application_id);
        if ($stmt->execute()) {
            // Update tbl_pipeline_stage
            $pipeline_sql = "UPDATE tbl_pipeline_stage SET withdrawn_at = NOW() WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($pipeline_sql);
            $pipeline_stmt->bind_param('i', $application_id);
            $pipeline_stmt->execute();

            // Update tbl_job_metrics
            $metrics_sql = "UPDATE tbl_job_metrics SET withdrawn_applicants = withdrawn_applicants + 1 WHERE job_id = ?";
            $metrics_stmt = $conn->prepare($metrics_sql);
            $metrics_stmt->bind_param('i', $job_id);
            $metrics_stmt->execute();

            echo "Offer withdrawn!";
        } else {
            echo "Error withdrawing offer: " . $conn->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Applications</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid black;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        form {
            margin: 0;
        }

        button {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <h1>My Applications</h1>
    <table>
        <thead>
            <tr>
                <th>Job Title</th>
                <th>Company</th>
                <th>Location</th>
                <th>Status</th>
                <th>Date Applied</th>
                <th>Details</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($applications_result->num_rows > 0): ?>
                <?php while ($application = $applications_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                        <td><?php echo htmlspecialchars($application['company']); ?></td>
                        <td><?php echo htmlspecialchars($application['location']); ?></td>
                        <td>
                            <?php 
                            // Display status based on application_status field
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
                                case 'REJECTED':
                                    echo 'Rejected';
                                    break;
                                default:
                                    echo 'Unknown Status';
                            }
                            ?>
                        </td>
                        <td><?php echo date('F d, Y', strtotime($application['time_applied'])); ?></td>
                        <td>
                            <?php if ($application['application_status'] == 'INTERVIEW'): ?>
                                <!-- Display interview details if scheduled -->
                                <p><strong>Interview Date:</strong> <?php echo date('F d, Y h:i A', strtotime($application['interview_date'])); ?></p>
                                <p><strong>Interview Type:</strong> <?php echo htmlspecialchars($application['interview_type']); ?></p>
                                <p><strong>Meeting Link:</strong> <a href="<?php echo htmlspecialchars($application['meet_link']); ?>" target="_blank">Join Interview</a></p>
                            <?php elseif ($application['application_status'] == 'REJECTED'): ?>
                                <!-- Display rejection reason if rejected -->
                                <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($application['rejection_reason']); ?></p>
                            <?php elseif ($application['application_status'] == 'OFFERED'): ?>
                                <!-- Fetch and display offer details -->
                                <?php
                                $offer_sql = "SELECT salary, start_date, benefits, remarks FROM tbl_offer_details WHERE job_id = ?";
                                $offer_stmt = $conn->prepare($offer_sql);
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
                                    <!-- Accept or Withdraw Offer -->
                                    <form method="POST" action="">
                                        <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                        <input type="hidden" name="job_id" value="<?php echo $application['job_id']; ?>">
                                        <button type="submit" name="accept_offer">Accept Offer</button>
                                        <button type="submit" name="withdraw_offer">Withdraw Offer</button>
                                    </form>
                                <?php else: ?>
                                    <p>No offer details available.</p>
                                <?php endif; ?>
                            <?php else: ?>
                                <p><strong>Status:</strong> In progress</p>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">You have not submitted any applications yet.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>