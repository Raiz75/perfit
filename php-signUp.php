<?php
header('Content-Type: application/json');
include 'php-dbCon.php';

// Get input
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 🧩 Basic validation
if (empty($email) || empty($password)) {
    echo json_encode(["success" => false, "message" => "Please fill in all fields."]);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["success" => false, "message" => "Invalid email format."]);
    exit;
}

// 🔍 Check if email already exists
$check = $conn->prepare("SELECT 1 FROM admin WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$result = $check->get_result();
if ($result->num_rows > 0) {
    echo json_encode(["success" => false, "message" => "Email already exists."]);
    exit;
}
$check->close();

// 🔐 Hash password
$hashed = password_hash($password, PASSWORD_DEFAULT);

// 🟢 Function to generate unique 9-character alphanumeric church code
function generateChurchCode($length = 9) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

// Generate unique church code
$churchCode = generateChurchCode();

// Make sure code is unique
$checkCode = $conn->prepare("SELECT 1 FROM admin WHERE churchCode = ?");
$checkCode->bind_param("s", $churchCode);
$checkCode->execute();
$resultCode = $checkCode->get_result();
while ($resultCode->num_rows > 0) {
    $churchCode = generateChurchCode();
    $checkCode->bind_param("s", $churchCode);
    $checkCode->execute();
    $resultCode = $checkCode->get_result();
}
$checkCode->close();

// 🧾 Insert new admin with churchCode
$insert = $conn->prepare("INSERT INTO admin (email, password, churchCode) VALUES (?, ?, ?)");
$insert->bind_param("sss", $email, $hashed, $churchCode);

if ($insert->execute()) {
    $newAdminID = $conn->insert_id;

    // ==================================================
    // ✅ COPY DEFAULT DATA FROM ADMINID = 1
    // ==================================================
    $defaultAdmin = 1;

    // 1️⃣ Copy demographic restrictions
    $copyDemo = "
        INSERT INTO restrictions_demographic (adminID, ministryID, gender, age1, age2, marital, baptized, timeInFaith)
        SELECT ?, ministryID, gender, age1, age2, marital, baptized, timeInFaith
        FROM restrictions_demographic
        WHERE adminID = ?
    ";
    $stmt = $conn->prepare($copyDemo);
    $stmt->bind_param("ii", $newAdminID, $defaultAdmin);
    $stmt->execute();
    $stmt->close();

    // 2️⃣ Copy skill restrictions
    $copySkill = "
        INSERT INTO restrictions_skill (adminID, ministryID, music, technology, technical, speaking, accounting, mentoring, bibleKnowledge)
        SELECT ?, ministryID, music, technology, technical, speaking, accounting, mentoring, bibleKnowledge
        FROM restrictions_skill
        WHERE adminID = ?
    ";
    $stmt = $conn->prepare($copySkill);
    $stmt->bind_param("ii", $newAdminID, $defaultAdmin);
    $stmt->execute();
    $stmt->close();

    // 3️⃣ Copy skill questions
    $copyQSkill = "
        INSERT INTO questions_skill (adminID, skillID, skillQuestionNum, skillQuestionEN, skillQuestionTL)
        SELECT ?, skillID, skillQuestionNum, skillQuestionEN, skillQuestionTL
        FROM questions_skill
        WHERE adminID = ?
    ";
    $stmt = $conn->prepare($copyQSkill);
    $stmt->bind_param("ii", $newAdminID, $defaultAdmin);
    $stmt->execute();
    $stmt->close();

    // 4️⃣ Copy interest & passion questions
    $copyQInterest = "
        INSERT INTO questions_interest_and_passion (adminID, ministryCategoryID, interestAndPassionQuestionNum, interestAndPassionQuestionEN, interestAndPassionQuestionTL)
        SELECT ?, ministryCategoryID, interestAndPassionQuestionNum, interestAndPassionQuestionEN, interestAndPassionQuestionTL
        FROM questions_interest_and_passion
        WHERE adminID = ?
    ";
    $stmt = $conn->prepare($copyQInterest);
    $stmt->bind_param("ii", $newAdminID, $defaultAdmin);
    $stmt->execute();
    $stmt->close();

    // 5️⃣ Copy behavioral questions
    $copyQBehavioral = "
        INSERT INTO questions_behavioral (adminID, ministryID, behavioralQuestionNum, behavioralQuestionEN, behavioralQuestionTL)
        SELECT ?, ministryID, behavioralQuestionNum, behavioralQuestionEN, behavioralQuestionTL
        FROM questions_behavioral
        WHERE adminID = ?
    ";
    $stmt = $conn->prepare($copyQBehavioral);
    $stmt->bind_param("ii", $newAdminID, $defaultAdmin);
    $stmt->execute();
    $stmt->close();

    // ==================================================
    // ✅ Step 2: Assign default church name
    // ==================================================
    $churchName = "Church " . $newAdminID;
    $updateChurch = $conn->prepare("UPDATE admin SET churchName = ? WHERE adminID = ?");
    $updateChurch->bind_param("si", $churchName, $newAdminID);
    $updateChurch->execute();
    $updateChurch->close();

    // ==================================================
    // ✅ SUCCESS RESPONSE
    // ==================================================
    echo json_encode([
        "success" => true,
        "message" => "Account created successfully! Default restrictions and questions copied from admin #1. Church name set to '$churchName', church code set to '$churchCode'.",
        "churchCode" => $churchCode
    ]);
} else {
    echo json_encode(["success" => false, "message" => "Error creating account."]);
}

$conn->close();
?>
