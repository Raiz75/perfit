<?php
header('Content-Type: application/json');
include 'php-dbCon.php';
session_start();

if (!isset($_SESSION['admin_email'])) {
    echo json_encode(['status'=>'error','message'=>'Unauthorized']);
    exit();
}

$adminEmail = $_SESSION['admin_email'];

// Get adminID
$getAdmin = $conn->prepare("SELECT adminID FROM admin WHERE email=?");
$getAdmin->bind_param("s",$adminEmail);
$getAdmin->execute();
$res = $getAdmin->get_result();
if($res->num_rows===0){
    echo json_encode(['status'=>'error','message'=>'Admin not found']);
    exit();
}
$adminID = $res->fetch_assoc()['adminID'];
$getAdmin->close();

// Get data from frontend
$input = json_decode(file_get_contents("php://input"), true);
$churchName = $input['churchName'] ?? null;
$tabsData = $input['tabsData'] ?? null;

if(!$tabsData || !is_array($tabsData)){
    echo json_encode(['status'=>'error','message'=>'Missing data']);
    exit();
}

// === Save demographics ===
if(isset($tabsData['demographicsTab'])){
    $stmt = $conn->prepare("
        UPDATE restrictions_demographic
        SET gender=?, age1=?, age2=?, marital=?, baptized=?, timeInFaith=?
        WHERE adminID=? AND ministryID=?
    ");
    foreach($tabsData['demographicsTab'] as $row){
        $stmt->bind_param("iiiiiiii",
            $row['gender'],$row['age1'],$row['age2'],$row['marital'],$row['baptized'],$row['timeInFaith'],
            $adminID,$row['ministryID']
        );
        $stmt->execute();
    }
    $stmt->close();
}

// === Save skills Restrictions===
if(isset($tabsData['skillsTab'])){
    $stmt = $conn->prepare("
        UPDATE restrictions_skill
        SET music=?, technology=?, writing=?, technical=?, speaking=?, accounting=?, mentoring=?, bibleKnowledge=?
        WHERE adminID=? AND ministryID=?
    ");
    foreach($tabsData['skillsTab'] as $row){
        $stmt->bind_param("iiiiiiiiii",
            $row['music'],$row['technology'],$row['writing'],$row['technical'],$row['speaking'],
            $row['accounting'],$row['mentoring'],$row['bibleKnowledge'],
            $adminID,$row['ministryID']
        );
        $stmt->execute();
    }
    $stmt->close();
}
// === Save Skill Questions ===
if(isset($tabsData['skillQuestionsTab'])){
    $stmt = $conn->prepare("
        UPDATE questions_skill
        SET skillQuestionEN=?, skillQuestionTL=?
        WHERE adminID=? AND skillQuestionNum=?
    ");
    foreach($tabsData['skillQuestionsTab'] as $row){
        $stmt->bind_param("ssii",
            $row['skillQuestionEN'],$row['skillQuestionTL'],
            $adminID,$row['skillQuestionNum']
        );
        $stmt->execute();
    }
    $stmt->close();
}

// === Save Interest & Passion Questions===
if(isset($tabsData['interestAndPassionTab'])){
    $stmt = $conn->prepare("
        UPDATE questions_interest_and_passion
        SET interestAndPassionQuestionEN=?, interestAndPassionQuestionTL=?
        WHERE adminID=? AND interestAndPassionQuestionNum=?
    ");
    foreach($tabsData['interestAndPassionTab'] as $row){
        $stmt->bind_param("ssii",
            $row['interestAndPassionQuestionEN'],$row['interestAndPassionQuestionTL'],
            $adminID,$row['interestAndPassionQuestionNum']
        );
        $stmt->execute();
    }
    $stmt->close();
}

// === Save Behavioral Questions===
if(isset($tabsData['behavioralTab'])){
    $stmt = $conn->prepare("
        UPDATE questions_behavioral
        SET behavioralQuestionEN=?, behavioralQuestionTL=?
        WHERE adminID=? AND ministryID=? AND behavioralQuestionNum=?
    ");
    foreach($tabsData['behavioralTab'] as $row){
        $stmt->bind_param("ssiii",
            $row['behavioralQuestionEN'],
            $row['behavioralQuestionTL'],
            $adminID,
            $row['ministryID'],        // ✅ added ministryID
            $row['behavioralQuestionNum']
        );
        $stmt->execute();
    }
    $stmt->close();
}

// === Save church name ===
if(!empty($churchName)){
    $updateChurch = $conn->prepare("UPDATE admin SET churchName=? WHERE adminID=?");
    $updateChurch->bind_param("si",$churchName,$adminID);
    $updateChurch->execute();
    $updateChurch->close();
}

$conn->close();
echo json_encode(['status'=>'success','message'=>'Changes saved successfully!']);
exit();
?>
