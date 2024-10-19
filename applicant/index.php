<?php
// login.php
include '../db.php';  // Database connection

// Check if the session is already initialized
if (isset($_SESSION['email']) && isset($_SESSION['role']) && $_SESSION['role'] == 'APPLICANT') {
    // If applicant session is already set, redirect to the dashboard
    header('Location: dashboard.php');
    exit();
}

// Check if login form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch user by email
    $stmt = prepare_and_execute($conn, "SELECT * FROM users WHERE email = ?", 's', $email);
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Set session and login the user
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];

        // Update last login time
        prepare_and_execute($conn, "UPDATE users SET last_login = NOW() WHERE user_id = ?", 'i', $user['user_id']);

        // Redirect based on role
        if ($user['role'] == 'APPLICANT') {
            header('Location: dashboard.php');
        } else {
            echo "Recruiters are not allowed to login from this page.";
        }
        exit();
    } else {
        $error = "Invalid email or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Applicant Login</title>
</head>
<body>
    <h2>Applicant Login</h2>
    <form method="POST" action="index.php">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
    
    <!-- Add the registration link here -->
    <p>Don't have an account? <a href="register.php">Register here</a></p>

    <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
</body>
</html>
