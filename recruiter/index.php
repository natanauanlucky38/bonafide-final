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
                    <input type="password" name="password" placeholder="Password" id="password" required>
                    <i class="bi bi-eye toggle-password" onclick="togglePasswordVisibility()" id="toggleIcon"></i>
                </div>
                <button type="submit">Login</button>
            </form>

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