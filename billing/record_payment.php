<?php
require_once '../includes/auth.php';
checkRole('Receptionist');

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !isset($_POST['invoice_id'])) {
    header("Location: view_invoices.php");
    exit();
}

$invoiceId = $_POST['invoice_id'];
$amount = $_POST['amount'];
$paymentMethod = $_POST['payment_method'];
$notes = $_POST['notes'] ?? '';

// Get invoice details
$invoice = $conn->query("SELECT * FROM invoices WHERE invoice_id = $invoiceId")->fetch_assoc();
if (!$invoice) {
    header("Location: view_invoices.php");
    exit();
}

// Calculate new payment status
$paidAmount = $amount;
if ($invoice['payment_status'] == 'Partially Paid') {
    // Need to get previous payments (this would require a payments table in a real system)
    // For this example, we'll assume we're only recording one payment per invoice
    $paidAmount = $amount;
}

if ($paidAmount >= $invoice['total_amount']) {
    $newStatus = 'Paid';
} elseif ($paidAmount > 0) {
    $newStatus = 'Partially Paid';
} else {
    $newStatus = 'Pending';
}

// Update invoice status
$stmt = $conn->prepare("UPDATE invoices SET payment_status = ? WHERE invoice_id = ?");
$stmt->bind_param("si", $newStatus, $invoiceId);

if ($stmt->execute()) {
    // In a real system, you would also record the payment details in a payments table
    $_SESSION['success'] = 'Payment recorded successfully!';
    header("Location: view_invoice_details.php?id=$invoiceId");
    exit();
} else {
    $_SESSION['error'] = 'Error recording payment: ' . $conn->error;
    header("Location: view_invoice_details.php?id=$invoiceId");
    exit();
}
?>