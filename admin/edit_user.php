<?php
require_once '../includes/auth.php';
checkRole('Admin');

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$userId = $_GET['id'];

// Get user details
$user = $conn->query("SELECT * FROM users WHERE user_id = $userId")->fetch_assoc();
if (!$user) {
    header("Location: manage_users.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_user'])) {
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $relatedId = null;
    
    // Check if username changed
    if ($username != $user['username']) {
        // Verify new username is unique
        $check = $conn->prepare("SELECT * FROM users WHERE username = ? AND user_id != ?");
        $check->bind_param("si", $username, $userId);
        $check->execute();
        
        if ($check->get_result()->num_rows > 0) {
            $error = 'Username already exists';
        }
    }
    
    // Validate related_id based on role
    if ($role == 'Doctor') {
        $relatedId = $_POST['doctor_id'] ?? null;
    }
    
    if (!isset($error)) {
        // Update user
        $stmt = $conn->prepare("UPDATE users SET username = ?, role = ?, related_id = ? WHERE user_id = ?");
        $stmt->bind_param("ssii", $username, $role, $relatedId, $userId);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'User updated successfully!';
            header("Location: manage_users.php");
            exit();
        } else {
            $error = 'Error updating user: ' . $conn->error;
        }
    }
}

// Get doctors for dropdown
$doctors = $conn->query("SELECT * FROM doctors ORDER BY last_name, first_name");

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Edit User</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="edit_user.php?id=<?php echo $userId; ?>">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="username" class="form-label required-field">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="role" class="form-label required-field">Role</label>
                    <select class="form-control" id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="Admin" <?php echo $user['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="Doctor" <?php echo $user['role'] == 'Doctor' ? 'selected' : ''; ?>>Doctor</option>
                        <option value="Nurse" <?php echo $user['role'] == 'Nurse' ? 'selected' : ''; ?>>Nurse</option>
                        <option value="Receptionist" <?php echo $user['role'] == 'Receptionist' ? 'selected' : ''; ?>>Receptionist</option>
                        <option value="Lab Technician" <?php echo $user['role'] == 'Lab Technician' ? 'selected' : ''; ?>>Lab Technician</option>
                        <option value="Radiologist" <?php echo $user['role'] == 'Radiologist' ? 'selected' : ''; ?>>Radiologist</option>
                        <option value="Pharmacist" <?php echo $user['role'] == 'Pharmacist' ? 'selected' : ''; ?>>Pharmacist</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3" id="doctor-field" style="<?php echo $user['role'] == 'Doctor' ? '' : 'display: none;'; ?>">
                <div class="col-md-12">
                    <label for="doctor_id" class="form-label <?php echo $user['role'] == 'Doctor' ? 'required-field' : ''; ?>">Select Doctor</label>
                    <select class="form-control" id="doctor_id" name="doctor_id" <?php echo $user['role'] == 'Doctor' ? 'required' : ''; ?>>
                        <option value="">Select Doctor</option>
                        <?php while ($doctor = $doctors->fetch_assoc()): ?>
                        <option value="<?php echo $doctor['doctor_id']; ?>" <?php echo $user['related_id'] == $doctor['doctor_id'] ? 'selected' : ''; ?>>
                            Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <p><strong>Note:</strong> To change password, use the "Reset Password" feature.</p>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <button type="submit" name="update_user" class="btn btn-primary me-md-2">Update User</button>
                <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const doctorField = document.getElementById('doctor-field');
    const doctorId = document.getElementById('doctor_id');
    
    roleSelect.addEventListener('change', function() {
        if (this.value === 'Doctor') {
            doctorField.style.display = 'block';
            doctorId.required = true;
        } else {
            doctorField.style.display = 'none';
            doctorId.required = false;
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>