<?php
/**
 * Login Page
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/config/config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = $_SESSION['role_name'] ?? '';
    if ($role == 'super_admin') {
        header('Location: ' . BASE_URL . 'super_admin/dashboard.php');
    } elseif ($role == 'admin') {
        header('Location: ' . BASE_URL . 'admin/dashboard.php');
    } elseif ($role == 'cashier') {
        header('Location: ' . BASE_URL . 'cashier/pos.php');
    } else {
        header('Location: ' . BASE_URL . 'dashboard.php');
    }
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $conn = getDBConnection();
        $stmt = $conn->prepare("SELECT u.*, r.name as role_name, s.id as store_id, s.name as store_name, s.status as store_status 
                               FROM users u 
                               INNER JOIN roles r ON u.role_id = r.id 
                               LEFT JOIN stores s ON u.store_id = s.id 
                               WHERE u.username = ? AND u.status = 'active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if store is active (for admin and cashier)
                if ($user['role_name'] != 'super_admin' && $user['store_status'] != 'active') {
                    $error = 'Your store account is currently inactive. Please contact the super administrator.';
                } else {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['role_name'] = $user['role_name'];
                    $_SESSION['store_id'] = $user['store_id'];
                    $_SESSION['store_name'] = $user['store_name'];
                    $_SESSION['last_activity'] = time();
                    
                    // Update last login
                    $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $updateStmt->bind_param("i", $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // Redirect based on role
                    if ($user['role_name'] == 'super_admin') {
                        header('Location: ' . BASE_URL . 'super_admin/dashboard.php');
                    } elseif ($user['role_name'] == 'admin') {
                        header('Location: ' . BASE_URL . 'admin/dashboard.php');
                    } elseif ($user['role_name'] == 'cashier') {
                        header('Location: ' . BASE_URL . 'cashier/pos.php');
                    } else {
                        header('Location: ' . BASE_URL . 'dashboard.php');
                    }
                    exit();
                }
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <i class="fas fa-cash-register"></i>
                <h1><?php echo APP_NAME; ?></h1>
                <p>Sign in to your account</p>
            </div>
            
            
            <form method="POST" action="" class="login-form">
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" id="username" name="username" required autofocus 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="password-wrapper">
                        <input type="password" id="password" name="password" required>
                        <button type="button" class="password-toggle" id="password-toggle-btn" title="Show password">
                            <i class="fas fa-eye" id="password-toggle-icon"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="login-footer">
                <p><small>Default Super Admin: vivekan / vivekan1409</small></p>
            </div>
        </div>
    </div>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Main JS -->
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    
    <script>
        // Password toggle for login page - Enhanced version
        (function() {
            function initPasswordToggle() {
                const passwordInput = document.getElementById('password');
                const toggleBtn = document.getElementById('password-toggle-btn');
                const toggleIcon = document.getElementById('password-toggle-icon');
                
                if (!passwordInput || !toggleBtn || !toggleIcon) {
                    console.error('Password toggle elements not found');
                    return;
                }
                
                // Remove any existing listeners
                const newToggleBtn = toggleBtn.cloneNode(true);
                toggleBtn.parentNode.replaceChild(newToggleBtn, toggleBtn);
                const newToggleIcon = newToggleBtn.querySelector('i');
                
                // Add click event
                newToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    e.stopImmediatePropagation();
                    
                    const input = document.getElementById('password');
                    if (!input) return;
                    
                    if (input.type === 'password') {
                        input.type = 'text';
                        newToggleIcon.classList.remove('fa-eye');
                        newToggleIcon.classList.add('fa-eye-slash');
                        newToggleBtn.setAttribute('title', 'Hide password');
                        newToggleBtn.setAttribute('aria-label', 'Hide password');
                    } else {
                        input.type = 'password';
                        newToggleIcon.classList.remove('fa-eye-slash');
                        newToggleIcon.classList.add('fa-eye');
                        newToggleBtn.setAttribute('title', 'Show password');
                        newToggleBtn.setAttribute('aria-label', 'Show password');
                    }
                    
                    // Add animation effect
                    newToggleIcon.style.transform = 'scale(0.8)';
                    setTimeout(() => {
                        newToggleIcon.style.transform = 'scale(1)';
                    }, 150);
                });
                
                // Also handle mousedown to ensure it works
                newToggleBtn.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });
            }
            
            // Try multiple times to ensure it works
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initPasswordToggle);
            } else {
                initPasswordToggle();
            }
            
            // Fallback - try again after a short delay
            setTimeout(initPasswordToggle, 100);
        })();
        
        // Show error message with SweetAlert2 if exists
        <?php if ($error): ?>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'error',
                title: 'Login Failed',
                text: '<?php echo htmlspecialchars($error); ?>',
                confirmButtonColor: '#2563eb',
                confirmButtonText: 'OK'
            });
        });
        <?php endif; ?>
    </script>
</body>
</html>

