<?php
header('Content-Type: application/json');
include 'php-dbCon.php';

$churchCode = $_POST['churchCode'] ?? '';
$churchCode = trim($churchCode);

if (empty($churchCode)) {
    echo json_encode(["success" => false, "message" => "Church code is required."]);
    exit;
}

// ✅ Case-sensitive check using BINARY
$stmt = $conn->prepare("SELECT 1 FROM admin WHERE BINARY churchCode = ?");
$stmt->bind_param("s", $churchCode);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["success" => false, "message" => "Church code not found."]);
}

$stmt->close();
$conn->close();
?>
