<?php
require_once '../includes/auth.php';
checkRole('Nurse');

if (!isset($_GET['visit_id'])) {
    header("Location: dashboard.php");
    exit();
}

$visitId = $_GET['visit_id'];

// Check if visit exists and is active
$visit = $conn->query("SELECT v.*, p.first_name, p.last_name 
                      FROM visits v
                      JOIN patients p ON v.patient_id = p.patient_id
                      WHERE v.visit_id = $visitId AND v.status = 'Active'")->fetch_assoc();

if (!$visit) {
    header("Location: dashboard.php");
    exit();
}

// Check if triage already recorded
$triageExists = $conn->query("SELECT * FROM triage WHERE visit_id = $visitId")->fetch_assoc();
if ($triageExists) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $bloodPressure = trim($_POST['blood_pressure']);
    $temperature = trim($_POST['temperature']);
    $weight = trim($_POST['weight']);
    $height = trim($_POST['height']);
    $notes = trim($_POST['notes']);
    
    // Get the nurse_id from the session - with better validation
    if (!isset($_SESSION['related_id']) || empty($_SESSION['related_id'])) {
        // If not set, try to get nurse_id from users table
        $userId = $_SESSION['user_id'];
        $userQuery = $conn->query("SELECT related_id FROM users WHERE user_id = $userId");
        $userData = $userQuery->fetch_assoc();
        
        if ($userData && !empty($userData['related_id'])) {
            $_SESSION['related_id'] = $userData['related_id'];
            $nurseId = $userData['related_id'];
        } else {
            $error = 'Nurse information not found in system records. Please contact administrator.';
        }
    } else {
        $nurseId = $_SESSION['related_id'];
    }
    
    if (!isset($error)) {
        $stmt = $conn->prepare("INSERT INTO triage (visit_id, nurse_id, blood_pressure, temperature, weight, height, notes) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisddds", $visitId, $nurseId, $bloodPressure, $temperature, $weight, $height, $notes);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Triage recorded successfully!';
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Error recording triage: ' . $conn->error;
        }
    }
}

include '../includes/header.php';
?>
<!-- Rest of your HTML form remains the same -->
<div class="container">
    <h2 class="text-center my-4">Record Triage</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="record_triage.php?visit_id=<?php echo $visitId; ?>">
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
                    <h4>Triage Measurements</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="blood_pressure" class="form-label">Blood Pressure (mmHg)</label>
                            <input type="text" class="form-control" id="blood_pressure" name="blood_pressure" placeholder="e.g., 120/80">
                        </div>
                        <div class="col-md-6">
                            <label for="temperature" class="form-label">Temperature (°C)</label>
                            <input type="number" step="0.1" class="form-control" id="temperature" name="temperature" placeholder="e.g., 36.6">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="weight" class="form-label">Weight (kg)</label>
                            <input type="number" step="0.1" class="form-control" id="weight" name="weight" placeholder="e.g., 70.5">
                        </div>
                        <div class="col-md-6">
                            <label for="height" class="form-label">Height (cm)</label>
                            <input type="number" step="0.1" class="form-control" id="height" name="height" placeholder="e.g., 175.0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Save Triage Record</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>