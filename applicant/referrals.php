<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'header.php';  // Include header
include 'footer.php';  // Include footer

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

// Fetch all referrals made by the logged-in user, excluding self-referrals
$referrals_sql = "
    SELECT r.referral_id, r.referral_code, r.points, 
           p_referred.fname AS referred_fname, p_referred.lname AS referred_lname, 
           p_referred.phone AS referred_phone, p_referred.linkedin_link, p_referred.facebook_link
    FROM referrals r
    JOIN profiles p_referred ON r.referred_user_id = p_referred.user_id
    WHERE r.referrer_user_id = ? AND r.referred_user_id != r.referrer_user_id
    ORDER BY r.referral_id DESC
";

// Prepare and execute the referral query
$stmt = $conn->prepare($referrals_sql);
if (!$stmt) {
    die("Error preparing referrals query: " . $conn->error);
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$referrals_result = $stmt->get_result();

// Handle errors fetching results
if (!$referrals_result) {
    die("Error fetching referrals: " . $conn->error);
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

        table, th, td {
            border: 1px solid black;
        }

        th, td {
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
    </style>
</head>
<body>
    <h1>My Referrals</h1>

    <!-- Display referral code and points outside the table -->
    <div class="profile-details">
        <h3>Your Referral Code: <?php echo htmlspecialchars($user_profile['referral_code']); ?></h3>
        <h3>Points Earned: <?php echo htmlspecialchars($points); ?></h3>
    </div>

    <table>
        <thead>
            <tr>
                <th>Referred Person</th>
                <th>Phone</th>
                <th>LinkedIn</th>
                <th>Facebook</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($referrals_result->num_rows > 0): ?>
                <?php while ($referral = $referrals_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($referral['referred_fname'] . ' ' . $referral['referred_lname']); ?></td>
                        <td><?php echo htmlspecialchars($referral['referred_phone']); ?></td>
                        <td>
                            <?php if (!empty($referral['linkedin_link'])): ?>
                                <a href="<?php echo htmlspecialchars($referral['linkedin_link']); ?>" target="_blank">LinkedIn</a>
                            <?php else: ?>
                                No LinkedIn link
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($referral['facebook_link'])): ?>
                                <a href="<?php echo htmlspecialchars($referral['facebook_link']); ?>" target="_blank">Facebook</a>
                            <?php else: ?>
                                No Facebook link
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">You have not made any referrals yet.</td>
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
