<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'header.php';  // Include header
include 'sidebar.php';
include 'footer.php';  // Include footer

// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

// Fetch all referrals across all users (no need to filter by logged-in user)
$referrals_sql = "
    SELECT r.referral_id, r.referral_code, r.points, 
           p_referred.fname AS referred_fname, p_referred.lname AS referred_lname, 
           p_referred.phone AS referred_phone, p_referred.linkedin_link, p_referred.facebook_link,
           p_referrer.fname AS referrer_fname, p_referrer.lname AS referrer_lname
    FROM referrals r
    JOIN profiles p_referred ON r.referred_user_id = p_referred.user_id
    JOIN profiles p_referrer ON r.referrer_user_id = p_referrer.user_id
    WHERE r.referred_user_id != r.referrer_user_id
    ORDER BY r.referral_id DESC
";

// Prepare and execute the referral query
$stmt = $conn->prepare($referrals_sql);
if (!$stmt) {
    die("Error preparing referrals query: " . $conn->error);
}
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
    <title>All Referrals</title>
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
    </style>
</head>

<body>
    <h1>All Referrals</h1>

    <table>
        <thead>
            <tr>
                <th>Referred Person</th>
                <th>Referrer</th>
                <th>Phone</th>
                <th>LinkedIn</th>
                <th>Facebook</th>
                <th>Referral Code</th>
                <th>Points Earned</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($referrals_result->num_rows > 0): ?>
                <?php while ($referral = $referrals_result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($referral['referred_fname'] . ' ' . $referral['referred_lname']); ?></td>
                        <td><?php echo htmlspecialchars($referral['referrer_fname'] . ' ' . $referral['referrer_lname']); ?></td>
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
                        <td><?php echo htmlspecialchars($referral['referral_code']); ?></td>
                        <td><?php echo htmlspecialchars($referral['points']); ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No referrals found.</td>
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