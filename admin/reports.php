<?php
require_once '../includes/auth.php';
checkRole('Admin');

// Default to current month
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Initialize variables to prevent undefined variable warnings
$revenueData = [
    'total_revenue' => 0,
    'paid_revenue' => 0,
    'partial_revenue' => 0,
    'pending_revenue' => 0
];

$visitStats = [
    'total_visits' => 0,
    'unique_patients' => 0,
    'doctors_consulted' => 0
];

$topServices = [];

try {
    // Get financial report data
    $revenueQuery = "SELECT SUM(total_amount) as total_revenue, 
                    SUM(CASE WHEN payment_status = 'Paid' THEN total_amount ELSE 0 END) as paid_revenue,
                    SUM(CASE WHEN payment_status = 'Partially Paid' THEN total_amount ELSE 0 END) as partial_revenue,
                    SUM(CASE WHEN payment_status = 'Pending' THEN total_amount ELSE 0 END) as pending_revenue
                    FROM invoices
                    WHERE DATE(created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($revenueQuery);
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $revenueData = $result->fetch_assoc() ?: $revenueData;
        }
        $stmt->close();
    }

    // Get visit statistics
    $visitQuery = "SELECT COUNT(*) as total_visits, 
                  COUNT(DISTINCT patient_id) as unique_patients,
                  COUNT(DISTINCT doctor_id) as doctors_consulted
                  FROM visits
                  WHERE DATE(visit_date) BETWEEN ? AND ?";
    $stmt = $conn->prepare($visitQuery);
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            $visitStats = $result->fetch_assoc() ?: $visitStats;
        }
        $stmt->close();
    }

    // Get top services
    $servicesQuery = "SELECT item_type, COUNT(*) as service_count, SUM(amount) as total_amount
                     FROM invoice_items ii
                     JOIN invoices i ON ii.invoice_id = i.invoice_id
                     WHERE DATE(i.created_at) BETWEEN ? AND ?
                     GROUP BY item_type
                     ORDER BY total_amount DESC";
    $stmt = $conn->prepare($servicesQuery);
    if ($stmt) {
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $topServices[] = $row;
            }
        }
        $stmt->close();
    }
} catch (Exception $e) {
    $error = "Error generating reports: " . $e->getMessage();
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Reports</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Report Filters</h4>
        </div>
        <div class="card-body">
            <form method="GET" action="reports.php">
                <div class="row mb-3">
                    <div class="col-md-5">
                        <label for="start_date" class="form-label">Start Date</label>
                        <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-5">
                        <label for="end_date" class="form-label">End Date</label>
                        <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4>Financial Summary</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5>Total Revenue: <?php echo number_format($revenueData['total_revenue'], 2); ?></h5>
                    </div>
                    <div class="mb-3">
                        <p>Paid: <?php echo number_format($revenueData['paid_revenue'], 2); ?></p>
                        <p>Partially Paid: <?php echo number_format($revenueData['partial_revenue'], 2); ?></p>
                        <p>Pending: <?php echo number_format($revenueData['pending_revenue'], 2); ?></p>
                    </div>
                    <div class="progress">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: <?php echo $revenueData['total_revenue'] ? ($revenueData['paid_revenue'] / $revenueData['total_revenue'] * 100) : 0; ?>%" 
                             aria-valuenow="<?php echo $revenueData['paid_revenue']; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="<?php echo $revenueData['total_revenue'] ?: 1; ?>">
                        </div>
                        <div class="progress-bar bg-warning" role="progressbar" 
                             style="width: <?php echo $revenueData['total_revenue'] ? ($revenueData['partial_revenue'] / $revenueData['total_revenue'] * 100) : 0; ?>%" 
                             aria-valuenow="<?php echo $revenueData['partial_revenue']; ?>" 
                             aria-valuemin="0" 
                             aria-valuemax="<?php echo $revenueData['total_revenue'] ?: 1; ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h4>Visit Statistics</h4>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h5>Total Visits: <?php echo $visitStats['total_visits']; ?></h5>
                    </div>
                    <div class="mb-3">
                        <p>Unique Patients: <?php echo $visitStats['unique_patients']; ?></p>
                        <p>Doctors Consulted: <?php echo $visitStats['doctors_consulted']; ?></p>
                    </div>
                    <div class="mb-3">
                        <p>Average Visits per Patient: <?php echo $visitStats['unique_patients'] ? number_format($visitStats['total_visits'] / $visitStats['unique_patients'], 1) : 0; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Top Services</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Service Type</th>
                            <th>Number of Services</th>
                            <th>Total Revenue</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topServices as $service): 
                            $percentage = $revenueData['total_revenue'] ? ($service['total_amount'] / $revenueData['total_revenue'] * 100) : 0;
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($service['item_type']); ?></td>
                            <td><?php echo $service['service_count']; ?></td>
                            <td><?php echo number_format($service['total_amount'], 2); ?></td>
                            <td>
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $percentage; ?>%" 
                                         aria-valuenow="<?php echo $percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo number_format($percentage, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="text-center">
        <a href="export_report.php?start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="btn btn-primary">
            <i class="fas fa-file-export"></i> Export Report
        </a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>