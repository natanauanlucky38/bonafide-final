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

function getSourcingAnalytics($conn, $job_id)
{
    $query = "SELECT referral_source, COUNT(*) as count FROM applications 
              WHERE job_id = $job_id GROUP BY referral_source";
    $result = mysqli_query($conn, $query);
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
    return mysqli_fetch_assoc($result) ?? [];
}

function getMostDesirableSkills($conn)
{
    $query = "SELECT detail_value AS skill, COUNT(*) as count FROM profile_details 
              JOIN applications ON profile_details.profile_id = applications.profile_id 
              WHERE applications.application_status = 'DEPLOYED' 
              AND skills IS NOT NULL 
              GROUP BY skill 
              ORDER BY count DESC 
              LIMIT 10";
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
    $query = "SELECT 
                COUNT(CASE WHEN screened_at IS NOT NULL AND interviewed_at IS NULL THEN 1 END) AS screened_dropoff,
                COUNT(CASE WHEN interviewed_at IS NOT NULL AND offered_at IS NULL THEN 1 END) AS interviewed_dropoff,
                COUNT(CASE WHEN offered_at IS NOT NULL AND deployed_at IS NULL THEN 1 END) AS offered_dropoff
              FROM tbl_pipeline_stage AS ps
              JOIN applications AS a ON ps.application_id = a.application_id
              WHERE a.job_id = $job_id";

    $result = mysqli_query($conn, $query);
    return mysqli_fetch_assoc($result) ?? [];
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
                $mostDesirableSkills = getMostDesirableSkills($conn);
                $mostAppliedJobs = getMostAppliedJobs($conn);

                foreach ($jobs as $job) {
                    $job_id = $job['job_id'];
                    $job_title = htmlspecialchars($job['job_title']);
                    $application_metrics = getApplicationMetrics($conn, $job_id);
                    $time_to_fill = getTimeToFill($conn, $job_id);
                    $sourcing_analytics = getSourcingAnalytics($conn, $job_id);
                    $pipeline_overview = getPipelineOverview($conn, $job_id);
                    $drop_off_points = getCandidateDropOffPoints($conn, $job_id);
                    $average_stage_times = getAverageStageTimes($conn, $job_id);

                    $total = $application_metrics['total'] ?? 1; // Avoid division by zero
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
                                <p><strong>Total Applications:</strong> <?php echo $application_metrics['total'] ?? 0; ?></p>
                                <p><strong>Screened:</strong> <?php echo round(($application_metrics['screened'] / $total) * 100, 2); ?>% (<?php echo $application_metrics['screened'] ?? 0; ?>)</p>
                                <p><strong>Interviewed:</strong> <?php echo round(($application_metrics['interviewed'] / $total) * 100, 2); ?>% (<?php echo $application_metrics['interviewed'] ?? 0; ?>)</p>
                                <p><strong>Offered:</strong> <?php echo round(($application_metrics['offered'] / $total) * 100, 2); ?>% (<?php echo $application_metrics['offered'] ?? 0; ?>)</p>
                                <p><strong>Deployed:</strong> <?php echo round(($application_metrics['deployed'] / $total) * 100, 2); ?>% (<?php echo $application_metrics['deployed'] ?? 0; ?>)</p>

                                <h4>Average Time in Each Stage</h4>
                                <p><strong>Screened:</strong> <?php echo round($average_stage_times['avg_screened'], 2) ?? 'N/A'; ?> days</p>
                                <p><strong>Interviewed:</strong> <?php echo round($average_stage_times['avg_interviewed'], 2) ?? 'N/A'; ?> days</p>
                                <p><strong>Offered:</strong> <?php echo round($average_stage_times['avg_offered'], 2) ?? 'N/A'; ?> days</p>
                                <p><strong>Hired:</strong> <?php echo round($average_stage_times['avg_hired'], 2) ?? 'N/A'; ?> days</p>

                                <h4>Sourcing Analytics</h4>
                                <ul>
                                    <?php foreach ($sourcing_analytics as $source => $count): ?>
                                        <li><?php echo ucfirst($source); ?>: <?php echo $count; ?> candidates</li>
                                    <?php endforeach; ?>
                                </ul>

                                <h4>Drop-off Points</h4>
                                <p><strong>Screened Drop-off:</strong> <?php echo $drop_off_points['screened_dropoff'] ?? 0; ?></p>
                                <p><strong>Interviewed Drop-off:</strong> <?php echo $drop_off_points['interviewed_dropoff'] ?? 0; ?></p>
                                <p><strong>Offered Drop-off:</strong> <?php echo $drop_off_points['offered_dropoff'] ?? 0; ?></p>
                            </div>
                        </div>
                    </div>
                <?php } ?>

                <h4>Most Sought-After Skills (Based on Successful Deployments)</h4>
                <ul>
                    <?php foreach ($mostDesirableSkills as $skill): ?>
                        <li><?php echo htmlspecialchars($skill['skill']); ?> - <?php echo $skill['count']; ?> deployments</li>
                    <?php endforeach; ?>
                </ul>

                <h4>Most Sought After Jobs</h4>
                <ul>
                    <?php foreach ($mostAppliedJobs as $job): ?>
                        <li><?php echo htmlspecialchars($job['job_title']); ?> - <?php echo $job['applications']; ?> applications</li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>

</html>