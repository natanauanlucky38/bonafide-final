<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'header.php';

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

include 'sidebar.php';

// Fetch all applications along with applicant details
$applications_sql = "
    SELECT a.*, p.fname AS applicant_fname, p.lname AS applicant_lname, a.resume AS resume_file, p.profile_id
    FROM applications a 
    JOIN profiles p ON a.profile_id = p.profile_id
";
$applications_result = $conn->query($applications_sql);

if (!$applications_result) {
    echo "Error fetching applications: " . $conn->error;
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications</title>
    <script>
        function toggleFields(selectElement) {
            var applicationId = selectElement.dataset.applicationId;
            var interviewFields = document.getElementById('interviewFields' + applicationId);
            var offerFields = document.getElementById('offerFields' + applicationId);
            var deploymentFields = document.getElementById('deploymentFields' + applicationId);
            var rejectionReasonTextarea = document.getElementById('rejectionReason' + applicationId);

            // Hide all fields initially
            interviewFields.style.display = 'none';
            offerFields.style.display = 'none';
            deploymentFields.style.display = 'none';
            rejectionReasonTextarea.style.display = 'none';

            // Remove 'required' attribute from all fields
            var interviewInputs = interviewFields.querySelectorAll('input, select, textarea');
            interviewInputs.forEach(function(input) {
                input.removeAttribute('required');
            });
            var offerInputs = offerFields.querySelectorAll('input, select, textarea');
            offerInputs.forEach(function(input) {
                input.removeAttribute('required');
            });
            var deploymentInputs = deploymentFields.querySelectorAll('input, select, textarea');
            deploymentInputs.forEach(function(input) {
                input.removeAttribute('required');
            });

            // Show and require the relevant fields based on the selection
            if (selectElement.value === 'interview') {
                interviewFields.style.display = 'block';
                interviewInputs.forEach(function(input) {
                    input.setAttribute('required', 'required');
                });
            } else if (selectElement.value === 'offer') {
                offerFields.style.display = 'block';
                offerInputs.forEach(function(input) {
                    input.setAttribute('required', 'required');
                });
            } else if (selectElement.value === 'deployment') {
                deploymentFields.style.display = 'block';
                deploymentInputs.forEach(function(input) {
                    input.setAttribute('required', 'required');
                });
            } else if (selectElement.value === 'reject') {
                rejectionReasonTextarea.style.display = 'block';
            }
        }
    </script>
</head>

<body>
    <h1>Applications</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Applicant Name</th>
                <th>Application Status</th>
                <th>Uploaded File</th>
                <th>Actions</th>
                <th>Screening Result</th>
                <th>Options</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($application = $applications_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?></td>
                    <td><?php echo htmlspecialchars($application['application_status']); ?></td>
                    <td>
                        <!-- Display uploaded file URL if it exists -->
                        <?php if (!empty($application['resume_file'])): ?>
                            <?php
                            // Construct the full file path to access the uploaded resume file
                            $uploaded_file_path = '../applicant/uploads/' . htmlspecialchars($application['resume_file']);
                            ?>
                            <a href="<?php echo $uploaded_file_path; ?>" target="_blank" download>Download Resume</a>
                        <?php else: ?>
                            No file uploaded.
                        <?php endif; ?>
                    </td>
                    <td><a href="view_application.php?application_id=<?php echo $application['application_id']; ?>">View Details</a></td>
                    <td>
                        <?php
                        // Screening logic: Fetch all relevant questions and answers
                        $screen_sql = "
                        SELECT q.question_text, aa.answer_text, q.correct_answer, q.question_type
                        FROM questionnaire_template q 
                        JOIN application_answers aa ON q.question_id = aa.question_id 
                        WHERE aa.application_id = '" . $application['application_id'] . "'
                        ";
                        $screen_result = $conn->query($screen_sql);
                        $failed_screening = false;

                        if (!$screen_result) {
                            echo "Error screening application: " . $conn->error;
                        } else {
                            // Check if there are any incorrect answers for pass/fail determination
                            while ($row = $screen_result->fetch_assoc()) {
                                if ($row['question_type'] !== 'TEXT' && $row['answer_text'] !== $row['correct_answer']) {
                                    $failed_screening = true;
                                }
                            }

                            // Display Pass/Fail indicator
                            if ($failed_screening) {
                                echo "<strong style='color: red;'>Failed Screening</strong><br><br>";
                            } else {
                                echo "<strong style='color: green;'>Passed Screening</strong><br><br>";
                            }

                            // Display questions and answers
                            $screen_result->data_seek(0); // Reset result pointer to the start
                            while ($row = $screen_result->fetch_assoc()) {
                                echo "<strong>Question:</strong> " . htmlspecialchars($row['question_text']) . "<br>";
                                echo "<strong>Your Answer:</strong> " . htmlspecialchars($row['answer_text']) . "<br>";
                                echo "<strong>Correct Answer:</strong> " . htmlspecialchars($row['correct_answer']) . "<br><br>";
                            }
                        }
                        ?>
                    </td>

                    <td>
                        <?php if ($application['application_status'] == 'DEPLOYED' || $application['application_status'] == 'WITHDRAWN' || $application['application_status'] == 'REJECTED'): ?>
                            <!-- View-only mode for deployed, withdrawn, and rejected applications -->
                            <p>No Actions Available</p>
                        <?php else: ?>
                            <form action="process_application.php" method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                                <select name="decision" required onchange="toggleFields(this)" data-application-id="<?php echo $application['application_id']; ?>">
                                    <option value="">Select Action</option>
                                    <?php if ($application['application_status'] == 'APPLIED'): ?>
                                        <option value="interview">Proceed to Interview</option>
                                    <?php elseif ($application['application_status'] == 'INTERVIEW'): ?>
                                        <option value="offer">Proceed to Offer</option>
                                    <?php elseif ($application['application_status'] == 'OFFERED'): ?>
                                        <option value="deployment">Proceed to Deployment</option>
                                    <?php endif; ?>
                                    <option value="reject">Reject</option>
                                </select>

                                <!-- Rejection reason -->
                                <textarea id="rejectionReason<?php echo $application['application_id']; ?>" name="rejection_reason" placeholder="Enter rejection reason" style="display:none;"></textarea>

                                <!-- Interview fields -->
                                <div id="interviewFields<?php echo $application['application_id']; ?>" style="display:none;">
                                    <label for="interview_time">Interview Date & Time:</label>
                                    <input type="datetime-local" name="interview_time">

                                    <label for="interview_type">Interview Type:</label>
                                    <select name="interview_type">
                                        <option value="">Select Interview Type</option>
                                        <option value="Online">Online</option>
                                        <option value="Face-to-Face">Face-to-Face</option>
                                    </select>

                                    <label for="meeting_link">Meeting Link:</label>
                                    <input type="text" name="meeting_link" placeholder="Meeting Link">

                                    <label for="recruiter_phone">Recruiter Phone Number:</label>
                                    <input type="text" name="recruiter_phone" placeholder="Recruiter Phone Number">

                                    <label for="recruiter_email">Recruiter Email:</label>
                                    <input type="email" name="recruiter_email" placeholder="Recruiter Email">

                                    <label for="remarks">Remarks (Description):</label>
                                    <textarea name="remarks" placeholder="Remarks (Description)"></textarea>
                                </div>

                                <!-- Offer fields -->
                                <div id="offerFields<?php echo $application['application_id']; ?>" style="display:none;">
                                    <label for="salary">Salary:</label>
                                    <input type="number" name="salary" placeholder="Salary" step="0.01">

                                    <label for="start_date">Start Date:</label>
                                    <input type="date" name="start_date">

                                    <label for="benefits">Benefits:</label>
                                    <textarea name="benefits" placeholder="Benefits"></textarea>

                                    <label for="offer_remarks">Offer Remarks:</label>
                                    <textarea name="offer_remarks" placeholder="Offer Remarks"></textarea>
                                </div>

                                <!-- Deployment fields -->
                                <div id="deploymentFields<?php echo $application['application_id']; ?>" style="display:none;">
                                    <label for="deployment_remarks">Deployment Remarks:</label>
                                    <textarea name="deployment_remarks" placeholder="Deployment Remarks"></textarea>
                                </div>

                                <button type="submit">Submit</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>