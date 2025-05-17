<?php
require_once '../includes/auth.php';
checkRole('Pharmacist');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_medication'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $cost = trim($_POST['cost']);
    $stock = trim($_POST['stock_quantity']);
    
    $stmt = $conn->prepare("INSERT INTO medications (name, description, cost_per_unit, stock_quantity) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssdi", $name, $description, $cost, $stock);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Medication added successfully!';
        header("Location: manage_inventory.php");
        exit();
    } else {
        $error = 'Error adding medication: ' . $conn->error;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_medication'])) {
    $medicationId = $_POST['medication_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $cost = trim($_POST['cost']);
    $stock = trim($_POST['stock_quantity']);
    
    $stmt = $conn->prepare("UPDATE medications SET name = ?, description = ?, cost_per_unit = ?, stock_quantity = ? WHERE medication_id = ?");
    $stmt->bind_param("ssdii", $name, $description, $cost, $stock, $medicationId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Medication updated successfully!';
        header("Location: manage_inventory.php");
        exit();
    } else {
        $error = 'Error updating medication: ' . $conn->error;
    }
}

if (isset($_GET['delete'])) {
    $medicationId = $_GET['delete'];
    
    // Check if medication is referenced in any prescriptions
    $hasPrescriptions = $conn->query("SELECT COUNT(*) as count FROM prescriptions WHERE medication_id = $medicationId")->fetch_assoc()['count'];
    
    if ($hasPrescriptions > 0) {
        $_SESSION['error'] = 'Cannot delete medication - it is referenced in existing prescriptions';
        header("Location: manage_inventory.php");
        exit();
    }
    
    $stmt = $conn->prepare("DELETE FROM medications WHERE medication_id = ?");
    $stmt->bind_param("i", $medicationId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Medication deleted successfully!';
        header("Location: manage_inventory.php");
        exit();
    } else {
        $error = 'Error deleting medication: ' . $conn->error;
    }
}

$medications = $conn->query("SELECT * FROM medications ORDER BY name");

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Manage Medication Inventory</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header">
            <h4>Add New Medication</h4>
        </div>
        <div class="card-body">
            <form method="POST" action="manage_inventory.php">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="name" class="form-label required-field">Medication Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cost" class="form-label required-field">Cost Per Unit</label>
                        <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="stock_quantity" class="form-label required-field">Initial Stock Quantity</label>
                        <input type="number" class="form-control" id="stock_quantity" name="stock_quantity" required>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" name="add_medication" class="btn btn-primary">Add Medication</button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h4>Current Inventory</h4>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Medication Name</th>
                            <th>Description</th>
                            <th>Cost</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($med = $medications->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($med['name']); ?></td>
                            <td><?php echo htmlspecialchars($med['description'] ? $med['description'] : 'N/A'); ?></td>
                            <td><?php echo number_format($med['cost_per_unit'], 2); ?></td>
                            <td>
                                <?php if ($med['stock_quantity'] <= 0): ?>
                                <span class="badge bg-danger">Out of Stock</span>
                                <?php elseif ($med['stock_quantity'] <= 5): ?>
                                <span class="badge bg-warning">Low Stock (<?php echo $med['stock_quantity']; ?>)</span>
                                <?php else: ?>
                                <span class="badge bg-success"><?php echo $med['stock_quantity']; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary edit-medication" data-id="<?php echo $med['medication_id']; ?>"
                                    data-name="<?php echo htmlspecialchars($med['name']); ?>"
                                    data-description="<?php echo htmlspecialchars($med['description']); ?>"
                                    data-cost="<?php echo $med['cost_per_unit']; ?>"
                                    data-stock="<?php echo $med['stock_quantity']; ?>">
                                    Edit
                                </button>
                                <a href="manage_inventory.php?delete=<?php echo $med['medication_id']; ?>" class="btn btn-sm btn-danger delete-btn">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Medication Modal -->
<div class="modal fade" id="editMedicationModal" tabindex="-1" aria-labelledby="editMedicationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="manage_inventory.php">
                <input type="hidden" name="medication_id" id="edit_medication_id">
                <div class="modal-header">
                    <h5 class="modal-title" id="editMedicationModalLabel">Edit Medication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label required-field">Medication Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_cost" class="form-label required-field">Cost Per Unit</label>
                        <input type="number" step="0.01" class="form-control" id="edit_cost" name="cost" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_stock" class="form-label required-field">Stock Quantity</label>
                        <input type="number" class="form-control" id="edit_stock" name="stock_quantity" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="update_medication" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit medication modal
    const editButtons = document.querySelectorAll('.edit-medication');
    const editModal = new bootstrap.Modal(document.getElementById('editMedicationModal'));
    
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('edit_medication_id').value = this.dataset.id;
            document.getElementById('edit_name').value = this.dataset.name;
            document.getElementById('edit_description').value = this.dataset.description;
            document.getElementById('edit_cost').value = this.dataset.cost;
            document.getElementById('edit_stock').value = this.dataset.stock;
            
            editModal.show();
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>