<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['patient']);

// Database connection
$db = (new Database())->connect();
$patient_id = $_SESSION['user_id'];

// Get upcoming appointments
$stmt = $db->prepare("
    SELECT a.*, u.full_name as doctor_name, d.name as department_name, h.name as hospital_name
    FROM appointments a
    JOIN users u ON a.doctor_id = u.user_id
    JOIN doctor_profiles dp ON u.user_id = dp.doctor_id
    JOIN departments d ON dp.dept_id = d.dept_id
    JOIN hospitals h ON d.hospital_id = h.hospital_id
    WHERE a.patient_id = :patient_id AND a.status IN ('pending', 'confirmed')
    ORDER BY a.appointment_date, a.start_time
");
$stmt->bindParam(':patient_id', $patient_id);
$stmt->execute();
$appointments = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Patient Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo count($appointments); ?></h3>
                <p>Upcoming Appointments</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Upcoming Appointments</h2>
            
            <?php if (empty($appointments)): ?>
                <p>No upcoming appointments found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Hospital</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($appt['appointment_date']); ?></td>
                            <td><?php echo htmlspecialchars($appt['start_time'] . ' - ' . $appt['end_time']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($appt['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($appt['hospital_name']); ?></td>
                            <td>
                                <span class="badge <?php echo $appt['status'] === 'confirmed' ? 'badge-success' : 'badge-warning'; ?>">
                                    <?php echo ucfirst($appt['status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="cancel_appointment.php?id=<?php echo $appt['appointment_id']; ?>" class="btn btn-danger btn-sm">Cancel</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="text-right">
                <a href="book_appointment.php" class="btn btn-primary">Book New Appointment</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>