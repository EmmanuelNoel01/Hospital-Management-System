<?php
require_once '../includes/auth.php';
checkRole('Lab Technician');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2 class="text-center my-4">Lab Dashboard</h2>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='pending_tests.php'">
                <div class="card-body text-center">
                    <i class="fas fa-flask fa-3x mb-3"></i>
                    <h5 class="card-title">Pending Tests</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='record_results.php'">
                <div class="card-body text-center">
                    <i class="fas fa-file-alt fa-3x mb-3"></i>
                    <h5 class="card-title">Record Results</h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h4>Recent Completed Tests</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Test Name</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Date Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT lo.order_id, lt.test_name, lo.result_date,
                                 p.first_name AS patient_first, p.last_name AS patient_last,
                                 d.first_name AS doctor_first, d.last_name AS doctor_last
                                 FROM lab_orders lo
                                 JOIN lab_tests lt ON lo.test_id = lt.test_id
                                 JOIN visits v ON lo.visit_id = v.visit_id
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 JOIN doctors d ON v.doctor_id = d.doctor_id
                                 WHERE lo.status = 'Completed'
                                 ORDER BY lo.result_date DESC
                                 LIMIT 5";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['test_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                            <td><?php echo date('M j, Y h:i A', strtotime($row['result_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>