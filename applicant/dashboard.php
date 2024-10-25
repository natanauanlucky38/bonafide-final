<?php
// dashboard.php
include '../db.php';  // Database connection
include 'sidebar.php';

// Check if user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');  // Redirect to login page
    exit();
}

include 'header.php';

// Check if the user has already filled out the profile
$user_id = $_SESSION['user_id'];
$stmt = prepare_and_execute($conn, "SELECT * FROM profiles WHERE user_id = ?", 'i', $user_id);
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // If no profile exists, redirect to setup profile
    header('Location: setup_profile.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Applicant Dashboard</title>
</head>

<body>
    <h2>Welcome, <?php echo $_SESSION['email']; ?>!</h2>
    <p>This is your applicant dashboard.</p>
    <a href="logout.php">Logout</a>
</body>

</html>