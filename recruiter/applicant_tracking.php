<?php
include '../db.php'; // Database connection
include 'header.php';
include 'sidebar.php';

// Fetch job postings with applicant counts per stage for each job
$job_sql = "
    SELECT jp.job_id, jp.job_title, jp.company,
           COUNT(a.application_id) AS total_applicants,
           SUM(a.application_status = 'SCREENING') AS screening_count,
           SUM(a.application_status = 'INTERVIEW') AS interview_count,
           SUM(a.application_status = 'OFFERED') AS offered_count,
           SUM(a.application_status = 'DEPLOYED') AS deployed_count,
           SUM(a.application_status = 'REJECTED') AS rejected_count
    FROM job_postings AS jp
    LEFT JOIN applications AS a ON jp.job_id = a.job_id
    WHERE jp.status = 'ACTIVE'
    GROUP BY jp.job_id
    ORDER BY jp.created_at DESC;
";

$job_stmt = $conn->prepare($job_sql);
$job_stmt->execute();
$job_result = $job_stmt->get_result();

// Fetch total count across all jobs
$total_sql = "
    SELECT 
           COUNT(application_id) AS total_applicants,
           SUM(application_status = 'SCREENING') AS screening_count,
           SUM(application_status = 'INTERVIEW') AS interview_count,
           SUM(application_status = 'OFFERED') AS offered_count,
           SUM(application_status = 'DEPLOYED') AS deployed_count,
           SUM(application_status = 'REJECTED') AS rejected_count
    FROM applications;
";
$total_result = $conn->query($total_sql)->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Applicant Tracking System</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f1f5f8;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 20px;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 1000px;
            padding: 20px;
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
        }

        .summary-box {
            width: 100%;
            margin-bottom: 30px;
            background: #007bff;
            color: #ffffff;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }

        .summary-box h2 {
            font-size: 1.6em;
            margin-bottom: 20px;
        }

        .summary-metrics {
            display: flex;
            justify-content: space-around;
        }

        .summary-metric {
            text-align: center;
        }

        .summary-metric-number {
            font-size: 1.4em;
            font-weight: bold;
        }

        .summary-metric-label {
            font-size: 0.85em;
        }

        .job-box {
            background: #f8f9fa;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 1px solid #ddd;
        }

        .job-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 1.2em;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .metrics {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 1em;
        }

        .metric {
            text-align: center;
            flex: 1;
        }

        .metric-number {
            font-weight: bold;
            font-size: 1.3em;
        }

        .metric-label {
            font-size: 0.85em;
            color: #666;
        }

        .applicant-info {
            background-color: #ffffff;
            padding: 10px;
            border-radius: 8px;
            border: 1px solid #ddd;
            margin-top: 15px;
        }

        .applicant-info h3 {
            font-size: 1.2em;
            color: #333;
            margin-bottom: 10px;
        }

        .applicant-details {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .applicant-details:last-child {
            border-bottom: none;
        }

        .applicant-name,
        .applicant-resume,
        .applicant-referral {
            flex: 1;
            padding: 0 10px;
        }

        .applicant-resume a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 style="text-align: center; margin-bottom: 20px;">Applicant Tracking System</h1>

        <div class="summary-box">
            <h2>Total Applications Summary</h2>
            <div class="summary-metrics">
                <div class="summary-metric">
                    <span class="summary-metric-number"><?php echo $total_result['total_applicants']; ?></span>
                    <div class="summary-metric-label">Total Applicants</div>
                </div>
                <div class="summary-metric">
                    <span class="summary-metric-number"><?php echo $total_result['screening_count']; ?></span>
                    <div class="summary-metric-label">Screening</div>
                </div>
                <div class="summary-metric">
                    <span class="summary-metric-number"><?php echo $total_result['interview_count']; ?></span>
                    <div class="summary-metric-label">Interview</div>
                </div>
                <div class="summary-metric">
                    <span class="summary-metric-number"><?php echo $total_result['offered_count']; ?></span>
                    <div class="summary-metric-label">Offered</div>
                </div>
                <div class="summary-metric">
                    <span class="summary-metric-number"><?php echo $total_result['deployed_count']; ?></span>
                    <div class="summary-metric-label">Deployed</div>
                </div>
                <div class="summary-metric">
                    <span class="summary-metric-number"><?php echo $total_result['rejected_count']; ?></span>
                    <div class="summary-metric-label">Rejected</div>
                </div>
            </div>
        </div>

        <?php while ($job = $job_result->fetch_assoc()): ?>
            <div class="job-box">
                <div class="job-header">
                    <span><?php echo htmlspecialchars($job['job_title']); ?></span>
                    <span style="font-size: 0.9em; color: #777;"><?php echo htmlspecialchars($job['company']); ?></span>
                </div>

                <div class="metrics">
                    <div class="metric">
                        <span class="metric-number"><?php echo $job['total_applicants']; ?></span>
                        <div class="metric-label">Total Applicants</div>
                    </div>
                    <div class="metric">
                        <span class="metric-number"><?php echo $job['screening_count']; ?></span>
                        <div class="metric-label">Screening</div>
                    </div>
                    <div class="metric">
                        <span class="metric-number"><?php echo $job['interview_count']; ?></span>
                        <div class="metric-label">Interview</div>
                    </div>
                    <div class="metric">
                        <span class="metric-number"><?php echo $job['offered_count']; ?></span>
                        <div class="metric-label">Offered</div>
                    </div>
                    <div class="metric">
                        <span class="metric-number"><?php echo $job['deployed_count']; ?></span>
                        <div class="metric-label">Deployed</div>
                    </div>
                    <div class="metric">
                        <span class="metric-number"><?php echo $job['rejected_count']; ?></span>
                        <div class="metric-label">Rejected</div>
                    </div>
                </div>

                <!-- Applicant Information Section -->
                <div class="applicant-info">
                    <h3>Applicant Information</h3>

                    <?php
                    // Fetch applicants for the current job
                    $applicant_sql = "
                    SELECT p.fname, p.lname, a.resume, a.referral_source
                    FROM applications AS a
                    INNER JOIN profiles AS p ON a.profile_id = p.profile_id
                    WHERE a.job_id = ?
                ";
                    $applicant_stmt = $conn->prepare($applicant_sql);
                    $applicant_stmt->bind_param("i", $job['job_id']);
                    $applicant_stmt->execute();
                    $applicant_result = $applicant_stmt->get_result();

                    while ($applicant = $applicant_result->fetch_assoc()):
                    ?>
                        <div class="applicant-details">
                            <div class="applicant-name"><strong>Name:</strong> <?php echo htmlspecialchars($applicant['fname'] . ' ' . $applicant['lname']); ?></div>
                            <div class="applicant-resume"><strong>Resume:</strong>
                                <a href="<?php echo htmlspecialchars($applicant['resume']); ?>" target="_blank">View</a>
                            </div>
                            <div class="applicant-referral"><strong>Referral:</strong> <?php echo htmlspecialchars($applicant['referral_source']); ?></div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

</body>

</html>

<?php
$conn->close();
?>