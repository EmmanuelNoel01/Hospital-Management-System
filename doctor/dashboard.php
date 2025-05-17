<?php
require_once '../includes/auth.php';
checkRole('Doctor');

$doctorId = $_SESSION['related_id'];
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2 class="text-center my-4">Doctor Dashboard</h2>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='patient_visits.php'">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h5 class="card-title">My Patients</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='record_notes.php'">
                <div class="card-body text-center">
                    <i class="fas fa-notes-medical fa-3x mb-3"></i>
                    <h5 class="card-title">Record Notes</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='order_tests.php'">
                <div class="card-body text-center">
                    <i class="fas fa-flask fa-3x mb-3"></i>
                    <h5 class="card-title">Order Tests</h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h4>Today's Appointments</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Visit Number</th>
                            <th>Patient Name</th>
                            <th>Time</th>
                            <th>Triage Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $today = date('Y-m-d');
                        $query = "SELECT v.visit_id, v.visit_number, v.visit_date, 
                                 p.first_name AS patient_first, p.last_name AS patient_last,
                                 CASE WHEN t.triage_id IS NULL THEN 'Pending' ELSE 'Completed' END AS triage_status
                                 FROM visits v
                                 JOIN patients p ON v.patient_id = p.patient_id
                                 LEFT JOIN triage t ON v.visit_id = t.visit_id
                                 WHERE v.doctor_id = ? AND DATE(v.visit_date) = ? AND v.status = 'Active'
                                 ORDER BY v.visit_date ASC";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("is", $doctorId, $today);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['visit_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['visit_date'])); ?></td>
                            <td><?php echo $row['triage_status']; ?></td>
                            <td>
                                <a href="record_notes.php?visit_id=<?php echo $row['visit_id']; ?>" class="btn btn-sm btn-primary">See Patient</a>
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