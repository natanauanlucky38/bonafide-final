<?php
// Include database connection
include '../db.php';
include 'header.php';

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
    SELECT a.*, p.fname AS applicant_fname, p.lname AS applicant_lname, j.job_title, p.profile_id, j.job_id
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

// Fetch qualifications, skills, and work experience
$details_sql = "SELECT detail_value, qualifications, skills, work_experience FROM profile_details WHERE profile_id = ?";
$details_stmt = $conn->prepare($details_sql);
$details_stmt->bind_param("i", $application['profile_id']);
$details_stmt->execute();
$details_result = $details_stmt->get_result();

$qualifications = [];
$skills = [];
$work_experience = [];

while ($detail = $details_result->fetch_assoc()) {
    if (!empty($detail['qualifications'])) $qualifications[] = htmlspecialchars($detail['detail_value']);
    if (!empty($detail['skills'])) $skills[] = htmlspecialchars($detail['detail_value']);
    if (!empty($detail['work_experience'])) $work_experience[] = htmlspecialchars($detail['detail_value']);
}

// Process form submission
$message = '';
$success = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['decision'];
    $job_id = $application['job_id']; // Retrieve the job_id for metrics updates

    switch ($action) {
        case 'interview':
            $interview_time = $_POST['interview_time'];
            $interview_type = $_POST['interview_type'];
            $meeting_link = $_POST['meeting_link'];
            $recruiter_phone = $_POST['recruiter_phone'];
            $recruiter_email = $_POST['recruiter_email'];
            $remarks = $_POST['remarks'];

            // Update application status and pipeline stage for interview
            $sql = "UPDATE applications SET application_status = 'INTERVIEW' WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $application_id);
            $stmt->execute();

            $pipeline_sql = "UPDATE tbl_pipeline_stage 
                             SET screened_at = IF(screened_at IS NULL, NOW(), screened_at),
                                 interviewed_at = ?, 
                                 duration_applied_to_screened = TIMESTAMPDIFF(DAY, applied_at, screened_at), 
                                 duration_screened_to_interviewed = TIMESTAMPDIFF(DAY, screened_at, ?) 
                             WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($pipeline_sql);
            $pipeline_stmt->bind_param("ssi", $interview_time, $interview_time, $application_id);
            $pipeline_stmt->execute();

            // Insert interview details
            $interview_sql = "INSERT INTO tbl_interview (application_id, interview_date, interview_type, meet_link, phone, recruiter_email, remarks) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
            $interview_stmt = $conn->prepare($interview_sql);
            $interview_stmt->bind_param("issssss", $application_id, $interview_time, $interview_type, $meeting_link, $recruiter_phone, $recruiter_email, $remarks);
            $success = $interview_stmt->execute();

            // Update job metrics: screened_applicants and interviewed_applicants
            $metrics_sql = "UPDATE tbl_job_metrics 
                            SET screened_applicants = screened_applicants + 1, 
                                interviewed_applicants = interviewed_applicants + 1 
                            WHERE job_id = ?";
            $metrics_stmt = $conn->prepare($metrics_sql);
            $metrics_stmt->bind_param("i", $job_id);
            $metrics_stmt->execute();

            $message = $success ? "Interview scheduled successfully." : "Error scheduling interview.";
            break;

        case 'offer':
            $salary = $_POST['salary'];
            $start_date = $_POST['start_date'];
            $benefits = $_POST['benefits'];
            $offer_remarks = $_POST['offer_remarks'];

            // Update application status and pipeline stage for offer
            $sql = "UPDATE applications SET application_status = 'OFFERED' WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $application_id);
            $stmt->execute();

            $pipeline_sql = "UPDATE tbl_pipeline_stage 
                             SET offered_at = NOW(),
                                 duration_interviewed_to_offered = TIMESTAMPDIFF(DAY, interviewed_at, NOW()) 
                             WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($pipeline_sql);
            $pipeline_stmt->bind_param("i", $application_id);
            $pipeline_stmt->execute();

            // Insert offer details
            $offer_sql = "INSERT INTO tbl_offer_details (job_id, salary, start_date, benefits, remarks) 
                          VALUES (?, ?, ?, ?, ?)";
            $offer_stmt = $conn->prepare($offer_sql);
            $offer_stmt->bind_param("idsss", $job_id, $salary, $start_date, $benefits, $offer_remarks);
            $success = $offer_stmt->execute();

            // Update job metrics: offered_applicants
            $metrics_sql = "UPDATE tbl_job_metrics 
                            SET offered_applicants = offered_applicants + 1 
                            WHERE job_id = ?";
            $metrics_stmt = $conn->prepare($metrics_sql);
            $metrics_stmt->bind_param("i", $job_id);
            $metrics_stmt->execute();

            $message = $success ? "Offer details saved successfully." : "Error saving offer details.";
            break;

        case 'deployment':
            $deployment_remarks = $_POST['deployment_remarks'];

            // Update application status and pipeline stage for deployment
            $sql = "UPDATE applications SET application_status = 'DEPLOYED' WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $application_id);
            $stmt->execute();

            $pipeline_sql = "UPDATE tbl_pipeline_stage 
                             SET deployed_at = NOW(),
                                 duration_offered_to_hired = TIMESTAMPDIFF(DAY, offered_at, NOW()),
                                 total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW()) 
                             WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($pipeline_sql);
            $pipeline_stmt->bind_param("i", $application_id);
            $pipeline_stmt->execute();

            // Insert deployment details
            $deployment_sql = "INSERT INTO tbl_deployment_details (application_id, deployment_date, deployment_remarks) 
                               VALUES (?, NOW(), ?)";
            $deployment_stmt = $conn->prepare($deployment_sql);
            $deployment_stmt->bind_param("is", $application_id, $deployment_remarks);
            $success = $deployment_stmt->execute();

            // Update job metrics: deployed_applicants
            $metrics_sql = "UPDATE tbl_job_metrics 
                            SET successful_placements = successful_placements + 1 
                            WHERE job_id = ?";
            $metrics_stmt = $conn->prepare($metrics_sql);
            $metrics_stmt->bind_param("i", $job_id);
            $metrics_stmt->execute();

            $message = $success ? "Deployment completed successfully." : "Error completing deployment.";
            break;

        case 'reject':
            $rejection_reason = $_POST['rejection_reason'];

            // Update application status and pipeline stage for rejection
            $sql = "UPDATE applications SET application_status = 'REJECTED', rejection_reason = ? WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $rejection_reason, $application_id);
            $success = $stmt->execute();

            $pipeline_sql = "UPDATE tbl_pipeline_stage 
                             SET rejected_at = NOW(),
                                 total_duration = TIMESTAMPDIFF(DAY, applied_at, NOW()) 
                             WHERE application_id = ?";
            $pipeline_stmt = $conn->prepare($pipeline_sql);
            $pipeline_stmt->bind_param("i", $application_id);
            $pipeline_stmt->execute();

            $message = $success ? "Application rejected successfully." : "Error rejecting application.";
            break;
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

        h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input,
        select,
        textarea {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border-radius: 4px;
            border: 1px solid #ccc;
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

        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
            padding: 10px;
            background-color: #007bff;
            color: #ffffff;
            text-decoration: none;
            border-radius: 5px;
        }

        .back-link:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Application Details for <?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?></h2>

        <?php if ($message): ?>
            <div class="message <?php echo $success ? 'success' : 'error'; ?>"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <p><strong>Job Title:</strong> <?php echo htmlspecialchars($application['job_title']); ?></p>
        <p><strong>Applicant Name:</strong> <?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?></p>
        <p><strong>Application Status:</strong> <?php echo htmlspecialchars($application['application_status']); ?></p>

        <!-- Adjusted resume download link -->
        <p><strong>Resume:</strong>
            <a href="applicant/uploads/<?php echo htmlspecialchars($application['resume']); ?>" download>Download Resume</a>
        </p>

        <p><strong>Qualifications:</strong><br><?php echo !empty($qualifications) ? implode('<br>', $qualifications) : "No qualifications listed."; ?></p>
        <p><strong>Skills:</strong><br><?php echo !empty($skills) ? implode('<br>', $skills) : "No skills listed."; ?></p>
        <p><strong>Work Experience:</strong><br><?php echo !empty($work_experience) ? implode('<br>', $work_experience) : "No work experience listed."; ?></p>

        <?php if ($application['application_status'] == 'REJECTED' && !empty($application['rejection_reason'])): ?>
            <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($application['rejection_reason']); ?></p>
        <?php endif; ?>

        <p><strong>Referral Source:</strong> <?php echo htmlspecialchars($application['referral_source']); ?></p>

        <form method="POST">
            <label>Choose Action:</label>
            <select name="decision" onchange="showFields(this.value)">
                <option value="">Select Action</option>
                <option value="interview">Proceed to Interview</option>
                <option value="offer">Proceed to Offer</option>
                <option value="deployment">Proceed to Deployment</option>
                <option value="reject">Reject</option>
            </select>

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

        <a href="application.php" class="back-link">Back to Applications</a>
    </div>

    <script>
        function showFields(action) {
            document.getElementById('interviewFields').style.display = action === 'interview' ? 'block' : 'none';
            document.getElementById('offerFields').style.display = action === 'offer' ? 'block' : 'none';
            document.getElementById('deploymentFields').style.display = action === 'deployment' ? 'block' : 'none';
            document.getElementById('rejectionFields').style.display = action === 'reject' ? 'block' : 'none';
        }
    </script>
</body>

</html>

<?php
$details_stmt->close();
$stmt->close();
$conn->close();
?>