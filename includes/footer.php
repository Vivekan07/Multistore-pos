    </main>

    <footer class="footer">
        <div class="footer-content">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            <p>Version <?php echo APP_VERSION; ?></p>
        </div>
    </footer>

    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <?php if (isset($additionalScripts)): ?>
        <?php foreach ($additionalScripts as $script): ?>
            <script src="<?php echo (strpos($script, 'http://') === 0 || strpos($script, 'https://') === 0) ? $script : BASE_URL . $script; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Load SweetAlert2 if not already loaded -->
    <script>
        if (typeof Swal === 'undefined') {
            document.write('<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"><\/script>');
        }
    </script>
    
    <script>
        // Convert session messages to SweetAlert2 if available
        document.addEventListener('DOMContentLoaded', function() {
            // Wait a bit for SweetAlert2 to load if it was just added
            setTimeout(function() {
                const successAlert = document.querySelector('.alert[data-swal="success"]');
                const errorAlert = document.querySelector('.alert[data-swal="error"]');
                
                if (typeof Swal !== 'undefined') {
                    if (successAlert) {
                        const message = successAlert.getAttribute('data-message');
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: message,
                            confirmButtonColor: '#10b981',
                            confirmButtonText: 'OK'
                        });
                        successAlert.remove();
                    }
                    
                    if (errorAlert) {
                        const message = errorAlert.getAttribute('data-message');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: message,
                            confirmButtonColor: '#dc2626',
                            confirmButtonText: 'OK'
                        });
                        errorAlert.remove();
                    }
                }
            }, 100);
        });
    </script>
</body>
</html>

