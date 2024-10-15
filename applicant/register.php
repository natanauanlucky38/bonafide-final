<?php
// register.php
include '../db.php';  // Database connection

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);  // Secure password hash
    $confirm_password = $_POST['confirm_password'];

    // Ensure password confirmation matches
    if (!password_verify($confirm_password, $password)) {
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
</head>
<body>
    <h2>Applicant Registration</h2>
    <form method="POST" action="register.php">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <input type="password" name="confirm_password" placeholder="Confirm Password" required><br>
        <button type="submit">Register</button>
    </form>
    <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
</body>
</html>
