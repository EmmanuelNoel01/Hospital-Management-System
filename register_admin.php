<?php
require_once 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = 'Admin';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Username already exists.';
        } else {
            $hashedPassword = secureHash($password);
            
            $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashedPassword, $role);
            
            if ($stmt->execute()) {
                $_SESSION['success'] = 'Admin account created successfully!';
                header("Location: login.php");
                exit();
            } else {
                $error = 'Error creating account: ' . $conn->error;
            }
        }
    }
}

include 'includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Register Admin Account</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="register_admin.php">
            <div class="mb-3">
                <label for="username" class="form-label required-field">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label required-field">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Register Admin</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>