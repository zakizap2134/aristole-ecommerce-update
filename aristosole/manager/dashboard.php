<?php
// =====================================================
// Manager Dashboard - Uses SQL VIEW
// View joins 3+ tables: products, brands, order_items
// Uses SUM(), COUNT(), GROUP BY in the database
// =====================================================

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();

if (!isManager()) {
    header('Location: ../public/shop.php');
    exit();
}

// Query the SQL VIEW
$sales = $pdo->query("SELECT * FROM vw_sales_report")->fetchAll();

// Get totals using database aggregation
$total = $pdo->query("SELECT SUM(total_revenue) as grand_total, SUM(total_units_sold) as total_units FROM vw_sales_report")->fetch();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manager Dashboard - AristoSole</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f0f2f5; }
        .navbar { background: #2c3e50; padding: 15px 0; }
        .nav-container { max-width: 1200px; margin: 0 auto; padding: 0 20px; display: flex; justify-content: space-between; align-items: center; }
        .navbar-brand { font-size: 24px; font-weight: bold; color: white; text-decoration: none; }
        .nav-links a { color: white; text-decoration: none; margin-left: 15px; padding: 8px 15px; border-radius: 5px; }
        .btn-warning { background: #f39c12; }
        .btn-outline { border: 1px solid white; background: transparent; }
        .btn-danger { background: #e74c3c; }
        .container { max-width: 1200px; margin: 20px auto; padding: 0 20px; }
        .stats { display: flex; gap: 20px; margin-bottom: 20px; }
        .stat-card { background: white; border-radius: 10px; padding: 20px; flex: 1; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .stat-card h3 { color: #2c3e50; margin-bottom: 10px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #27ae60; }
        .card { background: white; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background: #3498db; color: white; padding: 15px; border-radius: 10px 10px 0 0; font-weight: bold; font-size: 18px; }
        .card-body { padding: 20px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #ecf0f1; }
        .text-end { text-align: right; }
        .grand-total { font-weight: bold; background: #f8f9fa; }
        .alert { padding: 15px; background: #ecf0f1; border-radius: 5px; text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="navbar-brand">📊 Manager Dashboard</a>
            <div class="nav-links">
                <?php if (isAdmin()): ?>
                    <a href="../admin/dashboard.php" class="btn-warning">Admin Panel</a>
                <?php endif; ?>
                <a href="../public/shop.php" class="btn-outline">Shop</a>
                <a href="../auth/logout.php" class="btn-danger">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container">
        <!-- Statistics Cards -->
        <div class="stats">
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <div class="number"><?php echo formatPrice($total['grand_total'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Units Sold</h3>
                <div class="number"><?php echo number_format($total['total_units'] ?? 0); ?></div>
            </div>
            <div class="stat-card">
                <h3>Products</h3>
                <div class="number"><?php echo count($sales); ?></div>
            </div>
        </div>
        
        <!-- Sales Report Table (Powered by SQL VIEW) -->
        <div class="card">
            <div class="card-header">
                🏆 Product Sales Report
                <span style="float: right; font-size: 12px;">Data from vw_sales_report VIEW</span>
            </div>
            <div class="card-body">
                <?php if (empty($sales)): ?>
                    <div class="alert">
                        No sales yet. Place some test orders to see data!
                        <br><br>
                        <a href="../public/shop.php">Go to Shop →</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Product Name</th>
                                <th>Brand</th>
                                <th>Category</th>
                                <th class="text-end">Units Sold</th>
                                <th class="text-end">Revenue</th>
                                <th class="text-end">Current Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sales as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['brand_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                                <td class="text-end"><?php echo number_format($row['total_units_sold']); ?></td>
                                <td class="text-end"><?php echo formatPrice($row['total_revenue']); ?></td>
                                <td class="text-end">
                                    <span style="color: <?php echo $row['current_stock'] < 10 ? '#e74c3c' : '#27ae60'; ?>;">
                                        <?php echo $row['current_stock']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="grand-total">
                                <td colspan="4" class="text-end"><strong>Grand Total:</strong></td>
                                <td class="text-end"><strong><?php echo formatPrice($total['grand_total'] ?? 0); ?></strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        
    </div>
</body>
</html>