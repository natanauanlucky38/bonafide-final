<?php
require_once '../db.php'; // Include database connection

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
    <title>Recruiter Dashboard</title>
    <link rel="stylesheet" href="recruiter_styles.css"> <!-- Link to external CSS -->
</head>

<body>
    <!-- Main header section -->
    <header class="main-header">
        <div class="header-title">
            <img src="images/logo.png" alt="Company Logo" class="logo"> <!-- Update path to your logo -->
            <h1>Bonafide Trainology Placement Services</h1>
        </div>
        <!-- Right Section (Notifications and Logout) -->
        <div class="header-right">
            <!-- Notification Dropdown -->
            <div class="notification">
                <a href="#" id="notification-bell">
                    Notifications <span class="badge"><?php echo $unread_count; ?></span>
                </a>
                <div class="notification-list">
                    <?php
                    // Fetch unread notifications
                    $notifications_sql = "SELECT notification_id, title, subject, link FROM notifications WHERE user_id = ? AND is_read = 0 ORDER BY created_at DESC";
                    $notifications_stmt = $conn->prepare($notifications_sql);
                    $notifications_stmt->bind_param('i', $user_id);
                    $notifications_stmt->execute();
                    $notifications_result = $notifications_stmt->get_result();

                    if ($notifications_result->num_rows > 0) {
                        while ($notification = $notifications_result->fetch_assoc()) {
                            echo '<a href="read_notification.php?notification_id=' . htmlspecialchars($notification['notification_id']) . '">' .
                                '<strong>' . htmlspecialchars($notification['title']) . '</strong><br>' .
                                htmlspecialchars($notification['subject']) .
                                '</a>';
                        }
                    } else {
                        echo '<p>No new notifications</p>';
                    }
                    ?>
                </div>
            </div>
            <!-- Logout Link -->
            <a href="logout.php" class="logout">Logout</a>
        </div>
    </header>
</body>

</html>