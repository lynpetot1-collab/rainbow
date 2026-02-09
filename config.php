<?php
// config.php (ICEIY FIXED + LOGIN READY)

// --- PRODUCTION SAFE (no errors shown) ---
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_OFF);

// --- Session ---
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- Database (YOUR HOSTING DETAILS) ---
$DB_HOST = "sql302.iceiy.com";
$DB_USER = "icei_41107335";
$DB_PASS = "2LRTYQVnGBkF";
$DB_NAME = "icei_41107335_rnbwhtl";

$conn = @new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
    // don't expose details publicly
    http_response_code(500);
    die("Server error.");
}
$conn->set_charset("utf8mb4");

// --- Helpers ---
function e($s) { return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }
function clean_input($s) { return trim((string)($s ?? '')); }

// --- Admin auth helpers ---
function isAdminLoggedIn() {
    return !empty($_SESSION['admin_logged_in']);
}
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

// --- CSRF (optional) ---
function csrf_token() {
    if (empty($_SESSION['_csrf'])) {
        if (function_exists('random_bytes')) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        } else {
            $_SESSION['_csrf'] = bin2hex(openssl_random_pseudo_bytes(32));
        }
    }
    return $_SESSION['_csrf'];
}
function csrf_validate($token) {
    return isset($_SESSION['_csrf']) && is_string($token) && hash_equals($_SESSION['_csrf'], (string)$token);
}
?>
