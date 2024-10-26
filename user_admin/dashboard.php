<?php
include '../db.php'; // Database connection
include 'header.php';
include 'footer.php';

// Check if user is logged in and is a USER_ADMIN
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'USER_ADMIN') {
    header('Location: index.php'); // Redirect to login page if not an admin
    exit();
}

// Get the current admin's user_id
$current_user_id = $_SESSION['user_id'];

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $user_id = filter_var($_GET['delete_user'], FILTER_VALIDATE_INT); // Sanitize input

    if ($user_id && $user_id != $current_user_id) { // Prevent deleting own account
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $success_message = "User deleted successfully!";
        } else {
            $error_message = "Error deleting user: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $error_message = "You cannot delete your own account.";
    }
}

// Sorting functionality for each table's Status column
$applicant_sort = isset($_GET['applicant_sort']) && $_GET['applicant_sort'] === 'DESC' ? 'DESC' : 'ASC';
$recruiter_sort = isset($_GET['recruiter_sort']) && $_GET['recruiter_sort'] === 'DESC' ? 'DESC' : 'ASC';
$admin_sort = isset($_GET['admin_sort']) && $_GET['admin_sort'] === 'DESC' ? 'DESC' : 'ASC';

// Fetch users by role with dynamic sorting
function fetchUsersByRole($conn, $role, $status_sort, $current_user_id)
{
    $query = "SELECT * FROM users WHERE role = ? AND user_id != ? ORDER BY status $status_sort, email ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $role, $current_user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

// Fetch each type of user excluding the current USER_ADMIN
$applicants = fetchUsersByRole($conn, 'APPLICANT', $applicant_sort, $current_user_id);
$recruiters = fetchUsersByRole($conn, 'RECRUITER', $recruiter_sort, $current_user_id);
$user_admins = fetchUsersByRole($conn, 'USER_ADMIN', $admin_sort, $current_user_id);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Admin Dashboard</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">
        <h2>User Management</h2>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <a href="create_user.php" class="btn btn-primary mb-3">Create New User</a>

        <!-- Applicants Table -->
        <h4>Applicants</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Email</th>
                    <th>
                        <a href="?applicant_sort=<?php echo $applicant_sort === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                            Status <?php echo $applicant_sort === 'ASC' ? '▲' : '▼'; ?>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($applicants as $applicant): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($applicant['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                        <td><?php echo htmlspecialchars($applicant['status']); ?></td>
                        <td>
                            <a href="edit_user.php?user_id=<?php echo $applicant['user_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?delete_user=<?php echo $applicant['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Recruiters Table -->
        <h4>Recruiters</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Email</th>
                    <th>
                        <a href="?recruiter_sort=<?php echo $recruiter_sort === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                            Status <?php echo $recruiter_sort === 'ASC' ? '▲' : '▼'; ?>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recruiters as $recruiter): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($recruiter['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($recruiter['email']); ?></td>
                        <td><?php echo htmlspecialchars($recruiter['status']); ?></td>
                        <td>
                            <a href="edit_user.php?user_id=<?php echo $recruiter['user_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?delete_user=<?php echo $recruiter['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- User Admins Table -->
        <h4>User Admins</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Email</th>
                    <th>
                        <a href="?admin_sort=<?php echo $admin_sort === 'ASC' ? 'DESC' : 'ASC'; ?>" class="text-decoration-none">
                            Status <?php echo $admin_sort === 'ASC' ? '▲' : '▼'; ?>
                        </a>
                    </th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_admins as $user_admin): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user_admin['user_id']); ?></td>
                        <td><?php echo htmlspecialchars($user_admin['email']); ?></td>
                        <td><?php echo htmlspecialchars($user_admin['status']); ?></td>
                        <td>
                            <a href="edit_user.php?user_id=<?php echo $user_admin['user_id']; ?>" class="btn btn-warning btn-sm">Edit</a>
                            <a href="?delete_user=<?php echo $user_admin['user_id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>

</html>