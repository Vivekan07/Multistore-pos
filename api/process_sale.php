<?php
/**
 * Process Sale API Endpoint
 * Advanced Point Of Sale
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Check authentication
if (!isLoggedIn() || !hasRole('cashier')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['items']) || empty($input['items'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit();
}

$conn = getDBConnection();
$storeId = getCurrentStoreId();
$cashierId = $_SESSION['user_id'];

// Start transaction
$conn->begin_transaction();

try {
    // Generate sale number
    $saleNumber = 'SALE-' . date('Ymd') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Calculate totals
    $subtotal = 0;
    $taxAmount = 0;
    $discountPercent = floatval($input['discount_percent'] ?? 0);
    $discountAmount = 0;
    
    // Validate and calculate items
    $saleItems = [];
    foreach ($input['items'] as $item) {
        $productId = intval($item['product_id']);
        $quantity = intval($item['quantity']);
        $unitPrice = floatval($item['unit_price']);
        $taxRate = floatval($item['tax_rate'] ?? 0);
        
        // Check product exists and has stock
        $productStmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND store_id = ? AND status = 'active'");
        $productStmt->bind_param("ii", $productId, $storeId);
        $productStmt->execute();
        $productResult = $productStmt->get_result();
        
        if ($productResult->num_rows == 0) {
            throw new Exception("Product not found or inactive");
        }
        
        $product = $productResult->fetch_assoc();
        
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception("Insufficient stock for product: " . $product['name']);
        }
        
        $itemSubtotal = $unitPrice * $quantity;
        $itemTax = $itemSubtotal * ($taxRate / 100);
        
        $subtotal += $itemSubtotal;
        $taxAmount += $itemTax;
        
        $saleItems[] = [
            'product_id' => $productId,
            'product_name' => $product['name'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'tax_rate' => $taxRate,
            'tax_amount' => $itemTax,
            'subtotal' => $itemSubtotal
        ];
    }
    
    $discountAmount = $subtotal * ($discountPercent / 100);
    $finalAmount = $subtotal + $taxAmount - $discountAmount;
    
    // Insert sale
    $saleStmt = $conn->prepare("INSERT INTO sales (store_id, cashier_id, sale_number, total_amount, tax_amount, discount_amount, final_amount) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $saleStmt->bind_param("iisdddd", $storeId, $cashierId, $saleNumber, $subtotal, $taxAmount, $discountAmount, $finalAmount);
    
    if (!$saleStmt->execute()) {
        throw new Exception("Error creating sale: " . $saleStmt->error);
    }
    
    $saleId = $conn->insert_id;
    $saleStmt->close();
    
    // Insert sale items and update inventory
    foreach ($saleItems as $item) {
        // Insert sale item
        $itemStmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, product_name, quantity, unit_price, tax_rate, tax_amount, subtotal) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $itemStmt->bind_param("iisidddd", $saleId, $item['product_id'], $item['product_name'], $item['quantity'], $item['unit_price'], $item['tax_rate'], $item['tax_amount'], $item['subtotal']);
        
        if (!$itemStmt->execute()) {
            throw new Exception("Error creating sale item: " . $itemStmt->error);
        }
        $itemStmt->close();
        
        // Get previous quantity for log (before update)
        $prevStmt = $conn->prepare("SELECT stock_quantity FROM products WHERE id = ?");
        $prevStmt->bind_param("i", $item['product_id']);
        $prevStmt->execute();
        $prevResult = $prevStmt->get_result();
        $prevQuantity = $prevResult->fetch_assoc()['stock_quantity'];
        $prevStmt->close();
        
        // Update product stock
        $updateStmt = $conn->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $updateStmt->bind_param("ii", $item['quantity'], $item['product_id']);
        
        if (!$updateStmt->execute()) {
            throw new Exception("Error updating stock: " . $updateStmt->error);
        }
        
        $newQuantity = $prevQuantity - $item['quantity'];
        
        // Log inventory change
        $logStmt = $conn->prepare("INSERT INTO inventory_logs (store_id, product_id, user_id, transaction_type, quantity_change, previous_quantity, new_quantity, reference_id, notes) VALUES (?, ?, ?, 'sale', ?, ?, ?, ?, ?)");
        $quantityChange = -$item['quantity'];
        $notes = "Sale #" . $saleNumber;
        $logStmt->bind_param("iiiiiiis", $storeId, $item['product_id'], $cashierId, $quantityChange, $prevQuantity, $newQuantity, $saleId, $notes);
        $logStmt->execute();
        $logStmt->close();
        
        $updateStmt->close();
    }
    
    // Insert payment
    $paymentMethod = $input['payment_method'] ?? 'cash';
    $cashAmount = floatval($input['cash_amount'] ?? 0);
    $cardAmount = floatval($input['card_amount'] ?? 0);
    $changeAmount = max(0, $cashAmount - $finalAmount);
    
    $paymentStmt = $conn->prepare("INSERT INTO payments (sale_id, payment_method, amount, cash_amount, card_amount, change_amount) VALUES (?, ?, ?, ?, ?, ?)");
    $paymentStmt->bind_param("isdddd", $saleId, $paymentMethod, $finalAmount, $cashAmount, $cardAmount, $changeAmount);
    
    if (!$paymentStmt->execute()) {
        throw new Exception("Error creating payment: " . $paymentStmt->error);
    }
    $paymentStmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Sale processed successfully',
        'sale_id' => $saleId,
        'sale_number' => $saleNumber,
        'final_amount' => $finalAmount
    ]);
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();

