<?php
// dashboard.php for recruiters
include '../db.php';  // Include database connection

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: login.php');  // Redirect to login page if not a recruiter
    exit();
}

// Include the header and sidebar components
include 'header.php';
include 'sidebar.php';

// Helper function to validate intervals
function validateInterval($interval)
{
    $allowed_intervals = ['1 MONTH', '3 MONTH', '12 MONTH'];
    return in_array($interval, $allowed_intervals) ? $interval : '1 MONTH';
}

// Database query functions with error handling

// Get total jobs count based on time period
function getTotalJobs($conn, $interval)
{
    $interval = validateInterval($interval);
    $query = "SELECT COUNT(*) AS count FROM job_postings WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL $interval)";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error in getTotalJobs query: " . mysqli_error($conn));
    }
    return mysqli_fetch_assoc($result)['count'] ?? 0;
}

// Get applicants' status counts (Applied, Offered, Deployed, Rejected, Withdrawn)
function getApplicationMetrics($conn)
{
    $query = "SELECT 
                COUNT(*) as total, 
                SUM(application_status = 'APPLIED') as applied, 
                SUM(application_status = 'OFFERED') as offered,
                SUM(application_status = 'DEPLOYED') as deployed,
                SUM(application_status = 'REJECTED') as rejected,
                SUM(application_status = 'WITHDRAWN') as withdrawn
              FROM applications";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error in getApplicationMetrics query: " . mysqli_error($conn));
    }
    return mysqli_fetch_assoc($result) ?? [];
}

// Get candidate referral counts
function getReferrals($conn)
{
    $query = "SELECT referral_source, COUNT(*) as count FROM applications 
              GROUP BY referral_source";
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

// Get sourcing analytics (candidate sources)
function getSourcingAnalytics($conn)
{
    $query = "SELECT referral_source, COUNT(*) as count FROM applications GROUP BY referral_source";
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

// Get pipeline overview metrics from tbl_job_metrics
function getPipelineOverview($conn)
{
    $query = "SELECT 
                SUM(screened_applicants) AS screened, 
                SUM(interviewed_applicants) AS interviewed, 
                SUM(offered_applicants) AS offered, 
                SUM(successful_placements) AS placed, 
                SUM(rejected_applicants) AS rejected
              FROM tbl_job_metrics";
    $result = mysqli_query($conn, $query);
    if (!$result) {
        die("Error in getPipelineOverview query: " . mysqli_error($conn));
    }
    return mysqli_fetch_assoc($result) ?? [];
}

// Get calendar events for job deadlines and interviews
function getCalendarEvents($conn)
{
    $events = [];

    // Fetch job deadlines
    $jobQuery = "SELECT job_title AS title, deadline AS date FROM job_postings WHERE deadline IS NOT NULL";
    $jobResult = mysqli_query($conn, $jobQuery);
    if (!$jobResult) {
        die("Error in jobQuery for calendar events: " . mysqli_error($conn));
    }
    while ($job = mysqli_fetch_assoc($jobResult)) {
        $events[] = [
            'title' => 'Deadline: ' . $job['title'],
            'start' => $job['date']
        ];
    }

    // Fetch interview schedules
    $interviewQuery = "SELECT application_id, interview_date AS date, interview_type AS type FROM tbl_interview";
    $interviewResult = mysqli_query($conn, $interviewQuery);
    if (!$interviewResult) {
        die("Error in interviewQuery for calendar events: " . mysqli_error($conn));
    }
    while ($interview = mysqli_fetch_assoc($interviewResult)) {
        $events[] = [
            'title' => 'Interview - ' . $interview['type'],
            'start' => $interview['date']
        ];
    }

    return $events;
}

// Fetch and prepare data for charts
$monthly_jobs = getTotalJobs($conn, '1 MONTH');
$quarterly_jobs = getTotalJobs($conn, '3 MONTH');
$yearly_jobs = getTotalJobs($conn, '12 MONTH');

$application_metrics = getApplicationMetrics($conn);
$referrals = getReferrals($conn);
$sourcing_analytics = getSourcingAnalytics($conn); // Added sourcing analytics
$pipeline_overview = getPipelineOverview($conn);
$calendar_events = getCalendarEvents($conn);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Recruiter Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.0/main.min.js"></script>
    <style>
        .chart-container {
            padding: 20px;
            margin-top: 20px;
        }
    </style>
</head>

<body>
    <div class="content-area">
        <div class="container-fluid">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?>! You are logged in as a Recruiter.</h2>
            <p>This is your recruiter dashboard where you can manage users, track applicants, and more.</p>

            <div class="row">
                <div class="col-md-6 chart-container">
                    <h4>Total Jobs Posted</h4>
                    <canvas id="totalJobsChart"></canvas>
                </div>
                <div class="col-md-6 chart-container">
                    <h4>Application Success Rate</h4>
                    <canvas id="applicationMetricsChart"></canvas>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 chart-container">
                    <h4>Candidate Referrals</h4>
                    <canvas id="referralsChart"></canvas>
                </div>
                <div class="col-md-6 chart-container">
                    <h4>Pipeline Overview</h4>
                    <canvas id="pipelineChart"></canvas>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 chart-container">
                    <h4>Sourcing Analytics</h4>
                    <canvas id="sourcingAnalyticsChart"></canvas>
                </div>
                <div class="col-md-6 chart-container">
                    <h4>Upcoming Deadlines and Interviews</h4>
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Total Jobs Data
        const totalJobsData = {
            labels: ['Monthly', 'Quarterly', 'Yearly'],
            datasets: [{
                label: 'Total Jobs',
                data: [<?php echo $monthly_jobs; ?>, <?php echo $quarterly_jobs; ?>, <?php echo $yearly_jobs; ?>],
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
            }]
        };

        // Application Metrics Data
        const applicationMetricsData = {
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
        };

        // Candidate Referrals Data
        const referralsData = {
            labels: Object.keys(<?php echo json_encode($referrals); ?>),
            datasets: [{
                label: 'Candidate Referrals',
                data: Object.values(<?php echo json_encode($referrals); ?>),
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
            }]
        };

        // Pipeline Overview Data
        const pipelineOverviewData = {
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
        };

        // Sourcing Analytics Data
        const sourcingAnalyticsData = {
            labels: Object.keys(<?php echo json_encode($sourcing_analytics); ?>),
            datasets: [{
                label: 'Sourcing Analytics',
                data: Object.values(<?php echo json_encode($sourcing_analytics); ?>),
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e']
            }]
        };

        // Initialize Charts
        new Chart(document.getElementById('totalJobsChart').getContext('2d'), {
            type: 'bar',
            data: totalJobsData
        });
        new Chart(document.getElementById('applicationMetricsChart').getContext('2d'), {
            type: 'pie',
            data: applicationMetricsData
        });
        new Chart(document.getElementById('referralsChart').getContext('2d'), {
            type: 'doughnut',
            data: referralsData
        });
        new Chart(document.getElementById('pipelineChart').getContext('2d'), {
            type: 'bar',
            data: pipelineOverviewData
        });
        new Chart(document.getElementById('sourcingAnalyticsChart').getContext('2d'), {
            type: 'doughnut',
            data: sourcingAnalyticsData
        });

        // FullCalendar setup
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?php echo json_encode($calendar_events); ?>
            });
            calendar.render();
        });
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>