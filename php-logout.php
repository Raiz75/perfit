<?php
session_start(); // Start session to destroy it
// Destroy all session data
session_unset();
session_destroy();
// Optional: clear cookies
setcookie(session_name(), '', time() - 3600, '/');
// Return JSON response
echo json_encode([
    "status" => "success",
    "message" => "You have been logged out successfully."
]);
exit;
?>