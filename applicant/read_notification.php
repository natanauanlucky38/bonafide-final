<?php
include '../db.php';  // Database connection

// Ensure the notification ID is provided
if (!isset($_GET['notification_id'])) {
    echo "Error: Notification ID not provided.";
    exit();
}

$notification_id = (int)$_GET['notification_id'];

// Mark the notification as read
$update_notification_sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
$update_stmt = $conn->prepare($update_notification_sql);
$update_stmt->bind_param('i', $notification_id);
$update_stmt->execute();

// Fetch the link to redirect the user to the relevant page
$link_sql = "SELECT link FROM notifications WHERE notification_id = ?";
$link_stmt = $conn->prepare($link_sql);
$link_stmt->bind_param('i', $notification_id);
$link_stmt->execute();
$link_result = $link_stmt->get_result();
$link_row = $link_result->fetch_assoc();

if ($link_row) {
    $link = $link_row['link'];
    header("Location: $link");
    exit();
} else {
    echo "Error: Notification not found.";
    exit();
}
