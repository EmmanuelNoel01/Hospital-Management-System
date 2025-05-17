<?php
require_once '../includes/auth.php';
checkRole('Receptionist');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $patientId = $_POST['patient_id'];
    $doctorId = $_POST['doctor_id'];
    $visitNumber = generateVisitNumber();
    
    $stmt = $conn->prepare("INSERT INTO visits (patient_id, doctor_id, visit_number) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $patientId, $doctorId, $visitNumber);
    
    if ($stmt->execute()) {
        $success = "Visit created successfully! Visit Number: $visitNumber";
    } else {
        $error = "Error creating visit: " . $conn->error;
    }
}

// Get patients
$patients = $conn->query("SELECT patient_id, first_name, last_name FROM patients ORDER BY last_name, first_name");

// Get doctors
$doctors = $conn->query("SELECT doctor_id, first_name, last_name, specialization FROM doctors ORDER BY last_name, first_name");

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Create New Visit</h2>
    
    <?php if ($error): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="create_visit.php">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="patient_id" class="form-label required-field">Patient</label>
                    <select class="form-control" id="patient_id" name="patient_id" required>
                        <option value="">Select Patient</option>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                        <option value="<?php echo $patient['patient_id']; ?>">
                            <?php echo htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="doctor_id" class="form-label required-field">Doctor</label>
                    <select class="form-control" id="doctor_id" name="doctor_id" required>
                        <option value="">Select Doctor</option>
                        <?php while ($doctor = $doctors->fetch_assoc()): ?>
                        <option value="<?php echo $doctor['doctor_id']; ?>">
                            Dr. <?php echo htmlspecialchars($doctor['last_name'] . ', ' . $doctor['first_name'] . ' (' . $doctor['specialization'] . ')'); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Create Visit</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>