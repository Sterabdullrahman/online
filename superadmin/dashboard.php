<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['super_admin']);

$db = (new Database())->connect();

// Get stats for dashboard
$hospitals_count = $db->query("SELECT COUNT(*) FROM hospitals")->fetchColumn();
$admins_count = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'admin' AND is_active = 1")->fetchColumn();
$doctors_count = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'doctor' AND is_active = 1")->fetchColumn();
$patients_count = $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'patient' AND is_active = 1")->fetchColumn();

// Get recent hospitals
$recent_hospitals = $db->query("SELECT * FROM hospitals ORDER BY created_at DESC LIMIT 3")->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Super Admin Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $hospitals_count; ?></h3>
                <p>Hospitals</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $admins_count; ?></h3>
                <p>Admins</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $doctors_count; ?></h3>
                <p>Doctors</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $patients_count; ?></h3>
                <p>Patients</p>
            </div>
        </div>
        
        <div class="card">
            <h2>Recently Added Hospitals</h2>
            
            <?php if (empty($recent_hospitals)): ?>
                <p>No hospitals found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>City</th>
                            <th>Phone</th>
                            <th>Added On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_hospitals as $hospital): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($hospital['name']); ?></td>
                            <td><?php echo htmlspecialchars($hospital['city']); ?></td>
                            <td><?php echo htmlspecialchars($hospital['phone']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($hospital['created_at'])); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
            
            <div class="text-right">
                <a href="hospitals.php" class="btn btn-primary">View All Hospitals</a>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>