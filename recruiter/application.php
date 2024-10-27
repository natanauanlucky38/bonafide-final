<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'header.php';

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

include 'sidebar.php';

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
    <style>
        /* Styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
        }

        .container {
            width: 90%;
            margin: auto;
            padding: 20px;
        }

        .sort-dropdown {
            margin: 10px 0;
            font-size: 0.9em;
        }

        .status-section {
            margin-top: 30px;
        }

        /* Job card styling */
        .job-card {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .job-title {
            font-size: 1.5em;
            color: #333;
            margin-bottom: 5px;
        }

        .job-details {
            display: flex;
            justify-content: space-between;
            color: #555;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        /* Application card styling */
        .application-card {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-top: 10px;
            cursor: pointer;
        }

        .applicant-name {
            font-weight: bold;
            color: #333;
        }

        .no-applications {
            color: #888;
            font-style: italic;
            padding: 15px;
            text-align: center;
        }
    </style>
    <script>
        // Sorting function
        function sortCards(sortBy, type) {
            const container = document.getElementById(type + '-cards');
            const cards = Array.from(container.getElementsByClassName(type + '-card'));

            cards.sort((a, b) => {
                const aValue = a.getAttribute('data-' + sortBy).toLowerCase();
                const bValue = b.getAttribute('data-' + sortBy).toLowerCase();

                if (aValue < bValue) return -1;
                if (aValue > bValue) return 1;
                return 0;
            });

            // Clear container and re-append sorted cards
            container.innerHTML = '';
            cards.forEach(card => container.appendChild(card));
        }

        function onSortChange(selectElement, type) {
            const sortBy = selectElement.value;
            sortCards(sortBy, type);
        }

        // Redirect to process.php with application_id
        function redirectToProcess(applicationId) {
            window.location.href = 'view_application.php?application_id=' + applicationId;
        }
    </script>
</head>

<body>
    <div class="container">
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
                        <div class="job-card job-<?php echo strtolower($status); ?>-card"
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

                            <!-- Applications for this Job -->
                            <div class="sort-dropdown">
                                Sort Applications by:
                                <select onchange="onSortChange(this, 'application-<?php echo $job_id; ?>')">
                                    <option value="applicant_name">Applicant Name</option>
                                    <option value="application_status">Application Status</option>
                                    <option value="screening_result">Screening Result</option>
                                </select>
                            </div>
                            <div id="application-<?php echo $job_id; ?>-cards">
                                <?php foreach (['SCREENING', 'INTERVIEW', 'OFFERED', 'DEPLOYED', 'REJECTED', 'WITHDRAWN'] as $appStatus): ?>
                                    <h4><?php echo ucfirst(strtolower($appStatus)); ?> Applications</h4>
                                    <?php if (isset($applications[$job_id]['applications'][$appStatus])): ?>
                                        <?php foreach ($applications[$job_id]['applications'][$appStatus] as $application): ?>
                                            <div class="application-card application-<?php echo $job_id; ?>-card"
                                                data-applicant_name="<?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?>"
                                                data-application_status="<?php echo htmlspecialchars($application['display_status']); ?>"
                                                data-screening_result="<?php echo $application['screening_result'] ?? 'Pending'; ?>"
                                                onclick="redirectToProcess(<?php echo $application['application_id']; ?>)">
                                                <div class="applicant-name">
                                                    <?php echo htmlspecialchars($application['applicant_fname'] . ' ' . $application['applicant_lname']); ?>
                                                </div>
                                                <p>Status: <?php echo htmlspecialchars($application['display_status']); ?></p>
                                                <p>Screening Result: <?php echo $application['screening_result'] ?? 'Pending'; ?></p>
                                                <p>
                                                </p>
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
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>