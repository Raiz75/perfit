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
// Step 1: Get admin details
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
$churchName = $adminRow['churchName'];
$churchCode = $adminRow['churchCode'];
$getAdmin->close();
// Step 2: Get demographics restrictions
$sql = "
    SELECT 
        m.ministryID,
        m.ministryName,
        r.gender,
        r.age1,
        r.age2,
        r.marital,
        r.baptized,
        r.timeInFaith
    FROM ministries m
    LEFT JOIN restrictions_demographic r
        ON m.ministryID = r.ministryID AND r.adminID = ?
    ORDER BY m.ministryID
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $adminID);
$stmt->execute();
$result = $stmt->get_result();
$ministriesAdmin = [];
while ($row = $result->fetch_assoc()) {
    $ministriesAdmin[] = [
        "ministryID" => $row["ministryID"],
        "ministryName" => $row["ministryName"],
        "gender" => $row["gender"],
        "age1" => $row["age1"],
        "age2" => $row["age2"],
        "marital" => $row["marital"],
        "baptized" => $row["baptized"],
        "timeInFaith" => $row["timeInFaith"]
    ];
}
$stmt->close();
// Step 3: Get skills restrictions
$sqlSkills = "
    SELECT 
        m.ministryID,
        m.ministryName,
        rs.music,
        rs.technology,
        rs.writing,
        rs.technical,
        rs.speaking,
        rs.accounting,
        rs.mentoring,
        rs.bibleKnowledge
    FROM ministries m
    LEFT JOIN restrictions_skill rs
        ON m.ministryID = rs.ministryID AND rs.adminID = ?
    ORDER BY m.ministryID
";
$stmtSkills = $conn->prepare($sqlSkills);
$stmtSkills->bind_param("i", $adminID);
$stmtSkills->execute();
$resultSkills = $stmtSkills->get_result();
$skills = [];
while ($row = $resultSkills->fetch_assoc()) {
    $skills[] = $row;
}
$stmtSkills->close();
// Step 4: Get skill questions
$sqlQuestions = "
    SELECT 
        q.questionsSkillID,
        q.skillQuestionNum,
        q.skillQuestionEN,
        q.skillQuestionTL,
        s.skill AS skillName
    FROM questions_skill q
    LEFT JOIN skills s 
        ON q.skillID = s.skillID
    WHERE q.adminID = ?
    ORDER BY q.skillID, q.skillQuestionNum
";
$stmtQuestions = $conn->prepare($sqlQuestions);
$stmtQuestions->bind_param("i", $adminID);
$stmtQuestions->execute();
$resultQuestions = $stmtQuestions->get_result();
$questions = [];
while ($row = $resultQuestions->fetch_assoc()) {
    $questions[] = $row;
}
$stmtQuestions->close();
// Step 5: Get Interest and Passion questions
$sqlInterest = "
    SELECT 
        q.questionsInterestAndPassionID,
        q.interestAndPassionQuestionNum,
        q.interestAndPassionQuestionEN,
        q.interestAndPassionQuestionTL,
        c.category AS categoryName
    FROM questions_interest_and_passion q
    LEFT JOIN ministry_category c
        ON q.ministryCategoryID = c.ministryCategoryID
    WHERE q.adminID = ?
    ORDER BY q.ministryCategoryID, q.interestAndPassionQuestionNum
";
$stmtInterest = $conn->prepare($sqlInterest);
$stmtInterest->bind_param("i", $adminID);
$stmtInterest->execute();
$resultInterest = $stmtInterest->get_result();
$interestQuestions = [];
while ($row = $resultInterest->fetch_assoc()) {
    $interestQuestions[] = $row;
}
$stmtInterest->close();
// Step 6: Get Behavioral questions
$sqlBehavioral = "
    SELECT 
        q.questionsBehavioralID,
        q.ministryID,
        m.ministryName,
        q.behavioralQuestionNum,
        q.behavioralQuestionEN,
        q.behavioralQuestionTL
    FROM questions_behavioral q
    LEFT JOIN ministries m
        ON q.ministryID = m.ministryID
    WHERE q.adminID = ?
    ORDER BY q.ministryID, q.behavioralQuestionNum
";
$stmtBehavioral = $conn->prepare($sqlBehavioral);
$stmtBehavioral->bind_param("i", $adminID);
$stmtBehavioral->execute();
$resultBehavioral = $stmtBehavioral->get_result();
$behavioralQuestions = [];
while ($row = $resultBehavioral->fetch_assoc()) {
    $behavioralQuestions[] = $row;
}
$stmtBehavioral->close();
// Step 7: Get User Reports
$sqlReports = "
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
$stmtReports = $conn->prepare($sqlReports);
$stmtReports->bind_param("s", $churchCode);
$stmtReports->execute();
$resultReports = $stmtReports->get_result();

$userReports = [];
$userTakeCount = 0;
$userTodayCount = 0;

