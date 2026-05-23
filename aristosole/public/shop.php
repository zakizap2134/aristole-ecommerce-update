<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : null;

$sql = "SELECT p.*, b.brand_name, c.category_name 
        FROM products p
        INNER JOIN brands b ON p.brand_id = b.brand_id
        INNER JOIN categories c ON p.category_id = c.category_id
        WHERE p.is_active = 1";

$params = [];

if ($brand_id) {
    $sql .= " AND p.brand_id = :brand_id";
    $params[':brand_id'] = $brand_id;
}

if ($search) {
    $sql .= " AND (p.product_name LIKE :search OR b.brand_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$sql .= " ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

$brands = $pdo->query("SELECT * FROM brands ORDER BY brand_name")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>AristoSole - Premium Footwear</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .navbar { background: #2c3e50; color: white; padding: 15px 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 24px; font-weight: bold; color: white; text-decoration: none; }
        .nav-links a { color: white; text-decoration: none; margin-left: 20px; padding: 8px 15px; border-radius: 5px; }
        .btn-cart { background: #27ae60; }
        .btn-admin { background: #f39c12; }
        .btn-logout { background: #e74c3c; }
        .btn-login { background: #3498db; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .page-title { margin-bottom: 20px; color: #2c3e50; }
        .filters { background: white; padding: 15px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
        .product-card { background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.1); transition: transform 0.3s; }
        .product-card:hover { transform: translateY(-5px); }
        .product-image { width: 100%; height: 200px; object-fit: cover; background: #ecf0f1; }
        .no-image { width: 100%; height: 200px; background: #ecf0f1; display: flex; align-items: center; justify-content: center; font-size: 60px; color: #95a5a6; }
        .product-body { padding: 15px; }
        .product-name { font-size: 18px; font-weight: bold; margin-bottom: 5px; color: #2c3e50; }
        .product-meta { color: #7f8c8d; font-size: 14px; margin-bottom: 10px; }
        .product-price { font-size: 22px; color: #27ae60; font-weight: bold; margin: 10px 0; }
        .in-stock { color: #27ae60; font-size: 14px; }
        .out-of-stock { color: #e74c3c; font-size: 14px; }
        .form-inline { display: flex; gap: 10px; align-items: center; margin-top: 10px; }
        .quantity-input { width: 60px; padding: 8px; border: 1px solid #ddd; border-radius: 5px; text-align: center; }
        .btn-primary { background: #3498db; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-primary:hover { background: #2980b9; }
        .btn-secondary { background: #95a5a6; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        select, input[type="text"] { padding: 8px; border: 1px solid #ddd; border-radius: 5px; }
        .filter-form { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        .badge { background: #e74c3c; color: white; border-radius: 50%; padding: 2px 8px; font-size: 12px; margin-left: 5px; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="shop.php" class="navbar-brand">👟 AristoSole</a>
            <div class="nav-links">
                <a href="cart.php" class="btn-cart">🛒 Cart <?php $count = getCartCount(); if($count > 0) echo "<span class='badge'>$count</span>"; ?></a>
                <?php if (isLoggedIn()): ?>
                    <?php if (isAdmin()): ?>
                        <a href="../admin/dashboard.php" class="btn-admin">👑 Admin</a>
                    <?php endif; ?>
                    <span>Hello, <?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="../auth/logout.php" class="btn-logout">Logout</a>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn-login">Login</a>
                    <a href="../auth/register.php" class="btn-login">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="container">
        <h2 class="page-title">Our Collection</h2>
        
        <div class="filters">
            <form method="GET" class="filter-form">
                <select name="brand">
                    <option value="">All Brands</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?php echo $brand['brand_id']; ?>" <?php echo ($brand_id == $brand['brand_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($brand['brand_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" placeholder="Search shoes..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                <button type="submit" class="btn-primary">Filter</button>
                <?php if ($brand_id || $search): ?>
                    <a href="shop.php" class="btn-secondary">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="products-grid">
            <?php if (empty($products)): ?>
                <div style="text-align: center; padding: 50px;">No products found.</div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <?php if(!empty($product['image'])): ?>
                        <img src="images/<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['product_name']); ?>" class="product-image">
                    <?php else: ?>
                        <div class="no-image">👟</div>
                    <?php endif; ?>
                    <div class="product-body">
                        <div class="product-name"><?php echo htmlspecialchars($product['product_name']); ?></div>
                        <div class="product-meta"><?php echo htmlspecialchars($product['brand_name']); ?> | <?php echo htmlspecialchars($product['category_name']); ?></div>
                        <div class="product-price"><?php echo formatPrice($product['unit_price']); ?></div>
                        <div class="<?php echo $product['stock_qty'] > 0 ? 'in-stock' : 'out-of-stock'; ?>">
                            <?php echo $product['stock_qty'] > 0 ? "✅ In Stock: {$product['stock_qty']} units" : "❌ Out of Stock"; ?>
                        </div>
                        
                        <?php if ($product['stock_qty'] > 0 && isLoggedIn()): ?>
                            <form method="POST" action="add_to_cart.php" class="form-inline">
                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                <input type="hidden" name="price" value="<?php echo $product['unit_price']; ?>">
                                <input type="number" name="quantity" value="1" min="1" max="<?php echo $product['stock_qty']; ?>" class="quantity-input">
                                <button type="submit" name="add_to_cart" class="btn-primary">Add to Cart</button>
                            </form>
                        <?php elseif (!isLoggedIn()): ?>
                            <a href="../auth/login.php" class="btn-secondary">Login to Buy</a>
                        <?php else: ?>
                            <button class="btn-secondary" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>