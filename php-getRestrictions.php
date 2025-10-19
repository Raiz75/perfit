<?php
header('Content-Type: application/json');
include 'php-dbCon.php';

// Read JSON input
$input = json_decode(file_get_contents("php://input"), true);
$churchName = $input['selectedChurch'] ?? '';

if (empty($churchName)) {
    echo json_encode(["success" => false, "message" => "No church name provided."]);
    exit;
}

// Step 1: Get the adminID based on the church name
$getAdmin = $conn->prepare("SELECT adminID FROM admin WHERE churchName = ?");
$getAdmin->bind_param("s", $churchName);
$getAdmin->execute();
$result = $getAdmin->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Church not found."]);
    exit;
}

$admin = $result->fetch_assoc();
$adminID = $admin['adminID'];
$getAdmin->close();

// Step 2: Get all restrictions + ministry names for this admin
$getRestrictions = $conn->prepare("
    SELECT 
        r.adminID,
        r.ministryID,
        m.ministryName,
        r.gender,
        r.age1,
        r.age2,
        r.marital,
        r.baptist,
        r.timeInFaith
    FROM restrictions r
    INNER JOIN ministries m ON r.ministryID = m.ministryID
    WHERE r.adminID = ?
");
$getRestrictions->bind_param("i", $adminID);
$getRestrictions->execute();
$result = $getRestrictions->get_result();

$restrictions = [];
while ($row = $result->fetch_assoc()) {
    // Convert numeric values to integers for cleaner JSON
    $restrictions[] = [
        "ministryID"   => (int)$row["ministryID"],
        "ministryName" => $row["ministryName"],
        "gender"       => (int)$row["gender"],
        "age1"         => (int)$row["age1"],
        "age2"         => (int)$row["age2"],
        "marital"      => (int)$row["marital"],
        "baptist"      => (int)$row["baptist"],
        "timeInFaith"  => (int)$row["timeInFaith"]
    ];
}

$getRestrictions->close();
$conn->close();

// Step 3: Return structured data as JSON
echo json_encode([
    "success" => true,
    "church" => $churchName,
    "data" => $restrictions
]);
?>
