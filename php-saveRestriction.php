<?php
header('Content-Type: application/json');
include 'php-dbCon.php';
session_start();
// Step 1: Check if admin is logged in
if (!isset($_SESSION['admin_email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}
$adminEmail = $_SESSION['admin_email'];
// Step 2: Get adminID
$getAdmin = $conn->prepare("SELECT adminID FROM admin WHERE email = ?");
$getAdmin->bind_param("s", $adminEmail);
$getAdmin->execute();
$res = $getAdmin->get_result();
if ($res->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Admin not found']);
    exit();
}
$adminData = $res->fetch_assoc();
$adminID = $adminData['adminID'];
$getAdmin->close();
// Step 3: Get data from frontend (expecting restrictions + churchName)
$input = json_decode(file_get_contents("php://input"), true);
if (!$input || !is_array($input)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit();
}
// Extract churchName if sent
$churchName = $input['churchName'] ?? null;
$data = $input['restrictions'] ?? null;
if (!$data || !is_array($data)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing restriction data']);
    exit();
}
// Step 4: Prepare UPDATE for restrictions
$stmt = $conn->prepare("
    UPDATE restrictions
    SET 
        gender = ?,
        age1 = ?,
        age2 = ?,
        marital = ?,
        baptist = ?,
        timeInFaith = ?
    WHERE adminID = ? AND ministryID = ?
");
$updatedCount = 0;
foreach ($data as $item) {
    $ministryID = $item['ministryID'] ?? 0;
    $gender = $item['gender'] ?? 0;
    $age1 = $item['age1'] ?? 1;
    $age2 = $item['age2'] ?? 99;
    $marital = $item['marital'] ?? 0;
    $baptist = $item['baptist'] ?? 0;
    $timeInFaith = $item['timeInFaith'] ?? 0;
    $stmt->bind_param("iiiiiiii", $gender, $age1, $age2, $marital, $baptist, $timeInFaith, $adminID, $ministryID);
    $stmt->execute();
    if ($stmt->affected_rows > 0) {
        $updatedCount++;
    }
}
$stmt->close();
// Step 5: Update churchName if provided
if (!empty($churchName)) {
    $updateChurch = $conn->prepare("UPDATE admin SET churchName = ? WHERE adminID = ?");
    $updateChurch->bind_param("si", $churchName, $adminID);
    $updateChurch->execute();
    $updateChurch->close();
}
$conn->close();
echo json_encode([
    'status' => 'success',
    'message' => "$updatedCount restriction(s) updated and church name saved"
]);
exit();
?>
