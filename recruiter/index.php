<?php
// login.php for recruiters
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
    <link rel="stylesheet" href="styles.css"> <!-- Link to the external stylesheet for UI styling -->
    <style>
        /* Inline CSS for demo purposes. Move to styles.css for production */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .login-container {
            background-color: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 1rem;
        }

        h3 {
            text-align: center;
            color: #777;
            margin-bottom: 2rem;
        }

        .input-group {
            margin-bottom: 1.5rem;
        }

        .input-group input {
            width: 100%;
            padding: 0.8rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .input-group input:focus {
            border-color: #007bff;
            outline: none;
        }

        button {
            width: 100%;
            padding: 0.8rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .login-footer {
            text-align: center;
            margin-top: 1.5rem;
        }

        .login-footer a {
            color: #007bff;
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        .error {
            color: red;
            text-align: center;
            margin-bottom: 1rem;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <h2>Bonafide Trainology</h2>
        <h3>Recruiter Login</h3>
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

        <div class="login-footer">
            <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
            <p><a href="forgot_password.php">Forgot Password?</a></p>
        </div>
    </div>
</body>

</html>