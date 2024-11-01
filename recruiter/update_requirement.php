<?php
include '../db.php'; // Database connection

// Check if required POST variables are set
if (isset($_POST['req_id'], $_POST['application_id'], $_POST['is_submitted'])) {
    $req_id = (int)$_POST['req_id'];
    $application_id = (int)$_POST['application_id'];
    $is_submitted = (int)$_POST['is_submitted'];

    // Check if a matching record exists in requirement_tracking
    $check_sql = "SELECT tracking_id FROM requirement_tracking WHERE req_id = ? AND application_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $req_id, $application_id);
    $check_stmt->execute();
    $check_stmt->store_result();

    if ($check_stmt->num_rows > 0) {
        // Record exists, proceed with updating the is_submitted field
        $update_sql = "UPDATE requirement_tracking SET is_submitted = ? WHERE req_id = ? AND application_id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("iii", $is_submitted, $req_id, $application_id);

        if ($update_stmt->execute()) {
            echo "Requirement updated successfully.";
        } else {
            echo "Error updating requirement.";
        }

        $update_stmt->close();
    } else {
        // No record found to update, return an error message
        echo "Error: Requirement record not found for updating.";
    }

    $check_stmt->close();
}

$conn->close();
