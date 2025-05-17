<?php
require_once '../includes/auth.php';
checkRole('Receptionist');

if (!isset($_GET['id'])) {
    header("Location: view_invoices.php");
    exit();
}

$invoiceId = $_GET['id'];

// Get invoice details
$invoice = $conn->query("SELECT i.*, v.visit_number, 
                        p.first_name AS patient_first, p.last_name AS patient_last, p.address, p.phone,
                        d.first_name AS doctor_first, d.last_name AS doctor_last
                        FROM invoices i
                        JOIN visits v ON i.visit_id = v.visit_id
                        JOIN patients p ON v.patient_id = p.patient_id
                        JOIN doctors d ON v.doctor_id = d.doctor_id
                        WHERE i.invoice_id = $invoiceId")->fetch_assoc();

if (!$invoice) {
    header("Location: view_invoices.php");
    exit();
}

// Get invoice items
$items = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = $invoiceId");

// Set headers for download
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="invoice_' . $invoiceId . '.pdf"');

// In a real implementation, you would use a PDF library like TCPDF or DomPDF
// For this example, we'll just output HTML that can be printed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?php echo $invoiceId; ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; color: #333; }
        .invoice-header { text-align: center; margin-bottom: 20px; }
        .invoice-header h1 { margin: 0; color: #2c3e50; }
        .invoice-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .invoice-info div { width: 48%; }
        .invoice-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .invoice-table th, .invoice-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .invoice-table th { background-color: #f2f2f2; }
        .invoice-total { text-align: right; margin-top: 20px; }
        .footer { margin-top: 40px; text-align: center; font-size: 0.9em; color: #777; }
    </style>
</head>
<body>
    <div class="invoice-header">
        <h1>HOSPITAL INVOICE</h1>
        <p>123 Medical Drive, Cityville, ST 12345 | Phone: (123) 456-7890</p>
    </div>
    
    <div class="invoice-info">
        <div>
            <h3>Bill To:</h3>
            <p><?php echo htmlspecialchars($invoice['patient_first'] . ' ' . $invoice['patient_last']); ?></p>
            <p><?php echo htmlspecialchars($invoice['address']); ?></p>
            <p>Phone: <?php echo htmlspecialchars($invoice['phone']); ?></p>
        </div>
        <div>
            <p><strong>Invoice #:</strong> <?php echo $invoiceId; ?></p>
            <p><strong>Date:</strong> <?php echo date('M j, Y', strtotime($invoice['created_at'])); ?></p>
            <p><strong>Visit #:</strong> <?php echo htmlspecialchars($invoice['visit_number']); ?></p>
            <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($invoice['doctor_first'] . ' ' . $invoice['doctor_last']); ?></p>
        </div>
    </div>
    
    <table class="invoice-table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Description</th>
                <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = $items->fetch_assoc()): 
                $description = '';
                $itemType = $item['item_type'];
                $itemId = $item['item_id'];
                
                if ($itemType == 'Consultation') {
                    $description = 'Doctor Consultation Fee';
                } elseif ($itemType == 'Lab') {
                    $test = $conn->query("SELECT test_name FROM lab_tests WHERE test_id = $itemId")->fetch_assoc();
                    $description = 'Lab Test: ' . $test['test_name'];
                } elseif ($itemType == 'Radiology') {
                    $test = $conn->query("SELECT test_name FROM radiology_tests WHERE radiology_id = $itemId")->fetch_assoc();
                    $description = 'Radiology: ' . $test['test_name'];
                } elseif ($itemType == 'Medication') {
                    $med = $conn->query("SELECT name FROM medications WHERE medication_id = $itemId")->fetch_assoc();
                    $description = 'Medication: ' . $med['name'];
                }
            ?>
            <tr>
                <td><?php echo $itemType; ?></td>
                <td><?php echo $description; ?></td>
                <td><?php echo number_format($item['amount'], 2); ?></td>
            </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    
    <div class="invoice-total">
        <h3>Total Amount: <?php echo number_format($invoice['total_amount'], 2); ?></h3>
        <p>Payment Status: 
            <strong>
                <?php if ($invoice['payment_status'] == 'Paid'): ?>
                <span style="color: green;">PAID</span>
                <?php elseif ($invoice['payment_status'] == 'Partially Paid'): ?>
                <span style="color: orange;">PARTIALLY PAID</span>
                <?php else: ?>
                <span style="color: red;">PENDING</span>
                <?php endif; ?>
            </strong>
        </p>
    </div>
    
    <div class="footer">
        <p>Thank you for choosing our hospital!</p>
        <p>Please bring this invoice when making payments</p>
    </div>
    
    <script>
        // Auto-print the invoice when the page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>