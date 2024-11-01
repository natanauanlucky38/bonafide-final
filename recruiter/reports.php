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

// Verify database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Fetch all jobs
$jobQuery = "SELECT job_id, job_title FROM job_postings";
$jobResult = mysqli_query($conn, $jobQuery);

if (!$jobResult) {
    die("Error in jobQuery: " . mysqli_error($conn));
}

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

function getHistoricalAverageTimeToFillData($conn)
{
    $query = "SELECT DATE_FORMAT(jp.created_at, '%Y-%m') AS month, AVG(jm.time_to_fill) AS avg_time_to_fill
              FROM job_postings AS jp
              JOIN tbl_job_metrics AS jm ON jp.job_id = jm.job_id
              GROUP BY month
              ORDER BY month";
    $result = mysqli_query($conn, $query);
    $historical_data = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $historical_data[] = $row;
    }
    return $historical_data;
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
        $sourcing_data[] = ['source' => $row['referral_source'], 'count' => $count, 'percentage' => $percentage];
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

    // Calculate percentages for each drop-off point
    foreach ($drop_off_points as $stage => $count) {
        $percentage = $totalApplications > 0 ? round(($count / $totalApplications) * 100, 2) : 0;
        $drop_off_points[$stage] = ['count' => $count, 'percentage' => $percentage];
    }

    return $drop_off_points;
}

function getAverageStageTimes($conn, $job_id)
{
    $query = "SELECT 
                AVG(duration_applied_to_screened) AS avg_screened, 
                AVG(duration_screened_to_interviewed) AS avg_interviewed, 
                AVG(duration_interviewed_to_offered) AS avg_offered, 
                AVG(duration_offered_to_hired) AS avg_hired 
              FROM tbl_pipeline_stage AS ps
              JOIN applications AS a ON ps.application_id = a.application_id
              WHERE a.job_id = $job_id";

    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result) ?? [];
}

