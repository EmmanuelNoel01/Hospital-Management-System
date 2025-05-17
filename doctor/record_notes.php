<?php
require_once '../includes/auth.php';
checkRole('Doctor');

$doctorId = $_SESSION['related_id'];

$visitId = isset($_GET['visit_id']) ? $_GET['visit_id'] : null;

if ($visitId) {
    // Check if visit belongs to this doctor
    $visit = $conn->query("SELECT * FROM visits WHERE visit_id = $visitId AND doctor_id = $doctorId")->fetch_assoc();
    if (!$visit) {
        header("Location: dashboard.php");
        exit();
    }
    
    // Get patient info
    $patient = $conn->query("SELECT p.* FROM patients p 
                            JOIN visits v ON p.patient_id = v.patient_id
                            WHERE v.visit_id = $visitId")->fetch_assoc();
    
    // Get triage info
    $triage = $conn->query("SELECT * FROM triage WHERE visit_id = $visitId")->fetch_assoc();
    
    // Check if notes already exist
    $existingNotes = $conn->query("SELECT * FROM doctor_notes WHERE visit_id = $visitId")->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_notes'])) {
    $visitId = $_POST['visit_id'];
    $complaints = trim($_POST['complaints']);
    $diagnosis = trim($_POST['diagnosis']);
    $notes = trim($_POST['notes']);
    
    if ($existingNotes) {
        // Update existing notes
        $stmt = $conn->prepare("UPDATE doctor_notes SET complaints = ?, diagnosis = ?, notes = ? 
                               WHERE visit_id = ? AND doctor_id = ?");
        $stmt->bind_param("sssii", $complaints, $diagnosis, $notes, $visitId, $doctorId);
    } else {
        // Insert new notes
        $stmt = $conn->prepare("INSERT INTO doctor_notes (visit_id, doctor_id, complaints, diagnosis, notes) 
                               VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisss", $visitId, $doctorId, $complaints, $diagnosis, $notes);
    }
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Doctor notes saved successfully!';
        header("Location: record_notes.php?visit_id=$visitId");
        exit();
    } else {
        $error = 'Error saving doctor notes: ' . $conn->error;
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Doctor's Notes</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
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
                            <th>Triage Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT v.visit_id, v.visit_number, v.visit_date, 
                                 p.first_name AS patient_first, p.last_name AS patient_last,
                                 CASE WHEN t.triage_id IS NULL THEN 'Pending' ELSE 'Completed' END AS triage_status
                                 FROM visits v
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 LEFT JOIN triage t ON v.visit_id = t.visit_id
                                 WHERE v.doctor_id = $doctorId AND v.status = 'Active'
                                 ORDER BY v.visit_date DESC";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['visit_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td><?php echo date('M j, Y h:i A', strtotime($row['visit_date'])); ?></td>
                            <td><?php echo $row['triage_status']; ?></td>
                            <td>
                                <a href="record_notes.php?visit_id=<?php echo $row['visit_id']; ?>" class="btn btn-sm btn-primary">Record Notes</a>
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
        <form method="POST" action="record_notes.php">
            <input type="hidden" name="visit_id" value="<?php echo $visitId; ?>">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Patient Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Patient:</strong> <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></p>
                            <p><strong>Date of Birth:</strong> <?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?></p>
                            <p><strong>Gender:</strong> <?php echo htmlspecialchars($patient['gender']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Visit Number:</strong> <?php echo htmlspecialchars($visit['visit_number']); ?></p>
                            <p><strong>Visit Date:</strong> <?php echo date('M j, Y h:i A', strtotime($visit['visit_date'])); ?></p>
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
                        <p><strong>Nurse Notes:</strong> <?php echo htmlspecialchars($triage['notes']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Doctor's Notes</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="complaints" class="form-label">Patient Complaints</label>
                        <textarea class="form-control" id="complaints" name="complaints" rows="3" required><?php echo $existingNotes['complaints'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="diagnosis" class="form-label">Diagnosis</label>
                        <textarea class="form-control" id="diagnosis" name="diagnosis" rows="3" required><?php echo $existingNotes['diagnosis'] ?? ''; ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Additional Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo $existingNotes['notes'] ?? ''; ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="save_notes" class="btn btn-primary me-md-2">Save Notes</button>
                <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
        </form>
    </div>
    
    <div class="text-center mt-4">
        <div class="btn-group">
            <a href="order_tests.php?visit_id=<?php echo $visitId; ?>" class="btn btn-success">Order Lab Tests</a>
            <a href="order_tests.php?visit_id=<?php echo $visitId; ?>&type=radiology" class="btn btn-info">Order Radiology Tests</a>
            <a href="prescribe_medication.php?visit_id=<?php echo $visitId; ?>" class="btn btn-warning">Prescribe Medication</a>
            <a href="complete_visit.php?visit_id=<?php echo $visitId; ?>" class="btn btn-danger">Complete Visit</a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>