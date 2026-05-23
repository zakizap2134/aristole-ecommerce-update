<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Remove item
if (isset($_GET['remove'])) {
    unset($_SESSION['cart'][$_GET['remove']]);
    header('Location: cart.php');
    exit();
}

// Update quantities
if (isset($_POST['update'])) {
    foreach ($_POST['quantity'] as $id => $qty) {
        if ($qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            $_SESSION['cart'][$id]['quantity'] = $qty;
        }
    }
    header('Location: cart.php');
    exit();
}

// Clear cart
if (isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit();
}

// Get product details
$cart_items = [];
$total = 0;

foreach ($_SESSION['cart'] as $id => $item) {
    $stmt = $pdo->prepare("SELECT product_name, stock_qty FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $item['name'] = $product['product_name'];
        $item['subtotal'] = $item['quantity'] * $item['price'];
        $cart_items[$id] = $item;
        $total += $item['subtotal'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Shopping Cart - AristoSole</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .navbar { background: #2c3e50; padding: 15px 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 24px; font-weight: bold; color: white; text-decoration: none; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); padding: 20px; }
        .card-header { border-bottom: 2px solid #27ae60; padding-bottom: 10px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ecf0f1; }
        .text-right { text-align: right; }
        .btn-warning { background: #f39c12; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn-success { background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn-danger { background: #e74c3c; color: white; padding: 5px 10px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; font-size: 12px; }
        .btn-secondary { background: #95a5a6; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .quantity-input { width: 60px; padding: 5px; text-align: center; }
        .alert { padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
        .flex-between { display: flex; justify-content: space-between; margin-top: 15px; }
        .grand-total { font-size: 18px; font-weight: bold; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="shop.php" class="navbar-brand">👟 AristoSole</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Shopping Cart</h2>
            </div>
            
            <?php if (empty($cart_items)): ?>
                <div class="alert">
                    Your cart is empty. <a href="shop.php">Continue Shopping</a>
                </div>
            <?php else: ?>
                <form method="POST">
                    <table>
                        <thead>
                            <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart_items as $id => $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo formatPrice($item['price']); ?></td>
                                <td><input type="number" name="quantity[<?php echo $id; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="quantity-input"></td>
                                <td><?php echo formatPrice($item['subtotal']); ?></td>
                                <td><a href="cart.php?remove=<?php echo $id; ?>" class="btn-danger">Remove</a></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="grand-total">
                                <td colspan="3" class="text-right">Total:</td>
                                <td colspan="2"><?php echo formatPrice($total); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                    <div class="flex-between">
                        <div>
                            <button type="submit" name="update" class="btn-warning">Update Cart</button>
                            <button type="submit" name="clear_cart" class="btn-danger" onclick="return confirm('Clear entire cart?')">Clear Cart</button>
                        </div>
                        <div>
                            <a href="shop.php" class="btn-secondary">Continue Shopping</a>
                            <a href="checkout.php" class="btn-success">Proceed to Checkout</a>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>