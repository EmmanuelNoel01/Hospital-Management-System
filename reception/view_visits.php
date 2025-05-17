<?php
require_once '../includes/auth.php';
checkRole('Receptionist');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Get total visits
$total = $conn->query("SELECT COUNT(*) as total FROM visits")->fetch_assoc()['total'];
$pages = ceil($total / $perPage);

// Get visits with pagination
$query = "SELECT v.visit_id, v.visit_number, v.visit_date, v.status, 
          p.first_name AS patient_first, p.last_name AS patient_last,
          d.first_name AS doctor_first, d.last_name AS doctor_last
          FROM visits v
          JOIN patients p ON v.patient_id = p.patient_id
          JOIN doctors d ON v.doctor_id = d.doctor_id
          ORDER BY v.visit_date DESC
          LIMIT $start, $perPage";
$visits = $conn->query($query);

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">All Visits</h2>
    
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h4>Visit Records</h4>
                </div>
                <div class="col-md-6 text-end">
                    <a href="create_visit.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> New Visit
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Visit Number</th>
                            <th>Patient Name</th>
                            <th>Doctor</th>
                            <th>Date & Time</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($visit = $visits->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($visit['visit_number']); ?></td>
                            <td><?php echo htmlspecialchars($visit['patient_first'] . ' ' . $visit['patient_last']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($visit['doctor_first'] . ' ' . $visit['doctor_last']); ?></td>
                            <td><?php echo date('M j, Y h:i A', strtotime($visit['visit_date'])); ?></td>
                            <td>
                                <span class="visit-status-<?php echo strtolower($visit['status']); ?>">
                                    <?php echo $visit['status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_visit_details.php?id=<?php echo $visit['visit_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <?php if ($page < $pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>