<?php
header('Content-Type: application/json');
include 'php-dbCon.php';

// Get the churchName from the frontend
$data = json_decode(file_get_contents("php://input"), true);
$churchName = isset($data['churchName']) ? $data['churchName'] : '';
$response = [];

// Find adminID from churchName
$adminQuery = $conn->prepare("SELECT adminID FROM admin WHERE churchName = ?");
$adminQuery->bind_param("s", $churchName);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();

if ($adminResult->num_rows > 0) {
    $adminRow = $adminResult->fetch_assoc();
    $adminID = $adminRow['adminID'];

    // Questions: Skill
    $skillQ = $conn->prepare("SELECT * FROM questions_skill WHERE adminID = ?");
    $skillQ->bind_param("i", $adminID);
    $skillQ->execute();
    $skillRes = $skillQ->get_result();
    $questions_skill = [];
    while ($row = $skillRes->fetch_assoc()) {
        $questions_skill[] = $row;
    }

    // Questions: Interest & Passion
    $interestQ = $conn->prepare("SELECT * FROM questions_interest_and_passion WHERE adminID = ?");
    $interestQ->bind_param("i", $adminID);
    $interestQ->execute();
    $interestRes = $interestQ->get_result();
    $questions_interest = [];
    while ($row = $interestRes->fetch_assoc()) {
        $questions_interest[] = $row;
    }

    // Questions: Behavioral
    $behaviorQ = $conn->prepare("SELECT * FROM questions_behavioral WHERE adminID = ?");
    $behaviorQ->bind_param("i", $adminID);
    $behaviorQ->execute();
    $behaviorRes = $behaviorQ->get_result();
    $questions_behavioral = [];
    while ($row = $behaviorRes->fetch_assoc()) {
        $questions_behavioral[] = $row;
    }

    $response['questions_skill'] = $questions_skill;
    $response['questions_interest_and_passion'] = $questions_interest;
    $response['questions_behavioral'] = $questions_behavioral;
} else {
    $response['error'] = "ChurchName not found.";
}

echo json_encode($response);
$conn->close();
?>
