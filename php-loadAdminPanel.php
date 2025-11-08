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

// ✅ Final JSON Response
$response = [
    'churchCode' => $churchCode,
    'churchName' => $churchName,
    'ministriesAdmin' => $ministriesAdmin,
    'skills' => $skills,
    'questions' => $questions,
    'interestQuestions' => $interestQuestions,
    'behavioralQuestions' => $behavioralQuestions
    
];
echo json_encode($response);
$conn->close();
?>
