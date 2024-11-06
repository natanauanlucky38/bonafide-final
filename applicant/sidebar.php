<?php
// sidebar.php
?>

<!-- Sidebar Toggle Button (Hamburger Icon) -->
<button class="sidebar-toggle" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar -->
<div class="sidebar">
    <ul class="sidebar_list">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="view_job.php">Job Postings</a></li>
        <li><a href="referrals.php">Referrals</a></li>
        <li><a href="application.php">Applications</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>
</div>

<!-- Link to Font Awesome for icons -->
<link rel="stylesheet" href="applicant_styles.css"> <!-- Link to your CSS file -->

<script>
    function toggleSidebar() {
        document.querySelector('.sidebar').classList.toggle('sidebar-hidden');
    }
</script>