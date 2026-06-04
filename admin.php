<?php
// admin.php
// Only admins can access this page
// Admins can manage all users and all products

session_start();

// Block everyone who is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /art-store/index.php');
    exit;
}

require_once 'includes/db.php';

$error   = '';
$success = '';

// -----------------------------------------------
// Handle admin actions
// -----------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Change a user's role
    if ($action === 'update_role') {
        $userId  = intval($_POST['user_id']);
        $newRole = $_POST['new_role'] ?? '';
        $allowed = ['admin', 'moderator', 'user'];

        if (in_array($newRole, $allowed)) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$newRole, $userId]);
            $success = 'Role updated.';
        }
    }

    // Delete a user
    if ($action === 'delete_user') {
        $userId = intval($_POST['user_id']);

        // Do not let admin delete their own account
        if ($userId === intval($_SESSION['user_id'])) {
            $error = 'You cannot delete your own account.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $success = 'User deleted.';
        }
    }

    // Change a product's status
    if ($action === 'update_product_status') {
        $productId = intval($_POST['product_id']);
        $newStatus = $_POST['new_status'] ?? 'active';

        if (in_array($newStatus, ['active', 'inactive'])) {
            $stmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $productId]);
            $success = 'Product status updated.';
        }
    }

    // Delete a product
    if ($action === 'delete_product') {
        $productId = intval($_POST['product_id']);

        // Get the product first so we can delete its image file
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            if ($product['image'] && file_exists('uploads/' . $product['image'])) {
                unlink('uploads/' . $product['image']);
            }
            $stmt2 = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt2->execute([$productId]);
            $success = 'Product deleted.';
        }
    }

    // Write a note about a user to a text file
    if ($action === 'write_note') {
        $targetId = intval($_POST['target_user_id']);
        $noteText = trim($_POST['note_text'] ?? '');

        // Get the target user's name
        $stmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
        $stmt->execute([$targetId]);
        $targetUser = $stmt->fetch();

        if ($targetUser && !empty($noteText)) {
            $timestamp = date('Y-m-d H:i:s');
            $line = "[$timestamp] Admin '{$_SESSION['user_name']}' about '{$targetUser['name']}': $noteText\n";

            // FILE_APPEND adds to the end of the file instead of overwriting it
            file_put_contents('logs/user_notes.txt', $line, FILE_APPEND | LOCK_EX);
            $success = 'Note saved.';
        }
    }
}

// Load all users
$stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
$stmt->execute();
$allUsers = $stmt->fetchAll();

// Load all products with seller name
$stmt = $pdo->prepare("
    SELECT products.*, users.name AS seller_name
    FROM products
    JOIN users ON products.user_id = users.id
    ORDER BY products.created_at DESC
");
$stmt->execute();
$allProducts = $stmt->fetchAll();

// Read existing notes from the file
$notes = [];
if (file_exists('logs/user_notes.txt')) {
    // file() reads a file into an array of lines
    $notes = array_reverse(file('logs/user_notes.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

$pageTitle = 'Admin Panel - ArtStore';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1>Admin Panel</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- USERS TABLE -->
        <div class="panel">
            <h2>Users (<?php echo count($allUsers); ?>)</h2>
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allUsers as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td>
                                    <!-- Role change dropdown - submits automatically when changed -->
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="update_role">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <select name="new_role" onchange="this.form.submit()">
                                            <option value="user"      <?php echo $u['role']==='user'      ? 'selected' : ''; ?>>User</option>
                                            <option value="moderator" <?php echo $u['role']==='moderator' ? 'selected' : ''; ?>>Moderator</option>
                                            <option value="admin"     <?php echo $u['role']==='admin'     ? 'selected' : ''; ?>>Admin</option>
                                        </select>
                                    </form>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($u['created_at'])); ?></td>
                                <td class="table-actions">
                                    <!-- Write note button - shows a hidden note form for this user -->
                                    <button
                                        class="btn btn-sm btn-outline"
                                        onclick="showNoteForm(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>')"
                                    >Note</button>

                                    <!-- Delete user -->
                                    <form method="post" action=""
                                          onsubmit="return confirm('Delete <?php echo htmlspecialchars($u['name'], ENT_QUOTES); ?>?');"
                                          style="display:inline;">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- NOTE FORM (hidden by default, shown by JS) -->
        <div class="panel" id="notePanel" style="display:none;">
            <h2>Write Note About: <span id="noteUserName"></span></h2>
            <form method="post" action="">
                <input type="hidden" name="action" value="write_note">
                <input type="hidden" name="target_user_id" id="noteUserId">
                <div class="form-group">
                    <label for="noteText">Note</label>
                    <textarea
                        id="noteText"
                        name="note_text"
                        rows="3"
                        placeholder="Write your note here..."
                        required
                        maxlength="500"
                    ></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Save Note</button>
                <button type="button" class="btn btn-outline" onclick="hideNoteForm()">Cancel</button>
            </form>
        </div>

        <!-- NOTES LOG -->
        <?php if (!empty($notes)): ?>
            <div class="panel">
                <h2>User Notes Log</h2>
                <?php foreach ($notes as $note): ?>
                    <div class="note-line"><?php echo htmlspecialchars($note); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- PRODUCTS TABLE -->
        <div class="panel">
            <h2>All Products (<?php echo count($allProducts); ?>)</h2>
            <div class="table-scroll">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Price</th>
                            <th>Seller</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allProducts as $p): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($p['title']); ?></td>
                                <td>$<?php echo number_format($p['price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($p['seller_name']); ?></td>
                                <td>
                                    <form method="post" action="">
                                        <input type="hidden" name="action" value="update_product_status">
                                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                        <select name="new_status" onchange="this.form.submit()">
                                            <option value="active"   <?php echo $p['status']==='active'   ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $p['status']==='inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                    </form>
                                </td>
                                <td class="table-actions">
                                    <a href="/art-store/product.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-outline">View</a>
                                    <form method="post" action=""
                                          onsubmit="return confirm('Delete this product?');"
                                          style="display:inline;">
                                        <input type="hidden" name="action" value="delete_product">
                                        <input type="hidden" name="product_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</section>

<?php require_once 'includes/footer.php'; ?>