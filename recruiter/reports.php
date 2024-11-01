<?php
// reports.php for recruiters
include '../db.php';  // Include database connection

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');  // Redirect to login page if not a recruiter
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

function getTimeToFill($conn, $job_id)
{
    $query = "SELECT time_to_fill FROM tbl_job_metrics WHERE job_id = $job_id";
    $result = mysqli_query($conn, $query);
    if ($result && $row = mysqli_fetch_assoc($result)) {
        return $row['time_to_fill'];
    }
    return null;
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

function getMostDesirableSkills($conn)
{
    $query = "SELECT skills, COUNT(*) as count FROM profile_details 
              WHERE skills IS NOT NULL AND skills != '' 
              GROUP BY skills ORDER BY count DESC LIMIT 5";
    $result = mysqli_query($conn, $query);
    $skills = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $skills[] = $row;
    }
    return $skills;
}

function getMostAppliedJobs($conn)
{
    $query = "SELECT j.job_title, COUNT(a.job_id) as applications 
              FROM applications a 
              JOIN job_postings j ON a.job_id = j.job_id 
              GROUP BY a.job_id ORDER BY applications DESC LIMIT 5";
    $result = mysqli_query($conn, $query);
    $jobs = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $jobs[] = $row;
    }
    return $jobs;
}

function getCandidateDropOffPoints($conn, $job_id)
{
    $query = "SELECT application_status, COUNT(*) as count FROM applications 
              WHERE job_id = $job_id AND (application_status = 'REJECTED' OR application_status = 'WITHDRAWN') 
              GROUP BY application_status";
    $result = mysqli_query($conn, $query);
    $drop_off_points = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $drop_off_points[$row['application_status']] = $row['count'];
    }
    return $drop_off_points;
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
            height: 350px;
        }

        @media (max-width: 768px) {
            .chart {
                height: 300px;
            }
        }

        @media (max-width: 576px) {
            .chart {
                height: 250px;
            }
        }
    </style>
</head>

<body>
    <div class="content-area">
        <div class="container-fluid">
            <h2>Job Reports</h2>
            <div id="accordion">
                <?php
                $mostDesirableSkills = getMostDesirableSkills($conn);
                $mostAppliedJobs = getMostAppliedJobs($conn);

                foreach ($jobs as $job):
                    $job_id = $job['job_id'];
                    $job_title = htmlspecialchars($job['job_title']);

                    $application_metrics = getApplicationMetrics($conn, $job_id);
                    $time_to_fill = getTimeToFill($conn, $job_id);
                    $sourcing_analytics = getSourcingAnalytics($conn, $job_id);
                    $pipeline_overview = getPipelineOverview($conn, $job_id);
                    $drop_off_points = getCandidateDropOffPoints($conn, $job_id);
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
                                    <div class="col-md-6">
                                        <h4>Metrics</h4>
                                        <p><strong>Time to Fill:</strong> <?php echo $time_to_fill ?? 'N/A'; ?> days</p>
                                        <p><strong>Total Applications:</strong> <?php echo $application_metrics['total'] ?? 0; ?></p>
                                        <p><strong>Screened:</strong> <?php echo $pipeline_overview['screened'] ?? 0; ?></p>
                                        <p><strong>Interviewed:</strong> <?php echo $pipeline_overview['interviewed'] ?? 0; ?></p>
                                        <p><strong>Offered:</strong> <?php echo $pipeline_overview['offered'] ?? 0; ?></p>
                                        <p><strong>Placed:</strong> <?php echo $pipeline_overview['placed'] ?? 0; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <h4>Drop-off Points</h4>
                                        <p><strong>Rejected:</strong> <?php echo $drop_off_points['REJECTED'] ?? 0; ?></p>
                                        <p><strong>Withdrawn:</strong> <?php echo $drop_off_points['WITHDRAWN'] ?? 0; ?></p>
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
                                    labels: Object.keys(<?php echo json_encode($sourcing_analytics); ?>),
                                    datasets: [{
                                        label: 'Candidate Referrals',
                                        data: Object.values(<?php echo json_encode($sourcing_analytics); ?>),
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

                <div class="row">
                    <div class="col-md-6">
                        <h4>Most Desirable Skills</h4>
                        <ul>
                            <?php foreach ($mostDesirableSkills as $skill): ?>
                                <li><?php echo htmlspecialchars($skill['skills']); ?> - <?php echo $skill['count']; ?> candidates</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h4>Most Sought After Jobs</h4>
                        <ul>
                            <?php foreach ($mostAppliedJobs as $job): ?>
                                <li><?php echo htmlspecialchars($job['job_title']); ?> - <?php echo $job['applications']; ?> applications</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>