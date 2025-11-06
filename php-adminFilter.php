<?php
header("Content-Type: application/json");
include "php-dbCon.php";
session_start();

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_email'])) {
    echo json_encode(["error" => "Unauthorized"]);
    exit();
}

$adminEmail = $_SESSION['admin_email'];

// ✅ Get admin info (to retrieve churchCode)
$getAdmin = $conn->prepare("SELECT adminID, churchCode FROM admin WHERE email = ?");
$getAdmin->bind_param("s", $adminEmail);
$getAdmin->execute();
$resultAdmin = $getAdmin->get_result();

if ($resultAdmin->num_rows === 0) {
    echo json_encode(["error" => "Admin not found"]);
    exit();
}

$adminRow = $resultAdmin->fetch_assoc();
$churchCode = $adminRow["churchCode"];
$getAdmin->close();

// ✅ Fetch all user reports for this admin’s church
$sql = "
    SELECT 
        ur.userReportID,
        ur.churchCode,
        ur.email,
        ur.name,
        ur.music,
        ur.technology,
        ur.writing,
        ur.technical,
        ur.speaking,
        ur.accounting,
        ur.mentoring,
        ur.bibleKnowledge,
        ur.eligibleMinistry,
        ur.gender,
        ur.age,
        ur.marital,
        ur.baptized,
        ur.timeInFaith,
        ur.timeOfSubmission
    FROM user_report ur
    WHERE ur.churchCode = ?
    ORDER BY ur.userReportID DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $churchCode);
$stmt->execute();
$result = $stmt->get_result();

$userReports = [];
$userTakeCount = 0;
$userTodayCount = 0;
$today = date("Y-m-d");

// ✅ Process user reports
while ($row = $result->fetch_assoc()) {
    $userReports[] = $row;
    $userTakeCount++;

    // Count reports submitted today
    if (!empty($row["timeOfSubmission"])) {
        $submissionDate = explode(" ", $row["timeOfSubmission"])[0];
        if ($submissionDate === $today) {
            $userTodayCount++;
        }
    }
}

$stmt->close();
$conn->close();

// ✅ Send final JSON response
echo json_encode([
    "userReports" => $userReports,
    "userTakeCount" => $userTakeCount,
    "userTodayCount" => $userTodayCount
]);
exit();
?>
