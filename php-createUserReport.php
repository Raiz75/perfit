<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

include 'php-dbCon.php';

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['name']) || empty($data['email'])) {
    echo json_encode(['error' => 'Name and Email are required']);
    exit;
}

// Eligible ministries as string
$eligibleMinistryStr = trim($data['eligibleMinistry'] ?? '');

try {
    $stmt = $conn->prepare("INSERT INTO user_report 
        (churchName, email, name, music, technology, writing, technical, speaking, accounting, mentoring, bibleKnowledge, eligibleMinistry, gender, age, marital, baptized, timeInFaith)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    // 4 strings + 13 integers = 17 params
    $stmt->bind_param(
        "sssiiiiiiiisiiiii",
        $data['churchName'],       // s
        $data['email'],            // s
        $data['name'],             // s
        $data['music'],            // i
        $data['technology'],       // i
        $data['writing'],          // i
        $data['technical'],        // i
        $data['speaking'],         // i
        $data['accounting'],       // i
        $data['mentoring'],        // i
        $data['bibleKnowledge'],   // i
        $eligibleMinistryStr,      // s
        $data['gender'],           // i
        $data['age'],              // i
        $data['marital'],          // i
        $data['baptized'],         // i
        $data['timeInFaith']       // i
    );

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => $stmt->error]);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
