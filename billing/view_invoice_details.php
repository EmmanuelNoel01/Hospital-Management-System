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

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Invoice Details - #<?php echo $invoice['invoice_id']; ?></h2>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Invoice Information</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Patient:</strong> <?php echo htmlspecialchars($invoice['patient_first'] . ' ' . $invoice['patient_last']); ?></p>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($invoice['address']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($invoice['phone']); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>Invoice Date:</strong> <?php echo date('M j, Y h:i A', strtotime($invoice['created_at'])); ?></p>
                    <p><strong>Visit Number:</strong> <?php echo htmlspecialchars($invoice['visit_number']); ?></p>
                    <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($invoice['doctor_first'] . ' ' . $invoice['doctor_last']); ?></p>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Invoice Items</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Item Type</th>
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
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-end">Total Amount:</th>
                            <th><?php echo number_format($invoice['total_amount'], 2); ?></th>
                        </tr>
                        <tr>
                            <th colspan="2" class="text-end">Payment Status:</th>
                            <th>
                                <span class="badge bg-<?php 
                                    echo $invoice['payment_status'] == 'Paid' ? 'success' : 
                                         ($invoice['payment_status'] == 'Partially Paid' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo $invoice['payment_status']; ?>
                                </span>
                            </th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <div class="text-center">
        <a href="view_invoices.php" class="btn btn-secondary">Back to Invoices</a>
        <a href="print_invoice.php?id=<?php echo $invoiceId; ?>" class="btn btn-primary" target="_blank">
            <i class="fas fa-print"></i> Print Invoice
        </a>
        
        <?php if ($invoice['payment_status'] != 'Paid'): ?>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
            <i class="fas fa-money-bill-wave"></i> Record Payment
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="record_payment.php">
                <input type="hidden" name="invoice_id" value="<?php echo $invoiceId; ?>">
                <div class="modal-header">
                    <h5 class="modal-title" id="paymentModalLabel">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="amount" class="form-label">Amount Paid</label>
                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" 
                               max="<?php echo $invoice['total_amount']; ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="payment_method" class="form-label">Payment Method</label>
                        <select class="form-control" id="payment_method" name="payment_method" required>
                            <option value="Cash">Cash</option>
                            <option value="Credit Card">Credit Card</option>
                            <option value="Insurance">Insurance</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Record Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>