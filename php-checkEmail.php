<?php
header('Content-Type: application/json');
include 'php-dbCon.php';
$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
if (empty($email)) {
    echo json_encode(["exists" => false, "message" => "No email provided."]);
    exit;
}
$check = $conn->prepare("SELECT 1 FROM admin WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();
if ($result->num_rows > 0) {
    echo json_encode(["exists" => true, "message" => "Email already exists."]);
} else {
    echo json_encode(["exists" => false, "message" => "Email available."]);
}
$conn->close();
?>
