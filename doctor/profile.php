<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['doctor']);

$db = (new Database())->connect();
$doctor_id = $_SESSION['user_id'];

// Get doctor profile
$stmt = $db->prepare("
    SELECT u.*, dp.*, d.name as department_name, h.name as hospital_name
    FROM users u
    JOIN doctor_profiles dp ON u.user_id = dp.doctor_id
    JOIN departments d ON dp.dept_id = d.dept_id
    JOIN hospitals h ON d.hospital_id = h.hospital_id
    WHERE u.user_id = ?
");
$stmt->execute([$doctor_id]);
$profile = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $qualification = trim($_POST['qualification']);
    $specialization = trim($_POST['specialization']);
    $bio = trim($_POST['bio']);
    
    // Update user table
    $stmt = $db->prepare("
        UPDATE users 
        SET full_name = ?, username = ?, email = ?, phone = ?
        WHERE user_id = ?
    ");
    $stmt->execute([$full_name, $username, $email, $phone, $doctor_id]);
    
    // Update doctor profile
    $stmt = $db->prepare("
        UPDATE doctor_profiles 
        SET qualification = ?, specialization = ?, bio = ?
        WHERE doctor_id = ?
    ");
    $stmt->execute([$qualification, $specialization, $bio, $doctor_id]);
    
    $_SESSION['success'] = "Profile updated successfully!";
    header("Location: profile.php");
    exit();
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Doctor Profile</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="post" action="" class="profile-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['full_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['username']); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['email']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone">Phone</label>
                        <input type="tel" id="phone" name="phone" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['phone']); ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Hospital</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($profile['hospital_name']); ?>" readonly>
                </div>
                
                <div class="form-group">
                    <label>Department</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($profile['department_name']); ?>" readonly>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="qualification">Qualification</label>
                        <input type="text" id="qualification" name="qualification" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['qualification']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="specialization">Specialization</label>
                        <input type="text" id="specialization" name="specialization" class="form-control" 
                               value="<?php echo htmlspecialchars($profile['specialization']); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4"><?php echo htmlspecialchars($profile['bio']); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Change Password</h2>
            
            <form method="post" action="../auth/change_password.php">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Change Password</button>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>