<?php
/**
 * Analytics (Super Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('super_admin');

$pageTitle = 'Analytics';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();

// Get date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Store-wise performance
$stmt = $conn->prepare("SELECT s.id, s.name, 
                       COUNT(DISTINCT sa.id) as total_sales,
                       COALESCE(SUM(sa.final_amount), 0) as total_revenue,
                       COALESCE(SUM(si.quantity * (si.unit_price - p.cost_price)), 0) as total_profit
                       FROM stores s
                       LEFT JOIN sales sa ON sa.store_id = s.id AND sa.sale_date BETWEEN ? AND ?
                       LEFT JOIN sale_items si ON si.sale_id = sa.id
                       LEFT JOIN products p ON p.id = si.product_id
                       GROUP BY s.id, s.name
                       ORDER BY total_revenue DESC");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$storePerformance = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Top products across all stores
$stmt = $conn->prepare("SELECT p.name, p.sku, s.name as store_name,
                       SUM(si.quantity) as total_sold,
                       SUM(si.subtotal) as total_revenue
                       FROM sale_items si
                       INNER JOIN sales sa ON sa.id = si.sale_id
                       INNER JOIN products p ON p.id = si.product_id
                       INNER JOIN stores s ON s.id = sa.store_id
                       WHERE sa.sale_date BETWEEN ? AND ?
                       GROUP BY p.id, p.name, p.sku, s.name
                       ORDER BY total_sold DESC
                       LIMIT 10");
$stmt->bind_param("ss", $startDate, $endDate);
$stmt->execute();
$topProducts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="page-header">
    <h1><i class="fas fa-chart-line"></i> Analytics</h1>
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

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-store"></i> Store Performance</h2>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Store Name</th>
                    <th>Total Sales</th>
                    <th>Total Revenue</th>
                    <th>Total Profit</th>
                    <th>Profit Margin</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($storePerformance)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            No data available for the selected date range.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($storePerformance as $store): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($store['name']); ?></strong></td>
                            <td><?php echo $store['total_sales']; ?></td>
                            <td><?php echo formatCurrency($store['total_revenue']); ?></td>
                            <td><?php echo formatCurrency($store['total_profit']); ?></td>
                            <td>
                                <?php 
                                $margin = $store['total_revenue'] > 0 
                                    ? ($store['total_profit'] / $store['total_revenue']) * 100 
                                    : 0;
                                echo number_format($margin, 2) . '%';
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-star"></i> Top Products</h2>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>SKU</th>
                    <th>Store</th>
                    <th>Total Sold</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($topProducts)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px;">
                            No data available for the selected date range.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($topProducts as $product): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td><?php echo htmlspecialchars($product['store_name']); ?></td>
                            <td><?php echo $product['total_sold']; ?></td>
                            <td><?php echo formatCurrency($product['total_revenue']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

