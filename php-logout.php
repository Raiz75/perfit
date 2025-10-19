<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Optional: clear cookies
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Return JSON response (if used via fetch)
echo json_encode([
    "status" => "success",
    "message" => "You have been logged out successfully."
]);
exit;
?>
