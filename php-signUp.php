<?php
header('Content-Type: application/json');
include 'php-dbCon.php';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
// Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
    exit;
}
// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}
// Check if email already exists
$check = $conn->prepare("SELECT 1 FROM admin WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();
if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already exists."]);
    exit;
}
// Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);
// Insert new user (initially without church name)
$insert = $conn->prepare("INSERT INTO admin (email, password) VALUES (?, ?)");
$insert->bind_param("ss", $email, $hashed);
if ($insert->execute()) {
    // Get the new admin ID
    $newAdminID = $conn->insert_id;
    // ✅ Step 1: Copy default restrictions (from adminID = 1)
    $copySQL = "
        INSERT INTO restrictions (adminID, ministryID, gender, age1, age2, marital, baptist, timeInFaith)
        SELECT ?, ministryID, gender, age1, age2, marital, baptist, timeInFaith
        FROM restrictions
        WHERE adminID = 1
    ";
    $copyStmt = $conn->prepare($copySQL);
    $copyStmt->bind_param("i", $newAdminID);
    $copyStmt->execute();
    $copyStmt->close();
    // ✅ Step 2: Set default church name only (no image)
    $churchName = "Church " . $newAdminID;
    $updateChurch = $conn->prepare("UPDATE admin SET churchName = ? WHERE adminID = ?");
    $updateChurch->bind_param("si", $churchName, $newAdminID);
    $updateChurch->execute();
    $updateChurch->close();
    echo json_encode([
        "success" => true,
        "message" => "Account created successfully! Default restrictions applied and church name set to '$churchName'."
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Error creating account."]);
}
$conn->close();
?>
