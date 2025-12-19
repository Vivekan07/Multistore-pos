<?php
/**
 * Main Configuration File
 * Advanced Point Of Sale
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once __DIR__ . '/database.php';

// Application settings
define('APP_NAME', 'Point Of Sale');
define('APP_VERSION', '1.0.0');
define('BASE_URL', 'http://localhost/Pos/');

// Session settings
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds
define('SESSION_NAME', 'POS_SESSION');

// Security settings
define('CSRF_TOKEN_NAME', 'csrf_token');
define('PASSWORD_MIN_LENGTH', 6);

// Pagination
define('ITEMS_PER_PAGE', 20);

// Date format
define('DATE_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd M Y, h:i A');

// File upload settings
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5242880); // 5MB

// Error reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Timezone
date_default_timezone_set('UTC');

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role_id']);
}

// Helper function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

// Helper function to check user role
function hasRole($roleName) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT r.name FROM roles r 
                           INNER JOIN users u ON u.role_id = r.id 
                           WHERE u.id = ? AND r.name = ?");
    $stmt->bind_param("is", $_SESSION['user_id'], $roleName);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
    
    return $result->num_rows > 0;
}

// Helper function to require specific role
function requireRole($roleName) {
    requireLogin();
    if (!hasRole($roleName)) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit();
    }
}

// Helper function to get current user's store ID
function getCurrentStoreId() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return $_SESSION['store_id'] ?? null;
}

// Helper function to require store access
function requireStoreAccess($storeId) {
    requireLogin();
    
    // Super admin can access all stores
    if (hasRole('super_admin')) {
        return true;
    }
    
    // Admin and Cashier can only access their own store
    $currentStoreId = getCurrentStoreId();
    if ($currentStoreId != $storeId) {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit();
    }
    
    return true;
}

// CSRF Token functions
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function validateCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && 
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Format currency
function formatCurrency($amount) {
    return 'LKR ' . number_format($amount, 2, '.', ',');
}

// Format date
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    return date($format, strtotime($date));
}

