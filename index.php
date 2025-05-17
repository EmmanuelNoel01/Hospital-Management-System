<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    redirectBasedOnRole();
}

include 'includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-8 text-center">
        <h1 class="display-4 mb-4">Welcome to Hospital Management System</h1>
        <p class="lead">Please login to access the system</p>
        <a href="login.php" class="btn btn-primary btn-lg">Login</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>