<?php
// footer.php
// Included at the bottom of every page.
// Closes the tags opened in header.php
?>

</main><!-- end .page-content -->

<footer class="site-footer">
    <div class="footer-inner">
        <p>&copy; <?php echo date('Y'); ?> ArtStore. All rights reserved.</p>
        <nav>
            <a href="/art-store/index.php">Home</a>
            <a href="/art-store/products.php">Products</a>
            <a href="/art-store/auth.php">Login</a>
        </nav>
    </div>
</footer>

<!-- Our JavaScript file -->
<!-- Loaded at the bottom so the HTML is ready before JS runs -->
<script src="/art-store/js/main.js"></script>

</body>
</html>