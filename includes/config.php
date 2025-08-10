<?php
// includes/config.php
require_once __DIR__ . '/db.php';

// Enable error reporting for development
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log errors to a file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../error.log');

// Load environment variables
$dotenv = parse_ini_file(__DIR__ . '/../.env');
if ($dotenv === false) {
    die('Error: .env file not found');
}

// Define constants
define('SITE_URL', isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/' : 'http://localhost/');