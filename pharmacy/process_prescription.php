<?php
require_once '../includes/auth.php';
checkRole('Pharmacist');

if (!isset($_GET['action']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$action = $_GET['action'];
$prescriptionId = $_GET['id'];
$pharmacistId = $_SESSION['related_id'];

// Validate action
if (!in_array($action, ['approve', 'reject'])) {
    header("Location: dashboard.php");
    exit();
}

// Get prescription details
$prescription = $conn->query("SELECT * FROM prescriptions WHERE prescription_id = $prescriptionId AND status = 'Pending'")->fetch_assoc();
if (!$prescription) {
    header("Location: pending_prescriptions.php");
    exit();
}

// Get medication stock
$medication = $conn->query("SELECT * FROM medications WHERE medication_id = {$prescription['medication_id']}")->fetch_assoc();

if ($action == 'approve' && $medication['stock_quantity'] <= 0) {
    $_SESSION['error'] = 'Cannot approve prescription - medication out of stock';
    header("Location: pending_prescriptions.php");
    exit();
}

// Process the prescription
$status = $action == 'approve' ? 'Approved' : 'Rejected';

$conn->begin_transaction();
try {
    // Update prescription status
    $stmt = $conn->prepare("UPDATE prescriptions SET status = ?, pharmacist_id = ?, approval_date = NOW() WHERE prescription_id = ?");
    $stmt->bind_param("sii", $status, $pharmacistId, $prescriptionId);
    $stmt->execute();
    
    // If approved, reduce medication stock
    if ($action == 'approve') {
        $newStock = $medication['stock_quantity'] - 1;
        $stmt = $conn->prepare("UPDATE medications SET stock_quantity = ? WHERE medication_id = ?");
        $stmt->bind_param("ii", $newStock, $prescription['medication_id']);
        $stmt->execute();
    }
    
    $conn->commit();
    $_SESSION['success'] = "Prescription $status successfully!";
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error processing prescription: " . $e->getMessage();
}

header("Location: pending_prescriptions.php");
exit();
?>