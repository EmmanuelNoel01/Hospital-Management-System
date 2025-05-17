<?php
require_once '../includes/auth.php';
checkRole('Admin');

if (!isset($_GET['id'])) {
    header("Location: manage_users.php");
    exit();
}

$userId = $_GET['id'];

// Prevent resetting own password
if ($userId == $_SESSION['user_id']) {
    $_SESSION['error'] = 'You cannot reset your own password this way';
    header("Location: manage_users.php");
    exit();
}

// Define the known password you want to set
$knownPassword = "Default@123"; // Change this to your desired known password

// Hash the known password with the secret key
$hashedPassword = hash('sha256', SECRET_KEY . $knownPassword);

// Update the password in the database
$stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
$stmt->bind_param("si", $hashedPassword, $userId);

if ($stmt->execute()) {
    // Get username for display
    $userQuery = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
    $userQuery->bind_param("i", $userId);
    $userQuery->execute();
    $userResult = $userQuery->get_result();
    $user = $userResult->fetch_assoc();
    
    $_SESSION['reset_password'] = [
        'user_id' => $userId,
        'username' => $user['username'],
        'new_password' => $knownPassword
    ];
    $_SESSION['success'] = 'Password reset successfully!';
} else {
    $_SESSION['error'] = 'Error resetting password: ' . $conn->error;
}

header("Location: manage_users.php");
exit();
?>