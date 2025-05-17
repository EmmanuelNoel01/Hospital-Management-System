<?php
require_once '../includes/auth.php';
checkRole('Admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_settings'])) {
    // In a real system, you would update system settings here
    // This is just a placeholder for the functionality
    
    $_SESSION['success'] = 'System settings updated successfully!';
    header("Location: system_settings.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">System Settings</h2>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h4>General Settings</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="system_settings.php">
                <div class="mb-3">
                    <label for="hospital_name" class="form-label">Hospital Name</label>
                    <input type="text" class="form-control" id="hospital_name" name="hospital_name" value="General Hospital">
                </div>
                
                <div class="mb-3">
                    <label for="hospital_address" class="form-label">Hospital Address</label>
                    <textarea class="form-control" id="hospital_address" name="hospital_address" rows="3">123 Medical Drive, Cityville, ST 12345</textarea>
                </div>
                
                <div class="mb-3">
                    <label for="hospital_phone" class="form-label">Hospital Phone</label>
                    <input type="tel" class="form-control" id="hospital_phone" name="hospital_phone" value="(123) 456-7890">
                </div>
                
                <div class="mb-3">
                    <label for="consultation_fee" class="form-label">Default Consultation Fee</label>
                    <input type="number" step="0.01" class="form-control" id="consultation_fee" name="consultation_fee" value="50.00">
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" name="update_settings" class="btn btn-primary me-md-2">Save Settings</button>
                    <button type="reset" class="btn btn-secondary">Reset</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>