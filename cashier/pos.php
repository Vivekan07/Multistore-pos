<?php
/**
 * Point of Sale Interface (Cashier Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/upload_handler.php';
requireRole('cashier');

$pageTitle = 'Point of Sale';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$storeId = getCurrentStoreId();

// Get categories for filter
$categoriesStmt = $conn->prepare("SELECT DISTINCT c.id, c.name 
                                  FROM categories c 
                                  INNER JOIN products p ON c.id = p.category_id 
                                  WHERE p.store_id = ? AND p.status = 'active' 
                                  ORDER BY c.name");
$categoriesStmt->bind_param("i", $storeId);
$categoriesStmt->execute();
$categoriesResult = $categoriesStmt->get_result();
$categories = $categoriesResult->fetch_all(MYSQLI_ASSOC);
$categoriesStmt->close();

// Get active products
$stmt = $conn->prepare("SELECT p.*, c.name as category_name, c.id as category_id
                       FROM products p 
                       INNER JOIN categories c ON p.category_id = c.id 
                       WHERE p.store_id = ? AND p.status = 'active' 
                       ORDER BY c.name, p.name");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Add SweetAlert2 for POS page
$additionalScripts = ['https://cdn.jsdelivr.net/npm/sweetalert2@11'];
$additionalStyles = ['https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'];
?>

<div class="pos-container">
    <div class="pos-products">
        <div class="pos-filters">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="product-search" placeholder="Search products by name, SKU, or barcode..." 
                       class="search-input">
            </div>
        </div>
        
        <div class="category-tabs-container">
            <div class="category-tabs" id="category-tabs">
                <button class="category-tab active" data-category="all" onclick="filterByCategory('all')">
                    <i class="fas fa-th"></i>
                    <span>All Products</span>
                </button>
                <?php foreach ($categories as $category): ?>
                    <button class="category-tab" data-category="<?php echo $category['id']; ?>" onclick="filterByCategory(<?php echo $category['id']; ?>)">
                        <span><?php echo htmlspecialchars($category['name']); ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div id="products-grid" class="products-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card" data-product-id="<?php echo $product['id']; ?>" 
                     data-name="<?php echo htmlspecialchars($product['name']); ?>"
                     data-sku="<?php echo htmlspecialchars($product['sku']); ?>"
                     data-barcode="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>"
                     data-price="<?php echo $product['selling_price']; ?>"
                     data-tax="<?php echo $product['tax_rate']; ?>"
                     data-stock="<?php echo $product['stock_quantity']; ?>"
                     data-category="<?php echo $product['category_id']; ?>"
                     onclick="addToCart(<?php echo $product['id']; ?>)">
                    <div class="product-image-container">
                        <img src="<?php echo getProductImageUrl($product['product_image'] ?? null); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             class="product-image"
                             loading="lazy"
                             onerror="this.src='<?php echo BASE_URL; ?>assets/images/no-image.svg'">
                        <?php if ($product['stock_quantity'] <= $product['low_stock_threshold']): ?>
                            <span class="stock-badge stock-low">Low Stock</span>
                        <?php elseif ($product['stock_quantity'] == 0): ?>
                            <span class="stock-badge stock-out">Out of Stock</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-sku"><?php echo htmlspecialchars($product['sku']); ?></div>
                        <div class="product-price"><?php echo formatCurrency($product['selling_price']); ?></div>
                        <div class="product-stock">
                            <i class="fas fa-warehouse"></i> <?php echo $product['stock_quantity']; ?> in stock
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="pos-cart">
        <div class="cart-header">
            <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
            <span class="cart-count" id="cart-count">0 items</span>
        </div>
        
        <div class="cart-items" id="cart-items">
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <small>Add products to get started</small>
            </div>
        </div>
        
        <div class="cart-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span id="cart-subtotal">LKR 0.00</span>
            </div>
            <div class="summary-row">
                <span>Tax:</span>
                <span id="cart-tax">LKR 0.00</span>
            </div>
            <div class="summary-row">
                <span>Discount:</span>
                <span id="cart-discount">LKR 0.00</span>
            </div>
            <div class="summary-row total">
                <span>Total:</span>
                <span id="cart-total">LKR 0.00</span>
            </div>
            
            <div style="margin-top: 20px;">
                <div class="form-group">
                    <label for="discount-percent"><i class="fas fa-percent"></i> Discount (%)</label>
                    <input type="number" id="discount-percent" min="0" max="100" step="0.01" value="0" 
                           oninput="updateDiscount()" onchange="updateDiscount()" style="width: 100%;">
                </div>
                
                <div class="form-group">
                    <label for="payment-method"><i class="fas fa-money-bill-wave"></i> Payment Method</label>
                    <select id="payment-method" style="width: 100%;">
                        <option value="cash">Cash</option>
                        <option value="card">Card</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </div>
                
                <div id="cash-amount-group" style="display: none;">
                    <div class="form-group">
                        <label for="cash-amount"><i class="fas fa-dollar-sign"></i> Cash Received</label>
                        <input type="number" id="cash-amount" step="0.01" min="0" value="0" 
                               oninput="updateChange()" onchange="updateChange()" style="width: 100%;">
                    </div>
                    <div class="summary-row">
                        <span>Change:</span>
                        <span id="change-amount">LKR 0.00</span>
                    </div>
                </div>
                
                <button type="button" class="btn btn-success btn-block" onclick="processPayment()" id="checkout-btn" disabled>
                    <i class="fas fa-check"></i> Checkout
                </button>
                
                <button type="button" class="btn btn-secondary btn-block" onclick="clearCart()" style="margin-top: 10px;">
                    <i class="fas fa-trash"></i> Clear Cart
                </button>
            </div>
        </div>
    </div>
</div>

<!-- SweetAlert2 JS - Load before inline scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// Cart and discount variables
let cart = [];
let discountPercent = 0;

// Save cart to localStorage
function saveCartToStorage() {
    try {
        localStorage.setItem('pos_cart', JSON.stringify(cart));
        localStorage.setItem('pos_discount', discountPercent.toString());
    } catch (error) {
        console.error('Error saving cart to storage:', error);
    }
}

// Load cart from localStorage
function loadCartFromStorage() {
    try {
        const savedCart = localStorage.getItem('pos_cart');
        const savedDiscount = localStorage.getItem('pos_discount');
        
        if (savedCart) {
            cart = JSON.parse(savedCart);
        }
        
        if (savedDiscount) {
            discountPercent = parseFloat(savedDiscount);
            const discountInput = document.getElementById('discount-percent');
            if (discountInput) {
                discountInput.value = discountPercent;
            }
        }
        
        if (cart.length > 0) {
            updateCartDisplay();
            updateTotals();
        }
    } catch (error) {
        console.error('Error loading cart from storage:', error);
        cart = [];
        discountPercent = 0;
    }
}

// Load cart when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadCartFromStorage();
});

// Current filter state
let currentCategoryFilter = 'all';

// Filter by category
function filterByCategory(categoryId) {
    currentCategoryFilter = categoryId;
    
    // Update active tab with smooth transition
    document.querySelectorAll('.category-tab').forEach(tab => {
        tab.classList.remove('active');
        if (tab.getAttribute('data-category') == categoryId) {
            tab.classList.add('active');
            // Smooth scroll to active tab if needed
            tab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
        }
    });
    
    // Apply filters with fade effect
    const productsGrid = document.getElementById('products-grid');
    productsGrid.style.opacity = '0.5';
    productsGrid.style.transition = 'opacity 0.2s ease';
    
    setTimeout(() => {
        applyFilters();
        productsGrid.style.opacity = '1';
    }, 100);
}

// Apply both search and category filters
function applyFilters() {
    const searchTerm = document.getElementById('product-search').value.toLowerCase();
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        const name = card.getAttribute('data-name').toLowerCase();
        const sku = card.getAttribute('data-sku').toLowerCase();
        const barcode = card.getAttribute('data-barcode').toLowerCase();
        const category = card.getAttribute('data-category');
        
        // Category filter
        const categoryMatch = currentCategoryFilter === 'all' || category == currentCategoryFilter;
        
        // Search filter
        const searchMatch = !searchTerm || 
            name.includes(searchTerm) || 
            sku.includes(searchTerm) || 
            barcode.includes(searchTerm);
        
        if (categoryMatch && searchMatch) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Product search
document.getElementById('product-search').addEventListener('input', function(e) {
    applyFilters();
    
    // If barcode matches exactly, add to cart
    const searchTerm = e.target.value;
    if (searchTerm.length > 5) {
        const productCards = document.querySelectorAll('.product-card');
        productCards.forEach(card => {
            const barcode = card.getAttribute('data-barcode');
            if (barcode && barcode === searchTerm && card.style.display !== 'none') {
                const productId = parseInt(card.getAttribute('data-product-id'));
                addToCart(productId);
                e.target.value = '';
                applyFilters();
            }
        });
    }
});

// Add to cart
function addToCart(productId) {
    const productCard = document.querySelector(`[data-product-id="${productId}"]`);
    if (!productCard) return;
    
    const stock = parseInt(productCard.getAttribute('data-stock'));
    if (stock <= 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Out of Stock',
            text: 'This product is currently out of stock!',
            confirmButtonColor: '#2563eb'
        });
        return;
    }
    
    const existingItem = cart.find(item => item.id === productId);
    
    if (existingItem) {
        if (existingItem.quantity >= stock) {
            Swal.fire({
                icon: 'warning',
                title: 'Insufficient Stock',
                text: 'Not enough stock available!',
                confirmButtonColor: '#2563eb'
            });
            return;
        }
        existingItem.quantity++;
    } else {
        cart.push({
            id: productId,
            name: productCard.getAttribute('data-name'),
            sku: productCard.getAttribute('data-sku'),
            price: parseFloat(productCard.getAttribute('data-price')),
            tax: parseFloat(productCard.getAttribute('data-tax')),
            stock: stock,
            quantity: 1
        });
    }
    
    saveCartToStorage();
    updateCartDisplay();
}

// Remove from cart
function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    saveCartToStorage();
    updateCartDisplay();
}

// Update quantity
function updateQuantity(productId, change) {
    const item = cart.find(item => item.id === productId);
    if (!item) return;
    
    const newQuantity = item.quantity + change;
    if (newQuantity <= 0) {
        removeFromCart(productId);
    } else if (newQuantity > item.stock) {
        Swal.fire({
            icon: 'warning',
            title: 'Insufficient Stock',
            text: 'Not enough stock available!',
            confirmButtonColor: '#2563eb'
        });
    } else {
        item.quantity = newQuantity;
        saveCartToStorage();
        updateCartDisplay();
    }
}

// Update cart display
function updateCartDisplay() {
    const cartItemsDiv = document.getElementById('cart-items');
    const checkoutBtn = document.getElementById('checkout-btn');
    const cartCount = document.getElementById('cart-count');
    
    // Update cart count
    const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
    if (cartCount) {
        cartCount.textContent = totalItems + (totalItems === 1 ? ' item' : ' items');
    }
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = `
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <p>Your cart is empty</p>
                <small>Add products to get started</small>
            </div>
        `;
        checkoutBtn.disabled = true;
    } else {
        let html = '';
        cart.forEach(item => {
            const subtotal = item.price * item.quantity;
            const tax = subtotal * (item.tax / 100);
            html += `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-details">
                            LKR ${item.price.toFixed(2)} × ${item.quantity} = LKR ${subtotal.toFixed(2)}
                        </div>
                    </div>
                    <div class="cart-item-actions">
                        <div class="quantity-control">
                            <button class="quantity-btn" onclick="updateQuantity(${item.id}, -1)">-</button>
                            <input type="number" class="quantity-input" value="${item.quantity}" 
                                   onchange="updateQuantity(${item.id}, parseInt(this.value) - ${item.quantity})" 
                                   min="1" max="${item.stock}">
                            <button class="quantity-btn" onclick="updateQuantity(${item.id}, 1)">+</button>
                        </div>
                        <button class="btn btn-sm btn-danger" onclick="removeFromCart(${item.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        cartItemsDiv.innerHTML = html;
        checkoutBtn.disabled = false;
    }
    
    updateTotals();
}

// Update totals
function updateTotals() {
    let subtotal = 0;
    let tax = 0;
    
    cart.forEach(item => {
        const itemSubtotal = item.price * item.quantity;
        subtotal += itemSubtotal;
        tax += itemSubtotal * (item.tax / 100);
    });
    
    const discount = subtotal * (discountPercent / 100);
    const total = subtotal + tax - discount;
    
    document.getElementById('cart-subtotal').textContent = 'LKR ' + subtotal.toFixed(2);
    document.getElementById('cart-tax').textContent = 'LKR ' + tax.toFixed(2);
    document.getElementById('cart-discount').textContent = '-LKR ' + discount.toFixed(2);
    document.getElementById('cart-total').textContent = 'LKR ' + total.toFixed(2);
    
    updateChange();
}

// Update discount
function updateDiscount() {
    discountPercent = parseFloat(document.getElementById('discount-percent').value) || 0;
    saveCartToStorage();
    updateTotals();
}

// Update change
function updateChange() {
    const paymentMethod = document.getElementById('payment-method').value;
    const cashAmountGroup = document.getElementById('cash-amount-group');
    
    if (paymentMethod === 'cash' || paymentMethod === 'mixed') {
        cashAmountGroup.style.display = 'block';
        const total = parseFloat(document.getElementById('cart-total').textContent.replace('LKR ', ''));
        const cashAmount = parseFloat(document.getElementById('cash-amount').value) || 0;
        const change = Math.max(0, cashAmount - total);
        document.getElementById('change-amount').textContent = 'LKR ' + change.toFixed(2);
    } else {
        cashAmountGroup.style.display = 'none';
    }
}

// Payment method change
document.getElementById('payment-method').addEventListener('change', updateChange);

// Process payment
function processPayment() {
    if (cart.length === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Empty Cart',
            text: 'Your cart is empty!',
            confirmButtonColor: '#2563eb'
        });
        return;
    }
    
    const paymentMethod = document.getElementById('payment-method').value;
    const total = parseFloat(document.getElementById('cart-total').textContent.replace('LKR ', ''));
    
    if ((paymentMethod === 'cash' || paymentMethod === 'mixed') && parseFloat(document.getElementById('cash-amount').value) < total) {
        Swal.fire({
            icon: 'error',
            title: 'Insufficient Cash',
            text: 'Cash amount is less than the total amount!',
            confirmButtonColor: '#2563eb'
        });
        return;
    }
    
    Swal.fire({
        title: 'Confirm Payment',
        text: 'Process payment and complete sale?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Process Payment',
        cancelButtonText: 'Cancel'
    }).then((result) => {
        if (!result.isConfirmed) {
            return;
        }
        
        processSale();
    });
}

function processSale() {
    // Get payment method and total
    const paymentMethod = document.getElementById('payment-method').value;
    const total = parseFloat(document.getElementById('cart-total').textContent.replace('LKR ', ''));
    
    // Prepare data
    const saleData = {
        items: cart.map(item => ({
            product_id: item.id,
            quantity: item.quantity,
            unit_price: item.price,
            tax_rate: item.tax
        })),
        discount_percent: discountPercent,
        payment_method: paymentMethod,
        cash_amount: paymentMethod === 'cash' || paymentMethod === 'mixed' ? parseFloat(document.getElementById('cash-amount').value) || 0 : 0,
        card_amount: paymentMethod === 'card' || paymentMethod === 'mixed' ? (paymentMethod === 'mixed' ? total - parseFloat(document.getElementById('cash-amount').value || 0) : total) : 0
    };
    
    // Show loading
    Swal.fire({
        title: 'Processing...',
        text: 'Please wait while we process your payment',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    // Send to server
    fetch('<?php echo BASE_URL; ?>api/process_sale.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(saleData)
    })
    .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Clear cart immediately
                clearCart();
                
                // Open receipt in new window
                const receiptWindow = window.open('<?php echo BASE_URL; ?>cashier/receipt.php?id=' + data.sale_id, '_blank', 'width=800,height=600');
                
                // Show success message
                Swal.fire({
                    icon: 'success',
                    title: 'Sale Completed!',
                    text: 'Sale #' + data.sale_number + ' has been processed successfully',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#10b981',
                    allowOutsideClick: false,
                    allowEscapeKey: false
                }).then(() => {
                    // Refresh POS page after closing the alert
                    location.reload();
                });
                
                // Monitor receipt window - if closed, refresh POS
                if (receiptWindow) {
                    const checkClosed = setInterval(() => {
                        if (receiptWindow.closed) {
                            clearInterval(checkClosed);
                            // Refresh POS page when receipt window is closed
                            location.reload();
                        }
                    }, 500);
                }
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: data.message,
                    confirmButtonColor: '#2563eb'
                });
            }
        })
    .catch(error => {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred. Please try again.',
            confirmButtonColor: '#2563eb'
        });
    });
}

// Clear cart
function clearCart() {
    cart = [];
    discountPercent = 0;
    document.getElementById('discount-percent').value = 0;
    document.getElementById('payment-method').value = 'cash';
    document.getElementById('cash-amount').value = 0;
    // Clear localStorage
    localStorage.removeItem('pos_cart');
    localStorage.removeItem('pos_discount');
    updateCartDisplay();
    updateChange();
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

