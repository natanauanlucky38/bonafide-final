<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'header.php';

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

// Fetch all job posts with their statuses (ACTIVE, ARCHIVED, DRAFT)
$jobs_sql = "
    SELECT jp.job_id, jp.job_title, jp.status, jp.company, jp.location, jp.openings, jp.created_at, jp.deadline
    FROM job_postings jp
    ORDER BY jp.created_at DESC
";
$jobs_result = $conn->query($jobs_sql);

if (!$jobs_result) {
    echo "Error fetching job postings: " . $conn->error;
    exit();
}

// Fetch applications for all statuses, counting `APPLIED` as `SCREENING`
$applications_sql = "
    SELECT a.*, 
           IF(a.application_status = 'APPLIED', 'SCREENING', a.application_status) AS display_status, 
           p.fname AS applicant_fname, 
           p.lname AS applicant_lname, 
           a.resume AS resume_file, 
           p.profile_id, 
           jp.job_id, 
           jp.job_title
    FROM applications a 
    JOIN profiles p ON a.profile_id = p.profile_id
    JOIN job_postings jp ON a.job_id = jp.job_id
    ORDER BY jp.job_id, display_status
";
$applications_result = $conn->query($applications_sql);

if (!$applications_result) {
    echo "Error fetching applications: " . $conn->error;
    exit();
}

// Organize applications by job_id and display_status
$applications = [];
while ($row = $applications_result->fetch_assoc()) {
    $job_id = $row['job_id'];
    $status = $row['display_status'];

    // Group by job_id and display_status
    $applications[$job_id]['job_title'] = $row['job_title'];
    $applications[$job_id]['applications'][$status][] = $row;
}

// Organize jobs by status (ACTIVE, ARCHIVED, DRAFT)
$jobs = [
    'ACTIVE' => [],
    'ARCHIVED' => [],
    'DRAFT' => [],
];
while ($job = $jobs_result->fetch_assoc()) {
    $jobs[$job['status']][] = $job;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Applications</title>

    <script>
        // Toggle visibility of applications within each job using dropdown
        function toggleApplications(jobId) {
            const appSection = document.getElementById(`application-section-${jobId}`);
            const toggleAppBtn = document.getElementById(`toggle-app-btn-${jobId}`);

            if (appSection.style.display === 'none') {
                appSection.style.display = 'block';
                toggleAppBtn.textContent = 'Hide Applications';
            } else {
                appSection.style.display = 'none';
                toggleAppBtn.textContent = 'Show Applications';
            }
        }

        function onSortChange(selectElement, type) {
            const sortBy = selectElement.value;
            const container = document.getElementById(type + '-cards');
            const cards = Array.from(container.getElementsByClassName(type + '-card'));

            cards.sort((a, b) => {
                const aValue = a.getAttribute('data-' + sortBy).toLowerCase();
                const bValue = b.getAttribute('data-' + sortBy).toLowerCase();

                return aValue.localeCompare(bValue);
            });

            // Clear container and re-append sorted cards
            container.innerHTML = '';
            cards.forEach(card => container.appendChild(card));
        }

        function redirectToProcess(applicationId) {
            window.location.href = 'view_application.php?application_id=' + applicationId;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const highlightJobId = urlParams.get('highlight_job_id');
            const highlightApplicationId = urlParams.get('highlight_application_id');

            function highlightRow(elementId) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    element.style.backgroundColor = '#ffeb3b'; // Temporary highlight color
                    setTimeout(() => {
                        element.style.backgroundColor = ''; // Remove highlight after a delay
                    }, 500);
                }
            }

            if (highlightJobId) {
                highlightRow(`job-row-${highlightJobId}`);
            } else if (highlightApplicationId) {
                highlightRow(`application-row-${highlightApplicationId}`);
            }
        });
        // Automatically toggle down the application section and highlight the interview row
        if (highlightJobId && highlightApplicationId) {
            const appSection = document.getElementById(`application-section-${highlightJobId}`);
            const toggleAppBtn = document.getElementById(`toggle-app-btn-${highlightJobId}`);

            // Ensure the application section is open
            if (appSection && appSection.style.display === 'none') {
                toggleApplications(highlightJobId); // Open the section
            }

            // Wait a brief moment to ensure the section is open before highlighting
            setTimeout(() => {
                highlightRow(`application-row-${highlightApplicationId}`);
            }, 500); // Adjust the delay if needed for your application
        }
    </script>
