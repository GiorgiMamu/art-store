<?php
// auth.php
// Handles both Login and Register on the same page
// Two forms are shown - user clicks tabs to switch between them

session_start();

// If already logged in, go to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: /art-store/dashboard.php');
    exit;
}

require_once 'includes/db.php';

$error   = '';
$success = '';

// This runs when any form is submitted (method POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // A hidden field in each form tells us which form was submitted
    $action = $_POST['action'] ?? '';

    // REGISTER
    if ($action === 'register') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm'] ?? '';

        // Basic checks
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // filter_var checks if it looks like a real email address
            $error = 'Please enter a valid email.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);

            if ($stmt->fetch()) {
                $error = 'That email is already registered.';
            } else {
                // password_hash() scrambles the password so it is stored safely
                // Never store plain text passwords
                $hashed = password_hash($password, PASSWORD_DEFAULT);

                $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$name, $email, $hashed]);

                $success = 'Account created! You can now log in.';
            }
        }
    }

    // LOGIN
    if ($action === 'login') {
        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rememberMe = isset($_POST['remember_me']);

        if (empty($email) || empty($password)) {
            $error = 'Please enter your email and password.';
        } else {
            // Find the user by email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            // password_verify() checks the password against the stored hash
            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Wrong email or password.';
            } else {
                // Login successful - save user info in the session
                // Sessions are stored on the server and identified by a cookie
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = $user['role'];

                // Remember me - sets a cookie that lasts 30 days
                if ($rememberMe) {
                    $token = bin2hex(random_bytes(32)); // random secure string
                    $stmt = $pdo->prepare("UPDATE users SET remember_token = ? WHERE id = ?");
                    $stmt->execute([$token, $user['id']]);
                    // setcookie(name, value, expiry, path)
                    setcookie('remember_token', $token, [
                        'expires'  => time() + (30 * 24 * 60 * 60),
                        'path'     => '/',
                        'httponly' => true,
                        'samesite' => 'Strict'
                    ]);              
                }

                // Send admin to admin panel, others to dashboard
                if ($user['role'] === 'admin') {
                    header('Location: /art-store/admin.php');
                } else {
                    header('Location: /art-store/dashboard.php');
                }
                exit;
            }
        }
    }
}

$pageTitle = 'Login / Register - ArtStore';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <div class="auth-box">

            <!-- Tab buttons to switch between forms -->
            <div class="auth-tabs">
                <button class="auth-tab active" data-target="loginForm">Login</button>
                <button class="auth-tab" data-target="registerForm">Register</button>
            </div>

            <!-- Error message -->
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <!-- Success message -->
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <!-- LOGIN FORM -->
            <div id="loginForm" class="auth-form">
                <h2>Login</h2>
                <!--
                    method="post" sends form data securely in the request body
                    The hidden "action" field tells PHP which form this is
                -->
                <form method="post" action="">
                    <input type="hidden" name="action" value="login">

                    <div class="form-group">
                        <label for="loginEmail">Email</label>
                        <input
                            type="email"
                            id="loginEmail"
                            name="email"
                            placeholder="you@example.com"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <div class="form-group">
                        <label for="loginPassword">Password</label>
                        <input
                            type="password"
                            id="loginPassword"
                            name="password"
                            placeholder="Your password"
                            required
                            autocomplete="current-password"
                        >
                    </div>

                    <div class="form-group form-check">
                        <input type="checkbox" id="rememberMe" name="remember_me">
                        <label for="rememberMe">Remember me for 30 days</label>
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Login</button>
                </form>
            </div>

            <!-- REGISTER FORM (hidden by default, shown when tab clicked) -->
            <div id="registerForm" class="auth-form" style="display:none;">
                <h2>Create Account</h2>
                <form method="post" action="">
                    <input type="hidden" name="action" value="register">

                    <div class="form-group">
                        <label for="regName">Full Name</label>
                        <input
                            type="text"
                            id="regName"
                            name="name"
                            placeholder="Your name"
                            required
                            autocomplete="name"
                            maxlength="100"
                        >
                    </div>

                    <div class="form-group">
                        <label for="regEmail">Email</label>
                        <input
                            type="email"
                            id="regEmail"
                            name="email"
                            placeholder="you@example.com"
                            required
                            autocomplete="email"
                        >
                    </div>

                    <div class="form-group">
                        <label for="regPassword">Password</label>
                        <input
                            type="password"
                            id="regPassword"
                            name="password"
                            placeholder="At least 6 characters"
                            required
                            minlength="6"
                            autocomplete="new-password"
                        >
                    </div>

                    <div class="form-group">
                        <label for="regConfirm">Confirm Password</label>
                        <input
                            type="password"
                            id="regConfirm"
                            name="confirm"
                            placeholder="Repeat your password"
                            required
                            minlength="6"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary btn-full">Create Account</button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>