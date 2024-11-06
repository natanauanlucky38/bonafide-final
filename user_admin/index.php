<?php
include '../db.php';  // Include database connection

// Initialize error message variable
$error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize inputs
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $password = trim($_POST['password']);

    // Query to fetch the user by email
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        // if (password_verify($password, $user['password'])) {  // Uncomment this if password verification is required
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        // Update last_login if the user is USER_ADMIN
        if ($user['role'] == 'USER_ADMIN') {
            $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $updateStmt->bind_param("i", $user['user_id']);
            $updateStmt->execute();
            $updateStmt->close();
        }

        // Redirect to user_admin dashboard
        header('Location: dashboard.php');
        exit();
    } else {
        $error_message = "No user found with that email."; // Moved inside else block
    }

    // Close the $stmt only if it was successfully prepared
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="user_admin_styles.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

</head>

<body class="index-main-content">
    <div class="index-container mt-5">
        <h1 class="text-center">Bonafide Trainology Placement Services</h1>
        <h2 class="text-center">Admin - Login</h2>
        <br>
        <hr>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                    </div>
                    <input type="email" class="form-control" name="email" required>
                </div>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                    </div>
                    <input type="password" class="form-control" name="password" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>

    </div>
</body>

</html>