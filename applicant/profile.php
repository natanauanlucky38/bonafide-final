<?php
include '../db.php'; // Include database connection
include 'sidebar.php';
include 'header.php';

// Ensure the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: index.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch profile information
$profile_sql = "SELECT * FROM profiles WHERE user_id = ?";
$profile_stmt = $conn->prepare($profile_sql);
$profile_stmt->bind_param("i", $user_id);
$profile_stmt->execute();
$profile_result = $profile_stmt->get_result();
$profile = $profile_result->fetch_assoc();

// Fetch user login information (email)
$user_sql = "SELECT email FROM users WHERE user_id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>View Profile</title>
    <link rel="stylesheet" href="applicant_styles.css"> <!-- Include your CSS styles here -->

</head>

<body class="view_profile-content">

    <div class="profile-container">
        <div class="profile-header">
            <h2>Profile Information</h2>
        </div>

        <div class="profile-info">
            <div>
                <h3 class="section-title">Personal Information</h3>
                <p><strong>First Name:</strong> <?php echo htmlspecialchars($profile['fname']); ?></p>
                <p><strong>Last Name:</strong> <?php echo htmlspecialchars($profile['lname']); ?></p>
                <p><strong>Age:</strong> <?php echo htmlspecialchars($profile['age']); ?></p>
                <p><strong>Civil Status:</strong> <?php echo htmlspecialchars($profile['civil_status']); ?></p>
            </div>

            <div>
                <h3 class="section-title">Contact Information</h3>
                <p><strong>Phone:</strong> <?php echo htmlspecialchars($profile['phone']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($profile['address']); ?></p>
                <p><strong>LinkedIn Profile:</strong>
                    <a href="<?php echo htmlspecialchars($profile['linkedin_link']); ?>" target="_blank">
                        <?php echo htmlspecialchars($profile['linkedin_link']); ?>
                    </a>
                </p>
                <p><strong>Facebook Profile:</strong>
                    <a href="<?php echo htmlspecialchars($profile['facebook_link']); ?>" target="_blank">
                        <?php echo htmlspecialchars($profile['facebook_link']); ?>
                    </a>
                </p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            </div>

            <div>
                <h3 class="section-title">Education Information</h3>
                <p><strong>Education Level:</strong> <?php echo htmlspecialchars($profile['education_level']); ?></p>
                <p><strong>School Graduated:</strong> <?php echo htmlspecialchars($profile['school_graduated']); ?></p>
                <p><strong>Year Graduated:</strong> <?php echo htmlspecialchars($profile['year_graduated']); ?></p>
                <p><strong>Degree:</strong> <?php echo htmlspecialchars($profile['degree']); ?></p>
            </div>
        </div>

        <div class="edit-button">
            <a href="edit_profile.php">Edit Profile</a>
        </div>
    </div>
</body>

<?php include 'footer.php'; ?>

</html>

<?php
$conn->close();
?>