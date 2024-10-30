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
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <style>
        /* General Header and Navbar Styling */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }

        header {
            background-color: #007bff;
            padding: 10px 0;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.1);
        }

        nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        nav ul li {
            display: inline;
            margin: 0 15px;
        }

        nav ul li a {
            color: white;
            text-decoration: none;
            font-weight: bold;
            font-size: 16px;
            padding: 10px 15px;
            display: block;
        }

        nav ul li a:hover {
            background-color: #0056b3;
            border-radius: 5px;
        }

        /* Notification Dropdown Styling */
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
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            z-index: 1;
            padding: 10px;
            right: 0;
            border-radius: 8px;
        }

        .notification-list a {
            color: black;
            text-decoration: none;
            display: block;
            padding: 10px;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
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

        .notification-list p {
            color: #666;
            font-size: 14px;
            text-align: center;
            margin: 0;
            padding: 10px;
        }
    </style>
</head>

<body>
    <header>
        <nav>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>

                <!-- Notification Dropdown -->
                <li class="notification">
                    <a href="#" id="notification-bell">
                        Notifications <span class="badge"><?php echo $unread_count; ?></span>
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