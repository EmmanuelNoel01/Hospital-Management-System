<?php
require_once '../includes/auth.php';
checkRole('Admin');

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// In a real implementation, you would generate a CSV or Excel file
// For this example, we'll just redirect back with a message
$_SESSION['success'] = "Report exported for dates $startDate to $endDate";
header("Location: reports.php");
exit();
?>