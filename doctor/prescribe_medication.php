<?php
require_once '../includes/auth.php';
checkRole('Doctor');

$doctorId = $_SESSION['related_id'];
$visitId = isset($_GET['visit_id']) ? $_GET['visit_id'] : null;

if ($visitId) {
    // Check if visit belongs to this doctor
    $visit = $conn->query("SELECT v.*, p.first_name, p.last_name 
                          FROM visits v
                          JOIN patients p ON v.patient_id = p.patient_id
                          WHERE v.visit_id = $visitId AND v.doctor_id = $doctorId AND v.status = 'Active'")->fetch_assoc();
    if (!$visit) {
        header("Location: dashboard.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['prescribe_medication'])) {
    $visitId = $_POST['visit_id'];
    $medicationIds = $_POST['medication_ids'];
    $dosages = $_POST['dosages'];
    $frequencies = $_POST['frequencies'];
    $durations = $_POST['durations'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : [];
    
    $conn->begin_transaction();
    try {
        foreach ($medicationIds as $index => $medicationId) {
            $dosage = $dosages[$index];
            $frequency = $frequencies[$index];
            $duration = $durations[$index];
            $note = isset($notes[$index]) ? $notes[$index] : '';
            
            $stmt = $conn->prepare("INSERT INTO prescriptions (visit_id, doctor_id, medication_id, dosage, frequency, duration, notes) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("iiissss", $visitId, $doctorId, $medicationId, $dosage, $frequency, $duration, $note);
            $stmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success'] = 'Medications prescribed successfully!';
        header("Location: record_notes.php?visit_id=$visitId");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $error = 'Error prescribing medications: ' . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Prescribe Medication</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!$visitId): ?>
    <div class="card">
        <div class="card-header">
            <h4>Select Patient Visit</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Visit Number</th>
                            <th>Patient Name</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT v.visit_id, v.visit_number, v.visit_date, 
                                 p.first_name AS patient_first, p.last_name AS patient_last
                                 FROM visits v
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 WHERE v.doctor_id = $doctorId AND v.status = 'Active'
                                 ORDER BY v.visit_date DESC";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['visit_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td><?php echo date('M j, Y h:i A', strtotime($row['visit_date'])); ?></td>
                            <td>
                                <a href="prescribe_medication.php?visit_id=<?php echo $row['visit_id']; ?>" class="btn btn-sm btn-warning">Prescribe Medication</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="form-container">
        <form method="POST" action="prescribe_medication.php">
            <input type="hidden" name="visit_id" value="<?php echo $visitId; ?>">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Patient Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Patient:</strong> <?php echo htmlspecialchars($visit['first_name'] . ' ' . $visit['last_name']); ?></p>
                            <p><strong>Visit Number:</strong> <?php echo htmlspecialchars($visit['visit_number']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Visit Date:</strong> <?php echo date('M j, Y h:i A', strtotime($visit['visit_date'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Prescribe Medication</h4>
                </div>
                <div class="card-body">
                    <div id="medication-container">
                        <div class="row mb-3 medication-row">
                            <div class="col-md-4">
                                <select name="medication_ids[]" class="form-control" required>
                                    <option value="">Select Medication</option>
                                    <?php
                                    $meds = $conn->query("SELECT * FROM medications ORDER BY name");
                                    while ($med = $meds->fetch_assoc()): ?>
                                    <option value="<?php echo $med['medication_id']; ?>">
                                        <?php echo htmlspecialchars($med['name']); ?> (<?php echo number_format($med['cost_per_unit'], 2); ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="dosages[]" class="form-control" placeholder="Dosage" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="frequencies[]" class="form-control" placeholder="Frequency" required>
                            </div>
                            <div class="col-md-2">
                                <input type="text" name="durations[]" class="form-control" placeholder="Duration" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger remove-medication-btn">Remove</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="button" id="add-medication-btn" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Add Another Medication
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="prescribe_medication" class="btn btn-primary me-md-2">Submit Prescriptions</button>
                <a href="record_notes.php?visit_id=<?php echo $visitId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>