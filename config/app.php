<?php
/**
 * Application Core Configuration & Setup
 */

require_once __DIR__ . '/database.php';

// Configure session security if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    // Enable secure cookie if HTTPS
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    session_start();
}

// App definitions
define('APP_NAME', env('APP_NAME', 'SmartSpend'));
define('APP_URL', env('APP_URL', 'http://localhost/projects/Expense-Tracker'));
define('APP_SECRET', env('APP_SECRET', 'secret_spending_key'));
define('DEFAULT_CURRENCY', env('DEFAULT_CURRENCY', 'INR'));
define('DEFAULT_CURRENCY_SYMBOL', env('DEFAULT_CURRENCY_SYMBOL', '₹'));
define('DEFAULT_DATE_FORMAT', env('DEFAULT_DATE_FORMAT', 'd M Y'));

// Set default timezone
date_default_timezone_set('Asia/Kolkata');

