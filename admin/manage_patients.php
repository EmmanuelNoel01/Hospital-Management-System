<?php
require_once '../includes/auth.php';
checkRole('Admin');

if (isset($_GET['delete'])) {
    $patientId = $_GET['delete'];
    
    // Check if patient has any visits
    $hasVisits = $conn->query("SELECT COUNT(*) as count FROM visits WHERE patient_id = $patientId")->fetch_assoc()['count'];
    
    if ($hasVisits > 0) {
        $_SESSION['error'] = 'Cannot delete patient - they have associated visits';
        header("Location: manage_patients.php");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM patients WHERE patient_id = ?");
    $stmt->bind_param("i", $patientId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Patient deleted successfully!';
        header("Location: manage_patients.php");
        exit();
    } else {
        $error = 'Error deleting patient: ' . $conn->error;
    }
}

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Get total patients
$total = $conn->query("SELECT COUNT(*) as total FROM patients")->fetch_assoc()['total'];
$pages = ceil($total / $perPage);

// Get patients with pagination
$query = "SELECT * FROM patients ORDER BY last_name, first_name LIMIT $start, $perPage";
$patients = $conn->query($query);

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Manage Patients</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h4>All Patients</h4>
                </div>
                <div class="col-md-6 text-end">
                    <a href="../reception/register_patient.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Patient
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Date of Birth</th>
                            <th>Gender</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($patient = $patients->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($patient['last_name'] . ', ' . $patient['first_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($patient['date_of_birth'])); ?></td>
                            <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                            <td>
                                <?php if ($patient['phone']): ?>
                                <p><?php echo htmlspecialchars($patient['phone']); ?></p>
                                <?php endif; ?>
                                <?php if ($patient['email']): ?>
                                <p><?php echo htmlspecialchars($patient['email']); ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="../reception/view_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-info">View</a>
                                <a href="../reception/edit_patient.php?id=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="manage_patients.php?delete=<?php echo $patient['patient_id']; ?>" class="btn btn-sm btn-danger delete-btn">Delete</a>
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