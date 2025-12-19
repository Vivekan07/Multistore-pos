<?php
/**
 * Store Management (Super Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('super_admin');

$pageTitle = 'Store Management';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$storeId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
        header('Location: stores.php');
        exit();
    }
    
    if (isset($_POST['create_store'])) {
        $name = sanitizeInput($_POST['name']);
        $address = sanitizeInput($_POST['address'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        if (empty($name)) {
            $_SESSION['error_message'] = 'Store name is required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO stores (name, address, phone, email, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $address, $phone, $email, $status);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Store created successfully.';
                header('Location: stores.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Error creating store: ' . $stmt->error;
            }
            $stmt->close();
        }
    } elseif (isset($_POST['update_store'])) {
        $id = intval($_POST['id']);
        $name = sanitizeInput($_POST['name']);
        $address = sanitizeInput($_POST['address'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        $stmt = $conn->prepare("UPDATE stores SET name = ?, address = ?, phone = ?, email = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $address, $phone, $email, $status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Store updated successfully.';
            header('Location: stores.php');
            exit();
        } else {
            $_SESSION['error_message'] = 'Error updating store: ' . $stmt->error;
        }
        $stmt->close();
    }
}

// Get store for editing
$store = null;
if ($action == 'edit' && $storeId) {
    $stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
    $stmt->bind_param("i", $storeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $store = $result->fetch_assoc();
    $stmt->close();
}

// Get all stores
$result = $conn->query("SELECT s.*, 
                       (SELECT COUNT(*) FROM users WHERE store_id = s.id AND role_id = 2) as admin_count,
                       (SELECT COUNT(*) FROM users WHERE store_id = s.id AND role_id = 3) as cashier_count,
                       (SELECT COUNT(*) FROM sales WHERE store_id = s.id) as sales_count
                       FROM stores s 
                       ORDER BY s.created_at DESC");
$stores = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php if ($action == 'create' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-store"></i> <?php echo $action == 'edit' ? 'Edit' : 'Create'; ?> Store</h2>
            <a href="stores.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $store['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="name"><i class="fas fa-store"></i> Store Name *</label>
                    <input type="text" id="name" name="name" required 
                           value="<?php echo $store['name'] ?? ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                    <select id="status" name="status" required>
                        <option value="active" <?php echo ($store['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($store['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label for="address"><i class="fas fa-map-marker-alt"></i> Address</label>
                <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($store['address'] ?? ''); ?></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($store['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($store['email'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <button type="submit" name="<?php echo $action == 'edit' ? 'update_store' : 'create_store'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'edit' ? 'Update' : 'Create'; ?> Store
                </button>
            </div>
        </form>
    </div>
<?php else: ?>
    <div class="page-header">
        <h1><i class="fas fa-store"></i> Store Management</h1>
        <a href="stores.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Store
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>All Stores</h2>
            <input type="text" id="search-stores" placeholder="Search stores..." class="form-group" style="width: 300px; padding: 8px;">
        </div>
        
        <div class="table-container">
            <table id="stores-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Store Name</th>
                        <th>Address</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Admins</th>
                        <th>Cashiers</th>
                        <th>Sales</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($stores)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No stores found. Create your first store to get started.</p>
                                <a href="stores.php?action=create" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-plus"></i> Create Store
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($stores as $store): ?>
                            <tr>
                                <td><?php echo $store['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($store['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($store['address'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php if ($store['phone']): ?>
                                        <div><i class="fas fa-phone"></i> <?php echo htmlspecialchars($store['phone']); ?></div>
                                    <?php endif; ?>
                                    <?php if ($store['email']): ?>
                                        <div><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($store['email']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $store['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($store['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $store['admin_count']; ?></td>
                                <td><?php echo $store['cashier_count']; ?></td>
                                <td><?php echo $store['sales_count']; ?></td>
                                <td><?php echo formatDate($store['created_at']); ?></td>
                                <td>
                                    <a href="stores.php?action=edit&id=<?php echo $store['id']; ?>" class="btn btn-sm btn-secondary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="admins.php?store_id=<?php echo $store['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-user-shield"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-stores');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const table = document.getElementById('stores-table');
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
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

