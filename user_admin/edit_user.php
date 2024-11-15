<?php
include '../db.php'; // Include database connection
include 'header.php';

// Check if user is logged in and is a USER_ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'USER_ADMIN') {
    header('Location: index.php'); // Redirect to login page if not an admin
    exit();
}

// Handle Update User
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $user_id = filter_var($_POST['user_id'], FILTER_VALIDATE_INT);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $role = filter_var(trim($_POST['role']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $status = filter_var(trim($_POST['status']), FILTER_SANITIZE_FULL_SPECIAL_CHARS);

    if ($user_id && $email) {
        // Prepare and execute the SQL statement
        $stmt = $conn->prepare("UPDATE users SET email = ?, role = ?, status = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $email, $role, $status, $user_id);

        if ($stmt->execute()) {
            $success_message = "User updated successfully!";
        } else {
            $error_message = "Error updating user: " . htmlspecialchars($stmt->error);
        }

        $stmt->close();
    } else {
        $error_message = "Invalid user ID or email.";
    }
}

// Fetch user details for editing
if (isset($_GET['user_id'])) {
    $user_id = filter_var($_GET['user_id'], FILTER_VALIDATE_INT);

    if ($user_id) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (!$user) {
            die("User not found");
        }
        $stmt->close();
    } else {
        die("Invalid user ID.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="user_admin_styles.css"> <!-- Link to your CSS file -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="edit-user-main-content">
    <div class="edit-user-container mt-5">
        <h2><i class="fas fa-user-edit"></i> Edit User</h2>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Edit User Form -->
        <form method="POST">
            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
            <div class="form-group">
                <label for="email">Email <i class="fas fa-envelope"></i></label>
                <input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="form-group">
                <label for="role">Role <i class="fas fa-user-tag"></i></label>
                <select name="role" class="form-control" required>
                    <option value="RECRUITER" <?php echo ($user['role'] == 'RECRUITER') ? 'selected' : ''; ?>>RECRUITER</option>
                    <option value="APPLICANT" <?php echo ($user['role'] == 'APPLICANT') ? 'selected' : ''; ?>>APPLICANT</option>
                    <option value="USER_ADMIN" <?php echo ($user['role'] == 'USER_ADMIN') ? 'selected' : ''; ?>>USER_ADMIN</option>
                </select>
            </div>
            <div class="form-group">
                <label for="status">Status <i class="fas fa-check-circle"></i></label>
                <select name="status" class="form-control" required>
                    <option value="ACTIVE" <?php echo ($user['status'] == 'ACTIVE') ? 'selected' : ''; ?>>ACTIVE</option>
                    <option value="BANNED" <?php echo ($user['status'] == 'BANNED') ? 'selected' : ''; ?>>BANNED</option>
                </select>
            </div>
            <a href="dashboard.php" class="btn btn-secondary mr-2"><i class="fas fa-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update User</button>
        </form>
    </div>
</body>
<?php include 'footer.php'; ?>

</html>