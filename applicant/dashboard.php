<?php
// dashboard.php
include '../db.php';  // Database connection
include 'header.php';
include 'sidebar.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

$profile_sql = "SELECT fname, lname FROM profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();

if ($profile_result->num_rows > 0) {
    $profile = $profile_result->fetch_assoc();
    $_SESSION['fname'] = $profile['fname'];
    $_SESSION['lname'] = $profile['lname'];
} else {
    header('Location: setup_profile.php');
    exit();
}

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
    if (isset($row['application_id'])) {
        $interviews[] = [
            'title' => $row['job_title'],
            'start' => $row['interview_date'],
            'url' => 'application.php?application_id=' . $row['application_id']
        ];
    } else {
        echo "<p>Warning: application_id is missing for interview with job title: " . htmlspecialchars($row['job_title']) . "</p>";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Applicant Dashboard</title>
    <link rel="stylesheet" href="applicant_styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css" rel="stylesheet">
</head>

<body class="dashboard-main-content">

    <div class="dashboard-container">
        <div class="welcome-section">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['fname']) . ' ' . htmlspecialchars($_SESSION['lname']); ?>!</h2>
            <p>Welcome to the dashboard! We're glad to have you here. Let's get started! you can keep track of your upcoming interviews and other events.</p>
        </div>

        <div class="calendar-section">
            <h3>Upcoming Events</h3>
            <div id="calendar"></div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.10.2/dist/umd/popper.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var calendarEl = document.getElementById('calendar');
                var calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    events: <?php echo json_encode($interviews); ?>,
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,timeGridDay'
                    },
                    eventColor: '#3788d8',
                    eventClick: function(info) {
                        window.location.href = info.event.url;
                    }
                });
                calendar.render();
            });
        </script>
    </div>

</body>

</html>
<?php include 'footer.php'; ?>

<?php
$conn->close();
?>