$historical_data = getHistoricalAverageTimeToFillData($conn);
$months = array_column($historical_data, 'month');
$avg_times = array_column($historical_data, 'avg_time_to_fill');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recruiter Reports</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <div class="content-area">
        <div class="container-fluid">
            <h2>Job Reports</h2>

            <a href="download_reports.php" class="btn btn-primary mb-3">Download Report as CSV</a>

            <!-- Historical Average Time-to-Fill Graph -->
            <h4>Historical Average Time to Fill</h4>
            <div>
                <canvas id="historicalTimeToFillChart" width="400" height="200"></canvas>
            </div>
            <script>
                var ctx = document.getElementById('historicalTimeToFillChart').getContext('2d');
                var historicalTimeToFillChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($months); ?>,
                        datasets: [{
                            label: 'Average Time to Fill (Days)',
                            data: <?php echo json_encode($avg_times); ?>,
                            borderColor: 'rgba(75, 192, 192, 1)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                beginAtZero: true,
                                title: {
                                    display: true,
                                    text: 'Days'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Month'
                                }
                            }
                        }
                    }
                });
            </script>

            <div id="accordion">
                <?php
                foreach ($jobs as $job) {
                    $job_id = $job['job_id'];
                    $job_title = htmlspecialchars($job['job_title']);
                    $application_metrics = getApplicationMetrics($conn, $job_id);
                    $time_to_fill = getTimeToFill($conn, $job_id);
                    $totalApplications = $application_metrics['total'] ?? 1; // Avoid division by zero

                    $sourcing_analytics = getSourcingAnalytics($conn, $job_id, $totalApplications);
                    $drop_off_points = getCandidateDropOffPoints($conn, $job_id, $totalApplications);
                    $average_stage_times = getAverageStageTimes($conn, $job_id);

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
                                <h4>Metrics</h4>
                                <p><strong>Time to Fill:</strong> <?php echo $time_to_fill; ?> days</p>
                                <p><strong>Total Applications:</strong> <?php echo $totalApplications; ?></p>

                                <!-- Job Metrics Chart -->
                                <div>
                                    <canvas id="jobChart-<?php echo $job_id; ?>" width="400" height="200"></canvas>
                                </div>
                                <script>
                                    var ctxJob<?php echo $job_id; ?> = document.getElementById('jobChart-<?php echo $job_id; ?>').getContext('2d');
                                    var jobChart<?php echo $job_id; ?> = new Chart(ctxJob<?php echo $job_id; ?>, {
                                        type: 'bar',
                                        data: {
                                            labels: ['Screened', 'Interviewed', 'Offered', 'Deployed'],
                                            datasets: [{
                                                label: 'Job Metrics',
                                                data: [
                                                    <?php echo $application_metrics['screened'] ?? 0; ?>,
                                                    <?php echo $application_metrics['interviewed'] ?? 0; ?>,
                                                    <?php echo $application_metrics['offered'] ?? 0; ?>,
                                                    <?php echo $application_metrics['deployed'] ?? 0; ?>
                                                ],
                                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                                borderColor: 'rgba(54, 162, 235, 1)',
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Count'
                                                    }
                                                },
                                                x: {
                                                    title: {
                                                        display: true,
                                                        text: 'Stages'
                                                    }
                                                }
                                            }
                                        }
                                    });
                                </script>

                                <!-- Average Time in Each Stage Chart -->
                                <h4>Average Time in Each Stage</h4>
                                <div>
                                    <canvas id="avgStageTimeChart-<?php echo $job_id; ?>" width="400" height="200"></canvas>
                                </div>
                                <script>
                                    var ctxAvgStageTime<?php echo $job_id; ?> = document.getElementById('avgStageTimeChart-<?php echo $job_id; ?>').getContext('2d');
                                    var avgStageTimeChart<?php echo $job_id; ?> = new Chart(ctxAvgStageTime<?php echo $job_id; ?>, {
                                        type: 'bar',
                                        data: {
                                            labels: ['Screened', 'Interviewed', 'Offered', 'Hired'],
                                            datasets: [{
                                                label: 'Average Time (days)',
                                                data: [
                                                    <?php echo round($average_stage_times['avg_screened'], 2) ?? 0; ?>,
                                                    <?php echo round($average_stage_times['avg_interviewed'], 2) ?? 0; ?>,
                                                    <?php echo round($average_stage_times['avg_offered'], 2) ?? 0; ?>,
                                                    <?php echo round($average_stage_times['avg_hired'], 2) ?? 0; ?>
                                                ],
                                                backgroundColor: 'rgba(75, 192, 192, 0.6)',
                                                borderColor: 'rgba(75, 192, 192, 1)',
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Days'
                                                    }
                                                },
                                                x: {
                                                    title: {
                                                        display: true,
                                                        text: 'Stages'
                                                    }
                                                }
                                            }
                                        }
                                    });
                                </script>

                                <!-- Sourcing Analytics Chart -->
                                <h4>Sourcing Analytics</h4>
                                <div>
                                    <canvas id="sourcingChart-<?php echo $job_id; ?>" width="400" height="200"></canvas>
                                </div>
                                <script>
                                    var ctxSourcing<?php echo $job_id; ?> = document.getElementById('sourcingChart-<?php echo $job_id; ?>').getContext('2d');
                                    var sourcingLabels<?php echo $job_id; ?> = <?php echo json_encode(array_column($sourcing_analytics, 'source')); ?>;
                                    var sourcingData<?php echo $job_id; ?> = <?php echo json_encode(array_column($sourcing_analytics, 'count')); ?>;
                                    var sourcingChart<?php echo $job_id; ?> = new Chart(ctxSourcing<?php echo $job_id; ?>, {
                                        type: 'pie',
                                        data: {
                                            labels: sourcingLabels<?php echo $job_id; ?>,
                                            datasets: [{
                                                label: 'Candidates by Source',
                                                data: sourcingData<?php echo $job_id; ?>,
                                                backgroundColor: ['rgba(255, 99, 132, 0.6)', 'rgba(54, 162, 235, 0.6)', 'rgba(255, 206, 86, 0.6)', 'rgba(75, 192, 192, 0.6)'],
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            responsive: true
                                        }
                                    });
                                </script>

                                <!-- Drop-off Points Chart -->
                                <h4>Drop-off Points</h4>
                                <div>
                                    <canvas id="dropOffChart-<?php echo $job_id; ?>" width="400" height="200"></canvas>
                                </div>
                                <script>
                                    var ctxDropOff<?php echo $job_id; ?> = document.getElementById('dropOffChart-<?php echo $job_id; ?>').getContext('2d');
                                    var dropOffLabels<?php echo $job_id; ?> = ['Screened Drop-off', 'Interviewed Drop-off', 'Offered Drop-off'];
                                    var dropOffData<?php echo $job_id; ?> = [
                                        <?php echo $drop_off_points['screened_dropoff']['count'] ?? 0; ?>,
                                        <?php echo $drop_off_points['interviewed_dropoff']['count'] ?? 0; ?>,
                                        <?php echo $drop_off_points['offered_dropoff']['count'] ?? 0; ?>
                                    ];
                                    var dropOffChart<?php echo $job_id; ?> = new Chart(ctxDropOff<?php echo $job_id; ?>, {
                                        type: 'bar',
                                        data: {
                                            labels: dropOffLabels<?php echo $job_id; ?>,
                                            datasets: [{
                                                label: 'Drop-off Counts',
                                                data: dropOffData<?php echo $job_id; ?>,
                                                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                                                borderColor: 'rgba(255, 99, 132, 1)',
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            responsive: true,
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Count'
                                                    }
                                                }
                                            }
                                        }
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>