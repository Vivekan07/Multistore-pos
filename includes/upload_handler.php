<?php
/**
 * Image Upload Handler
 * Advanced Point Of Sale
 */

require_once __DIR__ . '/../config/config.php';

// Note: This file can be included by any authenticated user
// Upload functions will check permissions individually

// Allowed MIME types
$allowedMimes = [
    'image/jpeg',
    'image/jpg',
    'image/png',
    'image/webp'
];

// Allowed file extensions
$allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];

// Max file size: 2MB
$maxFileSize = 2 * 1024 * 1024;

// Upload directory
$uploadDir = __DIR__ . '/../uploads/products/';

// Create upload directory if it doesn't exist
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

/**
 * Handle image upload
 * @param array $file $_FILES array element
 * @param int $productId Product ID for naming
 * @return array ['success' => bool, 'message' => string, 'filename' => string|null]
 */
function handleImageUpload($file, $productId = null) {
    global $allowedMimes, $allowedExtensions, $maxFileSize, $uploadDir;
    
    // Only admin can upload images
    if (!isLoggedIn() || !hasRole('admin')) {
        return [
            'success' => false,
            'message' => 'Unauthorized: Only admins can upload images'
        ];
    }
    
    // Check if file was uploaded
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return [
            'success' => false,
            'message' => 'File upload error: ' . ($file['error'] ?? 'No file uploaded')
        ];
    }
    
    // Validate file size
    if ($file['size'] > $maxFileSize) {
        return [
            'success' => false,
            'message' => 'File size exceeds 2MB limit'
        ];
    }
    
    // Get file info
    $fileInfo = pathinfo($file['name']);
    $extension = strtolower($fileInfo['extension'] ?? '');
    
    // Validate extension
    if (!in_array($extension, $allowedExtensions)) {
        return [
            'success' => false,
            'message' => 'Invalid file type. Allowed: JPG, PNG, WEBP'
        ];
    }
    
    // Validate MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedMimes)) {
        return [
            'success' => false,
            'message' => 'Invalid file MIME type. Allowed: JPG, PNG, WEBP'
        ];
    }
    
    // Additional security: Check if file is actually an image
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return [
            'success' => false,
            'message' => 'File is not a valid image'
        ];
    }
    
    // Generate unique filename: productID_timestamp.extension
    $timestamp = time();
    if ($productId) {
        $filename = $productId . '_' . $timestamp . '.' . $extension;
    } else {
        $filename = 'temp_' . $timestamp . '_' . uniqid() . '.' . $extension;
    }
    
    $targetPath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return [
            'success' => false,
            'message' => 'Failed to save uploaded file'
        ];
    }
    
    // Set proper permissions
    chmod($targetPath, 0644);
    
    return [
        'success' => true,
        'message' => 'Image uploaded successfully',
        'filename' => $filename
    ];
}

/**
 * Delete product image
 * @param string $filename Image filename
 * @return bool Success status
 */
function deleteProductImage($filename) {
    global $uploadDir;
    
    // Only admin can delete images
    if (!isLoggedIn() || !hasRole('admin')) {
        return false;
    }
    
    if (empty($filename)) {
        return true; // No image to delete
    }
    
    $filePath = $uploadDir . $filename;
    
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    
    return true; // File doesn't exist, consider it deleted
}

/**
 * Get product image URL
 * @param string|null $filename Image filename
 * @return string Image URL or placeholder
 */
function getProductImageUrl($filename) {
    if (empty($filename)) {
        return BASE_URL . 'assets/images/no-image.svg';
    }
    
    $imagePath = BASE_URL . 'uploads/products/' . $filename;
    
    // Check if file exists
    $filePath = __DIR__ . '/../uploads/products/' . $filename;
    if (!file_exists($filePath)) {
        return BASE_URL . 'assets/images/no-image.svg';
    }
    
    return $imagePath;
}

