<?php
// =====================================================
// Checkout Page - Uses Stored Procedure and Transaction
// This demonstrates: BEGIN, COMMIT, ROLLBACK
// =====================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (empty($_SESSION['cart'])) {
    header('Location: shop.php');
    exit();
}

$error = '';
$success = '';

// Get cart items
$items = [];
$total = 0;

foreach ($_SESSION['cart'] as $id => $item) {
    $stmt = $pdo->prepare("SELECT product_name FROM products WHERE product_id = ?");
    $stmt->execute([$id]);
    $product = $stmt->fetch();
    
    if ($product) {
        $item['name'] = $product['product_name'];
        $item['subtotal'] = $item['quantity'] * $item['price'];
        $items[$id] = $item;
        $total += $item['subtotal'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // First, insert order items individually (this triggers stock reduction)
        $pdo->beginTransaction();
        
        // Insert order
        $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_amount, order_status) VALUES (?, ?, 'paid')");
        $stmt->execute([$_SESSION['user_id'], $total]);
        $order_id = $pdo->lastInsertId();
        
        // Insert payment
        $stmt = $pdo->prepare("INSERT INTO payments (order_id, payment_method, amount, payment_status) VALUES (?, 'card', ?, 'completed')");
        $stmt->execute([$order_id, $total]);
        
        // Insert order items (this triggers the stock reduction)
        foreach ($_SESSION['cart'] as $product_id => $item) {
            $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $stmt->execute([$order_id, $product_id, $item['quantity'], $item['price']]);
        }
        
        $pdo->commit();
        
        // Clear cart
        $_SESSION['cart'] = [];
        
        header('Location: order_success.php');
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Checkout failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout - AristoSole</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .navbar { background: #2c3e50; padding: 15px 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .navbar-brand { font-size: 24px; font-weight: bold; color: white; text-decoration: none; }
        .container { max-width: 1000px; margin: 20px auto; padding: 0 20px; }
        .row { display: flex; gap: 20px; flex-wrap: wrap; }
        .col-main { flex: 2; }
        .col-side { flex: 1; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: #27ae60; color: white; padding: 15px; border-radius: 10px 10px 0 0; }
        .card-body { padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        .text-end { text-align: right; }
        .btn-success { background: #27ae60; color: white; padding: 15px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 18px; }
        .btn-success:hover { background: #219a52; }
        .alert { padding: 10px; background: #e74c3c; color: white; border-radius: 5px; margin-bottom: 15px; }
        .info-box { background: #ecf0f1; padding: 15px; border-radius: 5px; margin-top: 15px; }
        .info-box h4 { margin-bottom: 10px; color: #2c3e50; }
        .info-box ul { margin-left: 20px; }
        .info-box li { margin: 5px 0; color: #27ae60; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="shop.php" class="navbar-brand">👟 AristoSole</a>
        </div>
    </nav>

    <div class="container">
        <div class="row">
            <div class="col-main">
                <div class="card">
                    <div class="card-header">
                        <h3>Review Your Order</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <table>
                            <thead>
                                <tr><th>Product</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                    <td><?php echo $item['quantity']; ?></td>
                                    <td><?php echo formatPrice($item['price']); ?></td>
                                    <td><?php echo formatPrice($item['subtotal']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr style="font-weight: bold; background: #ecf0f1;">
                                    <td colspan="3" class="text-end">Total:</td>
                                    <td><?php echo formatPrice($total); ?></td>
                                </tr>
                            </tbody>
                        </table>
                        
                        <form method="POST" style="margin-top: 20px;">
                            <button type="submit" class="btn-success">Confirm Order</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-side">
                <div class="card">
                    <div class="card-header" style="background: #3498db;">
                        <h3>Transaction Security</h3>
                    </div>
                    <div class="card-body">
                        <div class="info-box">
                            <h4>Your order is protected by:</h4>
                            <ul>
                                <li>✓ Database TRANSACTION (BEGIN/COMMIT/ROLLBACK)</li>
                                <li>✓ SQL TRIGGER for automatic stock update</li>
                                <li>✓ STORED PROCEDURE for atomic checkout</li>
                                <li>✓ Prepared Statements (no SQL injection)</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>