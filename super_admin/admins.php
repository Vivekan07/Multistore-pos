<?php
/**
 * Admin Management (Super Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('super_admin');

$pageTitle = 'Admin Management';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$action = $_GET['action'] ?? 'list';
$adminId = $_GET['id'] ?? null;
$storeId = $_GET['store_id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
        header('Location: admins.php');
        exit();
    }
    
    if (isset($_POST['create_admin'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $storeId = intval($_POST['store_id']);
        $status = $_POST['status'] ?? 'active';
        
        // Validate
        if (empty($username) || empty($password) || empty($fullName) || empty($storeId)) {
            $_SESSION['error_message'] = 'Please fill all required fields.';
        } elseif (strlen($password) < PASSWORD_MIN_LENGTH) {
            $_SESSION['error_message'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } else {
            // Check if username exists
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            if ($checkStmt->get_result()->num_rows > 0) {
                $_SESSION['error_message'] = 'Username already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $roleId = 2; // Admin role
                
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role_id, store_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssiis", $username, $hashedPassword, $fullName, $email, $phone, $roleId, $storeId, $status);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = 'Admin created successfully.';
                    header('Location: admins.php');
                    exit();
                } else {
                    $_SESSION['error_message'] = 'Error creating admin: ' . $stmt->error;
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    } elseif (isset($_POST['update_admin'])) {
        $id = intval($_POST['id']);
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, status = ? WHERE id = ? AND role_id = 2");
        $stmt->bind_param("ssssi", $fullName, $email, $phone, $status, $id);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Admin updated successfully.';
            header('Location: admins.php');
            exit();
        } else {
            $_SESSION['error_message'] = 'Error updating admin: ' . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['reset_password'])) {
        $id = intval($_POST['id']);
        $newPassword = $_POST['new_password'];
        
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $_SESSION['error_message'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role_id = 2");
            $stmt->bind_param("si", $hashedPassword, $id);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Password reset successfully.';
                header('Location: admins.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Error resetting password: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get admin for editing
$admin = null;
if ($action == 'edit' && $adminId) {
    $stmt = $conn->prepare("SELECT u.*, s.name as store_name FROM users u 
                           LEFT JOIN stores s ON u.store_id = s.id 
                           WHERE u.id = ? AND u.role_id = 2");
    $stmt->bind_param("i", $adminId);
    $stmt->execute();
    $result = $stmt->get_result();
    $admin = $result->fetch_assoc();
    $stmt->close();
}

// Get all stores for dropdown
$storesResult = $conn->query("SELECT id, name FROM stores WHERE status = 'active' ORDER BY name");
$stores = $storesResult->fetch_all(MYSQLI_ASSOC);

// Get all admins
$query = "SELECT u.*, s.name as store_name 
          FROM users u 
          LEFT JOIN stores s ON u.store_id = s.id 
          WHERE u.role_id = 2";
if ($storeId) {
    $query .= " AND u.store_id = " . intval($storeId);
}
$query .= " ORDER BY u.created_at DESC";

$result = $conn->query($query);
$admins = $result->fetch_all(MYSQLI_ASSOC);
?>

<?php if ($action == 'create' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-shield"></i> <?php echo $action == 'edit' ? 'Edit' : 'Create'; ?> Admin</h2>
            <a href="admins.php<?php echo $storeId ? '?store_id=' . $storeId : ''; ?>" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username <?php echo $action == 'create' ? '*' : ''; ?></label>
                    <input type="text" id="username" name="username" 
                           <?php echo $action == 'create' ? 'required' : 'readonly'; ?>
                           value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>">
                    <?php if ($action == 'edit'): ?>
                        <small style="color: var(--text-light);">Username cannot be changed</small>
                    <?php endif; ?>
                </div>
                
                <?php if ($action == 'create'): ?>
                    <div class="form-group">
                        <label for="password"><i class="fas fa-lock"></i> Password *</label>
                        <div class="password-wrapper">
                            <input type="password" id="password" name="password" required 
                                   minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                            <button type="button" class="password-toggle" onclick="togglePassword('password'); return false;" title="Show password">
                                <i class="fas fa-eye" id="password-toggle-icon"></i>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="full_name"><i class="fas fa-id-card"></i> Full Name *</label>
                <input type="text" id="full_name" name="full_name" required 
                       value="<?php echo htmlspecialchars($admin['full_name'] ?? ''); ?>">
            </div>
            
            <?php if ($action == 'create'): ?>
                <div class="form-group">
                    <label for="store_id"><i class="fas fa-store"></i> Store *</label>
                    <select id="store_id" name="store_id" required>
                        <option value="">Select Store</option>
                        <?php foreach ($stores as $store): ?>
                            <option value="<?php echo $store['id']; ?>" 
                                    <?php echo ($storeId && $store['id'] == $storeId) || ($admin && $admin['store_id'] == $store['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($store['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label><i class="fas fa-store"></i> Store</label>
                    <input type="text" value="<?php echo htmlspecialchars($admin['store_name'] ?? 'N/A'); ?>" readonly>
                </div>
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($admin['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($admin['phone'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                <select id="status" name="status" required>
                    <option value="active" <?php echo ($admin['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($admin['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" name="<?php echo $action == 'edit' ? 'update_admin' : 'create_admin'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'edit' ? 'Update' : 'Create'; ?> Admin
                </button>
            </div>
        </form>
    </div>
    
    <?php if ($action == 'edit'): ?>
        <div class="card" style="margin-top: 20px;">
            <div class="card-header">
                <h2><i class="fas fa-key"></i> Reset Password</h2>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="id" value="<?php echo $admin['id']; ?>">
                
                <div class="form-group">
                    <label for="new_password"><i class="fas fa-lock"></i> New Password *</label>
                    <div class="password-wrapper">
                        <input type="password" id="new_password" name="new_password" required 
                               minlength="<?php echo PASSWORD_MIN_LENGTH; ?>">
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password'); return false;" title="Show password">
                            <i class="fas fa-eye" id="new_password-toggle-icon"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" name="reset_password" class="btn btn-warning">
                        <i class="fas fa-key"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>
<?php else: ?>
    <div class="page-header">
        <h1><i class="fas fa-user-shield"></i> Admin Management</h1>
        <a href="admins.php?action=create<?php echo $storeId ? '&store_id=' . $storeId : ''; ?>" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Admin
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>All Admins</h2>
            <input type="text" id="search-admins" placeholder="Search admins..." style="width: 300px; padding: 8px;">
        </div>
        
        <div class="table-container">
            <table id="admins-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Store</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No admins found.</p>
                                <a href="admins.php?action=create" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-plus"></i> Create Admin
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($admins as $admin): ?>
                            <tr>
                                <td><?php echo $admin['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($admin['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($admin['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($admin['store_name'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($admin['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($admin['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $admin['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($admin['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo $admin['last_login'] ? formatDate($admin['last_login']) : 'Never'; ?></td>
                                <td><?php echo formatDate($admin['created_at']); ?></td>
                                <td>
                                    <a href="admins.php?action=edit&id=<?php echo $admin['id']; ?>" class="btn btn-sm btn-secondary">
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
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-admins');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const table = document.getElementById('admins-table');
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

