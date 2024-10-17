<?php
// Include necessary files
include '../db.php';  // Include database connection

// Check if user is logged in and is a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: login.php');  // Redirect to login page if not a recruiter
    exit();
}

// Default sort and search options
$searchQuery = '';
$sortField = 'last_login'; // Default sort field
$sortOrder = 'DESC'; // Default sort order

// Handle search
if (isset($_GET['search'])) {
    $searchQuery = trim($_GET['search']);
}

// Handle sorting
if (isset($_GET['sort']) && isset($_GET['order'])) {
    $sortField = $_GET['sort'];
    $sortOrder = strtoupper($_GET['order']) === 'ASC' ? 'ASC' : 'DESC';
}

// Handle mass actions (delete, activate, deactivate)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mass_action'])) {
    $action = $_POST['mass_action'];
    $selectedUsers = $_POST['selected_users'] ?? [];

    if (!empty($selectedUsers)) {
        $ids = implode(',', array_map('intval', $selectedUsers));

        // Perform mass action
        if ($action === 'delete') {
            $sql = "DELETE FROM users WHERE user_id IN ($ids)";
        } elseif ($action === 'activate') {
            $sql = "UPDATE users SET status = 'ACTIVE' WHERE user_id IN ($ids)";
        } elseif ($action === 'deactivate') {
            $sql = "UPDATE users SET status = 'INACTIVE' WHERE user_id IN ($ids)";
        }

        // Execute the action
        $conn->query($sql);
    }
}

// Fetch applicant accounts and profiles
$sql = "
    SELECT u.user_id, u.email, u.last_login, u.status, 
           p.fname, p.lname, p.age, p.phone, p.address, 
           p.civil_status, p.linkedin_link, p.facebook_link, 
           p.referral_code, p.education_level, p.school_graduated, p.year_graduated
    FROM users u
    LEFT JOIN profiles p ON u.user_id = p.user_id
    WHERE u.role = 'APPLICANT'
    AND (u.email LIKE ? OR p.fname LIKE ? OR p.lname LIKE ?)
    ORDER BY $sortField $sortOrder
";

$searchTerm = "%$searchQuery%";
$stmt = prepare_and_execute($conn, $sql, 'sss', $searchTerm, $searchTerm, $searchTerm);
$results = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .content-area {
            padding: 2rem;
            margin-left: 220px;
        }

        h2 {
            color: #333;
            font-size: 1.8rem;
            margin-bottom: 1rem;
        }

        .mass-action {
            margin: 1.5rem 0;
            display: flex;
            align-items: center;
        }

        .mass-action select, 
        .mass-action button {
            padding: 0.5rem 1rem;
            font-size: 1rem;
            margin-right: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .mass-action button {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
        }

        .mass-action button:hover {
            background-color: #0056b3;
        }

        input[type="text"] {
            padding: 0.5rem 1rem;
            font-size: 1rem;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 1rem;
            width: 300px;
        }

        button[type="submit"] {
            padding: 0.5rem 1rem;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
        }

        button[type="submit"]:hover {
            background-color: #0056b3;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }

        th, td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f4f4f9;
        }

        th a {
            color: #007bff;
            text-decoration: none;
        }

        th a:hover {
            text-decoration: underline;
        }

        td input[type="checkbox"] {
            transform: scale(1.2);
        }
    </style>
</head>
<body>

<?php include 'header.php'; ?>
<?php include 'sidebar.php'; ?>

<div class="content-area">
    <h2>User Management - Applicants</h2>

    <!-- Search Form -->
    <form method="GET" action="user_management.php">
        <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Search by name, email, or status">
        <button type="submit">Search</button>
    </form>

    <!-- Mass Action Form -->
    <form method="POST" action="user_management.php">
        <div class="mass-action">
            <select name="mass_action">
                <option value="activate">Activate</option>
                <option value="deactivate">Deactivate</option>
                <option value="delete">Delete</option>
            </select>
            <button type="submit">Apply</button>
        </div>

        <!-- Display Applicant Accounts Table -->
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                    <th><a href="?sort=fname&order=<?php echo ($sortField == 'fname' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">First Name</a></th>
                    <th><a href="?sort=lname&order=<?php echo ($sortField == 'lname' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Last Name</a></th>
                    <th><a href="?sort=email&order=<?php echo ($sortField == 'email' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Email</a></th>
                    <th><a href="?sort=age&order=<?php echo ($sortField == 'age' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Age</a></th>
                    <th><a href="?sort=phone&order=<?php echo ($sortField == 'phone' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Phone</a></th>
                    <th><a href="?sort=address&order=<?php echo ($sortField == 'address' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Address</a></th>
                    <th><a href="?sort=civil_status&order=<?php echo ($sortField == 'civil_status' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Civil Status</a></th>
                    <th><a href="?sort=linkedin_link&order=<?php echo ($sortField == 'linkedin_link' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">LinkedIn</a></th>
                    <th><a href="?sort=facebook_link&order=<?php echo ($sortField == 'facebook_link' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Facebook</a></th>
                    <th><a href="?sort=referral_code&order=<?php echo ($sortField == 'referral_code' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Referral Code</a></th>
                    <th><a href="?sort=education_level&order=<?php echo ($sortField == 'education_level' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Education Level</a></th>
                    <th><a href="?sort=school_graduated&order=<?php echo ($sortField == 'school_graduated' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">School Graduated</a></th>
                    <th><a href="?sort=year_graduated&order=<?php echo ($sortField == 'year_graduated' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Year Graduated</a></th>
                    <th><a href="?sort=last_login&order=<?php echo ($sortField == 'last_login' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Last Login</a></th>
                    <th><a href="?sort=status&order=<?php echo ($sortField == 'status' && $sortOrder == 'ASC') ? 'DESC' : 'ASC'; ?>">Status</a></th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $results->fetch_assoc()) { ?>
                <tr>
                    <td><input type="checkbox" name="selected_users[]" value="<?php echo $row['user_id']; ?>"></td> <!-- Checkbox for each row -->
                    <td><?php echo htmlspecialchars($row['fname']); ?></td>
                    <td><?php echo htmlspecialchars($row['lname']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['age']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone']); ?></td>
                    <td><?php echo htmlspecialchars($row['address']); ?></td>
                    <td><?php echo htmlspecialchars($row['civil_status']); ?></td>
                    <td><?php echo htmlspecialchars($row['linkedin_link']); ?></td>
                    <td><?php echo htmlspecialchars($row['facebook_link']); ?></td>
                    <td><?php echo htmlspecialchars($row['referral_code']); ?></td>
                    <td><?php echo htmlspecialchars($row['education_level']); ?></td>
                    <td><?php echo htmlspecialchars($row['school_graduated']); ?></td>
                    <td><?php echo htmlspecialchars($row['year_graduated']); ?></td>
                    <td><?php echo htmlspecialchars($row['last_login']); ?></td>
                    <td><?php echo htmlspecialchars($row['status']); ?></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </form>

</div>

<?php include 'footer.php'; ?>

</body>
</html>
