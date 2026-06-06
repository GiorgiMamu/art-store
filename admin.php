<?php
require_once 'includes/session.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /art-store/index.php');
    exit;
}

require_once 'includes/classes.php';

$userManager    = new UserManager($pdo);
$productManager = new ProductManager($pdo);
$fileManager    = new FileManager();
$error          = '';
$success        = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_role') {
        $result  = $userManager->updateRole(intval($_POST['user_id']), $_POST['new_role'] ?? '');
        $success = $result ? 'Role updated.' : 'Failed to update role.';
    }

    if ($action === 'delete_user') {
        $userId = intval($_POST['user_id']);
        if ($userId === intval($_SESSION['user_id'])) {
            $error = 'You cannot delete your own account.';
        } else {
            $userManager->delete($userId);
            $success = 'User deleted.';
        }
    }

    if ($action === 'update_product_status') {
        $productManager->updateStatus(intval($_POST['product_id']), $_POST['new_status'] ?? 'active');
        $success = 'Product status updated.';
    }

    if ($action === 'delete_product') {
        $productManager->delete(intval($_POST['product_id']));
        $success = 'Product deleted.';
    }

    if ($action === 'write_note') {
        $targetUser = $userManager->getById(intval($_POST['target_user_id']));
        $noteText   = trim($_POST['note_text'] ?? '');
        if ($targetUser && !empty($noteText)) {
            $fileManager->writeNote($_SESSION['user_name'], $targetUser['name'], $noteText);
            $success = 'Note saved.';
        } else {
            $error = 'Note cannot be empty.';
        }
    }
}

$allUsers    = $userManager->getAll();
$allProducts = $productManager->getAllForAdmin();
$notes       = $fileManager->readNotes();
$pageTitle   = 'Admin Panel - ArtStore';

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