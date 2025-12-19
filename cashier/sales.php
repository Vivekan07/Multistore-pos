<?php
/**
 * Sales History (Cashier Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('cashier');

$pageTitle = 'My Sales';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$cashierId = $_SESSION['user_id'];

// Get sales
$stmt = $conn->prepare("SELECT s.*, 
                       (SELECT COUNT(*) FROM sale_items WHERE sale_id = s.id) as item_count
                       FROM sales s 
                       WHERE s.cashier_id = ? 
                       ORDER BY s.sale_date DESC 
                       LIMIT 100");
$stmt->bind_param("i", $cashierId);
$stmt->execute();
$result = $stmt->get_result();
$sales = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<div class="page-header">
    <h1><i class="fas fa-receipt"></i> My Sales</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2>Sales History</h2>
    </div>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Sale #</th>
                    <th>Date</th>
                    <th>Items</th>
                    <th>Subtotal</th>
                    <th>Tax</th>
                    <th>Discount</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($sales)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px;">
                            <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                            <p>No sales found.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sale['sale_number']); ?></strong></td>
                            <td><?php echo formatDate($sale['sale_date']); ?></td>
                            <td><?php echo $sale['item_count']; ?></td>
                            <td><?php echo formatCurrency($sale['total_amount']); ?></td>
                            <td><?php echo formatCurrency($sale['tax_amount']); ?></td>
                            <td><?php echo formatCurrency($sale['discount_amount']); ?></td>
                            <td><strong><?php echo formatCurrency($sale['final_amount']); ?></strong></td>
                            <td>
                                <span class="badge badge-success">
                                    <?php echo ucfirst($sale['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="receipt.php?id=<?php echo $sale['id']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                    <i class="fas fa-print"></i> Receipt
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

