<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['super_admin']);

$db = (new Database())->connect();

// Get all hospitals with admin info
$hospitals = $db->query("
    SELECT h.*, u.full_name as admin_name, u.email as admin_email
    FROM hospitals h
    LEFT JOIN users u ON h.hospital_id = u.hospital_id AND u.user_type = 'admin'
    ORDER BY h.name
")->fetchAll();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_hospital'])) {
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $city = trim($_POST['city']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        
        try {
            $db->beginTransaction();
            
            // Insert hospital
            $stmt = $db->prepare("
                INSERT INTO hospitals 
                (name, address, city, phone, email, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$name, $address, $city, $phone, $email, $_SESSION['user_id']]);
            $hospital_id = $db->lastInsertId();
            
            // Create default department
            $stmt = $db->prepare("
                INSERT INTO departments 
                (hospital_id, name, description)
                VALUES (?, 'General', 'General department for all specialties')
            ");
            $stmt->execute([$hospital_id]);
            
            $db->commit();
            $_SESSION['success'] = "Hospital added successfully!";
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Failed to add hospital: " . $e->getMessage();
        }
    } elseif (isset($_POST['delete_hospital'])) {
        $hospital_id = $_POST['hospital_id'];
        
        try {
            $db->beginTransaction();
            
            // Delete hospital and related data
            $tables = [
                'appointments', 'medical_records', 'doctor_ratings', 
                'doctor_schedules', 'doctor_profiles', 'departments',
                'hospital_staff', 'users', 'hospitals'
            ];
            
            foreach ($tables as $table) {
                $db->exec("DELETE FROM $table WHERE hospital_id = $hospital_id");
            }
            
            $db->commit();
            $_SESSION['success'] = "Hospital and all related data deleted successfully!";
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Failed to delete hospital: " . $e->getMessage();
        }
    }
    
    header("Location: hospitals.php");
    exit();
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Manage Hospitals</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Add New Hospital</h2>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="name">Hospital Name</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="2" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="city">City</label>
                        <input type="text" id="city" name="city" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control">
                </div>
                
                <button type="submit" name="add_hospital" class="btn btn-primary">Add Hospital</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Hospital List</h2>
            
            <?php if (empty($hospitals)): ?>
                <p>No hospitals found.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>City</th>
                            <th>Contact</th>
                            <th>Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hospitals as $hospital): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($hospital['name']); ?></td>
                            <td><?php echo htmlspecialchars($hospital['city']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($hospital['phone']); ?><br>
                                <?php echo htmlspecialchars($hospital['email']); ?>
                            </td>
                            <td>
                                <?php if ($hospital['admin_name']): ?>
                                    <?php echo htmlspecialchars($hospital['admin_name']); ?><br>
                                    <?php echo htmlspecialchars($hospital['admin_email']); ?>
                                <?php else: ?>
                                    <span class="text-danger">No admin assigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="admins.php?hospital_id=<?php echo $hospital['hospital_id']; ?>" class="btn btn-primary btn-sm">Manage Admins</a>
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="hospital_id" value="<?php echo $hospital['hospital_id']; ?>">
                                    <button type="submit" name="delete_hospital" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('WARNING: This will delete ALL data for this hospital. Continue?');">
                                        Delete
                                    </button>
                                </form>
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