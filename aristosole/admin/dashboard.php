<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireAdmin();

// =====================================================
// IMAGE UPLOAD FUNCTION
// =====================================================
function uploadImage($file, $old_image = null) {
    // If no file uploaded, keep old image
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return $old_image;
    }
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return $old_image;
    }
    
    // Allowed file types
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        return $old_image;
    }
    
    // Create unique filename
    $new_filename = time() . '_' . uniqid() . '.' . $ext;
    $upload_path = __DIR__ . '/../public/images/' . $new_filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Delete old image if exists
        if ($old_image && file_exists(__DIR__ . '/../public/images/' . $old_image)) {
            unlink(__DIR__ . '/../public/images/' . $old_image);
        }
        return $new_filename;
    }
    
    return $old_image;
}

// =====================================================
// CREATE PRODUCT (with image)
// =====================================================
if (isset($_POST['add_product'])) {
    $image = uploadImage($_FILES['image']);
    
    $sql = "INSERT INTO products (brand_id, category_id, sku, product_name, description, unit_price, stock_qty, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['brand_id'], $_POST['category_id'], $_POST['sku'],
        $_POST['product_name'], $_POST['description'], $_POST['unit_price'],
        $_POST['stock_qty'], $image
    ]);
    $_SESSION['message'] = "Product added successfully!";
    header('Location: dashboard.php');
    exit();
}

// =====================================================
// UPDATE PRODUCT (with image)
// =====================================================
if (isset($_POST['edit_product'])) {
    $image = uploadImage($_FILES['image'], $_POST['old_image'] ?? null);
    
    $sql = "UPDATE products SET brand_id=?, category_id=?, sku=?, product_name=?, description=?, unit_price=?, stock_qty=?, is_active=?, image=?
            WHERE product_id=?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $_POST['brand_id'], $_POST['category_id'], $_POST['sku'],
        $_POST['product_name'], $_POST['description'], $_POST['unit_price'],
        $_POST['stock_qty'], isset($_POST['is_active']) ? 1 : 0,
        $image, $_POST['product_id']
    ]);
    $_SESSION['message'] = "Product updated successfully!";
    header('Location: dashboard.php');
    exit();
}

// =====================================================
// DELETE PRODUCT (also deletes image file)
// =====================================================
if (isset($_GET['delete'])) {
    // Get image name to delete file
    $stmt = $pdo->prepare("SELECT image FROM products WHERE product_id = ?");
    $stmt->execute([$_GET['delete']]);
    $product = $stmt->fetch();
    if ($product && $product['image'] && file_exists(__DIR__ . '/../public/images/' . $product['image'])) {
        unlink(__DIR__ . '/../public/images/' . $product['image']);
    }
    
    $sql = "DELETE FROM products WHERE product_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$_GET['delete']]);
    $_SESSION['message'] = "Product deleted!";
    header('Location: dashboard.php');
    exit();
}

// =====================================================
// GET ALL PRODUCTS
// =====================================================
$products = $pdo->query("SELECT p.*, b.brand_name, c.category_name 
                         FROM products p
                         JOIN brands b ON p.brand_id = b.brand_id
                         JOIN categories c ON p.category_id = c.category_id
                         ORDER BY p.product_id DESC")->fetchAll();

$brands = $pdo->query("SELECT * FROM brands ORDER BY brand_name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY category_name")->fetchAll();

$edit_product = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_product = $stmt->fetch();
}

