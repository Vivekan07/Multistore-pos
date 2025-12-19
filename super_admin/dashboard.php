<?php
/**
 * Super Admin Dashboard
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('super_admin');

$pageTitle = 'Super Admin Dashboard';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();

// Get statistics
$stats = [];

// Total stores
$result = $conn->query("SELECT COUNT(*) as total FROM stores");
$stats['total_stores'] = $result->fetch_assoc()['total'];

// Active stores
$result = $conn->query("SELECT COUNT(*) as total FROM stores WHERE status = 'active'");
$stats['active_stores'] = $result->fetch_assoc()['total'];

// Total admins
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role_id = 2");
$stats['total_admins'] = $result->fetch_assoc()['total'];

// Total cashiers
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role_id = 3");
$stats['total_cashiers'] = $result->fetch_assoc()['total'];

// Total sales today (all stores)
$result = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(final_amount), 0) as revenue 
                       FROM sales 
                       WHERE DATE(sale_date) = CURDATE()");
$todaySales = $result->fetch_assoc();
$stats['today_sales'] = $todaySales['total'];
$stats['today_revenue'] = $todaySales['revenue'];

// Total sales this month
$result = $conn->query("SELECT COUNT(*) as total, COALESCE(SUM(final_amount), 0) as revenue 
                       FROM sales 
                       WHERE MONTH(sale_date) = MONTH(CURDATE()) 
                       AND YEAR(sale_date) = YEAR(CURDATE())");
$monthSales = $result->fetch_assoc();
$stats['month_sales'] = $monthSales['total'];
$stats['month_revenue'] = $monthSales['revenue'];

// Recent stores
$result = $conn->query("SELECT s.*, 
                       (SELECT COUNT(*) FROM users WHERE store_id = s.id AND role_id = 2) as admin_count,
                       (SELECT COUNT(*) FROM users WHERE store_id = s.id AND role_id = 3) as cashier_count
                       FROM stores s 
                       ORDER BY s.created_at DESC 
                       LIMIT 5");
$recentStores = $result->fetch_all(MYSQLI_ASSOC);
?>

<div class="page-header">
    <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
    <p>Overview of all stores and system statistics</p>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Stores</span>
            <div class="stat-card-icon primary">
                <i class="fas fa-store"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_stores']; ?></div>
        <div class="stat-card-change"><?php echo $stats['active_stores']; ?> active</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Admins</span>
            <div class="stat-card-icon info">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_admins']; ?></div>
        <div class="stat-card-change">Across all stores</div>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <span class="stat-card-title">Total Cashiers</span>
            <div class="stat-card-icon success">
                <i class="fas fa-user-tie"></i>
            </div>
        </div>
        <div class="stat-card-value"><?php echo $stats['total_cashiers']; ?></div>
        <div class="stat-card-change">Across all stores</div>
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

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-store"></i> Recent Stores</h2>
        <a href="stores.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Manage Stores
        </a>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Store Name</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Admins</th>
                    <th>Cashiers</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentStores)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                            <p>No stores found. Create your first store to get started.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentStores as $store): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($store['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($store['address'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="badge <?php echo $store['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo ucfirst($store['status']); ?>
                                </span>
                            </td>
                            <td><?php echo $store['admin_count']; ?></td>
                            <td><?php echo $store['cashier_count']; ?></td>
                            <td><?php echo formatDate($store['created_at']); ?></td>
                            <td>
                                <a href="stores.php?action=edit&id=<?php echo $store['id']; ?>" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

