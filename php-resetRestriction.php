<?php
header('Content-Type: application/json');
include 'php-dbCon.php';
session_start();
// Check if admin is logged in
if (!isset($_SESSION['admin_email'])) {
    echo json_encode(["success" => false, "message" => "Unauthorized access."]);
    exit;
}
$adminEmail = $_SESSION['admin_email'];
// Get the current admin ID
$getAdmin = $conn->prepare("SELECT adminID FROM admin WHERE email = ?");
$getAdmin->bind_param("s", $adminEmail);
$getAdmin->execute();
$result = $getAdmin->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Admin not found."]);
    exit;
}
$admin = $result->fetch_assoc();
$adminID = $admin['adminID'];
$getAdmin->close();
// ✅ Step: Update restrictions in one query
$updateQuery = "
    UPDATE restrictions AS r
    JOIN restrictions AS d ON r.ministryID = d.ministryID
    SET 
        r.gender = d.gender,
        r.age1 = d.age1,
        r.age2 = d.age2,
        r.marital = d.marital,
        r.baptist = d.baptist,
        r.timeInFaith = d.timeInFaith
    WHERE r.adminID = ? AND d.adminID = 1
";
$update = $conn->prepare($updateQuery);
$update->bind_param("i", $adminID);
if ($update->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Restrictions successfully reset to default settings."
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Error resetting restrictions. Please try again."
    ]);
}
$update->close();
$conn->close();
?>
