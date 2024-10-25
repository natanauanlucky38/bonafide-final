<?php
// dashboard.php
include '../db.php';  // Database connection
include 'sidebar.php';

// Check if the user is logged in and is an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');  // Redirect to login page
    exit();
}

include 'header.php';

// Retrieve user ID from session
$user_id = $_SESSION['user_id'];

// Fetch fname and lname from the profiles table
$profile_sql = "SELECT fname, lname FROM profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();

if ($profile_result->num_rows > 0) {
    // Store fname and lname in the session for easy access
    $profile = $profile_result->fetch_assoc();
    $_SESSION['fname'] = $profile['fname'];
    $_SESSION['lname'] = $profile['lname'];
} else {
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
    <!-- Display first name and last name in the welcome message -->
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['fname']) . ' ' . htmlspecialchars($_SESSION['lname']); ?>!</h2>
    <p>This is your applicant dashboard.</p>
    <a href="logout.php">Logout</a>
</body>

</html>

<?php
$conn->close();
?>