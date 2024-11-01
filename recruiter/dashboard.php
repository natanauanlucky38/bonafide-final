<?php
// dashboard.php for recruiters
include '../db.php';  // Include database connection

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');  // Redirect to login page if not a recruiter
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
    $jobQuery = "SELECT job_id, job_title AS title, deadline AS date FROM job_postings WHERE deadline IS NOT NULL";
    $jobResult = mysqli_query($conn, $jobQuery);
    if (!$jobResult) {
        die("Error in jobQuery for calendar events: " . mysqli_error($conn));
    }
    while ($job = mysqli_fetch_assoc($jobResult)) {
        $events[] = [
            'title' => 'Deadline: ' . $job['title'],
            'start' => $job['date'],
            'url' => 'application.php?highlight_job_id=' . $job['job_id']
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
            'start' => $interview['date'],
            'url' => 'application.php?highlight_application_id=' . $interview['application_id']
        ];
    }

    return $events;
}

// Initial data fetch for the default interval
$default_interval = '1 MONTH';
$monthly_jobs = getTotalJobs($conn, $default_interval);
$quarterly_jobs = getTotalJobs($conn, '3 MONTH');
$yearly_jobs = getTotalJobs($conn, '12 MONTH');

$application_metrics = getApplicationMetrics($conn);
$referrals = getReferrals($conn);
$sourcing_analytics = getSourcingAnalytics($conn);
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
        /* General Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
        }

        .container-fluid {
            padding: 20px;
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 10px;
        }

        .content-area {
            max-width: 1200px;
            margin: auto;
        }

        /* Chart Container Styling */
        .chart-container {
            padding: 10px;
            margin-bottom: 15px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .chart-container h4 {
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        /* Compact Grid for Charts */
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .col-md-6 {
            flex: 1 1 48%;
            min-width: 320px;
        }

        /* Calendar Styling */
        #calendar {
            padding: 10px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="content-area">
        <div class="container-fluid">
            <h2>Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?>! You are logged in as a Recruiter.</h2>
            <p>Manage users, track applicants, and access analytics from your dashboard.</p>

            <!-- Dropdown for selecting the date range -->
            <div class="form-group">
                <label for="dateRange">Select Date Range:</label>
                <select id="dateRange" class="form-control" onchange="updateCharts()">
                    <option value="1 MONTH">Monthly</option>
                    <option value="3 MONTH">Quarterly</option>
                    <option value="12 MONTH">Yearly</option>
                </select>
            </div>

            <!-- Charts and Calendar in a Compact Grid -->
            <div class="row">
                <div class="col-md-6 chart-container">
                    <h4>Total Jobs Posted</h4>
                    <canvas id="totalJobsChart"></canvas>
                </div>
                <div class="col-md-6 chart-container">
                    <h4>Application Success Rate</h4>
                    <canvas id="applicationMetricsChart"></canvas>
                </div>
                <div class="col-md-6 chart-container">
                    <h4>Candidate Referrals</h4>
                    <canvas id="referralsChart"></canvas>
                </div>
                <div class="col-md-6 chart-container">
                    <h4>Pipeline Overview</h4>
                    <canvas id="pipelineChart"></canvas>
                </div>
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
        let currentInterval = '1 MONTH';

        function updateCharts() {
            currentInterval = document.getElementById('dateRange').value;

            // Fetch data using AJAX
            fetch(`get_chart_data.php?interval=${currentInterval}`)
                .then(response => response.json())
                .then(data => {
                    // Update charts with new data
                    updateTotalJobsChart(data.totalJobs);
                    updateApplicationMetricsChart(data.applicationMetrics);
                    updateReferralsChart(data.referrals);
                    updatePipelineChart(data.pipelineOverview);
                    updateSourcingAnalyticsChart(data.sourcingAnalytics);
                });
        }

        // Initialize Charts with initial data
        const totalJobsChart = new Chart(document.getElementById('totalJobsChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Monthly', 'Quarterly', 'Yearly'],
                datasets: [{
                    label: 'Total Jobs',
                    data: [<?php echo $monthly_jobs; ?>, <?php echo $quarterly_jobs; ?>, <?php echo $yearly_jobs; ?>],
                    backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc']
                }]
            }
        });

        const applicationMetricsChart = new Chart(document.getElementById('applicationMetricsChart').getContext('2d'), {
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

        const referralsChart = new Chart(document.getElementById('referralsChart').getContext('2d'), {
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

        const pipelineChart = new Chart(document.getElementById('pipelineChart').getContext('2d'), {
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

        const sourcingAnalyticsChart = new Chart(document.getElementById('sourcingAnalyticsChart').getContext('2d'), {
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

        // FullCalendar setup
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                events: <?php echo json_encode($calendar_events); ?>,
                eventClick: function(info) {
                    // Open the URL of the clicked event
                    window.location.href = info.event.url;
                }
            });
            calendar.render();
        });

        function updateTotalJobsChart(newData) {
            totalJobsChart.data.datasets[0].data = newData;
            totalJobsChart.update();
        }

        function updateApplicationMetricsChart(newData) {
            applicationMetricsChart.data.datasets[0].data = newData;
            applicationMetricsChart.update();
        }

        function updateReferralsChart(newData) {
            referralsChart.data.datasets[0].data = newData;
            referralsChart.update();
        }

        function updatePipelineChart(newData) {
            pipelineChart.data.datasets[0].data = newData;
            pipelineChart.update();
        }

        function updateSourcingAnalyticsChart(newData) {
            sourcingAnalyticsChart.data.datasets[0].data = newData;
            sourcingAnalyticsChart.update();
        }
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>