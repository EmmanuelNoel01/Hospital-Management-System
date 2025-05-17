<?php
require_once '../includes/auth.php';
checkRole('Receptionist');

if (!isset($_GET['id'])) {
    header("Location: view_visits.php");
    exit();
}

$visitId = $_GET['id'];

// Get visit details
$visitQuery = "SELECT v.*, p.first_name AS patient_first, p.last_name AS patient_last, p.date_of_birth, p.gender,
               d.first_name AS doctor_first, d.last_name AS doctor_last, d.specialization
               FROM visits v
               JOIN patients p ON v.patient_id = p.patient_id
               JOIN doctors d ON v.doctor_id = d.doctor_id
               WHERE v.visit_id = ?";
$stmt = $conn->prepare($visitQuery);
$stmt->bind_param("i", $visitId);
$stmt->execute();
$visit = $stmt->get_result()->fetch_assoc();

if (!$visit) {
    header("Location: view_visits.php");
    exit();
}

// Get triage info
$triage = $conn->query("SELECT * FROM triage WHERE visit_id = $visitId")->fetch_assoc();

// Get doctor notes
$notes = $conn->query("SELECT * FROM doctor_notes WHERE visit_id = $visitId")->fetch_assoc();

// Get lab orders
$labOrders = $conn->query("SELECT lo.*, lt.test_name, lt.cost 
                          FROM lab_orders lo
                          JOIN lab_tests lt ON lo.test_id = lt.test_id
                          WHERE lo.visit_id = $visitId");

// Get radiology orders
$radiologyOrders = $conn->query("SELECT ro.*, rt.test_name, rt.cost 
                                FROM radiology_orders ro
                                JOIN radiology_tests rt ON ro.radiology_id = rt.radiology_id
                                WHERE ro.visit_id = $visitId");

// Get prescriptions
$prescriptions = $conn->query("SELECT pr.*, m.name AS medication_name, m.cost_per_unit
                              FROM prescriptions pr
                              JOIN medications m ON pr.medication_id = m.medication_id
                              WHERE pr.visit_id = $visitId");

// Get invoice
$invoice = $conn->query("SELECT * FROM invoices WHERE visit_id = $visitId")->fetch_assoc();
$invoiceItems = [];
if ($invoice) {
    $invoiceItems = $conn->query("SELECT * FROM invoice_items WHERE invoice_id = {$invoice['invoice_id']}");
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Visit Details - <?php echo htmlspecialchars($visit['visit_number']); ?></h2>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Patient Information</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($visit['patient_first'] . ' ' . $visit['patient_last']); ?></p>
                    <p><strong>Date of Birth:</strong> <?php echo date('M j, Y', strtotime($visit['date_of_birth'])); ?></p>
                    <p><strong>Gender:</strong> <?php echo htmlspecialchars($visit['gender']); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($visit['doctor_first'] . ' ' . $visit['doctor_last'] . ' (' . $visit['specialization'] . ')'); ?></p>
                    <p><strong>Visit Date:</strong> <?php echo date('M j, Y h:i A', strtotime($visit['visit_date'])); ?></p>
                    <p><strong>Status:</strong> 
                        <span class="visit-status-<?php echo strtolower($visit['status']); ?>">
                            <?php echo $visit['status']; ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($triage): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Triage Information</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <p><strong>Blood Pressure:</strong> <?php echo htmlspecialchars($triage['blood_pressure']); ?></p>
                </div>
                <div class="col-md-3">
                    <p><strong>Temperature:</strong> <?php echo htmlspecialchars($triage['temperature']); ?>°C</p>
                </div>
                <div class="col-md-3">
                    <p><strong>Weight:</strong> <?php echo htmlspecialchars($triage['weight']); ?> kg</p>
                </div>
                <div class="col-md-3">
                    <p><strong>Height:</strong> <?php echo htmlspecialchars($triage['height']); ?> cm</p>
                </div>
            </div>
            <?php if ($triage['notes']): ?>
            <div class="mt-3">
                <p><strong>Notes:</strong> <?php echo htmlspecialchars($triage['notes']); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($notes): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Doctor's Notes</h4>
        </div>
        <div class="card-body">
            <p><strong>Complaints:</strong> <?php echo nl2br(htmlspecialchars($notes['complaints'])); ?></p>
            <p><strong>Diagnosis:</strong> <?php echo nl2br(htmlspecialchars($notes['diagnosis'])); ?></p>
            <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($notes['notes'])); ?></p>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($labOrders && $labOrders->num_rows > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Lab Tests</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Status</th>
                            <th>Results</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $labOrders->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['test_name']); ?></td>
                            <td>
                                <span class="order-status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $order['results'] ? nl2br(htmlspecialchars($order['results'])) : 'N/A'; ?></td>
                            <td><?php echo number_format($order['cost'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($radiologyOrders && $radiologyOrders->num_rows > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Radiology Tests</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Status</th>
                            <th>Results</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $radiologyOrders->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($order['test_name']); ?></td>
                            <td>
                                <span class="order-status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $order['results'] ? nl2br(htmlspecialchars($order['results'])) : 'N/A'; ?></td>
                            <td><?php echo number_format($order['cost'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($prescriptions && $prescriptions->num_rows > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Prescriptions</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Medication</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($prescription = $prescriptions->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($prescription['medication_name']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($prescription['duration']); ?></td>
                            <td>
                                <span class="prescription-status-<?php echo strtolower($prescription['status']); ?>">
                                    <?php echo $prescription['status']; ?>
                                </span>
                            </td>
                            <td><?php echo number_format($prescription['cost_per_unit'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($invoice): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h4>Invoice</h4>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <p><strong>Invoice ID:</strong> <?php echo $invoice['invoice_id']; ?></p>
                    <p><strong>Date:</strong> <?php echo date('M j, Y h:i A', strtotime($invoice['created_at'])); ?></p>
                </div>
                <div class="col-md-6 text-end">
                    <p><strong>Total Amount:</strong> <?php echo number_format($invoice['total_amount'], 2); ?></p>
                    <p><strong>Payment Status:</strong> 
                        <span class="badge bg-<?php 
                            echo $invoice['payment_status'] == 'Paid' ? 'success' : 
                                 ($invoice['payment_status'] == 'Partially Paid' ? 'warning' : 'danger'); 
                        ?>">
                            <?php echo $invoice['payment_status']; ?>
                        </span>
                    </p>
                </div>
            </div>
            
            <?php if ($invoiceItems && $invoiceItems->num_rows > 0): ?>
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
                        <?php while ($item = $invoiceItems->fetch_assoc()): 
                            $description = '';
                            $itemType = $item['item_type'];
                            $itemId = $item['item_id'];
                            
                            if ($itemType == 'Consultation') {
                                $description = 'Doctor Consultation';
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
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php elseif ($visit['status'] == 'Completed'): ?>
    <div class="text-center mb-4">
        <a href="generate_invoice.php?visit_id=<?php echo $visitId; ?>" class="btn btn-primary">
            Generate Invoice
        </a>
    </div>
    <?php endif; ?>
    
    <div class="text-center">
        <a href="view_visits.php" class="btn btn-secondary">Back to Visits</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>