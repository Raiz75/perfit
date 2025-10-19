<?php
session_start();
if (!isset($_SESSION['admin_email'])) {
    header("Location: admin.html");
    exit();
}
?>