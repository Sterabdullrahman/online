<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['doctor']);

$db = (new Database())->connect();
$doctor_id = $_SESSION['user_id'];

// Get status filter
$status = $_GET['status'] ?? 'all';
$valid_statuses = ['all', 'pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
$status = in_array($status, $valid_statuses) ? $status : 'all';

// Build query
$sql = "
    SELECT a.*, u.full_name as patient_name 
    FROM appointments a
    JOIN users u ON a.patient_id = u.user_id
    WHERE a.doctor_id = :doctor_id
";

if ($status !== 'all') {
    $sql .= " AND a.status = :status";
}

$sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':doctor_id', $doctor_id);

if ($status !== 'all') {
    $stmt->bindParam(':status', $status);
}

$stmt->execute();
$appointments = $stmt->fetchAll();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['confirm_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        
        $stmt = $db->prepare("UPDATE appointments SET status = 'confirmed' WHERE appointment_id = ? AND doctor_id = ?");
        if ($stmt->execute([$appointment_id, $doctor_id])) {
            $_SESSION['success'] = "Appointment confirmed successfully!";
        } else {
            $error = "Failed to confirm appointment.";
        }
    } elseif (isset($_POST['cancel_appointment'])) {
        $appointment_id = $_POST['appointment_id'];
        $reason = $_POST['reason'] ?? '';
        
        $stmt = $db->prepare("UPDATE appointments SET status = 'cancelled', reason = ? WHERE appointment_id = ? AND doctor_id = ?");
        if ($stmt->execute([$reason, $appointment_id, $doctor_id])) {
            $_SESSION['success'] = "Appointment cancelled successfully!";
        } else {
            $error = "Failed to cancel appointment.";
        }
    }
    
    header("Location: appointments.php?status=$status");
    exit();
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Appointments</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="filter-bar">
                <form method="get" action="">
                    <div class="form-group">
                        <label for="status">Filter by Status:</label>
                        <select id="status" name="status" class="form-control" onchange="this.form.submit()">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Appointments</option>
                            <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="no_show" <?php echo $status === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                        </select>
                    </div>
                </form>
            </div>
            
            <?php if (empty($appointments)): ?>
                <p>No appointments found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($appt['start_time'])); ?></td>
                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                            <td>
                                <span class="badge <?php 
                                    echo $appt['status'] === 'confirmed' ? 'badge-success' : 
                                         ($appt['status'] === 'pending' ? 'badge-warning' : 'badge-danger'); 
                                ?>">
                                    <?php echo ucfirst($appt['status']); ?>
                                </span>
                            </td>
                            <td>
                               
                                
                                <?php if ($appt['status'] === 'pending'): ?>
                                    <form method="post" action="" style="display:inline;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                        <button type="submit" name="confirm_appointment" class="btn btn-success btn-sm">Confirm</button>
                                    </form>
                                    
                                    <button type="button" class="btn btn-danger btn-sm" 
                                            onclick="showCancelForm(<?php echo $appt['appointment_id']; ?>)">Cancel</button>
                                    
                                    <form id="cancel-form-<?php echo $appt['appointment_id']; ?>" method="post" action="" style="display:none;">
                                        <input type="hidden" name="appointment_id" value="<?php echo $appt['appointment_id']; ?>">
                                        <div class="form-group">
                                            <label>Reason for Cancellation</label>
                                            <textarea name="reason" class="form-control" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" name="cancel_appointment" class="btn btn-danger btn-sm">Confirm Cancel</button>
                                    </form>
                                <?php elseif ($appt['status'] === 'confirmed'): ?>
                                    <a href="complete_appointment.php?id=<?php echo $appt['appointment_id']; ?>" class="btn btn-success btn-sm">Complete</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function showCancelForm(appointmentId) {
    const form = document.getElementById('cancel-form-' + appointmentId);
    form.style.display = 'block';
}
</script>

<?php include '../includes/footer.php'; ?>