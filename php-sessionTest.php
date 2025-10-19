<?php
session_start();
// Redirect if session not active
if (!isset($_SESSION['admin_email'])) {
    header("Location: admin.html");
    exit();
}
?>