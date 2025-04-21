<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['doctor']);

$db = (new Database())->connect();
$doctor_id = $_SESSION['user_id'];

$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: appointments.php"); // Redirect back to the appointments list
    exit();
}

// Fetch appointment details
$stmt_appointment = $db->prepare("
    SELECT a.*, p.full_name as patient_name, p.user_id as patient_user_id,
           h.name as hospital_name
    FROM appointments a
    JOIN users p ON a.patient_id = p.user_id
    JOIN hospitals h ON a.hospital_id = h.hospital_id -- Assuming appointments table has hospital_id
    WHERE a.appointment_id = ? AND a.doctor_id = ?
");
$stmt_appointment->execute([$appointment_id, $doctor_id]);
$appointment = $stmt_appointment->fetch();

if (!$appointment) {
    $_SESSION['error'] = "Appointment not found or does not belong to you.";
    header("Location: appointments.php"); // Redirect back to the appointments list
    exit();
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>View Appointment Details</h1>

        <div class="appointment-details">
            <p><strong>Appointment ID:</strong> <?php echo htmlspecialchars($appointment['appointment_id']); ?></p>
            <p><strong>Patient:</strong> <?php echo htmlspecialchars($appointment['patient_name']); ?></p>
            <p><strong>Hospital:</strong> <?php echo htmlspecialchars($appointment['hospital_name']); ?></p>
            <p><strong>Date:</strong> <?php echo htmlspecialchars(date('M j, Y', strtotime($appointment['appointment_date']))); ?></p>
            <p><strong>Time:</strong> <?php echo htmlspecialchars(date('h:i A', strtotime($appointment['start_time']))); ?> - <?php echo htmlspecialchars(date('h:i A', strtotime($appointment['end_time']))); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars(ucfirst($appointment['status'])); ?></p>
            <?php if (!empty($appointment['reason'])): ?>
                <p><strong>Reason for Appointment:</strong> <?php echo htmlspecialchars($appointment['reason']); ?></p>
            <?php endif; ?>

            <?php if ($appointment['status'] === 'canceled'): ?>
                <?php if (!empty($appointment['cancellation_reason'])): ?>
                    <div class="cancellation-info">
                        <h3>Cancellation Information</h3>
                        <p><strong>Reason for Cancellation:</strong> <?php echo htmlspecialchars($appointment['cancellation_reason']); ?></p>
                        <p><strong>Canceled At:</strong> <?php echo htmlspecialchars(date('M j, Y h:i A', strtotime($appointment['canceled_at']))); ?></p>
                    </div>
                <?php else: ?>
                    <p class="alert alert-warning">Cancellation reason not provided.</p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="appointments.php" class="btn btn-secondary">Back to Appointments</a>
                <?php if ($appointment['status'] === 'pending'): ?>
                    <a href="update_appointment_status.php?id=<?php echo $appointment['appointment_id']; ?>&status=confirmed" class="btn btn-success">Confirm Appointment</a>
                    <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>&role=doctor" class="btn btn-danger"
                       onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel Appointment</a>
                    <?php elseif ($appointment['status'] === 'confirmed'): ?>
                    <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>&role=doctor" class="btn btn-danger"
                       onclick="return confirm('Are you sure you want to cancel this appointment?')">Cancel Appointment</a>
                    <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.appointment-details {
    background-color: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid #e0e0e0;
}

.appointment-details p {
    margin-bottom: 10px;
}

.cancellation-info {
    margin-top: 20px;
    padding: 15px;
    background-color: #ffebee;
    border: 1px solid #ef9a9a;
    border-radius: 4px;
}

.cancellation-info h3 {
    margin-top: 0;
    color: #d32f2f;
}

.action-buttons {
    margin-top: 20px;
}

.action-buttons a {
    margin-right: 10px;
}
</style>

<?php include '../includes/footer.php'; ?>