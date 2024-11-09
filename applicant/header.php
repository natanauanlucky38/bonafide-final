<?php
require_once '../db.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');  // Redirect to login page if not logged in
    exit();
}

// Fetch unread notifications count for the logged-in recruiter
$user_id = $_SESSION['user_id']; // Assume the user is logged in
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
    <link rel="stylesheet" href="applicant_styles.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Link to Font Awesome -->
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('sidebar-hidden');
        }
    </script>
</head>

<body>
    <header>
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button> <!-- Toggle button for sidebar -->
        <div class="header-title">
            <img src="images/logo.png" alt="Company Logo" class="logo"> <!-- Update path to your logo -->
            <h1>Bonafide Trainology Placement Services</h1>
        </div>
        <nav class="header">
            <ul>
                <!-- Notification Dropdown -->
                <li class="notification">
                    <a href="#" id="notification-bell">
                        <i class="fas fa-bell"></i> <!-- Bell icon -->
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

                <li><a href="logout.php">Logout</a></li> <!-- Assume there's a logout page -->
            </ul>
        </nav>
    </header>
</body>

</html>