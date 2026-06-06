<?php
require_once 'includes/session.php';
require_once 'includes/classes.php';

$pageTitle      = 'Products - ArtStore';
$productManager = new ProductManager($pdo);
$search         = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    $products = $productManager->search($search);
} else {
    $products = $productManager->getAll('active');
}

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">All Products</h1>

        <!-- Search form -->
        <!-- method="get" puts the search in the URL so the page can read it -->
        <form class="search-form" method="get" action="">
            <input
                type="text"
                name="search"
                placeholder="Search products..."
                value="<?php echo htmlspecialchars($search); ?>"
                autocomplete="off"
            >
            <button type="submit" class="btn btn-primary">Search</button>

            <!-- Only show the clear button if a search is active -->
            <?php if ($search !== ''): ?>
                <a href="/art-store/products.php" class="btn btn-outline">Clear</a>
            <?php endif; ?>
        </form>

        <!-- Show what was searched for -->
        <?php if ($search !== ''): ?>
            <p class="search-info">
                Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong>
                (<?php echo count($products); ?> found)
            </p>
        <?php endif; ?>

        <?php if (empty($products)): ?>
            <p class="empty-msg">No products found.</p>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">

                        <?php if ($product['image']): ?>
                            <img
                                src="/art-store/uploads/<?php echo htmlspecialchars($product['image']); ?>"
                                alt="<?php echo htmlspecialchars($product['title']); ?>"
                                class="product-card-img"
                            >
                        <?php else: ?>
                            <div class="product-card-placeholder">🎨</div>
                        <?php endif; ?>

                        <div class="product-card-body">
                            <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            <p class="product-seller">By: <?php echo htmlspecialchars($product['seller_name']); ?></p>
                            <a href="/art-store/product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline">View Details</a>
                        </div>

                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>