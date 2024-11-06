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

// Fetch all jobs including created_at and deadline
$jobQuery = "SELECT job_id, job_title, created_at, deadline FROM job_postings";
$jobResult = mysqli_query($conn, $jobQuery);

if (!$jobResult) {
    die("Error in jobQuery: " . mysqli_error($conn));
}

$jobs = [];
while ($row = mysqli_fetch_assoc($jobResult)) {
    $jobs[] = $row; // This will now include created_at and deadline
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
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/0.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.2/jspdf.min.js"></script>
</head>

<body class="reports-main-content">
    <div class="reports-content-area">
        <div class="reports-container-fluid">
            <h2>Job Reports</h2>

            <div class="d-flex mb-3">
                <a href="download_reports.php" class="btn btn-primary mr-2">Download Report as CSV</a>
                <button onclick="downloadPDF()" class="btn btn-primary">Download Report as PDF</button>
            </div>

            <!-- Historical Average Time-to-Fill Graph -->
            <h4>Historical Average Time to Fill</h4>
            <div class="col-md-6 mb-3">
                <canvas id="historicalTimeToFillChart"></canvas>
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
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            datalabels: {
                                display: true,
                                align: 'top',
                                color: 'black',
                                font: {
                                    weight: 'bold'
                                },
                                formatter: (value) => `${value} days`
                            }
                        },
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
                    },
                    plugins: [ChartDataLabels]
                });
            </script>

            <!-- Job Reports Section -->
            <div id="accordion">
                <?php foreach ($jobs as $job): ?>
                    <?php
                    $job_id = $job['job_id'];
                    $job_title = htmlspecialchars($job['job_title']);
                    $created_at = htmlspecialchars($job['created_at']);
                    $deadline = htmlspecialchars($job['deadline']);
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
                                <p><strong>Created At:</strong> <?php echo $created_at; ?></p>
                                <p><strong>Deadline:</strong> <?php echo $deadline; ?></p>

                                <!-- Job Metrics Chart -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <canvas id="jobChart-<?php echo $job_id; ?>"></canvas>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <canvas id="avgStageTimeChart-<?php echo $job_id; ?>"></canvas>
                                    </div>
                                </div>

                                <script>
                                    // Job Metrics Chart
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
                                            plugins: {
                                                datalabels: {
                                                    display: true,
                                                    align: 'top',
                                                    color: 'black',
                                                    font: {
                                                        weight: 'bold'
                                                    },
                                                    formatter: (value) => `${value} (${((value / <?php echo $totalApplications; ?>) * 100).toFixed(2)}%)`
                                                }
                                            },
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
                                        },
                                        plugins: [ChartDataLabels]
                                    });

                                    // Average Stage Time Chart
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
                                            plugins: {
                                                datalabels: {
                                                    display: true,
                                                    align: 'top',
                                                    color: 'black',
                                                    font: {
                                                        weight: 'bold'
                                                    },
                                                    formatter: (value) => `${value} days`
                                                }
                                            },
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
                                        },
                                        plugins: [ChartDataLabels]
                                    });
                                </script>

                                <!-- Sourcing Analytics Chart (Horizontal Bar) -->
                                <h4>Sourcing Analytics</h4>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <canvas id="sourcingChart-<?php echo $job_id; ?>"></canvas>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <canvas id="dropOffChart-<?php echo $job_id; ?>"></canvas>
                                    </div>
                                </div>
                                <script>
                                    var ctxSourcing<?php echo $job_id; ?> = document.getElementById('sourcingChart-<?php echo $job_id; ?>').getContext('2d');
                                    var sourcingLabels<?php echo $job_id; ?> = <?php echo json_encode(array_column($sourcing_analytics, 'source')); ?>;
                                    var sourcingData<?php echo $job_id; ?> = <?php echo json_encode(array_column($sourcing_analytics, 'count')); ?>;
                                    var sourcingPercentages<?php echo $job_id; ?> = <?php echo json_encode(array_column($sourcing_analytics, 'percentage')); ?>;

                                    var sourcingChart<?php echo $job_id; ?> = new Chart(ctxSourcing<?php echo $job_id; ?>, {
                                        type: 'bar',
                                        data: {
                                            labels: sourcingLabels<?php echo $job_id; ?>,
                                            datasets: [{
                                                label: 'Candidates by Source',
                                                data: sourcingData<?php echo $job_id; ?>,
                                                backgroundColor: 'rgba(54, 162, 235, 0.6)',
                                                borderColor: 'rgba(54, 162, 235, 1)',
                                                borderWidth: 1
                                            }]
                                        },
                                        options: {
                                            indexAxis: 'y', // Horizontal bar chart
                                            responsive: true,
                                            plugins: {
                                                datalabels: {
                                                    display: true,
                                                    align: 'right',
                                                    color: 'black',
                                                    font: {
                                                        weight: 'bold'
                                                    },
                                                    formatter: (value, ctx) => `${value} (${sourcingPercentages<?php echo $job_id; ?>[ctx.dataIndex]}%)`
                                                }
                                            },
                                            scales: {
                                                x: {
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Count'
                                                    }
                                                },
                                                y: {
                                                    title: {
                                                        display: true,
                                                        text: 'Source'
                                                    }
                                                }
                                            }
                                        },
                                        plugins: [ChartDataLabels]
                                    });

                                    // Drop-off Points Chart
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
                                            plugins: {
                                                datalabels: {
                                                    display: true,
                                                    align: 'top',
                                                    color: 'black',
                                                    font: {
                                                        weight: 'bold'
                                                    },
                                                    formatter: (value) => `${value} (${((value / <?php echo $totalApplications; ?>) * 100).toFixed(2)}%)`
                                                }
                                            },
                                            scales: {
                                                y: {
                                                    beginAtZero: true,
                                                    title: {
                                                        display: true,
                                                        text: 'Count'
                                                    }
                                                }
                                            }
                                        },
                                        plugins: [ChartDataLabels]
                                    });
                                </script>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <script>
        async function downloadPDF() {
            const pdf = new jsPDF();
            const jobCards = document.querySelectorAll('.card');

            for (let i = 0; i < jobCards.length; i++) {
                const jobCard = jobCards[i];
                const jobDetail = jobCard.querySelector('.card-body');

                // Get job title and other information
                const jobTitleElement = jobCard.querySelector('.btn-link');
                const createdAtElement = jobDetail.querySelector('p:nth-of-type(3)');
                const deadlineElement = jobDetail.querySelector('p:nth-of-type(4)');

                const jobTitle = jobTitleElement ? jobTitleElement.innerText : 'Job Title Not Found';
                const createdAt = createdAtElement ? createdAtElement.innerText : 'Created At Not Available';
                const deadline = deadlineElement ? deadlineElement.innerText : 'Deadline Not Available';

                if (i > 0) pdf.addPage(); // New page for each job section if needed

                // Add job details to PDF
                pdf.setFontSize(16);
                pdf.text(`Job Report: ${jobTitle}`, 10, 15);
                pdf.setFontSize(12);
                pdf.text(`Created At: ${createdAt}`, 10, 25);
                pdf.text(`Deadline: ${deadline}`, 10, 35);

                // Locate all charts within the current job details section
                const charts = jobDetail.querySelectorAll('canvas');
                let yOffset = 50;

                // Convert each chart to an image and add it to the PDF
                for (const chart of charts) {
                    if (chart) {
                        try {
                            await new Promise((resolve) => setTimeout(resolve, 500)); // Small delay to ensure charts are rendered
                            const imgData = chart.toDataURL('image/png'); // Capture chart as an image
                            const imgWidth = 160; // Adjust width for the PDF
                            const imgHeight = (chart.height * imgWidth) / chart.width; // Keep aspect ratio

                            pdf.addImage(imgData, 'PNG', 10, yOffset, imgWidth, imgHeight);
                            yOffset += imgHeight + 10; // Move down for the next chart

                            if (yOffset > 250) { // Check if space on page is exceeded
                                pdf.addPage(); // Add a new page for the next chart if needed
                                yOffset = 20; // Reset yOffset for new page
                            }
                        } catch (error) {
                            console.error(`Error rendering chart image for ${jobTitle}:`, error);
                            pdf.text("Error rendering chart image.", 10, yOffset);
                            yOffset += 10;
                        }
                    } else {
                        console.warn(`Chart not found in job report for ${jobTitle}`);
                    }
                }
            }

            // Save the PDF file
            pdf.save('full_report_with_charts.pdf');
            console.log("PDF generated successfully with all charts, labels, and values.");
        }
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>