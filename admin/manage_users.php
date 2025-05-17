<?php
require_once '../includes/auth.php';
checkRole('Admin');

// Function to generate a random password
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $password;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $role = trim($_POST['role']);
    $relatedId = null;
    
    // Auto-generate a strong password
    $tempPassword = generateRandomPassword();
    
    // Validate related_id based on role
    if ($role == 'Doctor') {
        $relatedId = $_POST['doctor_id'] ?? null;
    }
    
    // Check if username exists
    $check = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $check->bind_param("s", $username);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $error = 'Username already exists';
    } else {
        // Use secure hash with secret key
        $hashedPassword = hash('sha256', SECRET_KEY . $tempPassword);
        
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, related_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $username, $hashedPassword, $role, $relatedId);
        
        if ($stmt->execute()) {
            $_SESSION['new_user'] = [
                'username' => $username,
                'temp_password' => $tempPassword,
                'role' => $role
            ];
            $_SESSION['success'] = 'User added successfully!';
            header("Location: manage_users.php");
            exit();
        } else {
            $error = 'Error adding user: ' . $conn->error;
        }
    }
}

if (isset($_GET['delete'])) {
    $userId = $_GET['delete'];
    
    // Prevent deleting own account
    if ($userId == $_SESSION['user_id']) {
        $_SESSION['error'] = 'You cannot delete your own account';
        header("Location: manage_users.php");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'User deleted successfully!';
        header("Location: manage_users.php");
        exit();
    } else {
        $error = 'Error deleting user: ' . $conn->error;
    }
}

// Get all users
$users = $conn->query("SELECT u.*, 
                      CASE 
                          WHEN u.role = 'Doctor' THEN CONCAT(d.first_name, ' ', d.last_name)
                          ELSE NULL
                      END as related_name
                      FROM users u
                      LEFT JOIN doctors d ON u.related_id = d.doctor_id AND u.role = 'Doctor'
                      ORDER BY u.role, u.username");

// Get doctors for dropdown
$doctors = $conn->query("SELECT * FROM doctors ORDER BY last_name, first_name");

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Manage Users</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['new_user'])): ?>
    <div class="alert alert-info">
        <h5>New User Credentials</h5>
        <p><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['new_user']['username']); ?></p>
        <p><strong>Temporary Password:</strong> <?php echo htmlspecialchars($_SESSION['new_user']['temp_password']); ?></p>
        <p><strong>Role:</strong> <?php echo htmlspecialchars($_SESSION['new_user']['role']); ?></p>
        <p class="text-danger">This password will not be shown again. Please provide it to the user securely.</p>
    </div>
    <?php unset($_SESSION['new_user']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['reset_password'])): ?>
    <div class="alert alert-info">
        <h5>Password Reset Information</h5>
        <p><strong>User ID:</strong> <?php echo htmlspecialchars($_SESSION['reset_password']['user_id']); ?></p>
        <p><strong>New Password:</strong> <?php echo htmlspecialchars($_SESSION['reset_password']['new_password']); ?></p>
        <p class="text-danger">This password has been set for the user.</p>
    </div>
    <?php unset($_SESSION['reset_password']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Add New User</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="manage_users.php">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="username" class="form-label required-field">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="col-md-4">
                        <label for="role" class="form-label required-field">Role</label>
                        <select class="form-control" id="role" name="role" required>
                            <option value="">Select Role</option>
                            <option value="Admin">Admin</option>
                            <option value="Doctor">Doctor</option>
                            <option value="Nurse">Nurse</option>
                            <option value="Receptionist">Receptionist</option>
                            <option value="Lab Technician">Lab Technician</option>
                            <option value="Radiologist">Radiologist</option>
                            <option value="Pharmacist">Pharmacist</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Password</label>
                        <div class="form-control bg-light">Auto-generated secure password</div>
                    </div>
                </div>
                
                <div class="row mb-3" id="doctor-field" style="display: none;">
                    <div class="col-md-12">
                        <label for="doctor_id" class="form-label required-field">Select Doctor</label>
                        <select class="form-control" id="doctor_id" name="doctor_id">
                            <option value="">Select Doctor</option>
                            <?php while ($doctor = $doctors->fetch_assoc()): ?>
                            <option value="<?php echo $doctor['doctor_id']; ?>">
                                Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h4>All Users</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Related To</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($user = $users->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo $user['related_name'] ? htmlspecialchars($user['related_name']) : 'N/A'; ?></td>
                            <td>
                                <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="manage_users.php?delete=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-danger delete-btn">Delete</a>
                                <?php if ($user['role'] != 'Admin'): ?>
                                <a href="reset_password.php?id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-warning">Reset Password</a>
                                <?php endif; ?>
                                <?php else: ?>
                                <span class="text-muted">Current User</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const roleSelect = document.getElementById('role');
    const doctorField = document.getElementById('doctor-field');
    
    roleSelect.addEventListener('change', function() {
        if (this.value === 'Doctor') {
            doctorField.style.display = 'block';
            document.getElementById('doctor_id').required = true;
        } else {
            doctorField.style.display = 'none';
            document.getElementById('doctor_id').required = false;
        }
    });
    
    // Confirm before deleting
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            if (!confirm('Are you sure you want to delete this user?')) {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>