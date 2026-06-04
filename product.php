<?php
// product.php
// Shows one product's full details
// The product ID comes from the URL: product.php?id=5

session_start();
require_once 'includes/db.php';

// Get the id from the URL
// FILTER_VALIDATE_INT makes sure it's actually a number
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

// If no valid id, send user back to products page
if (!$id) {
    header('Location: /art-store/products.php');
    exit;
}

// Get this product from the database
// We also get the seller's name by joining the users table
$stmt = $pdo->prepare("
    SELECT products.*, users.name AS seller_name
    FROM products
    JOIN users ON products.user_id = users.id
    WHERE products.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

// If product not found, go back to products page
if (!$product) {
    header('Location: /art-store/products.php');
    exit;
}

// Get the categories this product belongs to
$stmt2 = $pdo->prepare("
    SELECT categories.name
    FROM categories
    JOIN product_categories ON categories.id = product_categories.category_id
    WHERE product_categories.product_id = ?
");
$stmt2->execute([$id]);
$categories = $stmt2->fetchAll();

$pageTitle = htmlspecialchars($product['title']) . ' - ArtStore';

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">

        <!-- Back link -->
        <a href="/art-store/products.php" class="back-link">← Back to Products</a>

        <div class="product-detail">

            <!-- Left side: image -->
            <div class="product-detail-img-wrap">
                <?php if ($product['image']): ?>
                    <img
                        src="/art-store/uploads/<?php echo htmlspecialchars($product['image']); ?>"
                        alt="<?php echo htmlspecialchars($product['title']); ?>"
                        class="product-detail-img"
                    >
                <?php else: ?>
                    <div class="product-detail-placeholder">🎨</div>
                <?php endif; ?>
            </div>

            <!-- Right side: info -->
            <div class="product-detail-info">
                <h1><?php echo htmlspecialchars($product['title']); ?></h1>

                <p class="product-detail-price">$<?php echo number_format($product['price'], 2); ?></p>

                <!-- Categories -->
                <?php if (!empty($categories)): ?>
                    <div class="product-tags">
                        <?php foreach ($categories as $cat): ?>
                            <span class="tag"><?php echo htmlspecialchars($cat['name']); ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Description -->
                <div class="product-description">
                    <h3>Description</h3>
                    <!-- nl2br() turns newlines into <br> tags so line breaks show -->
                    <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>

                <p class="product-meta">Sold by: <strong><?php echo htmlspecialchars($product['seller_name']); ?></strong></p>
                <p class="product-meta">Listed: <?php echo date('F j, Y', strtotime($product['created_at'])); ?></p>

                <a href="/art-store/products.php" class="btn btn-outline">← Back to Products</a>
            </div>

        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>