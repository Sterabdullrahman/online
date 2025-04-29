<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php'; // Assuming you have a function to fetch user details and ratings

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['patient']);

$db = (new Database())->connect();
$patient_id = $_SESSION['user_id'];

$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    $_SESSION['error'] = "Invalid appointment ID.";
    header("Location: appointments.php"); // Redirect back to appointments list
    exit();
}

// Fetch appointment details
$stmt_appointment = $db->prepare("
    SELECT a.*, d.full_name as doctor_name, d.user_id as doctor_user_id,
           h.name as hospital_name
    FROM appointments a
    JOIN users d ON a.doctor_id = d.user_id
    JOIN hospitals h ON d.hospital_id = h.hospital_id
    WHERE a.appointment_id = ? AND a.patient_id = ?
");
$stmt_appointment->execute([$appointment_id, $patient_id]);
$appointment = $stmt_appointment->fetch();

if (!$appointment) {
    $_SESSION['error'] = "Appointment not found or does not belong to you.";
    header("Location: appointments.php"); // Redirect back to appointments list
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
            <p><strong>Doctor:</strong> <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
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
            <?php else: ?>
                <h2>Doctor Profile</h2>
                <?php
                // Fetch doctor's profile information
                $stmt_doctor_profile = $db->prepare("
                    SELECT u.*, dp.*
                    FROM users u
                    JOIN doctor_profiles dp ON u.user_id = dp.doctor_id
                    WHERE u.user_id = ?
                ");
                $stmt_doctor_profile->execute([$appointment['doctor_user_id']]);
                $doctor_profile = $stmt_doctor_profile->fetch();

                if ($doctor_profile):
                ?>
                    <div class="doctor-profile-view">
                        <div class="doctor-image">
                            <img src="../uploads/doctors/<?php echo htmlspecialchars($doctor_profile['profile_pic'] ?? 'default.jpg'); ?>"
                                 alt="Dr. <?php echo htmlspecialchars($doctor_profile['full_name']); ?>">
                        </div>
                        <div class="doctor-details">
                            <h3>Dr. <?php echo htmlspecialchars($doctor_profile['full_name']); ?></h3>
                            <p class="specialization"><?php echo htmlspecialchars($doctor_profile['specialization']); ?></p>
                            <p class="qualification"><?php echo htmlspecialchars($doctor_profile['qualification']); ?></p>
                            <?php if (!empty($doctor_profile['biography'])): ?>
                                <p class="biography"><?php echo htmlspecialchars($doctor_profile['biography']); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <h2>Doctor Rating</h2>
                    <?php
                    // Fetch doctor's average rating and review count
                    $stmt_rating = $db->prepare("
                        SELECT AVG(rating) as avg_rating, COUNT(rating_id) as review_count
                        FROM doctor_ratings
                        WHERE doctor_id = ?
                    ");
                    $stmt_rating->execute([$appointment['doctor_user_id']]);
                    $rating_info = $stmt_rating->fetch();

                    if ($rating_info):
                    ?>
                        <div class="rating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="star <?php echo $i <= round($rating_info['avg_rating']) ? 'filled' : ''; ?>">★</span>
                            <?php endfor; ?>
                            <span>(<?php echo $rating_info['review_count']; ?> reviews)</span>
                        </div>
                    <?php else: ?>
                        <p>No ratings yet for this doctor.</p>
                    <?php endif; ?>

                <?php else: ?>
                    <p class="alert alert-warning">Doctor profile information not found.</p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="action-buttons">
                <a href="appointments.php" class="btn btn-secondary">Back to Appointments</a>
                <?php if ($appointment['status'] === 'pending'): ?>
                    <a href="cancel_appointment.php?id=<?php echo $appointment['appointment_id']; ?>" class="btn btn-danger"
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

.doctor-profile-view {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    padding: 15px;
    background-color: #e9ecef;
    border-radius: 4px;
    border: 1px solid #ced4da;
}

.doctor-image {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    margin-right: 15px;
}

.doctor-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.doctor-details h3 {
    margin-top: 0;
    margin-bottom: 5px;
    color: #343a40;
}

.doctor-details p {
    margin-bottom: 5px;
    color: #6c757d;
}

.rating {
    color: #ffc107;
    font-size: 1.2em;
}

.rating .star.filled {
    color: #ffc107;
}

.action-buttons {
    margin-top: 20px;
}

.action-buttons a {
    margin-right: 10px;
}
</style>

<?php include '../includes/footer.php'; ?>