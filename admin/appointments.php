<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['admin']);

$db = (new Database())->connect();
$admin_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get filter parameters
$status = $_GET['status'] ?? 'all';
$date = $_GET['date'] ?? '';
$doctor_id = $_GET['doctor_id'] ?? '';
$error_message = '';

$valid_statuses = ['all', 'pending', 'confirmed', 'completed', 'cancelled', 'no_show'];
$status = in_array($status, $valid_statuses) ? $status : 'all';

// Validate date format if provided
if (!empty($date)) {
    $date_parts = explode('-', $date);
    if (count($date_parts) === 3) {
        if (!checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
            $error_message = "Invalid date format. Please use YYYY-MM-DD.";
            $date = ''; // Reset date to empty if invalid
        }
    } else {
        $error_message = "Invalid date format. Please use YYYY-MM-DD.";
        $date = ''; // Reset date to empty if invalid
    }
}

// Build base query
$sql = "
    SELECT a.*, u1.full_name as doctor_name, u2.full_name as patient_name, d.name as department_name
    FROM appointments a
    JOIN users u1 ON a.doctor_id = u1.user_id
    JOIN users u2 ON a.patient_id = u2.user_id
    JOIN doctor_profiles dp ON u1.user_id = dp.doctor_id
    JOIN departments d ON dp.dept_id = d.dept_id
    WHERE u1.hospital_id = :hospital_id
";

// Add filters
if ($status !== 'all') {
    $sql .= " AND a.status = :status";
}

if (!empty($date) && empty($error_message)) {
    $sql .= " AND a.appointment_date = :date";
}

if (!empty($doctor_id)) {
    $sql .= " AND a.doctor_id = :doctor_id";
}

$sql .= " ORDER BY a.appointment_date DESC, a.start_time DESC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':hospital_id', $hospital_id);

if ($status !== 'all') {
    $stmt->bindParam(':status', $status);
}

if (!empty($date) && empty($error_message)) {
    $stmt->bindParam(':date', $date);
}

if (!empty($doctor_id)) {
    $stmt->bindParam(':doctor_id', $doctor_id);
}

$stmt->execute();
$appointments = $stmt->fetchAll();

// Get doctors for filter
$doctors = $db->query("
    SELECT u.user_id, u.full_name 
    FROM users u 
    WHERE u.hospital_id = $hospital_id AND u.user_type = 'doctor' AND u.is_active = 1
    ORDER BY u.full_name
")->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Appointments Management</h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <div class="filter-bar">
                <form method="get" action="">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="status">Status:</label>
                            <select id="status" name="status" class="form-control">
                                <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="confirmed" <?php echo $status === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                <option value="no_show" <?php echo $status === 'no_show' ? 'selected' : ''; ?>>No Show</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="date">Date:</label>
                            <input type="date" id="date" name="date" class="form-control" value="<?php echo htmlspecialchars($date); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="doctor_id">Doctor:</label>
                            <select id="doctor_id" name="doctor_id" class="form-control">
                                <option value="">All Doctors</option>
                                <?php foreach ($doctors as $doctor): ?>
                                    <option value="<?php echo $doctor['user_id']; ?>" <?php echo $doctor_id == $doctor['user_id'] ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($doctor['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="appointments.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </div>
                </form>
            </div>
            
            <?php if (empty($appointments)): ?>
                <p>No appointments found matching your criteria.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Patient</th>
                            <th>Doctor</th>
                            <th>Department</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($appointments as $appt): ?>
                        <tr>
                            <td><?php echo date('M j, Y', strtotime($appt['appointment_date'])); ?></td>
                            <td><?php echo date('h:i A', strtotime($appt['start_time'])); ?></td>
                            <td><?php echo htmlspecialchars($appt['patient_name']); ?></td>
                            <td>Dr. <?php echo htmlspecialchars($appt['doctor_name']); ?></td>
                            <td><?php echo htmlspecialchars($appt['department_name']); ?></td>
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