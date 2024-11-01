<?php
// download_reports.php - Handles report generation and download as CSV
include '../db.php';  // Include database connection

header('Content-Type: text/csv');
header('Content-Disposition: attachment;filename=report.csv');

$output = fopen("php://output", "w");

// Header row for the CSV
fputcsv($output, [
    'Job Title',
    'Total Applications',
    'Screened (%)',
    'Interviewed (%)',
    'Offered (%)',
    'Deployed (%)',
    'Screened Drop-off (%)',
    'Interviewed Drop-off (%)',
    'Offered Drop-off (%)',
    'Time to Fill (days)',
    'Sourcing Analytics'
]);

// Fetch job postings
$jobQuery = "SELECT job_id, job_title FROM job_postings";
$jobResult = mysqli_query($conn, $jobQuery);

if ($jobResult) {
    while ($job = mysqli_fetch_assoc($jobResult)) {
        $job_id = $job['job_id'];
        $job_title = $job['job_title'];

        // Fetch metrics
        $application_metrics = getApplicationMetrics($conn, $job_id);
        $time_to_fill = getTimeToFill($conn, $job_id);
        $totalApplications = $application_metrics['total'] ?? 0;
        $totalApplications = max($totalApplications, 1); // Prevent division by zero

        // Fetch sourcing analytics and drop-off points
        $sourcing_analytics = getSourcingAnalytics($conn, $job_id, $totalApplications);
        $drop_off_points = getCandidateDropOffPoints($conn, $job_id, $totalApplications);

        // Format sourcing analytics as a string
        $sourcing_string = 'N/A';
        if (!empty($sourcing_analytics)) {
            $sourcing_entries = [];
            foreach ($sourcing_analytics as $source => $data) {
                $count = $data['count'] ?? 0;
                $percentage = $data['percentage'] ?? 0;
                $sourcing_entries[] = ucfirst($source) . ": $count ({$percentage}%)";
            }
            $sourcing_string = implode(', ', $sourcing_entries);
        }

        // Prepare drop-off points with percentages
        $screened_dropoff = formatDropOff($drop_off_points['screened_dropoff'] ?? ['count' => 0, 'percentage' => 0]);
        $interviewed_dropoff = formatDropOff($drop_off_points['interviewed_dropoff'] ?? ['count' => 0, 'percentage' => 0]);
        $offered_dropoff = formatDropOff($drop_off_points['offered_dropoff'] ?? ['count' => 0, 'percentage' => 0]);

        // Write row to CSV with actual numbers and percentages
        fputcsv($output, [
            $job_title,
            $totalApplications,
            formatPercentage($application_metrics['screened'] ?? 0, $totalApplications),
            formatPercentage($application_metrics['interviewed'] ?? 0, $totalApplications),
            formatPercentage($application_metrics['offered'] ?? 0, $totalApplications),
            formatPercentage($application_metrics['deployed'] ?? 0, $totalApplications),
            $screened_dropoff,
            $interviewed_dropoff,
            $offered_dropoff,
            $time_to_fill ?? 'N/A',
            $sourcing_string
        ]);
    }
}

fclose($output);
exit();

// Functions to retrieve data and format it

function getApplicationMetrics($conn, $job_id)
{
    $query = "SELECT 
                COUNT(*) as total, 
                SUM(application_status = 'SCREENING') as screened, 
                SUM(application_status = 'INTERVIEW') as interviewed, 
                SUM(application_status = 'OFFERED') as offered, 
                SUM(application_status = 'DEPLOYED') as deployed 
              FROM applications WHERE job_id = $job_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result) ?? [];
}

function getTimeToFill($conn, $job_id)
{
    $query = "SELECT time_to_fill FROM tbl_job_metrics WHERE job_id = $job_id";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['time_to_fill'] ?? 'N/A';
}

function getSourcingAnalytics($conn, $job_id, $totalApplications)
{
    $query = "SELECT referral_source, COUNT(*) as count FROM applications 
              WHERE job_id = $job_id GROUP BY referral_source";
    $result = mysqli_query($conn, $query);
    $sourcing_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $count = $row['count'];
        $percentage = round(($count / $totalApplications) * 100, 2);
        $sourcing_data[$row['referral_source']] = ['count' => $count, 'percentage' => $percentage];
    }
    return $sourcing_data;
}

function getCandidateDropOffPoints($conn, $job_id, $totalApplications)
{
    $query = "SELECT 
                COUNT(CASE WHEN screened_at IS NOT NULL AND interviewed_at IS NULL THEN 1 END) AS screened_dropoff,
                COUNT(CASE WHEN interviewed_at IS NOT NULL AND offered_at IS NULL THEN 1 END) AS interviewed_dropoff,
                COUNT(CASE WHEN offered_at IS NOT NULL AND deployed_at IS NULL THEN 1 END) AS offered_dropoff
              FROM tbl_pipeline_stage AS ps
              JOIN applications AS a ON ps.application_id = a.application_id
              WHERE a.job_id = $job_id";
    $result = mysqli_query($conn, $query);
    $drop_off_points = mysqli_fetch_assoc($result) ?? [];
    foreach ($drop_off_points as $stage => &$count) {
        $count = ['count' => $count, 'percentage' => round(($count / $totalApplications) * 100, 2)];
    }
    return $drop_off_points;
}

function formatPercentage($count, $total)
{
    $percentage = round(($count / $total) * 100, 2);
    return "$percentage% ($count)";
}

function formatDropOff($dropOff)
{
    return "{$dropOff['count']} ({$dropOff['percentage']}%)";
}
