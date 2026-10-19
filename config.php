<?php
/**
 * Romantic Memories Platform - Configuration
 */

// Error reporting (disable display in production, log errors)
ini_set('display_errors', '0');
error_reporting(E_ALL);

// Session setup
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base Path
define('BASE_PATH', __DIR__);

// Auto-detect Base URL regardless of calling script depth
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

$docRoot = !empty($_SERVER['DOCUMENT_ROOT']) ? str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT'])) : '';
$appDir = str_replace('\\', '/', realpath(__DIR__));
$subDir = '';
if ($docRoot && strpos($appDir, $docRoot) === 0) {
    $subDir = trim(substr($appDir, strlen($docRoot)), '/');
}
$basePath = ($subDir === '') ? '' : '/' . $subDir;
define('BASE_URL', $protocol . $host . $basePath);

// Database Configuration
// Defaults to SQLite in `data/romantic_platform.sqlite` for zero-configuration deployment on Laragon or Hostinger.
// Can switch to MySQL by setting DB_DRIVER to 'mysql' and filling credentials below.
define('DB_DRIVER', getenv('DB_DRIVER') ?: 'sqlite'); // 'sqlite' or 'mysql'

// SQLite Path
define('DB_SQLITE_PATH', BASE_PATH . '/data/romantic_platform.sqlite');

// MySQL Credentials (if DB_DRIVER == 'mysql')
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'romantic_platform');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Security Salts / Secrets
define('APP_SECRET', getenv('APP_SECRET') ?: 'romantic_secret_key_change_in_prod_78394628');
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'romantic2026'); // Change via env or admin setting

// Upload settings
define('UPLOAD_DIR', BASE_PATH . '/uploads');
define('UPLOAD_URL', BASE_URL . '/uploads');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
