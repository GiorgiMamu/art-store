<?php
require_once 'includes/session.php';
require_once 'includes/classes.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: /art-store/products.php');
    exit;
}

$productManager = new ProductManager($pdo);
$product        = $productManager->getById($id);

if (!$product) {
    header('Location: /art-store/products.php');
    exit;
}

$categories = $productManager->getCategoriesForProduct($id);
$pageTitle  = htmlspecialchars($product['title']) . ' - ArtStore';

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