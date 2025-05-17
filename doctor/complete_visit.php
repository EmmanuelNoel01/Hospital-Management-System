<?php
require_once '../includes/auth.php';
checkRole('Doctor');

$doctorId = $_SESSION['related_id'];

if (!isset($_GET['visit_id'])) {
    header("Location: dashboard.php");
    exit();
}

$visitId = $_GET['visit_id'];

// Check if visit belongs to this doctor and is active
$visit = $conn->query("SELECT * FROM visits WHERE visit_id = $visitId AND doctor_id = $doctorId AND status = 'Active'")->fetch_assoc();
if (!$visit) {
    header("Location: dashboard.php");
    exit();
}

// Check if doctor notes exist
$notesExist = $conn->query("SELECT * FROM doctor_notes WHERE visit_id = $visitId")->fetch_assoc();
if (!$notesExist) {
    $_SESSION['error'] = 'You must record doctor notes before completing the visit';
    header("Location: record_notes.php?visit_id=$visitId");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $stmt = $conn->prepare("UPDATE visits SET status = 'Completed' WHERE visit_id = ?");
    $stmt->bind_param("i", $visitId);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'Visit marked as completed successfully!';
        header("Location: dashboard.php");
        exit();
    } else {
        $error = 'Error completing visit: ' . $conn->error;
    }
}

include '../includes/header.php';
?>

<div class="container">
    <h2 class="text-center my-4">Complete Visit</h2>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h4>Confirm Visit Completion</h4>
        </div>
        <div class="card-body">
            <p>Are you sure you want to mark this visit as completed? This action cannot be undone.</p>
            <p><strong>Visit Number:</strong> <?php echo htmlspecialchars($visit['visit_number']); ?></p>
            
            <form method="POST" action="complete_visit.php?visit_id=<?php echo $visitId; ?>">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-danger me-md-2">Yes, Complete Visit</button>
                    <a href="record_notes.php?visit_id=<?php echo $visitId; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>