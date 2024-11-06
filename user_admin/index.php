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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.8.1/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .input-group {
            display: flex;
            align-items: center;
            width: 100%;
        }

        .input-group input[type="password"],
        .input-group input[type="email"] {
            flex: 1;
            padding-right: 2.5rem;
            /* Space for the eye icon */
        }

        .input-group .input-group-prepend .input-group-text {
            background-color: transparent;
            border: none;
        }

        .toggle-password {
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

        .toggle-password.active {
            color: #32cd32;
            /* Darker green when toggled */
        }
    </style>
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
                    <input type="password" class="form-control" name="password" id="password" required>
                    <i class="bi bi-eye toggle-password" onclick="togglePasswordVisibility()" id="toggleIcon"></i>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
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