$today = date('Y-m-d'); // ✅ today's date for comparison

$genderStats = [];
$faithStats = [];
$ageStats = [
    'Under 18' => 0,
    '18-25' => 0,
    '26-35' => 0,
    '36-50' => 0,
    '51+' => 0
];
$maritalStats = [
    'Single' => 0,
    'Married' => 0
];
$baptizedStats = [
    'Yes' => 0,
    'No' => 0
];

$skillStats = [
    'Music' => 0,
    'Technology' => 0,
    'Writing' => 0,
    'Technical' => 0,
    'Speaking' => 0,
    'Accounting' => 0,
    'Mentoring' => 0,
    'Bible Knowledge' => 0
];
$eligibleMinistryStats = [];

$skillColumnMap = [
    'Music' => 'music',
    'Technology' => 'technology',
    'Writing' => 'writing',
    'Technical' => 'technical',
    'Speaking' => 'speaking',
    'Accounting' => 'accounting',
    'Mentoring' => 'mentoring',
    'Bible Knowledge' => 'bibleKnowledge'
];

while ($row = $resultReports->fetch_assoc()) {
    $userTakeCount++;

    // ✅ Count users submitted today
    if (!empty($row['timeOfSubmission'])) {
        $submissionDate = date('Y-m-d', strtotime($row['timeOfSubmission']));
        if ($submissionDate === $today) {
            $userTodayCount++;
        }
    }

    // (keep your existing stats logic below unchanged)
    if ($row['gender'] == 1) {
        $genderStats['Male'] = ($genderStats['Male'] ?? 0) + 1;
    } elseif ($row['gender'] == 2) {
        $genderStats['Female'] = ($genderStats['Female'] ?? 0) + 1;
    }

    $faithMap = [
        1 => '1+ Week',
        2 => '6+ Months',
        3 => '1+ Year',
        4 => '2+ Years'
    ];
    $faithLabel = $faithMap[$row['timeInFaith']] ?? '-';
    if ($faithLabel != '-') {
        $faithStats[$faithLabel] = ($faithStats[$faithLabel] ?? 0) + 1;
    }

    $age = intval($row['age']);
    if ($age > 0 && $age < 18) $ageStats['Under 18']++;
    elseif ($age >= 18 && $age <= 25) $ageStats['18-25']++;
    elseif ($age >= 26 && $age <= 35) $ageStats['26-35']++;
    elseif ($age >= 36 && $age <= 50) $ageStats['36-50']++;
    elseif ($age >= 51) $ageStats['51+']++;

    if ($row['marital'] == 1) $maritalStats['Single']++;
    elseif ($row['marital'] == 2) $maritalStats['Married']++;

    if ($row['baptized'] == 1) $baptizedStats['Yes']++;
    elseif ($row['baptized'] == 2) $baptizedStats['No']++;

    foreach ($skillColumnMap as $label => $colName) {
        if (isset($row[$colName]) && $row[$colName] == 1) {
            $skillStats[$label]++;
        }
    }

    if (!empty($row['eligibleMinistry'])) {
        $ministriesList = array_map('trim', explode(',', $row['eligibleMinistry']));
        foreach ($ministriesList as $min) {
            if ($min !== '') {
                $eligibleMinistryStats[$min] = ($eligibleMinistryStats[$min] ?? 0) + 1;
            }
        }
    }

    $row['gender'] = $row['gender'] == 1 ? 'Male' : ($row['gender'] == 2 ? 'Female' : '-');
    $row['marital'] = $row['marital'] == 1 ? 'Single' : ($row['marital'] == 2 ? 'Married' : '-');
    $row['baptized'] = $row['baptized'] == 1 ? 'Yes' : ($row['baptized'] == 2 ? 'No' : '-');
    $row['timeInFaith'] = $faithLabel;

    foreach ($skillColumnMap as $label => $colName) {
        $row[$colName] = isset($row[$colName]) && $row[$colName] == 1 ? 'Yes' : 'No';
    }

    $userReports[] = $row;
}
$stmtReports->close();


// ✅ Final JSON Response
$response = [
    'churchCode' => $churchCode,
    'churchName' => $churchName,
    'ministriesAdmin' => $ministriesAdmin,
    'skills' => $skills,
    'questions' => $questions,
    'interestQuestions' => $interestQuestions,
    'behavioralQuestions' => $behavioralQuestions,
    'userReports' => $userReports,
    'charts' => [
        'userTakeCount' => $userTakeCount,
        'userTodayCount' => $userTodayCount,
        'gender' => $genderStats,
        'timeInFaith' => $faithStats,
        'age' => $ageStats,
        'marital' => $maritalStats,
        'baptized' => $baptizedStats,
        'skillStats' => $skillStats,
        'eligibleMinistry' => $eligibleMinistryStats
    ]
];



echo json_encode($response);
$conn->close();
?>
