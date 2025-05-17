<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Management System</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../index.php">Hospital Management</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <?php if (isLoggedIn()): ?>
                        <?php if ($_SESSION['role'] == 'Admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../admin/dashboard.php">Admin Dashboard</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'Receptionist'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../reception/dashboard.php">Reception Dashboard</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'Doctor'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../doctor/dashboard.php">Doctor Dashboard</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'Nurse'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../triage/dashboard.php">Triage Dashboard</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'Lab Technician'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../lab/dashboard.php">Lab Dashboard</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'Radiologist'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../radiology/dashboard.php">Radiology Dashboard</a>
                            </li>
                        <?php elseif ($_SESSION['role'] == 'Pharmacist'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="../pharmacy/dashboard.php">Pharmacy Dashboard</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>)</span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="../logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="../login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">