</head>

<body class="application-main-content">
    <div class="applicant-container">
        <h1>Job Applications</h1>

        <?php foreach (['ACTIVE', 'ARCHIVED', 'DRAFT'] as $status): ?>
            <div class="status-section">
                <h2><?php echo ucfirst(strtolower($status)); ?> Jobs</h2>

                <!-- Dropdown for Sorting Job Cards in this Section -->
                <div class="sort-dropdown">
                    Sort by:
                    <select onchange="onSortChange(this, 'job-<?php echo strtolower($status); ?>')">
                        <option value="job_title">Title</option>
                        <option value="company">Company</option>
                        <option value="location">Location</option>
                        <option value="openings">Openings</option>
                        <option value="created_at">Created Date</option>
                        <option value="deadline">Deadline</option>
                    </select>
                </div>

                <div id="job-<?php echo strtolower($status); ?>-cards">
                    <?php foreach ($jobs[$status] as $job): ?>
                        <?php $job_id = $job['job_id']; ?>
                        <div id="job-row-<?php echo $job_id; ?>" class="job-card job-<?php echo strtolower($status); ?>-card"
                            data-job_title="<?php echo htmlspecialchars($job['job_title']); ?>"
                            data-company="<?php echo htmlspecialchars($job['company']); ?>"
                            data-location="<?php echo htmlspecialchars($job['location']); ?>"
                            data-openings="<?php echo $job['openings']; ?>"
                            data-created_at="<?php echo $job['created_at']; ?>"
                            data-deadline="<?php echo $job['deadline']; ?>">

                            <div class="job-title"><?php echo htmlspecialchars($job['job_title']); ?></div>
                            <div class="job-details">
                                <span>Company: <?php echo htmlspecialchars($job['company']); ?></span>
                                <span>Location: <?php echo htmlspecialchars($job['location']); ?></span>
                                <span>Openings: <?php echo $job['openings']; ?></span>
                                <span>Created: <?php echo $job['created_at']; ?></span>
                                <span>Deadline: <?php echo $job['deadline']; ?></span>
                            </div>

                            <!-- Dropdown for Applications -->
                            <button id="toggle-app-btn-<?php echo $job_id; ?>" class="toggle-btn" onclick="toggleApplications(<?php echo $job_id; ?>)">
                                Show Applications
                            </button>

                            <!-- Applications for this Job -->
                            <div id="application-section-<?php echo $job_id; ?>" style="display: none;">
                                <?php foreach (['SCREENING', 'INTERVIEW', 'OFFERED', 'DEPLOYED', 'REJECTED', 'WITHDRAWN'] as $appStatus): ?>
                                    <h4><?php echo ucfirst(strtolower($appStatus)); ?> Applications</h4>
                                    <?php if (isset($applications[$job_id]['applications'][$appStatus])): ?>
                                        <?php foreach ($applications[$job_id]['applications'][$appStatus] as $application): ?>
                                            <div id="application-row-<?php echo $application['application_id']; ?>"
                                                class="application-card application-<?php echo $job_id; ?>-card"
                                                data-applicant_name="<?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?>"
                                                data-application_status="<?php echo htmlspecialchars($application['display_status']); ?>"
                                                data-screening_result="<?php echo $application['screening_result'] ?? 'Pending'; ?>"
                                                onclick="redirectToProcess(<?php echo $application['application_id']; ?>)">

                                                <div class="applicant-name">
                                                    <?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?>
                                                </div>
                                                <p>Status: <?php echo htmlspecialchars($application['display_status']); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <p class="no-applications">No applications available for this status.</p>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <div style="height: 500px;"></div>
</body>
<?php include 'footer.php'; ?>

</html>

<?php
// Close the database connection
$conn->close();
?>