<?php
// Include database connection
include '../db.php';

$application_id = $_GET['application_id'] ?? null;
if (!$application_id) {
    die('Application ID is required.');
}

// Fetch applicant information
$app_sql = "SELECT a.*, p.fname, p.lname FROM applications a JOIN profiles p ON a.profile_id = p.profile_id WHERE a.application_id = ?";
$stmt = $conn->prepare($app_sql);
$stmt->bind_param("i", $application_id);
$stmt->execute();
$applicant = $stmt->get_result()->fetch_assoc();

if (!$applicant) {
    die('Application not found.');
}

// Process form submission
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['decision'];
    switch ($action) {
        case 'interview':
            $interview_time = $_POST['interview_time'];
            $interview_type = $_POST['interview_type'];
            $meeting_link = $_POST['meeting_link'];
            $recruiter_phone = $_POST['recruiter_phone'];
            $recruiter_email = $_POST['recruiter_email'];
            $remarks = $_POST['remarks'];

            // Update application status to Interview and insert interview details
            $sql = "UPDATE applications SET application_status = 'INTERVIEW' WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $application_id);
            $stmt->execute();

            $interview_sql = "INSERT INTO tbl_interview (application_id, interview_date, interview_type, meet_link, phone, recruiter_email, remarks) 
                              VALUES (?, ?, ?, ?, ?, ?, ?)";
            $interview_stmt = $conn->prepare($interview_sql);
            $interview_stmt->bind_param("issssss", $application_id, $interview_time, $interview_type, $meeting_link, $recruiter_phone, $recruiter_email, $remarks);
            $interview_stmt->execute();

            $message = "Interview scheduled successfully.";
            break;

        case 'offer':
            $salary = $_POST['salary'];
            $start_date = $_POST['start_date'];
            $benefits = $_POST['benefits'];
            $offer_remarks = $_POST['offer_remarks'];

            // Update application status to Offered and insert offer details
            $sql = "UPDATE applications SET application_status = 'OFFERED' WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $application_id);
            $stmt->execute();

            $offer_sql = "INSERT INTO tbl_offer_details (application_id, salary, start_date, benefits, remarks) VALUES (?, ?, ?, ?, ?)";
            $offer_stmt = $conn->prepare($offer_sql);
            $offer_stmt->bind_param("idsss", $application_id, $salary, $start_date, $benefits, $offer_remarks);
            $offer_stmt->execute();

            $message = "Offer details saved successfully.";
            break;

        case 'deployment':
            $deployment_remarks = $_POST['deployment_remarks'];

            // Update application status to Deployed and insert deployment details
            $sql = "UPDATE applications SET application_status = 'DEPLOYED' WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $application_id);
            $stmt->execute();

            $deployment_sql = "INSERT INTO tbl_deployment_details (application_id, deployment_date, deployment_remarks) VALUES (?, NOW(), ?)";
            $deployment_stmt = $conn->prepare($deployment_sql);
            $deployment_stmt->bind_param("is", $application_id, $deployment_remarks);
            $deployment_stmt->execute();

            $message = "Deployment completed successfully.";
            break;

        case 'reject':
            $rejection_reason = $_POST['rejection_reason'];

            // Update application status to Rejected
            $sql = "UPDATE applications SET application_status = 'REJECTED', rejection_reason = ? WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $rejection_reason, $application_id);
            $stmt->execute();

            $message = "Application rejected successfully.";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Process Application</title>
    <style>
        /* Styling for the form */
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
            padding: 10px;
            color: #ffffff;
            border-radius: 5px;
            margin-bottom: 15px;
        }

        .success {
            background-color: #28a745;
        }

        .error {
            background-color: #dc3545;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2>Process Application for <?php echo htmlspecialchars($applicant['fname'] . ' ' . $applicant['lname']); ?></h2>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">

            <!-- Action Dropdown -->
            <label>Choose Action:</label>
            <select name="decision" onchange="showFields(this.value)">
                <option value="">Select Action</option>
                <option value="interview">Proceed to Interview</option>
                <option value="offer">Proceed to Offer</option>
                <option value="deployment">Proceed to Deployment</option>
                <option value="reject">Reject</option>
            </select>

            <!-- Dynamic Fields for Each Action -->
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