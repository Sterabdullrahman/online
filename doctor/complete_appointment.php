<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['doctor']);

$db = (new Database())->connect();
$doctor_id = $_SESSION['user_id'];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $appointment_id = $_GET['id'];

    $stmt = $db->prepare("UPDATE appointments SET status = 'completed' WHERE appointment_id = :id AND doctor_id = :doctor_id");
    $stmt->bindParam(':id', $appointment_id, PDO::PARAM_INT);
    $stmt->bindParam(':doctor_id', $doctor_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Appointment marked as completed.";
    } else {
        $_SESSION['error'] = "Failed to update appointment status.";
    }

    // Redirect back to the doctor's dashboard
    header("Location: ../doctor/dashboard.php");
    exit();

} else {
    // If no valid appointment ID is provided, redirect back with an error
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: ../doctor/dashboard.php");
    exit();
}
?>