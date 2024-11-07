<?php
include '../db.php'; // Database connection
include 'header.php';


// Check if user is logged in and is a USER_ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'USER_ADMIN') {
    header('Location: index.php'); // Redirect to login page if not an admin
    exit();
}

// Handle Create User
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role = filter_var(trim($_POST['role']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($email && !empty($password) && $password === $confirm_password && in_array($role, ['RECRUITER', 'USER_ADMIN'])) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $status = 'ACTIVE'; // Set default status to ACTIVE

        $stmt = $conn->prepare("INSERT INTO users (email, password, role, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $email, $hashedPassword, $role, $status);

        if ($stmt->execute()) {
            $success_message = "User created successfully!";
        } else {
            $error_message = "Error creating user: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $error_message = "Please provide valid inputs, ensure passwords match.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create New User</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="user_admin_styles.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="create-user-main-content">
    <div class="create-user-container mt-5">
        <h2><i class="fas fa-user-plus"></i> Create New User</h2>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <form method="POST" class="mb-4">
            <div class="form-group">
                <label for="email">Email <i class="fas fa-envelope"></i></label>
                <input type="email" class="form-control" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password <i class="fas fa-lock"></i></label>
                <input type="password" class="form-control" name="password" required>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password <i class="fas fa-lock"></i></label>
                <input type="password" class="form-control" name="confirm_password" required>
            </div>
            <div class="form-group">
                <label for="role">Role <i class="fas fa-user-tag"></i></label>
                <select name="role" class="form-control" required>
                    <option value="RECRUITER">RECRUITER</option>
                    <?php if ($_SESSION['role'] == 'USER_ADMIN'): ?>
                        <option value="USER_ADMIN">USER_ADMIN</option>
                    <?php endif; ?>
                </select>
            </div>
            <a href="dashboard.php" class="btn btn-secondary mr-2"><i class="fas fa-arrow-left"></i> Back</a>
            <button type="submit" name="create_user" class="btn btn-primary"><i class="fas fa-user-plus"></i> Create User</button>
        </form>
    </div>
</body>
<?php include 'footer.php'; ?>

</html>