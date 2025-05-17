<?php
require_once '../includes/auth.php';
checkRole('Doctor');

$doctorId = $_SESSION['related_id'];
$visitId = isset($_GET['visit_id']) ? $_GET['visit_id'] : null;
$testType = isset($_GET['type']) && $_GET['type'] == 'radiology' ? 'radiology' : 'lab';

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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['order_tests'])) {
    $visitId = $_POST['visit_id'];
    $testType = $_POST['test_type'];
    
    if ($testType == 'lab') {
        // Process lab test orders
        $testIds = $_POST['test_ids'];
        $testNotes = $_POST['test_notes'];
        
        $conn->begin_transaction();
        try {
            foreach ($testIds as $index => $testId) {
                $notes = isset($testNotes[$index]) ? $testNotes[$index] : '';
                
                $stmt = $conn->prepare("INSERT INTO lab_orders (visit_id, doctor_id, test_id, notes) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $visitId, $doctorId, $testId, $notes);
                $stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['success'] = 'Lab tests ordered successfully!';
            header("Location: record_notes.php?visit_id=$visitId");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error ordering lab tests: ' . $e->getMessage();
        }
    } else {
        // Process radiology test orders
        $radiologyIds = $_POST['radiology_ids'];
        $radiologyNotes = $_POST['radiology_notes'];
        
        $conn->begin_transaction();
        try {
            foreach ($radiologyIds as $index => $radiologyId) {
                $notes = isset($radiologyNotes[$index]) ? $radiologyNotes[$index] : '';
                
                $stmt = $conn->prepare("INSERT INTO radiology_orders (visit_id, doctor_id, radiology_id, notes) 
                                      VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $visitId, $doctorId, $radiologyId, $notes);
                $stmt->execute();
            }
            
            $conn->commit();
            $_SESSION['success'] = 'Radiology tests ordered successfully!';
            header("Location: record_notes.php?visit_id=$visitId");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = 'Error ordering radiology tests: ' . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Order <?php echo ucfirst($testType); ?> Tests</h2>
    
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
                                <a href="order_tests.php?visit_id=<?php echo $row['visit_id']; ?>" class="btn btn-sm btn-primary">Order Lab Tests</a>
                                <a href="order_tests.php?visit_id=<?php echo $row['visit_id']; ?>&type=radiology" class="btn btn-sm btn-info">Order Radiology</a>
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
        <form method="POST" action="order_tests.php">
            <input type="hidden" name="visit_id" value="<?php echo $visitId; ?>">
            <input type="hidden" name="test_type" value="<?php echo $testType; ?>">
            
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
                    <h4>Order <?php echo ucfirst($testType); ?> Tests</h4>
                </div>
                <div class="card-body">
                    <div id="<?php echo $testType; ?>-container">
                        <div class="row mb-3 <?php echo $testType; ?>-row">
                            <div class="col-md-6">
                                <select name="<?php echo $testType; ?>_ids[]" class="form-control" required>
                                    <option value="">Select <?php echo ucfirst($testType); ?> Test</option>
                                    <?php
                                    $table = $testType == 'lab' ? 'lab_tests' : 'radiology_tests';
                                    $tests = $conn->query("SELECT * FROM $table ORDER BY test_name");
                                    while ($test = $tests->fetch_assoc()): ?>
                                    <option value="<?php echo $test[$testType == 'lab' ? 'test_id' : 'radiology_id']; ?>">
                                        <?php echo htmlspecialchars($test['test_name']); ?> (<?php echo number_format($test['cost'], 2); ?>)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <textarea name="<?php echo $testType; ?>_notes[]" class="form-control" placeholder="Notes"></textarea>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger remove-<?php echo $testType; ?>-btn">Remove</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="button" id="add-<?php echo $testType; ?>-btn" class="btn btn-secondary">
                            <i class="fas fa-plus"></i> Add Another Test
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="order_tests" class="btn btn-primary me-md-2">Submit Orders</button>
                <a href="record_notes.php?visit_id=<?php echo $visitId; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>