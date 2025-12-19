<?php
/**
 * Settings (Admin Only)
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';
requireRole('admin');

$pageTitle = 'Settings';
require_once __DIR__ . '/../includes/header.php';

$conn = getDBConnection();
$storeId = getCurrentStoreId();

// Get store info
$stmt = $conn->prepare("SELECT * FROM stores WHERE id = ?");
$stmt->bind_param("i", $storeId);
$stmt->execute();
$result = $stmt->get_result();
$store = $result->fetch_assoc();
$stmt->close();
?>

<div class="page-header">
    <h1><i class="fas fa-cog"></i> Settings</h1>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-store"></i> Store Information</h2>
    </div>
    
    <div class="form-group">
        <label><i class="fas fa-store"></i> Store Name</label>
        <input type="text" value="<?php echo htmlspecialchars($store['name']); ?>" readonly>
        <small style="color: var(--text-light);">Contact Super Admin to change store name</small>
    </div>
    
    <div class="form-group">
        <label><i class="fas fa-map-marker-alt"></i> Address</label>
        <textarea readonly rows="3"><?php echo htmlspecialchars($store['address'] ?? 'N/A'); ?></textarea>
    </div>
    
    <div class="form-row">
        <div class="form-group">
            <label><i class="fas fa-phone"></i> Phone</label>
            <input type="text" value="<?php echo htmlspecialchars($store['phone'] ?? 'N/A'); ?>" readonly>
        </div>
        
        <div class="form-group">
            <label><i class="fas fa-envelope"></i> Email</label>
            <input type="text" value="<?php echo htmlspecialchars($store['email'] ?? 'N/A'); ?>" readonly>
        </div>
    </div>
    
    <div class="form-group">
        <label><i class="fas fa-toggle-on"></i> Status</label>
        <input type="text" value="<?php echo ucfirst($store['status']); ?>" readonly>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-info-circle"></i> System Information</h2>
    </div>
    
    <div class="form-group">
        <label>Application Name</label>
        <input type="text" value="<?php echo APP_NAME; ?>" readonly>
    </div>
    
    <div class="form-group">
        <label>Version</label>
        <input type="text" value="<?php echo APP_VERSION; ?>" readonly>
    </div>
    
    <div class="form-group">
        <label>PHP Version</label>
        <input type="text" value="<?php echo PHP_VERSION; ?>" readonly>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

