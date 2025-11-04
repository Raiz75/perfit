<?php
header('Content-Type: application/json');
include 'php-dbCon.php';
session_start();

// ✅ Require login
if (!isset($_SESSION['admin_email'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// ✅ Get date range from JS
$startDate = $_GET['startDate'] ?? null;
$endDate = $_GET['endDate'] ?? null;

if (!$startDate || !$endDate) {
    echo json_encode(['error' => 'Missing date range.']);
    exit();
}

$adminEmail = $_SESSION['admin_email'];

// ✅ Step 1: Get admin details (we’ll use churchCode to filter, and churchName for display)
$getAdmin = $conn->prepare("SELECT adminID, churchName, churchCode FROM admin WHERE email = ?");
$getAdmin->bind_param("s", $adminEmail);
$getAdmin->execute();
$resultAdmin = $getAdmin->get_result();

if ($resultAdmin->num_rows === 0) {
    echo json_encode(['error' => 'Admin not found']);
    exit();
}

$adminRow = $resultAdmin->fetch_assoc();
$adminID = $adminRow['adminID'];
$churchName = $adminRow['churchName']; // ✅ Used for display only
$churchCode = $adminRow['churchCode']; // ✅ Used for filtering user_report
$getAdmin->close();

// ✅ Step 2: Get user reports filtered by churchCode + date range
$sqlReports = "
    SELECT 
        ur.userReportID,
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
      AND DATE(ur.timeOfSubmission) BETWEEN ? AND ?
    ORDER BY ur.userReportID DESC
";
$stmtReports = $conn->prepare($sqlReports);
$stmtReports->bind_param("sss", $churchCode, $startDate, $endDate);
$stmtReports->execute();
$resultReports = $stmtReports->get_result();

// ✅ Initialize data containers
$userReports = [];
$userTakeCount = 0;
$genderStats = [];
$faithStats = [];
$ageStats = ['Under 18' => 0, '18-25' => 0, '26-35' => 0, '36-50' => 0, '51+' => 0];
$maritalStats = ['Single' => 0, 'Married' => 0];
$baptizedStats = ['Yes' => 0, 'No' => 0];
$skillStats = [
    'Music' => 0, 'Technology' => 0, 'Writing' => 0, 'Technical' => 0,
    'Speaking' => 0, 'Accounting' => 0, 'Mentoring' => 0, 'Bible Knowledge' => 0
];
$eligibleMinistryStats = [];
$skillColumnMap = [
    'Music' => 'music', 'Technology' => 'technology', 'Writing' => 'writing',
    'Technical' => 'technical', 'Speaking' => 'speaking', 'Accounting' => 'accounting',
    'Mentoring' => 'mentoring', 'Bible Knowledge' => 'bibleKnowledge'
];

// ✅ Loop through all reports
while ($row = $resultReports->fetch_assoc()) {
    $userTakeCount++;

    // Gender
    if ($row['gender'] == 1) $genderStats['Male'] = ($genderStats['Male'] ?? 0) + 1;
    elseif ($row['gender'] == 2) $genderStats['Female'] = ($genderStats['Female'] ?? 0) + 1;

    // Time in Faith
    $faithMap = [1 => '1+ Week', 2 => '6+ Months', 3 => '1+ Year', 4 => '2+ Years'];
    $faithLabel = $faithMap[$row['timeInFaith']] ?? '-';
    if ($faithLabel != '-') $faithStats[$faithLabel] = ($faithStats[$faithLabel] ?? 0) + 1;

    // Age
    $age = intval($row['age']);
    if ($age > 0 && $age < 18) $ageStats['Under 18']++;
    elseif ($age >= 18 && $age <= 25) $ageStats['18-25']++;
    elseif ($age >= 26 && $age <= 35) $ageStats['26-35']++;
    elseif ($age >= 36 && $age <= 50) $ageStats['36-50']++;
    elseif ($age >= 51) $ageStats['51+']++;

    // Marital & Baptized
    if ($row['marital'] == 1) $maritalStats['Single']++;
    elseif ($row['marital'] == 2) $maritalStats['Married']++;

    if ($row['baptized'] == 1) $baptizedStats['Yes']++;
    elseif ($row['baptized'] == 2) $baptizedStats['No']++;

    // Skills
    foreach ($skillColumnMap as $label => $colName) {
        if (!empty($row[$colName]) && $row[$colName] == 1) {
            $skillStats[$label]++;
        }
    }

    // Eligible Ministries
    if (!empty($row['eligibleMinistry'])) {
        $ministriesList = array_map('trim', explode(',', $row['eligibleMinistry']));
        foreach ($ministriesList as $min) {
            if ($min !== '') {
                $eligibleMinistryStats[$min] = ($eligibleMinistryStats[$min] ?? 0) + 1;
            }
        }
    }

    // Convert numeric codes to readable strings
    $row['gender'] = $row['gender'] == 1 ? 'Male' : ($row['gender'] == 2 ? 'Female' : '-');
    $row['marital'] = $row['marital'] == 1 ? 'Single' : ($row['marital'] == 2 ? 'Married' : '-');
    $row['baptized'] = $row['baptized'] == 1 ? 'Yes' : ($row['baptized'] == 2 ? 'No' : '-');
    $row['timeInFaith'] = $faithLabel;

    foreach ($skillColumnMap as $label => $colName) {
        $row[$colName] = (!empty($row[$colName]) && $row[$colName] == 1) ? 'Yes' : 'No';
    }

    $userReports[] = $row;
}

$stmtReports->close();

// ✅ Final JSON Response
$response = [
    'churchCode' => $churchCode,
    'churchName' => $churchName, // from admin table
    'dateRange' => [
        'start' => $startDate,
        'end' => $endDate
    ],
    'charts' => [
        'userTakeCount' => $userTakeCount,
        'gender' => $genderStats,
        'timeInFaith' => $faithStats,
        'age' => $ageStats,
        'marital' => $maritalStats,
        'baptized' => $baptizedStats,
        'skillStats' => $skillStats,
        'eligibleMinistry' => $eligibleMinistryStats
    ],
    'userReports' => $userReports
];

echo json_encode($response);
$conn->close();
?>
