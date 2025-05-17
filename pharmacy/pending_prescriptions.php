<?php
require_once '../includes/auth.php';
checkRole('Pharmacist');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2 class="text-center my-4">Pending Prescriptions</h2>
    
    <div class="card">
        <div class="card-header">
            <h4>Prescriptions Awaiting Approval</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Medication</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Dosage</th>
                            <th>Frequency</th>
                            <th>Duration</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT pr.prescription_id, m.name AS medication_name, 
                                 pr.dosage, pr.frequency, pr.duration, pr.notes,
                                 p.first_name AS patient_first, p.last_name AS patient_last,
                                 d.first_name AS doctor_first, d.last_name AS doctor_last
                                 FROM prescriptions pr
                                 JOIN medications m ON pr.medication_id = m.medication_id
                                 JOIN visits v ON pr.visit_id = v.visit_id
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 JOIN doctors d ON v.doctor_id = d.doctor_id
                                 WHERE pr.status = 'Pending'
                                 ORDER BY pr.prescription_id ASC";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['medication_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                            <td><?php echo htmlspecialchars($row['dosage']); ?></td>
                            <td><?php echo htmlspecialchars($row['frequency']); ?></td>
                            <td><?php echo htmlspecialchars($row['duration']); ?></td>
                            <td><?php echo $row['notes'] ? htmlspecialchars($row['notes']) : 'N/A'; ?></td>
                            <td>
                                <div class="btn-group">
                                    <a href="process_prescription.php?action=approve&id=<?php echo $row['prescription_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                    <a href="process_prescription.php?action=reject&id=<?php echo $row['prescription_id']; ?>" class="btn btn-sm btn-danger">Reject</a>
                                </div>
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