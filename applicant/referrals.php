<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'header.php';  // Include header
include 'sidebar.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch the referral code of the logged-in user from the profiles table
$profile_sql = "SELECT referral_code FROM profiles WHERE user_id = ?";
$stmt = $conn->prepare($profile_sql);
if (!$stmt) {
    die("Error preparing profile query: " . $conn->error);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$profile_result = $stmt->get_result();
$user_profile = $profile_result->fetch_assoc();

// Calculate total points earned from referrals in the referrals table
$points_sql = "SELECT SUM(points) AS total_points FROM referrals WHERE referrer_user_id = ?";
$stmt = $conn->prepare($points_sql);
if (!$stmt) {
    die("Error preparing points query: " . $conn->error);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$points_result = $stmt->get_result();
$points = $points_result->fetch_assoc()['total_points'] ?? 0;  // Default to 0 if no points

// Fetch all referred users who used the logged-in user's referral code, excluding the user's own account
$referred_users_sql = "
    SELECT 
        r.referral_id, 
        r.referral_code, 
        p_referred.fname AS referred_fname, 
        p_referred.lname AS referred_lname, 
        u_referred.email AS referred_email,
        p_referred.phone AS referred_phone, 
        p_referred.linkedin_link, 
        p_referred.facebook_link
    FROM referrals r
    JOIN profiles p_referred ON r.referred_user_id = p_referred.user_id
    JOIN users u_referred ON r.referred_user_id = u_referred.user_id
    WHERE r.referrer_user_id = ? AND r.referred_user_id != ?
";

// Prepare and execute the referred users query
$stmt = $conn->prepare($referred_users_sql);
if (!$stmt) {
    die("Error preparing referred users query: " . $conn->error);
}
$stmt->bind_param('ii', $user_id, $user_id);  // Passing the same $user_id for exclusion
$stmt->execute();
$referred_users_result = $stmt->get_result();

// Handle errors fetching results
if (!$referred_users_result) {
    die("Error fetching referred users: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Referrals</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .profile-details {
            margin-bottom: 20px;
        }

        .profile-details h3 {
            margin-bottom: 5px;
        }

        .no-referrals {
            text-align: center;
        }
    </style>
</head>

<body>
    <h1>My Referrals</h1>

    <!-- Display referral code and points -->
    <div class="profile-details">
        <h3>Your Referral Code: <?php echo htmlspecialchars($user_profile['referral_code']); ?></h3>
        <h3>Points Earned: <?php echo htmlspecialchars($points); ?></h3>
    </div>

    <!-- Display User Referrals Table -->
    <table>
        <thead>
            <tr>
                <th>Referred Person</th>
                <th>Email</th>
                <th>Phone</th>
                <th>LinkedIn</th>
                <th>Facebook</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($referred_users_result->num_rows > 0): ?>
                <?php while ($referral = $referred_users_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($referral['referred_fname'] . ' ' . $referral['referred_lname']); ?></td>
                        <td><?php echo htmlspecialchars($referral['referred_email']); ?></td>
                        <td><?php echo htmlspecialchars($referral['referred_phone']); ?></td>
                        <td>
                            <?php if (!empty($referral['linkedin_link'])): ?>
                                <?php
                                // Check if the URL starts with 'http://' or 'https://'
                                $linkedin_link = $referral['linkedin_link'];
                                if (!preg_match("/^http(s)?:\/\//", $linkedin_link)) {
                                    $linkedin_link = "http://" . $linkedin_link;
                                }
                                ?>
                                <a href="<?php echo htmlspecialchars($linkedin_link); ?>" target="_blank"><?php echo htmlspecialchars($linkedin_link); ?></a>
                            <?php else: ?>
                                No LinkedIn link
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($referral['facebook_link'])): ?>
                                <?php
                                // Check if the URL starts with 'http://' or 'https://'
                                $facebook_link = $referral['facebook_link'];
                                if (!preg_match("/^http(s)?:\/\//", $facebook_link)) {
                                    $facebook_link = "http://" . $facebook_link;
                                }
                                ?>
                                <a href="<?php echo htmlspecialchars($facebook_link); ?>" target="_blank"><?php echo htmlspecialchars($facebook_link); ?></a>
                            <?php else: ?>
                                No Facebook link
                            <?php endif; ?>
                        </td>

                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="no-referrals">No referrals made yet. Start referring others to earn points!</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php include 'footer.php'; ?>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>