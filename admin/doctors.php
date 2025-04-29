<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['admin']);

$db = (new Database())->connect();
$admin_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];

// Get all doctors in this hospital
$stmt = $db->prepare("
    SELECT u.*, dp.qualification, dp.specialization, d.name as department_name
    FROM users u
    JOIN doctor_profiles dp ON u.user_id = dp.doctor_id
    JOIN departments d ON dp.dept_id = d.dept_id
    WHERE u.hospital_id = :hospital_id AND u.user_type = 'doctor'
    ORDER BY u.full_name
");
$stmt->bindParam(':hospital_id', $hospital_id);
$stmt->execute();
$doctors = $stmt->fetchAll();

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_doctor'])) {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);
        $dept_id = $_POST['dept_id'];
        $qualification = trim($_POST['qualification']);
        $specialization = trim($_POST['specialization']);
        $license = trim($_POST['license']);
        
        // Validate password
        if ($password !== $confirm_password) {
            $error = "Passwords do not match!";
        } elseif (strlen($password) < 8) {
            $error = "Password must be at least 8 characters long!";
        } else {
            try {
                $db->beginTransaction();
                
                // Hash the password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Insert user
                $stmt = $db->prepare("
                    INSERT INTO users 
                    (username, email, password, phone, full_name, user_type, hospital_id, created_at)
                    VALUES (?, ?, ?, ?, ?, 'doctor', ?, NOW())
                ");
                $stmt->execute([$username, $email, $password_hash, $phone, $full_name, $hospital_id]);
                $doctor_id = $db->lastInsertId();
                
                // Insert doctor profile
                $stmt = $db->prepare("
                    INSERT INTO doctor_profiles 
                    (doctor_id, dept_id, qualification, specialization, license_number, graduation_year)
                    VALUES (?, ?, ?, ?, ?, YEAR(NOW()))
                ");
                $stmt->execute([$doctor_id, $dept_id, $qualification, $specialization, $license]);
                
                $db->commit();
                $_SESSION['success'] = "Doctor added successfully!";
                header("Location: doctors.php");
                exit();
            } catch (PDOException $e) {
                $db->rollBack();
                $error = "Failed to add doctor: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['toggle_status'])) {
        $doctor_id = $_POST['doctor_id'];
        $current_status = $_POST['current_status'];
        $new_status = $current_status ? 0 : 1;
        
        $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE user_id = ? AND hospital_id = ?");
        if ($stmt->execute([$new_status, $doctor_id, $hospital_id])) {
            $_SESSION['success'] = "Doctor status updated successfully!";
        } else {
            $error = "Failed to update doctor status.";
        }
        header("Location: doctors.php");
        exit();
    }
}

// Get departments for this hospital
$departments = $db->query("SELECT * FROM departments WHERE hospital_id = $hospital_id ORDER BY name")->fetchAll();

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Manage Doctors</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Add New Doctor</h2>
            
            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" class="form-control" required minlength="8">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="dept_id">Department</label>
                        <select id="dept_id" name="dept_id" class="form-control" required>
                            <option value="">-- Select Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['dept_id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="license">License Number</label>
                        <input type="text" id="license" name="license" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="qualification">Qualification</label>
                        <input type="text" id="qualification" name="qualification" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="specialization">Specialization</label>
                        <input type="text" id="specialization" name="specialization" class="form-control" required>
                    </div>
                </div>
                
                <button type="submit" name="add_doctor" class="btn btn-primary">Add Doctor</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Doctor List</h2>
            
            <?php if (empty($doctors)): ?>
                <p>No doctors found in this hospital.</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Department</th>
                            <th>Specialization</th>
                            <th>Contact</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($doctors as $doctor): ?>
                        <tr>
                            <td>Dr. <?php echo htmlspecialchars($doctor['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['department_name']); ?></td>
                            <td><?php echo htmlspecialchars($doctor['specialization']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($doctor['email']); ?><br>
                                <?php echo htmlspecialchars($doctor['phone']); ?>
                            </td>
                            <td>
                                <span class="badge <?php echo $doctor['is_active'] ? 'badge-success' : 'badge-danger'; ?>">
                                    <?php echo $doctor['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="../doctor/profile.php?id=<?php echo $doctor['user_id']; ?>" class="btn btn-primary btn-sm">View</a>
                                
                                <form method="post" action="" style="display:inline;">
                                    <input type="hidden" name="doctor_id" value="<?php echo $doctor['user_id']; ?>">
                                    <input type="hidden" name="current_status" value="<?php echo $doctor['is_active']; ?>">
                                    <button type="submit" name="toggle_status" class="btn <?php echo $doctor['is_active'] ? 'btn-warning' : 'btn-success'; ?> btn-sm">
                                        <?php echo $doctor['is_active'] ? 'Deactivate' : 'Activate'; ?>
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