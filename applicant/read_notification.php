<?php
include '../db.php';  // Database connection

if (!isset($_SESSION['user_id'])) {
    echo "Error: User not logged in.";
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user ID

// Ensure the notification ID is provided
if (!isset($_GET['notification_id'])) {
    echo "Error: Notification ID not provided.";
    exit();
}

$notification_id = (int)$_GET['notification_id'];

// Verify that the notification belongs to the logged-in user
$verify_notification_sql = "SELECT link FROM notifications WHERE notification_id = ? AND user_id = ?";
$verify_stmt = $conn->prepare($verify_notification_sql);
$verify_stmt->bind_param('ii', $notification_id, $user_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();
$notification = $verify_result->fetch_assoc();

if ($notification) {
    // Mark the notification as read
    $update_notification_sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
    $update_stmt = $conn->prepare($update_notification_sql);
    $update_stmt->bind_param('i', $notification_id);
    $update_stmt->execute();

    // Redirect to the notification's link
    $link = $notification['link'];
    header("Location: $link");
    exit();
} else {
    echo "Error: Notification not found or does not belong to this user.";
    exit();
}
