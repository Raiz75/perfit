<?php
include 'php-config.php';
header('Content-Type: application/json');

$input = json_decode(file_get_contents("php://input"), true);
$churchName = $input['churchName'] ?? '';

$query = "SELECT ministryID, ministryName FROM ministries WHERE churchName = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $churchName);
$stmt->execute();
$result = $stmt->get_result();

$ministries = [];
while ($row = $result->fetch_assoc()) {
    $ministries[] = $row;
}

echo json_encode(["ministries" => $ministries]);
