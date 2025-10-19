<?php
session_start();
include 'php-dbCon.php'; // your database connection file

// Make sure user is logged in
if (!isset($_SESSION['admin_email'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit;
}

// Get input
$data = json_decode(file_get_contents("php://input"), true);
$newPassword = trim($data['newPassword'] ?? '');

if (empty($newPassword)) {
    echo json_encode(["status" => "error", "message" => "Password cannot be empty"]);
    exit;
}

$email = $_SESSION['admin_email']; // or use $_SESSION['admin_id'] if available
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Update the password in DB
$sql = "UPDATE admin SET password = ? WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $hashedPassword, $email);

if ($stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Password updated successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to update password"]);
}

$stmt->close();
$conn->close();
?>
