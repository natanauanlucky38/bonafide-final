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

// Fetch all referrals, grouping by referrer and excluding duplicate referrer-referred pairs
$referrals_sql = "
    SELECT DISTINCT r.referral_id, r.referral_code, r.points, 
           p_referred.user_id AS referred_user_id, p_referred.fname AS referred_fname, p_referred.lname AS referred_lname, 
           p_referrer.user_id AS referrer_user_id, p_referrer.fname AS referrer_fname, p_referrer.lname AS referrer_lname
    FROM referrals r
    JOIN profiles p_referred ON r.referred_user_id = p_referred.user_id
    JOIN profiles p_referrer ON r.referrer_user_id = p_referrer.user_id
    WHERE r.referred_user_id != r.referrer_user_id
    ORDER BY p_referrer.user_id, r.referral_id DESC
";

// Prepare and execute the referral query
$stmt = $conn->prepare($referrals_sql);
if (!$stmt) {
    die("Error preparing referrals query: " . $conn->error);
}
$stmt->execute();
$referrals_result = $stmt->get_result();

// Organize referral data by referrer for easy categorization
$referrals_data = [];
while ($row = $referrals_result->fetch_assoc()) {
    $referrals_data[$row['referrer_user_id']]['referrer_name'] = $row['referrer_fname'] . ' ' . $row['referrer_lname'];
    $referrals_data[$row['referrer_user_id']]['referrals'][] = $row;
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

        .referrer-section {
            margin-top: 20px;
        }

        .referrer-section h2 {
            margin-bottom: 5px;
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

    <?php if (!empty($referrals_data)): ?>
        <?php foreach ($referrals_data as $referrer_id => $referrer_data): ?>
            <div class="referrer-section">
                <h2>Referrer: <?php echo htmlspecialchars($referrer_data['referrer_name']); ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Referred Person</th>
                            <th>Referral Code</th>
                            <th>Points Earned</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($referrer_data['referrals'] as $referral): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($referral['referred_fname'] . ' ' . $referral['referred_lname']); ?></td>
                                <td><?php echo htmlspecialchars($referral['referral_code']); ?></td>
                                <td><?php echo htmlspecialchars($referral['points']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No referrals found.</p>
    <?php endif; ?>

    <?php include 'footer.php'; ?>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>