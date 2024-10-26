<?php
include '../db.php';  // Include database connection

// Check if job_id is set
if (!isset($_GET['job_id'])) {
    echo json_encode(['error' => 'No job ID specified.']);
    exit();
}

$job_id = intval($_GET['job_id']);

// Get Application Metrics
function getApplicationMetrics($conn, $job_id)
{
    $query = "SELECT 
                COUNT(*) as total, 
                SUM(application_status = 'APPLIED') as applied, 
                SUM(application_status = 'OFFERED') as offered,
                SUM(application_status = 'DEPLOYED') as deployed,
                SUM(application_status = 'REJECTED') as rejected,
                SUM(application_status = 'WITHDRAWN') as withdrawn
              FROM applications
              WHERE job_id = $job_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result) ?? [];
}

// Get Candidate Referrals
function getReferrals($conn, $job_id)
{
    $query = "SELECT referral_source, COUNT(*) as count FROM applications 
              WHERE job_id = $job_id
              GROUP BY referral_source";
    $result = mysqli_query($conn, $query);

    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['referral_source']] = $row['count'];
    }
    return $data;
}

// Get Pipeline Overview
function getPipelineOverview($conn, $job_id)
{
    $query = "SELECT 
                screened_applicants AS screened, 
                interviewed_applicants AS interviewed, 
                offered_applicants AS offered, 
                successful_placements AS placed, 
                rejected_applicants AS rejected
              FROM tbl_job_metrics
              WHERE job_id = $job_id";
    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result) ?? [];
}

// Fetch data
$response = [
    'applicationMetrics' => getApplicationMetrics($conn, $job_id),
    'referrals' => getReferrals($conn, $job_id),
    'pipelineOverview' => getPipelineOverview($conn, $job_id)
];

// Return JSON response
echo json_encode($response);
