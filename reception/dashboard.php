<?php
require_once '../includes/auth.php';
checkRole('Receptionist');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2 class="text-center my-4">Reception Dashboard</h2>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='register_patient.php'">
                <div class="card-body text-center">
                    <i class="fas fa-user-plus fa-3x mb-3"></i>
                    <h5 class="card-title">Register New Patient</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='create_visit.php'">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-plus fa-3x mb-3"></i>
                    <h5 class="card-title">Create New Visit</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='view_visits.php'">
                <div class="card-body text-center">
                    <i class="fas fa-list fa-3x mb-3"></i>
                    <h5 class="card-title">View All Visits</h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h4>Today's Visits</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Visit Number</th>
                            <th>Patient Name</th>
                            <th>Doctor</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $today = date('Y-m-d');
                        $query = "SELECT v.visit_id, v.visit_number, v.visit_date, v.status, 
                                  p.first_name AS patient_first, p.last_name AS patient_last,
                                  d.first_name AS doctor_first, d.last_name AS doctor_last
                                  FROM visits v
                                  JOIN patients p ON v.patient_id = p.patient_id
                                  JOIN doctors d ON v.doctor_id = d.doctor_id
                                  WHERE DATE(v.visit_date) = ?
                                  ORDER BY v.visit_date DESC";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("s", $today);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        
                        while ($row = $result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['visit_number']); ?></td>
                            <td><?php echo htmlspecialchars($row['patient_first'] . ' ' . $row['patient_last']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($row['doctor_first'] . ' ' . $row['doctor_last']); ?></td>
                            <td><?php echo date('h:i A', strtotime($row['visit_date'])); ?></td>
                            <td>
                                <span class="visit-status-<?php echo strtolower($row['status']); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_visit_details.php?id=<?php echo $row['visit_id']; ?>" class="btn btn-sm btn-info">View</a>
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