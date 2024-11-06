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
           SUM(a.application_status = 'REJECTED') AS rejected_count,
           SUM(a.application_status = 'WITHDRAWN') AS withdrawn_count
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
           SUM(application_status = 'REJECTED') AS rejected_count,
           SUM(application_status = 'WITHDRAWN') AS withdrawn_count
    FROM applications;
";
$total_result = $conn->query($total_sql)->fetch_assoc();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Applicant Tracking System</title>
    <link rel="stylesheet" href="recruiter_styles.css"> <!-- Link to your CSS file -->
    <script>
        // Toggle visibility of applicant information for each job
        function toggleApplicantInfo(jobId) {
            const appInfoSection = document.getElementById(`applicant-info-section-${jobId}`);
            if (appInfoSection.style.display === 'none') {
                appInfoSection.style.display = 'block';
            } else {
                appInfoSection.style.display = 'none';
            }
        }
    </script>
</head>

<body class="applicant_tracking-main-content">
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
                <div class="summary-metric">
                    <span class="summary-metric-number"><?php echo $total_result['withdrawn_count']; ?></span>
                    <div class="summary-metric-label">Withdrawn</div>
                </div>
            </div>
        </div>

        <?php while ($job = $job_result->fetch_assoc()): ?>
            <div class="job-box">
                <div class="job-header">
                    <span><?php echo htmlspecialchars($job['job_title']); ?></span>
                    <span class="job-company"><?php echo htmlspecialchars($job['company']); ?></span>
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
                    <div class="metric">
                        <span class="metric-number"><?php echo $job['withdrawn_count']; ?></span>
                        <div class="metric-label">Withdrawn</div>
                    </div>
                </div>

                <!-- Toggle Button for Applicant Information -->
                <button class="toggle-btn" onclick="toggleApplicantInfo(<?php echo $job['job_id']; ?>)">
                    Show Applicant Information
                </button>

                <!-- Applicant Information Section -->
                <div id="applicant-info-section-<?php echo $job['job_id']; ?>" class="applicant-info-section">
                    <div class="applicant-info">
                        <h3>Applicant Information</h3>

                        <?php
                        // Fetch applicants for the current job, including qualifications and skills
                        $applicant_sql = "
                        SELECT p.fname, p.lname, a.application_id, a.resume, a.referral_source,
                               GROUP_CONCAT(CASE WHEN pd.qualifications = 'qualification' THEN pd.detail_value END) AS qualifications,
                               GROUP_CONCAT(CASE WHEN pd.skills = 'skill' THEN pd.detail_value END) AS skills
                        FROM applications AS a
                        INNER JOIN profiles AS p ON a.profile_id = p.profile_id
                        LEFT JOIN profile_details AS pd ON pd.profile_id = p.profile_id
                        WHERE a.job_id = ?
                        GROUP BY a.application_id
                    ";
                        $applicant_stmt = $conn->prepare($applicant_sql);
                        $applicant_stmt->bind_param("i", $job['job_id']);
                        $applicant_stmt->execute();
                        $applicant_stmt->store_result();
                        $applicant_stmt->bind_result($fname, $lname, $application_id, $resume, $referral_source, $qualifications, $skills);

                        while ($applicant_stmt->fetch()):
                        ?>
                            <div class="applicant-details">
                                <div class="applicant-name"><strong>Name:</strong> <?php echo htmlspecialchars($fname . ' ' . $lname); ?></div>
                                <div class="applicant-resume"><strong>View Application:</strong>
                                    <a href="view_application.php?application_id=<?php echo htmlspecialchars($application_id); ?>">View Application</a>
                                </div>
                                <div class="applicant-referral"><strong>Referral:</strong> <?php echo htmlspecialchars($referral_source); ?></div>
                                <div class="applicant-qualifications"><strong>Qualifications:</strong> <?php echo htmlspecialchars($qualifications); ?></div>
                                <div class="applicant-skills"><strong>Skills:</strong> <?php echo htmlspecialchars($skills); ?></div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>

</body>
<?php include 'footer.php'; ?>

</html>

<?php
$conn->close();
?>