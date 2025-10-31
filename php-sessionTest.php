<?php
session_start();

if (!isset($_SESSION['admin_email'])) {
    header("Location: admin.html");
    exit();
}

header('Content-Type: application/json');
$adminEmail = $_SESSION['admin_email'];

echo json_encode([
    'email' => $adminEmail
]);
?>