<?php
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

    // Check if user exists
    if ($user) {
        // Check if user is banned
        if ($user['status'] === 'BANNED') {
            $error = "Your account has been banned. Please contact support for assistance.";
        } else {
            // If user is not banned, verify the password
            if (password_verify($password, $user['password'])) {
                // Set session and login the user
                $_SESSION['email'] = $user['email'];
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['role'] = $user['role'];

                // Update last login time
                prepare_and_execute($conn, "UPDATE users SET last_login = NOW() WHERE user_id = ?", 'i', $user['user_id']);

                // Redirect based on role
                if ($user['role'] == 'APPLICANT') {
                    // Check if profile is set up
                    $profile_check_stmt = prepare_and_execute($conn, "SELECT profile_id FROM profiles WHERE user_id = ?", 'i', $user['user_id']);
                    $profile_check_result = $profile_check_stmt->get_result();

                    if ($profile_check_result->num_rows == 0) {
                        // If no profile found, redirect to profile setup page
                        header('Location: profile_setup.php');
                    } else {
                        // Profile exists, proceed to dashboard
                        header('Location: dashboard.php');
                    }
                } else {
                    echo "Recruiters are not allowed to login from this page.";
                }
                exit();
            } else {
                $error = "Invalid email or password!";
            }
        }
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
    <link rel="stylesheet" href="applicant_styles.css"> <!-- Link to your CSS file -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .input-group {
            position: relative;
            width: 100%;
        }

        .input-group input[type="password"],
        .input-group input[type="email"] {
            width: 100%;
            padding-right: 2.5rem;
        }

        .input-group .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.2rem;
            color: #90ee90;
            /* Light green color for the eye icon */
            transition: color 0.3s ease;
            /* Smooth transition for color change */
        }

        .input-group .toggle-password.active {
            color: #32cd32;
            /* Darker green when toggled */
        }
    </style>
</head>

<body class="login-page">
    <div class="login-container">
        <!-- Logo Section -->
        <div class="login-logo-company">
            <img src="images/logo.png" alt="Company Logo" class="logo-login">
        </div>

        <!-- Login Form Section -->
        <div class="login-form">
            <div class="title-container">
                <h2>Bonafide Trainology Placement Services</h2>
                <h3>Applicant Login</h3>
            </div>
            <form method="POST" action="index.php">
                <div class="input-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="input-group">
                    <input type="password" name="password" placeholder="Password" id="password" required>
                    <i class="bi bi-eye toggle-password" onclick="togglePasswordVisibility()" id="toggleIcon"></i>
                </div>
                <button type="submit">Login</button>
            </form>

            <!-- Registration Link -->
            <p>Don't have an account? <a href="register.php">Register here</a></p>

            <?php if (isset($error)) {
                echo "<p class='error'>$error</p>";
            } ?>
        </div>
    </div>

    <script>
        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.getElementById('toggleIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.add('active'); // Apply active class for darker green color
                toggleIcon.classList.replace('bi-eye', 'bi-eye-slash'); // Change to eye-slash icon
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('active'); // Revert to light green
                toggleIcon.classList.replace('bi-eye-slash', 'bi-eye'); // Change back to eye icon
            }
        }
    </script>
</body>

</html>