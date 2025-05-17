<?php
require_once '../includes/auth.php';
checkRole('Nurse');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2 class="text-center my-4">Triage Dashboard</h2>
    
    <div class="card">
        <div class="card-header">
            <h4>Patients Waiting for Triage</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Visit Number</th>
                            <th>Patient Name</th>
                            <th>Doctor</th>
                            <th>Arrival Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $query = "SELECT v.visit_id, v.visit_number, v.visit_date, 
                                 p.first_name AS patient_first, p.last_name AS patient_last,
                                 d.first_name AS doctor_first, d.last_name AS doctor_last
                                 FROM visits v
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 JOIN doctors d ON v.doctor_id = d.doctor_id
                                 WHERE v.status = 'Active' AND v.visit_id NOT IN (SELECT visit_id FROM triage)
                                 ORDER BY v.visit_date ASC";
                        $result = $conn->query($query);
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['visit_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['visit_date'])); ?></td>
                            <td>
                                <a href="record_triage.php?visit_id=<?php echo $row['visit_id']; ?>" class="btn btn-sm btn-primary">Record Triage</a>
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