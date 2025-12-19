<?php
/**
 * Inventory Management (Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$pageTitle = 'Inventory Management';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$storeId = getCurrentStoreId();

// Get inventory logs
$stmt = $conn->prepare("SELECT il.*, p.name as product_name, p.sku, u.full_name as user_name
                       FROM inventory_logs il
                       INNER JOIN products p ON il.product_id = p.id
                       INNER JOIN users u ON il.user_id = u.id
                       WHERE il.store_id = ?
                       ORDER BY il.created_at DESC
                       LIMIT 100");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$logs = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get low stock products
$stmt = $conn->prepare("SELECT p.*, c.name as category_name
                       FROM products p
                       INNER JOIN categories c ON p.category_id = c.id
                       WHERE p.store_id = ? AND p.stock_quantity <= p.low_stock_threshold AND p.status = 'active'
                       ORDER BY p.stock_quantity ASC");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$lowStock = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="page-header">
    <h1><i class="fas fa-warehouse"></i> Inventory Management</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-exclamation-triangle"></i> Low Stock Alerts</h2>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Threshold</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lowStock)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <i class="fas fa-check-circle" style="font-size: 48px; color: var(--success-color); margin-bottom: 10px;"></i>
                            <p>All products are well stocked!</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lowStock as $product): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($product['sku']); ?></td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td>
                                <span class="badge badge-danger"><?php echo $product['stock_quantity']; ?></span>
                            </td>
                            <td><?php echo $product['low_stock_threshold']; ?></td>
                            <td>
                                <a href="products.php?action=edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Update Stock
                                </a>
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
        <h2><i class="fas fa-history"></i> Inventory Logs</h2>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Transaction Type</th>
                    <th>Quantity Change</th>
                    <th>Previous</th>
                    <th>New</th>
                    <th>User</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                            <p>No inventory logs found.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo formatDate($log['created_at']); ?></td>
                            <td><strong><?php echo htmlspecialchars($log['product_name']); ?></strong></td>
                            <td><?php echo htmlspecialchars($log['sku']); ?></td>
                            <td>
                                <span class="badge badge-info">
                                    <?php echo ucfirst($log['transaction_type']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo $log['quantity_change'] >= 0 ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $log['quantity_change'] >= 0 ? '+' : ''; ?><?php echo $log['quantity_change']; ?>
                                </span>
                            </td>
                            <td><?php echo $log['previous_quantity']; ?></td>
                            <td><?php echo $log['new_quantity']; ?></td>
                            <td><?php echo htmlspecialchars($log['user_name']); ?></td>
                            <td><?php echo htmlspecialchars($log['notes'] ?? 'N/A'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

