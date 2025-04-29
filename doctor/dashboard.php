<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['doctor']);

$db = (new Database())->connect();
$doctor_id = $_SESSION['user_id'];

// Get today's appointments
$today = date('Y-m-d');
$stmt = $db->prepare("
    SELECT a.*, u.full_name as patient_name 
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    WHERE a.doctor_id = :doctor_id 
    AND a.appointment_date = :today
    AND a.status IN ('confirmed')
    ORDER BY a.start_time
");
$stmt->bindParam(':doctor_id', $doctor_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$todays_appointments = $stmt->fetchAll();

// Get upcoming appointments
$stmt = $db->prepare("
    SELECT a.*, u.full_name as patient_name 
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    WHERE a.doctor_id = :doctor_id 
    AND a.appointment_date > :today
    AND a.status IN ('confirmed')
    ORDER BY a.appointment_date, a.start_time
    LIMIT 5
");
$stmt->bindParam(':doctor_id', $doctor_id);
$stmt->bindParam(':today', $today);
$stmt->execute();
$upcoming_appointments = $stmt->fetchAll();

// Get recent ratings
$stmt = $db->prepare("
    SELECT r.*, u.full_name as patient_name
    FROM doctor_ratings r
    JOIN users u ON r.patient_id = u.user_id
    WHERE r.doctor_id = :doctor_id
    ORDER BY r.created_at DESC
    LIMIT 3
");
$stmt->bindParam(':doctor_id', $doctor_id);
$stmt->execute();
$recent_ratings = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Doctor Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo count($todays_appointments); ?></h3>
                <p>Today's Appointments</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($upcoming_appointments); ?></h3>
                <p>Upcoming Appointments</p>
            </div>
            <div class="stat-card">
                <h3>
                    <?php 
                    if (!empty($recent_ratings)) {
                        $avg = array_sum(array_column($recent_ratings, 'rating')) / count($recent_ratings);
                        echo number_format($avg, 1);
                    } else {
                        echo "N/A";
                    }
                    ?>
                </h3>
                <p>Average Rating</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Today's Appointments</h2>
            
            <?php if (empty($todays_appointments)): ?>
                <p>No appointments scheduled for today.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($todays_appointments as $appt): ?>
                        <tr>
                            <td><?php echo date('h:i A', strtotime($appt['start_time'])); ?></td>
                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                            <td><?php echo ucfirst($appt['status']); ?></td>
                            <td>
                                <a href="view_appointment.php?id=<?php echo $appt['appointment_id']; ?>" class="btn btn-primary btn-sm">View</a>
                                <a href="complete_appointment.php?id=<?php echo $appt['appointment_id']; ?>" class="btn btn-success btn-sm">Complete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>Upcoming Appointments</h2>
            
            <?php if (empty($upcoming_appointments)): ?>
                <p>No upcoming appointments.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($upcoming_appointments as $appt): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($appt['start_time'])); ?></td>
                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                            <td><?php echo ucfirst($appt['status']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($recent_ratings)): ?>
        <div class="card">
            <h2>Recent Ratings</h2>
            
            <div class="ratings-container">
                <?php foreach ($recent_ratings as $rating): ?>
                <div class="rating-item">
                    <div class="rating-stars">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <span class="rating-star <?php echo $i <= $rating['rating'] ? 'active' : ''; ?>">★</span>
                        <?php endfor; ?>
                    </div>
                    <p class="rating-review"><?php echo htmlspecialchars($rating['review']); ?></p>
                    <p class="rating-meta">By <?php echo htmlspecialchars($rating['patient_name']); ?> on <?php echo date('M j, Y', strtotime($rating['created_at'])); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>