<?php
// login.php for recruiters
include '../db.php';  // Include database connection

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
</head>
<body>
    <h2>Recruiter Login</h2>
    <form method="POST" action="login.php">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
    
    <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
</body>
</html>
