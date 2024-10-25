<?php
// sidebar.php
?>

<div class="sidebar">
    <ul>
        <li><a href="view_job.php">Job Postings</a></li>
        <li><a href="referrals.php">Referrals</a></li>
        <li><a href="application.php">Applications</a></li>
        <li><a href="profile.php">Profile</a></li>
    </ul>
</div>

<style>
    /* Optional styling for the sidebar */
    .sidebar {
        width: 200px;
        padding: 10px;
        background-color: #f4f4f4;
    }

    .sidebar ul {
        list-style-type: none;
        padding: 0;
    }

    .sidebar ul li {
        margin: 5px 0;
    }

    .sidebar ul li a {
        text-decoration: none;
        color: #333;
        font-size: 16px;
    }

    .sidebar ul li a:hover {
        color: #007bff;
        /* Change color on hover */
    }
</style>