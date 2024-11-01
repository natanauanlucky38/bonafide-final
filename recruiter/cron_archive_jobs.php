<?php
include 'C:\xampp\htdocs\bonafide-final\db.php'; // Absolute path to db.php

// Fetch all active job postings with their deadlines and creation dates
$jobs_sql = "
    SELECT job_id, created_at, deadline
    FROM job_postings
    WHERE status = 'ACTIVE';
";
$jobs_stmt = $conn->prepare($jobs_sql);
$jobs_stmt->execute();
$jobs_result = $jobs_stmt->get_result();

$current_date = new DateTime(); // Get the current date once to avoid repeated calls

while ($job = $jobs_result->fetch_assoc()) {
    $job_id = $job['job_id'];
    $created_at = new DateTime($job['created_at']);
    $deadline = new DateTime($job['deadline']);

    // Check if the job's deadline has passed
    if ($current_date > $deadline) {
        // Calculate `time_to_fill` as the difference between `created_at` and `current_date`
        $time_to_fill = $created_at->diff($current_date)->days; // Time to fill in days

        // Archive the job by updating `status` to 'ARCHIVED' and setting `filled_date`
        $filled_date = $current_date->format('Y-m-d H:i:s');
        $archive_sql = "
            UPDATE job_postings
            SET status = 'ARCHIVED', filled_date = ?
            WHERE job_id = ?
        ";
        $archive_stmt = $conn->prepare($archive_sql);
        $archive_stmt->bind_param("si", $filled_date, $job_id);
        $archive_stmt->execute();

        // Update `time_to_fill` in `tbl_job_metrics`
        $metrics_update_sql = "
            UPDATE tbl_job_metrics
            SET time_to_fill = ?
            WHERE job_id = ?
        ";
        $metrics_stmt = $conn->prepare($metrics_update_sql);
        $metrics_stmt->bind_param("ii", $time_to_fill, $job_id);
        $metrics_stmt->execute();
    }
}

// Close the database connection
$conn->close();
