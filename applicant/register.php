<?php
include '../db.php';  // Database connection

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);  // Secure password hash
    $confirm_password = $_POST['confirm_password'];
    $referral_code = trim($_POST['referral_code']); // Referral code input

    // Ensure password confirmation matches
    if ($_POST['password'] !== $confirm_password) {
        $error = "Passwords do not match!";
    } else {
        // Check if email already exists
        $stmt = prepare_and_execute($conn, "SELECT * FROM users WHERE email = ?", 's', $email);
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = "Email already registered.";
        } else {
            // Insert new applicant user into `users` table
            $role = 'APPLICANT';
            $sql = "INSERT INTO users (email, password, role, registration_date) VALUES (?, ?, ?, NOW())";
            $stmt = prepare_and_execute($conn, $sql, 'sss', $email, $password, $role);

            // Get the last inserted user_id
            $user_id = $conn->insert_id;

            // Initialize referral details
            $referrer_user_id = null;
            $points = 0;

            // Check if a referral code was provided during registration
            if (!empty($referral_code)) {
                // Get the referrer user ID based on the provided referral code
                $referrer_stmt = prepare_and_execute($conn, "SELECT user_id FROM profiles WHERE referral_code = ?", 's', $referral_code);
                $referrer_result = $referrer_stmt->get_result();

                if ($referrer_result->num_rows > 0) {
                    // Referral code is valid
                    $referrer_row = $referrer_result->fetch_assoc();
                    $referrer_user_id = $referrer_row['user_id'];

                    // Increment the points for the referrer
                    $increment_points_sql = "UPDATE referrals SET points = points + 1 WHERE referrer_user_id = ?";
                    prepare_and_execute($conn, $increment_points_sql, 'i', $referrer_user_id);
                    $points = 1;
                } else {
                    $error = "Invalid referral code.";
                }
            }

            // Insert the referral record for the new user
            $insert_new_user_referral_sql = "INSERT INTO referrals (referred_user_id, referrer_user_id, referral_code, points) VALUES (?, ?, ?, ?)";
            prepare_and_execute($conn, $insert_new_user_referral_sql, 'iisi', $user_id, $referrer_user_id, $referral_code, $points);

            // Store the session
            $_SESSION['email'] = $email;
            $_SESSION['user_id'] = $user_id;  // Store user_id for profile setup
            $_SESSION['role'] = 'APPLICANT';

            // Redirect to the profile setup page
            header('Location: setup_profile.php');
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Applicant Registration</title>
    <link rel="stylesheet" href="applicant_styles.css"> <!-- Include your CSS styles here -->

</head>

<body class="register-main-content">
    <div class="register-container">
        <h2>Applicant Registration</h2>
        <form method="POST" action="register.php">
            <input type="email" name="email" placeholder="Email" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
            <input type="text" name="referral_code" placeholder="Referral Code (if any)"><br> <!-- Referral code input -->
            <button type="submit">Register</button>
        </form>
        <?php if (isset($error)) {
            echo "<p style='color:red;'>$error</p>";
        } ?>
    </div>


</body>
<?php include 'footer.php'; ?>

</html>