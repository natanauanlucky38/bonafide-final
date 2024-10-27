<?php
// dashboard.php
include '../db.php';  // Database connection
include 'sidebar.php';

// Check if the user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');  // Redirect to login page
    exit();
}

include 'header.php';

// Retrieve user ID from session
$user_id = $_SESSION['user_id'];

// Fetch fname and lname from the profiles table
$profile_sql = "SELECT fname, lname FROM profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();

if ($profile_result->num_rows > 0) {
    // Store fname and lname in the session for easy access
    $profile = $profile_result->fetch_assoc();
    $_SESSION['fname'] = $profile['fname'];
    $_SESSION['lname'] = $profile['lname'];
} else {
    // If no profile exists, redirect to setup profile
    header('Location: setup_profile.php');
    exit();
}

// Fetch upcoming interview dates for the applicant
$interviews_sql = "
    SELECT i.interview_date, j.job_title 
    FROM tbl_interview i
    JOIN applications a ON i.application_id = a.application_id
    JOIN job_postings j ON a.job_id = j.job_id
    WHERE a.profile_id = (SELECT profile_id FROM profiles WHERE user_id = ?)
    AND i.interview_date >= NOW()
    ORDER BY i.interview_date ASC
";
$interviews_stmt = $conn->prepare($interviews_sql);
$interviews_stmt->bind_param("i", $user_id);
$interviews_stmt->execute();
$interviews_result = $interviews_stmt->get_result();

$interviews = [];
while ($row = $interviews_result->fetch_assoc()) {
    $interviews[] = [
        'title' => $row['job_title'],
        'start' => $row['interview_date']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Applicant Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-5">
        <!-- Welcome Message -->
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['fname']) . ' ' . htmlspecialchars($_SESSION['lname']); ?>!</h2>
        <p>This is your applicant dashboard.</p>
        <a href="logout.php" class="btn btn-secondary mb-3">Logout</a>

        <!-- Calendar Section -->
        <h3>Upcoming Interviews</h3>
        <div id="calendar"></div>
    </div>

    <!-- Bootstrap JS and FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?php echo json_encode($interviews); ?>, // Load interview dates
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                eventColor: '#3788d8' // Set a color for interview events
            });
            calendar.render();
        });
    </script>
</body>

</html>

<?php
$conn->close();
?>