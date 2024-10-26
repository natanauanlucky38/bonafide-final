<?php
// Include database connection
include '../db.php';  // Adjust this path according to your structure
include 'header.php';

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
    SELECT a.*, p.fname AS applicant_fname, p.lname AS applicant_lname, j.job_title, p.profile_id
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

// Fetch qualifications, skills, and work experience
$details_sql = "SELECT detail_value, qualifications, skills, work_experience
                FROM profile_details
                WHERE profile_id = ?";
$details_stmt = $conn->prepare($details_sql);
$details_stmt->bind_param("i", $application['profile_id']);
$details_stmt->execute();
$details_result = $details_stmt->get_result();

$qualifications = [];
$skills = [];
$work_experience = [];

// Sort the qualifications, skills, and work experience into separate arrays
while ($detail = $details_result->fetch_assoc()) {
    if (!empty($detail['qualifications'])) {
        $qualifications[] = htmlspecialchars($detail['detail_value']);
    }
    if (!empty($detail['skills'])) {
        $skills[] = htmlspecialchars($detail['detail_value']);
    }
    if (!empty($detail['work_experience'])) {
        $work_experience[] = htmlspecialchars($detail['detail_value']);
    }
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
            $success = $interview_stmt->execute();

            $message = $success ? "Interview scheduled successfully." : "Error scheduling interview.";
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
            $success = $offer_stmt->execute();

            $message = $success ? "Offer details saved successfully." : "Error saving offer details.";
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
            $success = $deployment_stmt->execute();

            $message = $success ? "Deployment completed successfully." : "Error completing deployment.";
            break;

        case 'reject':
            $rejection_reason = $_POST['rejection_reason'];

            // Update application status to Rejected
            $sql = "UPDATE applications SET application_status = 'REJECTED', rejection_reason = ? WHERE application_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("si", $rejection_reason, $application_id);
            $success = $stmt->execute();

            $message = $success ? "Application rejected successfully." : "Error rejecting application.";
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            transition: background-color 0.3s;
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

        <!-- Display Qualifications -->
        <p><strong>Qualifications:</strong><br>
            <?php
            if (!empty($qualifications)) {
                foreach ($qualifications as $qualification) {
                    echo $qualification . '<br>';
                }
            } else {
                echo "No qualifications listed.";
            }
            ?>
        </p>

        <!-- Display Skills -->
        <p><strong>Skills:</strong><br>
            <?php
            if (!empty($skills)) {
                foreach ($skills as $skill) {
                    echo $skill . '<br>';
                }
            } else {
                echo "No skills listed.";
            }
            ?>
        </p>

        <!-- Display Work Experience -->
        <p><strong>Work Experience:</strong><br>
            <?php
            if (!empty($work_experience)) {
                foreach ($work_experience as $experience) {
                    echo $experience . '<br>';
                }
            } else {
                echo "No work experience listed.";
            }
            ?>
        </p>

        <!-- Optional: Show rejection reason if applicable -->
        <?php if ($application['application_status'] == 'REJECTED' && !empty($application['rejection_reason'])): ?>
            <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($application['rejection_reason']); ?></p>
        <?php endif; ?>

        <p><strong>Referral Source:</strong> <?php echo htmlspecialchars($application['referral_source']); ?></p>

        <!-- Action Form -->
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
                <label>Interview Date & Time:</label><input type="datetime-local" name="interview_time" required>
                <label>Interview Type:</label>
                <select name="interview_type" required>
                    <option value="Online">Online</option>
                    <option value="Face-to-Face">Face-to-Face</option>
                </select>
                <label>Meeting Link:</label><input type="url" name="meeting_link">
                <label>Recruiter Phone:</label><input type="tel" name="recruiter_phone">
                <label>Recruiter Email:</label><input type="email" name="recruiter_email">
                <label>Remarks:</label><textarea name="remarks"></textarea>
            </div>

            <div id="offerFields" style="display:none;">
                <label>Salary:</label><input type="number" name="salary" step="0.01" required>
                <label>Start Date:</label><input type="date" name="start_date" required>
                <label>Benefits:</label><textarea name="benefits" required></textarea>
                <label>Offer Remarks:</label><textarea name="offer_remarks"></textarea>
            </div>

            <div id="deploymentFields" style="display:none;">
                <label>Deployment Remarks:</label><textarea name="deployment_remarks" required></textarea>
            </div>

            <div id="rejectionFields" style="display:none;">
                <label>Rejection Reason:</label><textarea name="rejection_reason" required></textarea>
            </div>

            <button type="submit">Submit</button>
        </form>

        <!-- Back Link -->
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
// Close the database connection
$details_stmt->close();
$stmt->close();  // Close the prepared statement
$conn->close();  // Close the database connection
?>