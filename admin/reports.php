<?php
/**
 * Reports (Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$pageTitle = 'Reports';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$storeId = getCurrentStoreId();

// Get date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Sales summary
$stmt = $conn->prepare("SELECT 
                       COUNT(*) as total_sales,
                       COALESCE(SUM(final_amount), 0) as total_revenue,
                       COALESCE(SUM(tax_amount), 0) as total_tax,
                       COALESCE(SUM(discount_amount), 0) as total_discount
                       FROM sales
                       WHERE store_id = ? AND sale_date BETWEEN ? AND ?");
$stmt->bind_param("iss", $storeId, $startDate, $endDate);
$stmt->execute();
$summary = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Best selling products
$stmt = $conn->prepare("SELECT p.name, p.sku,
                       SUM(si.quantity) as total_sold,
                       SUM(si.subtotal) as total_revenue
                       FROM sale_items si
                       INNER JOIN sales s ON s.id = si.sale_id
                       INNER JOIN products p ON p.id = si.product_id
                       WHERE s.store_id = ? AND s.sale_date BETWEEN ? AND ?
                       GROUP BY p.id, p.name, p.sku
                       ORDER BY total_sold DESC
                       LIMIT 10");
$stmt->bind_param("iss", $storeId, $startDate, $endDate);
$stmt->execute();
$bestProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Cashier performance
$stmt = $conn->prepare("SELECT u.full_name,
                       COUNT(s.id) as sales_count,
                       COALESCE(SUM(s.final_amount), 0) as total_revenue
                       FROM users u
                       LEFT JOIN sales s ON s.cashier_id = u.id AND s.sale_date BETWEEN ? AND ?
                       WHERE u.store_id = ? AND u.role_id = 3
                       GROUP BY u.id, u.full_name
                       ORDER BY total_revenue DESC");
$stmt->bind_param("ssi", $startDate, $endDate, $storeId);
$stmt->execute();
$cashierPerformance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Daily sales
$stmt = $conn->prepare("SELECT DATE(sale_date) as sale_day,
                       COUNT(*) as sales_count,
                       COALESCE(SUM(final_amount), 0) as daily_revenue
                       FROM sales
                       WHERE store_id = ? AND sale_date BETWEEN ? AND ?
                       GROUP BY DATE(sale_date)
                       ORDER BY sale_day DESC");
$stmt->bind_param("iss", $storeId, $startDate, $endDate);
$stmt->execute();
$dailySales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="page-header">
    <h1><i class="fas fa-chart-bar"></i> Reports</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2>Date Range</h2>
    </div>
    
    <form method="GET" action="" style="display: flex; gap: 15px; align-items: end;">
        <div class="form-group" style="margin-bottom: 0;">
            <label for="start_date">Start Date</label>
            <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>" required>
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <label for="end_date">End Date</label>
            <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>" required>
        </div>
        
        <div class="form-group" style="margin-bottom: 0;">
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-filter"></i> Filter
            </button>
        </div>
    </form>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Sales</span>
            <div class="stat-card-icon primary">
                <i class="fas fa-shopping-cart"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $summary['total_sales']; ?></div>
        <div class="stat-card-change">Transactions</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Revenue</span>
            <div class="stat-card-icon success">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo formatCurrency($summary['total_revenue']); ?></div>
        <div class="stat-card-change">Gross revenue</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Tax</span>
            <div class="stat-card-icon warning">
                <i class="fas fa-percent"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo formatCurrency($summary['total_tax']); ?></div>
        <div class="stat-card-change">Collected</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Discount</span>
            <div class="stat-card-icon info">
                <i class="fas fa-tag"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo formatCurrency($summary['total_discount']); ?></div>
        <div class="stat-card-change">Given</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-star"></i> Best Selling Products</h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bestProducts)): ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">
                                No sales data available.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bestProducts as $product): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['sku']); ?></td>
                                <td><?php echo $product['total_sold']; ?></td>
                                <td><?php echo formatCurrency($product['total_revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-tie"></i> Cashier Performance</h2>
        </div>
        
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Cashier</th>
                        <th>Sales</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cashierPerformance)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 20px;">
                                No cashier data available.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cashierPerformance as $cashier): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($cashier['full_name']); ?></strong></td>
                                <td><?php echo $cashier['sales_count']; ?></td>
                                <td><?php echo formatCurrency($cashier['total_revenue']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-calendar-day"></i> Daily Sales</h2>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Sales Count</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dailySales)): ?>
                    <tr>
                        <td colspan="3" style="text-align: center; padding: 20px;">
                            No daily sales data available.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($dailySales as $day): ?>
                        <tr>
                            <td><strong><?php echo formatDate($day['sale_day'], 'd M Y'); ?></strong></td>
                            <td><?php echo $day['sales_count']; ?></td>
                            <td><?php echo formatCurrency($day['daily_revenue']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

