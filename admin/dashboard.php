<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['admin']);

$db = (new Database())->connect();
$admin_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get stats for dashboard
$doctors_count = $db->query("SELECT COUNT(*) FROM users WHERE hospital_id = $hospital_id AND user_type = 'doctor' AND is_active = 1")->fetchColumn();
$appointments_today = $db->query("SELECT COUNT(*) FROM appointments a JOIN users u ON a.doctor_id = u.user_id WHERE u.hospital_id = $hospital_id AND a.appointment_date = CURDATE()")->fetchColumn();
$pending_appointments = $db->query("SELECT COUNT(*) FROM appointments a JOIN users u ON a.doctor_id = u.user_id WHERE u.hospital_id = $hospital_id AND a.status = 'pending'")->fetchColumn();

// Get recent appointments
$stmt = $db->prepare("
    SELECT a.*, u1.full_name as doctor_name, u2.full_name as patient_name
    FROM appointments a
    JOIN users u1 ON a.doctor_id = u1.user_id
    JOIN users u2 ON a.patient_id = u2.user_id
    WHERE u1.hospital_id = :hospital_id
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmt->bindParam(':hospital_id', $hospital_id);
$stmt->execute();
$recent_appointments = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Admin Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $doctors_count; ?></h3>
                <p>Active Doctors</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $appointments_today; ?></h3>
                <p>Today's Appointments</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $pending_appointments; ?></h3>
                <p>Pending Appointments</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Recent Appointments</h2>
            
            <?php if (empty($recent_appointments)): ?>
                <p>No recent appointments found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Doctor</th>
                            <th>Patient</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_appointments as $appt): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($appt['start_time'])); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                            <td>
                                <span class="badge <?php 
                                    echo $appt['status'] === 'confirmed' ? 'badge-success' : 
                                         ($appt['status'] === 'pending' ? 'badge-warning' : 'badge-danger'); 
                                ?>">
                                    <?php echo ucfirst($appt['status']); ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>