<?php
header('Content-Type: application/json');
include 'php-dbCon.php';
session_start();

if (!isset($_SESSION['admin_email'])) {
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$tab = $input['tab'] ?? '';

$adminEmail = $_SESSION['admin_email'];
$getAdmin = $conn->prepare("SELECT adminID FROM admin WHERE email = ?");
$getAdmin->bind_param("s", $adminEmail);
$getAdmin->execute();
$result = $getAdmin->get_result();
if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "Admin not found."]);
    exit;
}
$adminID = $result->fetch_assoc()['adminID'];
$getAdmin->close();

$status = "error";
$message = "Invalid tab specified.";

switch ($tab) {
    case "demographicsTab":
        $sql = "
            UPDATE restrictions_demographic AS r
            JOIN restrictions_demographic AS d ON r.ministryID = d.ministryID
            SET 
                r.gender = d.gender,
                r.age1 = d.age1,
                r.age2 = d.age2,
                r.marital = d.marital,
                r.baptized = d.baptized,
                r.timeInFaith = d.timeInFaith
            WHERE r.adminID = ? AND d.adminID = 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $adminID);
        $stmt->execute();
        $status = "success";
        $message = "Demographic restrictions reset to default.";
        break;

    case "skillsTab1":
        // ✅ Reset skill restrictions
        $sql = "
            UPDATE restrictions_skill AS r
            JOIN restrictions_skill AS d ON r.ministryID = d.ministryID
            SET 
                r.music = d.music,
                r.technology = d.technology,
                r.writing = d.writing,
                r.technical = d.technical,
                r.speaking = d.speaking,
                r.accounting = d.accounting,
                r.mentoring = d.mentoring,
                r.bibleKnowledge = d.bibleKnowledge
            WHERE r.adminID = ? AND d.adminID = 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $adminID);
        $stmt->execute();

    case "skillsTab2":
        // ✅ Reset skill profiling questions
        $sql2 = "
            UPDATE questions_skill AS q
            JOIN questions_skill AS d 
                ON q.skillQuestionNum = d.skillQuestionNum
            SET 
                q.skillQuestionEN = d.skillQuestionEN,
                q.skillQuestionTL = d.skillQuestionTL
            WHERE q.adminID = ? AND d.adminID = 1
        ";
        $stmt2 = $conn->prepare($sql2);
        $stmt2->bind_param("i", $adminID);
        $stmt2->execute();

        $status = "success";
        $message = "Skill restrictions and skill profiling questions reset to default.";
        break;

    case "interestAndPassionTab":
        $sql = "
            UPDATE questions_interest_and_passion AS q
            JOIN questions_interest_and_passion AS d 
                ON q.interestAndPassionQuestionNum = d.interestAndPassionQuestionNum
            SET 
                q.interestAndPassionQuestionEN = d.interestAndPassionQuestionEN,
                q.interestAndPassionQuestionTL = d.interestAndPassionQuestionTL
            WHERE q.adminID = ? AND d.adminID = 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $adminID);
        $stmt->execute();
        $status = "success";
        $message = "Interest & Passion profiling questions reset to default.";
        break;

    case "behavioralTab":
        $sql = "
            UPDATE questions_behavioral AS q
            JOIN questions_behavioral AS d 
                ON q.ministryID = d.ministryID 
                AND q.behavioralQuestionNum = d.behavioralQuestionNum
            SET 
                q.behavioralQuestionEN = d.behavioralQuestionEN,
                q.behavioralQuestionTL = d.behavioralQuestionTL
            WHERE q.adminID = ? AND d.adminID = 1
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $adminID);
        $stmt->execute();
        $status = "success";
        $message = "Behavioral profiling questions reset to default.";
        break;
}

echo json_encode(["status" => $status, "message" => $message]);
$conn->close();
?>
