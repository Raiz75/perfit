<?php
session_start();
header('Content-Type: application/json');
include 'php-dbCon.php';

$data = json_decode(file_get_contents('php://input'), true);
$churchName = trim($data['churchName'] ?? '');

$response = ['exists' => false];

// ✅ Make sure we have the current admin's ID or email stored in session
$currentAdminEmail = $_SESSION['admin_email'] ?? null;

if ($churchName !== '' && $currentAdminEmail !== null) {
    // ✅ Check if the church name exists, excluding the current admin
    $stmt = $conn->prepare("SELECT COUNT(*) FROM admin WHERE churchName = ? AND email != ?");
    $stmt->bind_param("ss", $churchName, $currentAdminEmail);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();

    if ($count > 0) {
        $response['exists'] = true;
    }

    $stmt->close();
} else {
    $response['message'] = "Session or church name missing.";
}

echo json_encode($response);
$conn->close();
?>
