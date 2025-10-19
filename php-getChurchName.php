<?php
include 'php-dbCon.php';
// Query all church names
$sql = "SELECT churchName FROM admin";
$result = $conn->query($sql);
$churches = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $churches[] = $row['churchName'];
    }
}
// Return as JSON
header('Content-Type: application/json');
echo json_encode($churches);
?>