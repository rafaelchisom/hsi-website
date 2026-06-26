<?php
require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';

session_start();
if (empty($_SESSION['admin_user'])) jsonError('Unauthorized', 401);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonError('Method not allowed', 405);

$type = $_GET['type'] ?? 'team';
$allowedTypes = ['team', 'news', 'pages', 'brand'];
if (!in_array($type, $allowedTypes, true)) jsonError('Invalid upload type');

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $errMsg = match($file['error'] ?? -1) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File too large (max 5MB)',
        UPLOAD_ERR_NO_FILE => 'No file uploaded',
        default => 'Upload error'
    };
    jsonError($errMsg);
}

// Validate mime type by reading file header (not trusting $_FILES['type'])
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

// Brand uploads also accept SVG and ICO
$isBrand = $type === 'brand';
$allowed = $isBrand
    ? ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon']
    : ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
if (!in_array($mime, $allowed, true)) {
    jsonError($isBrand ? 'Only JPG, PNG, WebP, SVG, or ICO files are allowed' : 'Only JPG, PNG, WebP, or GIF images are allowed');
}

// Max 5 MB (brand assets usually tiny, but be generous)
if ($file['size'] > 5 * 1024 * 1024) jsonError('File too large (max 5MB)');

$ext = match($mime) {
    'image/jpeg'                      => 'jpg',
    'image/png'                       => 'png',
    'image/webp'                      => 'webp',
    'image/gif'                       => 'gif',
    'image/svg+xml'                   => 'svg',
    'image/x-icon',
    'image/vnd.microsoft.icon'        => 'ico',
    default                           => 'bin',
};

$dir      = __DIR__ . '/../../uploads/' . $type . '/';
if (!is_dir($dir)) {
    mkdir($dir, 0777, true);
}
$filename = uniqid('', true) . '.' . $ext;
$destPath = $dir . $filename;

// If it's a JPEG or PNG, automatically convert and compress to WebP (80% quality)
if (function_exists('imagewebp') && in_array($mime, ['image/jpeg', 'image/png'], true)) {
    $filename = uniqid('', true) . '.webp';
    $destPath = $dir . $filename;
    
    $image = $mime === 'image/jpeg' 
        ? @imagecreatefromjpeg($file['tmp_name']) 
        : @imagecreatefrompng($file['tmp_name']);
        
    if ($image) {
        // Handle transparency for PNGs
        if ($mime === 'image/png') {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
        
        // Save as WebP with 80% quality
        if (imagewebp($image, $destPath, 80)) {
            imagedestroy($image);
            unlink($file['tmp_name']); // Clean up temp file
        } else {
            // Fallback to normal upload if WebP conversion fails
            imagedestroy($image);
            if (!move_uploaded_file($file['tmp_name'], $destPath)) {
                jsonError('Failed to save file — check server write permissions');
            }
        }
    } else {
        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            jsonError('Failed to save file — check server write permissions');
        }
    }
} else {
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        jsonError('Failed to save file — check server write permissions');
    }
}

// Return a web-accessible URL
$url = 'uploads/' . $type . '/' . $filename;
jsonResponse(['success' => true, 'url' => $url, 'filename' => $filename]);
