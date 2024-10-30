<?php

// Include your database connection file
include '../db.php';

// Ensure the notification ID is provided
if (!isset($_GET['notification_id'])) {
    echo "Error: Notification ID not provided.";
    exit();
}

// Sanitize and validate the notification ID
$notification_id = filter_var($_GET['notification_id'], FILTER_VALIDATE_INT);
if (!$notification_id) {
    echo "Error: Invalid notification ID.";
    exit();
}

try {
    // Mark the notification as read
    $update_notification_sql = "UPDATE notifications SET is_read = 1 WHERE notification_id = ?";
    $update_stmt = $conn->prepare($update_notification_sql);
    if (!$update_stmt) {
        throw new Exception("Failed to prepare statement for updating notification.");
    }
    $update_stmt->bind_param('i', $notification_id);
    $update_stmt->execute();

    // Check if any row was updated
    if ($update_stmt->affected_rows === 0) {
        echo "Error: Notification not found or already marked as read.";
        exit();
    }

    // Fetch the link to redirect the user to the relevant page
    $link_sql = "SELECT link FROM notifications WHERE notification_id = ?";
    $link_stmt = $conn->prepare($link_sql);
    if (!$link_stmt) {
        throw new Exception("Failed to prepare statement for fetching link.");
    }
    $link_stmt->bind_param('i', $notification_id);
    $link_stmt->execute();
    $link_result = $link_stmt->get_result();
    $link_row = $link_result->fetch_assoc();

    // Check if the link is found and redirect
    if ($link_row) {
        $link = $link_row['link'];
        header("Location: $link");
        exit();
    } else {
        echo "Error: Link not found for the notification.";
        exit();
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
