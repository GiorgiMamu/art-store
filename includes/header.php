<?php
// header.php
// This is included at the top of every page.
// It outputs the <head> section and the navigation bar.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- This makes the page work properly on phones -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- $pageTitle is set on each page before including this file -->
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Art Store'; ?></title>

    <!-- Our stylesheet -->
    <link rel="stylesheet" href="/art-store/css/style.css">

    <!-- jQuery loaded from the internet (CDN) -->
    <!-- This must be loaded before our main.js -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>

<!-- Navigation bar -->
<nav class="navbar">
    <div class="nav-inner">

        <!-- Site logo / name -->
        <a href="/art-store/index.php" class="nav-logo">🎨 ArtStore</a>

        <!-- Hamburger button - only shows on mobile -->
        <!-- When clicked, jQuery will show/hide the nav links -->
        <button class="nav-burger" id="burgerBtn">&#9776;</button>

        <!-- Nav links -->
        <ul class="nav-links" id="navLinks">
            <li><a href="/art-store/index.php">Home</a></li>
            <li><a href="/art-store/products.php">Products</a></li>

            <?php if (isset($_SESSION['user_id'])): ?>
                <!-- Only show these links when user is logged in -->
                <li><a href="/art-store/dashboard.php">Dashboard</a></li>

                <?php if ($_SESSION['user_role'] === 'admin'): ?>
                    <!-- Only show this link to admins -->
                    <li><a href="/art-store/admin.php">Admin</a></li>
                <?php endif; ?>

                <li><a href="/art-store/logout.php">Logout</a></li>
                <li class="nav-greeting">Hi, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</li>

            <?php else: ?>
                <!-- Show login link when not logged in -->
                <li><a href="/art-store/auth.php">Login</a></li>
            <?php endif; ?>
        </ul>

    </div>
</nav>

<!-- Every page's content goes after this -->
<main class="page-content">