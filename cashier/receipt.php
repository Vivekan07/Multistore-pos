<?php
/**
 * Receipt Print View
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('cashier');

$saleId = intval($_GET['id'] ?? 0);

if (!$saleId) {
    die('Invalid sale ID');
}

$conn = getDBConnection();
$cashierId = $_SESSION['user_id'];

// Get sale details
$stmt = $conn->prepare("SELECT s.*, u.full_name as cashier_name, st.name as store_name, st.address as store_address, st.phone as store_phone
                       FROM sales s
                       INNER JOIN users u ON s.cashier_id = u.id
                       INNER JOIN stores st ON s.store_id = st.id
                       WHERE s.id = ? AND s.cashier_id = ?");
$stmt->bind_param("ii", $saleId, $cashierId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die('Sale not found or access denied');
}

$sale = $result->fetch_assoc();
$stmt->close();

// Get sale items
$stmt = $conn->prepare("SELECT * FROM sale_items WHERE sale_id = ?");
$stmt->bind_param("i", $saleId);
$stmt->execute();
$result = $stmt->get_result();
$items = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Get payment details
$stmt = $conn->prepare("SELECT * FROM payments WHERE sale_id = ?");
$stmt->bind_param("i", $saleId);
$stmt->execute();
$result = $stmt->get_result();
$payment = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - <?php echo htmlspecialchars($sale['sale_number']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            max-width: 400px;
            margin: 0 auto;
        }
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px dashed #000;
            padding-bottom: 15px;
        }
        .receipt-header h1 {
            font-size: 20px;
            margin-bottom: 5px;
        }
        .receipt-info {
            margin-bottom: 15px;
            font-size: 12px;
        }
        .receipt-info div {
            margin-bottom: 3px;
        }
        .items-table {
            width: 100%;
            margin-bottom: 15px;
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 10px 0;
        }
        .items-table th,
        .items-table td {
            padding: 5px 0;
            font-size: 12px;
        }
        .items-table th {
            text-align: left;
            border-bottom: 1px dashed #000;
            padding-bottom: 5px;
            margin-bottom: 5px;
        }
        .items-table .item-row {
            border-bottom: 1px dotted #ccc;
        }
        .items-table .item-name {
            font-weight: bold;
        }
        .items-table .item-details {
            font-size: 11px;
            color: #666;
            padding-left: 10px;
        }
        .summary {
            margin-top: 15px;
            border-top: 2px dashed #000;
            padding-top: 10px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 12px;
        }
        .summary-row.total {
            font-weight: bold;
            font-size: 14px;
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px dashed #000;
            font-size: 11px;
        }
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="receipt-header">
        <h1><?php echo htmlspecialchars($sale['store_name']); ?></h1>
        <?php if ($sale['store_address']): ?>
            <div><?php echo htmlspecialchars($sale['store_address']); ?></div>
        <?php endif; ?>
        <?php if ($sale['store_phone']): ?>
            <div>Tel: <?php echo htmlspecialchars($sale['store_phone']); ?></div>
        <?php endif; ?>
    </div>
    
    <div class="receipt-info">
        <div><strong>Sale #:</strong> <?php echo htmlspecialchars($sale['sale_number']); ?></div>
        <div><strong>Date:</strong> <?php echo formatDate($sale['sale_date']); ?></div>
        <div><strong>Cashier:</strong> <?php echo htmlspecialchars($sale['cashier_name']); ?></div>
    </div>
    
    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th style="text-align: right;">Price</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr class="item-row">
                    <td>
                        <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                        <div class="item-details">
                            <?php echo $item['quantity']; ?> × <?php echo formatCurrency($item['unit_price']); ?>
                            <?php if ($item['tax_rate'] > 0): ?>
                                (Tax: <?php echo $item['tax_rate']; ?>%)
                            <?php endif; ?>
                        </div>
                    </td>
                    <td style="text-align: right;">
                        <?php echo formatCurrency($item['subtotal']); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <div class="summary">
        <div class="summary-row">
            <span>Subtotal:</span>
            <span><?php echo formatCurrency($sale['total_amount']); ?></span>
        </div>
        <?php if ($sale['tax_amount'] > 0): ?>
            <div class="summary-row">
                <span>Tax:</span>
                <span><?php echo formatCurrency($sale['tax_amount']); ?></span>
            </div>
        <?php endif; ?>
        <?php if ($sale['discount_amount'] > 0): ?>
            <div class="summary-row">
                <span>Discount:</span>
                <span>-<?php echo formatCurrency($sale['discount_amount']); ?></span>
            </div>
        <?php endif; ?>
        <div class="summary-row total">
            <span>TOTAL:</span>
            <span><?php echo formatCurrency($sale['final_amount']); ?></span>
        </div>
    </div>
    
    <?php if ($payment): ?>
        <div class="summary" style="margin-top: 10px;">
            <div class="summary-row">
                <span>Payment Method:</span>
                <span><?php echo ucfirst($payment['payment_method']); ?></span>
            </div>
            <?php if ($payment['cash_amount'] > 0): ?>
                <div class="summary-row">
                    <span>Cash:</span>
                    <span><?php echo formatCurrency($payment['cash_amount']); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($payment['card_amount'] > 0): ?>
                <div class="summary-row">
                    <span>Card:</span>
                    <span><?php echo formatCurrency($payment['card_amount']); ?></span>
                </div>
            <?php endif; ?>
            <?php if ($payment['change_amount'] > 0): ?>
                <div class="summary-row">
                    <span>Change:</span>
                    <span><?php echo formatCurrency($payment['change_amount']); ?></span>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <!-- <div class="footer">
        <div>Thank you for your business!</div>
        <div style="margin-top: 10px;"><?php echo date('Y'); ?> <?php echo APP_NAME; ?></div>
    </div> -->
    
    <div class="no-print" style="text-align: center; margin-top: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; background: #10b981; color: white; border: none; border-radius: 5px;">
            Print Receipt
        </button>
        <button onclick="closeReceipt()" style="padding: 10px 20px; font-size: 16px; cursor: pointer; margin-left: 10px; background: #64748b; color: white; border: none; border-radius: 5px;">
            Close
        </button>
    </div>
    
    <script>
        // Function to close receipt
        function closeReceipt() {
            window.close();
        }
        
        // Auto-close after print dialog (optional)
        window.addEventListener('afterprint', function() {
            // Optionally close after printing
            // Uncomment the line below if you want auto-close after print
            // setTimeout(() => window.close(), 1000);
        });
    </script>
</body>
</html>

