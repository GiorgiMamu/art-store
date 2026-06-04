<?php
// dashboard.php
// Logged-in users can add new products and manage their own listings

session_start();

// If not logged in, send to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: /art-store/auth.php');
    exit;
}

// Admins should use the admin panel
if ($_SESSION['user_role'] === 'admin') {
    header('Location: /art-store/admin.php');
    exit;
}

require_once 'includes/db.php';

$error   = '';
$success = '';

// -----------------------------------------------
// Handle form submissions
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ADD PRODUCT
    if ($action === 'add') {
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price       = floatval($_POST['price'] ?? 0);
        $cats        = $_POST['categories'] ?? []; // array of selected category IDs
        $imageName   = null;

        if (empty($title) || $price <= 0) {
            $error = 'Title and a valid price are required.';
        } else {

            // Handle image upload if a file was chosen
            if (!empty($_FILES['image']['name'])) {

                $file      = $_FILES['image'];
                $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

                // finfo checks the actual file type, not just the extension
                $finfo    = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowed)) {
                    $error = 'Only JPG, PNG, GIF or WEBP images are allowed.';
                } elseif ($file['size'] > 3 * 1024 * 1024) {
                    // 3MB limit
                    $error = 'Image must be under 3MB.';
                } else {
                    // Create a unique filename so files never overwrite each other
                    $ext       = pathinfo($file['name'], PATHINFO_EXTENSION);
                    $imageName = uniqid('img_') . '.' . strtolower($ext);
                    $dest      = 'uploads/' . $imageName;

                    // move_uploaded_file() moves the file from temp location to our folder
                    if (!move_uploaded_file($file['tmp_name'], $dest)) {
                        $error     = 'Could not save image. Check that the uploads folder exists.';
                        $imageName = null;
                    }
                }
            }

            if (!$error) {
                // Insert product into database
                $stmt = $pdo->prepare("
                    INSERT INTO products (title, description, price, image, user_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$title, $description, $price, $imageName, $_SESSION['user_id']]);

                // Get the ID of the product we just inserted
                $productId = $pdo->lastInsertId();

                // Link the product to its selected categories
                // This fills the product_categories table (the N:N junction table)
                foreach ($cats as $catId) {
                    $stmt2 = $pdo->prepare("
                        INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)
                    ");
                    $stmt2->execute([$productId, intval($catId)]);
                }

                $success = 'Product added successfully!';
            }
        }
    }

    // DELETE PRODUCT
    if ($action === 'delete') {
        $productId = intval($_POST['product_id']);

        // Make sure this product actually belongs to the logged-in user
        // We never trust that the user is who they say — we check the database
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
        $stmt->execute([$productId, $_SESSION['user_id']]);
        $product = $stmt->fetch();

        if ($product) {
            // Delete the image file if one exists
            if ($product['image'] && file_exists('uploads/' . $product['image'])) {
                unlink('uploads/' . $product['image']); // unlink() deletes a file
            }
            // Delete from database (product_categories rows are deleted automatically
            // because we set ON DELETE CASCADE in the database)
            $stmt2 = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt2->execute([$productId]);
            $success = 'Product deleted.';
        } else {
            $error = 'Product not found or you do not have permission.';
        }
    }
}

// Get all products belonging to this user
$stmt = $pdo->prepare("
    SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$myProducts = $stmt->fetchAll();

// Get all categories for the checkboxes
$stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name");
$stmt->execute();
$categories = $stmt->fetchAll();

$pageTitle = 'Dashboard - ArtStore';
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