<?php
require_once 'config.php';

// Check if user is logged in and has the required role
function checkRole($requiredRole) {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
    
    if ($_SESSION['role'] != $requiredRole && $_SESSION['role'] != 'Admin') {
        header("Location: ../index.php");
        exit();
    }
}
?>