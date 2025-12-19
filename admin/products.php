<?php
/**
 * Product Management (Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/upload_handler.php';
requireRole('admin');

$pageTitle = 'Product Management';
// Add SweetAlert2 CSS for admin pages
$additionalStyles = ['https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'];
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$storeId = getCurrentStoreId();
$action = $_GET['action'] ?? 'list';
$productId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
        header('Location: products.php');
        exit();
    }
    
    if (isset($_POST['create_product'])) {
        $name = sanitizeInput($_POST['name']);
        $sku = sanitizeInput($_POST['sku']);
        $barcode = sanitizeInput($_POST['barcode'] ?? '');
        $categoryId = intval($_POST['category_id']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $costPrice = floatval($_POST['cost_price']);
        $sellingPrice = floatval($_POST['selling_price']);
        $taxRate = floatval($_POST['tax_rate'] ?? 0);
        $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
        $lowStockThreshold = intval($_POST['low_stock_threshold'] ?? 10);
        $status = $_POST['status'] ?? 'active';
        
        // Validate
        if (empty($name) || empty($sku) || empty($categoryId)) {
            $_SESSION['error_message'] = 'Please fill all required fields.';
        } elseif ($sellingPrice <= 0) {
            $_SESSION['error_message'] = 'Selling price must be greater than 0.';
        } else {
            // Check if SKU exists for this store
            $checkStmt = $conn->prepare("SELECT id FROM products WHERE store_id = ? AND sku = ?");
            $checkStmt->bind_param("is", $storeId, $sku);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $_SESSION['error_message'] = 'SKU already exists for this store.';
            } else {
                // Handle image upload if provided
                $productImage = null;
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleImageUpload($_FILES['product_image']);
                    if ($uploadResult['success']) {
                        $productImage = $uploadResult['filename'];
                    } else {
                        $_SESSION['error_message'] = $uploadResult['message'];
                        header('Location: products.php?action=create');
                        exit();
                    }
                }
                
                $stmt = $conn->prepare("INSERT INTO products (store_id, category_id, name, sku, barcode, description, product_image, cost_price, selling_price, tax_rate, stock_quantity, low_stock_threshold, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iisssssdddiis", $storeId, $categoryId, $name, $sku, $barcode, $description, $productImage, $costPrice, $sellingPrice, $taxRate, $stockQuantity, $lowStockThreshold, $status);
                
                if ($stmt->execute()) {
                    $productId = $conn->insert_id;
                    
                    // If image was uploaded with temp name, rename it now
                    if ($productImage && strpos($productImage, 'temp_') === 0) {
                        $newFilename = $productId . '_' . time() . '.' . pathinfo($productImage, PATHINFO_EXTENSION);
                        $oldPath = __DIR__ . '/../uploads/products/' . $productImage;
                        $newPath = __DIR__ . '/../uploads/products/' . $newFilename;
                        if (file_exists($oldPath)) {
                            rename($oldPath, $newPath);
                            // Update database with correct filename
                            $updateStmt = $conn->prepare("UPDATE products SET product_image = ? WHERE id = ?");
                            $updateStmt->bind_param("si", $newFilename, $productId);
                            $updateStmt->execute();
                            $updateStmt->close();
                        }
                    }
                    
                    // Log inventory change
                    $logStmt = $conn->prepare("INSERT INTO inventory_logs (store_id, product_id, user_id, transaction_type, quantity_change, previous_quantity, new_quantity, notes) VALUES (?, ?, ?, 'purchase', ?, 0, ?, ?)");
                    $notes = "Initial stock entry";
                    $logStmt->bind_param("iiiiss", $storeId, $productId, $_SESSION['user_id'], $stockQuantity, $stockQuantity, $notes);
                    $logStmt->execute();
                    $logStmt->close();
                    
                    $_SESSION['success_message'] = 'Product created successfully.';
                    header('Location: products.php');
                    exit();
                } else {
                    // Delete uploaded image if product creation failed
                    if ($productImage) {
                        deleteProductImage($productImage);
                    }
                    $_SESSION['error_message'] = 'Error creating product: ' . $stmt->error;
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    } elseif (isset($_POST['update_product'])) {
        $id = intval($_POST['id']);
        $name = sanitizeInput($_POST['name']);
        $sku = sanitizeInput($_POST['sku']);
        $barcode = sanitizeInput($_POST['barcode'] ?? '');
        $categoryId = intval($_POST['category_id']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $costPrice = floatval($_POST['cost_price']);
        $sellingPrice = floatval($_POST['selling_price']);
        $taxRate = floatval($_POST['tax_rate'] ?? 0);
        $stockQuantity = intval($_POST['stock_quantity'] ?? 0);
        $lowStockThreshold = intval($_POST['low_stock_threshold'] ?? 10);
        $status = $_POST['status'] ?? 'active';
        
        // Verify product belongs to store
        $checkStmt = $conn->prepare("SELECT id, stock_quantity FROM products WHERE id = ? AND store_id = ?");
        $checkStmt->bind_param("ii", $id, $storeId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows == 0) {
            $_SESSION['error_message'] = 'Product not found or access denied.';
        } else {
            $oldProduct = $result->fetch_assoc();
            
            // Check if SKU exists for another product
            $skuCheck = $conn->prepare("SELECT id, product_image FROM products WHERE store_id = ? AND sku = ? AND id != ?");
            $skuCheck->bind_param("isi", $storeId, $sku, $id);
            $skuCheck->execute();
            $skuResult = $skuCheck->get_result();
            if ($skuResult->num_rows > 0) {
                $_SESSION['error_message'] = 'SKU already exists for another product.';
            } else {
                // Get current product image
                $currentImageStmt = $conn->prepare("SELECT product_image FROM products WHERE id = ? AND store_id = ?");
                $currentImageStmt->bind_param("ii", $id, $storeId);
                $currentImageStmt->execute();
                $currentImageResult = $currentImageStmt->get_result();
                $currentProduct = $currentImageResult->fetch_assoc();
                $oldImage = $currentProduct['product_image'] ?? null;
                $currentImageStmt->close();
                
                // Handle image upload if provided
                $productImage = $oldImage; // Keep old image by default
                if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
                    $uploadResult = handleImageUpload($_FILES['product_image'], $id);
                    if ($uploadResult['success']) {
                        // Delete old image if exists
                        if ($oldImage) {
                            deleteProductImage($oldImage);
                        }
                        $productImage = $uploadResult['filename'];
                    } else {
                        $_SESSION['error_message'] = $uploadResult['message'];
                        header('Location: products.php?action=edit&id=' . $id);
                        exit();
                    }
                } elseif (isset($_POST['delete_image']) && $_POST['delete_image'] == '1') {
                    // Delete image if requested
                    if ($oldImage) {
                        deleteProductImage($oldImage);
                    }
                    $productImage = null;
                }
                
                $stmt = $conn->prepare("UPDATE products SET category_id = ?, name = ?, sku = ?, barcode = ?, description = ?, product_image = ?, cost_price = ?, selling_price = ?, tax_rate = ?, stock_quantity = ?, low_stock_threshold = ?, status = ? WHERE id = ? AND store_id = ?");
                $stmt->bind_param("isssssdddiissi", $categoryId, $name, $sku, $barcode, $description, $productImage, $costPrice, $sellingPrice, $taxRate, $stockQuantity, $lowStockThreshold, $status, $id, $storeId);
                
                if ($stmt->execute()) {
                    // Log inventory change if quantity changed
                    if ($stockQuantity != $oldProduct['stock_quantity']) {
                        $quantityChange = $stockQuantity - $oldProduct['stock_quantity'];
                        $logStmt = $conn->prepare("INSERT INTO inventory_logs (store_id, product_id, user_id, transaction_type, quantity_change, previous_quantity, new_quantity, notes) VALUES (?, ?, ?, 'adjustment', ?, ?, ?, ?)");
                        $notes = "Manual stock adjustment";
                        $logStmt->bind_param("iiiiss", $storeId, $id, $_SESSION['user_id'], $quantityChange, $oldProduct['stock_quantity'], $stockQuantity, $notes);
                        $logStmt->execute();
                        $logStmt->close();
                    }
                    
                    $_SESSION['success_message'] = 'Product updated successfully.';
                    header('Location: products.php');
                    exit();
                } else {
                    // Delete new image if update failed
                    if ($productImage && $productImage != $oldImage) {
                        deleteProductImage($productImage);
                    }
                    $_SESSION['error_message'] = 'Error updating product: ' . $stmt->error;
                }
                $stmt->close();
            }
            $skuCheck->close();
        }
        $checkStmt->close();
    } elseif (isset($_POST['delete_product'])) {
        $id = intval($_POST['id']);
        
        // Verify product belongs to store
        $checkStmt = $conn->prepare("SELECT id, product_image FROM products WHERE id = ? AND store_id = ?");
        $checkStmt->bind_param("ii", $id, $storeId);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        if ($result->num_rows == 0) {
            $_SESSION['error_message'] = 'Product not found or access denied.';
        } else {
            $productToDelete = $result->fetch_assoc();
            
            // Check if product has sales
            $salesCheck = $conn->prepare("SELECT COUNT(*) as count FROM sale_items WHERE product_id = ?");
            $salesCheck->bind_param("i", $id);
            $salesCheck->execute();
            $salesResult = $salesCheck->get_result();
            $salesCount = $salesResult->fetch_assoc()['count'];
            $salesCheck->close();
            
            if ($salesCount > 0) {
                $_SESSION['error_message'] = 'Cannot delete product that has been sold. Product has ' . $salesCount . ' sale(s).';
            } else {
                // Delete product image if exists
                if (!empty($productToDelete['product_image'])) {
                    deleteProductImage($productToDelete['product_image']);
                }
                
                // Delete product
                $deleteStmt = $conn->prepare("DELETE FROM products WHERE id = ? AND store_id = ?");
                $deleteStmt->bind_param("ii", $id, $storeId);
                
                if ($deleteStmt->execute()) {
                    $_SESSION['success_message'] = 'Product deleted successfully.';
                } else {
                    $_SESSION['error_message'] = 'Error deleting product: ' . $deleteStmt->error;
                }
                $deleteStmt->close();
            }
        }
        $checkStmt->close();
        
        header('Location: products.php');
        exit();
    }
}

// Get product for editing
$product = null;
if ($action == 'edit' && $productId) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND store_id = ?");
    $stmt->bind_param("ii", $productId, $storeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

// Get all categories for dropdown
$categoriesResult = $conn->prepare("SELECT id, name FROM categories WHERE store_id = ? AND status = 'active' ORDER BY name");
$categoriesResult->bind_param("i", $storeId);
$categoriesResult->execute();
$categories = $categoriesResult->get_result()->fetch_all(MYSQLI_ASSOC);
$categoriesResult->close();

// Get all products
$stmt = $conn->prepare("SELECT p.*, c.name as category_name 
                       FROM products p 
                       INNER JOIN categories c ON p.category_id = c.id 
                       WHERE p.store_id = ? 
                       ORDER BY p.name");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$products = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php if ($action == 'create' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-box"></i> <?php echo $action == 'edit' ? 'Edit' : 'Create'; ?> Product</h2>
            <a href="products.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $product['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><i class="fas fa-box"></i> Product Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo htmlspecialchars($product['name'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="category_id"><i class="fas fa-tags"></i> Category *</label>
                    <select id="category_id" name="category_id" required>
                        <option value="">Select Category</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" 
                                    <?php echo ($product && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="sku"><i class="fas fa-barcode"></i> SKU *</label>
                    <input type="text" id="sku" name="sku" required 
                           value="<?php echo htmlspecialchars($product['sku'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="barcode"><i class="fas fa-qrcode"></i> Barcode</label>
                    <input type="text" id="barcode" name="barcode" 
                           value="<?php echo htmlspecialchars($product['barcode'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="product_image"><i class="fas fa-image"></i> Product Image</label>
                <input type="file" id="product_image" name="product_image" accept="image/jpeg,image/jpg,image/png,image/webp" onchange="previewImage(this)">
                <small style="color: var(--text-light); display: block; margin-top: 5px;">Max size: 2MB. Formats: JPG, PNG, WEBP</small>
                <?php if ($action == 'edit'): ?>
                    <small style="color: var(--info-color); display: block; margin-top: 5px;">
                        <i class="fas fa-info-circle"></i> Upload a new image to replace the current one.
                    </small>
                <?php endif; ?>
                
                <?php if ($action == 'edit' && !empty($product['product_image'])): ?>
                    <div id="current-image-preview" style="margin-top: 15px; padding: 15px; background: var(--light-color); border-radius: 5px; border: 1px solid var(--border-color);">
                        <div style="font-weight: 600; margin-bottom: 10px; color: var(--dark-color);">
                            <i class="fas fa-image"></i> Current Image:
                        </div>
                        <img src="<?php echo getProductImageUrl($product['product_image']); ?>" 
                             alt="Current Image" 
                             style="max-width: 200px; max-height: 200px; border: 1px solid var(--border-color); border-radius: 5px; padding: 5px; display: block; background: white; margin-bottom: 10px;">
                        <div style="margin-top: 10px;">
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 8px; background: white; border-radius: 5px; border: 1px solid var(--border-color);">
                                <input type="checkbox" name="delete_image" value="1" id="delete_image" onchange="toggleImageInput()">
                                <span style="color: var(--danger-color); font-weight: 500;">
                                    <i class="fas fa-trash"></i> Delete current image
                                </span>
                            </label>
                            <small style="color: var(--text-light); display: block; margin-top: 5px;">
                                Check this to remove the image. You can also upload a new image to replace it.
                            </small>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div id="image-preview" style="margin-top: 15px; display: none; padding: 15px; background: var(--light-color); border-radius: 5px; border: 1px solid var(--border-color);">
                    <div style="font-weight: 600; margin-bottom: 10px; color: var(--dark-color);">
                        <i class="fas fa-eye"></i> New Image Preview:
                    </div>
                    <img id="preview-img" src="" alt="Preview" style="max-width: 200px; max-height: 200px; border: 1px solid var(--border-color); border-radius: 5px; padding: 5px; display: block; background: white;">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="cost_price"><i class="fas fa-dollar-sign"></i> Cost Price</label>
                    <input type="number" id="cost_price" name="cost_price" step="0.01" min="0" 
                           value="<?php echo $product['cost_price'] ?? '0.00'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="selling_price"><i class="fas fa-tag"></i> Selling Price *</label>
                    <input type="number" id="selling_price" name="selling_price" step="0.01" min="0.01" required 
                           value="<?php echo $product['selling_price'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="tax_rate"><i class="fas fa-percent"></i> Tax Rate (%)</label>
                    <input type="number" id="tax_rate" name="tax_rate" step="0.01" min="0" max="100" 
                           value="<?php echo $product['tax_rate'] ?? '0.00'; ?>">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="stock_quantity"><i class="fas fa-warehouse"></i> Stock Quantity</label>
                    <input type="number" id="stock_quantity" name="stock_quantity" min="0" 
                           value="<?php echo $product['stock_quantity'] ?? '0'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="low_stock_threshold"><i class="fas fa-exclamation-triangle"></i> Low Stock Threshold</label>
                    <input type="number" id="low_stock_threshold" name="low_stock_threshold" min="0" 
                           value="<?php echo $product['low_stock_threshold'] ?? '10'; ?>">
                </div>
                
                <div class="form-group">
                    <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo ($product['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($product['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" name="<?php echo $action == 'edit' ? 'update_product' : 'create_product'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'edit' ? 'Update' : 'Create'; ?> Product
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="page-header">
        <h1><i class="fas fa-box"></i> Product Management</h1>
        <a href="products.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Product
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>All Products</h2>
            <input type="text" id="search-products" placeholder="Search products..." style="width: 300px; padding: 8px;">
        </div>
        
        <div class="table-container">
            <table id="products-table">
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>ID</th>
                        <th>Product Name</th>
                        <th>SKU</th>
                        <th>Category</th>
                        <th>Cost Price</th>
                        <th>Selling Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No products found. Create your first product to get started.</p>
                                <a href="products.php?action=create" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-plus"></i> Create Product
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $prod): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo getProductImageUrl($prod['product_image'] ?? null); ?>" 
                                         alt="<?php echo htmlspecialchars($prod['name']); ?>"
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; border: 1px solid var(--border-color);"
                                         onerror="this.src='<?php echo BASE_URL; ?>assets/images/no-image.svg'">
                                </td>
                                <td><?php echo $prod['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($prod['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($prod['sku']); ?></td>
                                <td><?php echo htmlspecialchars($prod['category_name']); ?></td>
                                <td><?php echo formatCurrency($prod['cost_price']); ?></td>
                                <td><?php echo formatCurrency($prod['selling_price']); ?></td>
                                <td>
                                    <?php if ($prod['stock_quantity'] <= $prod['low_stock_threshold']): ?>
                                        <span class="badge badge-danger"><?php echo $prod['stock_quantity']; ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-success"><?php echo $prod['stock_quantity']; ?></span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $prod['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($prod['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 5px;">
                                        <a href="products.php?action=edit&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirmDeleteProduct(event, '<?php echo htmlspecialchars($prod['name'], ENT_QUOTES); ?>');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                            <button type="submit" name="delete_product" class="btn btn-sm btn-danger" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- SweetAlert2 JS - Load before inline scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Ensure SweetAlert2 is loaded before using it
if (typeof Swal === 'undefined') {
    console.error('SweetAlert2 failed to load');
}

// Image preview function
function previewImage(input) {
    const preview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    const currentImage = document.getElementById('current-image-preview');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validate file size (2MB)
        if (file.size > 2 * 1024 * 1024) {
            Swal.fire({
                icon: 'error',
                title: 'File Too Large',
                text: 'File size exceeds 2MB limit. Please choose a smaller image.',
                confirmButtonColor: '#2563eb'
            });
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        // Validate file type
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            Swal.fire({
                icon: 'error',
                title: 'Invalid File Type',
                text: 'Please select a JPG, PNG, or WEBP image file.',
                confirmButtonColor: '#2563eb'
            });
            input.value = '';
            preview.style.display = 'none';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
            if (currentImage) {
                currentImage.style.display = 'none';
            }
        };
        reader.readAsDataURL(file);
    } else {
        preview.style.display = 'none';
        if (currentImage) {
            currentImage.style.display = 'block';
        }
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-products');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const table = document.getElementById('products-table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    }
    
    // Handle delete image checkbox
    const deleteImageCheckbox = document.getElementById('delete_image');
    if (deleteImageCheckbox) {
        deleteImageCheckbox.addEventListener('change', function() {
            const imageInput = document.getElementById('product_image');
            if (this.checked) {
                imageInput.disabled = true;
            } else {
                imageInput.disabled = false;
            }
        });
    }
});

// Confirm delete product with SweetAlert2
function confirmDeleteProduct(event, productName) {
    event.preventDefault();
    event.stopPropagation();
    
    // Get the form element
    let form = event.target;
    while (form && form.tagName !== 'FORM') {
        form = form.parentElement;
    }
    
    if (!form) {
        console.error('Form not found');
        return false;
    }
    
    // Check if SweetAlert2 is loaded
    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 not loaded');
        // Fallback to native confirm
        if (confirm('Are you sure you want to delete ' + productName + '?')) {
            form.submit();
        }
        return false;
    }
    
    Swal.fire({
        title: 'Delete Product?',
        html: 'Are you sure you want to delete <strong>' + productName + '</strong>?<br><br>This action cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#64748b',
        confirmButtonText: '<i class="fas fa-trash"></i> Yes, Delete',
        cancelButtonText: '<i class="fas fa-times"></i> Cancel',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            form.submit();
        }
    });
    
    return false;
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

