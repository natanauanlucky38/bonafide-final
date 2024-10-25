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
    <link rel="stylesheet" href="styles.css"> <!-- Link to external CSS -->
    <style>
        /* Additional styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        .main-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
        }

        .main-header h1 {
            margin: 0;
            font-size: 24px;
        }

        .notification {
            position: relative;
            display: inline-block;
        }

        .notification .badge {
            position: absolute;
            top: -5px;
            right: -10px;
            padding: 5px 8px;
            border-radius: 50%;
            background-color: red;
            color: white;
            font-size: 12px;
        }

        .notification-list {
            display: none;
            position: absolute;
            background-color: white;
            min-width: 300px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            z-index: 1;
            padding: 10px;
            right: 0;
            border-radius: 4px;
        }

        .notification-list a {
            color: black;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-bottom: 1px solid #f1f1f1;
        }

        .notification-list a:last-child {
            border-bottom: none;
        }

        .notification-list a:hover {
            background-color: #f1f1f1;
        }

        .notification:hover .notification-list {
            display: block;
        }
    </style>
</head>

<body>
    <!-- Main header section -->
    <header class="main-header">
        <h1>Recruiter Dashboard</h1>

        <!-- Notification Dropdown -->
        <div class="notification">
            <a href="#" id="notification-bell" style="color: white;">
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
    </header>
</body>

</html>