<?php
include '../db.php';  // Include database connection

// Check if the session is already initialized
if (isset($_SESSION['email']) && isset($_SESSION['role']) && $_SESSION['role'] == 'RECRUITER') {
    // If applicant session is already set, redirect to the dashboard
    header('Location: dashboard.php');
    exit();
}

// Check if login form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Fetch recruiter by email
    $stmt = prepare_and_execute($conn, "SELECT * FROM users WHERE email = ? AND role = 'RECRUITER'", 's', $email);
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if ($user && password_verify($password, $user['password'])) {
        // Set session and login the recruiter
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];

        // Update last login time
        prepare_and_execute($conn, "UPDATE users SET last_login = NOW() WHERE user_id = ?", 'i', $user['user_id']);

        // Redirect to recruiter dashboard
        header('Location: dashboard.php');
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
    <title>Recruiter Login</title>
    <link rel="stylesheet" href="recruiter_styles.css"> <!-- Link to the external stylesheet for UI styling -->

</head>

<body class="login-page">
    <div class="login-container">
        <div class="login-logo-company">
            <img src="images/logo.png" alt="Company Logo" class="logo-login"> <!-- Update path to your logo -->
        </div>

        <div class="login-form">
            <div class="title-container">
                <h2>Bonafide Trainology Placement Services</h2>
                <h3>Recruiter Login</h3>
            </div>
            <form method="POST" action="index.php">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit">Login</button>
            </form>

            <?php if (isset($error)) {
                echo "<p class='error'>$error</p>";
            } ?>
        </div>
    </div>
</body>

</html>