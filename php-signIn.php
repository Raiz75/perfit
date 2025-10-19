<?php
session_start();
header('Content-Type: application/json');
include 'php-dbCon.php';

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
    exit;
}

$query = $conn->prepare("SELECT * FROM admin WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Account not found."]);
    exit;
}

$user = $result->fetch_assoc();

if (password_verify($password, $user['password'])) {
    // Save email in session
    $_SESSION['admin_email'] = $user['email'];

    echo json_encode(["success" => true, "message" => "Logging in, Please wait."]);
} else {
    echo json_encode(["success" => false, "message" => "Incorrect password."]);
}

$conn->close();
?>
