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

<!-- Main content area -->
<div class="content-area">
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['email']); ?>! You are logged in as a Recruiter.</h2>
    <p>This is your recruiter dashboard where you can manage users, track applicants, and more.</p>
    <a href="logout.php" class="logout-button">Logout</a>
</div>

<?php 
// Include the footer component
include 'footer.php'; 
?>
