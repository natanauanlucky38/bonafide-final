<?php
// reports.php for recruiters
include '../db.php';  // Include database connection

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: login.php');  // Redirect to login page if not a recruiter
    exit();
}

// Include the header and sidebar components
include 'header.php';
include 'sidebar.php';

// Fetch all jobs
$jobQuery = "SELECT job_id, job_title FROM job_postings";
$jobResult = mysqli_query($conn, $jobQuery);
$jobs = [];
while ($row = mysqli_fetch_assoc($jobResult)) {
    $jobs[] = $row;
}

// Database query functions
function getApplicationMetrics($conn, $job_id)
{
    $query = "SELECT 
                COUNT(*) as total, 
                SUM(application_status = 'APPLIED') as applied, 
                SUM(application_status = 'OFFERED') as offered, 
                SUM(application_status = 'DEPLOYED') as deployed, 
                SUM(application_status = 'REJECTED') as rejected, 
                SUM(application_status = 'WITHDRAWN') as withdrawn
              FROM applications WHERE job_id = $job_id";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error in getApplicationMetrics query: " . mysqli_error($conn));
    }
    return mysqli_fetch_assoc($result) ?? [];
}

function getReferrals($conn, $job_id)
{
    $query = "SELECT referral_source, COUNT(*) as count FROM applications 
              WHERE job_id = $job_id GROUP BY referral_source";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error in getReferrals query: " . mysqli_error($conn));
    }
    $data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $data[$row['referral_source']] = $row['count'];
    }
    return $data;
}

function getSourcingAnalytics($conn, $job_id)
{
    $query = "SELECT referral_source, COUNT(*) as count FROM applications 
              WHERE job_id = $job_id GROUP BY referral_source";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error in getSourcingAnalytics query: " . mysqli_error($conn));
    }
    $sourcing_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $sourcing_data[$row['referral_source']] = $row['count'];
    }
    return $sourcing_data;
}

function getPipelineOverview($conn, $job_id)
{
    $query = "SELECT 
                SUM(screened_applicants) AS screened, 
                SUM(interviewed_applicants) AS interviewed, 
                SUM(offered_applicants) AS offered, 
                SUM(successful_placements) AS placed, 
                SUM(rejected_applicants) AS rejected
              FROM tbl_job_metrics WHERE job_id = $job_id";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error in getPipelineOverview query: " . mysqli_error($conn));
    }
    return mysqli_fetch_assoc($result) ?? [];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recruiter Reports</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        .chart-container {
            padding: 15px;
            margin-top: 15px;
        }

        .chart {
            max-width: 100%;
            /* Ensure the chart is responsive */
            height: 350px;
            /* Adjusted height for consistency */
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .chart {
                height: 300px;
                /* Adjust for smaller screens */
            }
        }

        /* Further adjustments for very small devices */
        @media (max-width: 576px) {
            .chart {
                height: 250px;
                /* Further adjust height */
            }
        }
    </style>
</head>

<body>
    <div class="content-area">
        <div class="container-fluid">
            <h2>Job Reports</h2>
            <div id="accordion">

                <?php foreach ($jobs as $job):
                    $job_id = $job['job_id'];
                    $job_title = htmlspecialchars($job['job_title']);

                    // Fetch data for this job
                    $application_metrics = getApplicationMetrics($conn, $job_id);
                    $referrals = getReferrals($conn, $job_id);
                    $sourcing_analytics = getSourcingAnalytics($conn, $job_id);
                    $pipeline_overview = getPipelineOverview($conn, $job_id);
                ?>

                    <div class="card">
                        <div class="card-header" id="heading-<?php echo $job_id; ?>">
                            <h5 class="mb-0">
                                <button class="btn btn-link" data-toggle="collapse" data-target="#collapse-<?php echo $job_id; ?>" aria-expanded="false" aria-controls="collapse-<?php echo $job_id; ?>">
                                    <?php echo $job_title; ?>
                                </button>
                            </h5>
                        </div>

                        <div id="collapse-<?php echo $job_id; ?>" class="collapse" aria-labelledby="heading-<?php echo $job_id; ?>" data-parent="#accordion">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 chart-container">
                                        <h4>Application Success Rate</h4>
                                        <canvas id="applicationMetricsChart-<?php echo $job_id; ?>" class="chart"></canvas>
                                    </div>
                                    <div class="col-md-6 chart-container">
                                        <h4>Candidate Referrals</h4>
                                        <canvas id="referralsChart-<?php echo $job_id; ?>" class="chart"></canvas>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 chart-container">
                                        <h4>Pipeline Overview</h4>
                                        <canvas id="pipelineChart-<?php echo $job_id; ?>" class="chart"></canvas>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12 chart-container">
                                        <h4>Sourcing Analytics</h4>
                                        <canvas id="sourcingAnalyticsChart-<?php echo $job_id; ?>" class="chart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        $(document).ready(function() {
                            // Application Metrics Data
                            new Chart(document.getElementById('applicationMetricsChart-<?php echo $job_id; ?>').getContext('2d'), {
                                type: 'pie',
                                data: {
                                    labels: ['Applied', 'Offered', 'Deployed', 'Rejected', 'Withdrawn'],
                                    datasets: [{
                                        label: 'Application Success Rate',
                                        data: [
                                            <?php echo $application_metrics['applied'] ?? 0; ?>,
                                            <?php echo $application_metrics['offered'] ?? 0; ?>,
                                            <?php echo $application_metrics['deployed'] ?? 0; ?>,
                                            <?php echo $application_metrics['rejected'] ?? 0; ?>,
                                            <?php echo $application_metrics['withdrawn'] ?? 0; ?>
                                        ],
                                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#e74a3b', '#f6c23e']
                                    }]
                                }
                            });

                            // Candidate Referrals Data
                            new Chart(document.getElementById('referralsChart-<?php echo $job_id; ?>').getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: Object.keys(<?php echo json_encode($referrals); ?>),
                                    datasets: [{
                                        label: 'Candidate Referrals',
                                        data: Object.values(<?php echo json_encode($referrals); ?>),
                                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
                                    }]
                                }
                            });

                            // Pipeline Overview Data
                            new Chart(document.getElementById('pipelineChart-<?php echo $job_id; ?>').getContext('2d'), {
                                type: 'bar',
                                data: {
                                    labels: ['Screened', 'Interviewed', 'Offered', 'Placed', 'Rejected'],
                                    datasets: [{
                                        label: 'Pipeline Overview',
                                        data: [
                                            <?php echo $pipeline_overview['screened'] ?? 0; ?>,
                                            <?php echo $pipeline_overview['interviewed'] ?? 0; ?>,
                                            <?php echo $pipeline_overview['offered'] ?? 0; ?>,
                                            <?php echo $pipeline_overview['placed'] ?? 0; ?>,
                                            <?php echo $pipeline_overview['rejected'] ?? 0; ?>
                                        ],
                                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#e74a3b', '#f6c23e']
                                    }]
                                }
                            });

                            // Sourcing Analytics Data
                            new Chart(document.getElementById('sourcingAnalyticsChart-<?php echo $job_id; ?>').getContext('2d'), {
                                type: 'doughnut',
                                data: {
                                    labels: Object.keys(<?php echo json_encode($sourcing_analytics); ?>),
                                    datasets: [{
                                        label: 'Sourcing Analytics',
                                        data: Object.values(<?php echo json_encode($sourcing_analytics); ?>),
                                        backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
                                    }]
                                }
                            });
                        });
                    </script>

                <?php endforeach; ?>

            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>