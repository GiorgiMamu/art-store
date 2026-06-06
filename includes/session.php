<?php
// session.php
// It starts the session AND checks the remember me cookie automatically

session_start();

// Session timeout - log out after 30 minutes of inactivity
$timeout = 1800;
if (isset($_SESSION['user_id']) && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $timeout) {
        session_destroy();
        header('Location: /art-store/auth.php');
        exit;
    }
}
// Update last activity time on every page load
if (isset($_SESSION['user_id'])) {
    $_SESSION['last_activity'] = time();
}
// Only check the cookie if the user is NOT already logged in via session
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    
    require_once __DIR__ . '/db.php';
    
    $token = $_COOKIE['remember_token'];
    
    // Look for a user with this token in the database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE remember_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Token matched — restore the session
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
    } else {
        // Token not found — delete the bad cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
}
?>