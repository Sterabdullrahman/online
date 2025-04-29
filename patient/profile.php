<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

$auth = new Auth();
$auth->redirectIfNotLoggedIn();
$auth->checkUserType(['patient']);

$db = (new Database())->connect();
$patient_id = $_SESSION['user_id'];

// Get patient profile
$stmt = $db->prepare("
    SELECT * FROM users 
    WHERE user_id = ? AND user_type = 'patient'
");
$stmt->execute([$patient_id]);
$profile = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $current_password = trim($_POST['current_password'] ?? '');
    $new_password = trim($_POST['new_password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');
    
    $error = '';
    
    // Basic validation
    if (empty($full_name) || empty($username) || empty($email)) {
        $error = "Full name, username and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    }
    
    // Password change validation
    if (empty($error) && !empty($current_password)) {
        if (!password_verify($current_password, $profile['password'])) {
            $error = "Current password is incorrect";
        } elseif ($new_password !== $confirm_password) {
            $error = "New passwords do not match";
        } elseif (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters";
        }
    }
    
    if (empty($error)) {
        try {
            $db->beginTransaction();
            
            // Update basic info
            $update_sql = "UPDATE users SET full_name = ?, username = ?, email = ?, phone = ?";
            $params = [$full_name, $username, $email, $phone];
            
            // Add password update if changing
            if (!empty($current_password)) {
                $update_sql .= ", password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            }
            
            $update_sql .= " WHERE user_id = ?";
            $params[] = $patient_id;
            
            $stmt = $db->prepare($update_sql);
            $stmt->execute($params);
            
            $db->commit();
            $_SESSION['success'] = "Profile updated successfully!";
            header("Location: profile.php");
            exit();
        } catch (PDOException $e) {
            $db->rollBack();
            $error = "Failed to update profile: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="dashboard">
    <?php include '../includes/sidebar.php'; ?>
    
    <div class="main-content">
        <h1>Patient Profile</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="notification success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="notification error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="card">
            <form method="POST" action="" class="profile-form">
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
                
                <h3>Change Password</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control">
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Profile</button>
            </form>
        </div>
        
        <div class="card">
            <h3>Account Information</h3>
            <div class="account-info">
                <p><strong>Account Type:</strong> Patient</p>
                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($profile['created_at'])); ?></p>
                <p><strong>Last Login:</strong> <?php echo $profile['last_login'] ? date('F j, Y g:i a', strtotime($profile['last_login'])) : 'Never'; ?></p>
            </div>
        </div>
    </div>
</div>

<style>
    .profile-form {
        margin-top: 20px;
    }
    
    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 15px;
    }
    
    .form-row .form-group {
        flex: 1;
    }
    
    .account-info p {
        margin-bottom: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .account-info p:last-child {
        border-bottom: none;
        margin-bottom: 0;
        padding-bottom: 0;
    }
</style>

<?php include '../includes/footer.php'; ?>