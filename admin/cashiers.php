<?php
/**
 * Cashier Management (Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$pageTitle = 'Cashier Management';
// Add SweetAlert2 CSS for admin pages
$additionalStyles = ['https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css'];
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$storeId = getCurrentStoreId();
$action = $_GET['action'] ?? 'list';
$cashierId = $_GET['id'] ?? null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!validateCSRFToken($token)) {
        $_SESSION['error_message'] = 'Invalid security token. Please try again.';
        header('Location: cashiers.php');
        exit();
    }
    
    if (isset($_POST['create_cashier'])) {
        $username = sanitizeInput($_POST['username']);
        $password = $_POST['password'];
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        // Validate
        if (empty($username) || empty($password) || empty($fullName)) {
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
                $roleId = 3; // Cashier role
                
                $stmt = $conn->prepare("INSERT INTO users (username, password, full_name, email, phone, role_id, store_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssiis", $username, $hashedPassword, $fullName, $email, $phone, $roleId, $storeId, $status);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = 'Cashier created successfully.';
                    header('Location: cashiers.php');
                    exit();
                } else {
                    $_SESSION['error_message'] = 'Error creating cashier: ' . $stmt->error;
                }
                $stmt->close();
            }
            $checkStmt->close();
        }
    } elseif (isset($_POST['update_cashier'])) {
        $id = intval($_POST['id']);
        $fullName = sanitizeInput($_POST['full_name']);
        $email = sanitizeInput($_POST['email'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $status = $_POST['status'] ?? 'active';
        
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, status = ? WHERE id = ? AND role_id = 3 AND store_id = ?");
        $stmt->bind_param("ssssii", $fullName, $email, $phone, $status, $id, $storeId);
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = 'Cashier updated successfully.';
            header('Location: cashiers.php');
            exit();
        } else {
            $_SESSION['error_message'] = 'Error updating cashier: ' . $stmt->error;
        }
        $stmt->close();
    } elseif (isset($_POST['reset_password'])) {
        $id = intval($_POST['id']);
        $newPassword = $_POST['new_password'];
        
        if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
            $_SESSION['error_message'] = 'Password must be at least ' . PASSWORD_MIN_LENGTH . ' characters long.';
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ? AND role_id = 3 AND store_id = ?");
            $stmt->bind_param("sii", $hashedPassword, $id, $storeId);
            
            if ($stmt->execute()) {
                $_SESSION['success_message'] = 'Password reset successfully.';
                header('Location: cashiers.php');
                exit();
            } else {
                $_SESSION['error_message'] = 'Error resetting password: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Get cashier for editing
$cashier = null;
if ($action == 'edit' && $cashierId) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role_id = 3 AND store_id = ?");
    $stmt->bind_param("ii", $cashierId, $storeId);
    $stmt->execute();
    $result = $stmt->get_result();
    $cashier = $result->fetch_assoc();
    $stmt->close();
}

// Get all cashiers
$stmt = $conn->prepare("SELECT u.*, 
                       (SELECT COUNT(*) FROM sales WHERE cashier_id = u.id) as sales_count,
                       (SELECT COALESCE(SUM(final_amount), 0) FROM sales WHERE cashier_id = u.id) as total_sales
                       FROM users u 
                       WHERE u.role_id = 3 AND u.store_id = ? 
                       ORDER BY u.created_at DESC");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$cashiers = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<?php if ($action == 'create' || $action == 'edit'): ?>
    <div class="card">
        <div class="card-header">
            <h2><i class="fas fa-user-tie"></i> <?php echo $action == 'edit' ? 'Edit' : 'Create'; ?> Cashier</h2>
            <a href="cashiers.php" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            <?php if ($action == 'edit'): ?>
                <input type="hidden" name="id" value="<?php echo $cashier['id']; ?>">
            <?php endif; ?>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Username <?php echo $action == 'create' ? '*' : ''; ?></label>
                    <input type="text" id="username" name="username" 
                           <?php echo $action == 'create' ? 'required' : 'readonly'; ?>
                           value="<?php echo htmlspecialchars($cashier['username'] ?? ''); ?>">
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
                       value="<?php echo htmlspecialchars($cashier['full_name'] ?? ''); ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="email"><i class="fas fa-envelope"></i> Email</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($cashier['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="phone"><i class="fas fa-phone"></i> Phone</label>
                    <input type="text" id="phone" name="phone" 
                           value="<?php echo htmlspecialchars($cashier['phone'] ?? ''); ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="status"><i class="fas fa-toggle-on"></i> Status</label>
                <select id="status" name="status" required>
                    <option value="active" <?php echo ($cashier['status'] ?? 'active') == 'active' ? 'selected' : ''; ?>>Active</option>
                    <option value="inactive" <?php echo ($cashier['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="submit" name="<?php echo $action == 'edit' ? 'update_cashier' : 'create_cashier'; ?>" class="btn btn-primary">
                    <i class="fas fa-save"></i> <?php echo $action == 'edit' ? 'Update' : 'Create'; ?> Cashier
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
                <input type="hidden" name="id" value="<?php echo $cashier['id']; ?>">
                
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
        <h1><i class="fas fa-user-tie"></i> Cashier Management</h1>
        <a href="cashiers.php?action=create" class="btn btn-primary">
            <i class="fas fa-plus"></i> Create New Cashier
        </a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>All Cashiers</h2>
            <input type="text" id="search-cashiers" placeholder="Search cashiers..." style="width: 300px; padding: 8px;">
        </div>
        
        <div class="table-container">
            <table id="cashiers-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Total Sales</th>
                        <th>Sales Count</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cashiers)): ?>
                        <tr>
                            <td colspan="10" style="text-align: center; padding: 40px;">
                                <i class="fas fa-inbox" style="font-size: 48px; color: #ccc; margin-bottom: 10px;"></i>
                                <p>No cashiers found. Create your first cashier to get started.</p>
                                <a href="cashiers.php?action=create" class="btn btn-primary" style="margin-top: 20px;">
                                    <i class="fas fa-plus"></i> Create Cashier
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($cashiers as $cash): ?>
                            <tr>
                                <td><?php echo $cash['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cash['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cash['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($cash['email'] ?? 'N/A'); ?></td>
                                <td><?php echo htmlspecialchars($cash['phone'] ?? 'N/A'); ?></td>
                                <td>
                                    <span class="badge <?php echo $cash['status'] == 'active' ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo ucfirst($cash['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatCurrency($cash['total_sales']); ?></td>
                                <td><?php echo $cash['sales_count']; ?></td>
                                <td><?php echo $cash['last_login'] ? formatDate($cash['last_login']) : 'Never'; ?></td>
                                <td>
                                    <a href="cashiers.php?action=edit&id=<?php echo $cash['id']; ?>" class="btn btn-sm btn-secondary">
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

<!-- SweetAlert2 JS - Load before inline scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Ensure SweetAlert2 is loaded before using it
if (typeof Swal === 'undefined') {
    console.error('SweetAlert2 failed to load');
}

document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('search-cashiers');
    if (searchInput) {
        searchInput.addEventListener('input', (e) => {
            const searchTerm = e.target.value.toLowerCase();
            const table = document.getElementById('cashiers-table');
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

