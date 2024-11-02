<?php
require('../fpdf/fpdf.php'); // Adjust the path as necessary
include '../db.php'; // Include database connection

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php'); // Redirect to login page if not a recruiter
    exit();
}

// Verify database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch all jobs
$jobQuery = "SELECT job_id, job_title FROM job_postings";
$jobResult = mysqli_query($conn, $jobQuery);
$jobs = [];

if (!$jobResult) {
    die("Error in jobQuery: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_assoc($jobResult)) {
    $jobs[] = $row;
}

// Database query functions
function getApplicationMetrics($conn, $job_id)
{
    $query = "SELECT 
                COUNT(*) as total, 
                SUM(application_status = 'APPLIED') as applied, 
                SUM(application_status = 'SCREENING') as screened, 
                SUM(application_status = 'INTERVIEW') as interviewed, 
                SUM(application_status = 'OFFERED') as offered, 
                SUM(application_status = 'DEPLOYED') as deployed, 
                SUM(application_status = 'REJECTED') as rejected, 
                SUM(application_status = 'WITHDRAWN') as withdrawn
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

// Create PDF
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 16);
$pdf->Cell(0, 10, 'Job Reports', 0, 1, 'C');

$pdf->SetFont('Arial', 'I', 12);
foreach ($jobs as $job) {
    $job_id = $job['job_id'];
    $job_title = htmlspecialchars($job['job_title']);

    // Fetch metrics for each job
    $application_metrics = getApplicationMetrics($conn, $job_id);
    $time_to_fill = getTimeToFill($conn, $job_id);

    // Prepare data
    $totalApplications = $application_metrics['total'] ?? 0;
    $screened = $application_metrics['screened'] ?? 0;
    $interviewed = $application_metrics['interviewed'] ?? 0;
    $offered = $application_metrics['offered'] ?? 0;
    $deployed = $application_metrics['deployed'] ?? 0;

    // Add Job Title
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Cell(0, 10, $job_title, 0, 1);

    // Add Metrics
    $pdf->SetFont('Arial', '', 12);
    $pdf->Cell(0, 10, "Time to Fill: $time_to_fill days", 0, 1);
    $pdf->Cell(0, 10, "Total Applications: $totalApplications", 0, 1);
    $pdf->Cell(0, 10, "Screened: $screened", 0, 1);
    $pdf->Cell(0, 10, "Interviewed: $interviewed", 0, 1);
    $pdf->Cell(0, 10, "Offered: $offered", 0, 1);
    $pdf->Cell(0, 10, "Deployed: $deployed", 0, 1);

    $pdf->Ln(5); // Line break
}

// Output the PDF
$pdf->Output('D', 'Job_Reports.pdf'); // Download the PDF
exit();
