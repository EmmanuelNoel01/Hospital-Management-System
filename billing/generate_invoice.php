<?php
require_once '../includes/auth.php';
checkRole('Receptionist');

if (!isset($_GET['visit_id'])) {
    header("Location: ../reception/view_visits.php");
    exit();
}

$visitId = $_GET['visit_id'];

// Check if visit exists and is completed
$visit = $conn->query("SELECT * FROM visits WHERE visit_id = $visitId AND status = 'Completed'")->fetch_assoc();
if (!$visit) {
    header("Location: ../reception/view_visits.php");
    exit();
}

// Check if invoice already exists
$invoiceExists = $conn->query("SELECT * FROM invoices WHERE visit_id = $visitId")->fetch_assoc();
if ($invoiceExists) {
    header("Location: view_invoices.php");
    exit();
}

// Calculate total amount
$totalAmount = 0;

// Doctor consultation fee (fixed for this example)
$consultationFee = 50.00;
$totalAmount += $consultationFee;

// Lab tests
$labTests = $conn->query("SELECT SUM(lt.cost) as total 
                         FROM lab_orders lo
                         JOIN lab_tests lt ON lo.test_id = lt.test_id
                         WHERE lo.visit_id = $visitId AND lo.status = 'Completed'");
$labTotal = $labTests->fetch_assoc()['total'];
$totalAmount += $labTotal ? $labTotal : 0;

// Radiology tests
$radiologyTests = $conn->query("SELECT SUM(rt.cost) as total 
                               FROM radiology_orders ro
                               JOIN radiology_tests rt ON ro.radiology_id = rt.radiology_id
                               WHERE ro.visit_id = $visitId AND ro.status = 'Completed'");
$radiologyTotal = $radiologyTests->fetch_assoc()['total'];
$totalAmount += $radiologyTotal ? $radiologyTotal : 0;

// Medications
$medications = $conn->query("SELECT SUM(m.cost_per_unit) as total 
                            FROM prescriptions pr
                            JOIN medications m ON pr.medication_id = m.medication_id
                            WHERE pr.visit_id = $visitId AND pr.status = 'Approved'");
$medicationTotal = $medications->fetch_assoc()['total'];
$totalAmount += $medicationTotal ? $medicationTotal : 0;

// Create invoice
$conn->begin_transaction();

try {
    // Insert invoice
    $stmt = $conn->prepare("INSERT INTO invoices (visit_id, total_amount) VALUES (?, ?)");
    $stmt->bind_param("id", $visitId, $totalAmount);
    $stmt->execute();
    $invoiceId = $conn->insert_id;
    
    // Add consultation fee
    $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, item_type, item_id, amount) VALUES (?, 'Consultation', 0, ?)");
    $stmt->bind_param("id", $invoiceId, $consultationFee);
    $stmt->execute();
    
    // Add lab tests
    $labItems = $conn->query("SELECT lo.order_id, lt.test_id, lt.test_name, lt.cost 
                             FROM lab_orders lo
                             JOIN lab_tests lt ON lo.test_id = lt.test_id
                             WHERE lo.visit_id = $visitId AND lo.status = 'Completed'");
    while ($item = $labItems->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, item_type, item_id, amount) VALUES (?, 'Lab', ?, ?)");
        $stmt->bind_param("iid", $invoiceId, $item['test_id'], $item['cost']);
        $stmt->execute();
    }
    
    // Add radiology tests
    $radiologyItems = $conn->query("SELECT ro.order_id, rt.radiology_id, rt.test_name, rt.cost 
                                   FROM radiology_orders ro
                                   JOIN radiology_tests rt ON ro.radiology_id = rt.radiology_id
                                   WHERE ro.visit_id = $visitId AND ro.status = 'Completed'");
    while ($item = $radiologyItems->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, item_type, item_id, amount) VALUES (?, 'Radiology', ?, ?)");
        $stmt->bind_param("iid", $invoiceId, $item['radiology_id'], $item['cost']);
        $stmt->execute();
    }
    
    // Add medications
    $medItems = $conn->query("SELECT pr.prescription_id, m.medication_id, m.name, m.cost_per_unit
                             FROM prescriptions pr
                             JOIN medications m ON pr.medication_id = m.medication_id
                             WHERE pr.visit_id = $visitId AND pr.status = 'Approved'");
    while ($item = $medItems->fetch_assoc()) {
        $stmt = $conn->prepare("INSERT INTO invoice_items (invoice_id, item_type, item_id, amount) VALUES (?, 'Medication', ?, ?)");
        $stmt->bind_param("iid", $invoiceId, $item['medication_id'], $item['cost_per_unit']);
        $stmt->execute();
    }
    
    $conn->commit();
    $_SESSION['success'] = 'Invoice generated successfully!';
    header("Location: view_invoices.php");
    exit();
} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = 'Error generating invoice: ' . $e->getMessage();
    header("Location: ../reception/view_visit_details.php?id=$visitId");
    exit();
}
?>