<?php
require_once '../includes/auth.php';
checkRole('Admin');
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <h2 class="text-center my-4">Admin Dashboard</h2>
    
    <div class="row">
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='manage_doctors.php'">
                <div class="card-body text-center">
                    <i class="fas fa-user-md fa-3x mb-3"></i>
                    <h5 class="card-title">Manage Doctors</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='manage_patients.php'">
                <div class="card-body text-center">
                    <i class="fas fa-users fa-3x mb-3"></i>
                    <h5 class="card-title">Manage Patients</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-4 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='manage_users.php'">
                <div class="card-body text-center">
                    <i class="fas fa-user-cog fa-3x mb-3"></i>
                    <h5 class="card-title">Manage Users</h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='system_settings.php'">
                <div class="card-body text-center">
                    <i class="fas fa-cog fa-3x mb-3"></i>
                    <h5 class="card-title">System Settings</h5>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card dashboard-card" onclick="window.location.href='reports.php'">
                <div class="card-body text-center">
                    <i class="fas fa-chart-bar fa-3x mb-3"></i>
                    <h5 class="card-title">Reports</h5>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h4>System Statistics</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3 text-center">
                    <h5><?php echo $conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0]; ?></h5>
                    <p>Patients</p>
                </div>
                <div class="col-md-3 text-center">
                    <h5><?php echo $conn->query("SELECT COUNT(*) FROM doctors")->fetch_row()[0]; ?></h5>
                    <p>Doctors</p>
                </div>
                <div class="col-md-3 text-center">
                    <h5><?php echo $conn->query("SELECT COUNT(*) FROM visits WHERE DATE(visit_date) = CURDATE()")->fetch_row()[0]; ?></h5>
                    <p>Today's Visits</p>
                </div>
                <div class="col-md-3 text-center">
                    <h5><?php 
                        $result = $conn->query("SELECT SUM(total_amount) FROM invoices WHERE payment_status = 'Paid' AND DATE(created_at) = CURDATE()");
                        echo number_format($result->fetch_row()[0] ?? 0, 2);
                    ?></h5>
                    <p>Today's Revenue</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>