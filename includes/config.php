<?php
// Error reporting (disable in production)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Session security
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Enable if using HTTPS
session_start();

// Application constants
define('APP_NAME', 'Hospital Management System');
define('BASE_URL', 'http://localhost/hospital_management');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
?>