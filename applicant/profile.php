<?php
include '../db.php'; // Include database connection
include 'sidebar.php';

// Ensure the user is logged in as an applicant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'APPLICANT') {
    header('Location: login.php');
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
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f0f2f5;
            color: #333;
            line-height: 1.6;
            padding: 20px;
            display: flex;
            justify-content: center;
        }

        .profile-container {
            max-width: 650px;
            background-color: #fff;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .profile-header {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 20px;
        }

        .profile-header h2 {
            font-size: 1.8rem;
        }

        .profile-info {
            padding: 20px;
        }

        .section-title {
            font-size: 1.3rem;
            color: #007bff;
            border-bottom: 1px solid #e0e0e0;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .profile-info p {
            margin-bottom: 12px;
            color: #555;
            font-size: 1rem;
        }

        .profile-info p strong {
            color: #333;
        }

        .profile-info a {
            color: #007bff;
            text-decoration: none;
        }

        .profile-info a:hover {
            text-decoration: underline;
        }

        .edit-button {
            text-align: center;
            padding: 15px;
            background-color: #f4f4f4;
            border-top: 1px solid #e0e0e0;
        }

        .edit-button a {
            text-decoration: none;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }

        .edit-button a:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
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

</html>

<?php
$conn->close();
?>