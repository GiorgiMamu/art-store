<?php
require_once 'includes/session.php';
require_once 'includes/classes.php';

$pageTitle      = 'ArtStore - Home';
$productManager = new ProductManager($pdo);
$featured       = $productManager->getAll('active');
// Limit to 4 manually 
$featured       = array_slice($featured, 0, 4);

require_once 'includes/header.php';
?>

<!-- Hero section - big banner at the top -->
<section class="hero">
    <div class="hero-text">
        <h1>Welcome to ArtStore</h1>
        <p>Your one-stop shop for quality art supplies</p>
        <a href="/art-store/products.php" class="btn btn-primary">Browse Products</a>
    </div>
</section>

<!-- Featured products section -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Featured Products</h2>

        <?php if (empty($featured)): ?>
            <!-- Show this message if no products exist yet -->
            <p class="empty-msg">No products yet. Check back soon!</p>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($featured as $product): ?>
                    <!-- One card per product -->
                    <div class="product-card">

                        <!-- Product image -->
                        <?php if ($product['image']): ?>
                            <img
                                src="/art-store/uploads/<?php echo htmlspecialchars($product['image']); ?>"
                                alt="<?php echo htmlspecialchars($product['title']); ?>"
                                class="product-card-img"
                            >
                        <?php else: ?>
                            <!-- Placeholder if no image -->
                            <div class="product-card-placeholder">🎨</div>
                        <?php endif; ?>

                        <div class="product-card-body">
                            <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            <a href="/art-store/product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">View Details</a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="center-link">
            <a href="/art-store/products.php" class="btn btn-primary">See All Products</a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>