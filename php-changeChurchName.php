<?php
header("Content-Type: application/json");
include "php-dbCon.php";
session_start(); // Start session to get admin info

// ✅ Require login
if (!isset($_SESSION['admin_email'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$adminEmail = $_SESSION['admin_email'];
$churchName = $_POST['churchName'] ?? '';

if (empty($churchName)) {
    echo json_encode(["error" => "Church name is required"]);
    exit;
}

// ✅ Get adminID from email
$getAdmin = $conn->prepare("SELECT adminID FROM admin WHERE email = ?");
$getAdmin->bind_param("s", $adminEmail);
$getAdmin->execute();
$result = $getAdmin->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["error" => "Admin not found"]);
    exit;
}

$adminRow = $result->fetch_assoc();
$adminID = $adminRow['adminID'];
$getAdmin->close();

// ✅ Check if church name already exists (excluding this admin)
$checkStmt = $conn->prepare("SELECT churchName FROM admin WHERE churchName = ? AND adminID != ?");
$checkStmt->bind_param("si", $churchName, $adminID);
$checkStmt->execute();
$checkResult = $checkStmt->get_result();

if ($checkResult->num_rows > 0) {
    echo json_encode(["error" => "Church name already exists. Please choose a different name."]);
    $checkStmt->close();
    $conn->close();
    exit;
}
$checkStmt->close();

// ✅ Update church name dynamically
$updateStmt = $conn->prepare("UPDATE admin SET churchName = ? WHERE adminID = ?");
$updateStmt->bind_param("si", $churchName, $adminID);

if ($updateStmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => "Failed to update church name."]);
}

$updateStmt->close();
$conn->close();
?>
