<?php
/**
 * Category Management (Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$pageTitle = 'Category Management';
// Add SweetAlert2 CSS for admin pages
$additionalStyles = ['https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'];
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$storeId = getCurrentStoreId();
$action = $_GET['action'] ?? 'list';
$categoryId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
        header('Location: categories.php');
        exit();
    }
    
    if (isset($_POST['create_category'])) {
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            $_SESSION['error_message'] = 'Category name is required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (store_id, name, description, status) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $storeId, $name, $description, $status);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Category created successfully.';
                header('Location: categories.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Error creating category: ' . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_category'])) {
        $id = intval($_POST['id']);
        $name = sanitizeInput($_POST['name']);
        $description = sanitizeInput($_POST['description'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        // Verify category belongs to store
        $checkStmt = $conn->prepare("SELECT id FROM categories WHERE id = ? AND store_id = ?");
        $checkStmt->bind_param("ii", $id, $storeId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->num_rows == 0) {
            $_SESSION['error_message'] = 'Category not found or access denied.';
        } else {
            $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ?, status = ? WHERE id = ? AND store_id = ?");
            $stmt->bind_param("sssii", $name, $description, $status, $id, $storeId);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Category updated successfully.';
                header('Location: categories.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Error updating category: ' . $stmt->error;
            }
            $stmt->close();
        }
        $checkStmt->close();
    } elseif (isset($_POST['delete_category'])) {
        $id = intval($_POST['id']);
        
        // Check if category has products
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count FROM products WHERE category_id = ?");
        $checkStmt->bind_param("i", $id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $productCount = $result->fetch_assoc()['count'];
        $checkStmt->close();
        
        if ($productCount > 0) {
            $_SESSION['error_message'] = 'Cannot delete category with existing products.';
        } else {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ? AND store_id = ?");
            $stmt->bind_param("ii", $id, $storeId);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Category deleted successfully.';
                header('Location: categories.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Error deleting category: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get category for editing
$category = null;
if ($action == 'edit' && $categoryId) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ? AND store_id = ?");
    $stmt->bind_param("ii", $categoryId, $storeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $category = $result->fetch_assoc();
    $stmt->close();
}

// Get all categories
$stmt = $conn->prepare("SELECT c.*, 
                       (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count
                       FROM categories c 
                       WHERE c.store_id = ? 
                       ORDER BY c.name");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$categories = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php if ($action == 'create' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-tags"></i> <?php echo $action == 'edit' ? 'Edit' : 'Create'; ?> Category</h2>
            <a href="categories.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
            <?php endif; ?>
            
            <div class="form-group">
                <label for="name"><i class="fas fa-tag"></i> Category Name *</label>
                <input type="text" id="name" name="name" required 
                       value="<?php echo htmlspecialchars($category['name'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="description"><i class="fas fa-align-left"></i> Description</label>
                <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($category['description'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-group">
                <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                <select id="status" name="status" required>
                    <option value="active" <?php echo ($category['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($category['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" name="<?php echo $action == 'edit' ? 'update_category' : 'create_category'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'edit' ? 'Update' : 'Create'; ?> Category
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="page-header">
        <h1><i class="fas fa-tags"></i> Category Management</h1>
        <a href="categories.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Category
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>All Categories</h2>
            <input type="text" id="search-categories" placeholder="Search categories..." style="width: 300px; padding: 8px;">
        </div>
        
        <div class="table-container">
            <table id="categories-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Category Name</th>
                        <th>Description</th>
                        <th>Products</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No categories found. Create your first category to get started.</p>
                                <a href="categories.php?action=create" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-plus"></i> Create Category
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?php echo $cat['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['description'] ?? 'N/A'); ?></td>
                                <td><?php echo $cat['product_count']; ?></td>
                                <td>
                                    <span class="badge <?php echo $cat['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($cat['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($cat['created_at']); ?></td>
                                <td>
                                    <a href="categories.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($cat['product_count'] == 0): ?>
                                        <form method="POST" action="" style="display: inline;" onsubmit="return confirmDeleteCategory(event, '<?php echo htmlspecialchars($cat['name'], ENT_QUOTES); ?>');">
                                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                            <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
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

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-categories');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const table = document.getElementById('categories-table');
            if (table) {
                const rows = table.querySelectorAll('tbody tr');
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            }
        });
    }
});

// Confirm delete category with SweetAlert2
function confirmDeleteCategory(event, categoryName) {
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
        if (confirm('Are you sure you want to delete ' + categoryName + '?')) {
            form.submit();
        }
        return false;
    }
    
    Swal.fire({
        title: 'Delete Category?',
        html: 'Are you sure you want to delete <strong>' + categoryName + '</strong>?<br><br>This action cannot be undone.',
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

