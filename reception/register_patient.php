<?php
require_once '../includes/auth.php';
checkRole('Receptionist');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName = trim($_POST['last_name']);
    $dob = trim($_POST['date_of_birth']);
    $gender = trim($_POST['gender']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    
    $stmt = $conn->prepare("INSERT INTO patients (first_name, last_name, date_of_birth, gender, address, phone, email) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssss", $firstName, $lastName, $dob, $gender, $address, $phone, $email);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Patient registered successfully!';
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Error registering patient: ' . $conn->error;
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Register New Patient</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST" action="register_patient.php">
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
                    <label for="date_of_birth" class="form-label required-field">Date of Birth</label>
                    <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required>
                </div>
                <div class="col-md-6">
                    <label for="gender" class="form-label required-field">Gender</label>
                    <select class="form-control" id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="phone" class="form-label required-field">Phone</label>
                    <input type="tel" class="form-control" id="phone" name="phone" required>
                </div>
                <div class="col-md-6">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email">
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary">Register Patient</button>
                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include '../includes/footer.php'; ?>