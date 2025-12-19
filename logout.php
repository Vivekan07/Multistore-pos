<?php
/**
 * Logout Page
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/config/config.php';

// Destroy session
session_destroy();

// Redirect to login
header('Location: ' . BASE_URL . 'index.php');
exit();

