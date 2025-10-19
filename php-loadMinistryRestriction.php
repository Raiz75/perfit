<?php
header('Content-Type: application/json');
include 'php-dbCon.php';

session_start();

// Check if admin is logged in
if (!isset($_SESSION['admin_email'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$adminEmail = $_SESSION['admin_email'];

// Step 1: Get admin details (only churchName)
$getAdmin = $conn->prepare("SELECT adminID, churchName FROM admin WHERE email = ?");
$getAdmin->bind_param("s", $adminEmail);
$getAdmin->execute();
$resultAdmin = $getAdmin->get_result();

if ($resultAdmin->num_rows === 0) {
    echo json_encode(['error' => 'Admin not found']);
    exit();
}

$adminRow = $resultAdmin->fetch_assoc();
$adminID = $adminRow['adminID'];
$churchName = $adminRow['churchName'];
$getAdmin->close();

// Step 2: Get ministries and restrictions for that admin
$sql = "
    SELECT 
        m.ministryID,
        m.ministryName,
        r.gender,
        r.age1,
        r.age2,
        r.marital,
        r.baptist,
        r.timeInFaith
    FROM ministries m
    LEFT JOIN restrictions r
        ON m.ministryID = r.ministryID AND r.adminID = ?
    ORDER BY m.ministryID
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminID);
$stmt->execute();
$result = $stmt->get_result();

$ministries = [];
while ($row = $result->fetch_assoc()) {
    $ministries[] = $row;
}

// ✅ Combine admin info + ministries (no img field)
$response = [
    'churchName' => $churchName,
    'ministries' => $ministries
];

echo json_encode($response);

$stmt->close();
$conn->close();
?>
