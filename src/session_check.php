<?php
/**
 * Session Check for Admin Pages
 * Include this file at the top of every admin page to ensure proper authentication
 */

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Prevent caching of admin pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

/**
 * Check if user is logged in and has admin privileges
 */
function check_admin_session($redirect_to_login = true) {
    // Check if user is logged in
    if (!isset($_SESSION['username']) || empty($_SESSION['username'])) {
        if ($redirect_to_login) {
            // Store the attempted URL for redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
            header("Location: admin_login.php?error=session_expired");
            exit;
        }
        return false;
    }

    // Check session source and role for database users
    if (isset($_SESSION['source']) && $_SESSION['source'] === 'database') {
        if (!isset($_SESSION['role']) || 
            ($_SESSION['role'] !== 'Admin' && $_SESSION['role'] !== 'Super Admin')) {
            if ($redirect_to_login) {
                session_destroy();
                header("Location: admin_login.php?error=insufficient_privileges");
                exit;
            }
            return false;
        }
    }

    // Optional: Check session timeout (uncomment if you want session timeout)
    /*
    $timeout_duration = 3600; // 1 hour in seconds
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > $timeout_duration) {
        session_destroy();
        if ($redirect_to_login) {
            header("Location: admin_login.php?error=session_timeout");
            exit;
        }
        return false;
    }
    $_SESSION['last_activity'] = time();
    */

    return true;
}

/**
 * Get current admin user info
 */
function get_current_admin_user() {
    if (!check_admin_session(false)) {
        return null;
    }
    
    return [
        'username' => $_SESSION['username'],
        'source' => $_SESSION['source'] ?? 'unknown',
        'role' => $_SESSION['role'] ?? 'Admin'
    ];
}

/**
 * Generate logout button HTML
 */
function get_admin_logout_button($classes = 'logout-btn', $text = 'Se déconnecter') {
    $user = get_current_admin_user();
    $username = $user ? htmlspecialchars($user['username']) : 'Utilisateur';
    
    return '
    <div class="user-info">
        <span class="username">Connecté: ' . $username . '</span>
        <a href="logout.php" class="' . $classes . '" onclick="return confirm(\'Êtes-vous sûr de vouloir vous déconnecter ?\');">
            ' . $text . '
        </a>
    </div>';
}

// Automatically check session unless explicitly disabled
if (!defined('SKIP_SESSION_CHECK')) {
    check_admin_session();
}
?>