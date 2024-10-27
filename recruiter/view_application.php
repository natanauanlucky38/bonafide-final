<?php
include '../db.php'; // Database connection
include 'header.php';

// Enable error reporting for debugging
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

// Check if application_id is provided
if (!isset($_GET['application_id'])) {
    echo "Error: Application ID not provided.";
    exit();
}

$application_id = (int)$_GET['application_id']; // Sanitize application_id

// Fetch application details with job title, applicant information, and resume path
$application_sql = "
    SELECT a.*, p.fname AS applicant_fname, p.lname AS applicant_lname, j.job_title, j.location, p.profile_id, j.job_id, j.company
    FROM applications a
    JOIN profiles p ON a.profile_id = p.profile_id
    JOIN job_postings j ON a.job_id = j.job_id
    WHERE a.application_id = ?
";
$stmt = $conn->prepare($application_sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$application_result = $stmt->get_result();

if ($application_result->num_rows == 0) {
    echo "Application not found.";
    exit();
}
$application = $application_result->fetch_assoc();

// Fetch questionnaire answers and screening evaluation
$questionnaire_sql = "
    SELECT q.question_text, a.answer_text, q.is_dealbreaker, 
           CASE 
               WHEN q.is_dealbreaker = 1 AND a.answer_text = 'NO' THEN 'Fail'
               WHEN q.is_dealbreaker = 1 AND a.answer_text = 'YES' THEN 'Pass'
               ELSE 'N/A'
           END AS evaluation
    FROM questionnaire_template q
    JOIN application_answers a ON q.question_id = a.question_id
    WHERE a.application_id = ?
";
$questionnaire_stmt = $conn->prepare($questionnaire_sql);
$questionnaire_stmt->bind_param("i", $application_id);
$questionnaire_stmt->execute();
$questionnaire_result = $questionnaire_stmt->get_result();

// Initialize variables for messages and success status
$message = '';
$success = false;
$base_url = "http://localhost/bonafide-final/applicant/application.php?application_id=" . $application_id;

// Check if the application status is view-only
$is_view_only = in_array($application['application_status'], ['DEPLOYED', 'WITHDRAWN', 'REJECTED']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$is_view_only) {
    $action = $_POST['decision'];
    $job_id = $application['job_id'];
    $applicant_id = $application['profile_id'];

    $conn->begin_transaction();

    try {
        switch ($action) {
            case 'interview':
                $interview_time = $_POST['interview_time'];
                $interview_type = $_POST['interview_type'];
                $meeting_link = $_POST['meeting_link'];
                $recruiter_phone = $_POST['recruiter_phone'];
                $recruiter_email = $_POST['recruiter_email'];
                $remarks = $_POST['remarks'];

                // Update application status
                $sql = "UPDATE applications SET application_status = 'INTERVIEW' WHERE application_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $application_id);
                $stmt->execute();

                // Update pipeline stage with screened_at and duration calculations
                $pipeline_sql = "UPDATE tbl_pipeline_stage 
                                 SET screened_at = NOW(), 
                                     interviewed_at = ?, 
                                     duration_screened_to_interviewed = TIMESTAMPDIFF(DAY, screened_at, ?),
                                     duration_applied_to_screened = TIMESTAMPDIFF(DAY, applied_at, NOW())
                                 WHERE application_id = ?";
                $pipeline_stmt = $conn->prepare($pipeline_sql);
                $pipeline_stmt->bind_param("ssi", $interview_time, $interview_time, $application_id);
                $pipeline_stmt->execute();

                // Increment screened_applicants and interviewed_applicants in tbl_job_metrics
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET screened_applicants = screened_applicants + 1, 
                                           interviewed_applicants = interviewed_applicants + 1 
                                       WHERE job_id = ?";
                $metrics_stmt = $conn->prepare($update_metrics_sql);
                $metrics_stmt->bind_param("i", $job_id);
                $metrics_stmt->execute();

                // Insert interview details
                $interview_sql = "INSERT INTO tbl_interview (application_id, interview_date, interview_type, meet_link, phone, recruiter_email, remarks) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)";
                $interview_stmt = $conn->prepare($interview_sql);
                $interview_stmt->bind_param("issssss", $application_id, $interview_time, $interview_type, $meeting_link, $recruiter_phone, $recruiter_email, $remarks);
                $success = $interview_stmt->execute();

                // Insert notification for interview scheduling
                $notification_link = $base_url;
                $notification_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                                     VALUES (?, 'Interview Scheduled', 'An interview has been scheduled for your application.', ?, 0)";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("is", $applicant_id, $notification_link);
                $notification_stmt->execute();

                $message = "Interview scheduled successfully.";
                break;

            case 'offer':
                $salary = $_POST['salary'];
                $start_date = $_POST['start_date'];
                $benefits = $_POST['benefits'];
                $offer_remarks = $_POST['offer_remarks'];

                $sql = "UPDATE applications SET application_status = 'OFFERED' WHERE application_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $application_id);
                $stmt->execute();

                $pipeline_sql = "UPDATE tbl_pipeline_stage 
                                 SET offered_at = NOW(), duration_interviewed_to_offered = TIMESTAMPDIFF(DAY, interviewed_at, NOW()) 
                                 WHERE application_id = ?";
                $pipeline_stmt = $conn->prepare($pipeline_sql);
                $pipeline_stmt->bind_param("i", $application_id);
                $pipeline_stmt->execute();

                // Increment offered_applicants in tbl_job_metrics
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET offered_applicants = offered_applicants + 1 
                                       WHERE job_id = ?";
                $metrics_stmt = $conn->prepare($update_metrics_sql);
                $metrics_stmt->bind_param("i", $job_id);
                $metrics_stmt->execute();

                // Insert offer details
                $offer_sql = "INSERT INTO tbl_offer_details (job_id, salary, start_date, benefits, remarks) 
                              VALUES (?, ?, ?, ?, ?)";
                $offer_stmt = $conn->prepare($offer_sql);
                $offer_stmt->bind_param("idsss", $job_id, $salary, $start_date, $benefits, $offer_remarks);
                $offer_stmt->execute();

                // Insert notification for offer
                $notification_link = $base_url;
                $notification_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                                     VALUES (?, 'Job Offer', 'You have received a job offer.', ?, 0)";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("is", $applicant_id, $notification_link);
                $notification_stmt->execute();

                $message = "Offer details saved successfully.";
                break;

            case 'deployment':
                $deployment_remarks = $_POST['deployment_remarks'];

                $sql = "UPDATE applications SET application_status = 'DEPLOYED' WHERE application_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $application_id);
                $stmt->execute();

                $pipeline_sql = "UPDATE tbl_pipeline_stage 
                                 SET deployed_at = NOW(), duration_offered_to_hired = TIMESTAMPDIFF(DAY, offered_at, NOW()), total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW()) 
                                 WHERE application_id = ?";
                $pipeline_stmt = $conn->prepare($pipeline_sql);
                $pipeline_stmt->bind_param("i", $application_id);
                $pipeline_stmt->execute();

                // Increment successful_placements in tbl_job_metrics
                $update_metrics_sql = "UPDATE tbl_job_metrics 
                                       SET successful_placements = successful_placements + 1 
                                       WHERE job_id = ?";
                $metrics_stmt = $conn->prepare($update_metrics_sql);
                $metrics_stmt->bind_param("i", $job_id);
                $metrics_stmt->execute();

                // Insert deployment details
                $deployment_sql = "INSERT INTO tbl_deployment_details (application_id, deployment_date, deployment_remarks) 
                                   VALUES (?, NOW(), ?)";
                $deployment_stmt = $conn->prepare($deployment_sql);
                $deployment_stmt->bind_param("is", $application_id, $deployment_remarks);
                $deployment_stmt->execute();

                // Insert notification for deployment
                $notification_link = $base_url;
                $notification_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                                     VALUES (?, 'Deployment Completed', 'You have been successfully deployed.', ?, 0)";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("is", $applicant_id, $notification_link);
                $notification_stmt->execute();

                $message = "Deployment completed successfully.";
                break;

            case 'reject':
                $rejection_reason = $_POST['rejection_reason'];

                // Update the application status to 'REJECTED' and add a rejection reason
                $sql = "UPDATE applications SET application_status = 'REJECTED', rejection_reason = ? WHERE application_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $rejection_reason, $application_id);
                $stmt->execute();

                // Update pipeline stage with rejection timestamp and duration
                $pipeline_sql = "UPDATE tbl_pipeline_stage 
                                 SET rejected_at = NOW(), total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW()) 
                                 WHERE application_id = ?";
                $pipeline_stmt = $conn->prepare($pipeline_sql);
                $pipeline_stmt->bind_param("i", $application_id);
                $pipeline_stmt->execute();

                // Increment rejected_applicants in tbl_job_metrics for the job_id
                $metrics_sql = "UPDATE tbl_job_metrics SET rejected_applicants = rejected_applicants + 1 WHERE job_id = ?";
                $metrics_stmt = $conn->prepare($metrics_sql);
                $metrics_stmt->bind_param("i", $job_id);
                $metrics_stmt->execute();

                // Insert notification for rejection
                $notification_link = $base_url;
                $notification_sql = "INSERT INTO notifications (user_id, title, subject, link, is_read) 
                                     VALUES (?, 'Application Rejected', 'Your application has been rejected.', ?, 0)";
                $notification_stmt = $conn->prepare($notification_sql);
                $notification_stmt->bind_param("is", $applicant_id, $notification_link);
                $notification_stmt->execute();

                $message = "Application rejected successfully.";
                break;
        }

        $conn->commit();
        header('Location: application.php');
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error processing the application: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Application</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        .container {
            width: 500px;
            margin: auto;
            padding: 20px;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 0px 15px rgba(0, 0, 0, 0.1);
        }

        .message {
            text-align: center;
            color: white;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
        }

        .success {
            background-color: #28a745;
        }

        .error {
            background-color: #dc3545;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 8px;
            border: 1px solid #ddd;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Application Details</h2>

        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <p><strong>Job Title:</strong> <?php echo htmlspecialchars($application['job_title']); ?></p>
        <p><strong>Company:</strong> <?php echo htmlspecialchars($application['company']); ?></p>
        <p><strong>Location:</strong> <?php echo htmlspecialchars($application['location']); ?></p>
        <p><strong>Applicant Name:</strong> <?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?></p>
        <p><strong>Application Status:</strong> <?php echo htmlspecialchars($application['application_status']); ?></p>
        <p><strong>Resume:</strong> <a href="../applicant/uploads/<?php echo htmlspecialchars($application['resume']); ?>" download>Download Resume</a></p>

        <h3>Questionnaire Responses</h3>
        <table>
            <tr>
                <th>Question</th>
                <th>Answer</th>
                <th>Screening Result</th>
            </tr>
            <?php while ($question = $questionnaire_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($question['question_text']); ?></td>
                    <td><?php echo htmlspecialchars($question['answer_text']); ?></td>
                    <td><?php echo htmlspecialchars($question['evaluation']); ?></td>
                </tr>
            <?php endwhile; ?>
        </table>

        <?php if (!$is_view_only): ?>
            <!-- Actions form -->
            <form method="POST">
                <label>Choose Action:</label>
                <select name="decision" onchange="showFields(this.value)">
                    <option value="">Select Action</option>
                    <option value="interview">Proceed to Interview</option>
                    <option value="offer">Proceed to Offer</option>
                    <option value="deployment">Proceed to Deployment</option>
                    <option value="reject">Reject</option>
                </select>

                <!-- Conditional fields -->
                <div id="interviewFields" style="display:none;">
                    <label>Interview Date & Time:</label><input type="datetime-local" name="interview_time">
                    <label>Interview Type:</label>
                    <select name="interview_type">
                        <option value="Online">Online</option>
                        <option value="Face-to-Face">Face-to-Face</option>
                    </select>
                    <label>Meeting Link:</label><input type="url" name="meeting_link">
                    <label>Recruiter Phone:</label><input type="tel" name="recruiter_phone">
                    <label>Recruiter Email:</label><input type="email" name="recruiter_email">
                    <label>Remarks:</label><textarea name="remarks"></textarea>
                </div>

                <div id="offerFields" style="display:none;">
                    <label>Salary:</label><input type="number" name="salary" step="0.01">
                    <label>Start Date:</label><input type="date" name="start_date">
                    <label>Benefits:</label><textarea name="benefits"></textarea>
                    <label>Offer Remarks:</label><textarea name="offer_remarks"></textarea>
                </div>

                <div id="deploymentFields" style="display:none;">
                    <label>Deployment Remarks:</label><textarea name="deployment_remarks"></textarea>
                </div>

                <div id="rejectionFields" style="display:none;">
                    <label>Rejection Reason:</label><textarea name="rejection_reason"></textarea>
                </div>

                <button type="submit">Submit</button>
            </form>
        <?php endif; ?>

        <script>
            function showFields(action) {
                document.getElementById('interviewFields').style.display = action === 'interview' ? 'block' : 'none';
                document.getElementById('offerFields').style.display = action === 'offer' ? 'block' : 'none';
                document.getElementById('deploymentFields').style.display = action === 'deployment' ? 'block' : 'none';
                document.getElementById('rejectionFields').style.display = action === 'reject' ? 'block' : 'none';
            }
        </script>
    </div>
</body>

</html>

<?php
$stmt->close();
$conn->close();
?>