$message = $_SESSION['message'] ?? '';
unset($_SESSION['message']);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - AristoSole</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .navbar { background: #2c3e50; color: white; padding: 15px 0; }
        .nav-container { max-width: 1400px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 24px; font-weight: bold; color: white; text-decoration: none; }
        .nav-links a { color: white; text-decoration: none; margin-left: 15px; padding: 8px 15px; border-radius: 5px; }
        .btn-outline { border: 1px solid white; background: transparent; }
        .btn-danger { background: #e74c3c; }
        .container { max-width: 1400px; margin: 20px auto; padding: 0 20px; }
        .row { display: flex; gap: 20px; flex-wrap: wrap; }
        .col-form { flex: 1; min-width: 350px; }
        .col-list { flex: 2; min-width: 500px; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: #2c3e50; color: white; padding: 12px 15px; border-radius: 10px 10px 0 0; font-weight: bold; }
        .card-body { padding: 15px; }
        .form-group { margin-bottom: 12px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        input[type="file"] { padding: 5px; }
        button { background: #27ae60; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #219a52; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; font-size: 14px; }
        th { background: #ecf0f1; }
        .btn-sm { padding: 3px 8px; font-size: 12px; margin: 0 2px; text-decoration: none; display: inline-block; border-radius: 3px; }
        .btn-primary { background: #3498db; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .badge-active { background: #27ae60; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; }
        .badge-inactive { background: #95a5a6; color: white; padding: 3px 8px; border-radius: 3px; font-size: 11px; }
        .w-100 { width: 100%; }
        .alert-success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .product-thumb { width: 40px; height: 40px; object-fit: cover; border-radius: 5px; background: #ecf0f1; }
        .image-preview { max-width: 100px; margin-top: 10px; border-radius: 5px; }
        .text-muted { color: #7f8c8d; font-size: 12px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="navbar-brand">👑 Admin Panel - AristoSole</a>
            <div class="nav-links">
                <a href="../public/shop.php" class="btn-outline">🛍️ View Shop</a>
                <a href="../manager/dashboard.php" class="btn-outline">📊 Reports</a>
                <a href="../auth/logout.php" class="btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <?php if ($message): ?>
            <div class="alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-form">
                <div class="card">
                    <div class="card-header">
                        <?php echo $edit_product ? '✏️ Edit Product' : '➕ Add New Product'; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <?php if ($edit_product): ?>
                                <input type="hidden" name="product_id" value="<?php echo $edit_product['product_id']; ?>">
                                <input type="hidden" name="old_image" value="<?php echo $edit_product['image'] ?? ''; ?>">
                            <?php endif; ?>
                            
                            <div class="form-group">
                                <label>Brand *</label>
                                <select name="brand_id" required>
                                    <option value="">Select Brand</option>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?php echo $b['brand_id']; ?>" <?php echo ($edit_product && $edit_product['brand_id'] == $b['brand_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($b['brand_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Category *</label>
                                <select name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $c): ?>
                                        <option value="<?php echo $c['category_id']; ?>" <?php echo ($edit_product && $edit_product['category_id'] == $c['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($c['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>SKU (Unique) *</label>
                                <input type="text" name="sku" value="<?php echo $edit_product['sku'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Name *</label>
                                <input type="text" name="product_name" value="<?php echo $edit_product['product_name'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="3"><?php echo $edit_product['description'] ?? ''; ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Price (₱) *</label>
                                <input type="number" step="0.01" name="unit_price" value="<?php echo $edit_product['unit_price'] ?? ''; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Stock Quantity *</label>
                                <input type="number" name="stock_qty" value="<?php echo $edit_product['stock_qty'] ?? '0'; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Image</label>
                                <input type="file" name="image" accept="image/jpeg,image/png,image/jpg,image/gif">
                                <small class="text-muted">Upload JPG, PNG, or GIF (max 2MB)</small>
                                <?php if ($edit_product && !empty($edit_product['image'])): ?>
                                    <div>
                                        <img src="../public/images/<?php echo $edit_product['image']; ?>" class="image-preview">
                                        <br><small class="text-muted">Current image</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <?php if ($edit_product): ?>
                                <div class="form-group">
                                    <label>
                                        <input type="checkbox" name="is_active" <?php echo ($edit_product['is_active']) ? 'checked' : ''; ?>>
                                        Active (visible to customers)
                                    </label>
                                </div>
                                <button type="submit" name="edit_product">Update Product</button>
                                <a href="dashboard.php" style="margin-left: 10px;">Cancel</a>
                            <?php else: ?>
                                <button type="submit" name="add_product" class="w-100">Add Product</button>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-list">
                <div class="card">
                    <div class="card-header">
                        📋 All Products
                    </div>
                    <div class="card-body">
                        <?php if (empty($products)): ?>
                            <p style="text-align: center;">No products yet. Add your first product!</p>
                        <?php else: ?>
                            <div style="overflow-x: auto;">
                                <table>
                                    <thead>
                                        <tr>
                                            <th>Image</th>
                                            <th>ID</th>
                                            <th>SKU</th>
                                            <th>Name</th>
                                            <th>Price</th>
                                            <th>Stock</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($products as $p): ?>
                                        <tr>
                                            <td>
                                                <?php if (!empty($p['image'])): ?>
                                                    <img src="../public/images/<?php echo $p['image']; ?>" class="product-thumb">
                                                <?php else: ?>
                                                    <div style="width: 40px; height: 40px; background: #ecf0f1; border-radius: 5px; display: flex; align-items: center; justify-content: center;">👟</div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo $p['product_id']; ?></td>
                                            <td><?php echo htmlspecialchars($p['sku']); ?></td>
                                            <td><?php echo htmlspecialchars($p['product_name']); ?></td>
                                            <td><?php echo formatPrice($p['unit_price']); ?></td>
                                            <td><?php echo $p['stock_qty']; ?></td>
                                            <td>
                                                <span class="<?php echo $p['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                                    <?php echo $p['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="dashboard.php?edit=<?php echo $p['product_id']; ?>" class="btn-sm btn-primary">Edit</a>
                                                <a href="dashboard.php?delete=<?php echo $p['product_id']; ?>" class="btn-sm btn-danger" onclick="return confirm('Delete this product?')">Delete</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>