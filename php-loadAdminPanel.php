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
$ministries = [];
while ($row = $result->fetch_assoc()) {
    $ministries[] = $row;
}


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

// Step 4: Get skills questions
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

// Step 5: Get Interest and Passion questions with category
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

// Step 6: Get Behavioral questions with ministry name
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
        ur.churchName,
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
        ur.timeInFaith
    FROM user_report ur
    WHERE ur.churchName = ?
    ORDER BY ur.userReportID DESC
";
$stmtReports = $conn->prepare($sqlReports);
$stmtReports->bind_param("s", $churchName);
$stmtReports->execute();
$resultReports = $stmtReports->get_result();

$userReports = [];
while ($row = $resultReports->fetch_assoc()) {
    // ✅ Combine skills dynamically
    $skillsList = [];

    $skillLabels = [
        'music' => 'Music',
        'technology' => 'Technology',
        'writing' => 'Writing',
        'technical' => 'Technical',
        'speaking' => 'Speaking',
        'accounting' => 'Accounting',
        'mentoring' => 'Mentoring',
        'bibleKnowledge' => 'Bible Knowledge'
    ];

    foreach ($skillLabels as $key => $label) {
        if (!empty($row[$key]) && $row[$key] == 1) {
            $skillsList[] = $label;
        }
        unset($row[$key]); // Remove raw numeric skill fields
    }

    $row['skills'] = implode(', ', $skillsList); // e.g. "Music, Technology, Mentoring"

    // ✅ Convert coded values into readable text
    $row['gender'] = match ($row['gender']) {
        1 => 'Male',
        2 => 'Female',
        default => '-'
    };

    $row['baptized'] = match ($row['baptized']) {
        1 => 'Yes',
        2 => 'No',
        default => '-'
    };

    $row['timeInFaith'] = match ($row['timeInFaith']) {
        1 => '1+ Week',
        2 => '6+ Months',
        3 => '1+ Year',
        4 => '2+ Years',
        default => '-'
    };

    $row['marital'] = match ($row['marital']){
        1 => 'Single',
        2 => 'Married',
        default => '-'
    };

    $userReports[] = $row;
}

$stmtReports->close();





// Combine everything
$response = [
    'churchName' => $churchName,
    'ministries' => $ministries,
    'skills' => $skills,
    'questions' => $questions,
    'interestQuestions' => $interestQuestions,
    'behavioralQuestions' => $behavioralQuestions,
    'userReports' => $userReports
];

echo json_encode($response);
$stmt->close();
$conn->close();
?>
