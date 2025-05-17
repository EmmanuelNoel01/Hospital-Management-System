<?php
require_once '../includes/auth.php';
checkRole('Lab Technician');

$orderId = isset($_GET['order_id']) ? $_GET['order_id'] : null;

if ($orderId) {
    // Get order details
    $order = $conn->query("SELECT lo.*, lt.test_name, 
                          p.first_name AS patient_first, p.last_name AS patient_last,
                          d.first_name AS doctor_first, d.last_name AS doctor_last
                          FROM lab_orders lo
                          JOIN lab_tests lt ON lo.test_id = lt.test_id
                          JOIN visits v ON lo.visit_id = v.visit_id
                          JOIN patients p ON v.patient_id = p.patient_id
                          JOIN doctors d ON v.doctor_id = d.doctor_id
                          WHERE lo.order_id = $orderId")->fetch_assoc();
    
    if (!$order) {
        header("Location: dashboard.php");
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_results'])) {
    $orderId = $_POST['order_id'];
    $results = trim($_POST['results']);
    $technicianId = $_SESSION['related_id'];
    
    $stmt = $conn->prepare("UPDATE lab_orders SET results = ?, status = 'Completed', result_date = NOW() WHERE order_id = ?");
    $stmt->bind_param("si", $results, $orderId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Test results recorded successfully!';
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Error recording test results: ' . $conn->error;
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Record Lab Test Results</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!$orderId): ?>
    <div class="card">
        <div class="card-header">
            <h4>Select Test to Record Results</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Order Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT lo.order_id, lt.test_name, lo.order_date,
                                 p.first_name AS patient_first, p.last_name AS patient_last,
                                 d.first_name AS doctor_first, d.last_name AS doctor_last
                                 FROM lab_orders lo
                                 JOIN lab_tests lt ON lo.test_id = lt.test_id
                                 JOIN visits v ON lo.visit_id = v.visit_id
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 JOIN doctors d ON v.doctor_id = d.doctor_id
                                 WHERE lo.status = 'Pending'
                                 ORDER BY lo.order_date ASC";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['test_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                            <td><?php echo date('M j, Y h:i A', strtotime($row['order_date'])); ?></td>
                            <td>
                                <a href="record_results.php?order_id=<?php echo $row['order_id']; ?>" class="btn btn-sm btn-primary">Record Results</a>
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
        <form method="POST" action="record_results.php">
            <input type="hidden" name="order_id" value="<?php echo $orderId; ?>">
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Test Information</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>Test Name:</strong> <?php echo htmlspecialchars($order['test_name']); ?></p>
                            <p><strong>Patient:</strong> <?php echo htmlspecialchars($order['patient_first'] . ' ' . $order['patient_last']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Doctor:</strong> Dr. <?php echo htmlspecialchars($order['doctor_first'] . ' ' . $order['doctor_last']); ?></p>
                            <p><strong>Order Date:</strong> <?php echo date('M j, Y h:i A', strtotime($order['order_date'])); ?></p>
                        </div>
                    </div>
                    <?php if ($order['notes']): ?>
                    <div class="mt-3">
                        <p><strong>Doctor's Notes:</strong> <?php echo htmlspecialchars($order['notes']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Test Results</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label for="results" class="form-label">Results</label>
                        <textarea class="form-control" id="results" name="results" rows="6" required><?php echo htmlspecialchars($order['results'] ?? ''); ?></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="save_results" class="btn btn-primary me-md-2">Save Results</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>