<?php
// products.php
// Shows all products with a search form at the top

session_start();
require_once 'includes/db.php';

$pageTitle = 'Products - ArtStore';

// Check if user typed something in the search box
// $_GET['search'] comes from the URL e.g. products.php?search=brush
// We use trim() to remove extra spaces
// We use htmlspecialchars() to prevent security issues
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

if ($search !== '') {
    // If search is not empty, find products whose title contains the search word
    // The % symbols mean "anything before or after"
    // The :search is a placeholder - PDO will safely insert the value
    $stmt = $pdo->prepare("
        SELECT products.*, users.name AS seller_name
        FROM products
        JOIN users ON products.user_id = users.id
        WHERE products.status = 'active'
        AND products.title LIKE :search
        ORDER BY products.created_at DESC
    ");
    $stmt->execute([':search' => '%' . $search . '%']);
} else {
    // If no search, just get all active products
    $stmt = $pdo->prepare("
        SELECT products.*, users.name AS seller_name
        FROM products
        JOIN users ON products.user_id = users.id
        WHERE products.status = 'active'
        ORDER BY products.created_at DESC
    ");
    $stmt->execute();
}

$products = $stmt->fetchAll();

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