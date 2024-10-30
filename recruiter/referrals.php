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

        .referral-graph {
            width: 100%;
            height: 600px;
            border: 1px solid #ddd;
            margin-top: 30px;
        }
    </style>
    <script src="https://d3js.org/d3.v7.min.js"></script>
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

    <?php include 'footer.php'; ?>
</body>

</html>

<?php
// Close the database connection
$conn->close();
?>