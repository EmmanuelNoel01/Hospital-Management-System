<?php
require_once '../includes/auth.php';
checkRole('Pharmacist');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2 class="text-center my-4">Pharmacy Dashboard</h2>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='pending_prescriptions.php'">
                <div class="card-body text-center">
                    <i class="fas fa-prescription fa-3x mb-3"></i>
                    <h5 class="card-title">Pending Prescriptions</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='manage_inventory.php'">
                <div class="card-body text-center">
                    <i class="fas fa-pills fa-3x mb-3"></i>
                    <h5 class="card-title">Manage Inventory</h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h4>Recent Processed Prescriptions</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Medication</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Date Processed</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT pr.prescription_id, m.name AS medication_name, pr.status, pr.approval_date,
                                 p.first_name AS patient_first, p.last_name AS patient_last,
                                 d.first_name AS doctor_first, d.last_name AS doctor_last
                                 FROM prescriptions pr
                                 JOIN medications m ON pr.medication_id = m.medication_id
                                 JOIN visits v ON pr.visit_id = v.visit_id
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 JOIN doctors d ON v.doctor_id = d.doctor_id
                                 WHERE pr.status != 'Pending'
                                 ORDER BY pr.approval_date DESC
                                 LIMIT 5";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['medication_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                            <td>
                                <span class="prescription-status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td><?php echo $row['approval_date'] ? date('M j, Y h:i A', strtotime($row['approval_date'])) : 'N/A'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>