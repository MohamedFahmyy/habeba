<?php
/**
 * Image Upload API Handler
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config.php';

// Ensure upload directory exists and is secured
$uploadDir = UPLOAD_DIR;
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Security: Prevent script execution in uploads directory
$htaccessPath = $uploadDir . '/.htaccess';
if (!file_exists($htaccessPath)) {
    $htaccessContent = "# Prevent script execution in uploads\n" .
        "<FilesMatch \"\.(?i:php|phtml|php3|php4|php5|php7|phps|cgi|pl|exe|sh|py)$\">\n" .
        "    Order Allow,Deny\n" .
        "    Deny from all\n" .
        "</FilesMatch>\n" .
        "<IfModule mod_php7.c>\n" .
        "    php_flag engine off\n" .
        "</IfModule>\n" .
        "<IfModule mod_php8.c>\n" .
        "    php_flag engine off\n" .
        "</IfModule>\n";
    file_put_contents($htaccessPath, $htaccessContent);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    $errMap = [
        UPLOAD_ERR_INI_SIZE   => 'Image exceeds server upload_max_filesize limit.',
        UPLOAD_ERR_FORM_SIZE  => 'Image exceeds MAX_FILE_SIZE limit.',
        UPLOAD_ERR_PARTIAL    => 'Image was only partially uploaded.',
        UPLOAD_ERR_NO_FILE    => 'No image file was uploaded.',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write image to disk.',
        UPLOAD_ERR_EXTENSION  => 'A PHP extension stopped the file upload.'
    ];
    $code = $_FILES['image']['error'] ?? UPLOAD_ERR_NO_FILE;
    $msg = $errMap[$code] ?? 'Upload error.';
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

$file = $_FILES['image'];

// Validate file size
if ($file['size'] > MAX_FILE_SIZE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Image exceeds 10MB limit.']);
    exit;
}

// Validate MIME type with finfo
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowedMimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif'
];

if (!array_key_exists($mimeType, $allowedMimes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file format. Only JPG, PNG, WEBP, and GIF are permitted.']);
    exit;
}

// Additional verification: check if it's a valid image using getimagesize
$imageInfo = @getimagesize($file['tmp_name']);
if ($imageInfo === false) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'The uploaded file is not a valid image.']);
    exit;
}

$ext = $allowedMimes[$mimeType];
$safeName = 'romantic_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
$targetPath = $uploadDir . '/' . $safeName;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Relative URL for portability
    $relativeUrl = 'uploads/' . $safeName;
    echo json_encode([
        'success' => true,
        'url' => $relativeUrl,
        'full_url' => UPLOAD_URL . '/' . $safeName,
        'filename' => $safeName
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded image to storage.']);
}
