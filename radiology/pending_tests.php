<?php
require_once '../includes/auth.php';
checkRole('Radiologist');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2 class="text-center my-4">Pending Radiology Tests</h2>
    
    <div class="card">
        <div class="card-header">
            <h4>Tests Awaiting Processing</h4>
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
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT ro.order_id, rt.test_name, ro.order_date, ro.notes,
                                 p.first_name AS patient_first, p.last_name AS patient_last,
                                 d.first_name AS doctor_first, d.last_name AS doctor_last
                                 FROM radiology_orders ro
                                 JOIN radiology_tests rt ON ro.radiology_id = rt.radiology_id
                                 JOIN visits v ON ro.visit_id = v.visit_id
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 JOIN doctors d ON v.doctor_id = d.doctor_id
                                 WHERE ro.status = 'Pending'
                                 ORDER BY ro.order_date ASC";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['test_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                            <td><?php echo date('M j, Y h:i A', strtotime($row['order_date'])); ?></td>
                            <td><?php echo $row['notes'] ? htmlspecialchars($row['notes']) : 'N/A'; ?></td>
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
</div>

<?php include '../includes/footer.php'; ?>