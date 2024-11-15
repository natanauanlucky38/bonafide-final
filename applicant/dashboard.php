<?php
// dashboard.php
include '../db.php';  // Database connection
include 'header.php';



// Check if the user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');  // Redirect to login page
    exit();
}



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
    SELECT i.interview_date, j.job_title, a.application_id
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
    // Ensure application_id is part of the row
    if (isset($row['application_id'])) {
        $interviews[] = [
            'title' => $row['job_title'],
            'start' => $row['interview_date'],
            'url' => 'application.php?application_id=' . $row['application_id'] // Link with application_id for highlighting
        ];
    } else {
        // Debug output in case application_id is missing
        echo "<p>Warning: application_id is missing for interview with job title: " . htmlspecialchars($row['job_title']) . "</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>
</head>

<body class="dashboard-main-content">

    <div class="dashboard-container">
        <!-- Left Column: Welcome Message -->
        <div class="welcome-section">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['fname']) . ' ' . htmlspecialchars($_SESSION['lname']); ?>!</h2>
            <p>Welcome to the dashboard! We're glad to have you here. Let's get started! you can keep track of your upcoming interviews and other events.</p>
        </div>

        <!-- Right Column: Calendar Section -->

        <div class="calendar-section" id="calendarSection">
            <h3>Upcoming Events</h3>
            <div id="calendar">
                <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
                <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
                <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
            </div>
        </div>
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
                    eventColor: '#3788d8', // Set a color for interview events
                    eventClick: function(info) {
                        window.location.href = 'application.php'; // Redirect to application.php
                    }
                });
                calendar.render();
            });

            // Listen for the custom event from the sidebar
            document.addEventListener('sidebarToggle', (event) => {
                const calendarSection = document.getElementById('calendarSection');
                if (event.detail === 'open') {
                    calendarSection.classList.add('hide-calendar');
                } else {
                    calendarSection.classList.remove('hide-calendar');
                }
            });
        </script>
    </div>
    <div style="height: 900px;"></div>
</body>

<?php
include 'footer.php';
?>

</html>


<?php
// Close the database connection
$conn->close();
?>