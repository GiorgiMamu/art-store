<?php
// classes.php
// Each class handles one part of the application

require_once __DIR__ . '/db.php';

// CLASS: UserManager
// Handles everything related to users
class UserManager {
    // $pdo is the database connection
    // private means only this class can use it directly
    private $pdo;

    // __construct runs when you do: new UserManager()
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all users from the database
    public function getAll() {
        $stmt = $this->pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
        $stmt->execute();
        return $stmt->fetchAll(); // returns as an array
    }

    // Get one user by their ID
    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Update a user's role
    public function updateRole($userId, $newRole) {
        $allowed = ['admin', 'moderator', 'user'];
        if (!in_array($newRole, $allowed)) return false;

        $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$newRole, $userId]);
    }

    // Delete a user by ID
    public function delete($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$userId]);
    }
}

// CLASS: ProductManager
// Handles everything related to products
class ProductManager {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    // Get all products with seller name, optional status filter
    public function getAll($status = 'active') {
        $stmt = $this->pdo->prepare("
            SELECT products.*, users.name AS seller_name
            FROM products
            JOIN users ON products.user_id = users.id
            WHERE products.status = ?
            ORDER BY products.created_at DESC
        ");
        $stmt->execute([$status]);
        return $stmt->fetchAll();
    }

    // Get all products regardless of status (for admin)
    public function getAllForAdmin() {
        $stmt = $this->pdo->prepare("
            SELECT products.*, users.name AS seller_name
            FROM products
            JOIN users ON products.user_id = users.id
            ORDER BY products.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get one product by ID
    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT products.*, users.name AS seller_name
            FROM products
            JOIN users ON products.user_id = users.id
            WHERE products.id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Get products belonging to one user
    public function getByUser($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    // Create a new product and link its categories
    public function create($title, $description, $price, $imageName, $userId, $categoryIds) {
        $stmt = $this->pdo->prepare("
            INSERT INTO products (title, description, price, image, user_id)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $price, $imageName, $userId]);
        $productId = $this->pdo->lastInsertId();

        // Link to categories (N:N relationship)
        foreach ($categoryIds as $catId) {
            $stmt2 = $this->pdo->prepare("
                INSERT INTO product_categories (product_id, category_id) VALUES (?, ?)
            ");
            $stmt2->execute([$productId, intval($catId)]);
        }

        return $productId;
    }

    // Update a product's status
    public function updateStatus($productId, $status) {
        $stmt = $this->pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $productId]);
    }

    // Delete a product and its image file
    public function delete($productId) {
        $product = $this->getById($productId);
        if ($product && $product['image'] && file_exists('uploads/' . $product['image'])) {
            unlink('uploads/' . $product['image']);
        }
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        return $stmt->execute([$productId]);
    }

    // Get all categories
    public function getCategories() {
        $stmt = $this->pdo->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // Get categories for one product
    public function getCategoriesForProduct($productId) {
        $stmt = $this->pdo->prepare("
            SELECT categories.name
            FROM categories
            JOIN product_categories ON categories.id = product_categories.category_id
            WHERE product_categories.product_id = ?
        ");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    // Search products by title
    public function search($searchTerm) {
        $stmt = $this->pdo->prepare("
            SELECT products.*, users.name AS seller_name
            FROM products
            JOIN users ON products.user_id = users.id
            WHERE products.status = 'active'
            AND products.title LIKE ?
            ORDER BY products.created_at DESC
        ");
        $stmt->execute(['%' . $searchTerm . '%']);
        return $stmt->fetchAll();
    }
}

// CLASS: FileManager
// Handles image uploads and user notes text file
class FileManager {

    // Upload a product image
    // Returns the filename on success, error string on failure
    public function uploadImage($fileInput) {
        if ($fileInput['error'] !== UPLOAD_ERR_OK) {
            return 'Upload error code: ' . $fileInput['error'];
        }

        $allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $finfo    = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $fileInput['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mimeType, $allowed)) {
            return 'Only JPG, PNG, GIF or WEBP images are allowed.';
        }

        if ($fileInput['size'] > 3 * 1024 * 1024) {
            return 'Image must be under 3MB.';
        }

        $ext       = pathinfo($fileInput['name'], PATHINFO_EXTENSION);
        $imageName = uniqid('img_') . '.' . strtolower($ext);
        $dest      = 'uploads/' . $imageName;

        if (!move_uploaded_file($fileInput['tmp_name'], $dest)) {
            return 'Could not save image. Check that the uploads folder exists.';
        }

        return $imageName; // success
    }

    // Write a note about a user to the text file
    public function writeNote($adminName, $targetUserName, $noteText) {
        $timestamp = date('Y-m-d H:i:s');
        $line      = "[$timestamp] Admin '$adminName' about '$targetUserName': $noteText\n";
        file_put_contents('logs/user_notes.txt', $line, FILE_APPEND | LOCK_EX);
    }

    // Read all notes from the text file
    public function readNotes() {
        if (!file_exists('logs/user_notes.txt')) return [];
        $lines = file('logs/user_notes.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return $lines ? array_reverse($lines) : [];
    }
}
?>