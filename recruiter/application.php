<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: login.php');
    exit();
}

include 'sidebar.php';

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
        function toggleFields(selectElement) {
            var applicationId = selectElement.dataset.applicationId;
            var interviewFields = document.getElementById('interviewFields' + applicationId);
            var offerFields = document.getElementById('offerFields' + applicationId);
            var rejectionReasonTextarea = document.getElementById('rejectionReason' + applicationId);

            // Hide all fields initially
            interviewFields.style.display = 'none';
            offerFields.style.display = 'none';
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

            // Show and require the relevant fields based on the selection
            if (selectElement.value === 'interview') {
                interviewFields.style.display = 'block';
                interviewInputs.forEach(function(input) {
                    input.setAttribute('required', 'required');
                });
            } else if (selectElement.value === 'reject') {
                rejectionReasonTextarea.style.display = 'block';
            } else if (selectElement.value === 'offer') {
                offerFields.style.display = 'block';
                offerInputs.forEach(function(input) {
                    input.setAttribute('required', 'required');
                });
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
                </td>
                <td>
                    <form action="process_application.php" method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="application_id" value="<?php echo $application['application_id']; ?>">
                        <select name="decision" required onchange="toggleFields(this)" data-application-id="<?php echo $application['application_id']; ?>">
                            <option value="">Select Action</option>
                            <option value="reject">Reject</option>
                            <option value="interview">Proceed to Interview</option>
                            <option value="offer">Proceed to Offer</option>
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