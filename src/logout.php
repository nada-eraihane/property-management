<?php
session_start();

// Store some info for the logout message before destroying session
$was_logged_in = isset($_SESSION['username']);
$username = $_SESSION['username'] ?? '';

// Destroy all session data
$_SESSION = array();

// Delete the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Start a new session for the logout message
session_start();
$_SESSION['logout_message'] = $was_logged_in ? "Vous avez été déconnecté avec succès." : "Aucune session active trouvée.";
$_SESSION['logged_out_user'] = $username;

// Prevent caching
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to logout confirmation page
header("Location: logout_confirmation.php");
exit;
?>