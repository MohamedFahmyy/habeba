<?php
/**
 * Logout Handler
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/core/Auth.php';

Auth::logout();
header('Location: index.php');
exit;
