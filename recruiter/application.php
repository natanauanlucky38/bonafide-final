<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: login.php');
    exit();
}

// Fetch all applications along with applicant details
$applications_sql = "
    SELECT a.*, p.fname AS applicant_fname, p.lname AS applicant_lname 
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
        function toggleInterviewFields(selectElement) {
            var interviewFields = document.getElementById('interviewFields' + selectElement.dataset.applicationId);
            var rejectionReasonTextarea = document.getElementById('rejectionReason' + selectElement.dataset.applicationId);
            if (selectElement.value === 'interview') {
                interviewFields.style.display = 'block';
                rejectionReasonTextarea.style.display = 'none'; // Hide rejection reason textarea
            } else if (selectElement.value === 'reject') {
                rejectionReasonTextarea.style.display = 'block'; // Show rejection reason textarea
                interviewFields.style.display = 'none'; // Hide interview fields
            } else {
                interviewFields.style.display = 'none'; // Hide interview fields
                rejectionReasonTextarea.style.display = 'none'; // Hide rejection reason textarea
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
                <td><a href="view_application.php?application_id=<?php echo $application['application_id']; ?>">View Details</a></td>
                <td>
                    <?php
                    // Screening logic: Check if the applicant failed any dealbreaker questions
                    $screen_sql = "
                        SELECT q.question_text, aa.answer_text, q.correct_answer, q.is_dealbreaker
                        FROM questionnaire_template q 
                        JOIN application_answers aa ON q.question_id = aa.question_id 
                        WHERE aa.application_id = '" . $application['application_id'] . "' 
                        AND q.is_dealbreaker = 1 
                        AND aa.answer_text != q.correct_answer
                    ";

                    $screen_result = $conn->query($screen_sql);
                    
                    if (!$screen_result) {
                        echo "Error screening application: " . $conn->error;
                    } elseif ($screen_result->num_rows > 0) {
                        echo '<span style="color: red;">Failed Dealbreaker</span>';
                    } else {
                        echo '<span style="color: green;">Passed</span>';
                    }
                    ?>
                    <form action="process_application.php" method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                        <select name="decision" required onchange="toggleInterviewFields(this)" data-application-id="<?php echo $application['application_id']; ?>">
                            <option value="">Select Action</option>
                            <option value="reject">Reject</option>
                            <option value="interview">Proceed to Interview</option>
                        </select>
                        <textarea id="rejectionReason<?php echo $application['application_id']; ?>" name="rejection_reason" placeholder="Enter rejection reason" style="display:none;"></textarea>
                        <div id="interviewFields<?php echo $application['application_id']; ?>" style="display:none;">
                            <label for="interview_time">Interview Date & Time:</label>
                            <input type="datetime-local" name="interview_time" required>
                            
                            <label for="interview_type">Interview Type:</label>
                            <select name="interview_type" required>
                                <option value="">Select Interview Type</option>
                                <option value="Online">Online</option>
                                <option value="Face-to-Face">Face-to-Face</option>
                            </select>
                            
                            <label for="meeting_link">Meeting Link:</label>
                            <input type="text" name="meeting_link" placeholder="Meeting Link" required>
                            
                            <label for="recruiter_phone">Recruiter Phone Number:</label>
                            <input type="text" name="recruiter_phone" placeholder="Recruiter Phone Number" required>
                            
                            <label for="recruiter_email">Recruiter Email:</label>
                            <input type="email" name="recruiter_email" placeholder="Recruiter Email" required>
                            
                            <label for="remarks">Remarks (Description):</label>
                            <textarea name="remarks" placeholder="Remarks (Description)"></textarea>
                        </div>
                        <button type="submit">Submit</button>
                    </form>
                </td>
                <td>
                    <!-- Display rejection reason if application is rejected -->
                    <?php if ($application['application_status'] == 'REJECTED' && !empty($application['rejection_reason'])): ?>
                        <p><strong>Rejection Reason:</strong> <?php echo htmlspecialchars($application['rejection_reason']); ?></p>
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
