<?php
// dashboard_layout.php
require_once '../db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');  // Redirect to login page if not logged in
    exit();
}

// Fetch unread notifications count for the logged-in recruiter
$user_id = $_SESSION['user_id'];
$unread_notifications_sql = "SELECT COUNT(*) AS unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$unread_stmt = $conn->prepare($unread_notifications_sql);
$unread_stmt->bind_param('i', $user_id);
$unread_stmt->execute();
$unread_result = $unread_stmt->get_result();
$unread_row = $unread_result->fetch_assoc();
$unread_count = $unread_row['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applicant Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="applicant_styles.css">
</head>

<body class="header-body">
    <!-- Header Section -->
    <header>
        <!-- Hamburger Icon for Mobile View -->
        <button id="hamburger-icon" class="hamburger" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <div class="header-title">
            <img src="images/logo.png" alt="Company Logo" class="logo">
            <h1>Bonafide Trainology Placement Services</h1>
        </div>


        <nav class="header">
            <ul>
                <!-- Notification Dropdown -->
                <li class="notification">
                    <a href="#" id="notification-bell">
                        <i class="fas fa-bell"></i>
                        <span class="badge"><?php echo $unread_count; ?></span>
                    </a>
                    <div class="notification-list">
                        <?php
                        // Fetch unread notifications
                        $notifications_sql = "SELECT notification_id, title, subject FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
                        $notifications_stmt = $conn->prepare($notifications_sql);
                        $notifications_stmt->bind_param('i', $user_id);
                        $notifications_stmt->execute();
                        $notifications_result = $notifications_stmt->get_result();

                        if ($notifications_result->num_rows > 0) {
                            while ($notification = $notifications_result->fetch_assoc()) {
                                echo '<a href="read_notification.php?notification_id=' . $notification['notification_id'] . '">' .
                                    '<strong>' . htmlspecialchars($notification['title']) . '</strong><br>' .
                                    htmlspecialchars($notification['subject']) .
                                    '</a>';
                            }
                        } else {
                            echo '<p>No new notifications</p>';
                        }
                        ?>
                    </div>
                </li>

                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <!-- Sidebar Section -->
    <div class="sidebar" id="sidebar">
        <ul class="sidebar_list">
            <li><a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
            <li><a href="view_job.php"><i class="fas fa-briefcase"></i> Job Postings</a></li>
            <li><a href="application.php"><i class="fas fa-file-alt"></i> Applications</a></li>
            <li><a href="referrals.php"><i class="fas fa-user-friends"></i> Referrals</a></li>
            <li><a href="profile.php"><i class="fas fa-user-circle"></i> Profile</a></li>
        </ul>
    </div>

    <!-- JavaScript for Sidebar Toggle -->
    <script>
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('show-sidebar');
        }
    </script>
</body>

</html>