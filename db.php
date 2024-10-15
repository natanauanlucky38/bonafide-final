<?php
// db.php - Reusable database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "db_bonafide";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// For prepared statements and parameterized queries
function prepare_and_execute($conn, $sql, $types, ...$params) {
    $stmt = $conn->prepare($sql);
    if ($types && $params) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return $stmt;
}

session_start();  // Start session for login
?>
