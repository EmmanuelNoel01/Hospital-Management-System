<?php
require_once '../includes/auth.php';
checkRole('Admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_doctor'])) {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $specialization = trim($_POST['specialization']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("INSERT INTO doctors (first_name, last_name, specialization, phone, email) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $firstName, $lastName, $specialization, $phone, $email);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Doctor added successfully!';
        header("Location: manage_doctors.php");
        exit();
    } else {
        $error = 'Error adding doctor: ' . $conn->error;
    }
}

if (isset($_GET['delete'])) {
    $doctorId = $_GET['delete'];
    
    // Check if doctor has any visits
    $hasVisits = $conn->query("SELECT COUNT(*) as count FROM visits WHERE doctor_id = $doctorId")->fetch_assoc()['count'];
    
    if ($hasVisits > 0) {
        $_SESSION['error'] = 'Cannot delete doctor - they have associated visits';
        header("Location: manage_doctors.php");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM doctors WHERE doctor_id = ?");
    $stmt->bind_param("i", $doctorId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Doctor deleted successfully!';
        header("Location: manage_doctors.php");
        exit();
    } else {
        $error = 'Error deleting doctor: ' . $conn->error;
    }
}

$doctors = $conn->query("SELECT * FROM doctors ORDER BY last_name, first_name");

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Manage Doctors</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Add New Doctor</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="manage_doctors.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="first_name" class="form-label required-field">First Name</label>
                        <input type="text" class="form-control" id="first_name" name="first_name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="last_name" class="form-label required-field">Last Name</label>
                        <input type="text" class="form-control" id="last_name" name="last_name" required>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="specialization" class="form-label required-field">Specialization</label>
                        <input type="text" class="form-control" id="specialization" name="specialization" required>
                    </div>
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="add_doctor" class="btn btn-primary">Add Doctor</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h4>All Doctors</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Specialization</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($doctor = $doctors->fetch_assoc()): ?>
                        <tr>
                            <td>Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                            <td>
                                <?php if ($doctor['phone']): ?>
                                <p><?php echo htmlspecialchars($doctor['phone']); ?></p>
                                <?php endif; ?>
                                <?php if ($doctor['email']): ?>
                                <p><?php echo htmlspecialchars($doctor['email']); ?></p>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit_doctor.php?id=<?php echo $doctor['doctor_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="manage_doctors.php?delete=<?php echo $doctor['doctor_id']; ?>" class="btn btn-sm btn-danger delete-btn">Delete</a>
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