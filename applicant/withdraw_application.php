<?php
include '../db.php';

// Check if the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');
    exit();
}

// Check if the application_id is provided
if (!isset($_POST['application_id'])) {
    echo "Error: Application ID not provided.";
    exit();
}

$application_id = (int)$_POST['application_id']; // Sanitize application_id

// Update the application status to 'WITHDRAWN' (no withdrawn_at column)
$withdraw_sql = "UPDATE applications SET application_status = 'WITHDRAWN' WHERE application_id = '$application_id'";
if ($conn->query($withdraw_sql) === TRUE) {
    // Redirect to view_jobs.php after successful withdrawal
    header('Location: view_jobs.php?withdrawn=1');
    exit();
} else {
    echo "Error withdrawing application: " . $conn->error;
}

$conn->close();
