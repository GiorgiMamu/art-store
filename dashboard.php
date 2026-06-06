<?php
require_once 'includes/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /art-store/auth.php');
    exit;
}
if ($_SESSION['user_role'] === 'admin') {
    header('Location: /art-store/admin.php');
    exit;
}

require_once 'includes/classes.php';

$productManager = new ProductManager($pdo);
$fileManager    = new FileManager();
$error          = '';
$success        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $cats        = $_POST['categories'] ?? [];
        $imageName   = null;

        if (empty($title) || $price <= 0) {
            $error = 'Title and a valid price are required.';
        } else {
            if (!empty($_FILES['image']['name'])) {
                $result = $fileManager->uploadImage($_FILES['image']);
                // If result starts with 'img_' it's a filename, otherwise it's an error
                if (strpos($result, 'img_') === 0) {
                    $imageName = $result;
                } else {
                    $error = $result;
                }
            }

            if (!$error) {
                $productManager->create($title, $description, $price, $imageName, $_SESSION['user_id'], $cats);
                $success = 'Product added successfully!';
            }
        }
    }

    if ($action === 'delete') {
        $productId = intval($_POST['product_id']);
        // Verify ownership before deleting
        $product = $productManager->getById($productId);
        if ($product && $product['user_id'] == $_SESSION['user_id']) {
            $productManager->delete($productId);
            $success = 'Product deleted.';
        } else {
            $error = 'Product not found or permission denied.';
        }
    }
}

$myProducts = $productManager->getByUser($_SESSION['user_id']);
$categories = $productManager->getCategories();
$pageTitle  = 'Dashboard - ArtStore';

require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">

        <div class="dashboard-header">
            <h1>My Dashboard</h1>
            <p>
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
                — Role:
                <span class="role-badge role-<?php echo $_SESSION['user_role']; ?>">
                    <?php echo ucfirst($_SESSION['user_role']); ?>
                </span>
            </p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="dashboard-grid">

            <!-- ADD PRODUCT FORM -->
            <div class="panel">
                <h2>Add New Product</h2>

                <!--
                    enctype="multipart/form-data" is required when the form uploads files
                    Without it, the file will not be sent to the server
                -->
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="add">

                    <div class="form-group">
                        <label for="title">Product Title</label>
                        <input
                            type="text"
                            id="title"
                            name="title"
                            placeholder="e.g. Professional Watercolors"
                            required
                            maxlength="200"
                        >
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea
                            id="description"
                            name="description"
                            rows="4"
                            placeholder="Describe the product..."
                            maxlength="1000"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <label for="price">Price ($)</label>
                        <input
                            type="number"
                            id="price"
                            name="price"
                            placeholder="e.g. 19.99"
                            required
                            min="0.01"
                            step="0.01"
                        >
                    </div>

                    <div class="form-group">
                        <label>Categories</label>
                        <div class="checkbox-group">
                            <?php foreach ($categories as $cat): ?>
                                <label class="checkbox-label">
                                    <input
                                        type="checkbox"
                                        name="categories[]"
                                        value="<?php echo $cat['id']; ?>"
                                    >
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="image">Product Image</label>
                        <input
                            type="file"
                            id="image"
                            name="image"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                        >
                        <small>Max 3MB. JPG, PNG, GIF or WEBP.</small>
                    </div>

                    <button type="submit" class="btn btn-primary">Add Product</button>
                </form>
            </div>

            <!-- MY PRODUCTS LIST -->
            <div class="panel">
                <h2>My Products (<?php echo count($myProducts); ?>)</h2>

                <?php if (empty($myProducts)): ?>
                    <p class="empty-msg">You have not added any products yet.</p>
                <?php else: ?>
                    <?php foreach ($myProducts as $p): ?>
                        <div class="product-row">
                            <div class="product-row-info">
                                <strong><?php echo htmlspecialchars($p['title']); ?></strong>
                                <span>$<?php echo number_format($p['price'], 2); ?></span>
                                <span class="status-badge status-<?php echo $p['status']; ?>">
                                    <?php echo ucfirst($p['status']); ?>
                                </span>
                            </div>
                            <div class="product-row-actions">
                                <a href="/art-store/product.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline">View</a>

                                <!-- Delete form - small inline form with confirm dialog -->
                                <form method="post" action=""
                                      onsubmit="return confirm('Delete this product?');"
                                      style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>