<?php
/**
 * Header Template
 * Includes navigation based on user role
 */

if (!isLoggedIn()) {
    header('Location: ' . BASE_URL . 'index.php');
    exit();
}

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT u.*, r.name as role_name, s.name as store_name 
                       FROM users u 
                       INNER JOIN roles r ON u.role_id = r.id 
                       LEFT JOIN stores s ON u.store_id = s.id 
                       WHERE u.id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if (isset($additionalStyles)): ?>
        <?php foreach ($additionalStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo $style; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
    <nav class="navbar">
        <div class="nav-container">
            <div class="nav-brand">
                <i class="fas fa-cash-register"></i>
                <span><?php echo APP_NAME; ?></span>
            </div>
            <div class="nav-menu">
                <div class="nav-user">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo htmlspecialchars($user['full_name']); ?></span>
                    <span class="role-badge role-<?php echo $user['role_name']; ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $user['role_name'])); ?>
                    </span>
                    <?php if ($user['store_name']): ?>
                        <span class="store-badge"><?php echo htmlspecialchars($user['store_name']); ?></span>
                    <?php endif; ?>
                </div>
                <a href="<?php echo BASE_URL; ?>logout.php" class="nav-link logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="sidebar">
        <ul class="sidebar-menu">
            <?php if (hasRole('super_admin')): ?>
                <li><a href="<?php echo BASE_URL; ?>super_admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>super_admin/stores.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'stores.php' ? 'active' : ''; ?>">
                    <i class="fas fa-store"></i> Stores
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>super_admin/admins.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i> Admins
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>super_admin/analytics.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i> Analytics
                </a></li>
            <?php elseif (hasRole('admin')): ?>
                <li><a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/products.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : ''; ?>">
                    <i class="fas fa-box"></i> Products
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/categories.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'categories.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i> Categories
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/cashiers.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'cashiers.php' ? 'active' : ''; ?>">
                    <i class="fas fa-user-tie"></i> Cashiers
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/inventory.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'inventory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-warehouse"></i> Inventory
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/reports.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i> Reports
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>admin/settings.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a></li>
            <?php elseif (hasRole('cashier')): ?>
                <li><a href="<?php echo BASE_URL; ?>cashier/pos.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'pos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shopping-cart"></i> POS
                </a></li>
                <li><a href="<?php echo BASE_URL; ?>cashier/sales.php" class="<?php echo basename($_SERVER['PHP_SELF']) == 'sales.php' ? 'active' : ''; ?>">
                    <i class="fas fa-receipt"></i> My Sales
                </a></li>
            <?php endif; ?>
        </ul>
    </div>

    <main class="main-content">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success" data-swal="success" data-message="<?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES); ?>" style="display: none;">
                <?php unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-error" data-swal="error" data-message="<?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES); ?>" style="display: none;">
                <?php unset($_SESSION['error_message']); ?>
            </div>
        <?php endif; ?>

