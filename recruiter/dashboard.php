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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Recruiter Dashboard</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to external stylesheet -->
    <style>
        /* Inline styles for the dashboard UI (can be moved to styles.css) */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .content-area {
            padding: 2rem;
            margin-left: 220px; /* Adjust based on sidebar width */
            background-color: #fff;
            min-height: 100vh;
        }

        h2 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        p {
            color: #666;
            font-size: 1rem;
            margin-bottom: 2rem;
        }

        .logout-button {
            display: inline-block;
            padding: 0.7rem 1.2rem;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1rem;
        }

        .logout-button:hover {
            background-color: #0056b3;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .dashboard-card {
            background-color: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .dashboard-card h3 {
            font-size: 1.4rem;
            color: #007bff;
            margin-bottom: 1rem;
        }

        .dashboard-card p {
            color: #555;
            font-size: 1rem;
        }
    </style>
</head>
<body>
    <!-- Main content area -->
    <div class="content-area">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?>! You are logged in as a Recruiter.</h2>
        <p>This is your recruiter dashboard where you can manage users, track applicants, and more.</p>

        <!-- Dashboard overview cards -->
        <div class="dashboard-grid">
            <div class="dashboard-card">
                <h3>Manage Users</h3>
                <p>View, update, and manage all user profiles in the system.</p>
            </div>
            <div class="dashboard-card">
                <h3>Track Applicants</h3>
                <p>Monitor and update the progress of all applicants in the system.</p>
            </div>
            <div class="dashboard-card">
                <h3>Job Postings</h3>
                <p>Create, edit, and view job listings that are live on the platform.</p>
            </div>
            <div class="dashboard-card">
                <h3>Referral Program</h3>
                <p>Manage the referral program, track performance, and incentivize employees.</p>
            </div>
        </div>

        <!-- Logout button -->
        <a href="logout.php" class="logout-button">Logout</a>
    </div>

    <?php 
    // Include the footer component
    include 'footer.php'; 
    ?>
</body>
</html>
