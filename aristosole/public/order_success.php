<?php
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmed - AristoSole</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .container { max-width: 600px; margin: 80px auto; padding: 20px; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; padding: 40px; }
        .success-icon { font-size: 80px; color: #27ae60; margin-bottom: 20px; }
        h2 { color: #2c3e50; margin-bottom: 15px; }
        .btn { display: inline-block; padding: 12px 25px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin: 10px 5px; }
        .btn:hover { background: #2980b9; }
        .btn-admin { background: #f39c12; }
        .btn-report { background: #17a2b8; }
        ul { text-align: left; display: inline-block; margin: 20px 0; }
        li { margin: 8px 0; color: #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="success-icon">✓</div>
            <h2>Order Successful!</h2>
            <p>Thank you for shopping at AristoSole.</p>
            <p>Your order has been placed successfully.</p>
            
            <ul>
                <li>✓ The TRIGGER automatically reduced inventory stock</li>
                <li>✓ Transaction was committed to the database</li>
                <li>✓ Your order is confirmed</li>
            </ul>
            
            <div>
                <a href="shop.php" class="btn">Continue Shopping</a>
                <?php if (isAdmin()): ?>
                    <a href="../admin/dashboard.php" class="btn btn-admin">Admin Panel</a>
                <?php endif; ?>
                <a href="../manager/dashboard.php" class="btn btn-report">View Reports</a>
            </div>
        </div>
    </div>
</body>
</html>