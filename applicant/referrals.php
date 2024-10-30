<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'header.php';  // Include header
include 'sidebar.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch the referral code of the logged-in user from the profiles table
$profile_sql = "SELECT referral_code FROM profiles WHERE user_id = ?";
$stmt = $conn->prepare($profile_sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$profile_result = $stmt->get_result();
$user_profile = $profile_result->fetch_assoc();

// Fetch all referred users who used the logged-in user's referral code, including name, email, and social media links
$referred_users_sql = "
    SELECT 
        r.referral_id, 
        r.referral_code, 
        p_referred.user_id AS referred_user_id, 
        p_referred.fname AS referred_fname, 
        p_referred.lname AS referred_lname,
        u.email AS referred_email,
        p_referred.linkedin_link,
        p_referred.facebook_link
    FROM referrals r
    JOIN profiles p_referred ON r.referred_user_id = p_referred.user_id
    JOIN users u ON p_referred.user_id = u.user_id
    WHERE r.referrer_user_id = ? AND r.referred_user_id != ?
";
$stmt = $conn->prepare($referred_users_sql);
$stmt->bind_param('ii', $user_id, $user_id);
$stmt->execute();
$referred_users_result = $stmt->get_result();
$referred_users = $referred_users_result->fetch_all(MYSQLI_ASSOC);
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

        .no-referrals {
            text-align: center;
        }

        .referral-graph {
            width: 100%;
            height: 400px;
            border: 1px solid #ddd;
            margin-top: 30px;
        }
    </style>
    <script src="https://d3js.org/d3.v7.min.js"></script>
</head>

<body>
    <h1>My Referrals</h1>

    <!-- Display referral code -->
    <div class="profile-details">
        <h3>Your Referral Code: <?php echo htmlspecialchars($user_profile['referral_code']); ?></h3>
    </div>

    <!-- Email Form -->
    <form method="POST" action="send_email.php" id="emailForm">
        <div class="email-action">
            <button type="button" onclick="sendEmail()" class="btn btn-primary">Send Email</button>
        </div>

        <!-- Display User Referrals Table -->
        <table>
            <thead>
                <tr>
                    <th><input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"></th>
                    <th>Referred Person</th>
                    <th>Email</th>
                    <th>LinkedIn</th>
                    <th>Facebook</th>
                    <th>Referral Code Used</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($referred_users)): ?>
                    <?php foreach ($referred_users as $referral): ?>
                        <tr>
                            <td><input type="checkbox" name="selected_emails[]" value="<?php echo htmlspecialchars($referral['referred_email']); ?>"></td>
                            <td><?php echo htmlspecialchars($referral['referred_fname'] . ' ' . $referral['referred_lname']); ?></td>
                            <td><?php echo htmlspecialchars($referral['referred_email']); ?></td>
                            <td><a href="<?php echo htmlspecialchars($referral['linkedin_link']); ?>" target="_blank">LinkedIn</a></td>
                            <td><a href="<?php echo htmlspecialchars($referral['facebook_link']); ?>" target="_blank">Facebook</a></td>
                            <td><?php echo htmlspecialchars($referral['referral_code']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="no-referrals">No referrals made yet. Start referring others to earn points!</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </form>

    <!-- Referral Connection Web -->
    <h2>Your Referral Connection</h2>
    <div id="referral-graph" class="referral-graph"></div>

    <script>
        // Prepare data for the referral connection web
        const referredUsers = <?php echo json_encode($referred_users); ?>;

        // Set up nodes and links for D3.js
        const nodes = [{
            id: <?php echo $user_id; ?>,
            name: "You"
        }]; // Central "You" node
        const links = [];

        referredUsers.forEach(user => {
            // Add each referred user as a node
            nodes.push({
                id: user.referred_user_id,
                name: `${user.referred_fname} ${user.referred_lname}`
            });

            // Add a link from "You" to each referred user
            links.push({
                source: <?php echo $user_id; ?>,
                target: user.referred_user_id
            });
        });

        // Set up D3.js visualization parameters
        const width = document.getElementById('referral-graph').clientWidth;
        const height = 400;

        const svg = d3.select("#referral-graph").append("svg")
            .attr("width", width)
            .attr("height", height);

        const simulation = d3.forceSimulation(nodes)
            .force("link", d3.forceLink(links).id(d => d.id).distance(100))
            .force("charge", d3.forceManyBody().strength(-300))
            .force("center", d3.forceCenter(width / 2, height / 2));

        // Draw links
        const link = svg.append("g")
            .selectAll("line")
            .data(links)
            .enter().append("line")
            .attr("stroke", "#999")
            .attr("stroke-opacity", 0.6)
            .attr("stroke-width", 1.5);

        // Draw nodes
        const node = svg.append("g")
            .selectAll("circle")
            .data(nodes)
            .enter().append("circle")
            .attr("r", 10)
            .attr("fill", (d, i) => i === 0 ? "#007BFF" : "#FF5722"); // "You" is blue, referrals are red

        // Add labels
        const label = svg.append("g")
            .selectAll("text")
            .data(nodes)
            .enter().append("text")
            .attr("x", 12)
            .attr("y", 3)
            .attr("font-size", "10px")
            .text(d => d.name);

        // Update positions
        simulation.on("tick", () => {
            link.attr("x1", d => d.source.x)
                .attr("y1", d => d.source.y)
                .attr("x2", d => d.target.x)
                .attr("y2", d => d.target.y);

            node.attr("cx", d => d.x)
                .attr("cy", d => d.y);

            label.attr("x", d => d.x + 12)
                .attr("y", d => d.y);
        });

        // Toggle select all checkboxes
        function toggleSelectAll(source) {
            const checkboxes = document.querySelectorAll('input[name="selected_emails[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = source.checked);
        }

        // Open default email client with selected emails in recipients
        function sendEmail() {
            const selectedEmails = Array.from(document.querySelectorAll('input[name="selected_emails[]"]:checked')).map(checkbox => checkbox.value);

            if (selectedEmails.length > 0) {
                const mailtoLink = `mailto:${encodeURIComponent(selectedEmails.join(','))}`;
                window.location.href = mailtoLink;
            } else {
                alert("Please select at least one user to email.");
            }
        }
    </script>

    <?php include 'footer.php'; ?>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>