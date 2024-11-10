<?php
// Include database connection and start session
include '../db.php';  // Adjust this path based on your directory structure
include 'header.php';  // Include header


// Check if the user is logged in as a recruiter
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'RECRUITER') {
    header('Location: index.php');
    exit();
}

// Fetch all referrals, grouping by referrer and excluding duplicate referrer-referred pairs
$referrals_sql = "
    SELECT DISTINCT r.referral_id, r.referral_code, r.points, 
           p_referred.user_id AS referred_user_id, 
           p_referred.fname AS referred_fname, 
           p_referred.lname AS referred_lname, 
           p_referred.phone AS referred_phone,
           u_referred.email AS referred_email,  
           p_referrer.user_id AS referrer_user_id, 
           p_referrer.fname AS referrer_fname, 
           p_referrer.lname AS referrer_lname,
           p_referrer.phone AS referrer_phone,
           u_referrer.email AS referrer_email  
    FROM referrals r
    JOIN profiles p_referred ON r.referred_user_id = p_referred.user_id
    JOIN profiles p_referrer ON r.referrer_user_id = p_referrer.user_id
    JOIN users u_referred ON u_referred.user_id = p_referred.user_id
    JOIN users u_referrer ON u_referrer.user_id = p_referrer.user_id
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
    $referrals_data[$row['referrer_user_id']]['referrer_phone'] = $row['referrer_phone'];
    $referrals_data[$row['referrer_user_id']]['referrer_email'] = $row['referrer_email'];
    $referrals_data[$row['referrer_user_id']]['referrals'][] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Referrals</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your CSS file -->
    <script src="https://d3js.org/d3.v7.min.js"></script>
</head>

<body class="referrals-main-content">
    <div class="referrals-container">
        <h1>All Referrals</h1>

        <?php if (!empty($referrals_data)): ?>
            <?php foreach ($referrals_data as $referrer_id => $referrer_data): ?>
                <div class="referrer-section">
                    <h2>Referrer: <?php echo htmlspecialchars($referrer_data['referrer_name']); ?></h2>
                    <div class="profile-details">
                        <p><strong>Contact Phone:</strong> <?php echo htmlspecialchars($referrer_data['referrer_phone']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($referrer_data['referrer_email']); ?></p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Referred Person</th>
                                <th>Contact Phone</th>
                                <th>Email</th>
                                <th>Referral Code</th>
                                <th>Points Earned</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($referrer_data['referrals'] as $referral): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($referral['referred_fname'] . ' ' . $referral['referred_lname']); ?></td>
                                    <td><?php echo htmlspecialchars($referral['referred_phone']); ?></td>
                                    <td><?php echo htmlspecialchars($referral['referred_email']); ?></td>
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

        <!-- Referral Connection Web -->
        <h2>Referral Connection Web</h2>
        <div id="referral-graph" class="referral-graph"></div>

        <script>
            // Prepare referral data for D3.js visualization
            const referrals = <?php echo json_encode(array_merge(...array_column($referrals_data, 'referrals'))); ?>;

            // Create nodes and links for D3.js
            const nodes = {};
            const links = referrals.map(referral => {
                nodes[referral.referrer_user_id] = {
                    id: referral.referrer_user_id,
                    name: `${referral.referrer_fname} ${referral.referrer_lname}`
                };
                nodes[referral.referred_user_id] = {
                    id: referral.referred_user_id,
                    name: `${referral.referred_fname} ${referral.referred_lname}`
                };
                return {
                    source: referral.referrer_user_id,
                    target: referral.referred_user_id
                };
            });

            const width = document.getElementById('referral-graph').clientWidth;
            const height = 600;

            // Create D3 force simulation
            const svg = d3.select("#referral-graph").append("svg")
                .attr("width", width)
                .attr("height", height);

            const simulation = d3.forceSimulation(Object.values(nodes))
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
                .data(Object.values(nodes))
                .enter().append("circle")
                .attr("r", 8)
                .attr("fill", "#007BFF");

            // Add labels
            const label = svg.append("g")
                .selectAll("text")
                .data(Object.values(nodes))
                .enter().append("text")
                .attr("x", 8)
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

                label.attr("x", d => d.x + 10)
                    .attr("y", d => d.y);
            });
        </script>
    </div>
</body>
<?php include 'footer.php'; ?>

</html>


<?php
// Close the database connection
$conn->close();
?>