<?php
include 'C:\xampp\htdocs\bonafide-final\db.php'; // Use the absolute path to db.php

// Fetch all active job postings with their total openings and creation date
$jobs_sql = "
    SELECT jp.job_id, jp.created_at, jp.openings, COUNT(ps.application_id) AS deployed_count
    FROM job_postings AS jp
    LEFT JOIN applications AS a ON jp.job_id = a.job_id
    LEFT JOIN tbl_pipeline_stage AS ps ON a.application_id = ps.application_id AND a.application_status = 'DEPLOYED'
    WHERE jp.status = 'ACTIVE'
    GROUP BY jp.job_id;
";
$jobs_stmt = $conn->prepare($jobs_sql);
$jobs_stmt->execute();
$jobs_result = $jobs_stmt->get_result();

while ($job = $jobs_result->fetch_assoc()) {
    $job_id = $job['job_id'];
    $created_at = new DateTime($job['created_at']);
    $openings = $job['openings'];
    $deployed_count = $job['deployed_count'];

    $should_archive = false;
    $time_to_fill = null;

    // Check if the job's deadline has passed
    $deadline_sql = "SELECT deadline FROM job_postings WHERE job_id = ?";
    $deadline_stmt = $conn->prepare($deadline_sql);
    $deadline_stmt->bind_param("i", $job_id);
    $deadline_stmt->execute();
    $deadline_result = $deadline_stmt->get_result()->fetch_assoc();

    $deadline_passed = (new DateTime($deadline_result['deadline'])) < new DateTime();

    // Archive and calculate `time_to_fill` if openings are filled or deadline passed
    if ($deployed_count >= $openings || $deadline_passed) {
        $should_archive = true;
        $fill_date = null;

        // Determine the date to use for `time_to_fill`
        if ($deployed_count >= $openings) {
            // Get the date of the last deployment that fills the opening
            $fill_date_sql = "
                SELECT MAX(ps.deployed_at) AS last_deployment
                FROM tbl_pipeline_stage AS ps
                INNER JOIN applications AS a ON ps.application_id = a.application_id
                WHERE a.job_id = ? AND a.application_status = 'DEPLOYED'
                ORDER BY ps.deployed_at DESC
                LIMIT ?
            ";
            $fill_date_stmt = $conn->prepare($fill_date_sql);
            $fill_date_stmt->bind_param("ii", $job_id, $openings);
            $fill_date_stmt->execute();
            $fill_date_result = $fill_date_stmt->get_result()->fetch_assoc();

            $fill_date = new DateTime($fill_date_result['last_deployment']);
        } else {
            // Use the current date if the job is archived due to deadline
            $fill_date = new DateTime();
        }

        // Calculate `time_to_fill`
        if ($fill_date) {
            $interval = $created_at->diff($fill_date);
            $time_to_fill = $interval->days; // Time to fill in days
        }
    }

    // Archive the job and update `time_to_fill` if applicable
    if ($should_archive) {
        $archive_sql = "
            UPDATE job_postings
            SET status = 'ARCHIVED'
            WHERE job_id = ?
        ";
        $archive_stmt = $conn->prepare($archive_sql);
        $archive_stmt->bind_param("i", $job_id);
        $archive_stmt->execute();

        if ($time_to_fill !== null) {
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
}

// Close the database connection
$conn->close();
