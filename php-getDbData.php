<?php
include 'php-dbCon.php';
header('Content-Type: application/json');

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
$churchCode = isset($data['churchCode']) ? trim($data['churchCode']) : '';
$response = [];

if (empty($churchCode)) {
    echo json_encode(["error" => "Church code is required."]);
    exit;
}

// --- Step 1: Get adminID from churchCode ---
$adminQuery = $conn->prepare("SELECT adminID, churchName FROM admin WHERE BINARY churchCode = ?");
$adminQuery->bind_param("s", $churchCode);
$adminQuery->execute();
$adminResult = $adminQuery->get_result();

if ($adminResult->num_rows > 0) {
    $adminRow = $adminResult->fetch_assoc();
    $adminID = $adminRow['adminID'];
    $churchName = $adminRow['churchName'];

    // --- Step 2: Ministries ---
    $ministriesQuery = $conn->prepare("SELECT * FROM ministries");
    $ministriesQuery->execute();
    $ministriesResult = $ministriesQuery->get_result();
    $ministries = [];
    while ($row = $ministriesResult->fetch_assoc()) {
        $ministries[] = $row;
    }

    // --- Step 3: Demographic Restrictions ---
    $demoQuery = $conn->prepare("SELECT * FROM restrictions_demographic WHERE adminID = ?");
    $demoQuery->bind_param("i", $adminID);
    $demoQuery->execute();
    $demoRes = $demoQuery->get_result();
    $restrictions_demographic = [];
    while ($row = $demoRes->fetch_assoc()) {
        $restrictions_demographic[] = $row;
    }

    // --- Step 4: Skill Restrictions ---
    $skillResQuery = $conn->prepare("SELECT * FROM restrictions_skill WHERE adminID = ?");
    $skillResQuery->bind_param("i", $adminID);
    $skillResQuery->execute();
    $skillRes = $skillResQuery->get_result();
    $restrictions_skill = [];
    while ($row = $skillRes->fetch_assoc()) {
        $restrictions_skill[] = $row;
    }

    // --- Step 5: Skill Questions ---
    $skillQ = $conn->prepare("SELECT * FROM questions_skill WHERE adminID = ?");
    $skillQ->bind_param("i", $adminID);
    $skillQ->execute();
    $skillRes = $skillQ->get_result();
    $questions_skill = [];
    while ($row = $skillRes->fetch_assoc()) {
        $questions_skill[] = $row;
    }

    // --- Step 6: Interest & Passion Questions ---
    $interestQ = $conn->prepare("SELECT * FROM questions_interest_and_passion WHERE adminID = ?");
    $interestQ->bind_param("i", $adminID);
    $interestQ->execute();
    $interestRes = $interestQ->get_result();
    $questions_interest_and_passion = [];
    while ($row = $interestRes->fetch_assoc()) {
        $questions_interest_and_passion[] = $row;
    }

    // --- Step 7: Behavioral Questions ---
    $behaviorQ = $conn->prepare("SELECT * FROM questions_behavioral WHERE adminID = ?");
    $behaviorQ->bind_param("i", $adminID);
    $behaviorQ->execute();
    $behaviorRes = $behaviorQ->get_result();
    $questions_behavioral = [];
    while ($row = $behaviorRes->fetch_assoc()) {
        $questions_behavioral[] = $row;
    }

    // --- Combine all results ---
    $response = [
        "churchName" => $churchName,
        "ministries" => $ministries,
        "restrictions_demographic" => $restrictions_demographic,
        "restrictions_skill" => $restrictions_skill,
        "questions_skill" => $questions_skill,
        "questions_interest_and_passion" => $questions_interest_and_passion,
        "questions_behavioral" => $questions_behavioral
    ];

} else {
    $response["error"] = "Church code not found.";
}

// --- Output final JSON ---
echo json_encode($response);
$conn->close();
?>
