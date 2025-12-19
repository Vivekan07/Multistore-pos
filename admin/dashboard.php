<?php
/**
 * Admin Dashboard
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$pageTitle = 'Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$storeId = getCurrentStoreId();

// Get statistics
$stats = [];

// Total products
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products WHERE store_id = ?");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_products'] = $result->fetch_assoc()['total'];
$stmt->close();

// Low stock products
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM products 
                       WHERE store_id = ? AND stock_quantity <= low_stock_threshold AND status = 'active'");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$stats['low_stock'] = $result->fetch_assoc()['total'];
$stmt->close();

// Total cashiers
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE store_id = ? AND role_id = 3");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$stats['total_cashiers'] = $result->fetch_assoc()['total'];
$stmt->close();

// Today's sales
$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(final_amount), 0) as revenue 
                       FROM sales 
                       WHERE store_id = ? AND DATE(sale_date) = CURDATE()");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$todaySales = $result->fetch_assoc();
$stats['today_sales'] = $todaySales['total'];
$stats['today_revenue'] = $todaySales['revenue'];
$stmt->close();

// Monthly sales
$stmt = $conn->prepare("SELECT COUNT(*) as total, COALESCE(SUM(final_amount), 0) as revenue 
                       FROM sales 
                       WHERE store_id = ? AND MONTH(sale_date) = MONTH(CURDATE()) 
                       AND YEAR(sale_date) = YEAR(CURDATE())");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$monthSales = $result->fetch_assoc();
$stats['month_sales'] = $monthSales['total'];
$stats['month_revenue'] = $monthSales['revenue'];
$stmt->close();

// Recent sales
$stmt = $conn->prepare("SELECT s.*, u.full_name as cashier_name 
                       FROM sales s 
                       INNER JOIN users u ON s.cashier_id = u.id 
                       WHERE s.store_id = ? 
                       ORDER BY s.sale_date DESC 
                       LIMIT 10");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$recentSales = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Low stock products
$stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                       FROM products p 
                       INNER JOIN categories c ON p.category_id = c.id 
                       WHERE p.store_id = ? AND p.stock_quantity <= p.low_stock_threshold AND p.status = 'active' 
                       ORDER BY p.stock_quantity ASC 
                       LIMIT 10");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$lowStockProducts = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <p>Store management overview</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Products</span>
            <div class="stat-card-icon primary">
                <i class="fas fa-box"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_products']; ?></div>
        <div class="stat-card-change">
            <span class="badge badge-warning"><?php echo $stats['low_stock']; ?> low stock</span>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Cashiers</span>
            <div class="stat-card-icon info">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_cashiers']; ?></div>
        <div class="stat-card-change">Active cashiers</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Today's Revenue</span>
            <div class="stat-card-icon success">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo formatCurrency($stats['today_revenue']); ?></div>
        <div class="stat-card-change"><?php echo $stats['today_sales']; ?> sales</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Monthly Revenue</span>
            <div class="stat-card-icon warning">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo formatCurrency($stats['month_revenue']); ?></div>
        <div class="stat-card-change"><?php echo $stats['month_sales']; ?> sales</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-exclamation-triangle"></i> Low Stock Products</h2>
            <a href="inventory.php" class="btn btn-primary btn-sm">
                <i class="fas fa-warehouse"></i> View All
            </a>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Stock</th>
                        <th>Threshold</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($lowStockProducts)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                                <p>All products are well stocked!</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($lowStockProducts as $product): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td>
                                    <span class="badge badge-danger"><?php echo $product['stock_quantity']; ?></span>
                                </td>
                                <td><?php echo $product['low_stock_threshold']; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-receipt"></i> Recent Sales</h2>
            <a href="reports.php" class="btn btn-primary btn-sm">
                <i class="fas fa-chart-bar"></i> View Reports
            </a>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Sale #</th>
                        <th>Cashier</th>
                        <th>Amount</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentSales)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">
                                <i class="fas fa-inbox" style="color: #ccc;"></i>
                                <p>No sales yet</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($recentSales as $sale): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($sale['sale_number']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sale['cashier_name']); ?></td>
                                <td><?php echo formatCurrency($sale['final_amount']); ?></td>
                                <td><?php echo formatDate($sale['sale_date']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

