<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hospital_management');

// Secret key for password hashing (CHANGE THIS IN PRODUCTION)
define('SECRET_KEY', 'x7F!2p9L#4q1z$8R%3t6Y&5v0W*9kS@');

// Create connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset("utf8");

// Start session
session_start();

// Password hashing function
function secureHash($password) {
    return hash('sha256', SECRET_KEY . $password);
}

// Helper functions
function generateVisitNumber() {
    return 'VN' . date('Ymd') . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function redirectBasedOnRole() {
    if (isLoggedIn()) {
        $role = getUserRole();
        $dashboard = '';
        
        switch ($role) {
            case 'Admin': $dashboard = 'admin/dashboard.php'; break;
            case 'Receptionist': $dashboard = 'reception/dashboard.php'; break;
            case 'Doctor': $dashboard = 'doctor/dashboard.php'; break;
            case 'Nurse': $dashboard = 'triage/dashboard.php'; break;
            case 'Lab Technician': $dashboard = 'lab/dashboard.php'; break;
            case 'Radiologist': $dashboard = 'radiology/dashboard.php'; break;
            case 'Pharmacist': $dashboard = 'pharmacy/dashboard.php'; break;
            default: $dashboard = 'index.php';
        }
        
        header("Location: $dashboard");
        exit();
    }
}
?>