<?php
require_once '../includes/auth.php';
checkRole('Receptionist');

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$start = ($page > 1) ? ($page * $perPage) - $perPage : 0;

// Get total invoices
$total = $conn->query("SELECT COUNT(*) as total FROM invoices")->fetch_assoc()['total'];
$pages = ceil($total / $perPage);

// Get invoices with pagination
$query = "SELECT i.*, v.visit_number, 
          p.first_name AS patient_first, p.last_name AS patient_last
          FROM invoices i
          JOIN visits v ON i.visit_id = v.visit_id
          JOIN patients p ON v.patient_id = p.patient_id
          ORDER BY i.created_at DESC
          LIMIT $start, $perPage";
$invoices = $conn->query($query);

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Invoices</h2>
    
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
                    <h4>All Invoices</h4>
                </div>
                <div class="col-md-6 text-end">
                    <a href="../reception/view_visits.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Visits
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Invoice ID</th>
                            <th>Visit Number</th>
                            <th>Patient</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($invoice = $invoices->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $invoice['invoice_id']; ?></td>
                            <td><?php echo htmlspecialchars($invoice['visit_number']); ?></td>
                            <td><?php echo htmlspecialchars($invoice['patient_first'] . ' ' . $invoice['patient_last']); ?></td>
                            <td><?php echo date('M j, Y h:i A', strtotime($invoice['created_at'])); ?></td>
                            <td><?php echo number_format($invoice['total_amount'], 2); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $invoice['payment_status'] == 'Paid' ? 'success' : 
                                         ($invoice['payment_status'] == 'Partially Paid' ? 'warning' : 'danger'); 
                                ?>">
                                    <?php echo $invoice['payment_status']; ?>
                                </span>
                            </td>
                            <td>
                                <a href="view_invoice_details.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <a href="print_invoice.php?id=<?php echo $invoice['invoice_id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fas fa-print"></i> Print
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