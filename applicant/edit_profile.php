<?php
include '../db.php'; // Include database connection

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

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect and sanitize input data
    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $age = (int)$_POST['age'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $civil_status = $_POST['civil_status'];
    $linkedin_link = $_POST['linkedin_link'];
    $facebook_link = $_POST['facebook_link'];
    $education_level = $_POST['education_level'];
    $school_graduated = $_POST['school_graduated'];
    $year_graduated = $_POST['year_graduated'];
    $degree = $_POST['degree'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if password and confirm password match
    if ($password !== $confirm_password) {
        $error_message = "Password and Confirm Password do not match.";
    } else {
        // Begin transaction to update both profiles and users tables
        $conn->begin_transaction();

        try {
            // Update profiles table
            $update_profile_sql = "UPDATE profiles SET fname = ?, lname = ?, age = ?, phone = ?, address = ?, civil_status = ?, 
                                   linkedin_link = ?, facebook_link = ?, education_level = ?, school_graduated = ?, year_graduated = ?, degree = ? 
                                   WHERE user_id = ?";
            $profile_stmt = $conn->prepare($update_profile_sql);
            $profile_stmt->bind_param(
                "ssisssssssssi",
                $fname,
                $lname,
                $age,
                $phone,
                $address,
                $civil_status,
                $linkedin_link,
                $facebook_link,
                $education_level,
                $school_graduated,
                $year_graduated,
                $degree,
                $user_id
            );
            $profile_stmt->execute();

            // Update users table for email and password
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $update_user_sql = "UPDATE users SET email = ?, password = ? WHERE user_id = ?";
                $user_stmt = $conn->prepare($update_user_sql);
                $user_stmt->bind_param("ssi", $email, $hashed_password, $user_id);
            } else {
                $update_user_sql = "UPDATE users SET email = ? WHERE user_id = ?";
                $user_stmt = $conn->prepare($update_user_sql);
                $user_stmt->bind_param("si", $email, $user_id);
            }
            $user_stmt->execute();

            // Commit transaction
            $conn->commit();
            $success_message = "Profile updated successfully.";
            header('Location: profile.php'); // Redirect to profile page after update
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating profile: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
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

        form {
            padding: 80px;
        }

        label {
            display: block;
            margin-top: 10px;
            font-weight: bold;
            color: #555;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1rem;
            color: #333;
        }

        .password-container {
            display: flex;
            align-items: center;
            position: relative;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            cursor: pointer;
            font-size: 1.2rem;
            color: #666;
        }

        .message {
            margin: 10px 0;
            padding: 10px;
            border-radius: 5px;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .button-group {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }

        button,
        .back-button {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        button {
            background-color: #007bff;
            color: white;
        }

        button:hover {
            background-color: #0056b3;
        }

        .back-button {
            background-color: #e0e0e0;
            color: #333;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }

        .back-button:hover {
            background-color: #c0c0c0;
        }
    </style>
</head>

<body>
    <div class="profile-container">
        <div class="profile-header">
            <h2>Edit Profile</h2>
        </div>

        <!-- Display success or error messages -->
        <?php if (isset($success_message)): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Profile form -->
        <form action="edit_profile.php" method="POST">
            <label for="fname">First Name</label>
            <input type="text" id="fname" name="fname" value="<?php echo htmlspecialchars($profile['fname']); ?>" required>

            <label for="lname">Last Name</label>
            <input type="text" id="lname" name="lname" value="<?php echo htmlspecialchars($profile['lname']); ?>" required>

            <label for="age">Age</label>
            <input type="text" id="age" name="age" value="<?php echo htmlspecialchars($profile['age']); ?>" required>

            <label for="phone">Phone</label>
            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($profile['phone']); ?>" required>

            <label for="address">Address</label>
            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($profile['address']); ?>" required>

            <label for="civil_status">Civil Status</label>
            <select id="civil_status" name="civil_status" required>
                <option value="Single" <?php echo ($profile['civil_status'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                <option value="Married" <?php echo ($profile['civil_status'] == 'Married') ? 'selected' : ''; ?>>Married</option>
                <option value="Divorced" <?php echo ($profile['civil_status'] == 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                <option value="Widowed" <?php echo ($profile['civil_status'] == 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
            </select>

            <label for="linkedin_link">LinkedIn Profile</label>
            <input type="text" id="linkedin_link" name="linkedin_link" value="<?php echo htmlspecialchars($profile['linkedin_link']); ?>">

            <label for="facebook_link">Facebook Profile</label>
            <input type="text" id="facebook_link" name="facebook_link" value="<?php echo htmlspecialchars($profile['facebook_link']); ?>">

            <label for="education_level">Education Level</label>
            <select id="education_level" name="education_level" required>
                <option value="PRIMARY" <?php echo ($profile['education_level'] == 'PRIMARY') ? 'selected' : ''; ?>>Primary</option>
                <option value="SECONDARY" <?php echo ($profile['education_level'] == 'SECONDARY') ? 'selected' : ''; ?>>Secondary</option>
                <option value="TERTIARY" <?php echo ($profile['education_level'] == 'TERTIARY') ? 'selected' : ''; ?>>Tertiary</option>
                <option value="POSTGRADUATE" <?php echo ($profile['education_level'] == 'POSTGRADUATE') ? 'selected' : ''; ?>>Postgraduate</option>
            </select>

            <label for="school_graduated">School Graduated</label>
            <input type="text" id="school_graduated" name="school_graduated" value="<?php echo htmlspecialchars($profile['school_graduated']); ?>" required>

            <label for="year_graduated">Year Graduated</label>
            <input type="text" id="year_graduated" name="year_graduated" value="<?php echo htmlspecialchars($profile['year_graduated']); ?>" required>

            <label for="degree">Degree</label>
            <input type="text" id="degree" name="degree" value="<?php echo htmlspecialchars($profile['degree']); ?>" required>

            <h3>Account Settings</h3>
            <label for="email">Email</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>

            <label for="password">New Password</label>
            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Enter new password (optional)">
                <span class="toggle-password" onclick="togglePasswordVisibility('password')">👁️</span>
            </div>

            <label for="confirm_password">Confirm New Password</label>
            <div class="password-container">
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password (optional)">
                <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">👁️</span>
            </div>

            <div class="button-group">
                <button type="submit">Update Profile</button>
                <a href="profile.php" class="back-button">Back to Profile</a>
            </div>
        </form>
    </div>

    <script>
        function togglePasswordVisibility(id) {
            const passwordField = document.getElementById(id);
            const isPasswordVisible = passwordField.type === "text";
            passwordField.type = isPasswordVisible ? "password" : "text";
        }
    </script>
</body>

</html>

<?php
$conn->close();
?>